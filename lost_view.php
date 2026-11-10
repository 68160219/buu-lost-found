<?php
// รายละเอียดประกาศตามหาของหาย (lf_items.post_type = 'lost'): ต้อง Login และเปิดได้เฉพาะประกาศของคณะเดียวกัน
// ขั้นตอน: คนที่เจอกด "ฉันเจอของชิ้นนี้" (claimed_by) → เจ้าของกดยืนยันว่าได้รับแล้ว (returned_to = เจ้าของ)
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/matching.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$faculty = current_faculty();

$stmt = $conn->prepare(
    "SELECT l.*, c.name AS category_name,
            o.full_name AS owner_name, o.phone AS owner_phone, o.line_id AS owner_line,
            cl.full_name AS finder_name, cl.student_id AS finder_student_id,
            cl.phone AS finder_phone, cl.line_id AS finder_line
     FROM lf_items l
     JOIN lf_categories c ON c.id = l.category_id
     JOIN lf_users o ON o.id = l.user_id
     LEFT JOIN lf_users cl ON cl.id = l.claimed_by
     WHERE l.id = ? AND l.post_type = 'lost' AND SUBSTRING(o.student_id, 3, 2) = ?"
);
$stmt->bind_param('is', $id, $faculty);
$stmt->execute();
$lost = $stmt->get_result()->fetch_assoc();

if (!$lost) {
    not_found('ไม่พบประกาศนี้ในคณะของคุณ อาจถูกลบไปแล้ว หรือเป็นประกาศของคณะอื่น');
}

$uid = current_user_id();
$is_owner = $uid === (int)$lost['user_id'];
$is_finder = $uid === (int)$lost['claimed_by'];
$matches = find_matches($conn, (int)$lost['id'], $faculty);

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
            <span class="fs-6"><?= status_badge($lost['status'], 'lost') ?></span>
        </div>
        <p class="text-secondary"><?= e($lost['category_name']) ?></p>

        <dl class="row mb-3">
            <dt class="col-sm-4">หายที่</dt>
            <dd class="col-sm-8"><?= e($lost['found_location']) ?></dd>
            <dt class="col-sm-4">วันที่หาย</dt>
            <dd class="col-sm-8"><?= e(thai_date($lost['found_date'])) ?></dd>
            <dt class="col-sm-4">โพสต์เมื่อ</dt>
            <dd class="col-sm-8"><?= e(thai_datetime($lost['created_at'])) ?></dd>
        </dl>

        <?php if ($lost['description']): ?>
            <h2 class="h6">รายละเอียด</h2>
            <p class="text-break"><?= nl2br(e($lost['description'])) ?></p>
        <?php endif; ?>

        <?php if (!$is_owner && $lost['status'] !== 'returned'): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h2 class="h6">ติดต่อเจ้าของ</h2>
                    <p class="mb-1 fw-semibold"><?= e($lost['owner_name']) ?></p>
                    <?php if ($lost['owner_phone']): ?>
                        <p class="mb-1">โทร: <a href="tel:<?= e(preg_replace('/[^0-9]/', '', $lost['owner_phone'])) ?>"><?= e($lost['owner_phone']) ?></a></p>
                    <?php endif; ?>
                    <?php if ($lost['owner_line']): ?>
                        <p class="mb-0">LINE ID: <?= e($lost['owner_line']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- สถานะและปุ่มดำเนินการ -->
        <?php if ($lost['status'] === 'found'): ?>
            <?php if (!$is_owner): ?>
                <form method="post" action="item_claim.php" onsubmit="return confirm('ยืนยันว่าคุณเจอของชิ้นนี้ และจะติดต่อเจ้าของเพื่อคืนของ?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$lost['id'] ?>">
                    <button type="submit" class="btn btn-success">ฉันเจอของชิ้นนี้</button>
                </form>
                <p class="small text-secondary mt-2 mb-0">
                    หรือฝากไว้ที่จุดรับคืนแล้ว<a href="item_create.php">โพสต์แจ้งของที่พบ</a> เจ้าของจะขอรับคืนผ่านระบบได้
                </p>
            <?php endif; ?>

        <?php elseif ($lost['status'] === 'claimed'): ?>
            <?php if ($is_owner): ?>
                <div class="alert alert-warning">
                    <p class="fw-semibold mb-1">มีคนแจ้งว่าเจอของ: <?= e($lost['finder_name']) ?> (<?= e($lost['finder_student_id']) ?>)</p>
                    <?php if ($lost['finder_phone']): ?><p class="mb-0 small">โทร: <?= e($lost['finder_phone']) ?></p><?php endif; ?>
                    <?php if ($lost['finder_line']): ?><p class="mb-0 small">LINE ID: <?= e($lost['finder_line']) ?></p><?php endif; ?>
                    <p class="small mt-2 mb-2">ติดต่อนัดรับของ แล้วกดยืนยันเมื่อได้รับของคืน ถ้าไม่ใช่ของคุณ กดยกเลิกเพื่อเปิดประกาศต่อ</p>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="post" action="item_return.php" onsubmit="return confirm('ยืนยันว่าได้รับของคืนแล้ว?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$lost['id'] ?>">
                            <input type="hidden" name="action" value="confirm">
                            <button type="submit" class="btn btn-success btn-sm">ได้รับของคืนแล้ว</button>
                        </form>
                        <form method="post" action="item_return.php" onsubmit="return confirm('ยกเลิก? ประกาศจะกลับเป็นสถานะ กำลังตามหา');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$lost['id'] ?>">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="btn btn-outline-danger btn-sm">ไม่ใช่ของฉัน — ยกเลิก</button>
                        </form>
                    </div>
                </div>
            <?php elseif ($is_finder): ?>
                <div class="alert alert-info mb-0">คุณแจ้งว่าเจอของชิ้นนี้แล้ว ติดต่อเจ้าของเพื่อนัดคืนของ แล้วรอเจ้าของกดยืนยัน</div>
            <?php else: ?>
                <div class="alert alert-secondary mb-0">มีคนแจ้งว่าเจอของชิ้นนี้แล้ว กำลังรอเจ้าของยืนยัน</div>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-secondary mb-0">
                เจ้าของได้ของคืนแล้ว<?= $lost['returned_at'] ? ' เมื่อ ' . e(thai_datetime($lost['returned_at'])) : '' ?>
                <?php if (($is_owner || $is_finder) && $lost['finder_name']): ?>
                    <br>ผู้ที่เจอของ: <?= e($lost['finder_name']) ?> (<?= e($lost['finder_student_id']) ?>)
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($is_owner): ?>
            <hr>
            <div class="d-flex flex-wrap gap-2">
                <a href="lost_edit.php?id=<?= (int)$lost['id'] ?>" class="btn btn-outline-primary btn-sm">แก้ไขประกาศ</a>
                <form method="post" action="item_delete.php" onsubmit="return confirm('ลบประกาศนี้ถาวร?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$lost['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm">ลบประกาศ</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/match_cards.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
