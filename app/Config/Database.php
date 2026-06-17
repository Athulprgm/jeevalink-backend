<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    /**
     * Get database PDO connection singleton instance.
     *
     * @return PDO
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $dbUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL') ?? null;
            if ($dbUrl) {
                $url = parse_url($dbUrl);
                $host = $url['host'] ?? '127.0.0.1';
                $port = $url['port'] ?? '5432';
                $dbName = isset($url['path']) ? ltrim($url['path'], '/') : 'jeevalink';
                $username = $url['user'] ?? 'postgres';
                $password = $url['pass'] ?? '';
            } else {
                $host = $_ENV['PGHOST'] ?? '127.0.0.1';
                $port = $_ENV['PGPORT'] ?? '5432';
                $dbName = $_ENV['PGDATABASE'] ?? 'jeevalink';
                $username = $_ENV['PGUSER'] ?? 'postgres';
                $password = $_ENV['PGPASSWORD'] ?? '';
            }

            try {
                $dsn = "pgsql:host={$host};port={$port};dbname={$dbName}";
                self::$connection = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                // Return a clean JSON error response if database connection fails
                if (!headers_sent()) {
                    header('Content-Type: application/json');
                    http_response_code(500);
                }
                echo json_encode([
                    'success' => false,
                    'message' => 'Database connection failed: ' . $e->getMessage()
                ]);
                exit();
            }
        }

        return self::$connection;
    }
}
