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
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'seller', 'buyer') DEFAULT 'buyer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Ensure roles are updated if table already exists
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'seller', 'buyer') DEFAULT 'buyer';

-- Use REPLACE to update existing users
REPLACE INTO users (id, username, password, role) VALUES 
(1, 'admin', '$2y$10$Y5Xy6y5y5y5y5y5y5y5y5e9MvV.MvV.MvV.MvV.MvV.MvV.MvV.', 'admin'),
(2, 'seller', '$2y$10$Y5Xy6y5y5y5y5y5y5y5e9MvV.MvV.MvV.MvV.MvV.MvV.MvV.', 'seller'),
(3, 'buyer', '$2y$10$Y5Xy6y5y5y5y5y5y5y5e9MvV.MvV.MvV.MvV.MvV.MvV.MvV.', 'buyer');

-- --- NEW TABLES FOR COMPUTER SETS AND ORDERS ---

CREATE TABLE IF NOT EXISTS product_sets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS product_set_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    set_id INT,
    product_id INT,
    quantity INT DEFAULT 1,
    FOREIGN KEY (set_id) REFERENCES product_sets(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'paid', 'shipped', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT NULL,
    set_id INT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    type ENUM('product', 'set') NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (set_id) REFERENCES product_sets(id) ON DELETE SET NULL
);
