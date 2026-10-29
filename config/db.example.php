<?php
// ตัวอย่างไฟล์ตั้งค่าการเชื่อมต่อฐานข้อมูล
// วิธีใช้: ก๊อปไฟล์นี้เป็น config/db.php แล้วแก้ค่าด้านล่างเป็นของตัวเอง
// (config/db.php ถูก ignore ไว้ใน .gitignore จะไม่ถูกขึ้น GitHub)

$db_host = 'localhost';
$db_user = 'your_username';
$db_pass = 'your_password';
$db_name = 'your_database';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die('เชื่อมต่อฐานข้อมูลไม่สำเร็จ: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
