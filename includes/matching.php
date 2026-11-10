<?php
// ข้อ 1 จับคู่อัตโนมัติ และข้อ 2 สถิติหน้าแรก

// หาประกาศฝั่งตรงข้าม (เจอของ ↔ ตามหา) ที่น่าจะเป็นของชิ้นเดียวกันกับประกาศ $item_id
// เงื่อนไข: หมวดเดียวกัน วันที่ห่างกันไม่เกิน 7 วัน ทั้งสองประกาศยังเปิดอยู่ ไม่ใช่โพสต์ของคนเดียวกัน
//          และคนโพสต์อยู่คณะเดียวกับผู้ดู — เรียงจากวันที่ใกล้ที่สุดก่อน
// ใช้ self-join ตาราง lf_items (i = ประกาศที่กำลังดู, m = ประกาศที่อาจตรงกัน)
function find_matches(mysqli $conn, $item_id, $faculty, $limit = 6)
{
    // ช่วงวันที่เขียนแบบ BETWEEN เพื่อให้ใช้ index idx_lf_items_match (post_type, category_id, found_date) ได้
    // ส่วน DATEDIFF ใช้คำนวณระยะห่างไว้เรียงลำดับและแสดงผล
    $stmt = $conn->prepare(
        "SELECT m.id, m.post_type, m.title, m.found_location, m.found_date, m.image_path, m.status,
                ABS(DATEDIFF(m.found_date, i.found_date)) AS day_diff
         FROM lf_items i
         JOIN lf_items m
           ON m.post_type <> i.post_type
          AND m.category_id = i.category_id
          AND m.found_date BETWEEN DATE_SUB(i.found_date, INTERVAL 7 DAY) AND DATE_ADD(i.found_date, INTERVAL 7 DAY)
          AND m.status = 'found'
          AND m.user_id <> i.user_id
         JOIN lf_users u ON u.id = m.user_id
         WHERE i.id = ? AND i.status = 'found' AND SUBSTRING(u.student_id, 3, 2) = ?
         ORDER BY day_diff, m.created_at DESC
         LIMIT ?"
    );
    $stmt->bind_param('isi', $item_id, $faculty, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// สถิติหน้าแรก: จำนวนของที่คืนสำเร็จ ประกาศที่ยังเปิดอยู่ (แยกเจอของ / ตามหา) และจำนวนประกาศแยกตามหมวด
// $faculty = null → นับทั้งมหาวิทยาลัย (ใช้ในหน้าแนะนำระบบของผู้ที่ยังไม่ Login)
function get_stats(mysqli $conn, $faculty = null)
{
    $faculty_sql = $faculty !== null ? 'SUBSTRING(u.student_id, 3, 2) = ?' : '1 = 1';

    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(i.status = 'returned'), 0)                         AS returned_count,
                COALESCE(SUM(i.status <> 'returned' AND i.post_type = 'found'), 0) AS open_found,
                COALESCE(SUM(i.status <> 'returned' AND i.post_type = 'lost'), 0)  AS open_lost
         FROM lf_items i
         JOIN lf_users u ON u.id = i.user_id
         WHERE $faculty_sql"
    );
    if ($faculty !== null) {
        $stmt->bind_param('s', $faculty);
    }
    $stmt->execute();
    $totals = $stmt->get_result()->fetch_assoc();

    // LEFT JOIN เพื่อให้หมวดที่ยังไม่มีประกาศแสดงเป็น 0 ด้วย
    $stmt = $conn->prepare(
        "SELECT c.id, c.name, COUNT(i.id) AS item_count
         FROM lf_categories c
         LEFT JOIN (lf_items i JOIN lf_users u ON u.id = i.user_id)
           ON i.category_id = c.id AND $faculty_sql
         GROUP BY c.id, c.name
         ORDER BY item_count DESC, c.id"
    );
    if ($faculty !== null) {
        $stmt->bind_param('s', $faculty);
    }
    $stmt->execute();

    return [
        'returned'    => (int)$totals['returned_count'],
        'open_found'  => (int)$totals['open_found'],
        'open_lost'   => (int)$totals['open_lost'],
        'by_category' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
    ];
}
