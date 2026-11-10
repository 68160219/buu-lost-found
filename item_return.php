<?php
// UC08 ยืนยันคืนเรียบร้อย / UC09 ยกเลิกคำขอ — ทำได้เฉพาะเจ้าของประกาศ และสถานะต้องเป็น claimed
require __DIR__ . '/includes/init.php';
require_login();
require_post();

$uid = current_user_id();
$id = (int)post('id');
$action = post('action');

$stmt = $conn->prepare('SELECT post_type FROM lf_items WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
if (!$item) {
    flash('danger', 'ไม่พบประกาศ หรือคุณไม่ใช่เจ้าของประกาศ');
    redirect('my_items.php');
}

if ($action === 'confirm') {
    // ผู้รับของจริงมาจากบัญชีเท่านั้น ไม่มีช่องให้พิมพ์ชื่อเอง:
    // ของที่พบ → ผู้ที่กดขอรับคืน (claimed_by) / ตามหาของหาย → คนโพสต์ที่เป็นเจ้าของของ (user_id)
    $stmt = $conn->prepare(
        "UPDATE lf_items
         SET status = 'returned',
             returned_to = IF(post_type = 'found', claimed_by, user_id),
             returned_at = NOW()
         WHERE id = ? AND user_id = ? AND status = 'claimed'"
    );
    $success_message = 'บันทึกการคืนของเรียบร้อยแล้ว';
} elseif ($action === 'cancel') {
    $stmt = $conn->prepare(
        "UPDATE lf_items SET status = 'found', claimed_by = NULL
         WHERE id = ? AND user_id = ? AND status = 'claimed'"
    );
    $success_message = 'ยกเลิกแล้ว ประกาศกลับมาเปิดอีกครั้ง';
} else {
    redirect(view_url($item['post_type'], $id));
}

$stmt->bind_param('ii', $id, $uid);
$stmt->execute();

if ($stmt->affected_rows === 1) {
    flash('success', $success_message);
} else {
    flash('danger', 'ดำเนินการไม่ได้ สถานะประกาศเปลี่ยนไปแล้ว');
}
redirect(view_url($item['post_type'], $id));
