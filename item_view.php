<?php
// UC03 / UC04 หน้ารายละเอียดประกาศ: ต้อง Login และเปิดได้เฉพาะประกาศของคณะเดียวกัน
require __DIR__ . '/includes/init.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$faculty = current_faculty();

$stmt = $conn->prepare(
    'SELECT i.*, c.name AS category_name,
            p.name AS pickup_name, p.description AS pickup_description,
            f.full_name AS finder_name, f.phone AS finder_phone, f.line_id AS finder_line,
            cl.full_name AS claimer_name, cl.student_id AS claimer_student_id,
            cl.phone AS claimer_phone, cl.line_id AS claimer_line,
            r.full_name AS receiver_name, r.student_id AS receiver_student_id
     FROM items i
     JOIN categories c ON c.id = i.category_id
     JOIN users f ON f.id = i.user_id
     LEFT JOIN pickup_points p ON p.id = i.pickup_point_id
     LEFT JOIN users cl ON cl.id = i.claimed_by
     LEFT JOIN users r ON r.id = i.returned_to
     WHERE i.id = ? AND SUBSTRING(f.student_id, 3, 2) = ?'
);
$stmt->bind_param('is', $id, $faculty);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    not_found('ไม่พบประกาศนี้ในคณะของคุณ อาจถูกลบไปแล้ว หรือเป็นประกาศของคณะอื่น');
}

$uid = current_user_id();
$is_owner = $uid === (int)$item['user_id'];
$is_claimer = $uid === (int)$item['claimed_by'];

$page_title = $item['title'];
require __DIR__ . '/includes/header.php';
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">ประกาศทั้งหมด</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($item['title']) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-lg-6">
        <?php if ($item['image_path']): ?>
            <img src="<?= e($item['image_path']) ?>" alt="<?= e($item['title']) ?>" class="img-fluid rounded shadow-sm w-100 item-photo">
        <?php else: ?>
            <div class="rounded item-photo item-thumb-empty">ไม่มีรูปภาพ</div>
        <?php endif; ?>
    </div>

    <div class="col-lg-6">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <h1 class="h3 mb-1"><?= e($item['title']) ?></h1>
            <span class="fs-6"><?= status_badge($item['status']) ?></span>
        </div>
        <p class="text-secondary"><?= e($item['category_name']) ?></p>

        <dl class="row mb-3">
            <dt class="col-sm-4">สถานที่ที่พบ</dt>
            <dd class="col-sm-8"><?= e($item['found_location']) ?></dd>
            <dt class="col-sm-4">วันที่พบ</dt>
            <dd class="col-sm-8"><?= e(thai_date($item['found_date'])) ?></dd>
            <dt class="col-sm-4">วิธีคืน</dt>
            <dd class="col-sm-8"><?= $item['return_method'] === 'pickup' ? 'ไปรับที่จุดรับคืน' : 'ติดต่อคนที่เจอโดยตรง' ?></dd>
            <dt class="col-sm-4">โพสต์เมื่อ</dt>
            <dd class="col-sm-8"><?= e(thai_datetime($item['created_at'])) ?></dd>
        </dl>

        <?php if ($item['description']): ?>
            <h2 class="h6">รายละเอียด</h2>
            <p class="text-break"><?= nl2br(e($item['description'])) ?></p>
        <?php endif; ?>

        <!-- ข้อมูลการรับคืน -->
        <div class="card mb-3">
            <div class="card-body">
                <?php if ($item['return_method'] === 'pickup'): ?>
                    <h2 class="h6">จุดรับคืน</h2>
                    <p class="mb-0 fw-semibold"><?= e($item['pickup_name']) ?></p>
                    <?php if ($item['pickup_description']): ?>
                        <p class="mb-0 small text-secondary"><?= e($item['pickup_description']) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <h2 class="h6">ติดต่อคนที่เจอ</h2>
                    <p class="mb-1 fw-semibold"><?= e($item['finder_name']) ?></p>
                    <?php if ($item['finder_phone']): ?>
                        <p class="mb-1">โทร: <a href="tel:<?= e(preg_replace('/[^0-9]/', '', $item['finder_phone'])) ?>"><?= e($item['finder_phone']) ?></a></p>
                    <?php endif; ?>
                    <?php if ($item['finder_line']): ?>
                        <p class="mb-0">LINE ID: <?= e($item['finder_line']) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- สถานะและปุ่มดำเนินการ -->
        <?php if ($item['status'] === 'found'): ?>
            <?php if (!$is_owner): ?>
                <form method="post" action="item_claim.php" onsubmit="return confirm('ยืนยันว่านี่คือของของคุณ และต้องการขอรับคืน?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <button type="submit" class="btn btn-success">นี่คือของของฉัน — ขอรับคืน</button>
                </form>
            <?php endif; ?>

        <?php elseif ($item['status'] === 'claimed'): ?>
            <?php if ($is_owner): ?>
                <div class="alert alert-warning">
                    <p class="fw-semibold mb-1">มีผู้ขอรับคืน: <?= e($item['claimer_name']) ?> (<?= e($item['claimer_student_id']) ?>)</p>
                    <?php if ($item['claimer_phone']): ?><p class="mb-0 small">โทร: <?= e($item['claimer_phone']) ?></p><?php endif; ?>
                    <?php if ($item['claimer_line']): ?><p class="mb-0 small">LINE ID: <?= e($item['claimer_line']) ?></p><?php endif; ?>
                    <p class="small mt-2 mb-2">ตรวจสอบว่าเป็นเจ้าของจริงก่อนส่งของ แล้วกดยืนยันเมื่อคืนเรียบร้อย</p>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="post" action="item_return.php" onsubmit="return confirm('ยืนยันว่าคืนของให้ผู้ขอรับเรียบร้อยแล้ว?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="action" value="confirm">
                            <button type="submit" class="btn btn-success btn-sm">ยืนยันคืนเรียบร้อย</button>
                        </form>
                        <form method="post" action="item_return.php" onsubmit="return confirm('ยกเลิกคำขอนี้? ประกาศจะกลับเป็นสถานะ ถูกพบ');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="btn btn-outline-danger btn-sm">ไม่ใช่เจ้าของจริง — ยกเลิกคำขอ</button>
                        </form>
                    </div>
                </div>
            <?php elseif ($is_claimer): ?>
                <div class="alert alert-info mb-0">คุณส่งคำขอรับคืนแล้ว ติดต่อผู้โพสต์หรือไปรับที่จุดรับคืน แล้วรอผู้โพสต์กดยืนยัน</div>
            <?php else: ?>
                <div class="alert alert-secondary mb-0">มีผู้ขอรับคืนของชิ้นนี้แล้ว กำลังรอการยืนยัน</div>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-secondary mb-0">
                คืนเรียบร้อยแล้ว<?= $item['returned_at'] ? ' เมื่อ ' . e(thai_datetime($item['returned_at'])) : '' ?>
                <?php if (($is_owner || $uid === (int)$item['returned_to']) && $item['receiver_name']): ?>
                    <br>ผู้รับ: <?= e($item['receiver_name']) ?> (<?= e($item['receiver_student_id']) ?>)
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($is_owner): ?>
            <hr>
            <div class="d-flex gap-2">
                <a href="item_edit.php?id=<?= (int)$item['id'] ?>" class="btn btn-outline-primary btn-sm">แก้ไขประกาศ</a>
                <form method="post" action="item_delete.php" onsubmit="return confirm('ลบประกาศนี้ถาวร?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm">ลบประกาศ</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
