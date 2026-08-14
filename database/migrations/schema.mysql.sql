-- Esquema equivalente para MySQL 8+. Migrar de SQLite a MySQL es cambiar
-- `DB_DRIVER=mysql` (y las credenciales) en config/database.php; el código de
-- la app no cambia porque `PdoContactRepository` solo usa SQL estándar (sin
-- funciones específicas de un motor).

CREATE TABLE IF NOT EXISTS contacts (
    id CHAR(36) NOT NULL PRIMARY KEY,
    first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL,
    email VARCHAR(190) NOT NULL,
    company VARCHAR(80) NULL,
    favorite TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY contacts_email_unique (email)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS phones (
    id CHAR(36) NOT NULL PRIMARY KEY,
    contact_id CHAR(36) NOT NULL,
    type VARCHAR(20) NOT NULL,
    number VARCHAR(20) NOT NULL,
    position INT NOT NULL DEFAULT 0,
    KEY phones_contact_id_idx (contact_id),
    CONSTRAINT phones_contact_id_fk FOREIGN KEY (contact_id) REFERENCES contacts (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
