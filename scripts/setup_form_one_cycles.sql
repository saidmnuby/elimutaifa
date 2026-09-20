CREATE TABLE IF NOT EXISTS elimutaifa.form_one_cycles (
 id BIGINT AUTO_INCREMENT PRIMARY KEY,
 cycle_key VARCHAR(60) NOT NULL UNIQUE,
 intake_year INT NOT NULL,
 exam_year INT NOT NULL,
 round_label VARCHAR(80) NOT NULL,
 source_url VARCHAR(2048) NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'draft',
 verified_at DATETIME NULL,
 verification_report TEXT NULL,
 verified_by BIGINT NULL,
 created_by BIGINT NULL,
 updated_by BIGINT NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 INDEX idx_form_one_public(status,intake_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
INSERT INTO elimutaifa.form_one_cycles(cycle_key,intake_year,exam_year,round_label,source_url,status,created_at,updated_at)
SELECT '2026-first',2026,2025,'First selection','https://selection.tamisemi.go.tz/allocations/2025/first-selection/index.html','draft',UTC_TIMESTAMP(),UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM elimutaifa.form_one_cycles WHERE cycle_key='2026-first');
