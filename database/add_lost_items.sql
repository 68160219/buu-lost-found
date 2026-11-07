-- =========================================================
--  เพิ่มตาราง lost_items (ประกาศตามหาของหาย) ให้ฐานข้อมูลที่นำเข้า setup.sql ไปแล้ว
--  วิธีใช้: phpMyAdmin → เลือกฐานข้อมูลของตัวเอง → แท็บ SQL → วางทั้งไฟล์ → Go
--  ไฟล์นี้ไม่ลบข้อมูลเดิม (ถ้าติดตั้งใหม่ทั้งหมด ใช้ setup.sql อย่างเดียวพอ)
-- =========================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS lost_items (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT          NOT NULL,
  category_id   INT          NOT NULL,
  title         VARCHAR(150) NOT NULL,
  description   TEXT         NULL,
  lost_location VARCHAR(150) NOT NULL,
  lost_date     DATE         NOT NULL,
  image_path    VARCHAR(255) NULL,
  status        ENUM('searching','resolved') NOT NULL DEFAULT 'searching',
  resolved_at   DATETIME     NULL,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_lost_user     FOREIGN KEY (user_id)     REFERENCES users(id),
  CONSTRAINT fk_lost_category FOREIGN KEY (category_id) REFERENCES categories(id),
  INDEX idx_lost_status (status),
  INDEX idx_lost_title  (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
