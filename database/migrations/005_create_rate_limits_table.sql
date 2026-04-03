CREATE TABLE IF NOT EXISTS rate_limits (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rate_key   VARCHAR(255) NOT NULL,
    hits       INT UNSIGNED NOT NULL DEFAULT 0,
    window_end DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rate_key (rate_key),
    KEY idx_window_end (window_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
