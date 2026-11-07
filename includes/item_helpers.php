<?php
// ฟังก์ชันที่ใช้ร่วมกันระหว่างหน้าโพสต์และหน้าแก้ไขประกาศ (ทั้งของที่พบ และตามหาของหาย)

const MAX_IMAGE_BYTES = 2097152; // 2 MB

function field_class($errors, $name)
{
    return isset($errors[$name]) ? ' is-invalid' : '';
}

function field_error($errors, $name)
{
    return isset($errors[$name]) ? '<div class="invalid-feedback">' . e($errors[$name]) . '</div>' : '';
}

// ตรวจช่องที่ประกาศทั้ง 2 แบบมีเหมือนกัน: ชื่อสิ่งของ หมวดหมู่ สถานที่ วันที่
function validate_common_fields(array $data, array $categories, $location_key, $date_key)
{
    $errors = [];

    if ($data['title'] === '') {
        $errors['title'] = 'กรุณากรอกชื่อสิ่งของ';
    } elseif (text_length($data['title']) > 150) {
        $errors['title'] = 'ชื่อสิ่งของต้องไม่เกิน 150 ตัวอักษร';
    }

    $category_ids = array_map('intval', array_column($categories, 'id'));
    if (!in_array($data['category_id'], $category_ids, true)) {
        $errors['category_id'] = 'กรุณาเลือกหมวดหมู่';
    }

    if ($data[$location_key] === '') {
        $errors[$location_key] = 'กรุณากรอกสถานที่';
    } elseif (text_length($data[$location_key]) > 150) {
        $errors[$location_key] = 'สถานที่ต้องไม่เกิน 150 ตัวอักษร';
    }

    $date = DateTime::createFromFormat('Y-m-d', $data[$date_key]);
    if (!$date || $date->format('Y-m-d') !== $data[$date_key]) {
        $errors[$date_key] = 'กรุณาเลือกวันที่';
    } elseif ($data[$date_key] > date('Y-m-d')) {
        $errors[$date_key] = 'วันที่ต้องไม่เป็นวันในอนาคต';
    }

    return $errors;
}

// อ่านและตรวจค่าจากฟอร์มประกาศตามหาของหาย คืนค่า [ข้อมูล, รายการ error ตามชื่อช่อง]
function read_lost_form(array $categories)
{
    $data = [
        'title'         => trim(post('title')),
        'category_id'   => (int)post('category_id'),
        'description'   => trim(post('description')),
        'lost_location' => trim(post('lost_location')),
        'lost_date'     => trim(post('lost_date')),
    ];
    return [$data, validate_common_fields($data, $categories, 'lost_location', 'lost_date')];
}

// อ่านและตรวจค่าจากฟอร์มประกาศของที่พบ คืนค่า [ข้อมูล, รายการ error ตามชื่อช่อง]
function read_item_form(array $categories, array $pickup_points)
{
    $data = [
        'title'           => trim(post('title')),
        'category_id'     => (int)post('category_id'),
        'description'     => trim(post('description')),
        'found_location'  => trim(post('found_location')),
        'found_date'      => trim(post('found_date')),
        'return_method'   => post('return_method'),
        'pickup_point_id' => (int)post('pickup_point_id'),
    ];
    $errors = validate_common_fields($data, $categories, 'found_location', 'found_date');

    if ($data['return_method'] === 'pickup') {
        $pickup_ids = array_map('intval', array_column($pickup_points, 'id'));
        if (!in_array($data['pickup_point_id'], $pickup_ids, true)) {
            $errors['pickup_point_id'] = 'กรุณาเลือกจุดรับคืน';
        }
    } elseif ($data['return_method'] === 'contact') {
        $data['pickup_point_id'] = null;
    } else {
        $errors['return_method'] = 'กรุณาเลือกวิธีคืนของ';
    }

    return [$data, $errors];
}

// รับไฟล์รูปจากฟอร์ม: เฉพาะ JPG / PNG ไม่เกิน 2 MB และตั้งชื่อไฟล์ใหม่แบบสุ่ม
// คืนค่า [path ที่บันทึก หรือ null ถ้าไม่ได้แนบรูป, ข้อความ error หรือ null]
function handle_image_upload($field)
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    $file = $_FILES[$field];

    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
        || $file['size'] > MAX_IMAGE_BYTES) {
        return [null, 'รูปภาพต้องมีขนาดไม่เกิน 2 MB'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, 'อัปโหลดรูปไม่สำเร็จ กรุณาลองใหม่'];
    }

    // ตรวจจากเนื้อไฟล์จริง ไม่เชื่อนามสกุลหรือ MIME type ที่เบราว์เซอร์ส่งมา
    $info = @getimagesize($file['tmp_name']);
    $extensions = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'];
    if ($info === false || !isset($extensions[$info[2]])) {
        return [null, 'รับเฉพาะไฟล์รูป JPG หรือ PNG เท่านั้น'];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../uploads/' . $filename)) {
        return [null, 'บันทึกรูปไม่สำเร็จ (ตรวจสิทธิ์การเขียนของโฟลเดอร์ uploads/)'];
    }
    return ['uploads/' . $filename, null];
}

// ลบไฟล์รูปเก่า เฉพาะไฟล์ที่ระบบตั้งชื่อให้เท่านั้น
function delete_image($path)
{
    if ($path && preg_match('#^uploads/[a-f0-9]{32}\.(jpg|png)$#', $path)) {
        $full_path = __DIR__ . '/../' . $path;
        if (is_file($full_path)) {
            unlink($full_path);
        }
    }
}
