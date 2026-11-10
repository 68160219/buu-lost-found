<?php
// แก้ไขประกาศตามหาของหาย — ได้เฉพาะประกาศที่ user_id ตรงกับผู้ที่ Login
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/item_helpers.php';
require_login();

$uid = current_user_id();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare(
    "SELECT id, category_id, title, description, image_path,
            found_location AS lost_location, found_date AS lost_date
     FROM lf_items WHERE id = ? AND user_id = ? AND post_type = 'lost'"
);
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$lost = $stmt->get_result()->fetch_assoc();

if (!$lost) {
    not_found('ไม่พบประกาศ หรือคุณไม่มีสิทธิ์แก้ไขประกาศนี้');
}

$categories = fetch_categories($conn);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    list($input, $errors) = read_lost_form($categories);
    $old_image = $lost['image_path'];
    $lost = array_merge($lost, $input);

    $new_image = null;
    if (!$errors) {
        list($new_image, $upload_error) = handle_image_upload('image');
        if ($upload_error) {
            $errors['image'] = $upload_error;
        }
    }

    if (!$errors) {
        $image_path = $new_image ? $new_image : $old_image;
        $description = $lost['description'] === '' ? null : $lost['description'];

        $stmt = $conn->prepare(
            "UPDATE lf_items
             SET category_id = ?, title = ?, description = ?, found_location = ?, found_date = ?, image_path = ?
             WHERE id = ? AND user_id = ? AND post_type = 'lost'"
        );
        $stmt->bind_param('isssssii', $lost['category_id'], $lost['title'], $description,
            $lost['lost_location'], $lost['lost_date'], $image_path, $id, $uid);
        $stmt->execute();

        if ($new_image) {
            delete_image($old_image);
        }

        flash('success', 'บันทึกการแก้ไขเรียบร้อยแล้ว');
        redirect('lost_view.php?id=' . $id);
    }
}

$page_title = 'แก้ไขประกาศตามหาของหาย';
$submit_label = 'บันทึกการแก้ไข';
$cancel_url = 'lost_view.php?id=' . $id;
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-3">แก้ไขประกาศตามหาของหาย</h1>
        <?php if ($errors): ?>
            <div class="alert alert-danger">กรุณาตรวจข้อมูลในช่องที่มีเครื่องหมายสีแดง</div>
        <?php endif; ?>
        <?php require __DIR__ . '/includes/lost_form.php'; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
