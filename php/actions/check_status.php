<?php
    /**
     * SISTEMA AZTECA - MONITOR DE ESTADOS EN TIEMPO REAL
     * Ubicación: php/actions/check_status.php
     * * Este script devuelve el estado actual de todas las mesas en formato JSON.
     * Es ideal para ser consultado mediante intervalos (polling) desde JavaScript
     * para actualizar los colores del mapa de mesas sin recargar el sitio.
     */

    // Usamos include_once para evitar errores de redifinición de variables de conexión
    include_once "../../config/conexion.php";

    /**
     * REGLA DE SEGURIDAD:
     * Forzamos que la respuesta sea interpretada por el navegador como JSON.
     */
    header('Content-Type: application/json');

    /**
     * CONSULTA DE ESTADOS
     * Seleccionamos ID y Estado. 
     * Nota: Asegúrate de que los nombres de las columnas coincidan con tu DB (id_mesa o id).
     */
    $sql = "SELECT id, estado FROM mesas ORDER BY id ASC"; 
    $resultado = $conn->query($sql); // Usamos $conn que es el estándar de tu proyecto

    $mesas = [];

    if ($resultado) {
        while($fila = $resultado->fetch_assoc()){
            $mesas[] = [
                'id'     => $fila['id'],
                'estado' => $fila['estado']
            ];
        }
    }

    /**
     * RETORNO DE DATOS
     * El frontend recibirá un array de objetos tipo: [{"id":1, "estado":"ocupada"}, ...]
     */
    echo json_encode($mesas);
?>