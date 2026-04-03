-- ── Production database ──────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS users (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token      VARCHAR(255) NOT NULL,
    username   VARCHAR(100) NOT NULL,
    role       ENUM('streamer','audience') NOT NULL DEFAULT 'audience',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS livestreams (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    streamer_id  BIGINT UNSIGNED NOT NULL,
    title        VARCHAR(255) NOT NULL,
    status       ENUM('CREATED','LIVE','ENDED','ARCHIVED') NOT NULL DEFAULT 'CREATED',
    viewer_count INT UNSIGNED NOT NULL DEFAULT 0,
    started_at   DATETIME NULL,
    ended_at     DATETIME NULL,
    archived_at  DATETIME NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_status (status),
    KEY idx_viewer_count (viewer_count),
    KEY idx_streamer_id (streamer_id),
    CONSTRAINT fk_ls_streamer FOREIGN KEY (streamer_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS livestream_viewers (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    livestream_id BIGINT UNSIGNED NOT NULL,
    user_id       BIGINT UNSIGNED NOT NULL,
    joined_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    left_at       DATETIME NULL,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_lv_livestream (livestream_id),
    KEY idx_lv_user (user_id),
    CONSTRAINT fk_lv_stream FOREIGN KEY (livestream_id) REFERENCES livestreams(id),
    CONSTRAINT fk_lv_user   FOREIGN KEY (user_id)       REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS idempotency_keys (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idempotency_key VARCHAR(255) NOT NULL,
    endpoint        VARCHAR(100) NOT NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    response_status SMALLINT NOT NULL,
    response_body   MEDIUMTEXT NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at      DATETIME NOT NULL,
    UNIQUE KEY uq_idem (idempotency_key, endpoint, user_id),
    KEY idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rate_limits (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rate_key   VARCHAR(255) NOT NULL,
    hits       INT UNSIGNED NOT NULL DEFAULT 0,
    window_end DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rate_key (rate_key),
    KEY idx_window_end (window_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event         VARCHAR(50) NOT NULL,
    user_id       BIGINT UNSIGNED NULL,
    livestream_id BIGINT UNSIGNED NULL,
    payload       JSON NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_al_event      (event),
    KEY idx_al_user       (user_id),
    KEY idx_al_livestream (livestream_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Seed users ───────────────────────────────────────────────────────────────
INSERT INTO users (token, username, role) VALUES
    ('streamer-token-1', 'streamer_alice', 'streamer'),
    ('streamer-token-2', 'streamer_bob',   'streamer'),
    ('audience-token-1', 'viewer_charlie', 'audience'),
    ('audience-token-2', 'viewer_diana',   'audience'),
    ('audience-token-3', 'viewer_eve',     'audience');

-- ── Test database ─────────────────────────────────────────────────────────────

CREATE DATABASE IF NOT EXISTS livestream_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON livestream_test.* TO 'livestream'@'%';

USE livestream_test;

CREATE TABLE IF NOT EXISTS users (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token      VARCHAR(255) NOT NULL,
    username   VARCHAR(100) NOT NULL,
    role       ENUM('streamer','audience') NOT NULL DEFAULT 'audience',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS livestreams (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    streamer_id  BIGINT UNSIGNED NOT NULL,
    title        VARCHAR(255) NOT NULL,
    status       ENUM('CREATED','LIVE','ENDED','ARCHIVED') NOT NULL DEFAULT 'CREATED',
    viewer_count INT UNSIGNED NOT NULL DEFAULT 0,
    started_at   DATETIME NULL,
    ended_at     DATETIME NULL,
    archived_at  DATETIME NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_status (status),
    KEY idx_viewer_count (viewer_count),
    KEY idx_streamer_id (streamer_id),
    CONSTRAINT fk_ls_streamer FOREIGN KEY (streamer_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS livestream_viewers (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    livestream_id BIGINT UNSIGNED NOT NULL,
    user_id       BIGINT UNSIGNED NOT NULL,
    joined_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    left_at       DATETIME NULL,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_lv_livestream (livestream_id),
    KEY idx_lv_user (user_id),
    CONSTRAINT fk_lv_stream FOREIGN KEY (livestream_id) REFERENCES livestreams(id),
    CONSTRAINT fk_lv_user   FOREIGN KEY (user_id)       REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS idempotency_keys (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idempotency_key VARCHAR(255) NOT NULL,
    endpoint        VARCHAR(100) NOT NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    response_status SMALLINT NOT NULL,
    response_body   MEDIUMTEXT NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at      DATETIME NOT NULL,
    UNIQUE KEY uq_idem (idempotency_key, endpoint, user_id),
    KEY idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rate_limits (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rate_key   VARCHAR(255) NOT NULL,
    hits       INT UNSIGNED NOT NULL DEFAULT 0,
    window_end DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rate_key (rate_key),
    KEY idx_window_end (window_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event         VARCHAR(50) NOT NULL,
    user_id       BIGINT UNSIGNED NULL,
    livestream_id BIGINT UNSIGNED NULL,
    payload       JSON NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_al_event      (event),
    KEY idx_al_user       (user_id),
    KEY idx_al_livestream (livestream_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
