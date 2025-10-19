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

// Función para obtener todos los huéspedes
function obtenerHuespedes($conn, $busqueda = '') {
    $sql = "SELECT * FROM huespedes WHERE 1=1";
    
    if (!empty($busqueda)) {
        $sql .= " AND (nombre LIKE ? OR apellido LIKE ? OR documento LIKE ? OR email LIKE ?)";
    }
    
    $sql .= " ORDER BY fecha_hora_registro DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($busqueda)) {
        $searchTerm = "%$busqueda%";
        $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $huespedes = [];
    while ($row = $result->fetch_assoc()) {
        $huespedes[] = $row;
    }
    
    return $huespedes;
}

// Función para agregar un huésped
function agregarHuesped($conn, $datos) {
    // Verificar si el documento ya existe
    $sql = "SELECT COUNT(*) as count FROM huespedes WHERE documento = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $datos['documento']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        return ['success' => false, 'message' => 'Ya existe un huésped con este documento'];
    }
    
    $sql = "INSERT INTO huespedes (nombre, apellido, documento, tipo_documento, email, telefono, direccion) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", 
        $datos['nombre'],
        $datos['apellido'],
        $datos['documento'],
        $datos['tipo_documento'],
        $datos['email'],
        $datos['telefono'],
        $datos['direccion']
    );
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Huésped agregado exitosamente', 'id' => $conn->insert_id];
    } else {
        return ['success' => false, 'message' => 'Error al agregar huésped: ' . $stmt->error];
    }
}

// Función para actualizar un huésped
function actualizarHuesped($conn, $id, $datos) {
    // Verificar si el documento ya existe en otro registro
    $sql = "SELECT COUNT(*) as count FROM huespedes WHERE documento = ? AND id_huesped != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $datos['documento'], $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        return ['success' => false, 'message' => 'Ya existe otro huésped con este documento'];
    }
    
    $sql = "UPDATE huespedes 
            SET nombre = ?, apellido = ?, documento = ?, tipo_documento = ?, 
                email = ?, telefono = ?, direccion = ?
            WHERE id_huesped = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssi", 
        $datos['nombre'],
        $datos['apellido'],
        $datos['documento'],
        $datos['tipo_documento'],
        $datos['email'],
        $datos['telefono'],
        $datos['direccion'],
        $id
    );
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Huésped actualizado exitosamente'];
    } else {
        return ['success' => false, 'message' => 'Error al actualizar huésped: ' . $stmt->error];
    }
}

// Función para eliminar un huésped
function eliminarHuesped($conn, $id) {
    // Verificar si el huésped existe
    $sql = "SELECT COUNT(*) as count FROM huespedes WHERE id_huesped = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        return ['success' => false, 'message' => 'El huésped no existe.'];
    }
    
    $sql = "DELETE FROM huespedes WHERE id_huesped = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Huésped eliminado exitosamente'];
    } else {
        return ['success' => false, 'message' => 'Error al eliminar huésped: ' . $stmt->error];
    }
}

// Función para obtener un huésped por ID
function obtenerHuespedPorId($conn, $id) {
    $sql = "SELECT * FROM huespedes WHERE id_huesped = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return ['success' => true, 'data' => $row];
    } else {
        return ['success' => false, 'message' => 'Huésped no encontrado'];
    }
}

// Procesar la acción solicitada
header('Content-Type: application/json');

switch ($action) {
    case 'listar':
        $busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
        $huespedes = obtenerHuespedes($conn, $busqueda);
        echo json_encode(['success' => true, 'data' => $huespedes]);
        break;
        
    case 'agregar':
        $datos = [
            'nombre' => $_POST['nombre'],
            'apellido' => $_POST['apellido'],
            'documento' => $_POST['documento'],
            'tipo_documento' => $_POST['tipo_documento'],
            'email' => $_POST['email'],
            'telefono' => $_POST['telefono'],
            'direccion' => $_POST['direccion']
        ];
        $resultado = agregarHuesped($conn, $datos);
        echo json_encode($resultado);
        break;
        
    case 'actualizar':
        $id = $_POST['id_huesped'];
        $datos = [
            'nombre' => $_POST['nombre'],
            'apellido' => $_POST['apellido'],
            'documento' => $_POST['documento'],
            'tipo_documento' => $_POST['tipo_documento'],
            'email' => $_POST['email'],
            'telefono' => $_POST['telefono'],
            'direccion' => $_POST['direccion']
        ];
        $resultado = actualizarHuesped($conn, $id, $datos);
        echo json_encode($resultado);
        break;
        
    case 'eliminar':
        $id = $_POST['id_huesped'];
        $resultado = eliminarHuesped($conn, $id);
        echo json_encode($resultado);
        break;
        
    case 'obtener':
        $id = $_GET['id'];
        $resultado = obtenerHuespedPorId($conn, $id);
        echo json_encode($resultado);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

$conn->close();
?>
