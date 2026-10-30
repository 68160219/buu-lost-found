<?php
// UC10 ประกาศของฉัน — แสดงเฉพาะประกาศที่ตัวเองโพสต์ และของที่ตัวเองขอรับคืน
require __DIR__ . '/includes/init.php';
require_login();

$uid = current_user_id();

$stmt = $conn->prepare(
    'SELECT i.id, i.title, i.found_date, i.status, c.name AS category_name,
            cl.full_name AS claimer_name, cl.student_id AS claimer_student_id
     FROM items i
     JOIN categories c ON c.id = i.category_id
     LEFT JOIN users cl ON cl.id = i.claimed_by
     WHERE i.user_id = ?
     ORDER BY i.created_at DESC, i.id DESC'
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$my_posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare(
    'SELECT i.id, i.title, i.status, i.returned_at, f.full_name AS finder_name
     FROM items i
     JOIN users f ON f.id = i.user_id
     WHERE i.claimed_by = ? OR i.returned_to = ?
     ORDER BY i.created_at DESC, i.id DESC'
);
$stmt->bind_param('ii', $uid, $uid);
$stmt->execute();
$my_claims = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = 'ประกาศของฉัน';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">ประกาศของฉัน</h1>
    <a href="item_create.php" class="btn btn-primary">+ โพสต์ของที่พบ</a>
</div>

<h2 class="h5 mt-4">ของที่ฉันโพสต์ (<?= count($my_posts) ?>)</h2>
<?php if (!$my_posts): ?>
    <p class="text-secondary">ยังไม่มีประกาศ</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
            <tr>
                <th>สิ่งของ</th>
                <th>หมวดหมู่</th>
                <th>วันที่พบ</th>
                <th>สถานะ</th>
                <th class="text-end">จัดการ</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($my_posts as $p): ?>
                <tr>
                    <td>
                        <a href="item_view.php?id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a>
                        <?php if ($p['status'] === 'claimed'): ?>
                            <div class="small text-warning-emphasis">ผู้ขอรับ: <?= e($p['claimer_name']) ?> (<?= e($p['claimer_student_id']) ?>) — รอคุณยืนยัน</div>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= e($p['category_name']) ?></td>
                    <td class="small text-nowrap"><?= e(thai_date($p['found_date'])) ?></td>
                    <td><?= status_badge($p['status']) ?></td>
                    <td class="text-end text-nowrap">
                        <a href="item_view.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-secondary">ดู</a>
                        <a href="item_edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary">แก้ไข</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<h2 class="h5 mt-4">ของที่ฉันขอรับคืน (<?= count($my_claims) ?>)</h2>
<?php if (!$my_claims): ?>
    <p class="text-secondary">ยังไม่มีคำขอรับคืน</p>
<?php else: ?>
    <ul class="list-group">
        <?php foreach ($my_claims as $c): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                <div>
                    <a href="item_view.php?id=<?= (int)$c['id'] ?>"><?= e($c['title']) ?></a>
                    <div class="small text-secondary">
                        ผู้โพสต์: <?= e($c['finder_name']) ?>
                        <?= $c['status'] === 'returned' && $c['returned_at'] ? ' · รับคืนเมื่อ ' . e(thai_datetime($c['returned_at'])) : '' ?>
                    </div>
                </div>
                <?= status_badge($c['status']) ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
