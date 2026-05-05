<?php
/**
 * Sistema de Gestión de Pedidos - Confirmación de Impresión y Despacho a Cocina
 * Este script actualiza el estado de los ítems del pedido tras su envío a impresión
 * y sincroniza el flujo de trabajo para servicios de delivery.
 */

include("../../config/conexion.php");

/**
 * 1. Validación de Parámetros de Entrada
 */
$mesa_id = isset($_POST['mesa_id']) ? intval($_POST['mesa_id']) : 0;
$tipo    = isset($_POST['tipo']) ? $_POST['tipo'] : '';

if ($mesa_id > 0 && !empty($tipo)) {

    /**
     * 2. Actualización de Estado de Ítems
     * Marca como 'impresos' todos los registros pendientes dentro del detalle del pedido.
     */
    $sql = "UPDATE pedido_detalle pd
            JOIN pedidos p ON pd.pedido_id = p.id
            SET pd.impreso = 1
            WHERE p.id = $mesa_id 
            AND pd.impreso = 0";

    if ($conn->query($sql)) {
        
        /**
         * 3. Gestión de Flujo Operativo (Delivery)
         * Si el origen es delivery, el pedido transiciona a estado de preparación.
         */
        if ($tipo === 'delivery') {
            $sql_delivery = "UPDATE pedidos_delivery SET estado = 'Cocina' WHERE id_delivery = $mesa_id";
            $conn->query($sql_delivery);
        }
        
        echo "ok";
    } else {
        // Fallo en la ejecución de la sentencia SQL
        echo "error";
    }
} else {
    // Error por parámetros nulos o fuera de rango
    echo "datos_incompletos";
}

/**
 * 4. Cierre de Conexión
 */
$conn->close();
?>