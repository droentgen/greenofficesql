-- Upgrade an existing database for:
--   • clickable missions: people join with name + หน่วยงาน/สาขาวิชา, progress computed from joins
-- Run once via phpMyAdmin or the mysql CLI. New installs already get this from schema.sql.

CREATE TABLE IF NOT EXISTS units (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  name      VARCHAR(150) NOT NULL,
  sort      INT NOT NULL DEFAULT 0,
  is_active SMALLINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE missions
  ADD COLUMN IF NOT EXISTS target_count INT NOT NULL DEFAULT 0 AFTER target_text;

ALTER TABLE missions DROP COLUMN IF EXISTS progress;

CREATE TABLE IF NOT EXISTS mission_participants (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  mission_id INT NOT NULL,
  name       VARCHAR(150) NOT NULL,
  unit_id    INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE,
  FOREIGN KEY (unit_id) REFERENCES units(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_mission_participants_mission ON mission_participants (mission_id);
CREATE INDEX idx_mission_participants_unit ON mission_participants (unit_id, created_at);
