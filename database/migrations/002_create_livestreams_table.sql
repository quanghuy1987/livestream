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
