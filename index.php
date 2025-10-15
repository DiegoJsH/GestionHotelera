<?php
session_start();

// Verificar si el usuario ya está logueado
if (isset($_SESSION['admin_id'])) {
    // Redirigir al dashboard si está logueado
    header("Location: templates/dashboard.php");
    exit;
} else {
    // Redirigir al login si no está logueado
    header("Location: login.php");
    exit;
}
?>