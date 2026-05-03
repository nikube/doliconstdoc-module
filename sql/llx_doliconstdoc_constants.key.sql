-- Copyright (C) 2026 DoliConstDoc contributors
-- GPL v3.

ALTER TABLE llx_doliconstdoc_constants ADD UNIQUE INDEX uk_doliconstdoc_constants_name (name);
ALTER TABLE llx_doliconstdoc_constants ADD INDEX idx_doliconstdoc_constants_module (module);
ALTER TABLE llx_doliconstdoc_constants ADD INDEX idx_doliconstdoc_constants_hidden (hidden_setting);
