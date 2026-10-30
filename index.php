<?php
// UC03 หน้าแรก: แสดงประกาศของคณะตัวเอง ค้นหาจากชื่อของ/สถานที่ และกรองตามหมวดหมู่ จุดรับคืน สถานะ
require __DIR__ . '/includes/init.php';

// ผู้เยี่ยมชมยังไม่รู้ว่าอยู่คณะไหน จึงเห็นแค่หน้าแนะนำระบบ
if (!is_logged_in()) {
    $page_title = 'ระบบแจ้งของหาย-ได้คืน';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="row justify-content-center text-center py-5">
        <div class="col-lg-8">
            <h1 class="h2 mb-3">ระบบแจ้งของหาย-ได้คืน มหาวิทยาลัยบูรพา</h1>
            <p class="lead text-secondary">เจอของหรือทำของหายในคณะ? เข้าสู่ระบบด้วยรหัสนิสิตเพื่อดูประกาศของที่พบภายในคณะของคุณ</p>
            <p class="small text-secondary">ระบบแยกประกาศตามคณะจากเลขหลักที่ 3-4 ของรหัสนิสิต เช่น 68<strong>16</strong>0219 จะเห็นเฉพาะประกาศของคณะรหัส 16</p>
            <div class="d-flex justify-content-center gap-2 mt-4">
                <a href="login.php" class="btn btn-primary btn-lg">เข้าสู่ระบบ</a>
                <a href="register.php" class="btn btn-outline-secondary btn-lg">สมัครสมาชิก</a>
            </div>
        </div>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$faculty = current_faculty();
$categories = fetch_categories($conn);
$pickup_points = fetch_pickup_points($conn);

$q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$pickup_id = isset($_GET['pickup']) ? (int)$_GET['pickup'] : 0;
$status = isset($_GET['status']) && in_array($_GET['status'], ['found', 'claimed', 'returned'], true) ? $_GET['status'] : '';

// ประกอบเงื่อนไข WHERE แบบ Prepared Statement — เงื่อนไขคณะใส่เสมอ ที่เหลือตามตัวกรองที่เลือก
$where = ['SUBSTRING(u.student_id, 3, 2) = ?'];
$types = 's';
$params = [$faculty];

if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(i.title LIKE ? OR i.found_location LIKE ?)';
    $types .= 'ss';
    $params[] = $like;
    $params[] = $like;
}
if ($category_id > 0) {
    $where[] = 'i.category_id = ?';
    $types .= 'i';
    $params[] = $category_id;
}
if ($pickup_id > 0) {
    $where[] = 'i.pickup_point_id = ?';
    $types .= 'i';
    $params[] = $pickup_id;
}
if ($status !== '') {
    $where[] = 'i.status = ?';
    $types .= 's';
    $params[] = $status;
}

$sql = 'SELECT i.id, i.title, i.found_location, i.found_date, i.image_path, i.status, i.return_method,
               c.name AS category_name
        FROM items i
        JOIN categories c ON c.id = i.category_id
        JOIN users u ON u.id = i.user_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY i.created_at DESC, i.id DESC';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$is_filtered = $q !== '' || $category_id || $pickup_id || $status !== '';

$page_title = 'ประกาศของที่พบในคณะ';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">ประกาศของที่พบในคณะ <span class="badge text-bg-dark align-middle fs-6">รหัสคณะ <?= e($faculty) ?></span></h1>
        <p class="text-secondary mb-0">แสดงเฉพาะของที่คนในคณะเดียวกับคุณโพสต์ไว้ ลองค้นหาดูว่ามีคนเจอของของคุณแล้วหรือยัง</p>
    </div>
    <a href="item_create.php" class="btn btn-primary">+ โพสต์ของที่พบ</a>
</div>

<form method="get" class="card card-body shadow-sm mb-4">
    <div class="row g-2">
        <div class="col-lg-4">
            <label for="q" class="visually-hidden">ค้นหา</label>
            <input type="search" id="q" name="q" class="form-control" value="<?= e($q) ?>" placeholder="ค้นหาชื่อของ หรือสถานที่ที่พบ">
        </div>
        <div class="col-sm-4 col-lg-2">
            <label for="category" class="visually-hidden">หมวดหมู่</label>
            <select id="category" name="category" class="form-select">
                <option value="">ทุกหมวดหมู่</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"<?= $category_id === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-4 col-lg-2">
            <label for="pickup" class="visually-hidden">จุดรับคืน</label>
            <select id="pickup" name="pickup" class="form-select">
                <option value="">ทุกจุดรับคืน</option>
                <?php foreach ($pickup_points as $p): ?>
                    <option value="<?= (int)$p['id'] ?>"<?= $pickup_id === (int)$p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-4 col-lg-2">
            <label for="status" class="visually-hidden">สถานะ</label>
            <select id="status" name="status" class="form-select">
                <option value="">ทุกสถานะ</option>
                <?php foreach (['found', 'claimed', 'returned'] as $s): ?>
                    <option value="<?= $s ?>"<?= $status === $s ? ' selected' : '' ?>><?= e(status_label($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-dark flex-fill">ค้นหา</button>
            <?php if ($is_filtered): ?>
                <a href="index.php" class="btn btn-outline-secondary">ล้าง</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<?php if (!$items): ?>
    <div class="text-center text-secondary py-5">
        <?= $is_filtered ? 'ไม่พบประกาศที่ตรงกับการค้นหา ลองเปลี่ยนคำค้นหรือตัวกรอง' : 'ยังไม่มีประกาศของที่พบในคณะของคุณ' ?>
    </div>
<?php else: ?>
    <p class="text-secondary small">พบ <?= count($items) ?> รายการ</p>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3">
        <?php foreach ($items as $item): ?>
            <div class="col">
                <a href="item_view.php?id=<?= (int)$item['id'] ?>" class="card h-100 shadow-sm item-card text-reset text-decoration-none">
                    <?php if ($item['image_path']): ?>
                        <img src="<?= e($item['image_path']) ?>" class="card-img-top item-thumb" alt="<?= e($item['title']) ?>">
                    <?php else: ?>
                        <div class="card-img-top item-thumb item-thumb-empty">ไม่มีรูปภาพ</div>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <h2 class="h6 card-title mb-0"><?= e($item['title']) ?></h2>
                            <?= status_badge($item['status']) ?>
                        </div>
                        <div class="small text-secondary"><?= e($item['category_name']) ?></div>
                        <div class="small mt-2">พบที่: <?= e($item['found_location']) ?></div>
                        <div class="small">วันที่: <?= e(thai_date($item['found_date'])) ?></div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
