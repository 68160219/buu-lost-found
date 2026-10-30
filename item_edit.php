<?php
// UC06 แก้ไขประกาศ — ได้เฉพาะประกาศที่ user_id ตรงกับผู้ที่ Login
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/item_helpers.php';
require_login();

$uid = current_user_id();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare('SELECT * FROM items WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    not_found('ไม่พบประกาศ หรือคุณไม่มีสิทธิ์แก้ไขประกาศนี้');
}

$categories = fetch_categories($conn);
$pickup_points = fetch_pickup_points($conn);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    list($input, $errors) = read_item_form($categories, $pickup_points);
    $old_image = $item['image_path'];
    $item = array_merge($item, $input);

    $new_image = null;
    if (!$errors) {
        list($new_image, $upload_error) = handle_image_upload('image');
        if ($upload_error) {
            $errors['image'] = $upload_error;
        }
    }

    if (!$errors) {
        $image_path = $new_image ? $new_image : $old_image;
        $description = $item['description'] === '' ? null : $item['description'];

        $stmt = $conn->prepare(
            'UPDATE items
             SET category_id = ?, title = ?, description = ?, found_location = ?, found_date = ?,
                 image_path = ?, return_method = ?, pickup_point_id = ?
             WHERE id = ? AND user_id = ?'
        );
        $stmt->bind_param('issssssiii', $item['category_id'], $item['title'], $description,
            $item['found_location'], $item['found_date'], $image_path, $item['return_method'],
            $item['pickup_point_id'], $id, $uid);
        $stmt->execute();

        if ($new_image) {
            delete_image($old_image);
        }

        flash('success', 'บันทึกการแก้ไขเรียบร้อยแล้ว');
        redirect('item_view.php?id=' . $id);
    }
}

$page_title = 'แก้ไขประกาศ';
$submit_label = 'บันทึกการแก้ไข';
$cancel_url = 'item_view.php?id=' . $id;
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-3">แก้ไขประกาศ</h1>
        <?php if ($errors): ?>
            <div class="alert alert-danger">กรุณาตรวจข้อมูลในช่องที่มีเครื่องหมายสีแดง</div>
        <?php endif; ?>
        <?php require __DIR__ . '/includes/item_form.php'; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
