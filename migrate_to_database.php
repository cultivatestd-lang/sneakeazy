<?php
/**
 * Migration Script: JSON to MySQL Database
 * This script migrates data from JSON files to MySQL database
 * 
 * Usage: Open in browser or run via CLI: php migrate_to_database.php
 */

require_once 'config/database.php';

$pdo = getDBConnection();

// Start transaction
$pdo->beginTransaction();

try {
    echo "Starting migration...\n";
    
    // 1. Migrate Users
    echo "Migrating users...\n";
    $usersFile = 'data/users.json';
    if (file_exists($usersFile)) {
        $users = json_decode(file_get_contents($usersFile), true);
        $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), email = VALUES(email)");
        
        foreach ($users as $user) {
            $stmt->execute([$user['id'], $user['name'], $user['email'], $user['password']]);
        }
        echo "✓ Migrated " . count($users) . " users\n";
    }
    
    // 2. Migrate Products
    echo "Migrating products...\n";
    $productsFile = 'data/products.json';
    if (file_exists($productsFile)) {
        $products = json_decode(file_get_contents($productsFile), true);
        $stmt = $pdo->prepare("INSERT INTO products (id, product_name, brand, original_price, sale_price, image_url, product_detail_url, rating, rating_count, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE product_name = VALUES(product_name), brand = VALUES(brand), original_price = VALUES(original_price), sale_price = VALUES(sale_price), image_url = VALUES(image_url), product_detail_url = VALUES(product_detail_url), rating = VALUES(rating), rating_count = VALUES(rating_count), category = VALUES(category)");
        
        foreach ($products as $product) {
            $stmt->execute([
                $product['id'],
                $product['product_name'],
                $product['brand'],
                $product['original_price'] ?? null,
                $product['sale_price'] ?? null,
                $product['image_url'] ?? null,
                $product['product_detail_url'] ?? null,
                floatval($product['rating'] ?? 0),
                intval($product['rating_count'] ?? 0),
                $product['category'] ?? 'Sneakers'
            ]);
        }
        echo "✓ Migrated " . count($products) . " products\n";
    }
    
    // 3. Migrate Interactions (with initial view_count = 0, view_score = 0)
    echo "Migrating interactions...\n";
    $interactionsFile = 'data/interactions.json';
    if (file_exists($interactionsFile)) {
        $interactions = json_decode(file_get_contents($interactionsFile), true);
        $stmt = $pdo->prepare("INSERT INTO interactions (user_id, product_id, rating, view_count, view_score, timestamp) VALUES (?, ?, ?, 0, 0, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating), timestamp = VALUES(timestamp)");
        
        $count = 0;
        foreach ($interactions as $interaction) {
            $stmt->execute([
                $interaction['user_id'],
                $interaction['product_id'],
                floatval($interaction['rating'] ?? null),
                intval($interaction['timestamp'] ?? time())
            ]);
            $count++;
        }
        echo "✓ Migrated " . $count . " interactions\n";
    }
    
    // Commit transaction
    $pdo->commit();
    echo "\n✓ Migration completed successfully!\n";
    echo "You can now use the application with database.\n";
    
} catch (Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}







