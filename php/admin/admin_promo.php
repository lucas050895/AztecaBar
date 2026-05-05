<?php
/**
 * SISTEMA AZTECA - PANEL DE CONTROL DE PRECIOS
 * Este archivo sincroniza promo_empanadas con la tabla productos.
 */
include("../../config/conexion.php");

// Procesar el cambio de precios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizamos las entradas
    $p_unidad = mysqli_real_escape_string($conn, $_POST['p_unidad']);
    $p_docena = mysqli_real_escape_string($conn, $_POST['p_docena']);

    // 1. Actualizamos la tabla de configuración de la promoción
    $conn->query("UPDATE promo_empanadas SET valor = '$p_unidad' WHERE clave = 'precio_unidad'");
    $conn->query("UPDATE promo_empanadas SET valor = '$p_docena' WHERE clave = 'precio_docena'");

    // 2. Actualizamos el precio individual de todos los productos de la categoría 'Empanadas'
    // Usamos un subquery para encontrar el ID de la categoría por su nombre
    $sql_update_prods = "UPDATE productos 
                         SET precio = '$p_unidad' 
                         WHERE subcategoria_id IN (
                            SELECT id FROM subcategorias WHERE nombre = 'Empanadas'
                         )";
    
    if ($conn->query($sql_update_prods)) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>
                <b>¡Éxito!</b> Se actualizó el precio de la promo y de todos los productos en la categoría Empanadas.
              </div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>
                Error al actualizar productos: " . $conn->error . "
              </div>";
    }
}

// Consultar precios actuales para mostrar en los inputs
$res = $conn->query("SELECT * FROM promo_empanadas");
$p = [];
while($r = $res->fetch_assoc()) {
    $p[$r['clave']] = $r['valor'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Precios y Promos</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 30px; background: #eceff1; color: #333; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 450px; margin: auto; }
        h2 { color: #e65100; margin-top: 0; border-bottom: 2px solid #ffb74d; padding-bottom: 10px; }
        label { display: block; margin-top: 15px; font-weight: bold; color: #555; }
        input { width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 16px; }
        button { background: #fb8c00; color: white; border: none; padding: 15px; width: 100%; cursor: pointer; font-weight: bold; border-radius: 6px; font-size: 16px; margin-top: 20px; transition: background 0.3s; }
        button:hover { background: #ef6c00; }
        .footer-link { display: block; text-align: center; margin-top: 20px; color: #757575; text-decoration: none; }
        .info { font-size: 0.85em; color: #666; margin-top: 5px; line-height: 1.4; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Gestión de Precios</h2>
        <p class="info">Al guardar, se cambiará el precio de <b>todas las empanadas</b> y se ajustará el cálculo de la docena automáticamente.</p>
        
        <form method="POST">
            <label>Precio por Unidad ($):</label>
            <input type="number" name="p_unidad" step="0.01" required value="<?php echo $p['precio_unidad'] ?? 1900; ?>">
            <div class="info">Este precio se aplicará a cada empanada individual en el menú.</div>
            
            <label>Precio por Docena ($):</label>
            <input type="number" name="p_docena" step="0.01" required value="<?php echo $p['precio_docena'] ?? 20800; ?>">
            <div class="info">El sistema calculará el descuento basándose en este valor.</div>
            
            <button type="submit">ACTUALIZAR TODO EL SISTEMA</button>
        </form>
        
        <a href="../../index.php" class="footer-link">← Volver al Panel Principal</a>
    </div>
</body>
</html>