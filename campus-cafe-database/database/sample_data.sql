-- OPTIONAL: Run this ONLY ONCE after database/schema.sql.
-- It adds sample records for testing the five tables.
USE campus_cafe;

INSERT INTO customers (customer_name, email, phone) VALUES
('Alex Jordan', 'alex.jordan@example.com', '555-0101'),
('Taylor Lee', 'taylor.lee@example.com', '555-0102');

INSERT INTO menu_items (item_name, category, price, is_available) VALUES
('Iced Latte', 'Coffee', 4.50, TRUE),
('Plain Bagel', 'Bakery', 2.75, TRUE),
('Breakfast Sandwich', 'Food', 6.25, TRUE),
('Hot Chocolate', 'Drinks', 3.50, TRUE);

INSERT INTO cafe_orders (customer_id, order_status) VALUES
(1, 'Completed'),
(2, 'Preparing');

INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price) VALUES
(1, 1, 2, 4.50),
(1, 2, 1, 2.75),
(2, 3, 1, 6.25),
(2, 4, 1, 3.50);

INSERT INTO payments (order_id, payment_method, amount, payment_status) VALUES
(1, 'Card', 11.75, 'Paid');
