<?php
session_start();
include 'db_connection.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener la acción solicitada
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// Función para obtener todas las habitaciones
function obtenerHabitaciones($conn, $filtroEstado = '', $filtroTipo = '') {
    $today = date('Y-m-d');
    
    $sql = "SELECT h.*, 
            (SELECT COUNT(*) 
             FROM reserva r 
             WHERE r.numero_habitacion = h.numero_habitacion 
             AND r.estado = 'confirmada' 
             AND ? BETWEEN r.fecha_entrada AND r.fecha_salida
            ) as esta_ocupada
            FROM habitacion h WHERE 1=1";
    
    if (!empty($filtroTipo) && $filtroTipo != 'todos') {
        $sql .= " AND h.tipo = ?";
    }
    
    $sql .= " ORDER BY h.numero_habitacion ASC";
    $stmt = $conn->prepare($sql);
    
    if (!empty($filtroTipo) && $filtroTipo != 'todos') {
        $stmt->bind_param("ss", $today, $filtroTipo);
    } else {
        $stmt->bind_param("s", $today);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $habitaciones = [];
    while ($row = $result->fetch_assoc()) {
        // CORRECCIÓN: Determinar el estado real basado en las reservas
        if ($row['esta_ocupada'] > 0) {
            $row['estado'] = 'ocupada';
        } else {
            // Verificar si hay columna estado en la tabla
            $row['estado'] = isset($row['estado']) && !empty($row['estado']) ? $row['estado'] : 'disponible';
        }
        unset($row['esta_ocupada']); // Remover campo auxiliar
        $habitaciones[] = $row;
    }
    
    // Aplicar filtro de estado si existe
    if (!empty($filtroEstado) && $filtroEstado != 'todas') {
        $habitaciones = array_filter($habitaciones, function($h) use ($filtroEstado) {
            return $h['estado'] === $filtroEstado;
        });
        $habitaciones = array_values($habitaciones); // Reindexar array
    }
    
    return $habitaciones;
}

// Función para agregar una habitación
function agregarHabitacion($conn, $datos) {
    $sql = "INSERT INTO habitacion (numero_habitacion, tipo, precio_por_noche, capacidad) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdi", 
        $datos['numero_habitacion'],
        $datos['tipo'],
        $datos['precio_por_noche'],
        $datos['capacidad']
    );
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Habitación agregada exitosamente'];
    } else {
        return ['success' => false, 'message' => 'Error al agregar habitación: ' . $stmt->error];
    }
}

// Función para actualizar una habitación
function actualizarHabitacion($conn, $numero, $datos) {
    $sql = "UPDATE habitacion 
            SET tipo = ?, precio_por_noche = ?, capacidad = ?, estado = ?
            WHERE numero_habitacion = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdisi", 
        $datos['tipo'],
        $datos['precio_por_noche'],
        $datos['capacidad'],
        $datos['estado'],
        $numero
    );
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Habitación actualizada exitosamente'];
    } else {
        return ['success' => false, 'message' => 'Error al actualizar habitación: ' . $stmt->error];
    }
}

// Función para eliminar una habitación
function eliminarHabitacion($conn, $numero) {
    // Verificar si la habitación existe
    $sql = "SELECT COUNT(*) as count FROM habitacion WHERE numero_habitacion = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $numero);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        return ['success' => false, 'message' => 'La habitación no existe.'];
    }
    
    $sql = "DELETE FROM habitacion WHERE numero_habitacion = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $numero);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Habitación eliminada exitosamente'];
    } else {
        return ['success' => false, 'message' => 'Error al eliminar habitación: ' . $stmt->error];
    }
}

// CORRECCIÓN: Función mejorada para cambiar el estado de una habitación
function cambiarEstadoHabitacion($conn, $numero, $nuevoEstado) {
    $today = date('Y-m-d');
    
    // Verificar si hay una reserva activa en esta habitación
    $sql = "SELECT COUNT(*) as count 
            FROM reserva 
            WHERE numero_habitacion = ? 
            AND estado = 'confirmada' 
            AND ? BETWEEN fecha_entrada AND fecha_salida";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $numero, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        return ['success' => false, 'message' => 'No se puede cambiar el estado. Hay una reserva activa en esta habitación. Por favor, cancele primero la reserva.'];
    }
    
    // Verificar si la columna 'estado' existe en la tabla
    $checkColumn = $conn->query("SHOW COLUMNS FROM habitacion LIKE 'estado'");
    
    if ($checkColumn->num_rows > 0) {
        $sql = "UPDATE habitacion SET estado = ? WHERE numero_habitacion = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $nuevoEstado, $numero);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Estado actualizado exitosamente'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar estado: ' . $stmt->error];
        }
    } else {
        return ['success' => false, 'message' => 'El campo estado no existe en la tabla.'];
    }
}

// CORRECCIÓN: Función mejorada para obtener una habitación con su estado real
function obtenerHabitacionPorNumero($conn, $numero) {
    $today = date('Y-m-d');
    
    $sql = "SELECT h.*, 
            (SELECT COUNT(*) 
             FROM reserva r 
             WHERE r.numero_habitacion = h.numero_habitacion 
             AND r.estado = 'confirmada' 
             AND ? BETWEEN r.fecha_entrada AND r.fecha_salida
            ) as esta_ocupada
            FROM habitacion h 
            WHERE h.numero_habitacion = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $today, $numero);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Determinar el estado real basado en reservas
        if ($row['esta_ocupada'] > 0) {
            $row['estado'] = 'ocupada';
        } else {
            $row['estado'] = isset($row['estado']) ? $row['estado'] : 'disponible';
        }
        unset($row['esta_ocupada']);
        
        return ['success' => true, 'data' => $row];
    } else {
        return ['success' => false, 'message' => 'Habitación no encontrada'];
    }
}

// Procesar la acción solicitada
header('Content-Type: application/json');

switch ($action) {
    case 'listar':
        $filtroEstado = isset($_GET['estado']) ? $_GET['estado'] : '';
        $filtroTipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
        $habitaciones = obtenerHabitaciones($conn, $filtroEstado, $filtroTipo);
        echo json_encode(['success' => true, 'data' => $habitaciones]);
        break;
        
    case 'agregar':
        $datos = [
            'numero_habitacion' => $_POST['numero_habitacion'],
            'tipo' => $_POST['tipo'],
            'precio_por_noche' => $_POST['precio_por_noche'],
            'capacidad' => $_POST['capacidad']
        ];
        $resultado = agregarHabitacion($conn, $datos);
        echo json_encode($resultado);
        break;
        
    case 'actualizar':
        $numero = $_POST['numero_habitacion'];
        $datos = [
            'tipo' => $_POST['tipo'],
            'precio_por_noche' => $_POST['precio_por_noche'],
            'capacidad' => $_POST['capacidad'],
            'estado' => isset($_POST['estado']) ? $_POST['estado'] : 'disponible'
        ];
        $resultado = actualizarHabitacion($conn, $numero, $datos);
        echo json_encode($resultado);
        break;
        
    case 'eliminar':
        $numero = $_POST['numero_habitacion'];
        $resultado = eliminarHabitacion($conn, $numero);
        echo json_encode($resultado);
        break;
        
    case 'cambiar_estado':
        $numero = $_POST['numero_habitacion'];
        $nuevoEstado = $_POST['estado'];
        $resultado = cambiarEstadoHabitacion($conn, $numero, $nuevoEstado);
        echo json_encode($resultado);
        break;
        
    case 'obtener':
        $numero = $_GET['numero'];
        $resultado = obtenerHabitacionPorNumero($conn, $numero);
        echo json_encode($resultado);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

$conn->close();
?>