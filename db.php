<?php
$servername = "localhost";
$username = "file_storage";      // 数据库用户名
$password = "your_pass"; // 数据库密码
$dbname = "file_storage";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}