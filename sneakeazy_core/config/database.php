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

// Cek apakah berjalan di Google App Engine
if (isset($_SERVER['GAE_ENV']) || getenv('INSTANCE_CONNECTION_NAME')) {
    // KONEKSI GOOGLE CLOUD SQL
    $is_cloud_sql = true;
    $db_user = getenv('DB_USER');
    $db_pass = getenv('DB_PASS');
    $db_name = getenv('DB_NAME');
    $connection_name = getenv('INSTANCE_CONNECTION_NAME');
    $socket_path = getenv('DB_SOCKET_PATH') ?: '/cloudsql';

    // DSN untuk Cloud SQL (menggunakan Unix Socket)
    $dsn_cloud = "mysql:dbname={$db_name};unix_socket={$socket_path}/{$connection_name}";
} else {
    // KONEKSI LOCALHOST (Laptop Kamu)
    $is_cloud_sql = false;
    $db_host = '127.0.0.1';
    $db_port = '8889';
    $db_name = 'sneakeazy'; // Pastikan nama DB lokal sesuai
    $db_user = 'root';
    $db_pass = 'root';
}

/**
 * Get database connection
 * @return PDO Database connection instance
 */
function getDBConnection()
{
    static $pdo = null;

    global $is_cloud_sql, $dsn_cloud, $db_host, $db_port, $db_name, $db_user, $db_pass;

    if ($pdo === null) {
        try {
            if (isset($is_cloud_sql) && $is_cloud_sql) {
                // Gunakan DSN khusus Cloud SQL Socket
                $dsn = $dsn_cloud;
            } else {
                // Gunakan DSN standar TCP/IP Local
                $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
            }

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            // Enable SSL for Cloud Databases (Jika bukan 127.0.0.1 dan bukan Cloud SQL Socket)
            if (!isset($is_cloud_sql) || !$is_cloud_sql) {
                if ($db_port === '4000' || $db_host !== '127.0.0.1') {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = __DIR__ . '/cacert.pem';
                }
            }

            $pdo = new PDO($dsn, $db_user, $db_pass, $options);
        } catch (PDOException $e) {
            // On production, don't echo full error details to users, but helpful for debugging now
            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}







