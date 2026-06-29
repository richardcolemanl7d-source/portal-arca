# Guía de Integración con API ARCA

Esta guía detalla los pasos necesarios para conectar el **Portal** con la **API ARCA** (Administración Regional de Contribuyentes Autonomos).

## 1. Prerrequisitos

Antes de comenzar, asegúrate de tener:
- Credenciales válidas de la API ARCA (URL Base y Token de Acceso).
- Acceso al servidor donde está alojado el Portal.
- Permisos de escritura en la carpeta `config/` del proyecto.

## 2. Configuración de Variables de Entorno

La conexión no debe hardcodearse en el código fuente por seguridad. Utilizaremos un archivo `.env` (o las variables de entorno de tu servidor).

### Paso 2.1: Crear o editar el archivo `.env`

En la raíz del proyecto `portal/`, crea un archivo llamado `.env` si no existe, y agrega las siguientes líneas:

```ini
# Configuración API ARCA
ARCA_BASE_URL=https://api.arca.gob.ar/v1
ARCA_SERVICE_TOKEN=tu_token_secreto_aqui
ARCA_TIMEOUT=30
```

> **Nota:** Reemplaza `https://api.arca.gob.ar/v1` por la URL real de producción o sandbox, y `tu_token_secreto_aqui` por el token provisto por ARCA.

### Paso 2.2: Cargar variables en PHP

El sistema ya incluye un cargador simple en `config/bootstrap.php`. Asegúrate de que este archivo se esté incluyendo en `public/index.php`.

Si necesitas cargar manualmente el `.env`, el sistema busca estas variables automáticamente al inicializar la clase `ArcaService`.

## 3. Configuración del Servicio (Backend)

El núcleo de la conexión reside en `app/Services/ArcaService.php`. Este servicio actúa como cliente HTTP.

### Paso 3.1: Verificar la configuración en `ArcaService.php`

Abre el archivo `app/Services/ArcaService.php`. Deberías ver algo similar a esto:

```php
<?php

namespace App\Services;

class ArcaService
{
    private string $baseUrl;
    private string $token;
    private int $timeout;

    public function __construct()
    {
        // Obtención de variables de entorno
        $this->baseUrl = getenv('ARCA_BASE_URL') ?: 'https://api.arca.gob.ar/v1';
        $this->token = getenv('ARCA_SERVICE_TOKEN') ?: '';
        $this->timeout = (int)(getenv('ARCA_TIMEOUT') ?: 30);

        if (empty($this->token)) {
            throw new \Exception("Token de API ARCA no configurado.");
        }
    }

    // ... resto del código
}
```

**¿Qué tocar aquí?**
- Generalmente **no necesitas modificar** este archivo si usas el `.env`.
- Si tu API ARCA requiere un encabezado personalizado diferente a `Authorization: Bearer <token>`, modifica el método privado `buildHeaders()` dentro de esta clase.

## 4. Implementación en Controladores

Para usar la conexión en tus módulos (Empresas, Facturas, etc.), inyecta o instancia el servicio `ArcaService`.

### Ejemplo: Emitir una factura desde `InvoiceController.php`

```php
<?php

namespace App\Controllers;

use App\Services\ArcaService;
use App\Core\Request;
use App\Core\Response;

class InvoiceController
{
    private ArcaService $arcaService;

    public function __construct()
    {
        // Inicializamos el servicio de conexión
        $this->arcaService = new ArcaService();
    }

    public function store(Request $request, Response $response)
    {
        $data = $request->all();

        try {
            // Llamada a la API ARCA
            $result = $this->arcaService->post('/invoices', $data);
            
            // Guardar respuesta en BD local (lógica propia)
            // ...

            return $response->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return $response->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
```

## 5. Métodos Disponibles en `ArcaService`

El servicio incluye métodos helper para las operaciones HTTP más comunes:

| Método | Descripción | Ejemplo de uso |
|--------|-------------|----------------|
| `get(string $endpoint)` | Petición GET | `$service->get('/companies/123')` |
| `post(string $endpoint, array $data)` | Petición POST (Crear) | `$service->post('/invoices', $payload)` |
| `put(string $endpoint, array $data)` | Petición PUT (Actualizar) | `$service->put('/companies/123', $data)` |
| `delete(string $endpoint)` | Petición DELETE (Borrar) | `$service->delete('/api-keys/55')` |
| `upload(string $endpoint, string $filePath)` | Subida de archivos (Certificados) | `$service->upload('/certificates', '/path/to/cert.pem')` |

## 6. Manejo de Certificados (Empresas)

Para el módulo de empresas, la subida de certificados (.pem, .crt) y llaves privadas se realiza mediante el método `upload`.

**Flujo en `CompanyController`:**
1. El usuario sube el archivo desde el formulario HTML.
2. El controlador guarda el archivo temporalmente en `storage/uploads/`.
3. Se llama a `$this->arcaService->upload('/companies/' . $id . '/certificate', $rutaLocal)`.
4. Si la API responde OK, se guarda la referencia en la base de datos local.

## 7. Pruebas de Conexión

Para verificar que la conexión funciona correctamente sin interactuar con la interfaz gráfica:

1. Crea un archivo de prueba temporal `test_arca.php` en la raíz:
   ```php
   <?php
   require 'vendor/autoload.php';
   
   // Cargar variables de entorno manualmente si no hay bootstrap
   $dotenv = parse_ini_file('.env'); 
   foreach($dotenv as $key => $val) { putenv("$key=$val"); }

   use App\Services\ArcaService;

   try {
       $service = new ArcaService();
       // Intentar hacer un ping o get a un endpoint público
       $status = $service->get('/status'); 
       echo "Conexión exitosa: " . json_encode($status);
   } catch (Exception $e) {
       echo "Error de conexión: " . $e->getMessage();
   }
   ```
2. Ejecuta en terminal: `php test_arca.php`.
3. Borra el archivo de prueba después de confirmar.

## 8. Solución de Problemas Comunes

- **Error 401 Unauthorized**: Verifica que el `ARCA_SERVICE_TOKEN` en tu `.env` sea correcto y no tenga espacios extra.
- **Error de Timeout**: Aumenta el valor de `ARCA_TIMEOUT` en el `.env` si la API ARCA es lenta.
- **Error SSL/Certificate**: Si estás en un entorno de desarrollo local sin certificados válidos, puede que necesites configurar cURL para aceptar certificados autofirmados (solo en desarrollo) modificando la opción `CURLOPT_SSL_VERIFYPEER` en `ArcaService.php`.

---

*Documentación generada para el proyecto Portal ARCA - PHP 8.3 MVC*
