<?php
// UC07 ขอรับคืน (ของที่พบ) / แจ้งว่าเจอของ (ตามหาของหาย)
// ผู้กดมาจาก Session เท่านั้น, ประกาศต้องเปิดอยู่ (status = found), ไม่ใช่ประกาศของตัวเอง
// และคนโพสต์ต้องอยู่คณะเดียวกับผู้กด
require __DIR__ . '/includes/init.php';
require_login();
require_post();

$uid = current_user_id();
$id = (int)post('id');
$faculty = current_faculty();

$stmt = $conn->prepare('SELECT post_type FROM lf_items WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
if (!$item) {
    flash('danger', 'ไม่พบประกาศ');
    redirect('index.php');
}

$stmt = $conn->prepare(
    "UPDATE lf_items i
     JOIN lf_users u ON u.id = i.user_id
     SET i.status = 'claimed', i.claimed_by = ?
     WHERE i.id = ? AND i.status = 'found' AND i.user_id <> ?
       AND SUBSTRING(u.student_id, 3, 2) = ?"
);
$stmt->bind_param('iiis', $uid, $id, $uid, $faculty);
$stmt->execute();

if ($stmt->affected_rows === 1) {
    flash('success', $item['post_type'] === 'lost'
        ? 'แจ้งเจ้าของแล้วว่าคุณเจอของ ติดต่อเจ้าของเพื่อนัดคืนของ แล้วรอเจ้าของกดยืนยันว่าได้รับแล้ว'
        : 'ส่งคำขอรับคืนแล้ว ติดต่อผู้โพสต์หรือไปรับที่จุดรับคืน แล้วรอผู้โพสต์กดยืนยัน');
} else {
    flash('warning', 'ดำเนินการไม่ได้ อาจมีผู้อื่นกดไปแล้ว หรือเป็นประกาศของคุณเอง');
}
redirect(view_url($item['post_type'], $id));
