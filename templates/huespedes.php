<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

include "../includes/db_connection.php";

// Obtener todos los huéspedes
$sql = "SELECT * FROM huespedes ORDER BY fecha_hora_registro DESC";
$result = $conn->query($sql);
$huespedes = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $huespedes[] = $row;
    }
}

// Función para obtener iniciales del nombre
function getIniciales($nombre, $apellido) {
    $inicial_nombre = mb_substr($nombre, 0, 1, 'UTF-8');
    $inicial_apellido = mb_substr($apellido, 0, 1, 'UTF-8');
    return strtoupper($inicial_nombre . $inicial_apellido);
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
                <h1>Gestión de Huéspedes</h1>
                <button class="btn btn-primary" onclick="openAddModal()">+ Nuevo Huésped</button>
            </header>

            <div class="content-area">
                <div class="filters">
                    <input type="text" class="filter-input" id="busquedaInput" 
                           placeholder="Buscar por nombre, documento o email..." 
                           onkeyup="buscarHuespedes()">
                </div>

                <div class="guests-grid" id="guestsGrid">
                    <?php if (empty($huespedes)): ?>
                        <div class="no-data">
                            <p>No hay huéspedes registrados. ¡Agrega el primero!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($huespedes as $huesped): ?>
                            <div class="guest-card" data-id="<?php echo $huesped['id_huesped']; ?>"
                                 data-nombre="<?php echo htmlspecialchars($huesped['nombre']); ?>"
                                 data-apellido="<?php echo htmlspecialchars($huesped['apellido']); ?>"
                                 data-documento="<?php echo htmlspecialchars($huesped['documento']); ?>"
                                 data-email="<?php echo htmlspecialchars($huesped['email']); ?>">
                                <div class="guest-avatar">
                                    <?php echo getIniciales($huesped['nombre'], $huesped['apellido']); ?>
                                </div>
                                <div class="guest-info">
                                    <h3><?php echo htmlspecialchars($huesped['nombre'] . ' ' . $huesped['apellido']); ?></h3>
                                    <p><strong>Documento:</strong> <?php echo htmlspecialchars($huesped['tipo_documento'] . ' ' . $huesped['documento']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($huesped['email']); ?></p>
                                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($huesped['telefono']); ?></p>
                                    <?php if (!empty($huesped['direccion'])): ?>
                                        <p><strong>Dirección:</strong> <?php echo htmlspecialchars($huesped['direccion']); ?></p>
                                    <?php endif; ?>
                                    <p><strong>Registrado:</strong> <?php echo date('d/m/Y', strtotime($huesped['fecha_hora_registro'])); ?></p>
                                </div>
                                <div class="guest-actions">
                                    <?php $id_huesped = $huesped['id_huesped']; ?>
                                    <button class="btn btn-small btn-secondary" 
                                            onclick="openEditModal(<?php echo $id_huesped; ?>)">
                                        Editar
                                    </button>
                                    <button class="btn btn-small btn-danger" 
                                            onclick="eliminarHuesped(<?php echo $id_huesped; ?>)">
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

    <!-- Modal para agregar/editar huésped -->
    <div id="guestModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2 id="modalTitle">Nuevo Huésped</h2>
                <span class="close" onclick="closeGuestModal()">&times;</span>
            </div>
            <form id="guestForm" class="modal-form" onsubmit="guardarHuesped(event)">
                <input type="hidden" id="formAction" name="action" value="agregar">
                <input type="hidden" id="id_huesped" name="id_huesped" value="">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre *</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Apellido *</label>
                        <input type="text" id="apellido" name="apellido" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tipo de Documento *</label>
                        <select id="tipo_documento" name="tipo_documento" required>
                            <option value="">Seleccione un tipo</option>
                            <option value="DNI">DNI</option>
                            <option value="Pasaporte">Pasaporte</option>
                            <option value="Cédula">Cédula</option>
                            <option value="RUT">RUT</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Número de Documento *</label>
                        <input type="text" id="documento" name="documento" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Teléfono *</label>
                        <input type="tel" id="telefono" name="telefono" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Dirección</label>
                    <textarea id="direccion" name="direccion" rows="2"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeGuestModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Buscar huéspedes en tiempo real
        function buscarHuespedes() {
            const busqueda = document.getElementById('busquedaInput').value.toLowerCase();
            const cards = document.querySelectorAll('.guest-card');
            
            cards.forEach(card => {
                const nombre = card.getAttribute('data-nombre').toLowerCase();
                const apellido = card.getAttribute('data-apellido').toLowerCase();
                const documento = card.getAttribute('data-documento').toLowerCase();
                const email = card.getAttribute('data-email').toLowerCase();
                
                const texto = `${nombre} ${apellido} ${documento} ${email}`;
                
                if (texto.includes(busqueda)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Abrir modal para agregar
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Nuevo Huésped';
            document.getElementById('formAction').value = 'agregar';
            document.getElementById('guestForm').reset();
            document.getElementById('id_huesped').value = '';
            document.getElementById('guestModal').style.display = 'flex';
        }
        
        // Abrir modal para editar
        function openEditModal(id) {
            document.getElementById('modalTitle').textContent = 'Editar Huésped';
            document.getElementById('formAction').value = 'actualizar';
            
            // Obtener datos del huésped
            fetch(`../includes/huespedes_handler.php?action=obtener&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const huesped = data.data;
                        document.getElementById('id_huesped').value = huesped.id_huesped;
                        document.getElementById('nombre').value = huesped.nombre;
                        document.getElementById('apellido').value = huesped.apellido;
                        document.getElementById('tipo_documento').value = huesped.tipo_documento;
                        document.getElementById('documento').value = huesped.documento;
                        document.getElementById('email').value = huesped.email;
                        document.getElementById('telefono').value = huesped.telefono;
                        document.getElementById('direccion').value = huesped.direccion || '';
                        document.getElementById('guestModal').style.display = 'flex';
                    } else {
                        alert('Error al cargar los datos: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los datos del huésped');
                });
        }
        
        // Cerrar modal
        function closeGuestModal() {
            document.getElementById('guestModal').style.display = 'none';
            document.getElementById('guestForm').reset();
        }
        
        // Guardar huésped (agregar o actualizar)
        function guardarHuesped(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            
            fetch('../includes/huespedes_handler.php', {
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
        
        // Eliminar huésped
        function eliminarHuesped(id) {
            if (confirm('¿Está seguro de eliminar este huésped? Esta acción no se puede deshacer.')) {
                const formData = new FormData();
                formData.append('action', 'eliminar');
                formData.append('id_huesped', id);
                
                fetch('../includes/huespedes_handler.php', {
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
                    alert('Error al eliminar el huésped');
                });
            }
        }
        
        // Cerrar modal al hacer clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('guestModal');
            if (event.target === modal) {
                closeGuestModal();
            }
        }
    </script>
</body>
</html>
