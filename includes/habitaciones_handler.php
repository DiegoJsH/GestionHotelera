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
    
    // Verificar si existe la columna piso
    $columnExists = $conn->query("SHOW COLUMNS FROM habitacion LIKE 'piso'");
    $hasPiso = ($columnExists && $columnExists->num_rows > 0);
    
    $pisoField = $hasPiso ? 'h.piso,' : '';
    
    $sql = "SELECT h.*, 
            $pisoField
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
        
        // Asegurar que siempre haya un valor de piso
        if (!isset($row['piso']) || empty($row['piso'])) {
            $row['piso'] = '1';
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
function agregarHabitacion($conn, $datos, $nombreImagen = 'hb_sinfoto.webp') {
    // Verificar si existe la columna piso
    $columnExists = $conn->query("SHOW COLUMNS FROM habitacion LIKE 'piso'");
    $hasPiso = ($columnExists && $columnExists->num_rows > 0);
    
    if ($hasPiso) {
        $sql = "INSERT INTO habitacion (numero_habitacion, piso, tipo, precio_por_noche, capacidad, imagen) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issdis", 
            $datos['numero_habitacion'],
            $datos['piso'],
            $datos['tipo'],
            $datos['precio_por_noche'],
            $datos['capacidad'],
            $nombreImagen
        );
    } else {
        $sql = "INSERT INTO habitacion (numero_habitacion, tipo, precio_por_noche, capacidad, imagen) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isdis", 
            $datos['numero_habitacion'],
            $datos['tipo'],
            $datos['precio_por_noche'],
            $datos['capacidad'],
            $nombreImagen
        );
    }
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Habitación agregada exitosamente'];
    } else {
        return ['success' => false, 'message' => 'Error al agregar habitación: ' . $stmt->error];
    }
}

// Función para actualizar una habitación
function actualizarHabitacion($conn, $numero, $datos, $nombreImagen = null) {
    // Verificar si existe la columna piso
    $columnExists = $conn->query("SHOW COLUMNS FROM habitacion LIKE 'piso'");
    $hasPiso = ($columnExists && $columnExists->num_rows > 0);
    
    if ($hasPiso) {
        if ($nombreImagen !== null) {
            $sql = "UPDATE habitacion 
                    SET piso = ?, tipo = ?, precio_por_noche = ?, capacidad = ?, estado = ?, imagen = ?
                    WHERE numero_habitacion = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdissi", 
                $datos['piso'],
                $datos['tipo'],
                $datos['precio_por_noche'],
                $datos['capacidad'],
                $datos['estado'],
                $nombreImagen,
                $numero
            );
        } else {
            $sql = "UPDATE habitacion 
                    SET piso = ?, tipo = ?, precio_por_noche = ?, capacidad = ?, estado = ?
                    WHERE numero_habitacion = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdisi", 
                $datos['piso'],
                $datos['tipo'],
                $datos['precio_por_noche'],
                $datos['capacidad'],
                $datos['estado'],
                $numero
            );
        }
    } else {
        if ($nombreImagen !== null) {
            $sql = "UPDATE habitacion 
                    SET tipo = ?, precio_por_noche = ?, capacidad = ?, estado = ?, imagen = ?
                    WHERE numero_habitacion = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdissi", 
                $datos['tipo'],
                $datos['precio_por_noche'],
                $datos['capacidad'],
                $datos['estado'],
                $nombreImagen,
                $numero
            );
        } else {
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
        }
    }
    
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
    
    // Verificar si existe la columna piso
    $columnExists = $conn->query("SHOW COLUMNS FROM habitacion LIKE 'piso'");
    $hasPiso = ($columnExists && $columnExists->num_rows > 0);
    
    $pisoField = $hasPiso ? 'h.piso,' : '';
    
    $sql = "SELECT h.*, 
            $pisoField
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
        
        // Asegurar que siempre haya un valor de piso
        if (!isset($row['piso']) || empty($row['piso'])) {
            $row['piso'] = '1';
        }
        
        unset($row['esta_ocupada']);
        
        return ['success' => true, 'data' => $row];
    } else {
        return ['success' => false, 'message' => 'Habitación no encontrada'];
    }
}

// Función para procesar upload de imagen
function procesarUploadImagen() {
    if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // No se subió imagen
    }
    
    $archivo = $_FILES['imagen'];
    
    // Verificar errores
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Error al subir el archivo'];
    }
    
    // Validar tamaño (máx 2MB)
    if ($archivo['size'] > 2 * 1024 * 1024) {
        return ['error' => 'La imagen es muy grande. Máximo 2MB'];
    }
    
    // Validar tipo
    $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $tipoMime = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($tipoMime, $tiposPermitidos)) {
        return ['error' => 'Formato no válido. Use JPG, PNG o WebP'];
    }
    
    // Usar el nombre original del archivo
    $nombreArchivo = basename($archivo['name']);
    
    // Ruta donde se guardará
    $rutaDestino = '../assets/img/habitaciones/' . $nombreArchivo;
    
    // Mover archivo
    if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        return $nombreArchivo;
    } else {
        return ['error' => 'Error al guardar la imagen'];
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
            'piso' => isset($_POST['piso']) ? $_POST['piso'] : '1',
            'tipo' => $_POST['tipo'],
            'precio_por_noche' => $_POST['precio_por_noche'],
            'capacidad' => $_POST['capacidad']
        ];
        
        // Procesar imagen si se subió
        $nombreImagen = procesarUploadImagen();
        if (is_array($nombreImagen) && isset($nombreImagen['error'])) {
            echo json_encode(['success' => false, 'message' => $nombreImagen['error']]);
            break;
        }
        
        $resultado = agregarHabitacion($conn, $datos, $nombreImagen);
        echo json_encode($resultado);
        break;
        
    case 'actualizar':
        $numero = $_POST['numero_habitacion'];
        $datos = [
            'piso' => isset($_POST['piso']) ? $_POST['piso'] : '1',
            'tipo' => $_POST['tipo'],
            'precio_por_noche' => $_POST['precio_por_noche'],
            'capacidad' => $_POST['capacidad'],
            'estado' => isset($_POST['estado']) ? $_POST['estado'] : 'disponible'
        ];
        
        // Procesar imagen si se subió
        $nombreImagen = procesarUploadImagen();
        if (is_array($nombreImagen) && isset($nombreImagen['error'])) {
            echo json_encode(['success' => false, 'message' => $nombreImagen['error']]);
            break;
        }
        
        $resultado = actualizarHabitacion($conn, $numero, $datos, $nombreImagen);
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