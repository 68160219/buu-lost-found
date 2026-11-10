<?php
// UC02 เข้าสู่ระบบด้วยรหัสนิสิต — ต้องยืนยันอีเมลมหาวิทยาลัยแล้ว (is_verified = 1)
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/verification.php';

if (is_logged_in()) {
    redirect('index.php');
}

$next = safe_next(isset($_GET['next']) ? $_GET['next'] : post('next'));
$student_id = '';
$error = null;
$unverified = null;   // บัญชียังไม่ยืนยัน: ['email' => ..., 'sent' => bool, 'demo_link' => string|null]

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $student_id = trim(post('student_id'));
    $password = post('password');

    $stmt = $conn->prepare(
        'SELECT id, student_id, email, password_hash, full_name, is_verified FROM lf_users WHERE student_id = ?'
    );
    $stmt->bind_param('s', $student_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ((int)$user['is_verified'] === 1) {
            log_in_user($user);
            flash('success', 'ยินดีต้อนรับ ' . $user['full_name']);
            redirect($next);
        }
        // รหัสผ่านถูกแต่ยังไม่ยืนยันอีเมล: ส่งลิงก์ใหม่ให้ (ลิงก์เก่าใช้ไม่ได้แล้ว)
        list($sent, $demo_link) = send_verification($conn, (int)$user['id'], $user['student_id']);
        $unverified = ['email' => $user['email'], 'sent' => $sent, 'demo_link' => $demo_link];
    } else {
        $error = 'รหัสนิสิตหรือรหัสผ่านไม่ถูกต้อง';
    }
}

$page_title = 'เข้าสู่ระบบ';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <h1 class="h3 mb-3">เข้าสู่ระบบ</h1>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($unverified): ?>
            <div class="alert alert-warning">
                <p class="fw-semibold mb-1">บัญชีนี้ยังไม่ได้ยืนยันอีเมล จึงยังเข้าสู่ระบบไม่ได้</p>
                <?php if ($unverified['demo_link']): ?>
                    <p class="small mb-2">โหมดสาธิต: ลิงก์ยืนยันที่จะส่งไปที่ <?= e($unverified['email']) ?> คือ</p>
                    <a href="<?= e($unverified['demo_link']) ?>" class="btn btn-success btn-sm">ยืนยันอีเมล</a>
                <?php elseif ($unverified['sent']): ?>
                    <p class="small mb-0">ส่งลิงก์ยืนยันใหม่ไปที่ <?= e($unverified['email']) ?> แล้ว</p>
                <?php else: ?>
                    <p class="small mb-0">ส่งอีเมลยืนยันไม่สำเร็จ กรุณาลองใหม่ภายหลัง</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <form method="post" class="card card-body shadow-sm">
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <div class="mb-3">
                <label for="student_id" class="form-label">รหัสนิสิต</label>
                <input type="text" id="student_id" name="student_id" inputmode="numeric" maxlength="8" required autofocus
                       class="form-control" value="<?= e($student_id) ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">รหัสผ่าน</label>
                <input type="password" id="password" name="password" required autocomplete="current-password" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
            <p class="text-center mt-3 mb-0 small">ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></p>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
