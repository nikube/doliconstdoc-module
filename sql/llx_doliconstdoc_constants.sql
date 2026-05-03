-- Copyright (C) 2026 DoliConstDoc contributors
-- GPL v3.

CREATE TABLE llx_doliconstdoc_constants (
  rowid           INT AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(255) NOT NULL,
  type            VARCHAR(32),
  default_value   TEXT,
  module          VARCHAR(64),
  purpose         VARCHAR(500),
  description     TEXT,
  impact          TEXT,
  possible_values TEXT,
  hidden_setting  TINYINT DEFAULT 0,
  hidden_setting_guess TINYINT DEFAULT 0,
  admin_ui_files  TEXT,
  conf_php_wiring TEXT,
  doc_quality     TINYINT DEFAULT 0,
  confidence      VARCHAR(32)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
