CREATE TABLE IF NOT EXISTS users (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token      VARCHAR(255) NOT NULL,
    username   VARCHAR(100) NOT NULL,
    role       ENUM('streamer','audience') NOT NULL DEFAULT 'audience',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
