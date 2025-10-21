<?php
session_start();
include 'db_connection.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener el periodo solicitado
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'mes';

// Calcular las fechas según el periodo
$fecha_actual = new DateTime();
$fecha_inicio = new DateTime();

switch ($periodo) {
    case 'mes':
        $fecha_inicio->modify('-1 month');
        break;
    case 'tres_meses':
        $fecha_inicio->modify('-3 months');
        break;
    case 'año':
        $fecha_inicio->modify('-1 year');
        break;
    default:
        $fecha_inicio->modify('-1 month');
}

$fecha_inicio_str = $fecha_inicio->format('Y-m-d');
$fecha_actual_str = $fecha_actual->format('Y-m-d');

// Función para obtener ingresos por mes
function obtenerIngresosPorMes($conn, $fecha_inicio, $fecha_actual) {
    $sql = "SELECT 
                DATE_FORMAT(r.fecha_entrada, '%Y-%m') as mes,
                DATE_FORMAT(r.fecha_entrada, '%M %Y') as mes_nombre,
                SUM(DATEDIFF(r.fecha_salida, r.fecha_entrada) * h.precio_por_noche) as total
            FROM reserva r
            INNER JOIN habitacion h ON r.numero_habitacion = h.numero_habitacion
            WHERE r.fecha_entrada BETWEEN ? AND ?
            AND r.estado != 'cancelada'
            GROUP BY DATE_FORMAT(r.fecha_entrada, '%Y-%m')
            ORDER BY mes ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_actual);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ingresos = [];
    $total_general = 0;
    
    // Array para traducir meses al español
    $meses_es = [
        'January' => 'Enero',
        'February' => 'Febrero',
        'March' => 'Marzo',
        'April' => 'Abril',
        'May' => 'Mayo',
        'June' => 'Junio',
        'July' => 'Julio',
        'August' => 'Agosto',
        'September' => 'Septiembre',
        'October' => 'Octubre',
        'November' => 'Noviembre',
        'December' => 'Diciembre'
    ];
    
    while ($row = $result->fetch_assoc()) {
        // Traducir el mes al español
        $mes_nombre = $row['mes_nombre'];
        foreach ($meses_es as $en => $es) {
            $mes_nombre = str_replace($en, $es, $mes_nombre);
        }
        
        $ingresos[] = [
            'mes' => $mes_nombre,
            'total' => floatval($row['total'])
        ];
        $total_general += floatval($row['total']);
    }
    
    return [
        'ingresos' => $ingresos,
        'total_general' => $total_general
    ];
}

// Función para obtener estadísticas generales
function obtenerEstadisticasGenerales($conn, $fecha_inicio, $fecha_actual) {
    // Total de reservas
    $sql = "SELECT COUNT(*) as count FROM reserva WHERE fecha_entrada BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_actual);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_reservas = intval($row['count']);
    
    // Huéspedes únicos
    $sql = "SELECT COUNT(DISTINCT id_huesped) as count FROM reserva WHERE fecha_entrada BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_actual);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $huespedes_unicos = intval($row['count']);
    
    // Promedio de estancia
    $sql = "SELECT AVG(DATEDIFF(fecha_salida, fecha_entrada)) as promedio 
            FROM reserva 
            WHERE fecha_entrada BETWEEN ? AND ?
            AND estado != 'cancelada'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_actual);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $promedio_estancia = $row['promedio'] ? floatval($row['promedio']) : 0;
    
    // Tasa de cancelación
    $sql = "SELECT COUNT(*) as count FROM reserva WHERE fecha_entrada BETWEEN ? AND ? AND estado = 'cancelada'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_actual);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $reservas_canceladas = intval($row['count']);
    
    $tasa_cancelacion = $total_reservas > 0 ? ($reservas_canceladas / $total_reservas) * 100 : 0;
    
    // Ingreso promedio por noche
    $sql = "SELECT AVG(h.precio_por_noche) as promedio 
            FROM reserva r
            INNER JOIN habitacion h ON r.numero_habitacion = h.numero_habitacion
            WHERE r.fecha_entrada BETWEEN ? AND ?
            AND r.estado != 'cancelada'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_actual);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $ingreso_promedio_noche = $row['promedio'] ? floatval($row['promedio']) : 0;
    
    return [
        'total_reservas' => $total_reservas,
        'huespedes_unicos' => $huespedes_unicos,
        'promedio_estancia' => round($promedio_estancia, 1),
        'tasa_cancelacion' => round($tasa_cancelacion, 1),
        'ingreso_promedio_noche' => round($ingreso_promedio_noche, 2)
    ];
}

// Procesar la solicitud
header('Content-Type: application/json');

try {
    $ingresos = obtenerIngresosPorMes($conn, $fecha_inicio_str, $fecha_actual_str);
    $estadisticas = obtenerEstadisticasGenerales($conn, $fecha_inicio_str, $fecha_actual_str);
    
    echo json_encode([
        'success' => true,
        'ingresos' => $ingresos,
        'estadisticas' => $estadisticas
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al generar reportes: ' . $e->getMessage()
    ]);
}

$conn->close();
?>