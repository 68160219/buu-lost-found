<?php
// UC06 ลบประกาศ — ได้เฉพาะประกาศของตัวเอง (AND user_id = ? ใน WHERE)
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/item_helpers.php';
require_login();
require_post();

$uid = current_user_id();
$id = (int)post('id');

$stmt = $conn->prepare('SELECT image_path FROM items WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    flash('danger', 'ไม่พบประกาศ หรือคุณไม่มีสิทธิ์ลบประกาศนี้');
    redirect('my_items.php');
}

$stmt = $conn->prepare('DELETE FROM items WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();

if ($stmt->affected_rows === 1) {
    delete_image($item['image_path']);
    flash('success', 'ลบประกาศเรียบร้อยแล้ว');
} else {
    flash('danger', 'ลบประกาศไม่สำเร็จ');
}
redirect('my_items.php');
