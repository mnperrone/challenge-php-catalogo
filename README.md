# Catálogo de Productos - SPA & API REST (PHP Nativo)

Este proyecto es una aplicación web responsiva en arquitectura de una sola página (SPA) conectada a una API REST construida enteramente en PHP Nativo (sin frameworks), diseñada para gestionar un catálogo de productos con conversión dinámica de divisas (ARS a USD).

---

## 🚀 Arquitectura y Tecnologías
* **Backend:** PHP 8.3 (Nativo) + Apache + MySQL 8.0.
  * **Ruteador:** `nikic/fast-route` (enrutamiento rápido y flexible).
  * **Persistencia:** PDO con patrón Singleton y manejo robusto de reintentos de conexión.
  * **CORS:** Soporte completo configurado para peticiones cruzadas.
* **Frontend:** Single Page Application (SPA).
  * **Estructura y Estilos:** Vanilla HTML5 + Vanilla CSS3 con diseño oscuro moderno y efectos *glassmorphism*.
  * **Interactividad:** Vanilla Javascript (ES6) asíncrono (`fetch`).
  * **Componentes visuales:** Sistema de notificaciones flotantes (Toasts) autolimpiables (5 segundos) y un Modal de confirmación estilizado para eliminar ítems.
* **Entorno:** Dockerizado con `docker-compose`.

---

## 🛠️ Requisitos Previos
* Tener instalado **Docker** y **Docker Compose**.

---

## ⚙️ Configuración y Setup

1. **Clonar el Repositorio:**
   ```bash
   git clone <URL_DEL_REPOSITORIO>
   cd challenge-php-catalogo
   ```

2. **Crear el Archivo de Entorno:**
   Copia el archivo `.env.example` como `.env`:
   ```bash
   cp .env.example .env
   ```

3. **Configurar Variables:**
   Abre el archivo `.env` y configura las credenciales de la base de datos y la tasa de conversión a dólar (`PRECIO_USD`):
   ```ini
   # Base de Datos
   DB_HOST=mysql
   DB_PORT=3306
   DB_NAME=catalogo
   DB_USER=catalogo_user
   DB_PASSWORD=catalogo_pass
   MYSQL_ROOT_PASSWORD=root_pass

   # Conversión de Moneda
   PRECIO_USD=1000.00
   ```

4. **Levantar el Entorno:**
   Ejecuta el siguiente comando para compilar e iniciar los servicios en segundo plano:
   ```bash
   docker compose up --build -d
   ```
   *Nota: La base de datos se inicializa automáticamente con la tabla necesaria gracias a `docker/mysql/init.sql`.*

---

## ⚠️ Advertencia Importante: Modificaciones del `.env`
Docker Compose lee las variables de entorno de tu archivo `.env` local al momento de levantar el servicio. Si editas y guardas cambios en el archivo `.env` (como modificar el valor de `PRECIO_USD`), **los cambios no se aplicarán automáticamente**. 

Debes recrear los contenedores para forzar la actualización ejecutando:
```bash
docker compose up --force-recreate -d
```

---

## 🖥️ Acceso a la Aplicación
* **Frontend (Interfaz de Usuario):** Accede a [http://localhost:8080/frontend/](http://localhost:8080/frontend/) desde tu navegador.
* **Backend (API REST):** La API corre en la raíz del servidor Apache en [http://localhost:8080/](http://localhost:8080/).

---

## 📑 Endpoints de la API REST

Todos los cuerpos de solicitud y respuestas son en formato `application/json`.

| Método | Ruta | Descripción | Request Body | Respuesta Exitosa |
|---|---|---|---|---|
| `GET` | `/productos` | Lista todos los productos y retorna el dólar configurado. | Ninguno | `200 OK` con `{ "data": [...], "precio_usd": X }` |
| `GET` | `/productos/{id}` | Retorna el detalle de un producto específico. | Ninguno | `200 OK` con `{ "data": { ... } }` |
| `POST` | `/productos` | Registra un nuevo producto en el catálogo. | `{ "nombre": "...", "descripcion": "...", "precio": X }` | `201 Created` con `{ "data": { ... } }` |
| `PUT` | `/productos/{id}` | Actualiza un producto existente. | `{ "nombre": "...", "descripcion": "...", "precio": X }` | `200 OK` con `{ "data": { ... } }` |
| `DELETE` | `/productos/{id}` | Elimina un producto de la base de datos. | Ninguno | `204 No Content` (sin body) |

---

## 🧪 Pruebas Automatizadas (Suite de Tests)
Implementamos una suite de pruebas de integración end-to-end con **PHPUnit** para asegurar la calidad de la API. Estas pruebas validan todo el flujo CRUD y las validaciones de campos.

Para ejecutar los tests, corre el siguiente comando dentro del contenedor PHP:
```bash
docker compose exec php ./vendor/bin/phpunit
```

*Ejemplo de salida:*
```text
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.3.31
Configuration: /var/www/html/phpunit.xml

...                                                                 3 / 3 (100%)

Time: 00:00.041, Memory: 8.00 MB

OK (3 tests, 24 assertions)
```

---

## 🔒 Decisiones de Seguridad y Estabilidad
* **Reintentos en Conexión a Base de Datos:** `Database.php` incluye un ciclo de hasta 5 intentos de conexión con esperas de 1 segundo para soportar la latencia inicial de levantamiento de MySQL en Docker.
* **Protección XSS:** El frontend mapea los strings del catálogo usando la propiedad `.textContent` del DOM, previniendo inyecciones de HTML/JS en el navegador.
* **Sanitización de Inputs:** El backend remueve nulos y caracteres de control (`\0`) de todas las cadenas entrantes antes de validar y guardar en base de datos.
* **Consultas Preparadas:** PDO se configuró con `ATTR_EMULATE_PREPARES => false`, utilizando sentencias preparadas nativas para anular cualquier riesgo de inyección SQL.
