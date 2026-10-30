<?php
// ไฟล์เริ่มต้นที่ทุกหน้าต้อง require: เปิด Session, เชื่อมฐานข้อมูล และฟังก์ชันช่วยที่ใช้ร่วมกัน

date_default_timezone_set('Asia/Bangkok');

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
session_start();

// ให้ MySQLi โยน exception เมื่อ query ผิดพลาด แทนการคืนค่า false เงียบๆ
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db_config = __DIR__ . '/../config/db.php';
if (!file_exists($db_config)) {
    die('ยังไม่มีไฟล์ config/db.php — ก๊อป config/db.example.php เป็น config/db.php แล้วใส่ค่าการเชื่อมต่อของตัวเอง');
}
try {
    require $db_config;
} catch (mysqli_sql_exception $ex) {
    die('เชื่อมต่อฐานข้อมูลไม่สำเร็จ ตรวจค่า host, user, password และชื่อฐานข้อมูลใน config/db.php');
}

// ---------------------------------------------------------
// ฟังก์ชันทั่วไป
// ---------------------------------------------------------

// ป้องกัน XSS: ทุกข้อความที่มาจากผู้ใช้ต้องแสดงผ่าน e()
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// อ่านค่าจาก $_POST แบบปลอดภัย (คืน string เสมอ)
function post($key)
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '';
}

// นับจำนวนตัวอักษรของข้อความ UTF-8 (ภาษาไทย 1 ตัว = 1 ตัวอักษร)
function text_length($text)
{
    return preg_match_all('/./us', $text);
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

// รับเฉพาะ path ภายในเว็บเดียวกัน กันการ redirect ไปเว็บอื่น
function safe_next($next)
{
    if (is_string($next) && preg_match('#^/(?![/\\\\])[^\r\n]*$#', $next)) {
        return $next;
    }
    return 'index.php';
}

// ---------------------------------------------------------
// ข้อความแจ้งเตือนชั่วคราว (แสดงครั้งเดียวในหน้าถัดไป)
// ---------------------------------------------------------

function flash($type, $message)
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes()
{
    $flashes = isset($_SESSION['flash']) ? $_SESSION['flash'] : [];
    unset($_SESSION['flash']);
    return $flashes;
}

// ---------------------------------------------------------
// Authentication — user_id มาจาก Session เท่านั้น ไม่รับจากฟอร์ม
// ---------------------------------------------------------

function current_user_id()
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function is_logged_in()
{
    return current_user_id() !== null;
}

function require_login()
{
    if (!is_logged_in()) {
        flash('warning', 'กรุณาเข้าสู่ระบบก่อนใช้งานหน้านี้');
        redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

// รหัสคณะ = เลขหลักที่ 3-4 ของรหัสนิสิต เช่น 68160219 → "16"
// ผู้ใช้เห็นและขอรับคืนได้เฉพาะประกาศที่คนโพสต์อยู่คณะเดียวกัน
function faculty_code($student_id)
{
    return substr($student_id, 2, 2);
}

function current_faculty()
{
    return is_logged_in() ? faculty_code($_SESSION['student_id']) : null;
}

function log_in_user(array $user)
{
    session_regenerate_id(true);   // กัน Session Fixation
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['student_id'] = $user['student_id'];
    $_SESSION['full_name'] = $user['full_name'];
    unset($_SESSION['csrf_token']);
}

// ---------------------------------------------------------
// CSRF Token — ใส่ในทุกฟอร์มที่เปลี่ยนข้อมูล
// ---------------------------------------------------------

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf()
{
    // ถ้าไฟล์ที่ส่งมาใหญ่เกิน post_max_size ของ PHP ค่า $_POST จะว่างทั้งหมด
    if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
        http_response_code(413);
        die('ข้อมูลที่ส่งมาใหญ่เกินไป (รูปภาพต้องไม่เกิน 2 MB) กรุณากลับไปลองใหม่');
    }
    $token = post('csrf_token');
    if ($token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        die('คำขอไม่ถูกต้อง (CSRF token ไม่ตรง) กรุณากลับไปรีเฟรชหน้าแล้วลองใหม่');
    }
}

// รับเฉพาะ POST สำหรับหน้าที่เปลี่ยนข้อมูล
function require_post()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('index.php');
    }
    verify_csrf();
}

// ---------------------------------------------------------
// การแสดงผล
// ---------------------------------------------------------

function status_label($status)
{
    $labels = ['found' => 'ถูกพบ', 'claimed' => 'รอรับคืน', 'returned' => 'คืนเรียบร้อย'];
    return isset($labels[$status]) ? $labels[$status] : $status;
}

function status_badge($status)
{
    $colors = ['found' => 'success', 'claimed' => 'warning', 'returned' => 'secondary'];
    $color = isset($colors[$status]) ? $colors[$status] : 'light';
    return '<span class="badge text-bg-' . $color . '">' . e(status_label($status)) . '</span>';
}

function thai_date($date)
{
    $months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $ts = strtotime($date);
    return date('j', $ts) . ' ' . $months[date('n', $ts) - 1] . ' ' . (date('Y', $ts) + 543);
}

function thai_datetime($datetime)
{
    return thai_date($datetime) . ' ' . date('H:i', strtotime($datetime)) . ' น.';
}

function not_found($message = 'ไม่พบประกาศที่ต้องการ อาจถูกลบไปแล้ว')
{
    http_response_code(404);
    $page_title = 'ไม่พบข้อมูล';
    require __DIR__ . '/header.php';
    echo '<div class="alert alert-warning">' . e($message) . '</div>';
    echo '<a href="index.php" class="btn btn-outline-secondary">กลับหน้าแรก</a>';
    require __DIR__ . '/footer.php';
    exit;
}

// ---------------------------------------------------------
// ข้อมูลอ้างอิง
// ---------------------------------------------------------

function fetch_categories(mysqli $conn)
{
    return $conn->query('SELECT id, name FROM categories ORDER BY id')->fetch_all(MYSQLI_ASSOC);
}

function fetch_pickup_points(mysqli $conn)
{
    return $conn->query('SELECT id, name, description FROM pickup_points ORDER BY id')->fetch_all(MYSQLI_ASSOC);
}
