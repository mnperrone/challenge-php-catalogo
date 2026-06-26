<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;

class ProductoApiTest extends TestCase
{
    private string $baseUrl = 'http://localhost'; // Puerto 80 interno del contenedor apache

    /**
     * Helper para realizar llamadas cURL sencillas.
     *
     * @param string $method
     * @param string $path
     * @param array|null $body
     * @return array{status: int, body: string}
     */
    private function request(string $method, string $path, ?array $body = null): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($body !== null) {
            $jsonData = json_encode($body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData)
            ]);
        }

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $status,
            'body'   => $response ?: ''
        ];
    }

    /**
     * Test: Obtener listado de productos inicial (vaciado o no)
     */
    public function testListadoInicial(): void
    {
        $res = $this->request('GET', '/productos');
        $this->assertSame(200, $res['status']);

        $data = json_decode($res['body'], true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('precio_usd', $data);
        $this->assertIsArray($data['data']);
        $this->assertGreaterThan(0, $data['precio_usd']);
    }

    /**
     * Test: Flujo completo CRUD de un producto
     */
    public function testFlujoCompletoCrud(): void
    {
        // 1. Crear Producto
        $newProduct = [
            'nombre'      => 'Producto de Prueba Unitario',
            'descripcion' => 'Descripción de prueba unitaria',
            'precio'      => 120000.00
        ];

        $resCreate = $this->request('POST', '/productos', $newProduct);
        $this->assertSame(201, $resCreate['status']);

        $createData = json_decode($resCreate['body'], true);
        $this->assertArrayHasKey('data', $createData);
        
        $producto = $createData['data'];
        $this->assertArrayHasKey('id', $producto);
        $this->assertSame($newProduct['nombre'], $producto['nombre']);
        $this->assertSame($newProduct['descripcion'], $producto['descripcion']);
        $this->assertEquals($newProduct['precio'], $producto['precio']);
        $this->assertArrayHasKey('precio_usd', $producto);

        $id = $producto['id'];

        // 2. Detalle del Producto Creado
        $resShow = $this->request('GET', "/productos/{$id}");
        $this->assertSame(200, $resShow['status']);
        
        $showData = json_decode($resShow['body'], true);
        $this->assertSame($id, $showData['data']['id']);

        // 3. Editar Producto
        $updateProduct = [
            'nombre'      => 'Producto de Prueba Unitario Modificado',
            'descripcion' => 'Descripción modificada',
            'precio'      => 150000.00
        ];

        $resUpdate = $this->request('PUT', "/productos/{$id}", $updateProduct);
        $this->assertSame(200, $resUpdate['status']);

        $updateData = json_decode($resUpdate['body'], true);
        $this->assertSame($updateProduct['nombre'], $updateData['data']['nombre']);
        $this->assertEquals($updateProduct['precio'], $updateData['data']['precio']);

        // 4. Eliminar Producto
        $resDelete = $this->request('DELETE', "/productos/{$id}");
        $this->assertSame(204, $resDelete['status']);
        $this->assertEmpty($resDelete['body']);

        // 5. Verificar que ya no existe (404)
        $resShowDeleted = $this->request('GET', "/productos/{$id}");
        $this->assertSame(404, $resShowDeleted['status']);
    }

    /**
     * Test: Validaciones y errores esperados (400 Bad Request)
     */
    public function testValidacionesDeFormulario(): void
    {
        // Nombre vacío
        $invalidProduct = [
            'nombre'      => '',
            'descripcion' => 'Falta nombre válido',
            'precio'      => 100
        ];
        $res = $this->request('POST', '/productos', $invalidProduct);
        $this->assertSame(400, $res['status']);
        $data = json_decode($res['body'], true);
        $this->assertArrayHasKey('error', $data);

        // Precio negativo
        $invalidProduct2 = [
            'nombre'      => 'Producto con precio inválido',
            'descripcion' => 'Precio negativo',
            'precio'      => -50.5
        ];
        $res2 = $this->request('POST', '/productos', $invalidProduct2);
        $this->assertSame(400, $res2['status']);
        $data2 = json_decode($res2['body'], true);
        $this->assertArrayHasKey('error', $data2);
    }
}
