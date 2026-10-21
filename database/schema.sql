-- Green Office Digital Platform — database structure (MySQL / MariaDB)
-- Import this first, then seed.sql.

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS criteria_evidence, criteria_reports, criteria_subitems, criteria_indicators, criteria_issues, categories,
  resource_monthly, news, posters, missions, activities, audit_findings, documents, complaints, complaint_plan_steps,
  game_responses, settings, users, app_sessions;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- Users & settings ----------
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name  VARCHAR(120) NOT NULL DEFAULT '',
  role          ENUM('admin','editor','staff','auditor') NOT NULL DEFAULT 'staff',
  unit          VARCHAR(150) NOT NULL DEFAULT '',
  is_active     SMALLINT NOT NULL DEFAULT 1,
  last_login    TIMESTAMP NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  k VARCHAR(60) PRIMARY KEY,
  v TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PHP session storage (see app/session_db.php) — needed on hosts where each
-- request can land on a different machine with no shared local disk.
CREATE TABLE app_sessions (
  id          VARCHAR(128) PRIMARY KEY,
  data        TEXT NOT NULL,
  last_access TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_app_sessions_last_access ON app_sessions (last_access);

-- ---------- Resource statistics (one row per month) ----------
-- GHG is not stored: it is calculated from fuel and electricity with the factors in settings.
CREATE TABLE resource_monthly (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  year_be    SMALLINT NOT NULL,                  -- Buddhist-era year, e.g. 2569
  month      SMALLINT NOT NULL,                  -- 1-12
  fuel_l     DECIMAL(12,2) NOT NULL DEFAULT 0,    -- น้ำมันเชื้อเพลิง (ลิตร)
  elec_kwh   DECIMAL(12,2) NOT NULL DEFAULT 0,    -- ไฟฟ้า (kWh)
  water_m3   DECIMAL(12,2) NOT NULL DEFAULT 0,    -- น้ำ (ลบ.ม.)
  paper_ream DECIMAL(10,2) NOT NULL DEFAULT 0,    -- กระดาษ (รีม)
  note       VARCHAR(255) NOT NULL DEFAULT '',
  updated_by INT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE (year_be, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Green Office criteria: 7 categories → issues → indicators → sub-items ----------
CREATE TABLE categories (
  id          SMALLINT PRIMARY KEY,               -- 1-7
  name        VARCHAR(255) NOT NULL,
  description VARCHAR(255) NOT NULL DEFAULT '',
  color_bg    VARCHAR(9) NOT NULL DEFAULT '#E6F3F1',
  color_fg    VARCHAR(9) NOT NULL DEFAULT '#006664'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE criteria_issues (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  category_id SMALLINT NOT NULL,
  code        VARCHAR(10) NOT NULL,              -- e.g. 1.1
  title       TEXT NOT NULL,
  sort        INT NOT NULL DEFAULT 0,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE criteria_indicators (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  issue_id INT NOT NULL,
  code     VARCHAR(12) NOT NULL UNIQUE,          -- e.g. 1.1.1
  title    TEXT NOT NULL,
  sort     INT NOT NULL DEFAULT 0,
  FOREIGN KEY (issue_id) REFERENCES criteria_issues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE criteria_subitems (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  indicator_id INT NOT NULL,
  idx          SMALLINT NOT NULL,                -- 0-based position
  text         TEXT NOT NULL,
  FOREIGN KEY (indicator_id) REFERENCES criteria_indicators(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One report per indicator per year (รายงานผล)
CREATE TABLE criteria_reports (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  indicator_id INT NOT NULL,
  year_be      SMALLINT NOT NULL,
  report_text  TEXT NOT NULL,
  checks_json  VARCHAR(255) NOT NULL DEFAULT '[]', -- indexes of finished sub-items, e.g. [0,2]
  updated_by   INT NULL,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE (indicator_id, year_be),
  FOREIGN KEY (indicator_id) REFERENCES criteria_indicators(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Evidence links (หลักฐาน) per indicator per year
CREATE TABLE criteria_evidence (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  indicator_id INT NOT NULL,
  year_be      SMALLINT NOT NULL,
  label        VARCHAR(255) NOT NULL,
  url          VARCHAR(1000) NOT NULL,
  created_by   INT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (indicator_id) REFERENCES criteria_indicators(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_criteria_evidence_ind_year ON criteria_evidence (indicator_id, year_be);

-- ---------- Content ----------
CREATE TABLE news (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  category     ENUM('กิจกรรม','ข่าวสาร','ประกาศ','อบรม/สัมมนา') NOT NULL DEFAULT 'ข่าวสาร',
  title        VARCHAR(255) NOT NULL,
  summary      VARCHAR(500) NOT NULL DEFAULT '',
  body         TEXT NOT NULL,                     -- plain text + [ข้อความ](url) links and ![](url) images, see render_body()
  image_path   VARCHAR(255) NULL,
  register_url VARCHAR(1000) NULL,                -- ลิงก์ลงทะเบียน
  pretest_url  VARCHAR(1000) NULL,                -- ลิงก์สอบก่อนอบรม
  posttest_url VARCHAR(1000) NULL,                -- ลิงก์สอบหลังอบรม
  eval_url     VARCHAR(1000) NULL,                -- ลิงก์ประเมินการฝึกอบรม
  doc_url      VARCHAR(1000) NULL,                -- ลิงก์เอกสารฝึกอบรม
  video_url    VARCHAR(1000) NULL,                -- ลิงก์คลิปฝึกอบรม (YouTube / Google Drive เล่นในหน้าข่าวได้)
  status       ENUM('draft','review','published') NOT NULL DEFAULT 'draft',
  published_at DATE NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Publicity posters (โปสเตอร์ประชาสัมพันธ์) shown under the news list
CREATE TABLE posters (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(255) NOT NULL,
  image_path   VARCHAR(255) NOT NULL,
  posted_at    DATE NULL,
  is_published SMALLINT NOT NULL DEFAULT 1,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documents shown on the About page tabs and the complaint plan page (image or PDF)
CREATE TABLE documents (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  section      ENUM('policy','goals','team','measures','complaint_plan','complaint_notice') NOT NULL,
  title        VARCHAR(255) NOT NULL,
  file_path    VARCHAR(255) NULL,                -- uploads/docs/....pdf
  external_url VARCHAR(1000) NULL,               -- or a link (e.g. Google Drive)
  file_type    ENUM('pdf','image','link') NOT NULL DEFAULT 'pdf',
  sort         INT NOT NULL DEFAULT 0,
  is_published SMALLINT NOT NULL DEFAULT 1,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Monthly missions on the home page
CREATE TABLE missions (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(200) NOT NULL,
  target_text VARCHAR(255) NOT NULL DEFAULT '',
  progress    SMALLINT NOT NULL DEFAULT 0,        -- 0-100
  color       VARCHAR(9) NOT NULL DEFAULT '#9CCB3B',
  sort        INT NOT NULL DEFAULT 0,
  is_active   SMALLINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plans & activities board
CREATE TABLE activities (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(255) NOT NULL,
  category_no SMALLINT NOT NULL DEFAULT 1,
  owner       VARCHAR(150) NOT NULL DEFAULT '',
  due_date    DATE NULL,
  status      ENUM('plan','doing','done') NOT NULL DEFAULT 'plan',
  progress    SMALLINT NOT NULL DEFAULT 0,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Internal audit findings & corrective actions
CREATE TABLE audit_findings (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  category_no SMALLINT NOT NULL DEFAULT 1,
  finding     TEXT NOT NULL,
  action      TEXT NOT NULL,
  owner       VARCHAR(150) NOT NULL DEFAULT '',
  due_date    DATE NULL,
  status      ENUM('open','fixing','closed') NOT NULL DEFAULT 'open',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Environmental feedback & complaints ----------
CREATE TABLE complaints (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  ticket_no     VARCHAR(20) NOT NULL UNIQUE,     -- GO-2569-001
  type          ENUM('ข้อคิดเห็น','ข้อร้องเรียน','ข้อเสนอแนะ') NOT NULL DEFAULT 'ข้อคิดเห็น',
  topic         VARCHAR(80) NOT NULL DEFAULT '',
  place         VARCHAR(255) NOT NULL DEFAULT '',
  detail        TEXT NOT NULL,
  rating        SMALLINT NULL,                   -- 1-5 satisfaction
  name          VARCHAR(150) NOT NULL DEFAULT '',
  contact       VARCHAR(150) NOT NULL DEFAULT '',
  status        ENUM('received','checking','fixing','done') NOT NULL DEFAULT 'received',
  result_text   TEXT NULL,
  is_public     SMALLINT NOT NULL DEFAULT 0,      -- show in ประกาศข้อร้องเรียน
  public_title  VARCHAR(255) NOT NULL DEFAULT '',
  public_detail TEXT NULL,                        -- text shown publicly (never name/contact)
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE complaint_plan_steps (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  step_no   SMALLINT NOT NULL,
  title     VARCHAR(150) NOT NULL,
  detail    VARCHAR(500) NOT NULL DEFAULT '',
  timeframe VARCHAR(80) NOT NULL DEFAULT '',
  owner     VARCHAR(150) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Intro game (Green Check-in) ----------
CREATE TABLE game_responses (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  a_elec     SMALLINT NOT NULL,                  -- 0 = not yet, 1 = sometimes, 2 = always
  a_plastic  SMALLINT NOT NULL,
  a_paper    SMALLINT NOT NULL,
  a_commute  SMALLINT NOT NULL,
  score      SMALLINT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
