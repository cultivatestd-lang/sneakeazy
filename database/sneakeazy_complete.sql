-- ============================================
-- SneakEazy Database - Complete Setup Script
-- ============================================
-- Project: Sistem Rekomendasi Sepatu (Shoe Recommender System)
-- Database: sneakeazy
-- Version: 1.0
-- Created: 2026-01-09
-- ============================================

-- Drop database if exists (WARNING: This will delete all data!)
-- Uncomment the line below if you want to start fresh
-- DROP DATABASE IF EXISTS sneakeazy;

-- Create database
CREATE DATABASE IF NOT EXISTS sneakeazy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE sneakeazy;

-- ============================================
-- TABLE DEFINITIONS
-- ============================================

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

-- ============================================
-- SEED DATA - USERS
-- ============================================
-- Note: This includes dummy users for testing the recommendation system
-- Password: 'hashed_dummy_password' (replace with actual hashed passwords in production)

INSERT IGNORE INTO users (id, name, email, password) VALUES
('69451859ce2a6', 'User 69451859ce2a6', '69451859ce2a6@example.com', 'hashed_dummy_password'),
('user_6684_0', 'User user_6684_0', 'user_6684_0@example.com', 'hashed_dummy_password'),
('user_4221_1', 'User user_4221_1', 'user_4221_1@example.com', 'hashed_dummy_password'),
('user_9608_2', 'User user_9608_2', 'user_9608_2@example.com', 'hashed_dummy_password'),
('user_9678_3', 'User user_9678_3', 'user_9678_3@example.com', 'hashed_dummy_password'),
('user_6859_4', 'User user_6859_4', 'user_6859_4@example.com', 'hashed_dummy_password'),
('user_9554_5', 'User user_9554_5', 'user_9554_5@example.com', 'hashed_dummy_password'),
('user_2988_6', 'User user_2988_6', 'user_2988_6@example.com', 'hashed_dummy_password'),
('user_7656_7', 'User user_7656_7', 'user_7656_7@example.com', 'hashed_dummy_password'),
('user_1970_8', 'User user_1970_8', 'user_1970_8@example.com', 'hashed_dummy_password'),
('user_5059_9', 'User user_5059_9', 'user_5059_9@example.com', 'hashed_dummy_password'),
('user_8000_10', 'User user_8000_10', 'user_8000_10@example.com', 'hashed_dummy_password'),
('user_1238_11', 'User user_1238_11', 'user_1238_11@example.com', 'hashed_dummy_password'),
('user_5290_12', 'User user_5290_12', 'user_5290_12@example.com', 'hashed_dummy_password'),
('user_2482_13', 'User user_2482_13', 'user_2482_13@example.com', 'hashed_dummy_password'),
('user_4497_14', 'User user_4497_14', 'user_4497_14@example.com', 'hashed_dummy_password'),
('user_9910_15', 'User user_9910_15', 'user_9910_15@example.com', 'hashed_dummy_password'),
('user_6978_16', 'User user_6978_16', 'user_6978_16@example.com', 'hashed_dummy_password'),
('user_3145_17', 'User user_3145_17', 'user_3145_17@example.com', 'hashed_dummy_password'),
('user_6833_18', 'User user_6833_18', 'user_6833_18@example.com', 'hashed_dummy_password'),
('user_5439_19', 'User user_5439_19', 'user_5439_19@example.com', 'hashed_dummy_password'),
('user_5148_20', 'User user_5148_20', 'user_5148_20@example.com', 'hashed_dummy_password'),
('user_2019_21', 'User user_2019_21', 'user_2019_21@example.com', 'hashed_dummy_password'),
('user_3952_22', 'User user_3952_22', 'user_3952_22@example.com', 'hashed_dummy_password'),
('user_8577_23', 'User user_8577_23', 'user_8577_23@example.com', 'hashed_dummy_password'),
('user_2919_24', 'User user_2919_24', 'user_2919_24@example.com', 'hashed_dummy_password'),
('user_4624_25', 'User user_4624_25', 'user_4624_25@example.com', 'hashed_dummy_password'),
('user_7618_26', 'User user_7618_26', 'user_7618_26@example.com', 'hashed_dummy_password'),
('user_1657_27', 'User user_1657_27', 'user_1657_27@example.com', 'hashed_dummy_password'),
('user_5140_28', 'User user_5140_28', 'user_5140_28@example.com', 'hashed_dummy_password'),
('user_2302_29', 'User user_2302_29', 'user_2302_29@example.com', 'hashed_dummy_password'),
('user_5079_30', 'User user_5079_30', 'user_5079_30@example.com', 'hashed_dummy_password'),
('user_2194_31', 'User user_2194_31', 'user_2194_31@example.com', 'hashed_dummy_password'),
('user_9358_32', 'User user_9358_32', 'user_9358_32@example.com', 'hashed_dummy_password'),
('user_8941_33', 'User user_8941_33', 'user_8941_33@example.com', 'hashed_dummy_password'),
('user_9674_34', 'User user_9674_34', 'user_9674_34@example.com', 'hashed_dummy_password'),
('user_1045_35', 'User user_1045_35', 'user_1045_35@example.com', 'hashed_dummy_password'),
('user_7275_36', 'User user_7275_36', 'user_7275_36@example.com', 'hashed_dummy_password'),
('user_2233_37', 'User user_2233_37', 'user_2233_37@example.com', 'hashed_dummy_password'),
('user_6289_38', 'User user_6289_38', 'user_6289_38@example.com', 'hashed_dummy_password'),
('user_3682_39', 'User user_3682_39', 'user_3682_39@example.com', 'hashed_dummy_password'),
('user_2589_40', 'User user_2589_40', 'user_2589_40@example.com', 'hashed_dummy_password'),
('user_8011_41', 'User user_8011_41', 'user_8011_41@example.com', 'hashed_dummy_password'),
('user_3651_42', 'User user_3651_42', 'user_3651_42@example.com', 'hashed_dummy_password'),
('user_8327_43', 'User user_8327_43', 'user_8327_43@example.com', 'hashed_dummy_password'),
('user_2275_44', 'User user_2275_44', 'user_2275_44@example.com', 'hashed_dummy_password'),
('user_1323_45', 'User user_1323_45', 'user_1323_45@example.com', 'hashed_dummy_password'),
('user_5874_46', 'User user_5874_46', 'user_5874_46@example.com', 'hashed_dummy_password'),
('user_6957_47', 'User user_6957_47', 'user_6957_47@example.com', 'hashed_dummy_password'),
('user_5095_48', 'User user_5095_48', 'user_5095_48@example.com', 'hashed_dummy_password'),
('user_9325_49', 'User user_9325_49', 'user_9325_49@example.com', 'hashed_dummy_password'),
('user_reza_demo', 'User user_reza_demo', 'user_reza_demo@example.com', 'hashed_dummy_password'),
('69451b59a0783', 'User 69451b59a0783', '69451b59a0783@example.com', 'hashed_dummy_password'),
('69452165f1224', 'User 69452165f1224', '69452165f1224@example.com', 'hashed_dummy_password'),
('6949656c6e2b0', 'User 6949656c6e2b0', '6949656c6e2b0@example.com', 'hashed_dummy_password');

-- ============================================
-- SEED DATA - PRODUCTS
-- ============================================
-- Note: Products will be imported from products.json via PHP seeding script
-- This ensures data consistency and easier updates

-- ============================================
-- SEED DATA - INTERACTIONS
-- ============================================
-- Note: Sample interactions for collaborative filtering
-- Format: (user_id, product_id, rating, timestamp)
