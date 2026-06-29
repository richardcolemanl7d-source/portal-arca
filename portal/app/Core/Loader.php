<?php

namespace App\Core;

/**
 * Autoloader Nativo PSR-4
 * 
 * Permite cargar clases automáticamente sin depender de Composer.
 * Mapea el namespace 'App\' a la carpeta raíz 'app/'.
 * 
 * Uso:
 *   require_once __DIR__ . '/Loader.php';
 *   $loader = new Loader();
 *   $loader->register();
 */
class Loader
{
    /**
     * @var string Namespace base del proyecto
     */
    private $baseNamespace = 'App\\';

    /**
     * @var string Ruta base donde se encuentran las clases
     */
    private $basePath;

    public function __construct()
    {
        // Define la ruta absoluta a la carpeta 'app'
        // Asumiendo que este archivo está en app/Core/Loader.php
        $this->basePath = dirname(__DIR__);
    }

    /**
     * Registra el autoloader en la pila de SPL
     */
    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Carga una clase específica
     *
     * @param string $className Nombre completo de la clase (ej: App\Controllers\HomeController)
     * @return bool True si la clase fue cargada, False si no
     */
    public function loadClass(string $className): bool
    {
        // Verificar si la clase pertenece a nuestro namespace base
        if (strpos($className, $this->baseNamespace) !== 0) {
            return false;
        }

        // Obtener la parte relativa al namespace (ej: Controllers\HomeController)
        $relativeClass = substr($className, strlen($this->baseNamespace));

        // Reemplazar separadores de namespace por separadores de directorio
        // y agregar la extensión .php
        $file = $this->basePath . '/' . str_replace('\\', '/', $relativeClass) . '.php';

        // Si el archivo existe, lo incluimos
        if (file_exists($file)) {
            require_once $file;
            return true;
        }

        return false;
    }
}
