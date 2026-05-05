<?php
/**
 * SISTEMA AZTECA - CONTROLADOR DE ACCIONES
 * Maneja el procesamiento de datos para productos y mesas.
 */
include("../../config/conexion.php");

/**
 * PROCESO DE ELIMINACIÓN GENÉRICA
 */
if (isset($_GET['eliminar'])) {
    $tabla = mysqli_real_escape_string($conn, $_GET['tabla']);
    $id = intval($_GET['id']);
    
    // Seguridad: lista de tablas permitidas
    $permitidas = ['productos', 'mesas', 'barriles', 'categorias_secciones', 'subcategorias'];
    
    if (in_array($tabla, $permitidas)) {
        // Validación específica para mesas
        if ($tabla === 'mesas') {
            // Impedir eliminar la mesa 0 (ID 99) por URL
            if ($id == 99) {
                header("Location: admin_mesas.php?error=protegido");
                exit();
            }

            // Impedir eliminar mesas ocupadas
            $check = $conn->query("SELECT estado FROM mesas WHERE id = $id")->fetch_assoc();
            if ($check['estado'] !== 'libre') {
                header("Location: admin_mesas.php?error=ocupada");
                exit();
            }
        }

        $conn->query("DELETE FROM $tabla WHERE id = $id");
    }
    
    // Redirección inteligente
    $redir = ($tabla === 'mesas') ? 'admin_mesas.php' : '../pages/dashboard.php';
    header("Location: $redir");
    exit();
}

/**
 * GUARDAR O EDITAR MESA
 */
if (isset($_POST['accion']) && $_POST['accion'] == 'guardar_mesa') {
    $id = !empty($_POST['id']) ? intval($_POST['id']) : null;
    $numero = intval($_POST['numero_mesa']);

    // Seguridad: No permitir crear o editar a número 0
    if ($numero <= 0) {
        header("Location: admin_mesas.php?error=numero_invalido");
        exit();
    }

    if ($id) {
        // No permitir editar la mesa 99 si alguien lo intenta via POST
        if ($id == 99) { header("Location: admin_mesas.php"); exit(); }
        $sql = "UPDATE mesas SET numero_mesa = $numero WHERE id = $id";
    } else {
        // Insertar nueva mesa siempre como 'libre'
        $sql = "INSERT INTO mesas (numero_mesa, estado) VALUES ($numero, 'libre')";
    }

    $conn->query($sql);
    header("Location: admin_mesas.php");
    exit();
}

/**
 * GUARDAR O EDITAR PRODUCTO
 */
if (isset($_POST['accion']) && $_POST['accion'] == 'guardar_producto') {
    $id = !empty($_POST['id']) ? intval($_POST['id']) : null;
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $precio = floatval($_POST['precio']);
    $sub_id = intval($_POST['subcategoria_id']);
    $es_cocina = intval($_POST['es_cocina']);

    if ($id) {
        $sql = "UPDATE productos SET 
                nombre = '$nombre', 
                precio = $precio, 
                subcategoria_id = $sub_id, 
                es_cocina = $es_cocina 
                WHERE id = $id";
    } else {
        $res_sub = $conn->query("SELECT nombre FROM subcategorias WHERE id = $sub_id");
        $nom_cat = $res_sub->fetch_assoc()['nombre'];
        
        $sql = "INSERT INTO productos (nombre, precio, stock, categoria, subcategoria_id, es_cocina) 
                VALUES ('$nombre', $precio, 0, '$nom_cat', $sub_id, $es_cocina)";
    }

    $conn->query($sql);
    header("Location: ../pages/dashboard.php");
    exit();

    if (isset($_POST['accion']) && $_POST['accion'] == 'guardar_barril') {
    $id = !empty($_POST['id']) ? intval($_POST['id']) : null;
    $numero = mysqli_real_escape_string($conn, $_POST['numero_barril']);

    if ($id) {
        $sql = "UPDATE barriles SET numero_barril = '$numero' WHERE id = $id";
    } else {
        $sql = "INSERT INTO barriles (numero_barril, estado) VALUES ('$numero', 'libre')";
    }

    $conn->query($sql);
    header("Location: admin_barriles.php"); // Cambia esto al nombre de tu archivo
    exit();
}
}
?>