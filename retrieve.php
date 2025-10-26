<?php
require 'config/db.php';
$message = '';

// 检查是否传入上传ID
if (isset($_GET['uploadId'])) {
    $uploadId = $_GET['uploadId'];

    // 从数据库查询对应文件信息
    try {
        $stmt = $conn->prepare("SELECT file_name, file_path FROM files WHERE upload_id = :upload_id");
        $stmt->execute(['upload_id' => $uploadId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($file) {
            $fileName = $file['file_name'];
            $filePath = $file['file_path'];

            // 验证文件是否实际存在于服务器
            if (file_exists($filePath)) {
                // 设置HTTP头，触发文件下载
                header('Content-Type: application/octet-stream');
                // 处理中文文件名乱码问题
                $encodedFileName = rawurlencode($fileName);
                header("Content-Disposition: attachment; filename*=UTF-8''$encodedFileName; filename=\"$fileName\"");
                header('Content-Length: ' . filesize($filePath));
                // 输出文件内容
                readfile($filePath);
                exit; // 终止脚本，避免额外输出干扰下载
            } else {
                $message = "文件已从服务器删除，请联系管理员。";
            }
        } else {
            $message = "未找到对应上传ID的文件，请检查ID是否正确。";
        }
    } catch (PDOException $e) {
        $message = "数据库查询失败: " . $e->getMessage();
    }
} else {
    $message = "未传入上传ID，请从文件列表页面发起下载请求。";
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>提取文件</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .container { border: 1px solid #eee; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .msg { margin: 10px 0; padding: 10px; border-radius: 4px; }
        .error { background: #f8d7da; color: #721c24; }
        .btn { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; text-decoration: none; }
    </style>
</head>
<body>
    <h1>文件存储系统-提取文件</h1>
    <?php if ($message): ?>
        <div class="msg error"><?= $message ?></div>
    <?php endif; ?>
    <div class="container">
        <a href="file_list.php" class="btn">返回文件列表</a>
    </div>
</body>
</html>