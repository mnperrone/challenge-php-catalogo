# Arquitectura del Catálogo

Este documento resume de forma directa las decisiones de diseño y la justificación técnica del proyecto:

*   **¿Por qué no usar un framework?**
    El challenge solicitaba PHP nativo. Se buscó demostrar conocimiento de los componentes que normalmente abstrae un framework (routing, front controller, configuración, acceso a datos y estructura MVC) utilizando únicamente las dependencias estrictamente necesarias. La única librería incorporada fue FastRoute, ya que resuelve un problema específico sin introducir el peso de un framework completo.

*   **¿Por qué MVC?**
    Provee una separación de responsabilidades clara y familiar. El Modelo (`Producto`) encapsula los datos y el cálculo dinámico de ARS a USD; el Controlador (`ProductoController`) gestiona la lógica HTTP (validación de JSON, tipos de datos y códigos de estado); y la Vista se delega a respuestas JSON estructuradas en la clase `Response`.

*   **¿Por qué FastRoute?**
    Evita reinventar la rueda del análisis de rutas con expresiones regulares. Es una librería ligera y el estándar *de facto* en proyectos PHP nativos sin framework para mapear peticiones y parámetros dinámicos de forma segura.

*   **¿Por qué Composition Root?**
    Se centraliza el cableado y la instanciación de dependencias en `index.php`. Al inyectar el objeto `PDO` y las variables de configuración en los constructores, se desacoplan las clases de negocio del entorno externo, facilitando los tests automatizados (como los de integración de la API).

*   **¿Por qué PDO + Singleton?**
    `PDO` es la abstracción nativa de PHP que garantiza seguridad frente a SQL Injection mediante sentencias preparadas nativas. El patrón Singleton en `Database::getInstance()` asegura una única conexión activa a MySQL dentro del ciclo de vida de una misma petición HTTP, optimizando recursos. En PHP, cada petición HTTP tiene un ciclo de vida independiente (shared-nothing). El Singleton no comparte conexiones entre usuarios; únicamente evita abrir múltiples conexiones PDO durante una misma request.

*   **¿Por qué SQL Nativo y no ORM?**
    Para gestionar una sola tabla con operaciones CRUD elementales, incluir un ORM completo (como Eloquent o Doctrine) representaría sobreingeniería y agregaría un peso innecesario a las dependencias. El SQL nativo mantiene el proyecto proporcional a su complejidad.

*   **¿Cómo evolucionaría el sistema?**
    Ante un crecimiento a decenas de recursos, la evolución se planificaría en tres fases progresivas:
    1.  **Contenedor de Inyección de Dependencias (PSR-11):** Para automatizar la resolución de dependencias (*autowiring*) y evitar que `index.php` crezca linealmente.
    2.  **Abstracción de persistencia:** Extraer la lógica común de acceso a datos cuando la duplicación empiece a justificarlo, evaluando en ese momento la conveniencia de un modelo base o un patrón Repository.
    3.  **Middlewares y Respuestas PSR-7:** Reemplazar los cortes `exit;` en los controladores por retornos de interfaces `ResponseInterface`, permitiendo interceptores globales de seguridad, logs y rate-limiting.
