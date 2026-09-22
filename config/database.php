<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $host = getenv('DAMS_DB_HOST') ?: 'localhost';
            $name = getenv('DAMS_DB_NAME') ?: 'digital_asset_management';
            $user = getenv('DAMS_DB_USER') ?: 'root';
            $pass = getenv('DAMS_DB_PASS') ?: '';
            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
            self::$connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$connection;
    }

    private function __construct() {}
}

function db(): PDO
{
    return Database::connection();
}

