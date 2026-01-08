<?php
// seed_cloud_db.php
// Script to initialize Cloud SQL database and seed it with JSON data

require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    echo "<h1>Database Setup & Seeding</h1>";
    echo "<p>Connected to database successfully.</p>";

    // 1. Create Tables
    $schemaErrors = [];
    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id VARCHAR(255) PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        "CREATE TABLE IF NOT EXISTS products (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        "CREATE TABLE IF NOT EXISTS interactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(255) NOT NULL,
            product_id VARCHAR(255) NOT NULL,
            rating DECIMAL(2,1) DEFAULT NULL,
            view_count INT DEFAULT 0,
            view_score INT DEFAULT 0,
            timestamp INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_product (user_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    ];

    foreach ($queries as $sql) {
        try {
            $pdo->exec($sql);
            echo "<p style='color:green'>Table created successfully.</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>Error creating table: " . $e->getMessage() . "</p>";
        }
    }

    // 2. Seed Products from JSON
    $jsonFile = __DIR__ . '/data/products.json';
    if (!file_exists($jsonFile)) {
        die("<p style='color:red'>Error: data/products.json not found.</p>");
    }

    $jsonData = file_get_contents($jsonFile);
    $products = json_decode($jsonData, true);

    if (!$products) {
        die("<p style='color:red'>Error: Failed to decode JSON.</p>");
    }

    echo "<p>Found " . count($products) . " products in JSON. Starting import...</p>";

    $stmt = $pdo->prepare("INSERT IGNORE INTO products 
        (id, product_name, brand, original_price, sale_price, image_url, product_detail_url, rating, rating_count, category) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $inserted = 0;
    foreach ($products as $p) {
        $stmt->execute([
            $p['id'],
            $p['product_name'],
            $p['brand'],
            $p['original_price'],
            $p['sale_price'],
            $p['image_url'],
            $p['product_detail_url'],
            $p['rating'] ?? 0,
            $p['rating_count'] ?? 0,
            $p['category'] ?? 'Sneakers'
        ]);
        $inserted++;
    }

    echo "<p style='color:green'><strong>Success! Inserted/Processed $inserted products.</strong></p>";
    echo "<p><a href='index.php'>Go to Home Page</a></p>";

} catch (PDOException $e) {
    echo "<h1>Database Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
