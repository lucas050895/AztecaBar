<?php
/**
 * SISTEMA AZTECA - CIERRE DE SESIÓN
 * Ubicación: php/auth/logout.php
 */
session_start();
session_unset();
session_destroy();

// Subimos un nivel (php/) y entramos a admin/
header("Location: ../../index.php");
exit;