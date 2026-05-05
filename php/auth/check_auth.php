<?php
/**
 * SISTEMA AZTECA - FIREWALL DE SEGURIDAD
 * Ubicación: php/auth/check_auth.php
 */
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// Si no hay sesión activa, redirigir a la raíz (donde está el modal de login)
if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
    // Al fallar el auth, lo mandamos al index para que use el login clickeable
    header("Location: ../../index.php");
    exit;
}
?>