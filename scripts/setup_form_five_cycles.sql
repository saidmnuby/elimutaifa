CREATE TABLE IF NOT EXISTS elimutaifa.form_five_cycles LIKE elimutaifa.form_one_cycles;
INSERT INTO elimutaifa.form_five_cycles(cycle_key,intake_year,exam_year,round_label,source_url,status,created_at,updated_at)
SELECT '2026-first',2026,2025,'First selection','https://selform.tamisemi.go.tz/content/selection-and-allocation/2026/first-selection/index.html','draft',UTC_TIMESTAMP(),UTC_TIMESTAMP()
WHERE NOT EXISTS(SELECT 1 FROM elimutaifa.form_five_cycles WHERE cycle_key='2026-first');
