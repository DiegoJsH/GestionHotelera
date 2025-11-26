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

// Si se proporcionan fechas personalizadas en la solicitud, úsalas
if (isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])) {
    // Validar formato básico YYYY-MM-DD
    $fi = $_GET['fecha_inicio'];
    $ff = $_GET['fecha_fin'];
    // Intentar crear objetos DateTime para validar
    try {
        $objFi = new DateTime($fi);
        $objFf = new DateTime($ff);
        // Asegurar que inicio <= fin
        if ($objFi > $objFf) {
            // intercambiar si están al revés
            $tmp = $objFi;
            $objFi = $objFf;
            $objFf = $tmp;
        }
        $fecha_inicio_str = $objFi->format('Y-m-d');
        $fecha_actual_str = $objFf->format('Y-m-d');
    } catch (Exception $e) {
        // Si falla la validación, caer al comportamiento por periodo
        $fecha_inicio->modify(match($periodo) {
            'tres_meses' => '-3 months',
            'año' => '-1 year',
            default => '-1 month'
        });
        $fecha_inicio_str = $fecha_inicio->format('Y-m-d');
        $fecha_actual_str = $fecha_actual->format('Y-m-d');
    }
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

// NUEVA FUNCIÓN: Obtener ingresos por día
function obtenerIngresosPorDia($conn, $fecha_inicio, $fecha_actual) {
    $sql = "SELECT 
                DATE(r.fecha_entrada) as fecha,
                COUNT(r.id_reserva) as num_reservas,
                SUM(DATEDIFF(r.fecha_salida, r.fecha_entrada) * h.precio_por_noche) as total_ingresos
            FROM reserva r
            INNER JOIN habitacion h ON r.numero_habitacion = h.numero_habitacion
            WHERE (r.fecha_entrada BETWEEN ? AND ? OR r.fecha_entrada >= ?)
            AND r.estado != 'cancelada'
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

// Obtener reservas por tipo de habitación según período para el gráfico
function obtenerReservasPorTipoHabitacion($conn, $periodo_grafico) {
    $where = match($periodo_grafico) {
        'diario' => "DATE(r.fecha_entrada) = CURDATE()",
        'semanal' => "YEARWEEK(r.fecha_entrada, 1) = YEARWEEK(CURDATE(), 1)",
        'anual' => "YEAR(r.fecha_entrada) = YEAR(CURDATE())",
        default => "YEAR(r.fecha_entrada) = YEAR(CURDATE()) AND MONTH(r.fecha_entrada) = MONTH(CURDATE())"
    };
    
    $sql = "SELECT h.tipo, COUNT(*) as total
            FROM reserva r
            INNER JOIN habitacion h ON r.numero_habitacion = h.numero_habitacion
            WHERE $where AND r.estado != 'cancelada'
            GROUP BY h.tipo
            ORDER BY total DESC";
    
    $result = $conn->query($sql);
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
    $datos = obtenerReservasPorTipoHabitacion($conn, $_GET['periodo_grafico'] ?? 'mensual');
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