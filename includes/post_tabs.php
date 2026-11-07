<?php
// แท็บสลับระหว่างรายการ "ของที่พบ" กับ "ตามหาของหาย" — ใช้ใน index.php และ lost_index.php
$tab_page = basename($_SERVER['PHP_SELF']);
?>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link<?= $tab_page === 'index.php' ? ' active' : '' ?>" href="index.php">ของที่พบ (รอเจ้าของมารับ)</a>
    </li>
    <li class="nav-item">
        <a class="nav-link<?= $tab_page === 'lost_index.php' ? ' active' : '' ?>" href="lost_index.php">ตามหาของหาย</a>
    </li>
</ul>
