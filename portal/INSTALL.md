# Guía de Instalación - Portal ARCA

## Requisitos Previos

- **PHP 8.3+** con las siguientes extensiones:
  - pdo
  - pdo_mysql
  - json
  - openssl
  - mbstring
  
- **MySQL 8.0+**

- **Composer** (gestor de dependencias de PHP)

- **Servidor Web**: Apache o Nginx

---

## Paso 1: Clonar el Repositorio

```bash
cd /var/www/html
git clone <url-del-repositorio> portal
cd portal
```

---

## Paso 2: Instalar Dependencias con Composer

```bash
composer install
```

Esto instalará las dependencias necesarias y generará el autoload PSR-4.

---

## Paso 3: Configurar la Base de Datos

### 3.1 Crear la Base de Datos

```bash
mysql -u root -p
```

```sql
CREATE DATABASE portal_arca CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### 3.2 Importar el Esquema

```bash
mysql -u root -p portal_arca < database.sql
```

### 3.3 Configurar Credenciales

Editar el archivo `config/database.php`:

```php
<?php

return [
    'host' => 'localhost',
    'database' => 'portal_arca',
    'username' => 'tu_usuario',
    'password' => 'tu_contraseña',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
];
```

---

## Paso 4: Configurar el Servidor Web

### Apache

Crear un archivo `.htaccess` en `public/` (si no existe):

```apache
RewriteEngine On

# Redirigir todo al index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

# Habilitar CORS si es necesario
# Header set Access-Control-Allow-Origin "*"
```

Configurar VirtualHost:

```apache
<VirtualHost *:80>
    ServerName portal.local
    DocumentRoot /var/www/html/portal/public
    
    <Directory /var/www/html/portal/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/portal_error.log
    CustomLog ${APACHE_LOG_DIR}/portal_access.log combined
</VirtualHost>
```

Habilitar el sitio:

```bash
sudo a2ensite portal.conf
sudo systemctl restart apache2
```

### Nginx

Configurar server block:

```nginx
server {
    listen 80;
    server_name portal.local;
    root /var/www/html/portal/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

---

## Paso 5: Configurar Permisos

```bash
# Directorio de logs
chmod -R 755 storage/logs
chown -R www-data:www-data storage/logs

# Directorio de uploads
chmod -R 755 public/uploads
chown -R www-data:www-data public/uploads

# Subdirectorios específicos
mkdir -p public/uploads/certificates
mkdir -p public/uploads/keys
chmod -R 755 public/uploads/certificates
chmod -R 755 public/uploads/keys
```

---

## Paso 6: Verificar la Instalación

Acceder a: `http://portal.local`

Debería redirigir automáticamente al login.

---

## Paso 7: Login Inicial

### Usuario Administrador por Defecto

- **Email**: `admin@portal.com`
- **Contraseña**: `admin123`

**¡IMPORTANTE!** Cambiar la contraseña inmediatamente después del primer login.

---

## Paso 8: Configuración Adicional

### 8.1 Configurar Zona Horaria

Editar `php.ini`:

```ini
date.timezone = America/Argentina/Buenos_Aires
```

O en `config/app.php`:

```php
'default_timezone' => 'America/Argentina/Buenos_Aires'
```

### 8.2 Configurar Límites de Upload

En `php.ini`:

```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

### 8.3 Configurar Logs de PHP

```ini
error_log = /var/log/php/portal_errors.log
log_errors = On
display_errors = Off
```

---

## Paso 9: Pruebas de Funcionalidad

### 9.1 Verificar Conexión a Base de Datos

```bash
php -r "
require 'vendor/autoload.php';
\$db = App\Core\Database::getInstance();
print_r(\$db->fetch('SELECT VERSION() as version'));
"
```

### 9.2 Verificar Rutas

Probar las siguientes URLs:

- `/login` - Formulario de login
- `/dashboard` - Panel principal (requiere autenticación)
- `/companies` - Gestión de empresas
- `/api-keys` - Gestión de API Keys
- `/admin` - Panel de administración (solo admin)

---

## Paso 10: Configuración de Producción

### 10.1 Habilitar HTTPS

Obtener certificado SSL (Let's Encrypt):

```bash
sudo certbot --apache -d portal.local
```

### 10.2 Variables de Entorno

Para mayor seguridad, usar variables de entorno en lugar de hardcodear credenciales:

```bash
export DB_HOST=localhost
export DB_DATABASE=portal_arca
export DB_USERNAME=usuario
export DB_PASSWORD=contraseña_segura
```

### 10.3 Deshabilitar Modo Debug

En `config/app.php`:

```php
'debug' => false,
```

### 10.4 Configurar Backup Automático

Script de backup diario:

```bash
#!/bin/bash
# /usr/local/bin/backup-portal.sh

DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u root -p'tu_password' portal_arca > /backups/portal_arca_$DATE.sql
find /backups -name "portal_arca_*.sql" -mtime +7 -delete
```

Agregar a crontab:

```bash
0 2 * * * /usr/local/bin/backup-portal.sh
```

---

## Solución de Problemas Comunes

### Error: "PDOException: SQLSTATE[HY000] [2002] Connection refused"

**Solución**: Verificar que MySQL esté corriendo:

```bash
sudo systemctl status mysql
sudo systemctl start mysql
```

### Error: "Class not found"

**Solución**: Regenerar autoload:

```bash
composer dump-autoload
```

### Error: "Permission denied" en uploads

**Solución**:

```bash
chown -R www-data:www-data public/uploads
chmod -R 755 public/uploads
```

### Error: "CSRF token mismatch"

**Solución**: Verificar que la sesión esté configurada correctamente y que los formularios incluyan el token CSRF.

### Error: "Certificate upload failed"

**Solución**: Verificar permisos del directorio y tamaño máximo de upload en php.ini.

---

## Usuarios de Prueba

Después de instalar, puede crear usuarios de prueba:

1. Loguearse como admin
2. Ir a `/admin/users`
3. Crear nuevos usuarios con diferentes roles

### Roles Disponibles

- **admin**: Acceso completo al sistema
- **cliente**: Acceso limitado a su empresa
- **operador**: Acceso operativo limitado

---

## Soporte

Para soporte técnico:

1. Revisar logs en `storage/logs/`
2. Verificar logs de errores de PHP
3. Consultar la documentación en `README.md`

---

## Actualización

Para actualizar el sistema:

```bash
git pull origin main
composer install
# Si hay migraciones de base de datos
mysql -u root -p portal_arca < migrations/latest.sql
```

---

## Seguridad

### Recomendaciones

1. Cambiar todas las contraseñas por defecto
2. Usar HTTPS en producción
3. Configurar firewall para permitir solo puertos necesarios
4. Actualizar regularmente PHP y MySQL
5. Hacer backups periódicos
6. Monitorear logs de auditoría regularmente
7. Rotar API Keys periódicamente

---

**Última actualización**: Junio 2024  
**Versión**: 1.0.0
