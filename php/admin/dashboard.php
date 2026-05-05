<?php
/**
 * SISTEMA AZTECA - DASHBOARD ADMINISTRATIVO
 * Provee una visión general del estado del inventario y la infraestructura del salón.
 */

require_once("../auth/check_auth.php"); 
require_once("../../config/conexion.php");

/**
 * CONSULTAS DE MÉTRICAS (KPIs)
 * Se obtienen los conteos totales de las entidades principales para el resumen.
 */
$c_p = $conn->query("SELECT COUNT(*) as t FROM productos")->fetch_assoc()['t'];
$c_m = $conn->query("SELECT COUNT(*) as t FROM mesas")->fetch_assoc()['t'];
$c_b = $conn->query("SELECT COUNT(*) as t FROM barriles")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- META -->
    <?php
        $title = "Dashboard";
        include("../pages/layout/meta.php");
    ?>

    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css?v=<?php echo filemtime('../../assets/css/dashboard.css'); ?>">

    <!-- ICONOS -->
    <?php
        include("../pages/layout/icons.php");
    ?>
</head>
<body>

<header class="admin-header">
    <div class="brand">
        AZTECA BAR <span class="brand-sub">DASHBOARD</span>
    </div>
    <nav class="user-nav">
        <a href="../auth/logout.php" class="logout-link">Cerrar Sesión</a>
    </nav>
</header>

<main class="main-content">
    <div class="grid-admin">
        <article class="admin-card card-productos" onclick="location.href='admin_productos.php'">
            <h2>PRODUCTOS</h2>
            <div class="val"><?php echo $c_p; ?></div>
            <p>Precios, Stock y Catálogo</p>
        </article>

        <article class="admin-card card-mesas" onclick="location.href='admin_mesas.php'">
            <h2>MESAS</h2>
            <div class="val"><?php echo $c_m; ?></div>
            <p>Gestión de Mesas</p>
        </article>

        <article class="admin-card card-barriles" onclick="location.href='admin_barriles.php'">
            <h2>BARRILES</h2>
            <div class="val"><?php echo $c_b; ?></div>
            <p>Gestión de Barriles</p>
        </article>
    </div>
</main>

</body>
</html>