<?php
// UC07 ขอรับคืน — ผู้ขอมาจาก Session เท่านั้น, ต้องเป็นสถานะ found, ไม่ใช่ประกาศของตัวเอง
// และคนโพสต์ต้องอยู่คณะเดียวกับผู้ขอ
require __DIR__ . '/includes/init.php';
require_login();
require_post();

$uid = current_user_id();
$id = (int)post('id');
$faculty = current_faculty();

$stmt = $conn->prepare(
    "UPDATE items i
     JOIN users u ON u.id = i.user_id
     SET i.status = 'claimed', i.claimed_by = ?
     WHERE i.id = ? AND i.status = 'found' AND i.user_id <> ?
       AND SUBSTRING(u.student_id, 3, 2) = ?"
);
$stmt->bind_param('iiis', $uid, $id, $uid, $faculty);
$stmt->execute();

if ($stmt->affected_rows === 1) {
    flash('success', 'ส่งคำขอรับคืนแล้ว ติดต่อผู้โพสต์หรือไปรับที่จุดรับคืน แล้วรอผู้โพสต์กดยืนยัน');
} else {
    flash('warning', 'ขอรับคืนไม่ได้ อาจมีผู้อื่นขอไปแล้ว หรือเป็นประกาศของคุณเอง');
}
redirect('item_view.php?id=' . $id);
