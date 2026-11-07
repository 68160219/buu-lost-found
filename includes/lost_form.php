<?php
// ฟอร์มประกาศตามหาของหายที่ใช้ร่วมกันระหว่าง lost_create.php และ lost_edit.php
// ต้องมีตัวแปร: $lost, $errors, $categories, $submit_label, $cancel_url
?>
<form method="post" enctype="multipart/form-data" class="card card-body shadow-sm" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= MAX_IMAGE_BYTES ?>">

    <div class="mb-3">
        <label for="title" class="form-label">ของที่หาย <span class="text-danger">*</span></label>
        <input type="text" id="title" name="title" maxlength="150" required
               class="form-control<?= field_class($errors, 'title') ?>"
               value="<?= e($lost['title']) ?>" placeholder="เช่น บัตรนิสิต, หูฟังไร้สายสีขาว">
        <?= field_error($errors, 'title') ?>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="category_id" class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
            <select id="category_id" name="category_id" required class="form-select<?= field_class($errors, 'category_id') ?>">
                <option value="">-- เลือกหมวดหมู่ --</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"<?= (int)$lost['category_id'] === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?= field_error($errors, 'category_id') ?>
        </div>
        <div class="col-md-6 mb-3">
            <label for="lost_date" class="form-label">วันที่หาย (โดยประมาณ) <span class="text-danger">*</span></label>
            <input type="date" id="lost_date" name="lost_date" required max="<?= date('Y-m-d') ?>"
                   class="form-control<?= field_class($errors, 'lost_date') ?>"
                   value="<?= e($lost['lost_date']) ?>">
            <?= field_error($errors, 'lost_date') ?>
        </div>
    </div>

    <div class="mb-3">
        <label for="lost_location" class="form-label">หายที่ไหน <span class="text-danger">*</span></label>
        <input type="text" id="lost_location" name="lost_location" maxlength="150" required
               class="form-control<?= field_class($errors, 'lost_location') ?>"
               value="<?= e($lost['lost_location']) ?>" placeholder="ถ้าไม่แน่ใจ ใส่ช่วงที่เดินผ่าน เช่น ระหว่างตึก IF ถึงโรงอาหารกลาง">
        <?= field_error($errors, 'lost_location') ?>
    </div>

    <div class="mb-3">
        <label for="description" class="form-label">รายละเอียดเพิ่มเติม</label>
        <textarea id="description" name="description" rows="3" class="form-control"
                  placeholder="สี ยี่ห้อ ลักษณะเด่น (เก็บรายละเอียดบางอย่างไว้ถามยืนยันตอนมีคนติดต่อมา)"><?= e($lost['description']) ?></textarea>
    </div>

    <div class="mb-4">
        <label for="image" class="form-label">รูปภาพตัวอย่างของ (ถ้ามี · JPG / PNG ไม่เกิน 2 MB)</label>
        <?php if (!empty($lost['image_path'])): ?>
            <div class="mb-2">
                <img src="<?= e($lost['image_path']) ?>" alt="รูปปัจจุบัน" class="img-thumbnail" style="max-height: 160px">
                <div class="form-text">อัปโหลดรูปใหม่เพื่อแทนที่รูปนี้</div>
            </div>
        <?php endif; ?>
        <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,image/jpeg,image/png"
               class="form-control<?= field_class($errors, 'image') ?>">
        <?= field_error($errors, 'image') ?>
    </div>

    <p class="form-text">คนที่เจอของจะเห็นเบอร์โทร / LINE ID ในบัญชีของคุณเพื่อติดต่อกลับ</p>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-danger"><?= e($submit_label) ?></button>
        <a href="<?= e($cancel_url) ?>" class="btn btn-outline-secondary">ยกเลิก</a>
    </div>
</form>
