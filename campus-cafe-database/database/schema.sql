-- Campus Cafe Database Schema
-- Five connected tables for a campus cafe ordering system.
-- MariaDB / MySQL compatible.

CREATE DATABASE IF NOT EXISTS campus_cafe
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE campus_cafe;

-- 1. Customers who place orders
CREATE TABLE IF NOT EXISTS customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name TINYTEXT NOT NULL,
    email TINYTEXT,
    phone TINYTEXT
) ENGINE=InnoDB;

-- 2. Food and drinks sold at the cafe
CREATE TABLE IF NOT EXISTS menu_items (
    menu_item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name TINYTEXT NOT NULL,
    category TINYTEXT NOT NULL,
    price DECIMAL(8,2) NOT NULL,
    is_available BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_menu_price CHECK (price >= 0)
) ENGINE=InnoDB;

-- 3. Each order belongs to a customer
CREATE TABLE IF NOT EXISTS cafe_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    order_status TINYTEXT NOT NULL,
    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
) ENGINE=InnoDB;

-- 4. Line items connect orders to menu items
CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(8,2) NOT NULL,
    CONSTRAINT chk_order_item_quantity CHECK (quantity > 0),
    CONSTRAINT chk_order_item_price CHECK (unit_price >= 0),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES cafe_orders(order_id),
    CONSTRAINT fk_order_items_menu_item
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(menu_item_id)
) ENGINE=InnoDB;

-- 5. Zero or one payment per order; UNIQUE enforces at most one
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    payment_method TINYTEXT NOT NULL,
    amount DECIMAL(8,2) NOT NULL,
    payment_status TINYTEXT NOT NULL,
    CONSTRAINT chk_payment_amount CHECK (amount >= 0),
    CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id) REFERENCES cafe_orders(order_id)
) ENGINE=InnoDB;
