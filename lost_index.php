<?php
// รายการประกาศตามหาของหายของคณะตัวเอง ค้นหาจากชื่อของ/สถานที่ และกรองตามหมวดหมู่ สถานะ
require __DIR__ . '/includes/init.php';
require_login();

$faculty = current_faculty();
$categories = fetch_categories($conn);

$q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$status = isset($_GET['status']) && in_array($_GET['status'], ['searching', 'resolved'], true) ? $_GET['status'] : '';

// ประกอบเงื่อนไข WHERE แบบ Prepared Statement — เงื่อนไขคณะใส่เสมอ ที่เหลือตามตัวกรองที่เลือก
$where = ['SUBSTRING(u.student_id, 3, 2) = ?'];
$types = 's';
$params = [$faculty];

if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(l.title LIKE ? OR l.lost_location LIKE ?)';
    $types .= 'ss';
    $params[] = $like;
    $params[] = $like;
}
if ($category_id > 0) {
    $where[] = 'l.category_id = ?';
    $types .= 'i';
    $params[] = $category_id;
}
if ($status !== '') {
    $where[] = 'l.status = ?';
    $types .= 's';
    $params[] = $status;
}

$sql = 'SELECT l.id, l.title, l.lost_location, l.lost_date, l.image_path, l.status,
               c.name AS category_name
        FROM lost_items l
        JOIN categories c ON c.id = l.category_id
        JOIN users u ON u.id = l.user_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY l.created_at DESC, l.id DESC';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$lost_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$is_filtered = $q !== '' || $category_id || $status !== '';

$page_title = 'ประกาศตามหาของหายในคณะ';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">ประกาศตามหาของหายในคณะ <span class="badge text-bg-dark align-middle fs-6">รหัสคณะ <?= e($faculty) ?></span></h1>
        <p class="text-secondary mb-0">เพื่อนในคณะกำลังตามหาของเหล่านี้ ถ้าคุณเห็นหรือเก็บได้ ติดต่อเจ้าของได้จากหน้ารายละเอียด</p>
    </div>
    <a href="lost_create.php" class="btn btn-danger">+ ตามหาของหาย</a>
</div>

<?php require __DIR__ . '/includes/post_tabs.php'; ?>

<form method="get" class="card card-body shadow-sm mb-4">
    <div class="row g-2">
        <div class="col-lg-5">
            <label for="q" class="visually-hidden">ค้นหา</label>
            <input type="search" id="q" name="q" class="form-control" value="<?= e($q) ?>" placeholder="ค้นหาชื่อของ หรือสถานที่ที่หาย">
        </div>
        <div class="col-sm-6 col-lg-3">
            <label for="category" class="visually-hidden">หมวดหมู่</label>
            <select id="category" name="category" class="form-select">
                <option value="">ทุกหมวดหมู่</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"<?= $category_id === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-lg-2">
            <label for="status" class="visually-hidden">สถานะ</label>
            <select id="status" name="status" class="form-select">
                <option value="">ทุกสถานะ</option>
                <?php foreach (['searching', 'resolved'] as $s): ?>
                    <option value="<?= $s ?>"<?= $status === $s ? ' selected' : '' ?>><?= e(status_label($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-dark flex-fill">ค้นหา</button>
            <?php if ($is_filtered): ?>
                <a href="lost_index.php" class="btn btn-outline-secondary">ล้าง</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<?php if (!$lost_items): ?>
    <div class="text-center text-secondary py-5">
        <?= $is_filtered ? 'ไม่พบประกาศที่ตรงกับการค้นหา ลองเปลี่ยนคำค้นหรือตัวกรอง' : 'ยังไม่มีประกาศตามหาของหายในคณะของคุณ' ?>
    </div>
<?php else: ?>
    <p class="text-secondary small">พบ <?= count($lost_items) ?> รายการ</p>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3">
        <?php foreach ($lost_items as $lost): ?>
            <div class="col">
                <a href="lost_view.php?id=<?= (int)$lost['id'] ?>" class="card h-100 shadow-sm item-card item-card-lost text-reset text-decoration-none">
                    <?php if ($lost['image_path']): ?>
                        <img src="<?= e($lost['image_path']) ?>" class="card-img-top item-thumb" alt="<?= e($lost['title']) ?>">
                    <?php else: ?>
                        <div class="card-img-top item-thumb item-thumb-empty">ไม่มีรูปภาพ</div>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <h2 class="h6 card-title mb-0"><?= e($lost['title']) ?></h2>
                            <?= status_badge($lost['status']) ?>
                        </div>
                        <div class="small text-secondary"><?= e($lost['category_name']) ?></div>
                        <div class="small mt-2">หายที่: <?= e($lost['lost_location']) ?></div>
                        <div class="small">วันที่: <?= e(thai_date($lost['lost_date'])) ?></div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
