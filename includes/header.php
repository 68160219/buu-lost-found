<?php
// ส่วนหัวของทุกหน้า: ตั้ง $page_title ก่อน require ไฟล์นี้
$full_title = isset($page_title) ? $page_title . ' | BUU Lost & Found' : 'BUU Lost & Found';
$current_page = basename($_SERVER['PHP_SELF']);

function nav_active($page, $current_page)
{
    return $page === $current_page ? ' active" aria-current="page' : '';
}
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($full_title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-buu">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="index.php">BUU Lost &amp; Found</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="เปิดเมนู">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <?php if (is_logged_in()): ?>
                    <li class="nav-item"><a class="nav-link<?= nav_active('index.php', $current_page) ?>" href="index.php">ประกาศในคณะ</a></li>
                    <li class="nav-item"><a class="nav-link<?= nav_active('item_create.php', $current_page) ?>" href="item_create.php">โพสต์ของที่พบ</a></li>
                    <li class="nav-item"><a class="nav-link<?= nav_active('my_items.php', $current_page) ?>" href="my_items.php">ประกาศของฉัน</a></li>
                <?php endif; ?>
            </ul>
            <?php if (is_logged_in()): ?>
                <div class="d-flex align-items-center gap-3">
                    <span class="navbar-text small"><?= e($_SESSION['full_name']) ?> (<?= e($_SESSION['student_id']) ?> · คณะ <?= e(current_faculty()) ?>)</span>
                    <form method="post" action="logout.php" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-light btn-sm">ออกจากระบบ</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="d-flex gap-2">
                    <a href="login.php" class="btn btn-outline-light btn-sm">เข้าสู่ระบบ</a>
                    <a href="register.php" class="btn btn-gold btn-sm">สมัครสมาชิก</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container py-4">
    <?php foreach (take_flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
            <?= e($f['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
        </div>
    <?php endforeach; ?>
