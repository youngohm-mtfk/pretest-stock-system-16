CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    sku VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    brand VARCHAR(100),
    model VARCHAR(100),
    price DECIMAL(10, 2) DEFAULT 0.00,
    quantity INT DEFAULT 0,
    min_quantity INT DEFAULT 5,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS stock_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    change_amount INT NOT NULL,
    current_quantity INT NOT NULL,
    type ENUM('in', 'out', 'adjustment') NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert initial categories
INSERT IGNORE INTO categories (name) VALUES 
('CPU'), ('GPU'), ('RAM'), ('Motherboard'), ('PSU'), ('SSD/HDD'), ('Case'), ('Cooler');

CREATE TABLE IF NOT EXISTS users (
-- Ensure roles are updated if table already exists
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'seller', 'buyer') DEFAULT 'buyer';

-- Use REPLACE to update existing users
REPLACE INTO users (id, username, password, role) VALUES 
(1, 'admin', '$2y$10$Y5Xy6y5y5y5y5y5y5y5y5e9MvV.MvV.MvV.MvV.MvV.MvV.MvV.', 'admin'),
(2, 'seller', '$2y$10$Y5Xy6y5y5y5y5y5y5y5e9MvV.MvV.MvV.MvV.MvV.MvV.MvV.', 'seller'),
(3, 'buyer', '$2y$10$Y5Xy6y5y5y5y5y5y5y5e9MvV.MvV.MvV.MvV.MvV.MvV.MvV.', 'buyer');
