-- =========================================================
--  Lost & Found BUU — Database Setup
--  วิธีใช้: phpMyAdmin → เลือกฐานข้อมูลของตัวเอง → แท็บ SQL → วางทั้งไฟล์ → Go
-- =========================================================

SET NAMES utf8mb4;

-- ลบตารางเก่า (ลบลูกก่อนแม่ เพราะติด Foreign Key)
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS pickup_points;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- ---------------------------------------------------------
-- 1) users : บัญชีผู้ใช้ Login ด้วยรหัสนิสิต
-- ---------------------------------------------------------
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  student_id    CHAR(8)      NOT NULL UNIQUE,      -- รหัสนิสิต 8 หลัก ใช้ Login
  password_hash VARCHAR(255) NOT NULL,             -- เก็บ hash เท่านั้น ห้ามเก็บรหัสจริง
  full_name     VARCHAR(100) NOT NULL,
  phone         VARCHAR(20)  NULL,
  line_id       VARCHAR(50)  NULL,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 2) categories : หมวดหมู่สิ่งของ
-- ---------------------------------------------------------
CREATE TABLE categories (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 3) pickup_points : จุดรับคืน (เช่น ป้อมยาม)
-- ---------------------------------------------------------
CREATE TABLE pickup_points (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) NULL                    -- เช่น เวลาทำการ / ตำแหน่ง
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 4) items : ประกาศของที่พบ
-- ---------------------------------------------------------
CREATE TABLE items (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT          NOT NULL,           -- คนที่เจอและโพสต์
  category_id     INT          NOT NULL,
  title           VARCHAR(150) NOT NULL,           -- ชื่อสิ่งของ
  description     TEXT         NULL,
  found_location  VARCHAR(150) NOT NULL,           -- พบที่ไหน
  found_date      DATE         NOT NULL,           -- พบเมื่อวันที่
  image_path      VARCHAR(255) NULL,
  return_method   ENUM('contact','pickup') NOT NULL,  -- ติดต่อคนเจอ / ไปรับที่จุดรับคืน
  pickup_point_id INT          NULL,               -- ใช้เมื่อ return_method = 'pickup'
  status          ENUM('found','claimed','returned') NOT NULL DEFAULT 'found',
  claimed_by      INT          NULL,               -- คนที่กด "ขอรับคืน" (จาก Session)
  returned_to     INT          NULL,               -- คนที่ได้รับของจริง
  returned_at     DATETIME     NULL,
  created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_items_user     FOREIGN KEY (user_id)         REFERENCES users(id),
  CONSTRAINT fk_items_category FOREIGN KEY (category_id)     REFERENCES categories(id),
  CONSTRAINT fk_items_pickup   FOREIGN KEY (pickup_point_id) REFERENCES pickup_points(id),
  CONSTRAINT fk_items_claimed  FOREIGN KEY (claimed_by)      REFERENCES users(id),
  CONSTRAINT fk_items_returned FOREIGN KEY (returned_to)     REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- index ช่วยให้ค้นหา/กรองเร็วขึ้น
CREATE INDEX idx_items_status ON items(status);
CREATE INDEX idx_items_title  ON items(title);

-- =========================================================
--  ข้อมูลเริ่มต้น
-- =========================================================
INSERT INTO categories (name) VALUES
  ('บัตร / เอกสาร'),
  ('กระเป๋า / กระเป๋าสตางค์'),
  ('มือถือ / อุปกรณ์อิเล็กทรอนิกส์'),
  ('กุญแจ'),
  ('เครื่องเขียน / หนังสือ'),
  ('เสื้อผ้า / เครื่องประดับ'),
  ('ขวดน้ำ / แก้วน้ำ'),
  ('อื่นๆ');

INSERT INTO pickup_points (name, description) VALUES
  ('ป้อมยามประตูหน้ามหาวิทยาลัย', 'เปิด 24 ชั่วโมง'),
  ('ห้องกองกิจการนิสิต', 'จันทร์–ศุกร์ 08:30–16:30'),
  ('ห้องธุรการคณะวิทยาการสารสนเทศ', 'จันทร์–ศุกร์ 08:30–16:30');

-- บัญชีทดสอบ 2 คน (รหัสผ่านทั้งคู่: test1234) — ไว้ทดสอบว่า A แก้ของ B ไม่ได้
INSERT INTO users (student_id, password_hash, full_name, phone, line_id) VALUES
  ('65000001', '$2y$10$Dl/KboyK5FKTevKBvhaTge5rYnsynB.cycAR0xaTds/AfNWDXiryG', 'ผู้ทดสอบ A', '0800000001', 'tester_a'),
  ('65000002', '$2y$10$Dl/KboyK5FKTevKBvhaTge5rYnsynB.cycAR0xaTds/AfNWDXiryG', 'ผู้ทดสอบ B', '0800000002', 'tester_b');

-- ตัวอย่างประกาศ 2 รายการ (แบบติดต่อคนเจอ / แบบฝากป้อมยาม)
INSERT INTO items (user_id, category_id, title, description, found_location, found_date, return_method, pickup_point_id) VALUES
  (1, 2, 'กระเป๋าสตางค์สีดำ', 'หนังสีดำ มีสายคล้อง', 'โรงอาหารกลาง', '2026-10-27', 'contact', NULL),
  (2, 4, 'พวงกุญแจรถมอเตอร์ไซค์', 'มีพวงกุญแจรูปแมว', 'ลานจอดรถตึก IF', '2026-10-28', 'pickup', 1);
