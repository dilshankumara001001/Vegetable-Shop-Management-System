DROP DATABASE IF EXISTS vegie_pro;
CREATE DATABASE vegie_pro DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vegie_pro;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO users (username, password, full_name, role) VALUES
('admin', SHA2('admin123',256), 'Administrator', 'admin'),
('staff', SHA2('staff123',256), 'Shop Staff', 'staff');

CREATE TABLE vegetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50),
    unit VARCHAR(10) NOT NULL DEFAULT 'kg',
    reorder_level DECIMAL(10,2) NOT NULL DEFAULT 20,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO vegetables (name, category, unit, reorder_level) VALUES
('Carrot','Upcountry','kg',20),('Beans','Upcountry','kg',15),
('Tomato','Lowcountry','kg',25),('Leeks','Upcountry','kg',10),
('Cabbage','Upcountry','kg',20),('Brinjal','Lowcountry','kg',15),
('Potato','Upcountry','kg',50),('Green Chilli','Lowcountry','kg',10);

CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO suppliers (name, phone, address) VALUES
('Nimal Perera','0771234567','Kandy'),
('Sunil Silva','0719876543','Dambulla'),
('Kamal Fernando','0765554433','Bandarawela');

CREATE TABLE stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veg_id INT NOT NULL,
    supplier_id INT,
    quantity_kg DECIMAL(10,2) NOT NULL,
    remaining_kg DECIMAL(10,2) NOT NULL,
    buying_price DECIMAL(10,2) NOT NULL,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veg_id) REFERENCES vegetables(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    INDEX idx_veg_rem (veg_id, remaining_kg)
) ENGINE=InnoDB;

CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(30) NOT NULL,
    veg_id INT NOT NULL,
    buyer_name VARCHAR(100),
    buyer_phone VARCHAR(20),
    quantity_kg DECIMAL(10,2) NOT NULL,
    selling_price DECIMAL(10,2) NOT NULL,
    cost_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT,
    FOREIGN KEY (veg_id) REFERENCES vegetables(id),
    INDEX idx_inv (invoice_no),
    INDEX idx_date (sale_date)
) ENGINE=InnoDB;

CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veg_id INT NOT NULL,
    type ENUM('in','out','adjust') NOT NULL,
    quantity_kg DECIMAL(10,2) NOT NULL,
    ref VARCHAR(50),
    note VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veg_id) REFERENCES vegetables(id) ON DELETE CASCADE
) ENGINE=InnoDB;