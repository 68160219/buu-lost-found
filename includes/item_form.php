<?php
// ฟอร์มประกาศที่ใช้ร่วมกันระหว่าง item_create.php และ item_edit.php
// ต้องมีตัวแปร: $item, $errors, $categories, $pickup_points, $submit_label

function field_class($errors, $name)
{
    return isset($errors[$name]) ? ' is-invalid' : '';
}

function field_error($errors, $name)
{
    return isset($errors[$name]) ? '<div class="invalid-feedback">' . e($errors[$name]) . '</div>' : '';
}
?>
<form method="post" enctype="multipart/form-data" class="card card-body shadow-sm" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= MAX_IMAGE_BYTES ?>">

    <div class="mb-3">
        <label for="title" class="form-label">ชื่อสิ่งของ <span class="text-danger">*</span></label>
        <input type="text" id="title" name="title" maxlength="150" required
               class="form-control<?= field_class($errors, 'title') ?>"
               value="<?= e($item['title']) ?>" placeholder="เช่น กระเป๋าสตางค์สีดำ">
        <?= field_error($errors, 'title') ?>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="category_id" class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
            <select id="category_id" name="category_id" required class="form-select<?= field_class($errors, 'category_id') ?>">
                <option value="">-- เลือกหมวดหมู่ --</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"<?= (int)$item['category_id'] === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?= field_error($errors, 'category_id') ?>
        </div>
        <div class="col-md-6 mb-3">
            <label for="found_date" class="form-label">วันที่พบ <span class="text-danger">*</span></label>
            <input type="date" id="found_date" name="found_date" required max="<?= date('Y-m-d') ?>"
                   class="form-control<?= field_class($errors, 'found_date') ?>"
                   value="<?= e($item['found_date']) ?>">
            <?= field_error($errors, 'found_date') ?>
        </div>
    </div>

    <div class="mb-3">
        <label for="found_location" class="form-label">สถานที่ที่พบ <span class="text-danger">*</span></label>
        <input type="text" id="found_location" name="found_location" maxlength="150" required
               class="form-control<?= field_class($errors, 'found_location') ?>"
               value="<?= e($item['found_location']) ?>" placeholder="เช่น โรงอาหารกลาง ชั้น 1">
        <?= field_error($errors, 'found_location') ?>
    </div>

    <div class="mb-3">
        <label for="description" class="form-label">รายละเอียดเพิ่มเติม</label>
        <textarea id="description" name="description" rows="3" class="form-control"
                  placeholder="สี ยี่ห้อ ตำหนิ หรือจุดสังเกต (ไม่ควรใส่ข้อมูลที่ใช้ยืนยันความเป็นเจ้าของทั้งหมด)"><?= e($item['description']) ?></textarea>
    </div>

    <div class="mb-3">
        <label class="form-label d-block">วิธีคืนของ <span class="text-danger">*</span></label>
        <div class="form-check">
            <input class="form-check-input<?= field_class($errors, 'return_method') ?>" type="radio" name="return_method"
                   id="method_contact" value="contact"<?= $item['return_method'] === 'contact' ? ' checked' : '' ?>>
            <label class="form-check-label" for="method_contact">ให้เจ้าของติดต่อฉันโดยตรง (แสดงเบอร์โทร / LINE ID ให้ผู้ที่ Login แล้ว)</label>
        </div>
        <div class="form-check">
            <input class="form-check-input<?= field_class($errors, 'return_method') ?>" type="radio" name="return_method"
                   id="method_pickup" value="pickup"<?= $item['return_method'] === 'pickup' ? ' checked' : '' ?>>
            <label class="form-check-label" for="method_pickup">ฝากไว้ที่จุดรับคืน</label>
            <?= field_error($errors, 'return_method') ?>
        </div>
    </div>

    <div class="mb-3" id="pickup_wrapper">
        <label for="pickup_point_id" class="form-label">จุดรับคืน <span class="text-danger">*</span></label>
        <select id="pickup_point_id" name="pickup_point_id" class="form-select<?= field_class($errors, 'pickup_point_id') ?>">
            <option value="">-- เลือกจุดรับคืน --</option>
            <?php foreach ($pickup_points as $p): ?>
                <option value="<?= (int)$p['id'] ?>"<?= (int)$item['pickup_point_id'] === (int)$p['id'] ? ' selected' : '' ?>>
                    <?= e($p['name']) ?><?= $p['description'] ? ' (' . e($p['description']) . ')' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?= field_error($errors, 'pickup_point_id') ?>
    </div>

    <div class="mb-4">
        <label for="image" class="form-label">รูปภาพ (JPG / PNG ไม่เกิน 2 MB)</label>
        <?php if (!empty($item['image_path'])): ?>
            <div class="mb-2">
                <img src="<?= e($item['image_path']) ?>" alt="รูปปัจจุบัน" class="img-thumbnail" style="max-height: 160px">
                <div class="form-text">อัปโหลดรูปใหม่เพื่อแทนที่รูปนี้</div>
            </div>
        <?php endif; ?>
        <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,image/jpeg,image/png"
               class="form-control<?= field_class($errors, 'image') ?>">
        <?= field_error($errors, 'image') ?>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= e($submit_label) ?></button>
        <a href="<?= isset($cancel_url) ? e($cancel_url) : 'my_items.php' ?>" class="btn btn-outline-secondary">ยกเลิก</a>
    </div>
</form>

<script>
    // แสดงช่องจุดรับคืนเฉพาะตอนเลือก "ฝากไว้ที่จุดรับคืน"
    (function () {
        var wrapper = document.getElementById('pickup_wrapper');
        var radios = document.querySelectorAll('input[name="return_method"]');
        function toggle() {
            wrapper.hidden = !document.getElementById('method_pickup').checked;
        }
        radios.forEach(function (r) { r.addEventListener('change', toggle); });
        toggle();
    })();
</script>
