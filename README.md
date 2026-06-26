# Catálogo de Productos - SPA & API REST (PHP Nativo)

Este proyecto es una aplicación web responsiva Single Page Application (SPA) conectada a una API REST construida en PHP Nativo, diseñada para gestionar un catálogo de productos con conversión dinámica de ARS a USD.

---

## Requisitos
* **Docker** y **Docker Compose** instalados en el sistema.

---

## Pasos para levantar el entorno

1. **Clonar el proyecto:**
   ```bash
   git clone <URL_DEL_REPOSITORIO>
   cd challenge-php-catalogo
   ```

2. **Configurar el archivo `.env`:**
   Copia el archivo de configuración de ejemplo:
   ```bash
   cp .env.example .env
   ```
   Abre el archivo `.env` y configura el valor de conversión a dólar deseado en la variable `PRECIO_USD`:
   ```ini
   PRECIO_USD=1000.00
   ```

3. **Compilar y levantar los contenedores:**
   ```bash
   docker compose up --build -d
   ```
   *Nota: Si modificas el archivo `.env` después de iniciar los servicios, debes recrear los contenedores para aplicar los cambios:*
   ```bash
   docker compose up --force-recreate -d
   ```

---

## Acceso al Sistema
* **Frontend (Catálogo):** [http://localhost:8080/](http://localhost:8080/) (redirige automáticamente a `/frontend/`).
* **Backend (API REST):** [http://localhost:8080/productos](http://localhost:8080/productos).

---

## Tabla de Endpoints de la API

| Método | Ruta | Descripción | Request Body (JSON) | Respuesta Exitosa |
|---|---|---|---|---|
| `GET` | `/productos` | Lista productos y retorna la cotización. | Ninguno | `200 OK` |
| `GET` | `/productos/{id}` | Muestra el detalle de un producto. | Ninguno | `200 OK` |
| `POST` | `/productos` | Registra un nuevo producto. | `{ "nombre": "...", "descripcion": "...", "precio": 1500 }` | `201 Created` |
| `PUT` | `/productos/{id}` | Modifica un producto existente. | `{ "nombre": "...", "descripcion": "...", "precio": 2500 }` | `200 OK` |
| `DELETE` | `/productos/{id}` | Remueve un producto del catálogo. | Ninguno | `204 No Content` |

---

## Ejecución de Tests
Para ejecutar la suite de pruebas de integración automatizadas (PHPUnit):
```bash
docker compose exec php ./vendor/bin/phpunit
```
## Capturas
- Index:
<img width="1734" height="920" alt="image" src="https://github.com/user-attachments/assets/bb98cff4-7270-41cc-8260-23bff3e56daf" />

- Alta de producto:
<img width="1736" height="913" alt="image" src="https://github.com/user-attachments/assets/84a93504-4465-4888-b816-58a144cf98fc" />

- Edición:
<img width="1797" height="929" alt="image" src="https://github.com/user-attachments/assets/29f80c4a-5530-4c14-bc97-fd51fd05e30c" />

- Eliminar producto:
<img width="1735" height="876" alt="image" src="https://github.com/user-attachments/assets/8fff7ba8-9167-47cb-8b1a-e7216695903a" />

<img width="1772" height="912" alt="image" src="https://github.com/user-attachments/assets/db7519ec-33fb-4a15-9310-7e497288a703" />




