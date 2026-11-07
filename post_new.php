<?php
// เลือกประเภทประกาศ: ตัวเลือกที่ 1 แจ้งของที่พบ / ตัวเลือกที่ 2 ตามหาของหาย
require __DIR__ . '/includes/init.php';
require_login();

$page_title = 'โพสต์ประกาศ';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-1">โพสต์ประกาศ</h1>
<p class="text-secondary mb-4">เลือกประเภทประกาศที่ต้องการ ประกาศจะแสดงให้คนในคณะเดียวกับคุณเห็น</p>

<div class="row g-3">
    <div class="col-md-6">
        <a href="item_create.php" class="card h-100 shadow-sm item-card text-reset text-decoration-none post-choice post-choice-found">
            <div class="card-body p-4">
                <div class="small text-secondary mb-1">ตัวเลือกที่ 1</div>
                <h2 class="h4 mb-2">แจ้งของที่พบ</h2>
                <p class="mb-0">ฉันเก็บของได้ และอยากคืนให้เจ้าของ เลือกได้ว่าจะให้เจ้าของติดต่อฉันโดยตรง หรือฝากไว้ที่จุดรับคืน เช่น ป้อมยาม</p>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="lost_create.php" class="card h-100 shadow-sm item-card text-reset text-decoration-none post-choice post-choice-lost">
            <div class="card-body p-4">
                <div class="small text-secondary mb-1">ตัวเลือกที่ 2</div>
                <h2 class="h4 mb-2">ตามหาของหาย</h2>
                <p class="mb-0">ฉันทำของหาย และอยากให้คนในคณะช่วยกันหา ใครเจอของจะติดต่อกลับมาหาฉันได้ เมื่อได้ของคืนแล้วกดปิดประกาศได้เลย</p>
            </div>
        </a>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
