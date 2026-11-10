<?php
// โพสต์ประกาศตามหาของหาย (ตัวเลือกที่ 2) — บันทึกลง lf_items ด้วย post_type = 'lost', user_id มาจาก Session
// ช่อง "หายที่ไหน / วันที่หาย" ในฟอร์มเก็บลงคอลัมน์ found_location / found_date
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/item_helpers.php';
require_login();

$categories = fetch_categories($conn);

$lost = [
    'title'         => '',
    'category_id'   => 0,
    'description'   => '',
    'lost_location' => '',
    'lost_date'     => date('Y-m-d'),
    'image_path'    => null,
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    list($input, $errors) = read_lost_form($categories);
    $lost = array_merge($lost, $input);

    // อัปโหลดรูปหลังจากข้อมูลอื่นถูกต้องแล้ว เพื่อไม่ให้มีไฟล์ค้างในเซิร์ฟเวอร์
    if (!$errors) {
        list($image_path, $upload_error) = handle_image_upload('image');
        if ($upload_error) {
            $errors['image'] = $upload_error;
        }
    }

    if (!$errors) {
        $uid = current_user_id();
        $description = $lost['description'] === '' ? null : $lost['description'];

        $stmt = $conn->prepare(
            "INSERT INTO lf_items (post_type, user_id, category_id, title, description, found_location, found_date, image_path)
             VALUES ('lost', ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('iisssss', $uid, $lost['category_id'], $lost['title'], $description,
            $lost['lost_location'], $lost['lost_date'], $image_path);
        $stmt->execute();

        flash('success', 'โพสต์ประกาศตามหาของหายเรียบร้อยแล้ว');
        redirect('lost_view.php?id=' . $conn->insert_id);
    }
}

$page_title = 'ตามหาของหาย';
$submit_label = 'โพสต์ประกาศตามหา';
$cancel_url = 'post_new.php';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-3">ตามหาของหาย</h1>
        <?php if ($errors): ?>
            <div class="alert alert-danger">กรุณาตรวจข้อมูลในช่องที่มีเครื่องหมายสีแดง</div>
        <?php endif; ?>
        <?php require __DIR__ . '/includes/lost_form.php'; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
