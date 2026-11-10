<?php
// การ์ด "อาจเป็นของชิ้นเดียวกัน" — ต้องมีตัวแปร: $matches (จาก find_matches())
if (!$matches) {
    return;
}
?>
<section class="mt-5">
    <h2 class="h5 mb-1">อาจเป็นของชิ้นเดียวกัน</h2>
    <p class="small text-secondary">ประกาศฝั่งตรงข้ามในคณะ หมวดเดียวกัน และวันที่ห่างกันไม่เกิน 7 วัน</p>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
        <?php foreach ($matches as $m): ?>
            <div class="col">
                <a href="<?= e(view_url($m['post_type'], $m['id'])) ?>"
                   class="card h-100 shadow-sm item-card text-reset text-decoration-none<?= $m['post_type'] === 'lost' ? ' item-card-lost' : '' ?>">
                    <div class="card-body">
                        <div class="small fw-semibold <?= $m['post_type'] === 'lost' ? 'text-danger' : 'text-success' ?> mb-1">
                            <?= $m['post_type'] === 'lost' ? 'มีคนตามหาของชิ้นนี้' : 'มีคนเจอของชิ้นนี้' ?>
                        </div>
                        <h3 class="h6 mb-1"><?= e($m['title']) ?></h3>
                        <div class="small"><?= $m['post_type'] === 'lost' ? 'หายที่' : 'พบที่' ?>: <?= e($m['found_location']) ?></div>
                        <div class="small text-secondary">
                            <?= e(thai_date($m['found_date'])) ?>
                            · <?= (int)$m['day_diff'] === 0 ? 'วันเดียวกัน' : 'ห่างกัน ' . (int)$m['day_diff'] . ' วัน' ?>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
