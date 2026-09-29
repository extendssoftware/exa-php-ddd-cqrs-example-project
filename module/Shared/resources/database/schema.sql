CREATE TABLE IF NOT EXISTS outbox
(
    id            BINARY(16)   NOT NULL,
    event_type    VARCHAR(100) NOT NULL,
    event_version INT UNSIGNED NOT NULL,
    payload       JSON         NOT NULL,
    recorded_at   DATETIME(6)  NOT NULL,
    PRIMARY KEY (id)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;
