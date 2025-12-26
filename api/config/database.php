<?php
/**
 * Database Configuration
 * MAMP MySQL Settings
 */

// Database connection settings
// Attempt to get from Environment Variables (for Vercel/Cloud)
$db_host = getenv('DB_HOST');
$db_port = getenv('DB_PORT');
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');

// If Environment Variables are NOT set, fall back to Local MAMP defaults
if (!$db_host) {
    $db_host = '127.0.0.1'; // Use IP to force TCP connection, avoids "No such file or directory" socket error
    $db_port = '8889';      // MAMP default port
    $db_name = 'shoe_recommender';
    $db_user = 'root';
    $db_pass = 'root';
}

define('DB_HOST', $db_host);
define('DB_PORT', $db_port ?: '3306'); // Default to standard MySQL port if env var exists but port doesn't
define('DB_NAME', $db_name);
define('DB_USER', $db_user);
define('DB_PASS', $db_pass);

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







