<?php
// init_db.php
$servername = "localhost";
$username = "root";      // 替换为你的数据库用户名
$password = "your_pass"; // 替换为你的数据库密码

try {
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 执行数据库创建SQL
    $sql = "
    CREATE DATABASE IF NOT EXISTS file_storage 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_general_ci;
    
    USE file_storage;
    
    CREATE TABLE IF NOT EXISTS files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        upload_id VARCHAR(50) NOT NULL UNIQUE,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        file_size BIGINT NOT NULL,
        upload_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        upload_ip VARCHAR(45) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE INDEX IF NOT EXISTS idx_upload_id ON files(upload_id);
    CREATE INDEX IF NOT EXISTS idx_upload_time ON files(upload_time);
    ";

    $conn->exec($sql);
    echo "数据库和表创建成功！";
} catch(PDOException $e) {
    die("执行失败: " . $e->getMessage());
}
$conn = null;
?>
