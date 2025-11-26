<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

include "../includes/db_connection.php";

$today = date('Y-m-d');

// ACTUALIZADO: Contar habitaciones ocupadas HOY (solo confirmadas, no finalizadas)
$ocupadas = 0;
if ($stmt = $conn->prepare("SELECT COUNT(DISTINCT r.numero_habitacion) AS cnt 
                             FROM reserva r 
                             WHERE r.estado = 'confirmada' 
                             AND ? BETWEEN r.fecha_entrada AND r.fecha_salida")) {
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $ocupadas = intval($row['cnt'] ?? 0);
    $stmt->close();
}

// ACTUALIZADO: Reservas Hoy (solo confirmadas, excluyendo finalizadas y canceladas)
$reservas_hoy = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM reserva 
                             WHERE fecha_entrada <= ? AND fecha_salida >= ? 
                             AND estado = 'confirmada'")) {
    $stmt->bind_param('ss', $today, $today);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $reservas_hoy = intval($row['cnt'] ?? 0);
    $stmt->close();
}

// Huéspedes activos (total de huéspedes)
$huespedes_activos = 0;
if ($result = $conn->query("SELECT COUNT(*) AS cnt FROM huespedes")) {
    $row = $result->fetch_assoc();
    $huespedes_activos = intval($row['cnt'] ?? 0);
}

// Total de habitaciones
$total_habitaciones = null;
$habitaciones_exist = false;
if ($res = $conn->query("SHOW TABLES LIKE 'habitacion'")) {
    if ($res->num_rows > 0) {
        $habitaciones_exist = true;
        if ($r = $conn->query("SELECT COUNT(*) AS cnt FROM habitacion")) {
            $row = $r->fetch_assoc();
            $total_habitaciones = intval($row['cnt'] ?? 0);
        }
    }
}

// ACTUALIZADO: Ingresos hoy - solo de reservas confirmadas activas
$ingresos_hoy = 0;
if ($stmt = $conn->prepare("SELECT SUM(DATEDIFF(r.fecha_salida, r.fecha_entrada) * h.precio_por_noche) AS total 
                             FROM reserva r 
                             INNER JOIN habitacion h ON r.numero_habitacion = h.numero_habitacion 
                             WHERE r.estado = 'confirmada'
                             AND ? BETWEEN r.fecha_entrada AND r.fecha_salida")) {
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $ingresos_hoy = floatval($r['total'] ?? 0);
    $stmt->close();
}

// Recent activity: últimas 6 reservas
$actividad = [];
if ($stmt = $conn->prepare("SELECT r.*, h.nombre, h.apellido FROM reserva r 
                             LEFT JOIN huespedes h ON r.id_huesped = h.id_huesped 
                             ORDER BY r.fecha_hora_reserva DESC LIMIT 6")) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $actividad[] = $row;
    }
    $stmt->close();
}

// Estado de habitaciones
$ocupadas_detalle = $ocupadas;
$disponibles = null;
$en_mantenimiento = 0;
if ($habitaciones_exist) {
    if ($total_habitaciones !== null) {
        $disponibles = $total_habitaciones - $ocupadas_detalle;
        
        $colEstado = $conn->query("SHOW COLUMNS FROM habitacion LIKE 'estado'");
        if ($colEstado && $colEstado->num_rows > 0) {
            if ($r = $conn->query("SELECT COUNT(*) AS cnt FROM habitacion WHERE estado LIKE '%manten%' OR estado LIKE '%mant%'")) {
                $row = $r->fetch_assoc();
                $en_mantenimiento = intval($row['cnt'] ?? 0);
                $disponibles = $disponibles - $en_mantenimiento;
            }
        }
        
        if ($disponibles < 0) $disponibles = 0;
    }
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

            <div class="content-area">
                <div class="dashboard-content">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">🛏️</div>
                            <div class="stat-info">
                                <h3>Habitaciones Ocupadas</h3>
                                <p class="stat-number"><?php echo $ocupadas_detalle; ?><?php echo $total_habitaciones !== null ? "/{$total_habitaciones}" : ''; ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">📅</div>
                            <div class="stat-info">
                                <h3>Reservas Activas Hoy</h3>
                                <p class="stat-number"><?php echo $reservas_hoy; ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">👥</div>
                            <div class="stat-info">
                                <h3>Huéspedes Registrados</h3>
                                <p class="stat-number"><?php echo $huespedes_activos; ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">💰</div>
                            <div class="stat-info">
                                <h3>Ingresos Activos Hoy</h3>
                                <p class="stat-number">$<?php echo number_format($ingresos_hoy, 2, '.', ','); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-sections">
                        <div class="section">
                            <h2>Actividad Reciente</h2>
                            <div class="activity-list">
                                <?php if (empty($actividad)): ?>
                                    <div class="activity-item"><span>No hay actividad reciente.</span></div>
                                <?php else: ?>
                                    <?php foreach ($actividad as $a): ?>
                                        <div class="activity-item">
                                            <span class="activity-time"><?php echo date('d/m H:i', strtotime($a['fecha_hora_reserva'])); ?></span>
                                            <span class="activity-text">
                                                <span class="status <?php echo htmlspecialchars($a['estado']); ?>" style="font-size: 10px; padding: 2px 6px;">
                                                    <?php echo ucfirst(htmlspecialchars($a['estado'])); ?>
                                                </span>
                                                Hab. <?php echo htmlspecialchars($a['numero_habitacion']); ?> - 
                                                <?php echo htmlspecialchars(($a['nombre'] ?? '') . ' ' . ($a['apellido'] ?? '')); ?>
                                                (<?php echo htmlspecialchars($a['fecha_entrada']); ?> → <?php echo htmlspecialchars($a['fecha_salida']); ?>)
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="section">
                            <h2>Estado de Habitaciones</h2>
                            <div class="room-status">
                                <div class="status-item">
                                    <span class="status-color occupied"></span>
                                    <span>Ocupadas: <?php echo $ocupadas_detalle; ?></span>
                                </div>
                                <div class="status-item">
                                    <span class="status-color available"></span>
                                    <span>Disponibles: <?php echo $disponibles !== null ? $disponibles : 'N/D'; ?></span>
                                </div>
                                <div class="status-item">
                                    <span class="status-color maintenance"></span>
                                    <span>Mantenimiento: <?php echo $en_mantenimiento; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>