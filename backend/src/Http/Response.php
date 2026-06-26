<?php

declare(strict_types=1);

namespace App\Http;

class Response
{
    public static function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function noContent(): void
    {
        http_response_code(204);
        header_remove('Content-Type');
        exit;
    }
}
