<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
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
                    <div class="no-data">Cargando habitaciones...</div>
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
                
                <div class="form-group">
                    <label>Imagen de la Habitación</label>
                    <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/jpg,image/png,image/webp">
                    <small style="color: #666; font-size: 0.85em;">Formatos: JPG, PNG, WebP (máx. 2MB)</small>
                    <div id="imagenPreview" style="margin-top: 10px;"></div>
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

    <!-- Modal para ver foto de habitación -->
    <div id="fotoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="fotoModalTitle">Foto de la Habitación</h2>
                <span class="close" onclick="closeFotoModal()">&times;</span>
            </div>
            <div class="modal-body" style="text-align: center; padding: 20px;">
                <img id="fotoHabitacion" src="" alt="Foto de habitación" style="max-width: 100%; max-height: 500px; border-radius: 8px;">
            </div>
        </div>
    </div>

    <script>
        let habitacionesData = [];
        
        // Cargar habitaciones al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarHabitaciones();
        });
        
        // Cargar todas las habitaciones
        function cargarHabitaciones() {
            fetch('../includes/habitaciones_handler.php?action=listar')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        habitacionesData = data.data;
                        mostrarHabitaciones(habitacionesData);
                    } else {
                        console.error('Error al cargar habitaciones:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('roomsGrid').innerHTML = 
                        '<div class="no-data">Error al cargar las habitaciones</div>';
                });
        }
        
        // Mostrar habitaciones en el grid
        function mostrarHabitaciones(habitaciones) {
            const grid = document.getElementById('roomsGrid');
            
            if (habitaciones.length === 0) {
                grid.innerHTML = '<div class="no-data"><p>No hay habitaciones registradas. ¡Agrega la primera!</p></div>';
                return;
            }
            
            grid.innerHTML = '';
            habitaciones.forEach(habitacion => {
                const estadoClass = getEstadoClass(habitacion.estado);
                const estadoTexto = getEstadoTexto(habitacion.estado);
                const fechaRegistro = new Date(habitacion.fecha_hora_registro).toLocaleDateString('es-ES');
                
                const card = document.createElement('div');
                card.className = `room-card ${estadoClass}`;
                card.setAttribute('data-estado', habitacion.estado.toLowerCase());
                card.setAttribute('data-tipo', habitacion.tipo);
                
                card.innerHTML = `
                    <div class="room-header">
                        <h3>Habitación ${habitacion.numero_habitacion}</h3>
                        <span class="room-status">${estadoTexto}</span>
                    </div>
                    <div class="room-info">
                        <p><strong>Tipo:</strong> ${habitacion.tipo}</p>
                        <p><strong>Precio:</strong> \$${parseFloat(habitacion.precio_por_noche).toFixed(2)}/noche</p>
                        <p><strong>Capacidad:</strong> ${habitacion.capacidad} ${habitacion.capacidad == 1 ? 'persona' : 'personas'}</p>
                        <p><strong>Registrada:</strong> ${fechaRegistro}</p>
                    </div>
                    <div class="room-actions">
                        <button class="btn btn-small btn-info" onclick="verFoto('${habitacion.imagen || 'hb_sinfoto.webp'}', ${habitacion.numero_habitacion})">
                            Ver Foto
                        </button>
                        ${generarBotonesEstado(habitacion)}
                        <button class="btn btn-small btn-secondary" onclick="openEditModal(${habitacion.numero_habitacion})">
                            Editar
                        </button>
                        <button class="btn btn-small btn-danger" onclick="eliminarHabitacion(${habitacion.numero_habitacion})">
                            Eliminar
                        </button>
                    </div>
                `;
                
                grid.appendChild(card);
            });
        }
        
        // Generar botones según el estado
        function generarBotonesEstado(habitacion) {
            const estado = habitacion.estado.toLowerCase();
            
            if (estado === 'disponible') {
                return `<button class="btn btn-small btn-success" onclick="cambiarEstado(${habitacion.numero_habitacion}, 'ocupada')">
                    Marcar Ocupada
                </button>`;
            } else if (estado === 'ocupada') {
                return `<button class="btn btn-small btn-warning" onclick="cambiarEstado(${habitacion.numero_habitacion}, 'disponible')">
                    Liberar
                </button>`;
            } else if (estado === 'mantenimiento') {
                return `<button class="btn btn-small btn-success" onclick="cambiarEstado(${habitacion.numero_habitacion}, 'disponible')">
                    Marcar Lista
                </button>`;
            }
            return '';
        }
        
        // Obtener clase CSS según estado
        function getEstadoClass(estado) {
            switch(estado.toLowerCase()) {
                case 'disponible': return 'available';
                case 'ocupada': return 'occupied';
                case 'mantenimiento': return 'maintenance';
                default: return 'available';
            }
        }
        
        // Obtener texto del estado
        function getEstadoTexto(estado) {
            switch(estado.toLowerCase()) {
                case 'disponible': return 'Disponible';
                case 'ocupada': return 'Ocupada';
                case 'mantenimiento': return 'Mantenimiento';
                default: return estado.charAt(0).toUpperCase() + estado.slice(1);
            }
        }
        
        // Filtrar habitaciones por estado y tipo
        function filtrarHabitaciones() {
            const filtroEstado = document.getElementById('filtroEstado').value;
            const filtroTipo = document.getElementById('filtroTipo').value;
            
            let habitacionesFiltradas = habitacionesData;
            
            if (filtroEstado !== 'todas') {
                habitacionesFiltradas = habitacionesFiltradas.filter(h => 
                    h.estado.toLowerCase() === filtroEstado.toLowerCase()
                );
            }
            
            if (filtroTipo !== 'todos') {
                habitacionesFiltradas = habitacionesFiltradas.filter(h => 
                    h.tipo === filtroTipo
                );
            }
            
            mostrarHabitaciones(habitacionesFiltradas);
        }
        
        // Abrir modal para agregar
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Nueva Habitación';
            document.getElementById('formAction').value = 'agregar';
            document.getElementById('roomForm').reset();
            document.getElementById('estadoGroup').style.display = 'none';
            document.getElementById('imagenPreview').innerHTML = '';
            document.getElementById('roomModal').style.display = 'flex';
        }
        
        // Preview de imagen al seleccionar
        document.getElementById('imagen').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagenPreview');
            
            if (file) {
                // Validar tamaño (máx 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('La imagen es muy grande. Máximo 2MB');
                    e.target.value = '';
                    preview.innerHTML = '';
                    return;
                }
                
                // Validar tipo
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Formato no válido. Use JPG, PNG o WebP');
                    e.target.value = '';
                    preview.innerHTML = '';
                    return;
                }
                
                // Mostrar preview
                const reader = new FileReader();
                reader.onload = function(event) {
                    preview.innerHTML = `<img src="${event.target.result}" style="max-width: 200px; max-height: 150px; border-radius: 4px;">`;
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        });
        
        // Ver foto de habitación
        function verFoto(imagen, numero) {
            const imgPath = `../assets/img/habitaciones/${imagen}`;
            document.getElementById('fotoHabitacion').src = imgPath;
            document.getElementById('fotoModalTitle').textContent = `Foto de la Habitación ${numero}`;
            document.getElementById('fotoModal').style.display = 'flex';
        }
        
        // Cerrar modal de foto
        function closeFotoModal() {
            document.getElementById('fotoModal').style.display = 'none';
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
                    closeRoomModal();
                    cargarHabitaciones(); // Recargar habitaciones
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
            const textoEstado = getEstadoTexto(nuevoEstado);
            if (confirm(`¿Está seguro de cambiar el estado de la habitación a "${textoEstado}"?`)) {
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
                        cargarHabitaciones(); // Recargar habitaciones
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
                        cargarHabitaciones(); // Recargar habitaciones
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
