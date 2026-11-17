-- Database Schema for Multi-Panel VPN Telegram Bot with Mini App & Web Admin

CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` TEXT
);

CREATE TABLE IF NOT EXISTS panels (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(150) NOT NULL,
    type VARCHAR(50) NOT NULL,
    url TEXT NOT NULL,
    username VARCHAR(150) NOT NULL,
    password TEXT NOT NULL,
    inbounds_json TEXT,
    is_active INTEGER DEFAULT 1,
    capacity INTEGER DEFAULT 500,
    current_clients INTEGER DEFAULT 0,
    custom_service_enabled INTEGER DEFAULT 0,
    price_per_gb INTEGER DEFAULT 4000,
    price_per_day INTEGER DEFAULT 1000,
    min_gb INTEGER DEFAULT 5,
    max_gb INTEGER DEFAULT 500,
    min_days INTEGER DEFAULT 1,
    max_days INTEGER DEFAULT 365,
    categories_json TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    price INTEGER NOT NULL DEFAULT 0, -- Toman
    traffic_gb INTEGER NOT NULL DEFAULT 0,
    days INTEGER NOT NULL DEFAULT 30,
    ip_limit INTEGER NOT NULL DEFAULT 1, -- 0 for unlimited
    hwid_limit INTEGER NOT NULL DEFAULT 0, -- 0 for unlimited
    panel_id INTEGER DEFAULT 0, -- 0 = Auto-balance / Any active panel
    is_active INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bank_cards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    card_number VARCHAR(30) NOT NULL,
    holder_name VARCHAR(150) NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    telegram_id BIGINT UNIQUE NOT NULL,
    username VARCHAR(100),
    first_name VARCHAR(150),
    last_name VARCHAR(150),
    balance INTEGER DEFAULT 0,
    is_admin INTEGER NOT NULL DEFAULT 0,
    step VARCHAR(100) DEFAULT 'idle',
    step_data TEXT,
    is_banned INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    plan_id INTEGER NOT NULL,
    panel_id INTEGER DEFAULT 0,
    amount INTEGER NOT NULL DEFAULT 0,
    status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'approved', 'rejected'
    receipt_photo TEXT,
    receipt_text TEXT,
    reject_reason TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME
);

CREATE TABLE IF NOT EXISTS services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER,
    user_id INTEGER NOT NULL,
    panel_id INTEGER NOT NULL,
    client_uuid VARCHAR(100) NOT NULL,
    client_email VARCHAR(150) NOT NULL,
    inbound_id INTEGER DEFAULT 0,
    subscription_url TEXT,
    config_link TEXT,
    wireguard_conf TEXT,
    traffic_gb INTEGER DEFAULT 0,
    days INTEGER DEFAULT 30,
    ip_limit INTEGER DEFAULT 1,
    hwid_limit INTEGER DEFAULT 0,
    used_traffic_bytes BIGINT DEFAULT 0,
    expires_at DATETIME,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password TEXT NOT NULL,
    name VARCHAR(150) NOT NULL,
    telegram_id BIGINT DEFAULT NULL,
    role VARCHAR(50) DEFAULT 'operator', -- 'super_admin' or 'operator'
    permissions TEXT, -- JSON array of allowed permissions
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bot_buttons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    `key` VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(150) NOT NULL,
    row_num INTEGER NOT NULL DEFAULT 1,
    order_in_row INTEGER NOT NULL DEFAULT 1,
    is_active INTEGER DEFAULT 1,
    color VARCHAR(30) DEFAULT '#2563eb',
    text_color VARCHAR(30) DEFAULT '#ffffff',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

