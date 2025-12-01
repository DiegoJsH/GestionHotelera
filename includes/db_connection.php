<?php
// Configuración para InfinityFree (cambiar con tus credenciales)
// Localhost para desarrollo local
if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
    $host = "localhost";
    $user = "root";
    $password = "";
    $database = "sistemahoteleria";
} else {
    // Configuración para InfinityFree - Datos verificados del panel
    $host = "sql312.infinityfree.com"; // ✓ Hostname correcto de tu panel
    $user = "if0_40527572";            // ✓ Usuario MySQL
    $password = "QqCwYnUEygxan";       // ✓ Contraseña MySQL
    $database = "if0_40527572_sistemahoteleriahost"; // ✓ Nombre de base de datos
}

$conn = new mysqli($host, $user, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Configurar charset UTF-8
$conn->set_charset("utf8mb4");
?>