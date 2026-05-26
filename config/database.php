<?php
// ================================================================
//  config/database.php  –  PDO connection factory
//  ⚠  Edit DB_USER / DB_PASS to match your local environment.
// ================================================================

declare(strict_types=1);

define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'coworking_hub');
define('DB_USER',    'root');
define('DB_PASS',    '');           // empty = no password (XAMPP default)
define('DB_CHARSET', 'utf8mb4');

/**
 * Return a singleton PDO instance.
 */
function getDBConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB connection error: ' . $e->getMessage());
            die('
            <div style="font-family:sans-serif;max-width:600px;margin:60px auto;padding:30px;
                        border:1px solid #fca5a5;border-radius:8px;background:#fef2f2;color:#991b1b;">
              <h2 style="margin:0 0 12px">⚠ Database Connection Failed</h2>
              <p>Please verify your credentials in <code>config/database.php</code> and ensure
                 the <strong>' . DB_NAME . '</strong> database has been created by running
                 <code>database/schema.sql</code>.</p>
            </div>');
        }
    }

    return $pdo;
}
