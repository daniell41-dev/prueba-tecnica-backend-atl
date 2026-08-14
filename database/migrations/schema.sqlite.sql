-- Esquema SQLite. Se aplica con `bin/migrate.php` (CREATE TABLE IF NOT EXISTS: idempotente).

CREATE TABLE IF NOT EXISTS contacts (
    id TEXT PRIMARY KEY,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    email TEXT NOT NULL,
    company TEXT NULL,
    favorite INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

-- Email único, insensible a mayúsculas/minúsculas (Ana@x.com == ana@x.com).
CREATE UNIQUE INDEX IF NOT EXISTS contacts_email_unique ON contacts (email COLLATE NOCASE);

CREATE TABLE IF NOT EXISTS phones (
    id TEXT PRIMARY KEY,
    contact_id TEXT NOT NULL REFERENCES contacts (id) ON DELETE CASCADE,
    type TEXT NOT NULL,
    number TEXT NOT NULL,
    position INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS phones_contact_id_idx ON phones (contact_id);
