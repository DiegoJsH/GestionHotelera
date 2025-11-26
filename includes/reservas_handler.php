<?php
session_start();
include 'db_connection.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// NUEVA FUNCIÓN: Actualizar estados de reservas finalizadas
function actualizarReservasFinalizadas($conn) {
    $today = date('Y-m-d');
    
    $sql = "UPDATE reserva 
            SET estado = 'finalizada' 
            WHERE estado = 'confirmada' 
            AND fecha_salida < ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    
    return $stmt->affected_rows;
}

// Obtener la acción solicitada
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// Función para obtener todas las reservas con información de huéspedes
function obtenerReservas($conn, $filtros = []) {
    // Primero actualizar reservas finalizadas
    actualizarReservasFinalizadas($conn);
    
    $sql = "SELECT r.*, h.nombre, h.apellido, h.email, h.telefono, hab.tipo, hab.precio_por_noche 
            FROM reserva r 
            INNER JOIN huespedes h ON r.id_huesped = h.id_huesped 
            INNER JOIN habitacion hab ON r.numero_habitacion = hab.numero_habitacion 
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    // Aplicar filtros
    if (!empty($filtros['fecha_entrada'])) {
        $sql .= " AND r.fecha_entrada >= ?";
        $params[] = $filtros['fecha_entrada'];
        $types .= "s";
    }
    
    if (!empty($filtros['fecha_salida'])) {
        $sql .= " AND r.fecha_salida <= ?";
        $params[] = $filtros['fecha_salida'];
        $types .= "s";
    }
    
    if (!empty($filtros['estado']) && $filtros['estado'] != 'todas') {
        $sql .= " AND r.estado = ?";
        $params[] = $filtros['estado'];
        $types .= "s";
    }
    
    if (!empty($filtros['busqueda'])) {
        $sql .= " AND (h.nombre LIKE ? OR h.apellido LIKE ? OR r.numero_habitacion LIKE ?)";
        $searchTerm = "%{$filtros['busqueda']}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
    }
    
    $sql .= " ORDER BY r.fecha_hora_reserva DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reservas = [];
    while ($row = $result->fetch_assoc()) {
        // Calcular el total
        $fecha1 = new DateTime($row['fecha_entrada']);
        $fecha2 = new DateTime($row['fecha_salida']);
        $dias = $fecha1->diff($fecha2)->days;
        $row['dias'] = $dias;
        $row['total'] = $dias * $row['precio_por_noche'];
        
        $reservas[] = $row;
    }
    
    return $reservas;
}

// Función para agregar una reserva
function agregarReserva($conn, $datos) {
    // Verificar que el huésped existe
    $sql = "SELECT COUNT(*) as count FROM huespedes WHERE id_huesped = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $datos['id_huesped']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        return ['success' => false, 'message' => 'El huésped seleccionado no existe'];
    }
    
    // Verificar que la habitación existe
    $sql = "SELECT COUNT(*) as count FROM habitacion WHERE numero_habitacion = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $datos['numero_habitacion']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        return ['success' => false, 'message' => 'La habitación seleccionada no existe'];
    }
    
    // Verificar disponibilidad de la habitación
    $sql = "SELECT COUNT(*) as count FROM reserva 
            WHERE numero_habitacion = ? 
            AND estado NOT IN ('cancelada', 'finalizada')
            AND (
                (fecha_entrada <= ? AND fecha_salida >= ?) OR
                (fecha_entrada <= ? AND fecha_salida >= ?) OR
                (fecha_entrada >= ? AND fecha_salida <= ?)
            )";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssss", 
        $datos['numero_habitacion'],
        $datos['fecha_entrada'], $datos['fecha_entrada'],
        $datos['fecha_salida'], $datos['fecha_salida'],
        $datos['fecha_entrada'], $datos['fecha_salida']
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        return ['success' => false, 'message' => 'La habitación no está disponible en las fechas seleccionadas'];
    }
    
    // Insertar la reserva
    $sql = "INSERT INTO reserva (id_huesped, numero_habitacion, fecha_entrada, fecha_salida, observaciones, estado) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $estado = isset($datos['estado']) ? $datos['estado'] : 'confirmada';
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissss", 
        $datos['id_huesped'],
        $datos['numero_habitacion'],
        $datos['fecha_entrada'],
        $datos['fecha_salida'],
        $datos['observaciones'],
        $estado
    );
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Reserva creada exitosamente', 'id' => $conn->insert_id];
    } else {
        return ['success' => false, 'message' => 'Error al crear la reserva: ' . $stmt->error];
    }
}

