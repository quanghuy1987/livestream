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
