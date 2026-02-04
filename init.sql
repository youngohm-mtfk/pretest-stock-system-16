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
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default credentials: 
-- admin / 555
-- user / 555
INSERT IGNORE INTO users (username, password, role) VALUES 
('admin', '$2y$10$Y5Xy6y5y5y5y5y5y5y5y5e9MvV.MvV.MvV.MvV.MvV.MvV.MvV.', 'admin'),
('user', '$2y$10$Y5Xy6y5y5y5y5y5y5y5y5e9MvV.MvV.MvV.MvV.MvV.MvV.MvV.', 'user');
