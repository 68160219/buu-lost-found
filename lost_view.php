<?php
// รายละเอียดประกาศตามหาของหาย: ต้อง Login และเปิดได้เฉพาะประกาศของคณะเดียวกัน
require __DIR__ . '/includes/init.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$faculty = current_faculty();

$stmt = $conn->prepare(
    'SELECT l.*, c.name AS category_name,
            o.full_name AS owner_name, o.phone AS owner_phone, o.line_id AS owner_line
     FROM lost_items l
     JOIN categories c ON c.id = l.category_id
     JOIN users o ON o.id = l.user_id
     WHERE l.id = ? AND SUBSTRING(o.student_id, 3, 2) = ?'
);
$stmt->bind_param('is', $id, $faculty);
$stmt->execute();
$lost = $stmt->get_result()->fetch_assoc();

if (!$lost) {
    not_found('ไม่พบประกาศนี้ในคณะของคุณ อาจถูกลบไปแล้ว หรือเป็นประกาศของคณะอื่น');
}

$is_owner = current_user_id() === (int)$lost['user_id'];

$page_title = 'ตามหา: ' . $lost['title'];
require __DIR__ . '/includes/header.php';
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="lost_index.php">ตามหาของหาย</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($lost['title']) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-lg-6">
        <?php if ($lost['image_path']): ?>
            <img src="<?= e($lost['image_path']) ?>" alt="<?= e($lost['title']) ?>" class="img-fluid rounded shadow-sm w-100 item-photo">
        <?php else: ?>
            <div class="rounded item-photo item-thumb-empty">ไม่มีรูปภาพ</div>
        <?php endif; ?>
    </div>

    <div class="col-lg-6">
        <div class="small text-danger fw-semibold mb-1">ประกาศตามหาของหาย</div>
        <div class="d-flex justify-content-between align-items-start gap-2">
            <h1 class="h3 mb-1"><?= e($lost['title']) ?></h1>
            <span class="fs-6"><?= status_badge($lost['status']) ?></span>
        </div>
        <p class="text-secondary"><?= e($lost['category_name']) ?></p>

        <dl class="row mb-3">
            <dt class="col-sm-4">หายที่</dt>
            <dd class="col-sm-8"><?= e($lost['lost_location']) ?></dd>
            <dt class="col-sm-4">วันที่หาย</dt>
            <dd class="col-sm-8"><?= e(thai_date($lost['lost_date'])) ?></dd>
            <dt class="col-sm-4">โพสต์เมื่อ</dt>
            <dd class="col-sm-8"><?= e(thai_datetime($lost['created_at'])) ?></dd>
        </dl>

        <?php if ($lost['description']): ?>
            <h2 class="h6">รายละเอียด</h2>
            <p class="text-break"><?= nl2br(e($lost['description'])) ?></p>
        <?php endif; ?>

        <?php if ($lost['status'] === 'searching'): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h2 class="h6">เจอของชิ้นนี้? ติดต่อเจ้าของ</h2>
                    <p class="mb-1 fw-semibold"><?= e($lost['owner_name']) ?></p>
                    <?php if ($lost['owner_phone']): ?>
                        <p class="mb-1">โทร: <a href="tel:<?= e(preg_replace('/[^0-9]/', '', $lost['owner_phone'])) ?>"><?= e($lost['owner_phone']) ?></a></p>
                    <?php endif; ?>
                    <?php if ($lost['owner_line']): ?>
                        <p class="mb-1">LINE ID: <?= e($lost['owner_line']) ?></p>
                    <?php endif; ?>
                    <p class="small text-secondary mb-0 mt-2">
                        หรือฝากไว้ที่จุดรับคืนแล้ว<a href="item_create.php">โพสต์แจ้งของที่พบ</a> เจ้าของจะขอรับคืนผ่านระบบได้
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-secondary">
                เจ้าของได้ของคืนแล้ว<?= $lost['resolved_at'] ? ' เมื่อ ' . e(thai_datetime($lost['resolved_at'])) : '' ?> ขอบคุณทุกคนที่ช่วยตามหา
            </div>
        <?php endif; ?>

        <?php if ($is_owner): ?>
            <hr>
            <div class="d-flex flex-wrap gap-2">
                <form method="post" action="lost_resolve.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$lost['id'] ?>">
                    <?php if ($lost['status'] === 'searching'): ?>
                        <input type="hidden" name="action" value="resolve">
                        <button type="submit" class="btn btn-success btn-sm">ได้ของคืนแล้ว — ปิดประกาศ</button>
                    <?php else: ?>
                        <input type="hidden" name="action" value="reopen">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">เปิดตามหาอีกครั้ง</button>
                    <?php endif; ?>
                </form>
                <a href="lost_edit.php?id=<?= (int)$lost['id'] ?>" class="btn btn-outline-primary btn-sm">แก้ไขประกาศ</a>
                <form method="post" action="lost_delete.php" onsubmit="return confirm('ลบประกาศนี้ถาวร?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$lost['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm">ลบประกาศ</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
