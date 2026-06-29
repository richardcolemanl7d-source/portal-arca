# Portal ARCA - Sin Dependencias (No-Composer)

Esta versión del proyecto ha sido configurada para funcionar **sin necesidad de ejecutar `composer install`**, utilizando un autoloader nativo PSR-4 desarrollado internamente.

## 🚀 Cambios Realizados

### 1. Nuevo Archivo: `app/Core/Loader.php`
Se ha creado una clase `App\Core\Loader` que implementa el estándar PSR-4 de forma nativa usando `spl_autoload_register`.

**Funcionamiento:**
- Detecta automáticamente clases bajo el namespace `App\`.
- Mapea el namespace a la carpeta `app/`.
- Convierte los separadores `\` en `/` para buscar el archivo correcto.
- Ejemplo: `App\Controllers\HomeController` → `app/Controllers/HomeController.php`.

### 2. Modificado: `public/index.php`
Se reemplazó la línea:
```php
require __DIR__ . '/../vendor/autoload.php'; // O autoload.php antiguo
```
Por:
```php
require_once __DIR__ . '/../app/Core/Loader.php';
$loader = new App\Core\Loader();
$loader->register();
```

## 📋 Requisitos Ahora Simplificados

Para levantar el proyecto solo necesitas:

1.  **PHP 8.3+** instalado y habilitado en el servidor web.
2.  **MySQL 8.0+** corriendo.
3.  **Extensión PDO MySQL** habilitada en PHP (`extension=pdo_mysql`).
4.  **Extensión cURL** habilitada (para conectar con API ARCA).
5.  **Extensión JSON** habilitada (usualmente viene por defecto).

**Ya NO es necesario:**
- Tener Composer instalado en el servidor de producción.
- Ejecutar `composer install`.
- Tener la carpeta `vendor/`.

## ⚠️ Consideraciones Importantes sobre Frontend

Al eliminar Composer, la gestión de librerías frontend cambia:

### Bootstrap 5, DataTables, ChartJS
En esta configuración "Sin Composer", estas librerías **se cargan vía CDN** directamente en los layouts.

**Archivo afectado:** `app/Views/Layouts/main.php`

Verifica que las líneas de inclusión sean así (ejemplo):
```html
<!-- Bootstrap CSS (CDN) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- DataTables (CDN) -->
<link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<!-- ChartJS (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
```

*Si antes usabas rutas locales tipo `/assets/vendor/bootstrap...`, deberás cambiarlas a las URLs del CDN o descargar los archivos manualmente a tu carpeta `public/assets/`.*

## 🔧 Pasos para Desplegar (Modo Sin Composer)

1.  **Subir Archivos:** Copia todo el contenido de la carpeta `portal/` a tu servidor.
2.  **Base de Datos:**
    *   Crea la base de datos en MySQL.
    *   Importa `database.sql`.
3.  **Configuración:**
    *   Edita `config/database.php` con tus credenciales reales.
    *   (Opcional) Crea `.env` si usas variables de entorno para la API ARCA.
4.  **Permisos:**
    *   Asegura que el usuario del servidor web tenga permiso de escritura en:
        *   `storage/logs/`
        *   `storage/uploads/`
5.  **Web Server:**
    *   Configura tu Document Root para que apunte a la carpeta `public/`.
6.  **Probar:**
    *   Accede a `http://tu-dominio.com/login`.
    *   No deberías ver errores de "Class not found".

## 🛠️ Solución de Problemas

**Error: `Class 'App\Core\Router' not found`**
*   **Causa:** El Loader no encuentra el archivo.
*   **Solución:** Verifica que la estructura de carpetas sea exacta. La clase `App\Core\Router` DEBE estar en `app/Core/Router.php`. Respeta mayúsculas y minúsculas (Case Sensitive), especialmente en servidores Linux.

**Error: `Call to undefined function getenv()` o similares**
*   **Causa:** Configuración de PHP restringida.
*   **Solución:** Revisa tu `php.ini`.

**Las librerías JS/CSS no cargan**
*   **Causa:** Bloqueo de CDN o rutas incorrectas.
*   **Solución:** Abre la consola del navegador (F12) y verifica que los archivos de `cdn.jsdelivr.net` estén cargando (estado 200). Si estás en una intranet sin internet, deberás descargar los archivos `.css` y `.js` y ponerlos en `public/assets/vendor/` y actualizar los `<link>` y `<script>` en `main.php`.

## 🔄 ¿Volver a usar Composer?

Si en el futuro decides usar Composer nuevamente:

1.  Borra o comenta las líneas del Loader en `public/index.php`.
2.  Restaura la línea original: `require __DIR__ . '/../vendor/autoload.php';`.
3.  Ejecuta `composer install` en la raíz del proyecto.
4.  Elimina el archivo `app/Core/Loader.php` (opcional, ya no se usará).

---

**Nota del Arquitecto:** Esta implementación es ideal para entornos donde no se tiene acceso a SSH para correr Composer o se busca minimizar la superficie de ataque eliminando la carpeta `vendor`. Sin embargo, para desarrollo local, seguir recomendando el uso de Composer para gestionar dependencias externas de forma más robusta.
