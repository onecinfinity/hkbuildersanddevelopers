-- ============================================================
-- Builder Management Migration
-- Run once on both local and production
-- ============================================================

CREATE TABLE IF NOT EXISTS builders (
    id             INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(200)   NOT NULL,
    contact_person VARCHAR(150)   NULL,
    phone          VARCHAR(50)    NULL,
    email          VARCHAR(150)   NULL,
    address        TEXT           NULL,
    notes          TEXT           NULL,
    status         ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by     INT UNSIGNED   NOT NULL,
    created_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS builder_projects (
    id           INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    builder_id   INT UNSIGNED   NOT NULL,
    name         VARCHAR(200)   NOT NULL,
    location     VARCHAR(200)   NULL,
    total_plots  INT UNSIGNED   NULL DEFAULT 0,
    total_value  DECIMAL(16,2)  NULL DEFAULT 0,
    status       ENUM('active','completed','on_hold') NOT NULL DEFAULT 'active',
    notes        TEXT           NULL,
    created_by   INT UNSIGNED   NOT NULL,
    created_at   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (builder_id) REFERENCES builders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS builder_payments (
    id            INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    builder_id    INT UNSIGNED   NOT NULL,
    project_id    INT UNSIGNED   NULL,
    amount        DECIMAL(14,2)  NOT NULL DEFAULT 0,
    payment_type  ENUM('advance','installment','final','other') NOT NULL DEFAULT 'installment',
    payment_date  DATE           NOT NULL,
    payment_month TINYINT UNSIGNED NULL,
    payment_year  SMALLINT UNSIGNED NULL,
    reference     VARCHAR(150)   NULL,
    notes         TEXT           NULL,
    created_by    INT UNSIGNED   NOT NULL,
    created_at    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (builder_id) REFERENCES builders(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES builder_projects(id) ON DELETE SET NULL
) ENGINE=InnoDB;
