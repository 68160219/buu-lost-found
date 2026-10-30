<?php
// UC08 ยืนยันคืนเรียบร้อย / UC09 ยกเลิกคำขอรับคืน — ทำได้เฉพาะเจ้าของประกาศ และสถานะต้องเป็น claimed
require __DIR__ . '/includes/init.php';
require_login();
require_post();

$uid = current_user_id();
$id = (int)post('id');
$action = post('action');

if ($action === 'confirm') {
    // ผู้รับของจริง = ผู้ที่กดขอรับคืน (claimed_by) ไม่มีช่องให้พิมพ์ชื่อเอง
    $stmt = $conn->prepare(
        "UPDATE items SET status = 'returned', returned_to = claimed_by, returned_at = NOW()
         WHERE id = ? AND user_id = ? AND status = 'claimed'"
    );
    $success_message = 'บันทึกการคืนของเรียบร้อยแล้ว';
} elseif ($action === 'cancel') {
    $stmt = $conn->prepare(
        "UPDATE items SET status = 'found', claimed_by = NULL
         WHERE id = ? AND user_id = ? AND status = 'claimed'"
    );
    $success_message = 'ยกเลิกคำขอแล้ว ประกาศกลับเป็นสถานะ "ถูกพบ" ให้คนอื่นขอรับคืนได้';
} else {
    redirect('item_view.php?id=' . $id);
}

$stmt->bind_param('ii', $id, $uid);
$stmt->execute();

if ($stmt->affected_rows === 1) {
    flash('success', $success_message);
} else {
    flash('danger', 'ดำเนินการไม่ได้ คุณไม่ใช่เจ้าของประกาศ หรือสถานะประกาศเปลี่ยนไปแล้ว');
}
redirect('item_view.php?id=' . $id);
