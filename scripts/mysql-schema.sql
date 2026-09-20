CREATE TABLE admin_users (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(80) COLLATE utf8mb4_general_ci NOT NULL UNIQUE,
 display_name VARCHAR(255) NOT NULL, password_hash VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL DEFAULT 'admin',
 is_active TINYINT NOT NULL DEFAULT 1, deleted_at DATETIME, last_login_at DATETIME,
 created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE content_items (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, category VARCHAR(80) NOT NULL, title TEXT NOT NULL,
 slug VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL UNIQUE, excerpt TEXT NOT NULL, body LONGTEXT NOT NULL,
 media_type VARCHAR(20) NOT NULL DEFAULT 'none', media_url VARCHAR(2048) NOT NULL DEFAULT '',
 media_caption VARCHAR(2048) NOT NULL DEFAULT '', audience VARCHAR(80) NOT NULL DEFAULT 'all',
 source_name VARCHAR(255) NOT NULL DEFAULT '', source_url VARCHAR(2048) NOT NULL DEFAULT '',
 destination_type VARCHAR(20) NOT NULL DEFAULT 'internal', external_url VARCHAR(2048) NOT NULL DEFAULT '',
 status VARCHAR(20) NOT NULL DEFAULT 'draft', is_featured TINYINT NOT NULL DEFAULT 0, is_popup TINYINT NOT NULL DEFAULT 0,
 published_at DATETIME, expires_at DATETIME, created_by BIGINT NOT NULL, updated_by BIGINT NOT NULL,
 created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 FOREIGN KEY(created_by) REFERENCES admin_users(id), FOREIGN KEY(updated_by) REFERENCES admin_users(id),
 INDEX idx_content_publication(status,published_at,expires_at,is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE submissions (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, topic VARCHAR(80) NOT NULL, sender_role VARCHAR(80) NOT NULL,
 contact VARCHAR(120) NOT NULL DEFAULT '', message TEXT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'new',
 admin_note VARCHAR(4096) NOT NULL DEFAULT '', ip_hash VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 INDEX idx_submissions_status(status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE login_attempts (
 attempt_key VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 attempts BIGINT NOT NULL, first_attempt_at BIGINT NOT NULL, blocked_until BIGINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE audit_logs (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, admin_user_id BIGINT, action VARCHAR(80) NOT NULL,
 entity_type VARCHAR(80) NOT NULL, entity_id BIGINT, details VARCHAR(2048) NOT NULL DEFAULT '', created_at DATETIME NOT NULL,
 FOREIGN KEY(admin_user_id) REFERENCES admin_users(id), INDEX idx_audit_created(created_at), INDEX idx_audit_action(action,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE traffic_daily (
 day DATE NOT NULL, path VARCHAR(255) NOT NULL, views BIGINT NOT NULL DEFAULT 0, unique_visitors BIGINT NOT NULL DEFAULT 0,
 last_view_at DATETIME NOT NULL, PRIMARY KEY(day,path), INDEX idx_traffic_day(day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE traffic_unique_visitors (
 day DATE NOT NULL, path VARCHAR(255) NOT NULL, visitor_hash VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 PRIMARY KEY(day,path,visitor_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE traffic_recent (
 visitor_hash VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, path VARCHAR(255) NOT NULL,
 last_recorded_at BIGINT NOT NULL, PRIMARY KEY(visitor_hash,path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE system_events (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, fingerprint VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
 severity VARCHAR(20) NOT NULL, event_type VARCHAR(80) NOT NULL, exam_type VARCHAR(20) NOT NULL DEFAULT '',
 request_path VARCHAR(255) NOT NULL DEFAULT '', target VARCHAR(500) NOT NULL DEFAULT '', http_status INTEGER,
 error_code VARCHAR(80) NOT NULL DEFAULT '', message TEXT NOT NULL, occurrences BIGINT NOT NULL DEFAULT 1,
 status VARCHAR(20) NOT NULL DEFAULT 'open', first_seen_at DATETIME NOT NULL, last_seen_at DATETIME NOT NULL, resolved_at DATETIME,
 INDEX idx_system_events_status_seen(status,last_seen_at), INDEX idx_system_events_type_seen(event_type,last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE app_settings (
 setting_key VARCHAR(191) NOT NULL PRIMARY KEY, setting_value LONGTEXT NOT NULL, updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
