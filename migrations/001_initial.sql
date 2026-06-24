-- Cabinet Comptable Platform - Initial Schema
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS cabinets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabinet_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'collaborateur') NOT NULL DEFAULT 'collaborateur',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cabinet_id) REFERENCES cabinets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabinet_id INT NOT NULL,
    raison_sociale VARCHAR(255) NOT NULL,
    nif_encrypted TEXT NULL,
    nin_encrypted TEXT NULL,
    numero_cotisant VARCHAR(50) NULL,
    secteur ENUM('BTP', 'SERVICES', 'COMMERCE', 'AUTO_ENTREPRENEUR', 'AUTRE') NOT NULL DEFAULT 'SERVICES',
    regime_fiscal ENUM('MENSUEL', 'TRIMESTRIEL', 'ANNUEL') NOT NULL DEFAULT 'MENSUEL',
    cnas_regime ENUM('MENSUEL', 'TRIMESTRIEL') NOT NULL DEFAULT 'MENSUEL',
    wilaya VARCHAR(100) NULL,
    adresse TEXT NULL,
    activite VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (cabinet_id) REFERENCES cabinets(id) ON DELETE CASCADE,
    INDEX idx_clients_cabinet (cabinet_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payroll_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    period_year INT NOT NULL,
    period_month INT NOT NULL,
    masse_salariale DECIMAL(15,2) NOT NULL DEFAULT 0,
    effectif INT NOT NULL DEFAULT 0,
    entrees INT NOT NULL DEFAULT 0,
    sorties INT NOT NULL DEFAULT 0,
    nombre_assurees INT NULL,
    source ENUM('manual', 'ocr', 'import') NOT NULL DEFAULT 'manual',
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_payroll_period (client_id, period_year, period_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sales_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    period_year INT NOT NULL,
    period_month INT NULL,
    ca_biens DECIMAL(15,2) NOT NULL DEFAULT 0,
    ca_services DECIMAL(15,2) NOT NULL DEFAULT 0,
    ca_auto_entrepreneur DECIMAL(15,2) NOT NULL DEFAULT 0,
    irg_acompte_base DECIMAL(15,2) NULL,
    source ENUM('manual', 'ocr', 'import') NOT NULL DEFAULT 'manual',
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cotisation_rate_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    label VARCHAR(255) NOT NULL,
    taux DECIMAL(8,4) NOT NULL,
    secteur VARCHAR(50) NULL,
    declaration_type VARCHAR(50) NOT NULL,
    valid_from DATE NOT NULL,
    valid_to DATE NULL,
    INDEX idx_rates_type (declaration_type, secteur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS deadline_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    declaration_type VARCHAR(50) NOT NULL,
    frequency ENUM('monthly', 'quarterly', 'annual') NOT NULL,
    due_day INT NOT NULL,
    due_month INT NULL,
    label_fr VARCHAR(255) NOT NULL,
    conditions_json JSON NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS automation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    conditions_json JSON NULL,
    action_json JSON NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS declarations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    type ENUM('CNAS_MENSUELLE', 'CNAS_TRIMESTRIELLE', 'CACOBATPH', 'G50', 'G12', 'G12_BIS') NOT NULL,
    period_year INT NOT NULL,
    period_month INT NULL,
    period_quarter INT NULL,
    status ENUM('DRAFT_CALCULATED', 'APPROVED', 'SUBMITTED') NOT NULL DEFAULT 'DRAFT_CALCULATED',
    computed_fields JSON NOT NULL,
    payroll_entry_id INT NULL,
    sales_entry_id INT NULL,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    submitted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (payroll_entry_id) REFERENCES payroll_entries(id) ON DELETE SET NULL,
    FOREIGN KEY (sales_entry_id) REFERENCES sales_entries(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_declarations_client (client_id, type, period_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS declaration_pdf_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    declaration_type VARCHAR(50) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    field_map JSON NULL,
    version VARCHAR(20) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabinet_id INT NOT NULL,
    client_id INT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    mime VARCHAR(100) NOT NULL,
    doc_type VARCHAR(50) NULL,
    status ENUM('pending', 'processing', 'done', 'failed', 'awaiting_review') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (cabinet_id) REFERENCES cabinets(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS document_ocr_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    raw_text LONGTEXT NULL,
    extracted_json JSON NULL,
    confidence DECIMAL(5,2) NULL,
    extraction_source ENUM('template', 'llm', 'mixed') NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabinet_id INT NOT NULL,
    client_id INT NULL,
    declaration_id INT NULL,
    type VARCHAR(50) NOT NULL,
    severity ENUM('info', 'warning', 'critical') NOT NULL DEFAULT 'warning',
    message_fr TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cabinet_id) REFERENCES cabinets(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (declaration_id) REFERENCES declarations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabinet_id INT NULL,
    user_id INT NULL,
    action VARCHAR(50) NOT NULL,
    entity VARCHAR(50) NOT NULL,
    entity_id INT NULL,
    meta JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    payload JSON NOT NULL,
    status ENUM('pending', 'processing', 'done', 'failed') NOT NULL DEFAULT 'pending',
    attempts INT NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    INDEX idx_jobs_pending (type, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
