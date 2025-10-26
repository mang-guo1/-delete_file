<?php
require 'config/db.php';
$message = '';
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>文件存储系统</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .container { border: 1px solid #eee; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .drop-area { border: 2px dashed #ccc; padding: 30px; text-align: center; margin: 20px 0; border-radius: 8px; }
        .drop-area.highlight { border-color: #007bff; }
        .btn { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .msg { margin: 10px 0; padding: 10px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .progress-container { margin: 20px 0; display: none; }
        .progress-bar { height: 20px; background: #f1f1f1; border-radius: 10px; overflow: hidden; }
        .progress { height: 100%; background: #007bff; width: 0; border-radius: 10px; transition: width 0.3s; }
    </style>
</head>
<body>
    <h1>文件存储系统</h1>

    <!-- 上传区域 -->
    <div class="container">
        <h2>上传文件</h2>
        <div class="drop-area" id="dropArea">
            拖放文件到此处或 <button class="btn" type="button" onclick="document.getElementById('fileInput').click()">选择文件</button>
            <input type="file" id="fileInput" name="file" style="display: none;" onchange="handleFileSelect()">
        </div>
        <div class="progress-container" id="progressContainer">
            <div class="progress-bar">
                <div class="progress" id="progressBar"></div>
            </div>
            <p id="progressText">上传进度: 0%</p>
        </div>
        <button class="btn" id="uploadBtn" onclick="uploadFile()" disabled>开始上传</button>
        <p id="fileInfo" style="margin-top: 10px;"></p>
    </div>

    <!-- 功能入口 -->
    <div class="container">
        <a href="file_list.php" class="btn">查看文件列表</a>
    </div>

    <script>
        let selectedFile = null;
        const dropArea = document.getElementById('dropArea');
        const fileInput = document.getElementById('fileInput');
        const uploadBtn = document.getElementById('uploadBtn');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const fileInfo = document.getElementById('fileInfo');

        // 拖放事件处理
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.classList.add('highlight');
        });
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.classList.remove('highlight');
        });
        dropArea.addEventListener('drop', handleDrop, false);
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        // 选择文件处理
        function handleFileSelect() {
            handleFiles(fileInput.files);
        }
        function handleFiles(files) {
            if (files.length === 0) return;
            selectedFile = files[0];
            fileInfo.textContent = `将上传: ${selectedFile.name} (${formatFileSize(selectedFile.size)})`;
            uploadBtn.disabled = false;
        }

        // 格式化文件大小
        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            else if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
            else if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
            else return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB';
        }

        // 生成26位随机字母数字ID
        function generateUploadId() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let id = '';
            for (let i = 0; i < 26; i++) {
                id += chars[Math.floor(Math.random() * chars.length)];
            }
            return id;
        }

        // 上传文件（修复进度条卡住问题）
        function uploadFile() {
            if (!selectedFile) return;

            const uploadId = generateUploadId();
            const formData = new FormData();
            formData.append('file', selectedFile);
            formData.append('upload_id', uploadId);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'upload.php', true);
            xhr.timeout = 300000; // 超时时间5分钟（适配大文件）

            // 进度条更新
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percent = (e.loaded / e.total) * 100;
                    progressBar.style.width = percent + '%';
                    progressText.textContent = `上传进度: ${percent.toFixed(0)}%`;
                }
            };

            // 上传完成处理（无论成功失败）
            xhr.onload = function() {
                progressContainer.style.display = 'none'; // 隐藏进度条
                try {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        // 解析JSON响应
                        let response;
                        try {
                            response = JSON.parse(xhr.responseText);
                        } catch (parseErr) {
                            throw new Error('后端响应格式错误');
                        }
                        if (response.success) {
                            fileInfo.innerHTML = `<div class="msg success">上传成功！上传ID: <strong>${uploadId}</strong></div>`;
                            uploadBtn.disabled = true;
                        } else {
                            fileInfo.innerHTML = `<div class="msg error">上传失败: ${response.message || '未知错误'}</div>`;
                        }
                    } else {
                        throw new Error(`服务器错误，状态码: ${xhr.status}`);
                    }
                } catch (err) {
                    fileInfo.innerHTML = `<div class="msg error">上传异常: ${err.message}</div>`;
                }
            };

            // 超时处理
            xhr.ontimeout = function() {
                progressContainer.style.display = 'none';
                fileInfo.innerHTML = `<div class="msg error">上传超时，请检查文件大小或网络</div>`;
            };

            // 网络错误处理
            xhr.onerror = function() {
                progressContainer.style.display = 'none';
                fileInfo.innerHTML = `<div class="msg error">网络错误，上传中断</div>`;
            };

            // 显示进度条并开始上传
            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            progressText.textContent = '上传进度: 0%';

            xhr.send(formData);
        }
    </script>
</body>
</html>