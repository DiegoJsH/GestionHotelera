<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

include "../includes/db_connection.php";

// Obtener todas las habitaciones
$sql = "SELECT * FROM habitacion ORDER BY numero_habitacion ASC";
$result = $conn->query($sql);
$habitaciones = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Agregar estado por defecto si no existe
        if (!isset($row['estado'])) {
            $row['estado'] = 'disponible';
        }
        $habitaciones[] = $row;
    }
}

// Función para obtener la clase CSS según el estado
function getEstadoClass($estado) {
    switch(strtolower($estado)) {
        case 'disponible':
            return 'available';
        case 'ocupada':
            return 'occupied';
        case 'mantenimiento':
            return 'maintenance';
        default:
            return 'available';
    }
}

// Función para obtener el texto del estado
function getEstadoTexto($estado) {
    switch(strtolower($estado)) {
        case 'disponible':
            return 'Disponible';
        case 'ocupada':
            return 'Ocupada';
        case 'mantenimiento':
            return 'Mantenimiento';
        default:
            return ucfirst($estado);
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
                <h1>Gestión de Habitaciones</h1>
                <button class="btn btn-primary" onclick="openAddModal()">+ Nueva Habitación</button>
            </header>

            <div class="content-area">
                <div class="filters">
                    <select class="filter-select" id="filtroEstado" onchange="filtrarHabitaciones()">
                        <option value="todas">Todas las habitaciones</option>
                        <option value="disponible">Disponibles</option>
                        <option value="ocupada">Ocupadas</option>
                        <option value="mantenimiento">Mantenimiento</option>
                    </select>
                    <select class="filter-select" id="filtroTipo" onchange="filtrarHabitaciones()">
                        <option value="todos">Todos los tipos</option>
                        <option value="Individual">Individual</option>
                        <option value="Doble">Doble</option>
                        <option value="Suite">Suite</option>
                        <option value="Familiar">Familiar</option>
                    </select>
                </div>

                <div class="rooms-grid" id="roomsGrid">
                    <?php if (empty($habitaciones)): ?>
                        <div class="no-data">
                            <p>No hay habitaciones registradas. ¡Agrega la primera!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($habitaciones as $habitacion): 
                            $estado = isset($habitacion['estado']) ? $habitacion['estado'] : 'disponible';
                        ?>
                            <div class="room-card <?php echo getEstadoClass($estado); ?>" 
                                 data-estado="<?php echo strtolower($estado); ?>"
                                 data-tipo="<?php echo htmlspecialchars($habitacion['tipo']); ?>">
                                <div class="room-header">
                                    <h3>Habitación <?php echo htmlspecialchars($habitacion['numero_habitacion']); ?></h3>
                                    <span class="room-status"><?php echo getEstadoTexto($estado); ?></span>
                                </div>
                                <div class="room-info">
                                    <p><strong>Tipo:</strong> <?php echo htmlspecialchars($habitacion['tipo']); ?></p>
                                    <p><strong>Precio:</strong> $<?php echo number_format($habitacion['precio_por_noche'], 2); ?>/noche</p>
                                    <p><strong>Capacidad:</strong> <?php echo $habitacion['capacidad']; ?> 
                                        <?php echo $habitacion['capacidad'] == 1 ? 'persona' : 'personas'; ?></p>
                                    <p><strong>Registrada:</strong> <?php echo date('d/m/Y', strtotime($habitacion['fecha_hora_registro'])); ?></p>
                                </div>
                                <div class="room-actions">
                                    <?php 
                                    $estado_actual = isset($habitacion['estado']) ? strtolower($habitacion['estado']) : 'disponible';
                                    $num_hab = $habitacion['numero_habitacion'];
                                    
                                    if ($estado_actual == 'disponible'): 
                                    ?>
                                        <button class="btn btn-small btn-success" 
                                                onclick="cambiarEstado(<?php echo $num_hab; ?>, 'ocupada')">
                                            Marcar Ocupada
                                        </button>
                                    <?php elseif ($estado_actual == 'ocupada'): ?>
                                        <button class="btn btn-small btn-warning" 
                                                onclick="cambiarEstado(<?php echo $num_hab; ?>, 'disponible')">
                                            Liberar
                                        </button>
                                    <?php elseif ($estado_actual == 'mantenimiento'): ?>
                                        <button class="btn btn-small btn-success" 
                                                onclick="cambiarEstado(<?php echo $num_hab; ?>, 'disponible')">
                                            Marcar Lista
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-small btn-secondary" 
                                            onclick="openEditModal(<?php echo $num_hab; ?>)">
                                        Editar
                                    </button>
                                    <button class="btn btn-small btn-danger" 
                                            onclick="eliminarHabitacion(<?php echo $num_hab; ?>)">
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal para agregar/editar habitación -->
    <div id="roomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nueva Habitación</h2>
                <span class="close" onclick="closeRoomModal()">&times;</span>
            </div>
            <form id="roomForm" class="modal-form" onsubmit="guardarHabitacion(event)">
                <input type="hidden" id="formAction" name="action" value="agregar">
                
                <div class="form-group">
                    <label>Número de Habitación *</label>
                    <input type="number" id="numero_habitacion" name="numero_habitacion" required min="1">
                </div>
                
                <div class="form-group">
                    <label>Tipo *</label>
                    <select id="tipo" name="tipo" required>
                        <option value="">Seleccione un tipo</option>
                        <option value="Individual">Individual</option>
                        <option value="Doble">Doble</option>
                        <option value="Suite">Suite</option>
                        <option value="Familiar">Familiar</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Precio por Noche ($) *</label>
                    <input type="number" id="precio_por_noche" name="precio_por_noche" 
                           step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Capacidad (personas) *</label>
                    <input type="number" id="capacidad" name="capacidad" required min="1" max="10">
                </div>
                
                <div class="form-group" id="estadoGroup" style="display:none;">
                    <label>Estado</label>
                    <select id="estado" name="estado">
                        <option value="disponible">Disponible</option>
                        <option value="ocupada">Ocupada</option>
                        <option value="mantenimiento">Mantenimiento</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeRoomModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Filtrar habitaciones por estado y tipo
        function filtrarHabitaciones() {
            const filtroEstado = document.getElementById('filtroEstado').value;
            const filtroTipo = document.getElementById('filtroTipo').value;
            const cards = document.querySelectorAll('.room-card');
            
            cards.forEach(card => {
                const estado = card.getAttribute('data-estado');
                const tipo = card.getAttribute('data-tipo');
                
                const matchEstado = filtroEstado === 'todas' || estado === filtroEstado;
                const matchTipo = filtroTipo === 'todos' || tipo === filtroTipo;
                
                if (matchEstado && matchTipo) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Abrir modal para agregar
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Nueva Habitación';
            document.getElementById('formAction').value = 'agregar';
            document.getElementById('roomForm').reset();
            document.getElementById('estadoGroup').style.display = 'none';
            document.getElementById('roomModal').style.display = 'flex';
        }
        
        // Abrir modal para editar
        function openEditModal(numero) {
            document.getElementById('modalTitle').textContent = 'Editar Habitación';
            document.getElementById('formAction').value = 'actualizar';
            document.getElementById('estadoGroup').style.display = 'block';
            
            // Obtener datos de la habitación
            fetch(`../includes/habitaciones_handler.php?action=obtener&numero=${numero}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const habitacion = data.data;
                        document.getElementById('numero_habitacion').value = habitacion.numero_habitacion;
                        document.getElementById('tipo').value = habitacion.tipo;
                        document.getElementById('precio_por_noche').value = habitacion.precio_por_noche;
                        document.getElementById('capacidad').value = habitacion.capacidad;
                        document.getElementById('estado').value = habitacion.estado;
                        document.getElementById('roomModal').style.display = 'flex';
                    } else {
                        alert('Error al cargar los datos: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los datos de la habitación');
                });
        }
        
        // Cerrar modal
        function closeRoomModal() {
            document.getElementById('roomModal').style.display = 'none';
            document.getElementById('roomForm').reset();
        }
        
        // Guardar habitación (agregar o actualizar)
        function guardarHabitacion(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            
            fetch('../includes/habitaciones_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            });
        }
        
        // Cambiar estado de habitación
        function cambiarEstado(numero, nuevoEstado) {
            if (confirm(`¿Está seguro de cambiar el estado de la habitación a "${nuevoEstado}"?`)) {
                const formData = new FormData();
                formData.append('action', 'cambiar_estado');
                formData.append('numero_habitacion', numero);
                formData.append('estado', nuevoEstado);
                
                fetch('../includes/habitaciones_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cambiar el estado');
                });
            }
        }
        
        // Eliminar habitación
        function eliminarHabitacion(numero) {
            if (confirm('¿Está seguro de eliminar esta habitación? Esta acción no se puede deshacer.')) {
                const formData = new FormData();
                formData.append('action', 'eliminar');
                formData.append('numero_habitacion', numero);
                
                fetch('../includes/habitaciones_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar la habitación');
                });
            }
        }
        
        // Cerrar modal al hacer clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('roomModal');
            if (event.target === modal) {
                closeRoomModal();
            }
        }
    </script>
</body>
</html>
