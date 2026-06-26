<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use App\Models\Producto;

class ProductoController
{
    private Producto $modelo;

    public function __construct(Producto $modelo)
    {
        $this->modelo = $modelo;
    }

    /**
     * GET /productos
     * Lista todos los productos.
     */
    public function index(): void
    {
        $productos = $this->modelo->findAll();
        Response::json([
            'data' => $productos,
            'precio_usd' => $this->modelo->getPrecioUsd()
        ], 200);
    }

    /**
     * GET /productos/{id}
     * Detalle de un producto específico.
     */
    public function show(int $id): void
    {
        $producto = $this->modelo->findById($id);

        if ($producto === null) {
            Response::json(['error' => 'Producto no encontrado'], 404);
        }

        Response::json(['data' => $producto], 200);
    }

    /**
     * POST /productos
     * Crea un nuevo producto.
     */
    public function store(): void
    {
        $input = $this->parseJsonInput();
        $validated = $this->validateInput($input);

        $producto = $this->modelo->create($validated);

        if ($producto === null) {
            Response::json(['error' => 'No se pudo crear el producto'], 500);
        }

        header("Location: /productos/{$producto['id']}");
        Response::json(['data' => $producto], 201);
    }

    /**
     * PUT /productos/{id}
     * Actualiza un producto existente.
     */
    public function update(int $id): void
    {
        // Verificar existencia previa
        $productoExistente = $this->modelo->findById($id);
        if ($productoExistente === null) {
            Response::json(['error' => 'Producto no encontrado'], 404);
        }

        $input = $this->parseJsonInput();
        $validated = $this->validateInput($input);

        $productoActualizado = $this->modelo->update($id, $validated);

        if ($productoActualizado === null) {
            Response::json(['error' => 'No se pudo actualizar el producto'], 500);
        }

        Response::json(['data' => $productoActualizado], 200);
    }

    /**
     * DELETE /productos/{id}
     * Elimina un producto.
     */
    public function destroy(int $id): void
    {
        $eliminado = $this->modelo->delete($id);

        if (!$eliminado) {
            Response::json(['error' => 'Producto no encontrado'], 404);
        }

        Response::noContent();
    }

    /**
     * Parsea el cuerpo JSON de la solicitud.
     */
    private function parseJsonInput(): ?array
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if (in_array($method, ['POST', 'PUT'])) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
            $contentTypeBase = trim(strtolower(explode(';', $contentType)[0]));
            if ($contentTypeBase !== 'application/json') {
                Response::json(['error' => 'El tipo de contenido debe ser application/json'], 415);
            }
        }

        $body = file_get_contents('php://input');
        if (trim($body) === '') {
            return null;
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::json(['error' => 'Formato JSON inválido'], 400);
        }

        if ($data !== null && !is_array($data)) {
            Response::json(['error' => 'El cuerpo de la solicitud debe ser un objeto JSON.'], 400);
        }

        return $data;
    }

    /**
     * Valida el input y retorna el array limpio o corta con 400 Bad Request.
     */
    private function validateInput(?array $input): array
    {
        if ($input === null) {
            Response::json(['error' => 'El cuerpo de la solicitud no puede estar vacío.'], 400);
        }

        // Sanitización contra inyección de null-bytes
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                $input[$key] = str_replace("\0", '', $value);
            }
        }

        $errors = [];

        // Validar nombre
        if (!isset($input['nombre']) || !is_string($input['nombre'])) {
            $errors[] = 'El campo "nombre" es requerido y debe ser una cadena de texto.';
        } else {
            $nombre = trim($input['nombre']);
            if ($nombre === '') {
                $errors[] = 'El campo "nombre" no puede estar vacío.';
            } elseif (mb_strlen($nombre) > 255) {
                $errors[] = 'El campo "nombre" no puede superar los 255 caracteres.';
            }
        }

        // Validar descripción (opcional)
        $descripcion = null;
        if (isset($input['descripcion'])) {
            if (!is_string($input['descripcion']) && $input['descripcion'] !== null) {
                $errors[] = 'El campo "descripcion" debe ser una cadena de texto o nulo.';
            } else {
                $descripcion = $input['descripcion'] !== null ? trim($input['descripcion']) : null;
            }
        }

        // Validar precio
        if (!isset($input['precio'])) {
            $errors[] = 'El campo "precio" es requerido.';
        } elseif (!is_numeric($input['precio'])) {
            $errors[] = 'El campo "precio" debe ser un número.';
        } else {
            $precio = (float) $input['precio'];
            if ($precio < 0.0) {
                $errors[] = 'El campo "precio" debe ser un valor igual o mayor a cero.';
            }
        }

        if (!empty($errors)) {
            Response::json(['error' => $errors[0]], 400);
        }

        return [
            'nombre'      => trim($input['nombre']),
            'descripcion' => $descripcion,
            'precio'      => (float) $input['precio'],
        ];
    }
}
