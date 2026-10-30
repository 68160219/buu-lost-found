<?php
// UC02 เข้าสู่ระบบด้วยรหัสนิสิต
require __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect('index.php');
}

$next = safe_next(isset($_GET['next']) ? $_GET['next'] : post('next'));
$student_id = '';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $student_id = trim(post('student_id'));
    $password = post('password');

    $stmt = $conn->prepare('SELECT id, student_id, password_hash, full_name FROM users WHERE student_id = ?');
    $stmt->bind_param('s', $student_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        log_in_user($user);
        flash('success', 'ยินดีต้อนรับ ' . $user['full_name']);
        redirect($next);
    }
    $error = 'รหัสนิสิตหรือรหัสผ่านไม่ถูกต้อง';
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
