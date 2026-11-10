<?php
// แผงสถิติหน้าแรก — ต้องมีตัวแปร: $stats (จาก get_stats()), $stats_scope (เช่น "ในคณะของคุณ")
// $stats_link_categories = true → ชื่อหมวดเป็นลิงก์กรองรายการในหน้าแรก
$max_category = 0;
foreach ($stats['by_category'] as $row) {
    $max_category = max($max_category, (int)$row['item_count']);
}
?>
<section class="mb-4" aria-label="สถิติ">
    <div class="row g-3">
        <div class="col-sm-4">
            <div class="card stat-card stat-returned h-100">
                <div class="card-body">
                    <div class="stat-value"><?= number_format($stats['returned']) ?></div>
                    <div class="small text-secondary">ของที่คืนสำเร็จ<?= e($stats_scope) ?></div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card stat-card stat-found h-100">
                <div class="card-body">
                    <div class="stat-value"><?= number_format($stats['open_found']) ?></div>
                    <div class="small text-secondary">ประกาศเจอของที่ยังเปิดอยู่</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card stat-card stat-lost h-100">
                <div class="card-body">
                    <div class="stat-value"><?= number_format($stats['open_lost']) ?></div>
                    <div class="small text-secondary">ประกาศตามหาที่ยังเปิดอยู่</div>
                </div>
            </div>
        </div>
    </div>

    <details class="card card-body mt-3 py-2">
        <summary class="small fw-semibold">จำนวนประกาศแยกตามหมวด</summary>
        <div class="mt-2">
            <?php foreach ($stats['by_category'] as $row): ?>
                <?php $count = (int)$row['item_count']; ?>
                <div class="d-flex align-items-center gap-2 small mb-1">
                    <div class="stat-cat-name text-truncate">
                        <?php if (!empty($stats_link_categories)): ?>
                            <a href="index.php?category=<?= (int)$row['id'] ?>"><?= e($row['name']) ?></a>
                        <?php else: ?>
                            <?= e($row['name']) ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-fill stat-bar-track">
                        <div class="stat-bar" style="width: <?= $max_category ? round($count / $max_category * 100) : 0 ?>%"></div>
                    </div>
                    <div class="stat-cat-count text-end"><?= $count ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </details>
</section>
