<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include "../includes/header.php" ?>
</head>

<body>
    <div class="app-container">
        <nav class="sidebar">
            <?php include "../includes/sidebar.php" ?>
        </nav>

        <main class="main-content">
            <header class="top-bar">
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span>Bienvenido, Administrador</span>
                </div>
            </header>

            <div class="dashboard-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">🛏️</div>
                        <div class="stat-info">
                            <h3>Habitaciones Ocupadas</h3>
                            <p class="stat-number">24/30</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📅</div>
                        <div class="stat-info">
                            <h3>Reservas Hoy</h3>
                            <p class="stat-number">8</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-info">
                            <h3>Huéspedes Activos</h3>
                            <p class="stat-number">45</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-info">
                            <h3>Ingresos Hoy</h3>
                            <p class="stat-number">$2,450</p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-sections">
                    <div class="section">
                        <h2>Actividad Reciente</h2>
                        <div class="activity-list">
                            <div class="activity-item">
                                <span class="activity-time">10:30 AM</span>
                                <span class="activity-text">Check-in: Habitación 205 - Juan Pérez</span>
                            </div>
                            <div class="activity-item">
                                <span class="activity-time">09:15 AM</span>
                                <span class="activity-text">Nueva reserva: Habitación 301 - María García</span>
                            </div>
                            <div class="activity-item">
                                <span class="activity-time">08:45 AM</span>
                                <span class="activity-text">Check-out: Habitación 102 - Carlos López</span>
                            </div>
                        </div>
                    </div>

                    <div class="section">
                        <h2>Estado de Habitaciones</h2>
                        <div class="room-status">
                            <div class="status-item">
                                <span class="status-color occupied"></span>
                                <span>Ocupadas: 24</span>
                            </div>
                            <div class="status-item">
                                <span class="status-color available"></span>
                                <span>Disponibles: 6</span>
                            </div>
                            <div class="status-item">
                                <span class="status-color maintenance"></span>
                                <span>Mantenimiento: 2</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>