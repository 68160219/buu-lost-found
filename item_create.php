<?php
// UC05 โพสต์ของที่พบ — user_id มาจาก Session
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/item_helpers.php';
require_login();

$categories = fetch_categories($conn);
$pickup_points = fetch_pickup_points($conn);

$item = [
    'title'           => '',
    'category_id'     => 0,
    'description'     => '',
    'found_location'  => '',
    'found_date'      => date('Y-m-d'),
    'return_method'   => 'contact',
    'pickup_point_id' => 0,
    'image_path'      => null,
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    list($input, $errors) = read_item_form($categories, $pickup_points);
    $item = array_merge($item, $input);

    // อัปโหลดรูปหลังจากข้อมูลอื่นถูกต้องแล้ว เพื่อไม่ให้มีไฟล์ค้างในเซิร์ฟเวอร์
    if (!$errors) {
        list($image_path, $upload_error) = handle_image_upload('image');
        if ($upload_error) {
            $errors['image'] = $upload_error;
        }
    }

    if (!$errors) {
        $uid = current_user_id();
        $description = $item['description'] === '' ? null : $item['description'];

        $stmt = $conn->prepare(
            'INSERT INTO items (user_id, category_id, title, description, found_location, found_date,
                                image_path, return_method, pickup_point_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('iissssssi', $uid, $item['category_id'], $item['title'], $description,
            $item['found_location'], $item['found_date'], $image_path, $item['return_method'], $item['pickup_point_id']);
        $stmt->execute();

        flash('success', 'โพสต์ประกาศเรียบร้อยแล้ว');
        redirect('item_view.php?id=' . $conn->insert_id);
    }
}

$page_title = 'โพสต์ของที่พบ';
$submit_label = 'โพสต์ประกาศ';
$cancel_url = 'index.php';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-3">โพสต์ของที่พบ</h1>
        <?php if ($errors): ?>
            <div class="alert alert-danger">กรุณาตรวจข้อมูลในช่องที่มีเครื่องหมายสีแดง</div>
        <?php endif; ?>
        <?php require __DIR__ . '/includes/item_form.php'; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
