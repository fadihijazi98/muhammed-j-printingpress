CREATE TABLE users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    name          TEXT    NOT NULL,
    email         TEXT    NOT NULL UNIQUE,
    password_hash TEXT    NOT NULL,
    role          TEXT    NOT NULL DEFAULT 'staff',
    is_active     INTEGER NOT NULL DEFAULT 1,

    created_at    TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at    TEXT
);

CREATE TABLE merchants (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT    NOT NULL,
    phone      TEXT,
    note       TEXT,
    is_active  INTEGER NOT NULL DEFAULT 1,

    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at TEXT
);

/* default_unit_price is the suggested price only; each sale keeps the price it was sold at. */
CREATE TABLE job_types (
    id                 INTEGER PRIMARY KEY AUTOINCREMENT,
    name               TEXT    NOT NULL,
    unit               TEXT    NOT NULL,
    default_unit_price INTEGER NOT NULL DEFAULT 0,
    is_active          INTEGER NOT NULL DEFAULT 1,

    created_by         INTEGER REFERENCES users(id),
    updated_by         INTEGER REFERENCES users(id),
    created_at         TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at         TEXT
);

CREATE TABLE sales (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    merchant_id INTEGER NOT NULL REFERENCES merchants(id),
    job_type_id INTEGER REFERENCES job_types(id),
    description TEXT,

    quantity    REAL    NOT NULL,
    unit_price  INTEGER NOT NULL,
    total       INTEGER NOT NULL,

    sale_date   TEXT    NOT NULL,
    note        TEXT,

    created_by  INTEGER REFERENCES users(id),
    updated_by  INTEGER REFERENCES users(id),
    created_at  TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at  TEXT
);

CREATE INDEX idx_sales_merchant ON sales (merchant_id);
CREATE INDEX idx_sales_date     ON sales (sale_date);

CREATE TABLE payments (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    merchant_id  INTEGER NOT NULL REFERENCES merchants(id),
    amount       INTEGER NOT NULL,
    payment_date TEXT    NOT NULL,
    method       TEXT,
    note         TEXT,

    created_by   INTEGER REFERENCES users(id),
    updated_by   INTEGER REFERENCES users(id),
    created_at   TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at   TEXT
);

CREATE INDEX idx_payments_merchant ON payments (merchant_id);
CREATE INDEX idx_payments_date     ON payments (payment_date);

CREATE TABLE discounts (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    merchant_id   INTEGER NOT NULL REFERENCES merchants(id),
    amount        INTEGER NOT NULL,
    discount_date TEXT    NOT NULL,
    reason        TEXT,

    created_by    INTEGER REFERENCES users(id),
    updated_by    INTEGER REFERENCES users(id),
    created_at    TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at    TEXT
);

CREATE INDEX idx_discounts_merchant ON discounts (merchant_id);

CREATE TABLE materials (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    name              TEXT    NOT NULL,
    unit              TEXT    NOT NULL,
    default_unit_cost INTEGER NOT NULL DEFAULT 0,
    is_active         INTEGER NOT NULL DEFAULT 1,

    created_by        INTEGER REFERENCES users(id),
    updated_by        INTEGER REFERENCES users(id),
    created_at        TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at        TEXT
);

CREATE TABLE material_purchases (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    material_id   INTEGER NOT NULL REFERENCES materials(id),

    quantity      REAL    NOT NULL,
    unit_cost     INTEGER NOT NULL,
    total         INTEGER NOT NULL,

    purchase_date TEXT    NOT NULL,
    supplier      TEXT,
    note          TEXT,

    created_by    INTEGER REFERENCES users(id),
    updated_by    INTEGER REFERENCES users(id),
    created_at    TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at    TEXT
);

CREATE INDEX idx_material_purchases_date ON material_purchases (purchase_date);

CREATE TABLE workers (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT    NOT NULL,
    phone       TEXT,
    hourly_rate INTEGER NOT NULL DEFAULT 0,
    is_active   INTEGER NOT NULL DEFAULT 1,

    created_by  INTEGER REFERENCES users(id),
    updated_by  INTEGER REFERENCES users(id),
    created_at  TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at  TEXT
);

CREATE TABLE worker_payments (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    worker_id   INTEGER NOT NULL REFERENCES workers(id),

    hours       REAL    NOT NULL,
    hourly_rate INTEGER NOT NULL,
    total       INTEGER NOT NULL,

    work_date   TEXT    NOT NULL,
    note        TEXT,

    created_by  INTEGER REFERENCES users(id),
    updated_by  INTEGER REFERENCES users(id),
    created_at  TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at  TEXT
);

CREATE INDEX idx_worker_payments_date ON worker_payments (work_date);

/* user_name is copied in so the trail still reads correctly if the account is later renamed. */
CREATE TABLE audit_log (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER REFERENCES users(id),
    user_name  TEXT,
    action     TEXT NOT NULL,
    entity     TEXT NOT NULL,
    entity_id  INTEGER,
    summary    TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE INDEX idx_audit_created ON audit_log (created_at);
