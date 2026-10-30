<?php
// UC02 ออกจากระบบ (รับเฉพาะ POST พร้อม CSRF Token)
require __DIR__ . '/includes/init.php';
require_post();

$_SESSION = [];
session_regenerate_id(true);

flash('success', 'ออกจากระบบเรียบร้อยแล้ว');
redirect('index.php');
