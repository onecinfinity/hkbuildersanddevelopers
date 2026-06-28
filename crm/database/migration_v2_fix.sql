-- ============================================================
-- CRM v2 Fix Migration — Safe version (skips existing columns)
-- Run this if migration_v2.sql gave "Duplicate column" errors.
-- ============================================================

-- Users table — add only missing columns (IF NOT EXISTS = safe)
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS phone           VARCHAR(30)       NULL AFTER email,
    ADD COLUMN IF NOT EXISTS address         VARCHAR(255)      NULL AFTER phone,
    ADD COLUMN IF NOT EXISTS cnic            VARCHAR(20)       NULL AFTER address,
    ADD COLUMN IF NOT EXISTS guardian_phone  VARCHAR(30)       NULL AFTER cnic,
    ADD COLUMN IF NOT EXISTS designation     VARCHAR(100)      NULL AFTER guardian_phone,
    ADD COLUMN IF NOT EXISTS base_salary     DECIMAL(12,2)     NOT NULL DEFAULT 0.00 AFTER designation,
    ADD COLUMN IF NOT EXISTS commission_rate DECIMAL(5,2)      NOT NULL DEFAULT 0.00 AFTER base_salary,
    ADD COLUMN IF NOT EXISTS team_id         SMALLINT UNSIGNED NULL AFTER commission_rate;

-- Extend role ENUM to include sales_manager (safe — MySQL ignores if already exists)
ALTER TABLE users
    MODIFY COLUMN role ENUM('admin','sales_manager','agent') NOT NULL DEFAULT 'agent';

-- Leads table — add new columns if missing
ALTER TABLE leads
    MODIFY COLUMN name VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS address           VARCHAR(255)  NULL AFTER country,
    ADD COLUMN IF NOT EXISTS project           VARCHAR(150)  NULL AFTER address,
    ADD COLUMN IF NOT EXISTS investment_amount DECIMAL(15,2) NULL AFTER project,
    ADD COLUMN IF NOT EXISTS unit              VARCHAR(80)   NULL AFTER investment_amount,
    ADD COLUMN IF NOT EXISTS category          VARCHAR(80)   NULL AFTER unit;

-- Teams table
CREATE TABLE IF NOT EXISTS teams (
    id               SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(80)   NOT NULL UNIQUE,
    sales_manager_id INT UNSIGNED  NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sales_manager_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sales_manager (sales_manager_id)
) ENGINE=InnoDB;

-- Add FK from users.team_id to teams (only if not already added)
ALTER TABLE users
    ADD CONSTRAINT IF NOT EXISTS fk_users_team
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL;

-- Clients table
CREATE TABLE IF NOT EXISTS clients (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id        INT UNSIGNED   NULL,
    name           VARCHAR(150)   NULL,
    address        VARCHAR(255)   NULL,
    contact_no     VARCHAR(30)    NULL,
    project        VARCHAR(150)   NULL,
    block          VARCHAR(80)    NULL,
    unit_no        VARCHAR(80)    NULL,
    category       VARCHAR(80)    NULL,
    booking_amount DECIMAL(15,2)  NULL,
    agent_id       INT UNSIGNED   NULL,
    source_id      TINYINT UNSIGNED NULL,
    file_status    ENUM('mature','immature') NOT NULL DEFAULT 'mature',
    file_flag_reason VARCHAR(255) NULL,
    flagged_by     INT UNSIGNED   NULL,
    flagged_at     DATETIME       NULL,
    created_by     INT UNSIGNED   NOT NULL,
    created_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id)    REFERENCES leads(id)        ON DELETE SET NULL,
    FOREIGN KEY (agent_id)   REFERENCES users(id)        ON DELETE SET NULL,
    FOREIGN KEY (source_id)  REFERENCES lead_sources(id) ON DELETE SET NULL,
    FOREIGN KEY (flagged_by) REFERENCES users(id)        ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)        ON DELETE RESTRICT,
    INDEX idx_agent       (agent_id),
    INDEX idx_file_status (file_status),
    INDEX idx_created_at  (created_at)
) ENGINE=InnoDB;

-- Salaries table
CREATE TABLE IF NOT EXISTS salaries (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           INT UNSIGNED  NOT NULL,
    period_month      DATE          NOT NULL,
    base_salary       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    adjustment        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    is_paid           TINYINT(1)    NOT NULL DEFAULT 0,
    paid_at           DATETIME      NULL,
    is_locked         TINYINT(1)    NOT NULL DEFAULT 0,
    notes             VARCHAR(255)  NULL,
    created_by        INT UNSIGNED  NOT NULL,
    created_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    UNIQUE KEY uq_user_month (user_id, period_month),
    INDEX idx_period  (period_month),
    INDEX idx_is_paid (is_paid)
) ENGINE=InnoDB;

-- Client payments table
CREATE TABLE IF NOT EXISTS client_payments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id       INT UNSIGNED   NOT NULL,
    payment_type    ENUM('booking') NOT NULL DEFAULT 'booking',
    expected_amount DECIMAL(15,2)  NULL,
    paid_amount     DECIMAL(15,2)  NOT NULL DEFAULT 0.00,
    payment_date    DATE           NULL,
    due_date        DATE           NULL,
    is_paid         TINYINT(1)     NOT NULL DEFAULT 0,
    pending_reason  ENUM('immature_file','no_funds','other') NULL,
    notes           VARCHAR(255)   NULL,
    created_by      INT UNSIGNED   NOT NULL,
    created_at      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id)  REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)   ON DELETE RESTRICT,
    INDEX idx_client  (client_id),
    INDEX idx_is_paid (is_paid)
) ENGINE=InnoDB;

-- Expenses table
CREATE TABLE IF NOT EXISTS expenses (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category     ENUM('marketing','office','other') NOT NULL DEFAULT 'other',
    amount       DECIMAL(12,2) NOT NULL,
    description  VARCHAR(255)  NULL,
    expense_date DATE          NOT NULL,
    created_by   INT UNSIGNED  NOT NULL,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_category     (category),
    INDEX idx_expense_date (expense_date)
) ENGINE=InnoDB;

-- Income table
CREATE TABLE IF NOT EXISTS income (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source        ENUM('booking','installment','other') NOT NULL DEFAULT 'booking',
    client_id     INT UNSIGNED   NULL,
    amount        DECIMAL(15,2)  NOT NULL,
    description   VARCHAR(255)   NULL,
    received_date DATE           NOT NULL,
    created_by    INT UNSIGNED   NOT NULL,
    created_at    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id)  REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)   ON DELETE RESTRICT,
    INDEX idx_source        (source),
    INDEX idx_received_date (received_date)
) ENGINE=InnoDB;

-- ============================================================
-- Done. All tables and columns now exist safely.
-- ============================================================
