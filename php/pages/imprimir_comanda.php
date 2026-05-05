<?php
/**
 * AZTECA BAR - Generador de Comandas para Cocina
 * Procesa la lista de IDs de productos y genera un ticket de impresión.
 */

include("../../config/conexion.php");

// Recepción y validación de parámetros de entrada
$ids  = isset($_GET['ids']) ? $_GET['ids'] : '';
$mesa = isset($_GET['mesa']) ? $_GET['mesa'] : '?';
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'Mesa';

if (empty($ids)) {
    die("Error: No se han especificado productos para la comanda.");
}

// Sanitización de IDs para la consulta SQL
$id_array = explode(',', $ids);
$ids_safe = implode(',', array_map('intval', $id_array));

/**
 * Recuperación de información de productos y subcategorías.
 * Se utiliza un JOIN para minimizar el impacto en la base de datos.
 */
$sql = "SELECT p.nombre, s.nombre as subcategoria 
        FROM productos p 
        JOIN subcategorias s ON p.subcategoria_id = s.id 
        WHERE p.id IN ($ids_safe)";

$res = $conn->query($sql);

$resumen = [];
if ($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()){
        $nom = $row['nombre'];
        if (!isset($resumen[$nom])) {
            $resumen[$nom] = [
                'cant' => 0, 
                'sub' => $row['subcategoria']
            ];
        }
        $resumen[$nom]['cant']++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- META -->
    <?php
        $title = "Comanda - " . htmlspecialchars($mesa);
        include("layout/meta.php");
    ?>

    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/imprimir_comanda.css?v=<?php echo filemtime('../../assets/css/imprimir_comanda.css'); ?>">

    <!-- ICONOS -->
    <?php
        include("layout/icons.php");
    ?>
</head>
<body onload="window.print(); setTimeout(() => { window.close(); }, 1000);">

    <div class="ticket">
        <div class="centrado">
            <strong>AZTECA BAR</strong><br>
            <strong>*** <?php echo strtoupper(htmlspecialchars($tipo)) . " " . htmlspecialchars($mesa); ?> ***</strong>
        </div>

        <div class="separador"></div>

        <table>
            <?php foreach($resumen as $nombre => $datos): ?>
            <tr>
                <td class="qty"><?php echo $datos['cant']; ?>x</td>
                <td class="item">
                    <?php echo htmlspecialchars($nombre); ?><br>
                    <span class="subcategoria">(<?php echo htmlspecialchars($datos['sub']); ?>)</span>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <div class="separador"></div>

        <div class="centrado">
            <?php echo date('d/m/Y H:i'); ?> HS<br>
            <strong>SECCIÓN: COCINA</strong>
        </div>
        
        <br><br>
    </div>

</body>
</html>