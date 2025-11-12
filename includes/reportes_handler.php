<?php
session_start();
include 'db_connection.php';

// Verificar autenticación
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Calcular fechas según periodo
$periodo = $_GET['periodo'] ?? 'mes';
$fecha_actual = new DateTime();
$fecha_inicio = clone $fecha_actual;

$fecha_inicio->modify(match($periodo) {
    'tres_meses' => '-3 months',
    'año' => '-1 year',
    default => '-1 month'
});

$fecha_inicio_str = $fecha_inicio->format('Y-m-d');
$fecha_actual_str = $fecha_actual->format('Y-m-d');

// Función helper para ejecutar consultas con periodo
function ejecutarConsultaPeriodo($conn, $sql, $fecha_inicio, $fecha_actual) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $fecha_inicio, $fecha_actual, $fecha_actual);
    $stmt->execute();
    return $stmt->get_result();
}

// Obtener ingresos por mes
function obtenerIngresosPorMes($conn, $fecha_inicio, $fecha_actual) {
    $sql = "SELECT 
                DATE_FORMAT(r.fecha_entrada, '%M %Y') as mes_nombre,
                SUM(DATEDIFF(r.fecha_salida, r.fecha_entrada) * h.precio_por_noche) as total
            FROM reserva r
            INNER JOIN habitacion h ON r.numero_habitacion = h.numero_habitacion
            WHERE (r.fecha_entrada BETWEEN ? AND ? OR r.fecha_entrada >= ?)
            AND r.estado != 'cancelada'
            GROUP BY DATE_FORMAT(r.fecha_entrada, '%Y-%m')
            ORDER BY DATE_FORMAT(r.fecha_entrada, '%Y-%m') ASC";
    
    $result = ejecutarConsultaPeriodo($conn, $sql, $fecha_inicio, $fecha_actual);
    
    $meses_es = ['January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo', 
                 'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio', 
                 'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre', 
                 'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'];
    
    $ingresos = [];
    $total_general = 0;
    
    while ($row = $result->fetch_assoc()) {
        $mes = str_replace(array_keys($meses_es), array_values($meses_es), $row['mes_nombre']);
        $total = floatval($row['total']);
        $ingresos[] = ['mes' => $mes, 'total' => $total];
        $total_general += $total;
    }
    
    return ['ingresos' => $ingresos, 'total_general' => $total_general];
}

// Obtener estadísticas generales
function obtenerEstadisticasGenerales($conn, $fecha_inicio, $fecha_actual) {
    $consultas = [
        'total_reservas' => "SELECT COUNT(*) as val FROM reserva 
                            WHERE (fecha_entrada BETWEEN ? AND ? OR fecha_entrada >= ?)",
        'huespedes_unicos' => "SELECT COUNT(DISTINCT id_huesped) as val FROM reserva 
                              WHERE (fecha_entrada BETWEEN ? AND ? OR fecha_entrada >= ?)",
        'promedio_estancia' => "SELECT AVG(DATEDIFF(fecha_salida, fecha_entrada)) as val 
                               FROM reserva 
                               WHERE (fecha_entrada BETWEEN ? AND ? OR fecha_entrada >= ?)
                               AND estado != 'cancelada'",
        'reservas_canceladas' => "SELECT COUNT(*) as val FROM reserva 
                                 WHERE (fecha_entrada BETWEEN ? AND ? OR fecha_entrada >= ?) 
                                 AND estado = 'cancelada'",
        'ingreso_promedio_noche' => "SELECT AVG(h.precio_por_noche) as val 
                                    FROM reserva r
                                    INNER JOIN habitacion h ON r.numero_habitacion = h.numero_habitacion
                                    WHERE (r.fecha_entrada BETWEEN ? AND ? OR r.fecha_entrada >= ?)
                                    AND r.estado != 'cancelada'"
    ];
    
    $stats = [];
    foreach ($consultas as $key => $sql) {
        $result = ejecutarConsultaPeriodo($conn, $sql, $fecha_inicio, $fecha_actual);
        $row = $result->fetch_assoc();
        $stats[$key] = $row['val'] ? floatval($row['val']) : 0;
    }
    
    $tasa_cancelacion = $stats['total_reservas'] > 0 
        ? ($stats['reservas_canceladas'] / $stats['total_reservas']) * 100 
        : 0;
    
    return [
        'total_reservas' => intval($stats['total_reservas']),
        'huespedes_unicos' => intval($stats['huespedes_unicos']),
        'promedio_estancia' => round($stats['promedio_estancia'], 1),
        'tasa_cancelacion' => round($tasa_cancelacion, 1),
        'ingreso_promedio_noche' => round($stats['ingreso_promedio_noche'], 2)
    ];
}

// Obtener habitaciones más reservadas
function obtenerHabitacionesMasReservadas($conn, $fecha_inicio, $fecha_actual) {
    $hasPiso = $conn->query("SHOW COLUMNS FROM habitacion LIKE 'piso'")->num_rows > 0;
    $pisoField = $hasPiso ? 'h.piso,' : "'1' as piso,";
    
    $sql = "SELECT 
                h.numero_habitacion,
                $pisoField
                h.tipo,
                COUNT(r.id_reserva) as total_reservas,
                h.precio_por_noche,
                SUM(DATEDIFF(r.fecha_salida, r.fecha_entrada) * h.precio_por_noche) as ingresos_generados
            FROM habitacion h
            INNER JOIN reserva r ON h.numero_habitacion = r.numero_habitacion
            WHERE (r.fecha_entrada BETWEEN ? AND ? OR r.fecha_entrada >= ?)
            AND r.estado != 'cancelada'
            GROUP BY h.numero_habitacion, h.tipo, h.precio_por_noche" . ($hasPiso ? ", h.piso" : "") . "
            ORDER BY total_reservas DESC
            LIMIT 10";
    
    $result = ejecutarConsultaPeriodo($conn, $sql, $fecha_inicio, $fecha_actual);
    
    $habitaciones = [];
    while ($row = $result->fetch_assoc()) {
        $habitaciones[] = [
            'numero_habitacion' => intval($row['numero_habitacion']),
            'piso' => $row['piso'],
            'tipo' => $row['tipo'],
            'total_reservas' => intval($row['total_reservas']),
            'precio_por_noche' => floatval($row['precio_por_noche']),
            'ingresos_generados' => floatval($row['ingresos_generados'])
        ];
    }
    
    return $habitaciones;
}

// Procesar solicitud
header('Content-Type: application/json');

try {
    echo json_encode([
        'success' => true,
        'ingresos' => obtenerIngresosPorMes($conn, $fecha_inicio_str, $fecha_actual_str),
        'estadisticas' => obtenerEstadisticasGenerales($conn, $fecha_inicio_str, $fecha_actual_str),
        'habitaciones_populares' => obtenerHabitacionesMasReservadas($conn, $fecha_inicio_str, $fecha_actual_str)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al generar reportes: ' . $e->getMessage()]);
}

$conn->close();
?>