// Función para actualizar una reserva
function actualizarReserva($conn, $id, $datos) {
    // Verificar disponibilidad de la habitación (excluyendo la reserva actual)
    $sql = "SELECT COUNT(*) as count FROM reserva 
            WHERE numero_habitacion = ? 
            AND id_reserva != ?
            AND estado NOT IN ('cancelada', 'finalizada')
            AND (
                (fecha_entrada <= ? AND fecha_salida >= ?) OR
                (fecha_entrada <= ? AND fecha_salida >= ?) OR
                (fecha_entrada >= ? AND fecha_salida <= ?)
            )";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissssss", 
        $datos['numero_habitacion'], $id,
        $datos['fecha_entrada'], $datos['fecha_entrada'],
        $datos['fecha_salida'], $datos['fecha_salida'],
        $datos['fecha_entrada'], $datos['fecha_salida']
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        return ['success' => false, 'message' => 'La habitación no está disponible en las fechas seleccionadas'];
    }
    
    $sql = "UPDATE reserva 
            SET id_huesped = ?, numero_habitacion = ?, fecha_entrada = ?, 
                fecha_salida = ?, observaciones = ?, estado = ?
            WHERE id_reserva = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissssi", 
        $datos['id_huesped'],
        $datos['numero_habitacion'],
        $datos['fecha_entrada'],
        $datos['fecha_salida'],
        $datos['observaciones'],
        $datos['estado'],
        $id
    );
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Reserva actualizada exitosamente'];
    } else {
        return ['success' => false, 'message' => 'Error al actualizar la reserva: ' . $stmt->error];
    }
}

// Función para cambiar el estado de una reserva
function cambiarEstadoReserva($conn, $id, $nuevoEstado) {
    $sql = "UPDATE reserva SET estado = ? WHERE id_reserva = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nuevoEstado, $id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Estado actualizado exitosamente'];
    } else {
        return ['success' => false, 'message' => 'Error al actualizar estado: ' . $stmt->error];
    }
}

// Función para obtener una reserva por ID
function obtenerReservaPorId($conn, $id) {
    $sql = "SELECT r.*, h.nombre, h.apellido, h.email, h.telefono, hab.tipo, hab.precio_por_noche 
            FROM reserva r 
            INNER JOIN huespedes h ON r.id_huesped = h.id_huesped 
            INNER JOIN habitacion hab ON r.numero_habitacion = hab.numero_habitacion 
            WHERE r.id_reserva = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Calcular el total
        $fecha1 = new DateTime($row['fecha_entrada']);
        $fecha2 = new DateTime($row['fecha_salida']);
        $dias = $fecha1->diff($fecha2)->days;
        $row['dias'] = $dias;
        $row['total'] = $dias * $row['precio_por_noche'];
        
        return ['success' => true, 'data' => $row];
    } else {
        return ['success' => false, 'message' => 'Reserva no encontrada'];
    }
}

// Función para obtener huéspedes disponibles
function obtenerHuespedes($conn) {
    $sql = "SELECT id_huesped, nombre, apellido FROM huespedes ORDER BY nombre, apellido";
    $result = $conn->query($sql);
    
    $huespedes = [];
    while ($row = $result->fetch_assoc()) {
        $huespedes[] = $row;
    }
    
    return $huespedes;
}

