    <?php
// Obtener el nombre del archivo actual
$current_page = basename($_SERVER['PHP_SELF']);

// Función para verificar si la página está activa
function isActive($page) {
    global $current_page;
    return ($current_page === $page) ? 'active' : '';
}
?>

<div class="sidebar-header">
        <h2>🏨 Sistema de Gestión Hotelera</h2>
    </div>
    <ul class="nav-menu">
        <li><a href="../templates/dashboard.php" class="nav-link <?php echo isActive('dashboard.php'); ?>">📊 Dashboard</a></li>
        <li><a href="../templates/habitaciones.php" class="nav-link <?php echo isActive('habitaciones.php'); ?>">🛏️ Habitaciones</a></li>
        <li><a href="../templates/reservas.php" class="nav-link <?php echo isActive('reservas.php'); ?>">📅 Reservas</a></li>
        <li><a href="../templates/huespedes.php" class="nav-link <?php echo isActive('huespedes.php'); ?>">👥 Huéspedes</a></li>
        <li><a href="../templates/reportes.php" class="nav-link <?php echo isActive('reportes.php'); ?>">📈 Reportes</a></li>
        <li><a href="../logout.php" class="nav-link logout">🚪 Cerrar Sesión</a></li>
    </ul>