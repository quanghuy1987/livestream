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
