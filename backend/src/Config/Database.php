<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(
        string $host,
        string $port,
        string $dbName,
        string $user,
        string $password
    ): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $password, $options);
            } catch (PDOException $e) {
                throw new RuntimeException('Error al conectar con la base de datos: ' . $e->getMessage(), 500, $e);
            }
        }

        return self::$instance;
    }
}
