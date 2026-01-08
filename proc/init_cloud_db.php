<?php
/**
 * Cloud Database Initializer
 * ONE-STOP SCRIPT to set up your TiDB/Aiven/Cloud Database.
 *
 * Usage from Terminal:
 * DB_HOST=xxx DB_PORT=3306 DB_USER=xxx DB_PASS=xxx php init_cloud_db.php
 */

// 1. Bootstrap Phase: Create Database if not exists
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'root';
$targetDb = getenv('DB_NAME') ?: 'shoe_recommender';

echo "🔌 Bootstrap Connection to $host...\n";

try {
    // Connect WITHOUT dbname to allow creation
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // SSL Config for Cloud
        PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/config/cacert.pem',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];
    // If local, remove SSL options usually, but for TiDB script strictness:
    if ($host === '127.0.0.1') {
        unset($options[PDO::MYSQL_ATTR_SSL_CA]);
        unset($options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]);
    }

    $pdoBoot = new PDO($dsn, $user, $pass, $options);

    echo "🏗 Creating Database '$targetDb' if needed...\n";
    $pdoBoot->exec("CREATE DATABASE IF NOT EXISTS `$targetDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database ready.\n";

} catch (PDOException $e) {
    echo "Warning during Bootstrap: " . $e->getMessage() . "\n";
    echo "Proceeding, assuming Database might already exist...\n";
}

// 2. Load Config & Standard Connection
require_once 'config/database.php';

echo "🔌 Connecting via Application Schema...\n";
$pdo = getDBConnection();


try {
    // 1. Run Schema (Create Tables)
    echo "\n🏗 Creating Tables (from database/schema.sql)...\n";
    $schemaSql = file_get_contents('database/schema.sql');

    // Remove "CREATE DATABASE" line if it exists because Cloud DBs usually give you a pre-made DB
// and your user might not have permission to create new ones, or it selects the wrong one.
// However, our schema.sql has 'USE shoe_recommender'. We should be careful.
// Best practice for Cloud: Connect to the SPECIFIC DB name in connection string, and ignore 'USE'.

    // Simple split by semicolon to run queries
    $queries = explode(';', $schemaSql);

    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query))
            continue;

        // Skip CREATE DATABASE and USE commands to respect the Connection String's DB
        if (stripos($query, 'CREATE DATABASE') === 0) {
            echo " Skipping CREATE DATABASE (relying on connection settings)...\n";
            continue;
        }
        if (stripos($query, 'USE ') === 0) {
            echo " Skipping USE command...\n";
            continue;
        }

        try {
            $pdo->exec($query);
        } catch (PDOException $e) {
            // Ignore "Table already exists" errors
            if (strpos($e->getMessage(), '1050') !== false) {
                echo " Notice: Table already exists (skipping)...\n";
            } else {
                echo " Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    echo "✅ Tables created/verified.\n";

    // 2. Run Data Migration (Seed Data)
    echo "\n🌱 Seeding Data (from migrate_to_database.php)...\n";
    // We can just include the logic or file.
// Since migrate_to_database.php includes config/database.php again, might be safer to just run its logic here or
// include it if it uses 'require_once'.
// It uses 'require_once', so safe to include.

    // However, migrate_to_database.php creates its own $pdo from getDBConnection().
// That's fine, it will use the same env vars.
    include 'migrate_to_database.php';

    echo "\n🎉 DONE! Your Cloud Database is ready.\n";

} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}