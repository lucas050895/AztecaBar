<?php
/**
 * Ubicación: php/actions/autoload_manual.php
 */
spl_autoload_register(function ($class_name) {
    // Definimos la raíz subiendo dos niveles desde php/actions/
    $raiz = dirname(__DIR__, 2); 
    
    // Construimos la ruta: Azteca / librerias / Mike42 / Escpos / ... .php
    $file = $raiz . DIRECTORY_SEPARATOR . 'librerias' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class_name) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});