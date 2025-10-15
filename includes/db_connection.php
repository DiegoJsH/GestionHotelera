<?php
    $host = "localhost";
    $user = "root";
    $password = "";
    $database = "sistemahoteleria";

    $conn = new mysqli($host, $user, $password, $database);

    // Verificar conexión
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
}
?>