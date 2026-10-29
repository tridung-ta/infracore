-- Manual execution only. Select and verify the target schema first.
-- Back up DB/source/config first. DDL implicitly commits; ROLLBACK cannot undo CREATE.
-- Do not rerun if the table exists: inspect its structure and data instead.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE ita_user_permissions (
    store_id INT NOT NULL,
    user_id INT NOT NULL,
    permission_key VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    active TINYINT UNSIGNED NOT NULL DEFAULT 1,
    updated_by INT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (store_id,user_id,permission_key),
    KEY idx_ita_permissions_key (store_id,permission_key,active,user_id),
    CONSTRAINT ck_ita_permissions_store CHECK (store_id > 0),
    CONSTRAINT ck_ita_permissions_user CHECK (user_id > 0),
    CONSTRAINT ck_ita_permissions_active CHECK (active IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Rollback: keep this additive table; restore reviewed source/config, not DROP/data restore.
