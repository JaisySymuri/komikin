-- KOMIKIN: Comic Rental Management System
-- Database: rental_komik
-- Compatible baseline: MySQL 8.x / MariaDB
-- Run this script in phpMyAdmin after selecting the rental_komik database.

USE rental_komik;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS rental_details;
DROP TABLE IF EXISTS rentals;
DROP TABLE IF EXISTS comics;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users: administrators, employees, and customers
CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee', 'customer') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Categories: comic genres
CREATE TABLE categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_name VARCHAR(80) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Comics: catalog and currently available stock
CREATE TABLE comics (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(180) NOT NULL,
    author VARCHAR(120) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    rental_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_comics_category (category_id),
    KEY idx_comics_title (title),
    CONSTRAINT fk_comics_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Rentals: transaction header
CREATE TABLE rentals (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    rental_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    due_date DATE NOT NULL,
    status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rentals_user (user_id),
    KEY idx_rentals_status (status),
    KEY idx_rentals_due_date (due_date),
    CONSTRAINT fk_rentals_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Rental details: individual comic titles in each transaction
-- returned_at and late_fee are tracked per detail so items can be returned separately.
CREATE TABLE rental_details (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    rental_id INT UNSIGNED NOT NULL,
    comic_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    returned_at DATETIME DEFAULT NULL,
    late_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rental_comic (rental_id, comic_id),
    KEY idx_rental_details_comic (comic_id),
    KEY idx_rental_details_returned (returned_at),
    CONSTRAINT fk_rental_details_rental
        FOREIGN KEY (rental_id) REFERENCES rentals (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_rental_details_comic
        FOREIGN KEY (comic_id) REFERENCES comics (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial comic categories
INSERT INTO categories (category_name) VALUES
    ('Action'),
    ('Adventure'),
    ('Comedy'),
    ('Drama'),
    ('Fantasy'),
    ('Horror'),
    ('Mystery'),
    ('Romance'),
    ('Sci-Fi'),
    ('Slice of Life');

-- Optional sample catalog records. These are demo records; edit or remove them later.
INSERT INTO comics
    (category_id, title, author, description, rental_price, stock)
VALUES
    (
        (SELECT id FROM categories WHERE category_name = 'Adventure' LIMIT 1),
        'Petualangan di Negeri Awan',
        'Komik Demo',
        'Contoh data komik untuk menguji katalog dan transaksi penyewaan.',
        5000.00,
        5
    ),
    (
        (SELECT id FROM categories WHERE category_name = 'Fantasy' LIMIT 1),
        'Ksatria Cahaya',
        'Komik Demo',
        'Contoh komik fantasi untuk data awal aplikasi KOMIKIN.',
        6000.00,
        3
    ),
    (
        (SELECT id FROM categories WHERE category_name = 'Comedy' LIMIT 1),
        'Hari-Hari Si Kocak',
        'Komik Demo',
        'Contoh komik komedi untuk menguji tampilan katalog.',
        4000.00,
        4
    );

-- No default administrator is inserted.
-- Admin accounts should be created through the application using PHP password_hash().
