<?php

declare(strict_types=1);

namespace App\Config;

use RuntimeException;

class AppConfig
{
    public readonly string $dbHost;
    public readonly string $dbPort;
    public readonly string $dbName;
    public readonly string $dbUser;
    public readonly string $dbPassword;
    public readonly float $precioUsd;

    public function __construct()
    {
        $this->dbHost = $this->getEnvString('DB_HOST', 'mysql');
        $this->dbPort = $this->getEnvString('DB_PORT', '3306');
        $this->dbName = $this->getEnvString('DB_NAME', 'catalogo');
        $this->dbUser = $this->getEnvString('DB_USER', 'catalogo_user');
        $this->dbPassword = $this->getEnvString('DB_PASSWORD', 'catalogo_pass');

        $precioUsdRaw = getenv('PRECIO_USD');
        if ($precioUsdRaw === false || $precioUsdRaw === '') {
            throw new RuntimeException('La variable de entorno PRECIO_USD no está definida.');
        }

        $precioUsdVal = (float) $precioUsdRaw;
        if ($precioUsdVal <= 0.0) {
            throw new RuntimeException('La variable de entorno PRECIO_USD debe ser un valor mayor a cero.');
        }

        $this->precioUsd = $precioUsdVal;
    }

    private function getEnvString(string $name, string $default): string
    {
        $value = getenv($name);
        return ($value !== false && $value !== '') ? $value : $default;
    }
}
