-- =========================================================
--  Lost & Found BUU — Database Setup (v2)
--  วิธีใช้: phpMyAdmin → เลือกฐานข้อมูลของตัวเอง → แท็บ SQL → วางทั้งไฟล์ → Go
--
--  ทุกตารางขึ้นต้นด้วย lf_ เพราะฐานข้อมูลใช้ร่วมกับ Lab อื่น
--  ไฟล์นี้ลบและสร้างใหม่เฉพาะตาราง lf_ เท่านั้น ไม่แตะตารางอื่นในฐานข้อมูล
-- =========================================================

SET NAMES utf8mb4;

-- ลบตารางเก่า (ลบลูกก่อนแม่ เพราะติด Foreign Key)
DROP TABLE IF EXISTS lf_items;
DROP TABLE IF EXISTS lf_pickup_points;
DROP TABLE IF EXISTS lf_categories;
DROP TABLE IF EXISTS lf_users;

-- ---------------------------------------------------------
-- 1) lf_users : บัญชีผู้ใช้ Login ด้วยรหัสนิสิต (นิสิต 1 คน = 1 บัญชี)
-- ---------------------------------------------------------
CREATE TABLE lf_users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  student_id    CHAR(8)      NOT NULL UNIQUE,      -- รหัสนิสิต 8 หลัก ใช้ Login (ซ้ำไม่ได้)
  email         VARCHAR(100) NOT NULL UNIQUE,      -- ระบบสร้างให้เป็น รหัสนิสิต@go.buu.ac.th
  password_hash VARCHAR(255) NOT NULL,             -- เก็บ hash เท่านั้น ห้ามเก็บรหัสจริง
  full_name     VARCHAR(100) NOT NULL,
  phone         VARCHAR(20)  NULL,
  line_id       VARCHAR(50)  NULL,
  is_verified   TINYINT(1)   NOT NULL DEFAULT 0,   -- 1 = กดลิงก์ยืนยันอีเมลแล้ว จึง Login ได้
  verify_token  CHAR(64)     NULL,                 -- SHA-256 ของโทเคนยืนยัน (ไม่เก็บโทเคนจริง)
  verified_at   DATETIME     NULL,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 2) lf_categories : หมวดหมู่สิ่งของ
