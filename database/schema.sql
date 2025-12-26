-- Database Schema for Shoe Recommender System
-- MAMP MySQL Configuration: localhost:8888

CREATE DATABASE IF NOT EXISTS shoe_recommender CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE shoe_recommender;

-- Table: users
-- Stores user account information
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: products
-- Stores product information
CREATE TABLE IF NOT EXISTS products (
    id VARCHAR(255) PRIMARY KEY,
    product_name VARCHAR(500) NOT NULL,
    brand VARCHAR(255) NOT NULL,
    original_price VARCHAR(100),
    sale_price VARCHAR(100) DEFAULT NULL,
    image_url TEXT,
    product_detail_url TEXT,
    rating DECIMAL(3,1) DEFAULT 0.0,
    rating_count INT DEFAULT 0,
    category VARCHAR(255) DEFAULT 'Sneakers',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_brand (brand),
    INDEX idx_category (category),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: interactions
-- Stores user interactions (ratings, views/clicks with scoring)
CREATE TABLE IF NOT EXISTS interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    product_id VARCHAR(255) NOT NULL,
    rating DECIMAL(2,1) DEFAULT NULL COMMENT 'Rating from 1-5 stars',
    view_count INT DEFAULT 0 COMMENT 'Number of times user viewed/clicked this product',
    view_score INT DEFAULT 0 COMMENT 'Calculated score from views (1 point per view, max 5)',
    timestamp INT NOT NULL COMMENT 'Unix timestamp',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id),
    INDEX idx_user_id (user_id),
    INDEX idx_product_id (product_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;