// Función mejorada para obtener habitaciones disponibles (excluyendo mantenimiento, canceladas y finalizadas)
function obtenerHabitacionesDisponibles($conn, $fecha_entrada, $fecha_salida, $id_reserva = null) {
    $sql = "SELECT DISTINCT h.numero_habitacion, h.tipo, h.precio_por_noche, h.capacidad 
            FROM habitacion h 
            WHERE 1=1";
    
    // Excluir habitaciones en mantenimiento si existe la columna estado
    $checkColumn = $conn->query("SHOW COLUMNS FROM habitacion LIKE 'estado'");
    if ($checkColumn && $checkColumn->num_rows > 0) {
        $sql .= " AND (h.estado IS NULL OR h.estado NOT LIKE '%manten%')";
    }
    
    // Excluir habitaciones con reservas activas en el rango de fechas
    $sql .= " AND h.numero_habitacion NOT IN (
                SELECT r.numero_habitacion 
                FROM reserva r 
                WHERE r.estado NOT IN ('cancelada', 'finalizada')";
    
    if ($id_reserva !== null) {
        $sql .= " AND r.id_reserva != ?";
    }
    
    $sql .= " AND (
                    (r.fecha_entrada <= ? AND r.fecha_salida >= ?) OR
                    (r.fecha_entrada <= ? AND r.fecha_salida >= ?) OR
                    (r.fecha_entrada >= ? AND r.fecha_salida <= ?)
                )
            ) ORDER BY h.numero_habitacion";
    
    $stmt = $conn->prepare($sql);
    
    if ($id_reserva !== null) {
        $stmt->bind_param("issssss", $id_reserva, $fecha_entrada, $fecha_entrada, $fecha_salida, $fecha_salida, $fecha_entrada, $fecha_salida);
    } else {
        $stmt->bind_param("ssssss", $fecha_entrada, $fecha_entrada, $fecha_salida, $fecha_salida, $fecha_entrada, $fecha_salida);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $habitaciones = [];
    while ($row = $result->fetch_assoc()) {
        $habitaciones[] = $row;
    }
    
    return $habitaciones;
}

// Procesar la acción solicitada
header('Content-Type: application/json');

switch ($action) {
    case 'listar':
        $filtros = [
            'fecha_entrada' => isset($_GET['fecha_entrada']) ? $_GET['fecha_entrada'] : '',
            'fecha_salida' => isset($_GET['fecha_salida']) ? $_GET['fecha_salida'] : '',
            'estado' => isset($_GET['estado']) ? $_GET['estado'] : '',
            'busqueda' => isset($_GET['busqueda']) ? $_GET['busqueda'] : ''
        ];
        $reservas = obtenerReservas($conn, $filtros);
        echo json_encode(['success' => true, 'data' => $reservas]);
        break;
        
    case 'agregar':
        $datos = [
            'id_huesped' => $_POST['id_huesped'],
            'numero_habitacion' => $_POST['numero_habitacion'],
            'fecha_entrada' => $_POST['fecha_entrada'],
            'fecha_salida' => $_POST['fecha_salida'],
            'observaciones' => $_POST['observaciones'],
            'estado' => isset($_POST['estado']) ? $_POST['estado'] : 'confirmada'
        ];
        $resultado = agregarReserva($conn, $datos);
        echo json_encode($resultado);
        break;
        
    case 'actualizar':
        $id = $_POST['id_reserva'];
        $datos = [
            'id_huesped' => $_POST['id_huesped'],
            'numero_habitacion' => $_POST['numero_habitacion'],
            'fecha_entrada' => $_POST['fecha_entrada'],
            'fecha_salida' => $_POST['fecha_salida'],
            'observaciones' => $_POST['observaciones'],
            'estado' => $_POST['estado']
        ];
        $resultado = actualizarReserva($conn, $id, $datos);
        echo json_encode($resultado);
        break;
        
    case 'cambiar_estado':
        $id = $_POST['id_reserva'];
        $nuevoEstado = $_POST['estado'];
        $resultado = cambiarEstadoReserva($conn, $id, $nuevoEstado);
        echo json_encode($resultado);
        break;
        
    case 'obtener':
        $id = $_GET['id'];
        $resultado = obtenerReservaPorId($conn, $id);
        echo json_encode($resultado);
        break;
        
    case 'obtener_huespedes':
        $huespedes = obtenerHuespedes($conn);
        echo json_encode(['success' => true, 'data' => $huespedes]);
        break;
        
    case 'obtener_habitaciones_disponibles':
        $fecha_entrada = $_GET['fecha_entrada'];
        $fecha_salida = $_GET['fecha_salida'];
        $id_reserva = isset($_GET['id_reserva']) ? $_GET['id_reserva'] : null;
        $habitaciones = obtenerHabitacionesDisponibles($conn, $fecha_entrada, $fecha_salida, $id_reserva);
        echo json_encode(['success' => true, 'data' => $habitaciones]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

$conn->close();
?>