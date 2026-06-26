<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Producto
{
    private PDO $pdo;
    private float $precioUsd;

    public function __construct(PDO $pdo, float $precioUsd)
    {
        $this->pdo = $pdo;
        $this->precioUsd = $precioUsd;
    }

    /**
     * Obtiene todos los productos con la conversión a USD.
     *
     * @return array<array<string, mixed>>
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, nombre, descripcion, precio, created_at, updated_at FROM productos ORDER BY id DESC');
        $productos = $stmt->fetchAll();

        return array_map([$this, 'addPrecioUsd'], $productos);
    }

    /**
     * Obtiene un producto por ID con la conversión a USD.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nombre, descripcion, precio, created_at, updated_at FROM productos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $producto = $stmt->fetch();

        if ($producto === false) {
            return null;
        }

        return $this->addPrecioUsd($producto);
    }

    /**
     * Crea un nuevo producto y retorna el producto creado.
     *
     * @param array{nombre: string, descripcion: ?string, precio: float} $data
     * @return array<string, mixed>|null
     */
    public function create(array $data): ?array
    {
        $stmt = $this->pdo->prepare('INSERT INTO productos (nombre, descripcion, precio) VALUES (:nombre, :descripcion, :precio)');
        $stmt->execute([
            'nombre'      => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'precio'      => $data['precio'],
        ]);

        $lastId = $this->pdo->lastInsertId();

        return $lastId ? $this->findById((int) $lastId) : null;
    }

    /**
     * Actualiza un producto existente y lo retorna.
     *
     * @param int $id
     * @param array{nombre: string, descripcion: ?string, precio: float} $data
     * @return array<string, mixed>|null
     */
    public function update(int $id, array $data): ?array
    {
        $stmt = $this->pdo->prepare('UPDATE productos SET nombre = :nombre, descripcion = :descripcion, precio = :precio WHERE id = :id');
        $stmt->execute([
            'id'          => $id,
            'nombre'      => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'precio'      => $data['precio'],
        ]);

        return $this->findById($id);
    }

    /**
     * Elimina un producto por ID. Retorna true si se eliminó, false si no existía.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM productos WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Helper para agregar el precio convertido a USD y realizar casting de tipos de datos.
     *
     * @param array<string, mixed> $producto
     * @return array<string, mixed>
     */
    private function addPrecioUsd(array $producto): array
    {
        $precio = (float) $producto['precio'];
        $producto['id'] = (int) $producto['id'];
        $producto['precio'] = $precio;
        $producto['precio_usd'] = round($precio / $this->precioUsd, 2);

        return $producto;
    }

    /**
     * Obtiene el valor del dólar configurado en la aplicación.
     *
     * @return float
     */
    public function getPrecioUsd(): float
    {
        return $this->precioUsd;
    }
}
