<?php
// ข้อ 3–4 นิสิต 1 คน = 1 บัญชี: ยืนยันตัวตนผ่านอีเมลมหาวิทยาลัย

// ระบบสร้างอีเมลจากรหัสนิสิตเอง ผู้ใช้พิมพ์อีเมลเองไม่ได้
function university_email($student_id)
{
    return $student_id . '@go.buu.ac.th';
}

// สร้างโทเคนใหม่ (ลิงก์เก่าใช้ไม่ได้ทันที) เก็บเฉพาะ SHA-256 ในฐานข้อมูล แล้วคืนโทเคนจริงไว้ใส่ในลิงก์
function issue_verify_token(mysqli $conn, $user_id)
{
    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);

    $stmt = $conn->prepare('UPDATE lf_users SET verify_token = ? WHERE id = ? AND is_verified = 0');
    $stmt->bind_param('si', $token_hash, $user_id);
    $stmt->execute();
    return $token;
}

function verify_link($token)
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $dir . '/verify.php?token=' . $token;
}

// ส่งลิงก์ยืนยันไปที่อีเมลมหาวิทยาลัย
// คืนค่า [ส่งสำเร็จหรือไม่, ลิงก์ที่ต้องแสดงบนจอในโหมดสาธิต หรือ null]
function send_verification(mysqli $conn, $user_id, $student_id)
{
    $link = verify_link(issue_verify_token($conn, $user_id));

    if (VERIFY_DEMO_MODE) {
        return [true, $link];
    }

    $subject = '=?UTF-8?B?' . base64_encode('ยืนยันบัญชี BUU Lost & Found') . '?=';
    $body = "กดลิงก์นี้เพื่อยืนยันบัญชี BUU Lost & Found ของรหัสนิสิต $student_id (ใช้ได้ครั้งเดียว)\r\n\r\n"
          . $link . "\r\n\r\nถ้าคุณไม่ได้สมัครสมาชิก ไม่ต้องทำอะไร";
    $headers = "Content-Type: text/plain; charset=UTF-8\r\n";

    return [@mail(university_email($student_id), $subject, $body, $headers), null];
}
