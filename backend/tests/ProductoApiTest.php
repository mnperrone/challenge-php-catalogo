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
     * @param array|string|null $body
     * @param array $extraHeaders
     * @return array{status: int, body: string, headers: array<string, string>}
     */
    private function request(string $method, string $path, $body = null, array $extraHeaders = []): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $headers = [];
        if (is_array($body)) {
            $jsonData = json_encode($body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($jsonData);
        } elseif (is_string($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $headers[] = 'Content-Length: ' . strlen($body);
        }

        foreach ($extraHeaders as $h) {
            $headers[] = $h;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $responseHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$responseHeaders) {
            $len = strlen($header);
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return $len;
        });

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status'  => $status,
            'body'    => $response ?: '',
            'headers' => $responseHeaders
        ];
    }

    /**
     * Test: Obtener listado de productos inicial y verificar cabeceras CORS
     */
    public function testListadoInicial(): void
    {
        $res = $this->request('GET', '/productos');
        $this->assertSame(200, $res['status']);

        // Verificar CORS
        $this->assertArrayHasKey('access-control-allow-origin', $res['headers']);
        $this->assertSame('*', $res['headers']['access-control-allow-origin']);

        $data = json_decode($res['body'], true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('precio_usd', $data);
    }

    /**
     * Test: Flujo completo CRUD de un producto con cabecera Location
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

        // Verificar cabecera Location
        $this->assertArrayHasKey('location', $resCreate['headers']);
        
        $createData = json_decode($resCreate['body'], true);
        $id = $createData['data']['id'];
        $this->assertSame("/productos/{$id}", $resCreate['headers']['location']);

        // 2. Detalle del Producto Creado
        $resShow = $this->request('GET', "/productos/{$id}");
        $this->assertSame(200, $resShow['status']);

        // 3. Editar Producto
        $updateProduct = [
            'nombre'      => 'Producto de Prueba Unitario Modificado',
            'descripcion' => 'Descripción modificada',
            'precio'      => 150000.00
        ];

        $resUpdate = $this->request('PUT', "/productos/{$id}", $updateProduct);
        $this->assertSame(200, $resUpdate['status']);

        // 4. Eliminar Producto
        $resDelete = $this->request('DELETE', "/productos/{$id}");
        $this->assertSame(204, $resDelete['status']);

        // 5. Verificar que ya no existe (404)
        $resShowDeleted = $this->request('GET', "/productos/{$id}");
        $this->assertSame(404, $resShowDeleted['status']);
    }

    /**
     * Test: Validaciones de formulario (400)
     */
    public function testValidacionesDeFormulario(): void
    {
        $invalidProduct = [
            'nombre'      => '',
            'descripcion' => 'Falta nombre válido',
            'precio'      => 100
        ];
        $res = $this->request('POST', '/productos', $invalidProduct);
        $this->assertSame(400, $res['status']);
    }

    /**
     * Test: Normalización de trailing slash
     */
    public function testTrailingSlashNormalization(): void
    {
        $res = $this->request('GET', '/productos/');
        $this->assertSame(200, $res['status']);
    }

    /**
     * Test: Method Not Allowed (405) y cabecera Allow
     */
    public function testMethodNotAllowedAllowHeader(): void
    {
        $res = $this->request('POST', '/productos/123', ['nombre' => 'Test']);
        $this->assertSame(405, $res['status']);
        $this->assertArrayHasKey('allow', $res['headers']);
        $this->assertStringContainsString('GET', $res['headers']['allow']);
        $this->assertStringContainsString('PUT', $res['headers']['allow']);
        $this->assertStringContainsString('DELETE', $res['headers']['allow']);
    }

    /**
     * Test: Content-Type inválido (415)
     */
    public function testUnsupportedMediaType(): void
    {
        $newProduct = [
            'nombre' => 'Producto de Prueba',
            'precio' => 100
        ];
        // Enviar con Content-Type texto plano
        $res = $this->request('POST', '/productos', json_encode($newProduct), ['Content-Type: text/plain']);
        $this->assertSame(415, $res['status']);
    }

    /**
     * Test: JSON malformado (400)
     */
    public function testInvalidJsonMalformed(): void
    {
        $res = $this->request('POST', '/productos', '{invalid_json}', ['Content-Type: application/json']);
        $this->assertSame(400, $res['status']);
    }

    /**
     * Test: JSON de tipo de dato inválido (400)
     */
    public function testInvalidJsonWrongType(): void
    {
        // Enviar un string simple "texto" en lugar de un objeto JSON {}
        $res = $this->request('POST', '/productos', '"texto"', ['Content-Type: application/json']);
        $this->assertSame(400, $res['status']);
    }
}
