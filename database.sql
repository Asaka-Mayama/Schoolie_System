CREATE DATABASE IF NOT EXISTS Schoolie_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE Schoolie_db;

DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id            INT           AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)   NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE         = utf8mb4_unicode_ci;

INSERT INTO users (username, password_hash) VALUES
('admin', '$2y$10$VMsX8mdmWf52htvdkDFgte2qIEZHdALznyLSne0m5H/S1r8RnqHP.');

CREATE TABLE products (
  id          INT           AUTO_INCREMENT PRIMARY KEY,
  item_code   VARCHAR(20)   UNIQUE NOT NULL,
  item_name   VARCHAR(100)  NOT NULL,
  description TEXT          NOT NULL,
  category    VARCHAR(50)   NOT NULL,
  unit        VARCHAR(20)   NOT NULL,
  price       DECIMAL(10,2) NOT NULL,
  stock       INT           NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE         = utf8mb4_unicode_ci;

CREATE TABLE product_images (
  id         INT          AUTO_INCREMENT PRIMARY KEY,
  product_id INT          NOT NULL,
  filename   VARCHAR(255) NOT NULL,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE         = utf8mb4_unicode_ci;

INSERT INTO products (item_code, item_name, description, category, unit, price, stock) VALUES
('SUP-001', 'HBW Ballpen (Black)',         'Smooth-writing black ballpoint pen with a comfortable grip. Perfect for exams, quizzes and everyday note-taking.',                                     'Writing',                'piece', 12.00,  120),
('SUP-002', 'A4 Bond Paper (80gsm)',       'Thick 80gsm A4 bond paper that resists ink smudging. Ideal for reports, letters and school printing.',                                                   'Paper',                  'ream',  240.00, 35),
('SUP-003', 'Yellow Pad Paper',            'Classic yellow pad with 80 ruled sheets for clear, organized notes. Great for lectures and draft writing.',                                              'Paper',                  'pad',   45.00,  60),
('SUP-004', 'Heavy Duty Stapler #35',      'Metal heavy-duty stapler that binds up to 100 sheets using No. 35 staples. Built for busy offices and classrooms.',                                      'Desk Accessories',       'piece', 185.00, 15),
('SUP-005', 'Spiral Composition Notebook', 'Durable spiral notebook with 100 pages of smooth paper. Lies flat for easy writing anywhere.',                                                           'Paper',                  'piece', 35.00,  80),
('SUP-006', 'Whiteboard Marker (Blue)',    'Low-odor blue whiteboard marker with a bold chisel tip. Erases cleanly without staining the board.',                                                     'Writing',                'piece', 48.00,  45),
('SUP-007', 'Correction Tape (5mm x 8m)',  'Smooth correction tape that covers mistakes instantly with no drying time needed. 5mm wide and 8 meters long.',                                          'Correction & Adhesives', 'piece', 30.00,  50),
('SUP-008', 'Paper Clips (Vinyl Coated)',  'Box of 100 vinyl-coated paper clips that hold papers tight without tearing. Rust-resistant and reusable.',                                               'Desk Accessories',       'box',   25.00,  40);
