<?php
/**
 * Sistema de Gestión de Pedidos - Módulo de Transacciones
 * Este script gestiona la creación, actualización y cálculo de montos
 * para pedidos de salón (mesas/barriles) y servicios de delivery.
 */

include("../../config/conexion.php");

/**
 * 1. Inicialización y Saneamiento de Datos
 * Se capturan los parámetros POST y se aplican filtros de tipo para asegurar la integridad.
 */
$mesa_id     = isset($_POST['mesa_id']) ? intval($_POST['mesa_id']) : 0;
$tipo        = isset($_POST['tipo']) ? $_POST['tipo'] : 'mesa'; 
$producto_id = isset($_POST['producto_id']) ? intval($_POST['producto_id']) : 0;
$precio      = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;
$accion      = isset($_POST['accion']) ? $_POST['accion'] : ''; 
$pedido_id   = isset($_POST['pedido_id']) ? intval($_POST['pedido_id']) : 0;
$direccion   = isset($_POST['direccion']) ? $_POST['direccion'] : '';

/**
 * 2. Clasificación del Origen del Pedido
 * Lógica de detección para integraciones externas (WhatsApp / PedidosYa).
 */
$origen_post = isset($_POST['origen']) ? $_POST['origen'] : 'WhatsApp';
if (stripos($origen_post, 'pedidos') !== false || stripos($origen_post, 'pya') !== false) {
    $origen = 'PedidosYa';
} else {
    $origen = 'WhatsApp';
}

/**
 * 3. Gestión de Pedidos de Delivery
 * Inicialización de registros en tablas 'pedidos' y 'pedidos_delivery'.
 */
if ($accion == 'crear_vacio' && $tipo == 'delivery') {
    $mesa_virtual = 99; 
    $sql_p = "INSERT INTO pedidos (mesa_id, tipo_entidad, estado, fecha, monto_total) 
              VALUES ($mesa_virtual, 'delivery', 'abierto', NOW(), 0)";
    
    if ($conn->query($sql_p)) {
        $nuevo_id = $conn->insert_id;
        $sql_d = "INSERT INTO pedidos_delivery (id_delivery, origen, estado, monto_total, direccion) 
                  VALUES ($nuevo_id, '$origen', 'Pendiente', 0, '$direccion')";
        
        if ($conn->query($sql_d)) {
            echo $nuevo_id;
        } else {
            echo "Error en pedidos_delivery: " . $conn->error;
        }
    } else {
        echo "Error en pedidos: " . $conn->error;
    }
    exit;
}

/**
 * 4. Recuperación de Pedidos Activos
 * Localiza un ID de pedido existente para entidades de salón con estado abierto.
 */
if ($pedido_id == 0 && $mesa_id > 0 && $tipo != 'delivery') {
    $res = $conn->query("SELECT id FROM pedidos WHERE mesa_id=$mesa_id AND tipo_entidad='$tipo' AND estado='abierto' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $pedido_id = $res->fetch_assoc()['id'];
    }
}

/**
 * 5. Registro de Apertura de Pedido
 * Crea el registro inicial y actualiza el estado de la entidad (mesa/barril) a ocupado.
 */
if ($pedido_id == 0 && $accion == 'add' && $mesa_id > 0) {
    $conn->query("INSERT INTO pedidos (mesa_id, tipo_entidad, estado, fecha, monto_total) VALUES ($mesa_id, '$tipo', 'abierto', NOW(), 0)");
    $pedido_id = $conn->insert_id;
    
    $tabla_ent = ($tipo == 'mesa') ? 'mesas' : 'barriles';
    $conn->query("UPDATE $tabla_ent SET estado = 'ocupada' WHERE id = $mesa_id");
}

/**
 * 6. Procesamiento de Ítems y Recálculo de Totales
 * Gestiona la inserción/eliminación de productos y aplica reglas de negocio para descuentos.
 */
if ($pedido_id > 0) {
    // Persistencia de datos de entrega si la entidad es delivery
    if (!empty($direccion)) {
        $stmt = $conn->prepare("UPDATE pedidos_delivery SET direccion = ? WHERE id_delivery = ?");
        $stmt->bind_param("si", $direccion, $pedido_id);
        $stmt->execute();
    }

    if ($accion == 'add' && $producto_id > 0) {
        $conn->query("INSERT INTO pedido_detalle (pedido_id, producto_id, precio_unitario, impreso) VALUES ($pedido_id, $producto_id, $precio, 0)");
    } elseif ($accion == 'remove' && $producto_id > 0) {
        $conn->query("DELETE FROM pedido_detalle WHERE pedido_id=$pedido_id AND producto_id=$producto_id AND impreso=0 LIMIT 1");
    }

    // Cálculo de base imponible y cantidad de ítems
    $res_s = $conn->query("SELECT SUM(precio_unitario) as total, COUNT(*) as cantidad FROM pedido_detalle WHERE pedido_id = $pedido_id");
    $row_s = $res_s->fetch_assoc();
    $total_base = $row_s['total'] ?? 0;
    $cantidad_restante = $row_s['cantidad'] ?? 0;

    // Aplicación de bonificación por volumen (Promoción Empanadas)
    $res_emp = $conn->query("SELECT COUNT(*) as cant FROM pedido_detalle pd 
                            JOIN productos p ON pd.producto_id = p.id 
                            JOIN subcategorias s ON p.subcategoria_id = s.id 
                            WHERE pd.pedido_id = $pedido_id AND s.nombre LIKE '%empanada%'");
    $cant_e = $res_emp->fetch_assoc()['cant'] ?? 0;
    $descuento = (floor($cant_e / 12) * 2000); 
    $total_final = $total_base - $descuento;

    // Sincronización de montos en registros contables
    $conn->query("UPDATE pedidos SET monto_total = $total_final WHERE id = $pedido_id");
    
    $check_del = $conn->query("SELECT id_delivery FROM pedidos_delivery WHERE id_delivery = $pedido_id");
    if ($check_del->num_rows > 0) {
        $conn->query("UPDATE pedidos_delivery SET monto_total = $total_final WHERE id_delivery = $pedido_id");
    }

    /**
     * 7. Respuesta del Servidor
     * Retorna el estado del pedido para la actualización de la interfaz de usuario.
     */
    if ($cantidad_restante == 0 && $accion == 'remove') {
        echo "vacio";
    } else {
        echo $pedido_id;
    }
}
?>