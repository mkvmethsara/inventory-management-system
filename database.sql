CREATE DATABASE IF NOT EXISTS warehouse_db;
USE warehouse_db;

CREATE TABLE IF NOT EXISTS Items (
    item_id AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(50) NOT NULL,
    item_code VARCHAR(20) NOT NULL,
    category VARCHAR(30),
    minimum_level INT DEFAULT 0,
    create_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    supplier_id INT,
);

