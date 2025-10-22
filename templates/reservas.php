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
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="app-container">
        <nav class="sidebar">
            <?php include "../includes/sidebar.php" ?>
        </nav>

        <main class="main-content">
            <header class="top-bar">
                <h1>Gestión de Reservas</h1>
                <button class="btn btn-primary" onclick="openAddModal()">+ Nueva Reserva</button>
            </header>

            <div class="content-area">
                <div class="filters">
                    <input type="date" class="filter-input" id="filtroFechaEntrada" onchange="filtrarReservas()">
                    <input type="date" class="filter-input" id="filtroFechaSalida" onchange="filtrarReservas()">
                    <select class="filter-select" id="filtroEstado" onchange="filtrarReservas()">
                        <option value="todas">Todas las reservas</option>
                        <option value="confirmada">Confirmadas</option>
                        <option value="pendiente">Pendientes</option>
                        <option value="cancelada">Canceladas</option>
                    </select>
                    <input type="text" class="filter-input" id="filtroBusqueda" placeholder="Buscar por huésped..." onkeyup="filtrarReservas()">
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Huésped</th>
                                <th>Habitación</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Días</th>
                                <th>Estado</th>
                                <th>Total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="reservasTableBody">
                            <tr>
                                <td colspan="9" class="no-data">Cargando reservas...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal para agregar/editar reserva -->
    <div id="addReservationModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2 id="modalTitle">Nueva Reserva</h2>
                <span class="close" onclick="closeReservationModal()">&times;</span>
            </div>
            <form id="reservationForm" class="modal-form" onsubmit="guardarReserva(event)">
                <input type="hidden" id="formAction" name="action" value="agregar">
                <input type="hidden" id="id_reserva" name="id_reserva" value="">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Huésped *</label>
                        <select id="id_huesped" name="id_huesped" required>
                            <option value="">Seleccionar huésped...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Habitación *</label>
                        <select id="numero_habitacion" name="numero_habitacion" required disabled>
                            <option value="">Primero seleccione las fechas</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha de Entrada *</label>
                        <input type="date" id="fecha_entrada" name="fecha_entrada" required onchange="cargarHabitacionesDisponibles()">
                    </div>
                    <div class="form-group">
                        <label>Fecha de Salida *</label>
                        <input type="date" id="fecha_salida" name="fecha_salida" required onchange="cargarHabitacionesDisponibles()">
                    </div>
                </div>
                
                <div class="form-group" id="estadoGroup" style="display:none;">
                    <label>Estado</label>
                    <select id="estado" name="estado">
                        <option value="confirmada">Confirmada</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea id="observaciones" name="observaciones" rows="3"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeReservationModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para ver detalles -->
    <div id="viewReservationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalles de la Reserva</h2>
                <span class="close" onclick="closeViewModal()">&times;</span>
            </div>
            <div class="modal-form">
                <div class="form-group">
                    <label><strong>ID Reserva:</strong></label>
                    <p id="view_id_reserva"></p>
                </div>
                <div class="form-group">
                    <label><strong>Huésped:</strong></label>
                    <p id="view_huesped"></p>
                </div>
                <div class="form-group">
                    <label><strong>Email:</strong></label>
                    <p id="view_email"></p>
                </div>
                <div class="form-group">
                    <label><strong>Teléfono:</strong></label>
                    <p id="view_telefono"></p>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><strong>Habitación:</strong></label>
                        <p id="view_habitacion"></p>
                    </div>
                    <div class="form-group">
                        <label><strong>Tipo:</strong></label>
                        <p id="view_tipo"></p>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><strong>Check-in:</strong></label>
                        <p id="view_fecha_entrada"></p>
                    </div>
                    <div class="form-group">
                        <label><strong>Check-out:</strong></label>
                        <p id="view_fecha_salida"></p>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><strong>Días:</strong></label>
                        <p id="view_dias"></p>
                    </div>
                    <div class="form-group">
                        <label><strong>Estado:</strong></label>
                        <p id="view_estado"></p>
                    </div>
                </div>
                <div class="form-group">
                    <label><strong>Total:</strong></label>
                    <p id="view_total"></p>
                </div>
                <div class="form-group">
                    <label><strong>Observaciones:</strong></label>
                    <p id="view_observaciones"></p>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let reservasData = [];
        
        // Cargar reservas al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarHuespedes();
            cargarReservas();
        });
        
        // Cargar todas las reservas
        function cargarReservas() {
            fetch('../includes/reservas_handler.php?action=listar')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        reservasData = data.data;
                        mostrarReservas(reservasData);
                    } else {
                        console.error('Error al cargar reservas:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('reservasTableBody').innerHTML = 
                        '<tr><td colspan="9" class="no-data">Error al cargar las reservas</td></tr>';
                });
        }
        
        // Mostrar reservas en la tabla
        function mostrarReservas(reservas) {
            const tbody = document.getElementById('reservasTableBody');
            
            if (reservas.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="no-data">No hay reservas registradas</td></tr>';
                return;
            }
            
            tbody.innerHTML = '';
            reservas.forEach(reserva => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#${String(reserva.id_reserva).padStart(3, '0')}</td>
                    <td>${reserva.nombre} ${reserva.apellido}</td>
                    <td>${reserva.numero_habitacion}</td>
                    <td>${formatearFecha(reserva.fecha_entrada)}</td>
                    <td>${formatearFecha(reserva.fecha_salida)}</td>
                    <td>${reserva.dias}</td>
                    <td><span class="status ${reserva.estado}">${capitalizar(reserva.estado)}</span></td>
                    <td>\$${reserva.total.toFixed(2)}</td>
                    <td>
                        <button class="btn btn-small btn-primary" onclick="verDetalles(${reserva.id_reserva})">Ver</button>
                        ${reserva.estado !== 'cancelada' ? `
                            <button class="btn btn-small btn-secondary" onclick="openEditModal(${reserva.id_reserva})">Editar</button>
                        ` : ''}
                        ${reserva.estado === 'pendiente' ? `
                            <button class="btn btn-small btn-success" onclick="confirmarReserva(${reserva.id_reserva})">Confirmar</button>
                        ` : ''}
                        ${reserva.estado !== 'cancelada' ? `
                            <button class="btn btn-small btn-danger" onclick="cancelarReserva(${reserva.id_reserva})">Cancelar</button>
                        ` : ''}
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
        
        // Filtrar reservas
        function filtrarReservas() {
            const fechaEntrada = document.getElementById('filtroFechaEntrada').value;
            const fechaSalida = document.getElementById('filtroFechaSalida').value;
            const estado = document.getElementById('filtroEstado').value;
            const busqueda = document.getElementById('filtroBusqueda').value.toLowerCase();
            
            let reservasFiltradas = reservasData;
            
            if (fechaEntrada) {
                reservasFiltradas = reservasFiltradas.filter(r => r.fecha_entrada >= fechaEntrada);
            }
            
            if (fechaSalida) {
                reservasFiltradas = reservasFiltradas.filter(r => r.fecha_salida <= fechaSalida);
            }
            
            if (estado !== 'todas') {
                reservasFiltradas = reservasFiltradas.filter(r => r.estado === estado);
            }
            
            if (busqueda) {
                reservasFiltradas = reservasFiltradas.filter(r => 
                    r.nombre.toLowerCase().includes(busqueda) ||
                    r.apellido.toLowerCase().includes(busqueda) ||
                    String(r.numero_habitacion).includes(busqueda)
                );
            }
            
            mostrarReservas(reservasFiltradas);
        }
        
        // Cargar lista de huéspedes
        function cargarHuespedes() {
            console.log('Cargando lista de huéspedes...');
            return fetch('../includes/reservas_handler.php?action=obtener_huespedes')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('id_huesped');
                        select.innerHTML = '<option value="">Seleccionar huésped...</option>';
                        data.data.forEach(huesped => {
                            const option = document.createElement('option');
                            option.value = huesped.id_huesped;
                            option.textContent = `${huesped.nombre} ${huesped.apellido}`;
                            select.appendChild(option);
                        });
                        console.log(`${data.data.length} huéspedes cargados correctamente`);
                        return true;
                    }
                    console.error('Error al cargar huéspedes desde el servidor');
                    return false;
                })
                .catch(error => {
                    console.error('Error al cargar huéspedes:', error);
                    return false;
                });
        }
        
        // Cargar habitaciones disponibles según fechas
        function cargarHabitacionesDisponibles() {
            const fechaEntrada = document.getElementById('fecha_entrada').value;
            const fechaSalida = document.getElementById('fecha_salida').value;
            const idReserva = document.getElementById('id_reserva').value;
            
            if (!fechaEntrada || !fechaSalida) {
                console.log('Faltan fechas para cargar habitaciones');
                return Promise.resolve(false);
            }
            
            if (new Date(fechaSalida) <= new Date(fechaEntrada)) {
                alert('La fecha de salida debe ser posterior a la fecha de entrada');
                document.getElementById('fecha_salida').value = '';
                return Promise.resolve(false);
            }
            
            let url = `../includes/reservas_handler.php?action=obtener_habitaciones_disponibles&fecha_entrada=${fechaEntrada}&fecha_salida=${fechaSalida}`;
            if (idReserva) {
                url += `&id_reserva=${idReserva}`;
            }
            
            console.log('Cargando habitaciones disponibles...', { fechaEntrada, fechaSalida, idReserva });
            
            return fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('numero_habitacion');
                        select.disabled = false;
                        select.innerHTML = '<option value="">Seleccionar habitación...</option>';
                        
                        if (data.data.length === 0) {
                            select.innerHTML = '<option value="">No hay habitaciones disponibles</option>';
                            select.disabled = true;
                            console.warn('No hay habitaciones disponibles');
                            return false;
                        } else {
                            data.data.forEach(habitacion => {
                                const option = document.createElement('option');
                                option.value = habitacion.numero_habitacion;
                                option.textContent = `Habitación ${habitacion.numero_habitacion} - ${habitacion.tipo} (\$${habitacion.precio_por_noche}/noche)`;
                                select.appendChild(option);
                            });
                            console.log(`${data.data.length} habitaciones cargadas`);
                            return true;
                        }
                    }
                    console.error('Error en respuesta del servidor');
                    return false;
                })
                .catch(error => {
                    console.error('Error al cargar habitaciones:', error);
                    return false;
                });
        }
        
        // Abrir modal para agregar
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Nueva Reserva';
            document.getElementById('formAction').value = 'agregar';
            document.getElementById('reservationForm').reset();
            document.getElementById('id_reserva').value = '';
            document.getElementById('estadoGroup').style.display = 'none';
            document.getElementById('numero_habitacion').disabled = true;
            document.getElementById('numero_habitacion').innerHTML = '<option value="">Primero seleccione las fechas</option>';
            
            // Cargar huéspedes cada vez que se abre el modal
            cargarHuespedes();
            
            // Establecer fecha mínima como hoy
            const hoy = new Date().toISOString().split('T')[0];
            document.getElementById('fecha_entrada').setAttribute('min', hoy);
            document.getElementById('fecha_salida').setAttribute('min', hoy);
            
            document.getElementById('addReservationModal').style.display = 'flex';
        }
        
        // Abrir modal para editar
        function openEditModal(id) {
            document.getElementById('modalTitle').textContent = 'Editar Reserva';
            document.getElementById('formAction').value = 'actualizar';
            document.getElementById('estadoGroup').style.display = 'block';
            
            // Primero cargar los huéspedes, luego los datos de la reserva
            cargarHuespedes().then(() => {
                fetch(`../includes/reservas_handler.php?action=obtener&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const reserva = data.data;
                            
                            // Llenar todos los campos primero
                            document.getElementById('id_reserva').value = reserva.id_reserva;
                            document.getElementById('id_huesped').value = reserva.id_huesped;
                            document.getElementById('observaciones').value = reserva.observaciones || '';
                            document.getElementById('estado').value = reserva.estado;
                            
                            // Llenar las fechas ANTES de cargar habitaciones
                            document.getElementById('fecha_entrada').value = reserva.fecha_entrada;
                            document.getElementById('fecha_salida').value = reserva.fecha_salida;
                            
                            // Ahora sí, cargar habitaciones con las fechas ya establecidas
                            cargarHabitacionesDisponibles().then((success) => {
                                if (success) {
                                    // Usar requestAnimationFrame para asegurar que el DOM esté actualizado
                                    requestAnimationFrame(() => {
                                        const selectHabitacion = document.getElementById('numero_habitacion');
                                        selectHabitacion.value = reserva.numero_habitacion;
                                        
                                        // Verificar si se seleccionó correctamente
                                        if (selectHabitacion.value !== String(reserva.numero_habitacion)) {
                                            console.warn('Reintentando seleccionar habitación:', reserva.numero_habitacion);
                                            // Intentar de nuevo con un pequeño delay
                                            setTimeout(() => {
                                                selectHabitacion.value = reserva.numero_habitacion;
                                                if (selectHabitacion.value !== String(reserva.numero_habitacion)) {
                                                    console.error('No se pudo seleccionar la habitación después de reintentar');
                                                } else {
                                                    console.log('Habitación seleccionada correctamente en segundo intento');
                                                }
                                            }, 200);
                                        } else {
                                            console.log('Habitación seleccionada correctamente:', reserva.numero_habitacion);
                                        }
                                    });
                                } else {
                                    console.error('No se pudieron cargar las habitaciones disponibles');
                                }
                            });
                            
                            document.getElementById('addReservationModal').style.display = 'flex';
                        } else {
                            alert('Error al cargar los datos: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al cargar los datos de la reserva');
                    });
            }).catch(error => {
                console.error('Error al cargar huéspedes:', error);
                alert('Error al cargar la lista de huéspedes');
            });
        }
        
        // Cerrar modal
        function closeReservationModal() {
            document.getElementById('addReservationModal').style.display = 'none';
            document.getElementById('reservationForm').reset();
        }
        
        // Guardar reserva
        function guardarReserva(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            
            fetch('../includes/reservas_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeReservationModal();
                    cargarReservas();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            });
        }
        
        // Ver detalles de una reserva
        function verDetalles(id) {
            fetch(`../includes/reservas_handler.php?action=obtener&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const reserva = data.data;
                        document.getElementById('view_id_reserva').textContent = '#' + String(reserva.id_reserva).padStart(3, '0');
                        document.getElementById('view_huesped').textContent = `${reserva.nombre} ${reserva.apellido}`;
                        document.getElementById('view_email').textContent = reserva.email;
                        document.getElementById('view_telefono').textContent = reserva.telefono;
                        document.getElementById('view_habitacion').textContent = reserva.numero_habitacion;
                        document.getElementById('view_tipo').textContent = reserva.tipo;
                        document.getElementById('view_fecha_entrada').textContent = formatearFecha(reserva.fecha_entrada);
                        document.getElementById('view_fecha_salida').textContent = formatearFecha(reserva.fecha_salida);
                        document.getElementById('view_dias').textContent = reserva.dias;
                        document.getElementById('view_estado').textContent = capitalizar(reserva.estado);
                        document.getElementById('view_total').textContent = '$' + reserva.total.toFixed(2);
                        document.getElementById('view_observaciones').textContent = reserva.observaciones || 'Sin observaciones';
                        
                        document.getElementById('viewReservationModal').style.display = 'flex';
                    } else {
                        alert('Error al cargar los datos: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los detalles de la reserva');
                });
        }
        
        // Cerrar modal de detalles
        function closeViewModal() {
            document.getElementById('viewReservationModal').style.display = 'none';
        }
        
        // Confirmar reserva
        function confirmarReserva(id) {
            if (confirm('¿Está seguro de confirmar esta reserva?')) {
                cambiarEstado(id, 'confirmada');
            }
        }
        
        // Cancelar reserva
        function cancelarReserva(id) {
            if (confirm('¿Está seguro de cancelar esta reserva?')) {
                cambiarEstado(id, 'cancelada');
            }
        }
        
        // Cambiar estado de reserva
        function cambiarEstado(id, nuevoEstado) {
            const formData = new FormData();
            formData.append('action', 'cambiar_estado');
            formData.append('id_reserva', id);
            formData.append('estado', nuevoEstado);
            
            fetch('../includes/reservas_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    cargarReservas();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cambiar el estado');
            });
        }
        
        // Funciones auxiliares
        function formatearFecha(fecha) {
            const [year, month, day] = fecha.split('-');
            return `${day}/${month}/${year}`;
        }
        
        function capitalizar(texto) {
            return texto.charAt(0).toUpperCase() + texto.slice(1);
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal1 = document.getElementById('addReservationModal');
            const modal2 = document.getElementById('viewReservationModal');
            if (event.target === modal1) {
                closeReservationModal();
            }
            if (event.target === modal2) {
                closeViewModal();
            }
        }
    </script>
</body>
</html>