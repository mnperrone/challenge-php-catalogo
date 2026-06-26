<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\AppConfig;
use App\Config\Database;
use App\Http\Response;
use App\Models\Producto;
use App\Controllers\ProductoController;

// Configuración de cabeceras CORS globales
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    // 1. Configuración y Conexión (Composition Root)
    $config = new AppConfig();
    $pdo = Database::getInstance(
        $config->dbHost,
        $config->dbPort,
        $config->dbName,
        $config->dbUser,
        $config->dbPassword
    );

    $modelo = new Producto($pdo, $config->precioUsd);
    $controller = new ProductoController($modelo);

    // 2. Dispatcher de Rutas
    $dispatcher = require __DIR__ . '/../src/Routes/api.php';

    $httpMethod = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];

    // Limpiar query string de la URI
    if (false !== $pos = strpos($uri, '?')) {
        $uri = substr($uri, 0, $pos);
    }
    $uri = rawurldecode($uri);

    // Redirigir la raíz al frontend
    if ($uri === '/') {
        header('Location: /frontend/');
        exit;
    }

    // Normalizar barra diagonal al final (trailing slash) para evitar 404s innecesarios
    if ($uri !== '/' && str_ends_with($uri, '/')) {
        $uri = rtrim($uri, '/');
    }

    $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            Response::json(['error' => 'Ruta no encontrada'], 404);
            break;

        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            $allowedMethods = $routeInfo[1];
            header('Allow: ' . implode(', ', $allowedMethods));
            Response::json(['error' => 'Método no permitido'], 405);
            break;

        case FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];

            if (method_exists($controller, $handler)) {
                if (isset($vars['id'])) {
                    $controller->$handler((int) $vars['id']);
                } else {
                    $controller->$handler();
                }
            } else {
                Response::json(['error' => 'Acción no encontrada'], 500);
            }
            break;
    }
} catch (Throwable $e) {
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    Response::json([
        'error' => 'Error interno del servidor'
    ], 500);
}
