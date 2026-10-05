-- ============================================================
-- SISTEM MANAJEMEN ATLET - athlete_management.sql
-- Target : MySQL 5.7+/8.x atau MariaDB 10.4+ (XAMPP / Laragon)
-- Import : phpMyAdmin > tab Import > pilih file ini
-- ============================================================

CREATE DATABASE IF NOT EXISTS athlete_management
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE athlete_management;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS fitness_tests;
DROP TABLE IF EXISTS achievements;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS athletes;
DROP TABLE IF EXISTS sports;
DROP TABLE IF EXISTS settings;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 1. ATHLETES  (dibuat lebih dulu karena dirujuk users)
-- ------------------------------------------------------------
CREATE TABLE athletes (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name     VARCHAR(100) NOT NULL,
  gender        ENUM('Laki-laki','Perempuan') NOT NULL,
  birth_place   VARCHAR(100) NULL,
  birth_date    DATE NOT NULL,
  address       TEXT NULL,
  phone         VARCHAR(20) NULL,
  school        VARCHAR(150) NULL,
  `class`       VARCHAR(20) NULL,
  sport         VARCHAR(50) NOT NULL,              -- fleksibel (bukan ENUM)
  position      VARCHAR(50) NULL,
  jersey_number TINYINT UNSIGNED NULL,
  status        ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  photo         VARCHAR(255) NULL,                 -- hanya nama file
  notes         TEXT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_athletes_name   (full_name),
  INDEX idx_athletes_sport  (sport),
  INDEX idx_athletes_school (school),
  INDEX idx_athletes_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. USERS
-- ------------------------------------------------------------
CREATE TABLE users (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name   VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL,
  password    VARCHAR(255) NOT NULL,               -- hasil password_hash()
  role        ENUM('admin','pelatih','atlet') NOT NULL DEFAULT 'atlet',
  athlete_id  INT UNSIGNED NULL,                   -- NULL untuk admin & pelatih
  status      ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_athlete (athlete_id),        -- 1 atlet = maks. 1 akun
  INDEX idx_users_role (role),
  CONSTRAINT fk_users_athlete
    FOREIGN KEY (athlete_id) REFERENCES athletes(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. ACHIEVEMENTS
-- ------------------------------------------------------------
CREATE TABLE achievements (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  athlete_id       INT UNSIGNED NOT NULL,
  competition_year YEAR NOT NULL,
  competition_name VARCHAR(150) NOT NULL,
  category         VARCHAR(100) NULL,
  result           VARCHAR(100) NOT NULL,          -- contoh: Juara 1
  `rank`           SMALLINT UNSIGNED NULL,         -- 1,2,3,... (backtick wajib: RANK reserved di MySQL 8)
  notes            TEXT NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_ach_athlete (athlete_id),
  INDEX idx_ach_year (competition_year),
  CONSTRAINT fk_ach_athlete
    FOREIGN KEY (athlete_id) REFERENCES athletes(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. FITNESS_TESTS
-- ------------------------------------------------------------
CREATE TABLE fitness_tests (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  athlete_id    INT UNSIGNED NOT NULL,
  test_date     DATE NOT NULL,
  height        DECIMAL(5,2) NULL,                 -- cm
  weight        DECIMAL(5,2) NULL,                 -- kg
  push_up       SMALLINT UNSIGNED NULL,            -- repetisi
  sit_up        SMALLINT UNSIGNED NULL,            -- repetisi
  sprint_20m    DECIMAL(5,2) NULL,                 -- detik
  beep_test     DECIMAL(4,1) NULL,                 -- level
  sit_and_reach DECIMAL(5,2) NULL,                 -- cm
  agility       DECIMAL(5,2) NULL,                 -- detik
  vo2_max       DECIMAL(5,2) NULL,                 -- ml/kg/menit
  notes         TEXT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_ft_athlete_date (athlete_id, test_date),
  CONSTRAINT fk_ft_athlete
    FOREIGN KEY (athlete_id) REFERENCES athletes(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_ft_height CHECK (height IS NULL OR (height > 0 AND height < 300)),
  CONSTRAINT chk_ft_weight CHECK (weight IS NULL OR (weight > 0 AND weight < 300))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. SPORTS (master untuk dropdown; athletes.sport tetap bebas)
-- ------------------------------------------------------------
CREATE TABLE sports (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sports_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. SETTINGS (Pengaturan sistem untuk admin)
-- ------------------------------------------------------------
CREATE TABLE settings (
  setting_key   VARCHAR(50)  NOT NULL,
  setting_value VARCHAR(255) NOT NULL,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATA AWAL (SEED)
-- Password awal (SEGERA GANTI setelah login pertama):
--   admin@athlete.local    -> Admin#2026
--   pelatih@athlete.local  -> Pelatih#2026
--   atlet1/2/3@athlete.local -> Atlet#2026
-- ============================================================

INSERT INTO sports (name) VALUES
('Sepak Bola'),('Futsal'),('Basket'),('Voli'),
('Atletik'),('Renang'),('Bulutangkis'),('Pencak Silat');

INSERT INTO settings (setting_key, setting_value) VALUES
('app_name','Sistem Manajemen Atlet'),
('organization','Klub Olahraga Contoh'),
('max_photo_mb','2'),
('records_per_page','10');

INSERT INTO athletes
(id, full_name, gender, birth_place, birth_date, address, phone, school, `class`, sport, position, jersey_number, status, notes) VALUES
(1,'Rizky Pratama','Laki-laki','Sidoarjo','2008-03-14','Jl. Merdeka No. 10, Sidoarjo','081234567801','SMAN 1 Sidoarjo','XI','Atletik','Sprinter 100m',7,'aktif','Spesialis sprint'),
(2,'Aulia Rahmawati','Perempuan','Surabaya','2009-07-22','Jl. Melati No. 5, Surabaya','081234567802','SMAN 2 Surabaya','X','Bulutangkis','Tunggal Putri',3,'aktif',NULL),
(3,'Dimas Saputra','Laki-laki','Gresik','2007-11-05','Jl. Veteran No. 21, Gresik','081234567803','SMKN 1 Gresik','XII','Futsal','Anchor',10,'aktif',NULL),
(4,'Nabila Putri','Perempuan','Malang','2008-01-30','Jl. Kenanga No. 8, Malang','081234567804','SMAN 3 Malang','XI','Voli','Spiker',9,'aktif',NULL),
(5,'Fajar Nugroho','Laki-laki','Sidoarjo','2006-09-18','Jl. Pahlawan No. 3, Sidoarjo','081234567805','SMAN 1 Sidoarjo','XII','Sepak Bola','Gelandang',8,'nonaktif','Cedera, istirahat');

INSERT INTO users (id, full_name, email, password, role, athlete_id, status) VALUES
(1,'Administrator','admin@athlete.local','$2y$10$LvFiPOSonSDyhYJgfolON.7ahAx8c0cKzx3kaANKtHpSLL84EIFDe','admin',NULL,'aktif'),
(2,'Pelatih Utama','pelatih@athlete.local','$2y$10$ctU3NQA98.gexecUCkUVzuuunVcHpTBuiDQMPwHTukDhLOmLUaaKu','pelatih',NULL,'aktif'),
(3,'Rizky Pratama','atlet1@athlete.local','$2y$10$eNgIKqpqbiwnkway4mx3weXG0FZ68jDO8pIG5FvWPimnwnfvRwVKC','atlet',1,'aktif'),
(4,'Aulia Rahmawati','atlet2@athlete.local','$2y$10$eNgIKqpqbiwnkway4mx3weXG0FZ68jDO8pIG5FvWPimnwnfvRwVKC','atlet',2,'aktif'),
(5,'Dimas Saputra','atlet3@athlete.local','$2y$10$eNgIKqpqbiwnkway4mx3weXG0FZ68jDO8pIG5FvWPimnwnfvRwVKC','atlet',3,'aktif');

INSERT INTO achievements (athlete_id, competition_year, competition_name, category, result, `rank`, notes) VALUES
(1,2025,'Kejurprov','Sprint 100 Meter','Juara 1',1,NULL),
(1,2025,'POPDA','Atletik','Juara 2',2,NULL),
(1,2026,'Kejurnas','Sprint','Juara 3',3,NULL),
(2,2025,'Kejurkab Bulutangkis','Tunggal Putri U-17','Juara 1',1,NULL),
(2,2026,'POPDA','Bulutangkis','Juara 2',2,NULL),
(3,2025,'Liga Pelajar','Futsal Putra','Juara 3',3,NULL),
(4,2026,'Kejurkot Voli','Putri U-17','Juara 2',2,NULL);

INSERT INTO fitness_tests
(athlete_id, test_date, height, weight, push_up, sit_up, sprint_20m, beep_test, sit_and_reach, agility, vo2_max, notes) VALUES
(1,'2025-10-10',170.0,60.5,35,40,3.45,9.5,22.0,16.80,46.2,'Tes awal'),
(1,'2026-01-12',170.5,61.0,40,44,3.38,10.2,23.5,16.40,48.1,NULL),
(1,'2026-04-15',171.0,61.5,44,48,3.30,11.0,24.0,16.10,50.3,NULL),
(1,'2026-07-20',171.0,62.0,48,52,3.24,11.6,25.0,15.90,52.0,'Meningkat stabil'),
(2,'2025-10-10',160.0,50.0,20,35,3.80,8.0,26.0,17.50,41.0,'Tes awal'),
(2,'2026-02-10',160.5,50.5,24,38,3.72,8.8,27.0,17.20,43.2,NULL),
(2,'2026-06-10',161.0,51.0,27,41,3.65,9.5,28.0,16.90,44.8,NULL),
(3,'2025-10-12',172.0,68.0,38,42,3.55,9.0,18.0,16.90,45.0,'Tes awal'),
(3,'2026-03-12',172.5,67.5,42,46,3.48,9.9,19.5,16.50,47.0,NULL),
(4,'2026-01-20',168.0,55.0,25,40,3.70,8.5,24.0,17.00,42.5,'Tes awal');

-- ============================================================
-- SELESAI
-- ============================================================
