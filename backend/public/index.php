<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\AppConfig;
use App\Config\Database;
use App\Http\Response;
use App\Models\Producto;
use App\Controllers\ProductoController;

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

    $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            Response::json(['error' => 'Ruta no encontrada'], 404);
            break;

        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
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
    Response::json([
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ], 500);
}
