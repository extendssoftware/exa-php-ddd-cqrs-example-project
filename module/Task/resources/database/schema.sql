CREATE TABLE IF NOT EXISTS task
(
    id           BINARY(16)   NOT NULL,
    title        VARCHAR(100) NOT NULL,
    status       VARCHAR(20)  NOT NULL,
    created_at   DATETIME(6)  NOT NULL,
    completed_at DATETIME(6)  NULL,
    PRIMARY KEY (id)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;