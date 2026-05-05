<?php
/**
 * AZTECA BAR - Procesamiento de Cierre y Cobro
 * Finaliza el estado del pedido, libera la mesa y genera el ticket legal/interno.
 */

include("../../config/conexion.php");

$mesa_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$items_resumen = [];
$total_cierre = 0;

if ($mesa_id <= 0) {
    header("Location: ../../index.php");
    exit;
}

/**
 * Localización del pedido activo para la mesa especificada
 */
$sql_pedido = "SELECT id FROM pedidos WHERE mesa_id = $mesa_id AND estado = 'abierto' ORDER BY id DESC LIMIT 1";
$res_pedido = $conn->query($sql_pedido);

if ($res_pedido && $res_pedido->num_rows > 0) {
    $pedido = $res_pedido->fetch_assoc();
    $pedido_id = $pedido['id'];

    /**
     * Obtención de detalles del pedido.
     * Se agrupan productos iguales para mostrar cantidad x precio.
     */
    $sql_detalles = "SELECT p.nombre, pd.precio_unitario, COUNT(*) as cantidad, SUM(pd.precio_unitario) as subtotal
                     FROM pedido_detalle pd 
                     JOIN productos p ON pd.producto_id = p.id 
                     WHERE pd.pedido_id = $pedido_id
                     GROUP BY p.nombre, pd.precio_unitario";
    
    $res_detalles = $conn->query($sql_detalles);

    while ($fila = $res_detalles->fetch_assoc()) {
        $items_resumen[] = $fila;
        $total_cierre += $fila['subtotal'];
    }

    /**
     * Actualización de estados en base de datos.
     * Cierre de pedido y liberación de la mesa física.
     */
    $conn->query("UPDATE pedidos SET estado = 'cerrado' WHERE id = $pedido_id");
    $conn->query("UPDATE mesas SET estado = 'libre' WHERE id = $mesa_id");

} else {
    echo "<script>alert('No hay pedidos abiertos para esta mesa.'); window.location='../../index.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!-- META -->
    <?php
        $title = "Cierre de Cuenta - Mesa # - " . $mesa_id;
        include("layout/meta.php");
    ?>

    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/cobrar.css?v=<?php echo filemtime('../../assets/css/cobrar.css'); ?>">

    <!-- ICONOS -->
    <?php
        include("layout/icons.php");
    ?>
</head>
<body onload="window.print();">

<div class="ticket-container">
    <div class="text-center">
        <h2 style="margin: 0;">BAR AZTECA</h2>
        <p style="margin: 5px 0;">TICKET DE CUENTA</p>
        <p>Mesa: <strong><?php echo $mesa_id; ?></strong></p>
        <p style="font-size: 12px;"><?php echo date("d/m/Y H:i:s"); ?> HS</p>
    </div>

    <div class="separador"></div>

    <table>
        <thead>
            <tr>
                <th align="left">DESCRIPCIÓN</th>
                <th align="right">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items_resumen as $item): ?>
            <tr>
                <td>
                    <?php echo $item['cantidad']; ?> x <?php echo htmlspecialchars($item['nombre']); ?>
                </td>
                <td class="text-right">
                    $<?php echo number_format($item['subtotal'], 2); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="separador"></div>

    <div class="text-right total-seccion">
        TOTAL A PAGAR: $<?php echo number_format($total_cierre, 2); ?>
    </div>

    <div class="text-center footer-ticket">
        <p>*** GRACIAS POR SU VISITA ***</p>
        <p>SISTEMA AZTECA V1.0</p>
    </div>

    <a href="../../index.php" class="btn-volver">FINALIZAR Y VOLVER</a>
</div>

</body>
</html>