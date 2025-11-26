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

// Calcular fechas según periodo o usar personalizadas
if (isset($_GET['fecha_inicio'], $_GET['fecha_fin'])) {
    $fecha_inicio_str = $_GET['fecha_inicio'];
    $fecha_actual_str = $_GET['fecha_fin'];
} else {
    $fecha_inicio->modify(match($periodo) {
        'tres_meses' => '-3 months',
        'año' => '-1 year',
        default => '-1 month'
    });
    $fecha_inicio_str = $fecha_inicio->format('Y-m-d');
    $fecha_actual_str = $fecha_actual->format('Y-m-d');
}

// Función helper para ejecutar consultas con periodo
function ejecutarConsultaPeriodo($conn, $sql, $fecha_inicio, $fecha_actual) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_actual, $fecha_inicio);
    $stmt->execute();
    return $stmt->get_result();
}

// Obtener ingresos por mes - ACTUALIZADO para usar fecha_entrada y fecha_salida
function obtenerIngresosPorMes($conn, $fecha_inicio, $fecha_actual) {
    $sql = "SELECT 
                DATE_FORMAT(r.fecha_entrada, '%M %Y') as mes_nombre,
                SUM(DATEDIFF(r.fecha_salida, r.fecha_entrada) * h.precio_por_noche) as total
            FROM reserva r
            INNER JOIN habitacion h ON r.numero_habitacion = h.numero_habitacion
            WHERE (r.fecha_entrada <= ? AND r.fecha_salida >= ?)
            AND r.estado NOT IN ('cancelada')
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

// Obtener ingresos por día - ACTUALIZADO para usar fecha_entrada y fecha_salida
function obtenerIngresosPorDia($conn, $fecha_inicio, $fecha_actual) {
    $sql = "SELECT 
                DATE(r.fecha_entrada) as fecha,
                COUNT(r.id_reserva) as num_reservas,
                SUM(DATEDIFF(r.fecha_salida, r.fecha_entrada) * h.precio_por_noche) as total_ingresos
            FROM reserva r
            INNER JOIN habitacion h ON r.numero_habitacion = h.numero_habitacion
            WHERE (r.fecha_entrada <= ? AND r.fecha_salida >= ?)
            AND r.estado NOT IN ('cancelada')
            GROUP BY DATE(r.fecha_entrada)
            ORDER BY DATE(r.fecha_entrada) DESC
            LIMIT 30";
    
    $result = ejecutarConsultaPeriodo($conn, $sql, $fecha_inicio, $fecha_actual);
    
    $ingresos_diarios = [];
    $total_periodo = 0;
    $total_reservas = 0;
    
    while ($row = $result->fetch_assoc()) {
        $ingresos = floatval($row['total_ingresos']);
        $reservas = intval($row['num_reservas']);
        
        $ingresos_diarios[] = [
            'fecha' => $row['fecha'],
            'fecha_formateada' => date('d/m/Y', strtotime($row['fecha'])),
            'dia_semana' => obtenerDiaSemana($row['fecha']),
            'num_reservas' => $reservas,
            'total_ingresos' => $ingresos
        ];
        
        $total_periodo += $ingresos;
        $total_reservas += $reservas;
    }
    
    $promedio_diario = count($ingresos_diarios) > 0 ? $total_periodo / count($ingresos_diarios) : 0;
    
    return [
        'ingresos' => $ingresos_diarios,
        'total_periodo' => $total_periodo,
        'total_reservas' => $total_reservas,
        'promedio_diario' => $promedio_diario,
        'dias_con_datos' => count($ingresos_diarios)
    ];
}

// Función auxiliar para obtener día de la semana en español
function obtenerDiaSemana($fecha) {
    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $fecha_obj = new DateTime($fecha);
    return $dias[$fecha_obj->format('w')];
}

// Obtener estadísticas generales con una sola query optimizada
function obtenerEstadisticasGenerales($conn, $fecha_inicio, $fecha_actual) {
    $sql = "SELECT 
                COUNT(*) as total_reservas,
                COUNT(DISTINCT id_huesped) as huespedes_unicos,
                AVG(CASE WHEN estado != 'cancelada' THEN DATEDIFF(fecha_salida, fecha_entrada) END) as promedio_estancia,
                SUM(estado = 'cancelada') as reservas_canceladas,
                (SELECT AVG(h.precio_por_noche) FROM reserva r2 
                 INNER JOIN habitacion h ON r2.numero_habitacion = h.numero_habitacion 
                 WHERE r2.fecha_entrada <= ? AND r2.fecha_salida >= ? AND r2.estado != 'cancelada') as ingreso_promedio_noche
            FROM reserva 
            WHERE fecha_entrada <= ? AND fecha_salida >= ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $fecha_actual, $fecha_inicio, $fecha_actual, $fecha_inicio);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    
    $total = intval($row['total_reservas']);
    $canceladas = intval($row['reservas_canceladas']);
    $tasa_cancelacion = $total > 0 ? ($canceladas / $total) * 100 : 0;
    
    return [
        'total_reservas' => $total,
        'huespedes_unicos' => intval($row['huespedes_unicos']),
        'promedio_estancia' => round($row['promedio_estancia'] ?? 0, 1),
        'tasa_cancelacion' => round($tasa_cancelacion, 1),
        'ingreso_promedio_noche' => round($row['ingreso_promedio_noche'] ?? 0, 2)
    ];
}

// Obtener habitaciones más reservadas - ACTUALIZADO para usar fecha_entrada y fecha_salida
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
            WHERE r.fecha_entrada <= ? AND r.fecha_salida >= ?
            AND r.estado NOT IN ('cancelada')
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

// Obtener reservas por tipo de habitación según período
function obtenerReservasPorTipoHabitacion($conn, $fecha_inicio, $fecha_actual) {
    $sql = "SELECT h.tipo, COUNT(*) as total
            FROM reserva r
            INNER JOIN habitacion h ON r.numero_habitacion = h.numero_habitacion
            WHERE r.fecha_entrada <= ? AND r.fecha_salida >= ?
            AND r.estado NOT IN ('cancelada')
            GROUP BY h.tipo
            ORDER BY total DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_actual, $fecha_inicio);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $labels = [];
    $valores = [];
    
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['tipo'];
        $valores[] = (int)$row['total'];
    }
    
    return ['labels' => $labels, 'valores' => $valores];
}

// Procesar solicitud
header('Content-Type: application/json');

// Si se solicita datos del gráfico
if (isset($_GET['accion']) && $_GET['accion'] === 'grafico') {
    $datos = obtenerReservasPorTipoHabitacion($conn, $fecha_inicio_str, $fecha_actual_str);
    echo json_encode(['success' => true, 'datos' => $datos]);
} else {
    // Respuesta normal de reportes
    try {
        echo json_encode([
            'success' => true,
            'ingresos' => obtenerIngresosPorMes($conn, $fecha_inicio_str, $fecha_actual_str),
            'ingresos_diarios' => obtenerIngresosPorDia($conn, $fecha_inicio_str, $fecha_actual_str),
            'estadisticas' => obtenerEstadisticasGenerales($conn, $fecha_inicio_str, $fecha_actual_str),
            'habitaciones_populares' => obtenerHabitacionesMasReservadas($conn, $fecha_inicio_str, $fecha_actual_str)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al generar reportes: ' . $e->getMessage()]);
    }
}

$conn->close();
?>