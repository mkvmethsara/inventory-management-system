CREATE DATABASE IF NOT EXISTS warehouse_db;
USE warehouse_db;

CREATE TABLE suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20)
);

CREATE TABLE IF NOT EXISTS Items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    rfid_tag_id VARCHAR(50) UNIQUE,
    item_name VARCHAR(50) NOT NULL,
    item_code VARCHAR(20) NOT NULL,
    category VARCHAR(30),
    minimum_level INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    supplier_id INT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id)
);

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR (255),
    role VARCHAR (30) NOT NULL
);


CREATE TABLE locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    location_code VARCHAR(50) NOT NULL,
    description VARCHAR(150)
);
CREATE TABLE item_batches (
    batch_id INT ,
    item_id INT NOT NULL,
    expiry_date DATE,
    received_date DATE NOT NULL,

    PRIMARY KEY (item_id, batch_id),

    CONSTRAINT fk_batch_item
        FOREIGN KEY (item_id)
        REFERENCES Items(item_id)
        ON DELETE CASCADE
);

CREATE TABLE stock (
    item_id INT NOT NULL,
    batch_id INT NOT NULL,
    location_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,

    PRIMARY KEY (item_id, batch_id, location_id),

    FOREIGN KEY (item_id, batch_id)
        REFERENCES item_batches(item_id, batch_id)
        ON DELETE CASCADE,

    FOREIGN KEY (location_id)
        REFERENCES locations(location_id)
);



CREATE TABLE item_suppliers (
    item_id INT NOT NULL,
    supplier_id INT NOT NULL,

    PRIMARY KEY (item_id, supplier_id),

    FOREIGN KEY (item_id) REFERENCES Items(item_id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id)
);

CREATE TABLE stock_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,

    item_id INT NOT NULL,
    batch_id INT NOT NULL,

    location_id INT NOT NULL,
    from_location_id INT NULL,

    user_id INT NOT NULL,

    transaction_type ENUM('IN','OUT','TRANSFER') NOT NULL,
    quantity INT NOT NULL,

    transaction_time DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (item_id, batch_id)
        REFERENCES item_batches(item_id, batch_id),

    FOREIGN KEY (location_id)
        REFERENCES locations(location_id),

    FOREIGN KEY (from_location_id)
        REFERENCES locations(location_id),

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
);
INSERT INTO item_batches
(item_id, expiry_date, received_date)
VALUES
(1, '2026-05-30', CURDATE());
