<?php

declare(strict_types=1);

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

return simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('GET', '/productos', 'index');
    $r->addRoute('GET', '/productos/{id:\d+}', 'show');
    $r->addRoute('POST', '/productos', 'store');
    $r->addRoute('PUT', '/productos/{id:\d+}', 'update');
    $r->addRoute('DELETE', '/productos/{id:\d+}', 'destroy');
});
