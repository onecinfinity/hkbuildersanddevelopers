-- ============================================================
-- Accounts Section Migration
-- Run once on both local (crm_system) and production (u813506845_crm)
-- ============================================================

-- Commission pending reasons lookup table
CREATE TABLE IF NOT EXISTS commission_pending_reason (
    id         TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reason     VARCHAR(150) NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO commission_pending_reason (reason) VALUES
    ('Client Payment Pending'),
    ('Paperwork Incomplete'),
    ('Legal Hold'),
    ('Management Approval Required'),
    ('Dispute / Under Review'),
    ('Other');

-- Agent commission records
CREATE TABLE IF NOT EXISTS commission_payments (
    id                INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    agent_id          INT UNSIGNED   NOT NULL,
    lead_id           INT UNSIGNED   NULL,
    client_name       VARCHAR(150)   NULL,
    project           VARCHAR(150)   NULL,
    plot_number       VARCHAR(50)    NULL,
    total_commission  DECIMAL(14,2)  NOT NULL DEFAULT 0,
    maturity_status   ENUM('mature','immature') NOT NULL DEFAULT 'immature',
    payment_status    ENUM('paid','pending')    NOT NULL DEFAULT 'pending',
    paid_amount       DECIMAL(14,2)  NOT NULL DEFAULT 0,
    pending_reason_id TINYINT UNSIGNED NULL,
    pending_notes     TEXT           NULL,
    sale_month        TINYINT UNSIGNED NULL,
    sale_year         SMALLINT UNSIGNED NULL,
    notes             TEXT           NULL,
    created_by        INT UNSIGNED   NOT NULL,
    created_at        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (agent_id)          REFERENCES users(id)                     ON DELETE RESTRICT,
    FOREIGN KEY (lead_id)           REFERENCES leads(id)                     ON DELETE SET NULL,
    FOREIGN KEY (pending_reason_id) REFERENCES commission_pending_reason(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by)        REFERENCES users(id)                     ON DELETE RESTRICT,

    INDEX idx_agent_id      (agent_id),
    INDEX idx_maturity      (maturity_status),
    INDEX idx_payment_status(payment_status)
) ENGINE=InnoDB;

-- General expenses (marketing, salary, general)
CREATE TABLE IF NOT EXISTS expenses (
    id             INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    type           ENUM('marketing','salary','general') NOT NULL,
    category       VARCHAR(100)   NOT NULL,
    description    TEXT           NULL,
    amount         DECIMAL(14,2)  NOT NULL DEFAULT 0,
    agent_id       INT UNSIGNED   NULL,
    expense_date   DATE           NOT NULL,
    expense_month  TINYINT UNSIGNED NULL,
    expense_year   SMALLINT UNSIGNED NULL,
    created_by     INT UNSIGNED   NOT NULL,
    created_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (agent_id)   REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,

    INDEX idx_type        (type),
    INDEX idx_expense_date(expense_date)
) ENGINE=InnoDB;
