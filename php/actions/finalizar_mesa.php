<?php
/**
 * Sistema de Gestión de Pedidos - Cierre y Finalización
 * Este script procesa la transición de estados de los pedidos a 'finalizado'
 * y gestiona la liberación de recursos (mesas, barriles o despachos).
 */

include("../../config/conexion.php");

/**
 * 1. Recopilación de Parámetros
 */
$pedido_id = isset($_POST['pedido_id']) ? intval($_POST['pedido_id']) : 0;
$mesa_id   = isset($_POST['mesa_id']) ? intval($_POST['mesa_id']) : 0;
$tipo      = isset($_POST['tipo']) ? $_POST['tipo'] : '';

/**
 * 2. Validación de Consistencia de Datos
 * Procedimiento de recuperación de ID de pedido en caso de ausencia de identificador 
 * en la petición inicial para entidades con estado 'abierto'.
 */
if ($pedido_id === 0 && $mesa_id > 0) {
    $sql_rescatar = "SELECT id FROM pedidos WHERE mesa_id = $mesa_id AND tipo_entidad = '$tipo' AND estado = 'abierto' LIMIT 1";
    $res_rescatar = $conn->query($sql_rescatar);
    if ($res_rescatar && $row = $res_rescatar->fetch_assoc()) {
        $pedido_id = $row['id'];
    }
}

/**
 * 3. Procesamiento de Cierre
 */
if ($pedido_id > 0) {
    
    // Actualización de estado en el registro principal de pedidos
    $conn->query("UPDATE pedidos SET estado = 'finalizado' WHERE id = $pedido_id");

    /**
     * 4. Gestión de Recursos según Tipo de Entidad
     */
    if ($tipo === 'delivery') {
        // Actualización de estado en el módulo de logística de entregas
        $conn->query("UPDATE pedidos_delivery SET estado = 'Finalizado' WHERE id_delivery = $pedido_id");
    } else {
        // Liberación de disponibilidad para servicios de salón
        $tabla = ($tipo === 'mesa') ? 'mesas' : 'barriles';
        $conn->query("UPDATE $tabla SET estado = 'libre' WHERE id = $mesa_id");
    }
    
    echo "ok";
} else {
    // Retorno de excepción en caso de no localizar registros activos
    echo "error_no_se_encontro_pedido_abierto";
}
?>