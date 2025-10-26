<?php
require 'config/db.php';
$message = '';
$fileList = [];

// 获取文件列表（不含upload_id，保护隐私）
try {
    $stmt = $conn->query("SELECT id, file_name, file_size, upload_ip, upload_time FROM files ORDER BY upload_time DESC");
    $fileList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "获取文件列表失败: " . $e->getMessage();
}

// 格式化文件大小的辅助函数
function formatFileSize($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    elseif ($bytes < 1024 * 1024) return number_format($bytes / 1024, 2) . ' KB';
    elseif ($bytes < 1024 * 1024 * 1024) return number_format($bytes / (1024 * 1024), 2) . ' MB';
    else return number_format($bytes / (1024 * 1024 * 1024), 2) . ' GB';
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>文件列表</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
        .container { border: 1px solid #eee; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f2f2f2; }
        .msg { margin: 10px 0; padding: 10px; border-radius: 4px; }
        .error { background: #f8d7da; color: #721c24; }
        .success { background: #d4edda; color: #155724; }
        .btn { border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn-download { background: #007bff; color: white; }
        .btn-delete { background: #dc3545; color: white; margin-left: 5px; }
        .btn-back { background: #007bff; color: white; padding: 10px 20px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100; }
        .modal-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; width: 300px; }
    </style>
</head>
<body>
    <h1>文件存储系统-文件列表</h1>

    <div class="container">
        <?php if ($message): ?>
            <div class="msg error"><?= $message ?></div>
        <?php endif; ?>

        <?php if (empty($fileList)): ?>
            <p>暂无上传文件</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <<th>文件名</</th>
                        <<th>文件大小</</th>
                        <<th>上传IP</</th>
                        <<th>上传时间</</th>
                        <<th>操作</</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fileList as $file): ?>
                        <tr>
                            <td><?= htmlspecialchars($file['file_name']) ?></td>
                            <td><?= formatFileSize($file['file_size']) ?></td>
                            <td><?= htmlspecialchars($file['upload_ip']) ?></td>
                            <td><?= $file['upload_time'] ?></td>
                            <td>
                                <button class="btn btn-download" onclick="showDownloadModal()">下载</button>
                                <button class="btn btn-delete" onclick="showDeleteModal()">删除</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="container">
        <a href="index.php" class="btn btn-back">返回上传页面</a>
    </div>

    <!-- 下载模态框 -->
    <div id="downloadModal" class="modal">
        <div class="modal-content">
            <h3>输入上传ID下载文件</h3>
            <p style="color: #666; font-size: 14px;">请输入文件上传时生成的26位ID</p>
            <input type="text" id="downloadUploadId" placeholder="例如：aB3dEf5GhIj7Kl9Mn1Op3Qr5St" style="width: 100%; padding: 10px; margin: 10px 0;">
            <button class="btn btn-download" onclick="confirmDownload()">确认下载</button>
            <button class="btn" style="background: #6c757d; margin-left: 10px;" onclick="hideModal('downloadModal')">取消</button>
        </div>
    </div>

    <!-- 删除模态框 -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>输入上传ID确认删除</h3>
            <p style="color: #dc3545; font-size: 14px;">警告：删除后文件无法恢复，请谨慎操作！</p>
            <input type="text" id="deleteUploadId" placeholder="请输入文件对应的26位上传ID" style="width: 100%; padding: 10px; margin: 10px 0;">
            <button class="btn btn-delete" onclick="confirmDelete()">确认删除</button>
            <button class="btn" style="background: #6c757d; margin-left: 10px;" onclick="hideModal('deleteModal')">取消</button>
        </div>
    </div>

    <!-- 提示信息框 -->
    <div id="alertModal" class="modal">
        <div class="modal-content">
            <p id="alertMsg" style="font-size: 16px; margin-bottom: 20px;"></p>
            <button class="btn btn-download" onclick="hideModal('alertModal')">确定</button>
        </div>
    </div>

    <script>
        // 显示模态框
        function showDownloadModal() {
            document.getElementById('downloadUploadId').value = '';
            document.getElementById('downloadModal').style.display = 'block';
        }
        function showDeleteModal() {
            document.getElementById('deleteUploadId').value = '';
            document.getElementById('deleteModal').style.display = 'block';
        }
        // 隐藏模态框
        function hideModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        // 显示提示信息
        function showAlert(msg, isSuccess = true) {
            const alertMsg = document.getElementById('alertMsg');
            alertMsg.textContent = msg;
            alertMsg.style.color = isSuccess ? '#155724' : '#721c24';
            document.getElementById('alertModal').style.display = 'block';
        }

        // 确认下载
        function confirmDownload() {
            const uploadId = document.getElementById('downloadUploadId').value.trim();
            if (uploadId) {
                window.location.href = 'retrieve.php?uploadId=' + uploadId;
            } else {
                alert('请输入上传ID');
            }
        }

        // 确认删除（异步请求）
        function confirmDelete() {
            const uploadId = document.getElementById('deleteUploadId').value.trim();
            if (!uploadId) {
                alert('请输入上传ID');
                return;
            }

            // 发送删除请求
            fetch('delete_file.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ uploadId: uploadId })
            })
            .then(response => response.json())
            .then(data => {
                hideModal('deleteModal');
                showAlert(data.message, data.success);
                // 删除成功后刷新页面
                if (data.success) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            })
            .catch(error => {
                hideModal('deleteModal');
                showAlert('删除请求失败: ' + error, false);
            });
        }
    </script>
</body>
</html>