# Portal ARCA

Sistema de administración de clientes para la API de ARCA (Autoridad Regional de Certificación).

## Características

- **Autenticación y Autorización**
  - Login/Logout
  - Recuperación de contraseña
  - Sistema de roles (admin, cliente, operador)
  
- **Gestión de Empresas**
  - Alta, edición y baja de empresas
  - Subida de certificados digitales
  - Subida de private keys
  - Control de vencimiento de certificados

- **API Keys**
  - Generación de API Keys
  - Revocación de API Keys
  - Rotación de API Keys
  - Control de permisos

- **Dashboard**
  - Cantidad de facturas emitidas
  - CAE emitidos
  - Consumo mensual
  - Estado de certificados
  - Gráficas con ChartJS

- **Estadísticas**
  - Facturas emitidas
  - Errores
  - Latencia promedio
  - Consumo por día

## Requisitos

- PHP 8.3+
- MySQL 8.0+
- Composer
- Extensiones PHP: pdo, pdo_mysql, json, openssl

## Instalación

1. Clonar el repositorio:
```bash
git clone <repository-url> portal
cd portal
```

2. Instalar dependencias:
```bash
composer install
```

3. Configurar la base de datos:
   - Crear archivo `config/database.php` con tus credenciales
   - O usar variables de entorno:
     - `DB_HOST`
     - `DB_DATABASE`
     - `DB_USERNAME`
     - `DB_PASSWORD`

4. Importar el esquema de base de datos:
```bash
mysql -u root -p portal_arca < database.sql
```

5. Configurar el servidor web para apuntar a `public/` como directorio raíz

6. Asignar permisos:
```bash
chmod -R 755 storage/logs
chmod -R 755 public/uploads
```

## Estructura del Proyecto

```
portal/
├── app/
│   ├── Controllers/          # Controladores MVC
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── CompanyController.php
│   │   ├── ApiKeyController.php
│   │   ├── BillingController.php
│   │   └── InvoiceController.php
│   ├── Core/                 # Núcleo del framework
│   │   ├── Database.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Router.php
│   │   ├── Auth.php
│   │   ├── Session.php
│   │   └── Validator.php
│   ├── Middleware/           # Middleware
│   │   ├── AuthMiddleware.php
│   │   └── AdminMiddleware.php
│   ├── Models/               # Modelos
│   │   └── Repositories/     # Repositorios
│   ├── Services/             # Servicios
│   ├── Views/                # Vistas
│   │   ├── Layouts/          # Plantillas base
│   │   ├── Components/       # Componentes reutilizables
│   │   ├── auth/             # Vistas de autenticación
│   │   ├── dashboard/        # Vistas del dashboard
│   │   ├── companies/        # Vistas de empresas
│   │   ├── api-keys/         # Vistas de API Keys
│   │   ├── invoices/         # Vistas de facturas
│   │   └── errors/           # Páginas de error
│   └── Helpers/              # Funciones auxiliares
├── config/                   # Configuración
│   ├── database.php
│   ├── app.php
│   └── routes.php
├── public/                   # Directorio público
│   ├── assets/               # CSS, JS, imágenes
│   ├── uploads/              # Archivos subidos
│   └── index.php             # Punto de entrada
├── storage/                  # Almacenamiento
│   └── logs/                 # Logs de la aplicación
├── composer.json
├── database.sql
└── README.md
```

## Rutas Principales

### Públicas
- `GET /` - Redirecciona al dashboard o login
- `GET /login` - Formulario de login
- `POST /login` - Procesar login
- `GET /logout` - Cerrar sesión
- `GET /forgot-password` - Recuperar contraseña
- `POST /forgot-password` - Enviar email de recuperación
- `GET /reset-password/{token}` - Restablecer contraseña
- `POST /reset-password` - Procesar restablecimiento

### Protegidas
- `GET /dashboard` - Panel principal
- `GET /dashboard/stats` - Estadísticas en tiempo real (AJAX)
- `GET /companies` - Listado de empresas
- `GET /companies/create` - Formulario crear empresa (admin)
- `POST /companies` - Crear empresa
- `GET /companies/{id}` - Ver detalle de empresa
- `GET /companies/{id}/edit` - Formulario editar empresa (admin)
- `PUT /companies/{id}` - Actualizar empresa
- `DELETE /companies/{id}` - Eliminar empresa
- `POST /companies/{id}/upload-certificate` - Subir certificado
- `GET /api-keys` - Listado de API Keys
- `POST /api-keys` - Generar API Key
- `PUT /api-keys/{id}/revoke` - Revocar API Key
- `PUT /api-keys/{id}/rotate` - Rotar API Key
- `DELETE /api-keys/{id}` - Eliminar API Key
- `GET /invoices` - Listado de facturas
- `POST /invoices` - Emitir factura
- `GET /billing` - Facturación y suscripción
- `GET /profile` - Mi perfil
- `PUT /profile` - Actualizar perfil
- `PUT /profile/change-password` - Cambiar contraseña

### Administración (solo rol admin)
- `GET /admin` - Dashboard de administración con estadísticas globales
- `GET /admin/statistics` - Estadísticas detalladas (AJAX)
- `GET /admin/users` - Gestión de usuarios (ABM)
- `POST /admin/users` - Crear usuario
- `PUT /admin/users/{id}` - Actualizar usuario
- `DELETE /admin/users/{id}` - Eliminar usuario
- `GET /admin/plans` - Gestión de planes (ABM)
- `POST /admin/plans` - Crear plan
- `PUT /admin/plans/{id}` - Actualizar plan
- `DELETE /admin/plans/{id}` - Eliminar plan
- `GET /admin/audit-logs` - Logs de auditoría del sistema

## Roles de Usuario

- **admin**: Acceso completo al sistema
- **cliente**: Acceso limitado a su empresa
- **operador**: Acceso operativo limitado

## Tecnologías Utilizadas

- **Backend**: PHP 8.3 (sin frameworks)
- **Base de Datos**: MySQL 8.0
- **Frontend**: 
  - Bootstrap 5.3
  - DataTables 1.13
  - Chart.js 4.4
  - Font Awesome 6.4
- **Patrón**: MVC
- **Autoload**: PSR-4
- **Dependencias**: Composer

## Seguridad

- Hash de contraseñas con bcrypt
- Protección CSRF (implementar en formularios)
- Validación de entradas
- Sanitización de salidas
- API Keys con hash SHA-256
- Logs de auditoría

## API ARCA

El sistema está diseñado para consumir la API de ARCA mediante HTTP. La integración incluye:

- Autenticación con certificados digitales
- Emisión de comprobantes
- Consulta de CAE
- Manejo de errores y reintentos

## Logs

Los logs se almacenan en `storage/logs/`. Se recomienda configurar rotación de logs.

## Licencia

Propietario - Todos los derechos reservados.

## Soporte

Para soporte técnico, contactar al administrador del sistema.
