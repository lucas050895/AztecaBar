<?php
include("../../config/conexion.php");

$mesa_id = isset($_GET['mesa']) ? intval($_GET['mesa']) : 0;
$tipo    = isset($_GET['tipo']) ? $_GET['tipo'] : 'mesa';

if ($mesa_id == 0) die("Error: ID de mesa no especificado.");

// 1. Buscamos el último pedido
$sql_pedido = "SELECT id FROM pedidos 
               WHERE mesa_id = $mesa_id AND tipo_entidad = '$tipo' 
               ORDER BY id DESC LIMIT 1";

$res_pedido = $conn->query($sql_pedido);
$pedido = $res_pedido->fetch_assoc();

if (!$pedido) die("Error: No se encontró ningún pedido para esta mesa.");

$pedido_id = $pedido['id'];

// 2. Obtener productos y AGRUPARLOS (como en la comanda)
$sql_detalle = "SELECT pd.precio_unitario, p.nombre, s.nombre as subcategoria 
                FROM pedido_detalle pd 
                JOIN productos p ON pd.producto_id = p.id 
                JOIN subcategorias s ON p.subcategoria_id = s.id
                WHERE pd.pedido_id = $pedido_id";
$res_detalle = $conn->query($sql_detalle);

$resumen = [];
$totalBruto = 0;
$cantEmpanadas = 0;

while($row = $res_detalle->fetch_assoc()){
    $nom = $row['nombre'];
    $precio = $row['precio_unitario'];
    
    if(!isset($resumen[$nom])) {
        $resumen[$nom] = [
            'cant' => 0, 
            'precio' => $precio,
            'sub' => $row['subcategoria']
        ];
    }
    $resumen[$nom]['cant']++;
    $totalBruto += $precio;
    
    if(stripos($nom, 'empanada') !== false) $cantEmpanadas++;
}

// Lógica de descuento
$descuento = ($cantEmpanadas >= 12) ? floor($cantEmpanadas / 12) * 2800 : 0;
$totalFinal = $totalBruto - $descuento;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- META -->
    <?php
        $title = 'Ticket #' . $pedido_id;
        include("layout/meta.php")
    ?>

    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/imprimir_ticket.css">

    <!-- ICONS -->
    <?php include("layout/icons.php") ?>
</head>
<body onload="window.print(); setTimeout(() => { window.close(); }, 1000);">
    <div class="header">
        <strong class="system">AZTECA BAR</strong>
        <br>
        <strong>TICKET DE CONSUMO</strong><br>
        <?php echo strtoupper($tipo); ?> #<?php echo $mesa_id; ?><br>
        <?php echo date("d/m/Y H:i"); ?>
    </div>
    
    <div class="separador"></div>
    
    <table>
        <?php foreach($resumen as $nombre => $datos): ?>
        <tr>
            <td class="qty"><?php echo $datos['cant']; ?>x</td>
            <td class="item">
                <?php echo strtoupper($nombre); ?><br>
                <small style="font-size: 10pt;">(<?php echo $datos['sub']; ?>)</small>
            </td>
            <td class="price">$<?php echo number_format($datos['precio'] * $datos['cant'], 0, ',', '.'); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php if($descuento > 0): ?>
        <div class="separador"></div>
        <div class="text-right">Promo Docena: -$<?php echo number_format($descuento, 0, ',', '.'); ?></div>
    <?php endif; ?>

    <div class="separador"></div>
    <div class="text-right total">
        TOTAL: $<?php echo number_format($totalFinal, 0, ',', '.'); ?>
    </div>
    
    <div class="thanks">
        ¡Muchas gracias por su visita!
    </div>
    <br>
    <div class="invalid">
        Ticket no valido como factura
    </div>
</body>
</html>