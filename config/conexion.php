<?php
    // Conexión a la base de datos
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "azteca_db";

    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }


    date_default_timezone_set('America/Argentina/Buenos_Aires');