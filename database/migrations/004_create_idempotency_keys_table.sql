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
