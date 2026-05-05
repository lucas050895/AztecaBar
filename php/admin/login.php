<?php
/**
 * SISTEMA AZTECA - PROCESADOR DE LOGIN (AJAX)
 */
session_start();
include("../../config/conexion.php");

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = mysqli_real_escape_string($conn, $_POST['usuario']);
    $pass = $_POST['password'];

    // Consulta segura
    $stmt = $conn->prepare("SELECT password FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Comparación directa (texto plano)
        if ($pass === $row['password']) {
            $_SESSION['admin_auth'] = true;
            $_SESSION['admin_user'] = $user;
            $response['success'] = true;
        } else {
            $response['message'] = "Contraseña incorrecta.";
        }
    } else {
        $response['message'] = "Usuario no encontrado.";
    }
}

// Devolver JSON y terminar ejecución
header('Content-Type: application/json');
echo json_encode($response);
exit;