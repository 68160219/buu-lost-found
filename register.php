<?php
// UC01 สมัครสมาชิกด้วยรหัสนิสิต — นิสิต 1 คน = 1 บัญชี ต้องยืนยันผ่านอีเมลมหาวิทยาลัยก่อน Login
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/verification.php';

if (is_logged_in()) {
    redirect('index.php');
}

$form = ['student_id' => '', 'full_name' => '', 'phone' => '', 'line_id' => ''];
$errors = [];
$registered = null;   // เมื่อสมัครสำเร็จ: ['email' => ..., 'sent' => bool, 'demo_link' => string|null]

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    foreach ($form as $key => $unused) {
        $form[$key] = trim(post($key));
    }
    $password = post('password');
    $password_confirm = post('password_confirm');

    if (!preg_match('/^\d{8}$/', $form['student_id'])) {
        $errors['student_id'] = 'รหัสนิสิตต้องเป็นตัวเลข 8 หลัก';
    }
    if ($form['full_name'] === '') {
        $errors['full_name'] = 'กรุณากรอกชื่อ-นามสกุล';
    } elseif (text_length($form['full_name']) > 100) {
        $errors['full_name'] = 'ชื่อ-นามสกุลต้องไม่เกิน 100 ตัวอักษร';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    } elseif ($password !== $password_confirm) {
        $errors['password_confirm'] = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
    }
    if ($form['phone'] !== '' && !preg_match('/^[0-9\- ]{9,20}$/', $form['phone'])) {
        $errors['phone'] = 'เบอร์โทรไม่ถูกต้อง';
    }
    if (text_length($form['line_id']) > 50) {
        $errors['line_id'] = 'LINE ID ต้องไม่เกิน 50 ตัวอักษร';
    }
    // ต้องมีช่องทางติดต่ออย่างน้อย 1 ช่องทาง เพื่อใช้กับประกาศแบบ "ติดต่อคนเจอ"
    if ($form['phone'] === '' && $form['line_id'] === '' && !isset($errors['phone'])) {
        $errors['phone'] = 'กรุณากรอกเบอร์โทรหรือ LINE ID อย่างน้อย 1 ช่องทาง';
    }

    if (!$errors) {
        $stmt = $conn->prepare('SELECT id FROM lf_users WHERE student_id = ?');
        $stmt->bind_param('s', $form['student_id']);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $errors['student_id'] = 'รหัสนิสิตนี้มีบัญชีอยู่แล้ว';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $email = university_email($form['student_id']);   // สร้างจากรหัสนิสิต ผู้ใช้พิมพ์เองไม่ได้
        $phone = $form['phone'] === '' ? null : $form['phone'];
        $line_id = $form['line_id'] === '' ? null : $form['line_id'];

        $stmt = $conn->prepare(
            'INSERT INTO lf_users (student_id, email, password_hash, full_name, phone, line_id) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssssss', $form['student_id'], $email, $hash, $form['full_name'], $phone, $line_id);
        try {
            $stmt->execute();
        } catch (mysqli_sql_exception $ex) {
            if ($ex->getCode() !== 1062) {   // 1062 = ข้อมูล UNIQUE ซ้ำ (สมัครพร้อมกัน) ฐานข้อมูลปฏิเสธเอง
                throw $ex;
            }
            $errors['student_id'] = 'รหัสนิสิตนี้มีบัญชีอยู่แล้ว';
        }

        if (!$errors) {
            list($sent, $demo_link) = send_verification($conn, $conn->insert_id, $form['student_id']);
            $registered = ['email' => $email, 'sent' => $sent, 'demo_link' => $demo_link];
        }
    }
}

function input_class($errors, $name)
{
    return 'form-control' . (isset($errors[$name]) ? ' is-invalid' : '');
}

function input_error($errors, $name)
{
    return isset($errors[$name]) ? '<div class="invalid-feedback">' . e($errors[$name]) . '</div>' : '';
}

