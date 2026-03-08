INSERT INTO settings (setting_key, setting_value) VALUES
('whatsapp_number', '256772985659'),
('currency_label', 'UGX'),
('products_per_page', '15'),
('show_out_of_stock', '1')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO admins (username, email, full_name, password_hash, role, status)
VALUES ('admin', 'admin@example.com', 'Shop Administrator', '$2y$12$C7rCxAHXeciEkys7bzY/JeaGwsT1nslqD9PfYXZ.jlSO/Gq.Gzn1i', 'admin', 1)
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO categories (name, slug, description, status)
VALUES
('Phones', 'phones', 'Smartphones and accessories', 1),
('Laptops', 'laptops', 'Business and personal laptops', 1),
('Networking', 'networking', 'Routers and office networking devices', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), status = VALUES(status);

INSERT INTO products (category_id, name, slug, short_description, description, price, discount_price, stock_qty, status, featured, main_image)
VALUES
((SELECT id FROM categories WHERE slug = 'phones' LIMIT 1), 'Galaxy A15', 'galaxy-a15', '128GB storage smartphone', 'Reliable battery, clear camera, and dual SIM support.', 750000, 699000, 20, 1, 1, 'placeholder.svg'),
((SELECT id FROM categories WHERE slug = 'laptops' LIMIT 1), 'Lenovo ThinkPad E14', 'lenovo-thinkpad-e14', '14-inch business laptop', 'Intel Core i5, 16GB RAM, 512GB SSD.', 3250000, NULL, 8, 1, 1, 'placeholder.svg'),
((SELECT id FROM categories WHERE slug = 'networking' LIMIT 1), 'TP-Link Archer C6', 'tp-link-archer-c6', 'Dual-band Wi-Fi router', 'Strong home and office coverage with 4 external antennas.', 280000, 250000, 35, 1, 0, 'placeholder.svg')
ON DUPLICATE KEY UPDATE
short_description = VALUES(short_description),
description = VALUES(description),
price = VALUES(price),
discount_price = VALUES(discount_price),
stock_qty = VALUES(stock_qty),
status = VALUES(status),
featured = VALUES(featured);
