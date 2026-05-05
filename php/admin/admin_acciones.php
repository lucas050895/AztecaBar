<?php
/**
 * SISTEMA AZTECA - CONTROLADOR DE OPERACIONES ADMINISTRATIVAS
 * Gestión de persistencia de datos para el catálogo de productos.
 */

include("../auth/check_auth.php");
include("../../config/conexion.php");

/**
 * Procesamiento de peticiones mediante método POST
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $accion = $_POST['accion'] ?? '';

    /**
     * Gestión de registros: Creación y Actualización
     */
    if ($accion === 'guardar_producto') {
        
        $id        = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $nombre    = mysqli_real_escape_string($conn, $_POST['nombre']);
        $precio    = floatval($_POST['precio']);
        $stock     = intval($_POST['stock']);
        $es_cocina = intval($_POST['es_cocina']);
        $subcat_id = intval($_POST['subcategoria_id']);

        if ($id > 0) {
            /** Actualización de registro existente */
            $stmt = $conn->prepare("UPDATE productos SET nombre=?, precio=?, stock=?, es_cocina=?, subcategoria_id=? WHERE id=?");
            $stmt->bind_param("sdiiii", $nombre, $precio, $stock, $es_cocina, $subcat_id, $id);
        } else {
            /** Inserción de nuevo registro */
            $stmt = $conn->prepare("INSERT INTO productos (nombre, precio, stock, es_cocina, subcategoria_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sdiii", $nombre, $precio, $stock, $es_cocina, $subcat_id);
        }

        /** Redirección basada en el resultado de la transacción */
        if ($stmt->execute()) {
            header("Location: admin_productos.php?res=success");
        } else {
            header("Location: admin_productos.php?res=error");
        }
        exit;
    }

    /**
     * Eliminación de registros con respuesta para peticiones asíncronas
     */
    if ($accion === 'eliminar_producto') {
        
        $id = intval($_POST['id']);
        
        $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo "ok";
        }
        exit;
    }



    if ($accion === 'actualizar_config_promo') {
        foreach ($_POST as $clave => $valor) {
            if ($clave !== 'accion') {
                $stmt = $conn->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
                $stmt->bind_param("ss", $valor, $clave);
                $stmt->execute();
            }
        }
        header("Location: admin_productos.php?res=config_ok");
        exit;
    }
}