<?php
// UC10 ประกาศของฉัน — แสดงเฉพาะประกาศที่ตัวเองโพสต์ (ตามหาของหาย / ของที่พบ)
// และประกาศของคนอื่นที่ตัวเองกดขอรับคืนหรือแจ้งว่าเจอ
require __DIR__ . '/includes/init.php';
require_login();

$uid = current_user_id();

// ประกาศที่ฉันโพสต์ ทั้ง 2 แบบ แล้วแยกตาม post_type
$stmt = $conn->prepare(
    'SELECT i.id, i.post_type, i.title, i.found_date, i.status, c.name AS category_name,
            cl.full_name AS claimer_name, cl.student_id AS claimer_student_id
     FROM lf_items i
     JOIN lf_categories c ON c.id = i.category_id
     LEFT JOIN lf_users cl ON cl.id = i.claimed_by
     WHERE i.user_id = ?
     ORDER BY i.created_at DESC, i.id DESC'
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$my_lost = [];
$my_posts = [];
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
    if ($row['post_type'] === 'lost') {
        $my_lost[] = $row;
    } else {
        $my_posts[] = $row;
    }
}

// ของที่พบที่ฉันขอรับคืน / ได้รับคืน และประกาศตามหาที่ฉันแจ้งว่าเจอ
$stmt = $conn->prepare(
    "SELECT i.id, i.post_type, i.title, i.status, i.returned_at, p.full_name AS poster_name
     FROM lf_items i
     JOIN lf_users p ON p.id = i.user_id
     WHERE (i.post_type = 'found' AND (i.claimed_by = ? OR i.returned_to = ?))
        OR (i.post_type = 'lost' AND i.claimed_by = ?)
     ORDER BY i.created_at DESC, i.id DESC"
);
$stmt->bind_param('iii', $uid, $uid, $uid);
$stmt->execute();
$my_claims = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ตารางประกาศที่ฉันโพสต์ ใช้ร่วมกันทั้ง 2 แบบ
function render_my_posts(array $rows, $post_type)
{
    $is_lost = $post_type === 'lost';
    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
            <tr>
                <th>สิ่งของ</th>
                <th>หมวดหมู่</th>
                <th><?= $is_lost ? 'วันที่หาย' : 'วันที่พบ' ?></th>
                <th>สถานะ</th>
                <th class="text-end">จัดการ</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $p): ?>
                <tr>
                    <td>
                        <a href="<?= e(view_url($post_type, $p['id'])) ?>"><?= e($p['title']) ?></a>
                        <?php if ($p['status'] === 'claimed'): ?>
                            <div class="small text-warning-emphasis">
                                <?= $is_lost ? 'ผู้ที่แจ้งว่าเจอ' : 'ผู้ขอรับ' ?>: <?= e($p['claimer_name']) ?> (<?= e($p['claimer_student_id']) ?>) — รอคุณยืนยัน
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= e($p['category_name']) ?></td>
                    <td class="small text-nowrap"><?= e(thai_date($p['found_date'])) ?></td>
                    <td><?= status_badge($p['status'], $post_type) ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= e(view_url($post_type, $p['id'])) ?>" class="btn btn-sm btn-outline-secondary">ดู</a>
                        <a href="<?= $is_lost ? 'lost_edit.php' : 'item_edit.php' ?>?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary">แก้ไข</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

$page_title = 'ประกาศของฉัน';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">ประกาศของฉัน</h1>
    <a href="post_new.php" class="btn btn-primary">+ โพสต์ประกาศ</a>
</div>

<h2 class="h5 mt-4">ของที่ฉันตามหา (<?= count($my_lost) ?>)</h2>
<?php if (!$my_lost): ?>
    <p class="text-secondary">ยังไม่มีประกาศตามหาของหาย</p>
<?php else: ?>
    <?php render_my_posts($my_lost, 'lost'); ?>
<?php endif; ?>

<h2 class="h5 mt-4">ของที่ฉันเจอและโพสต์แจ้ง (<?= count($my_posts) ?>)</h2>
<?php if (!$my_posts): ?>
    <p class="text-secondary">ยังไม่มีประกาศ</p>
<?php else: ?>
    <?php render_my_posts($my_posts, 'found'); ?>
<?php endif; ?>

<h2 class="h5 mt-4">ของที่ฉันขอรับคืน / แจ้งว่าเจอ (<?= count($my_claims) ?>)</h2>
<?php if (!$my_claims): ?>
    <p class="text-secondary">ยังไม่มีรายการ</p>
<?php else: ?>
    <ul class="list-group">
        <?php foreach ($my_claims as $c): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                <div>
                    <a href="<?= e(view_url($c['post_type'], $c['id'])) ?>"><?= e($c['title']) ?></a>
                    <div class="small text-secondary">
                        <?= $c['post_type'] === 'lost' ? 'ฉันแจ้งว่าเจอ · เจ้าของ' : 'ฉันขอรับคืน · ผู้โพสต์' ?>: <?= e($c['poster_name']) ?>
                        <?= $c['status'] === 'returned' && $c['returned_at'] ? ' · คืนเมื่อ ' . e(thai_datetime($c['returned_at'])) : '' ?>
                    </div>
                </div>
                <?= status_badge($c['status'], $c['post_type']) ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
