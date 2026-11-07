<?php
// ปิดประกาศตามหาของหาย (ได้ของคืนแล้ว) หรือเปิดตามหาอีกครั้ง — ทำได้เฉพาะเจ้าของประกาศ
require __DIR__ . '/includes/init.php';
require_login();
require_post();

$uid = current_user_id();
$id = (int)post('id');
$action = post('action');

if ($action === 'resolve') {
    $stmt = $conn->prepare(
        "UPDATE lost_items SET status = 'resolved', resolved_at = NOW()
         WHERE id = ? AND user_id = ? AND status = 'searching'"
    );
    $success_message = 'ยินดีด้วย! ปิดประกาศเรียบร้อยแล้ว';
} elseif ($action === 'reopen') {
    $stmt = $conn->prepare(
        "UPDATE lost_items SET status = 'searching', resolved_at = NULL
         WHERE id = ? AND user_id = ? AND status = 'resolved'"
    );
    $success_message = 'เปิดประกาศตามหาอีกครั้งแล้ว';
} else {
    redirect('lost_view.php?id=' . $id);
}

$stmt->bind_param('ii', $id, $uid);
$stmt->execute();

if ($stmt->affected_rows === 1) {
    flash('success', $success_message);
} else {
    flash('danger', 'ดำเนินการไม่ได้ คุณไม่ใช่เจ้าของประกาศ หรือสถานะประกาศเปลี่ยนไปแล้ว');
}
redirect('lost_view.php?id=' . $id);
