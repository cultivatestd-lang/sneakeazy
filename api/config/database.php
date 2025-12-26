<?php
/**
 * Database Configuration
 * MAMP MySQL Settings
 */

// Database connection settings
// Attempt to get from Environment Variables (for Vercel/Cloud), otherwise use Local MAMP defaults
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '8889');
define('DB_NAME', getenv('DB_NAME') ?: 'shoe_recommender');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'root');

/**
 * Get database connection
 * @return PDO Database connection instance
 */
function getDBConnection()
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            // If on a remote host (implied by non-localhost logic or specific ENV var), we might need SSL
            // For many cloud providers (Aiven, PlanetScale), getting a clean connection is key.
            // Often just connecting works, but if SSL is strictly required, we might need:
            // if (getenv('DB_USE_SSL') === 'true') { $options[PDO::MYSQL_ATTR_SSL_CA] = '/path/to/cert'; }
            // For simplicity in this demo, we assume standard auth works or the provider string is sufficient.

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // On production, don't echo full error details to users, but helpful for debugging now
            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}







