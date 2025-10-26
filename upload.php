<?php
require 'config/db.php';

// 强制输出JSON格式
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && isset($_POST['upload_id'])) {
    $file = $_FILES['file'];
    $uploadId = $_POST['upload_id'];
    $fileName = $file['name'];
    $fileTemp = $file['tmp_name'];
    $fileSize = $file['size'];
    $uploadIp = $_SERVER['REMOTE_ADDR']; // 记录上传IP

    // 检查文件上传是否有内置错误
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = getUploadErrorMsg($file['error']);
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        exit;
    }

    // 处理上传目录
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        // 创建目录（权限0755，生产环境更安全）
        if (!mkdir($uploadDir, 0755, true)) {
            echo json_encode(['success' => false, 'message' => '无法创建上传目录，权限不足']);
            exit;
        }
    } elseif (!is_writable($uploadDir)) {
        echo json_encode(['success' => false, 'message' => '上传目录不可写，请检查权限']);
        exit;
    }

    // 构建文件路径（用上传ID避免重名）
    $filePath = $uploadDir . $uploadId . '_' . $fileName;

    // 移动临时文件
    if (move_uploaded_file($fileTemp, $filePath)) {
        try {
            // 存入数据库（含IP）
            $stmt = $conn->prepare("INSERT INTO files (upload_id, file_name, file_path, file_size, upload_ip) 
                                  VALUES (:upload_id, :file_name, :file_path, :file_size, :upload_ip)");
            $stmt->execute([
                'upload_id' => $uploadId,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'upload_ip' => $uploadIp
            ]);
            echo json_encode(['success' => true, 'message' => '上传成功']);
        } catch (PDOException $e) {
            // 数据库失败，删除已上传文件
            if (file_exists($filePath)) unlink($filePath);
            echo json_encode(['success' => false, 'message' => '数据库存储失败: ' . $e->getMessage()]);
        }
    } else {
        // 移动失败的详细原因
        $errorMsg = '文件移动失败';
        if (file_exists($fileTemp) && !is_readable($fileTemp)) $errorMsg .= '（临时文件不可读）';
        if (is_dir($uploadDir) && !is_writable($uploadDir)) $errorMsg .= '（上传目录不可写）';
        echo json_encode(['success' => false, 'message' => $errorMsg]);
    }
} else {
    echo json_encode(['success' => false, 'message' => '请求参数不完整（缺少文件或上传ID）']);
}

// 解析文件上传错误码
function getUploadErrorMsg($errorCode) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => '文件超过php.ini限制的最大尺寸',
        UPLOAD_ERR_FORM_SIZE => '文件超过表单限制的最大尺寸',
        UPLOAD_ERR_PARTIAL => '文件仅部分上传',
        UPLOAD_ERR_NO_FILE => '未选择上传文件',
        UPLOAD_ERR_NO_TMP_DIR => '缺少临时文件目录',
        UPLOAD_ERR_CANT_WRITE => '临时文件写入失败',
        UPLOAD_ERR_EXTENSION => '文件上传被扩展程序中断'
    ];
    return $errors[$errorCode] ?? "上传错误（错误码: $errorCode）";
}
?>