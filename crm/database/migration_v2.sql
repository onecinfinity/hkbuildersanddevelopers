-- ============================================================
-- CRM v2 Migration — HK Builders & Developers
-- Adds: extended lead/client/agent fields, sales_manager role,
--       teams, accounts (salaries, expenses), client payments.
-- Safe to run once on the existing v1 database.
-- ============================================================

-- ------------------------------------------------------------
-- 1. USERS — extended agent profile + pay config + new role
-- ------------------------------------------------------------
ALTER TABLE users
    MODIFY COLUMN role ENUM('admin','sales_manager','agent') NOT NULL DEFAULT 'agent';

ALTER TABLE users
    ADD COLUMN phone           VARCHAR(30)    NULL AFTER email,
    ADD COLUMN address         VARCHAR(255)   NULL AFTER phone,
    ADD COLUMN cnic            VARCHAR(20)    NULL AFTER address,
    ADD COLUMN guardian_phone  VARCHAR(30)    NULL AFTER cnic,
    ADD COLUMN designation     VARCHAR(100)   NULL AFTER guardian_phone,
    -- Pay configuration (per-agent; each different). Set by admin.
    ADD COLUMN base_salary     DECIMAL(12,2)  NOT NULL DEFAULT 0.00 AFTER designation,
    ADD COLUMN commission_rate DECIMAL(5,2)   NOT NULL DEFAULT 0.00 AFTER base_salary,  -- percent, e.g. 1.50
    ADD COLUMN team_id         SMALLINT UNSIGNED NULL AFTER commission_rate;            -- FK set after teams table exists

-- ------------------------------------------------------------
-- 2. LEADS — extended fields + make name optional (phone-only leads)
-- ------------------------------------------------------------
ALTER TABLE leads
    MODIFY COLUMN name VARCHAR(150) NULL;   -- phone-only leads allowed; display "Unknown" when null

ALTER TABLE leads
    ADD COLUMN address           VARCHAR(255)   NULL AFTER country,
    ADD COLUMN project           VARCHAR(150)   NULL AFTER address,
    ADD COLUMN investment_amount DECIMAL(15,2)  NULL AFTER project,
    ADD COLUMN unit              VARCHAR(80)    NULL AFTER investment_amount,
    ADD COLUMN category          VARCHAR(80)    NULL AFTER unit;   -- Residential / Commercial / Plot

-- ------------------------------------------------------------
-- 3. TEAMS — created & managed by admin
-- ------------------------------------------------------------
CREATE TABLE teams (
    id               SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(80)   NOT NULL UNIQUE,          -- e.g. "Team A"
    sales_manager_id INT UNSIGNED  NULL,                     -- FK -> users.id (the team leader)
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (sales_manager_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sales_manager (sales_manager_id)
) ENGINE=InnoDB;

-- Now wire users.team_id -> teams.id (one team per agent for now)
ALTER TABLE users
    ADD CONSTRAINT fk_users_team
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL;

-- ------------------------------------------------------------
-- 4. CLIENTS — created when a lead is Won
-- ------------------------------------------------------------
CREATE TABLE clients (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id        INT UNSIGNED   NULL,                      -- original lead (history link)

    name           VARCHAR(150)   NULL,
    address        VARCHAR(255)   NULL,
    contact_no     VARCHAR(30)    NULL,
    project        VARCHAR(150)   NULL,
    block          VARCHAR(80)    NULL,
    unit_no        VARCHAR(80)    NULL,
    category       VARCHAR(80)    NULL,
    booking_amount DECIMAL(15,2)  NULL,

    agent_id       INT UNSIGNED   NULL,                      -- closing agent
    source_id      TINYINT UNSIGNED NULL,                    -- lead from

    -- Immature file: flagged manually by admin OR agent.
    file_status    ENUM('mature','immature') NOT NULL DEFAULT 'mature',
    file_flag_reason VARCHAR(255) NULL,                      -- why it's immature
    flagged_by     INT UNSIGNED   NULL,                      -- who flagged it
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

-- ------------------------------------------------------------
-- 5. SALARIES — one record per agent per month (Accounts panel)
--    Commission auto-calculated from matured closed files,
--    but every figure is editable until marked paid (then locked).
-- ------------------------------------------------------------
CREATE TABLE salaries (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           INT UNSIGNED  NOT NULL,                -- the agent
    period_month      DATE          NOT NULL,                -- first day of the month, e.g. 2026-06-01

    base_salary       DECIMAL(12,2) NOT NULL DEFAULT 0.00,   -- snapshot from agent profile at run time
    commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,   -- auto-calc, editable
    adjustment        DECIMAL(12,2) NOT NULL DEFAULT 0.00,   -- +bonus / -deduction
    total_amount      DECIMAL(12,2) NOT NULL DEFAULT 0.00,   -- base + commission + adjustment

    is_paid           TINYINT(1)    NOT NULL DEFAULT 0,
    paid_at           DATETIME      NULL,
    is_locked         TINYINT(1)    NOT NULL DEFAULT 0,       -- locked once paid
    notes             VARCHAR(255)  NULL,

    created_by        INT UNSIGNED  NOT NULL,
    created_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,

    UNIQUE KEY uq_user_month (user_id, period_month),         -- one salary row per agent per month
    INDEX idx_period (period_month),
    INDEX idx_is_paid (is_paid)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. CLIENT PAYMENTS — single booking amount per client for now.
--    Tracks expected vs paid, and pending reason for commission.
-- ------------------------------------------------------------
CREATE TABLE client_payments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id       INT UNSIGNED   NOT NULL,
    payment_type    ENUM('booking') NOT NULL DEFAULT 'booking',  -- room to grow (installments later)

    expected_amount DECIMAL(15,2)  NULL,
    paid_amount     DECIMAL(15,2)  NOT NULL DEFAULT 0.00,
    payment_date    DATE           NULL,
    due_date        DATE           NULL,

    is_paid         TINYINT(1)     NOT NULL DEFAULT 0,
    -- Why a payment / commission is pending.
    pending_reason  ENUM('immature_file','no_funds','other') NULL,
    notes           VARCHAR(255)   NULL,

    created_by      INT UNSIGNED   NOT NULL,
    created_at      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (client_id)  REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)   ON DELETE RESTRICT,

    INDEX idx_client (client_id),
    INDEX idx_is_paid (is_paid)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. EXPENSES — marketing / office / other (Accounts panel)
-- ------------------------------------------------------------
CREATE TABLE expenses (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category     ENUM('marketing','office','other') NOT NULL DEFAULT 'other',
    amount       DECIMAL(12,2) NOT NULL,
    description  VARCHAR(255)  NULL,
    expense_date DATE          NOT NULL,

    created_by   INT UNSIGNED  NOT NULL,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,

    INDEX idx_category (category),
    INDEX idx_expense_date (expense_date)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 8. INCOME — money received (date-wise reporting)
-- ------------------------------------------------------------
CREATE TABLE income (
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

    INDEX idx_source (source),
    INDEX idx_received_date (received_date)
) ENGINE=InnoDB;

-- ============================================================
-- Migration complete.
-- ============================================================
