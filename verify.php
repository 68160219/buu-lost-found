<?php
// รับลิงก์ยืนยันอีเมล แล้วตั้ง is_verified = 1 — ลิงก์ใช้ได้ครั้งเดียว (ล้าง verify_token ทิ้งทันที)
require __DIR__ . '/includes/init.php';

$token = isset($_GET['token']) && is_string($_GET['token']) ? $_GET['token'] : '';
$verified = false;

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    // ฐานข้อมูลเก็บเฉพาะ hash จึงต้อง hash โทเคนจากลิงก์ก่อนเทียบ
    $token_hash = hash('sha256', $token);
    $stmt = $conn->prepare(
        'UPDATE lf_users SET is_verified = 1, verified_at = NOW(), verify_token = NULL
         WHERE verify_token = ? AND is_verified = 0'
    );
    $stmt->bind_param('s', $token_hash);
    $stmt->execute();
    $verified = $stmt->affected_rows === 1;
}

if ($verified) {
    flash('success', 'ยืนยันอีเมลเรียบร้อยแล้ว เข้าสู่ระบบได้เลย');
    redirect('login.php');
}

$page_title = 'ยืนยันอีเมล';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="alert alert-warning">
            ลิงก์ยืนยันไม่ถูกต้อง หรือถูกใช้ไปแล้ว ถ้ายังไม่ได้ยืนยันบัญชี ให้เข้าสู่ระบบด้วยรหัสนิสิตและรหัสผ่าน ระบบจะส่งลิงก์ใหม่ให้
        </div>
        <a href="login.php" class="btn btn-primary">ไปหน้าเข้าสู่ระบบ</a>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
