<?php
    // Evita acceso directo por URL
    if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
        exit('Acceso denegado');
    }
?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="description" content="Plataforma integral de gestión para Bar Azteca. Control de mesas, pedidos en tiempo real y análisis detallado de ingresos y egresos para optimizar la rentabilidad.">
<meta name="keywords" content="sistema de mesas, gestión de bar, balance de ingresos, control de stock, comandas digitales, hostelería, administración de ventas">
<meta name="robots" content="index, follow">
<meta name="author" content="Lucas Conde">
<meta name="author" content="Bar Azteca">



<meta http-equiv="Expires" content="0">
<meta http-equiv="Last-Modified" content="0">
<meta http-equiv="Cache-Control" content="no-cache, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">



<title>
    <?php
        echo $title
    ?>
</title>