-- ---------------------------------------------------------
CREATE TABLE lf_categories (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 3) lf_pickup_points : จุดรับคืน (เช่น ป้อมยาม)
-- ---------------------------------------------------------
CREATE TABLE lf_pickup_points (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) NULL                    -- เช่น เวลาทำการ / ตำแหน่ง
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 4) lf_items : ประกาศทั้ง 2 แบบ
--    post_type = 'found' → คนเจอของโพสต์ (ของที่พบ)
--    post_type = 'lost'  → เจ้าของโพสต์ (ตามหาของหาย)
--
--    สถานะใช้ชุดเดียวกัน: found = ประกาศเปิดอยู่, claimed = มีคนกดขอรับ/แจ้งว่าเจอ, returned = คืนแล้ว
--    ผู้ได้รับของ (returned_to): ของที่พบ = ผู้ขอรับคืน (claimed_by), ตามหาของหาย = คนโพสต์ (user_id)
-- ---------------------------------------------------------
CREATE TABLE lf_items (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  post_type       ENUM('found','lost') NOT NULL DEFAULT 'found',
  user_id         INT          NOT NULL,           -- คนโพสต์
  category_id     INT          NOT NULL,
  title           VARCHAR(150) NOT NULL,           -- ชื่อสิ่งของ
  description     TEXT         NULL,
  found_location  VARCHAR(150) NOT NULL,           -- พบที่ไหน / หายที่ไหน
  found_date      DATE         NOT NULL,           -- พบเมื่อวันที่ / หายเมื่อวันที่
  image_path      VARCHAR(255) NULL,
  return_method   ENUM('contact','pickup') NULL,   -- ใช้กับ post_type = 'found' เท่านั้น
  pickup_point_id INT          NULL,               -- ใช้เมื่อ return_method = 'pickup'
  status          ENUM('found','claimed','returned') NOT NULL DEFAULT 'found',
  claimed_by      INT          NULL,               -- คนที่กด "ขอรับคืน" / "ฉันเจอของชิ้นนี้" (จาก Session)
  returned_to     INT          NULL,               -- คนที่ได้รับของจริง
  returned_at     DATETIME     NULL,
  created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_lf_items_user     FOREIGN KEY (user_id)         REFERENCES lf_users(id),
  CONSTRAINT fk_lf_items_category FOREIGN KEY (category_id)     REFERENCES lf_categories(id),
  CONSTRAINT fk_lf_items_pickup   FOREIGN KEY (pickup_point_id) REFERENCES lf_pickup_points(id),
  CONSTRAINT fk_lf_items_claimed  FOREIGN KEY (claimed_by)      REFERENCES lf_users(id),
  CONSTRAINT fk_lf_items_returned FOREIGN KEY (returned_to)     REFERENCES lf_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- index ช่วยให้ค้นหา/กรองเร็วขึ้น
CREATE INDEX idx_lf_items_status ON lf_items(status);
CREATE INDEX idx_lf_items_title  ON lf_items(title);
-- index สำหรับจับคู่อัตโนมัติ (หาประกาศฝั่งตรงข้าม หมวดเดียวกัน วันที่ใกล้กัน)
CREATE INDEX idx_lf_items_match  ON lf_items(post_type, category_id, found_date);

-- =========================================================
--  ข้อมูลเริ่มต้น
-- =========================================================
INSERT INTO lf_categories (name) VALUES
  ('บัตร / เอกสาร'),
  ('กระเป๋า / กระเป๋าสตางค์'),
  ('มือถือ / อุปกรณ์อิเล็กทรอนิกส์'),
  ('กุญแจ'),
  ('เครื่องเขียน / หนังสือ'),
  ('เสื้อผ้า / เครื่องประดับ'),
  ('ขวดน้ำ / แก้วน้ำ'),
  ('อื่นๆ');

INSERT INTO lf_pickup_points (name, description) VALUES
  ('ป้อมยามประตูหน้ามหาวิทยาลัย', 'เปิด 24 ชั่วโมง'),
  ('ห้องกองกิจการนิสิต', 'จันทร์–ศุกร์ 08:30–16:30'),
  ('ห้องธุรการคณะวิทยาการสารสนเทศ', 'จันทร์–ศุกร์ 08:30–16:30');

-- บัญชีทดสอบ 2 คน (รหัสผ่านทั้งคู่: test1234) ยืนยันอีเมลไว้แล้ว — ไว้ทดสอบว่า A แก้ของ B ไม่ได้
INSERT INTO lf_users (student_id, email, password_hash, full_name, phone, line_id, is_verified, verified_at) VALUES
  ('65000001', '65000001@go.buu.ac.th', '$2y$10$Dl/KboyK5FKTevKBvhaTge5rYnsynB.cycAR0xaTds/AfNWDXiryG', 'ผู้ทดสอบ A', '0800000001', 'tester_a', 1, NOW()),
  ('65000002', '65000002@go.buu.ac.th', '$2y$10$Dl/KboyK5FKTevKBvhaTge5rYnsynB.cycAR0xaTds/AfNWDXiryG', 'ผู้ทดสอบ B', '0800000002', 'tester_b', 1, NOW());

-- ตัวอย่างประกาศของที่พบ 2 รายการ (แบบติดต่อคนเจอ / แบบฝากป้อมยาม)
INSERT INTO lf_items (post_type, user_id, category_id, title, description, found_location, found_date, return_method, pickup_point_id) VALUES
  ('found', 1, 2, 'กระเป๋าสตางค์สีดำ', 'หนังสีดำ มีสายคล้อง', 'โรงอาหารกลาง', '2026-10-27', 'contact', NULL),
  ('found', 2, 4, 'พวงกุญแจรถมอเตอร์ไซค์', 'มีพวงกุญแจรูปแมว', 'ลานจอดรถตึก IF', '2026-10-28', 'pickup', 1);

-- ตัวอย่างประกาศตามหาของหาย 2 รายการ
-- (กระเป๋าสตางค์ของ B จะถูกจับคู่กับกระเป๋าสตางค์ที่ A เจอ เพราะหมวดเดียวกันและวันที่ห่างกัน 1 วัน)
INSERT INTO lf_items (post_type, user_id, category_id, title, description, found_location, found_date) VALUES
  ('lost', 2, 2, 'กระเป๋าสตางค์หนังสีดำ', 'มีบัตรนิสิตอยู่ข้างใน', 'แถวโรงอาหารกลาง', '2026-10-26'),
  ('lost', 2, 1, 'บัตรนิสิต', 'ใส่ซองใสสีฟ้า', 'ระหว่างตึก IF ถึงโรงอาหารกลาง', '2026-10-28');