$page_title = 'สมัครสมาชิก';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <h1 class="h3 mb-3">สมัครสมาชิก</h1>
        <?php if ($registered): ?>
            <div class="card card-body shadow-sm">
                <h2 class="h5">สมัครสมาชิกสำเร็จ — ยืนยันอีเมลก่อนเข้าสู่ระบบ</h2>
                <?php if ($registered['demo_link']): ?>
                    <p>ระบบจะส่งลิงก์ยืนยันไปที่ <strong><?= e($registered['email']) ?></strong></p>
                    <div class="alert alert-warning mb-0">
                        <div class="fw-semibold mb-1">โหมดสาธิต (เซิร์ฟเวอร์ไม่ได้ส่งอีเมลจริง)</div>
                        <p class="small mb-2">ลิงก์ที่จะอยู่ในอีเมลคือลิงก์ด้านล่าง ใช้ได้ครั้งเดียว</p>
                        <a href="<?= e($registered['demo_link']) ?>" class="btn btn-success btn-sm">ยืนยันอีเมล</a>
                    </div>
                <?php elseif ($registered['sent']): ?>
                    <p class="mb-0">ส่งลิงก์ยืนยันไปที่ <strong><?= e($registered['email']) ?></strong> แล้ว กรุณาเปิดอีเมลมหาวิทยาลัยแล้วกดลิงก์ (ใช้ได้ครั้งเดียว)</p>
                <?php else: ?>
                    <div class="alert alert-danger mb-0">สร้างบัญชีแล้ว แต่ส่งอีเมลยืนยันไม่สำเร็จ ลองเข้าสู่ระบบอีกครั้งในภายหลังเพื่อขอลิงก์ใหม่</div>
                <?php endif; ?>
                <a href="login.php" class="btn btn-outline-secondary mt-3">ไปหน้าเข้าสู่ระบบ</a>
            </div>
        <?php else: ?>
        <form method="post" class="card card-body shadow-sm" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="student_id" class="form-label">รหัสนิสิต <span class="text-danger">*</span></label>
                <input type="text" id="student_id" name="student_id" inputmode="numeric" maxlength="8" required
                       class="<?= input_class($errors, 'student_id') ?>" value="<?= e($form['student_id']) ?>">
                <?= input_error($errors, 'student_id') ?>
            </div>
            <p class="form-text mt-n2">อีเมลยืนยันจะส่งไปที่ <strong>รหัสนิสิต@go.buu.ac.th</strong> โดยอัตโนมัติ (นิสิต 1 คนสมัครได้ 1 บัญชี)</p>
            <div class="mb-3">
                <label for="full_name" class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                <input type="text" id="full_name" name="full_name" maxlength="100" required
                       class="<?= input_class($errors, 'full_name') ?>" value="<?= e($form['full_name']) ?>">
                <?= input_error($errors, 'full_name') ?>
            </div>
            <div class="row">
                <div class="col-sm-6 mb-3">
                    <label for="password" class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                    <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password"
                           class="<?= input_class($errors, 'password') ?>">
                    <?= input_error($errors, 'password') ?>
                </div>
                <div class="col-sm-6 mb-3">
                    <label for="password_confirm" class="form-label">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                    <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password"
                           class="<?= input_class($errors, 'password_confirm') ?>">
                    <?= input_error($errors, 'password_confirm') ?>
                </div>
            </div>
            <p class="form-text mt-0">ช่องทางติดต่อจะแสดงเฉพาะผู้ที่ Login แล้วเท่านั้น (กรอกอย่างน้อย 1 ช่อง)</p>
            <div class="row">
                <div class="col-sm-6 mb-3">
                    <label for="phone" class="form-label">เบอร์โทร</label>
                    <input type="tel" id="phone" name="phone" maxlength="20"
                           class="<?= input_class($errors, 'phone') ?>" value="<?= e($form['phone']) ?>">
                    <?= input_error($errors, 'phone') ?>
                </div>
                <div class="col-sm-6 mb-3">
                    <label for="line_id" class="form-label">LINE ID</label>
                    <input type="text" id="line_id" name="line_id" maxlength="50"
                           class="<?= input_class($errors, 'line_id') ?>" value="<?= e($form['line_id']) ?>">
                    <?= input_error($errors, 'line_id') ?>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">สมัครสมาชิก</button>
            <p class="text-center mt-3 mb-0 small">มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
