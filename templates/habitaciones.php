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
                <button class="btn btn-primary" onclick="openModal('addRoomModal')">+ Nueva Habitación</button>
            </header>

            <div class="content-area">
                <div class="filters">
                    <select class="filter-select">
                        <option>Todas las habitaciones</option>
                        <option>Disponibles</option>
                        <option>Ocupadas</option>
                        <option>Mantenimiento</option>
                    </select>
                    <select class="filter-select">
                        <option>Todos los tipos</option>
                        <option>Individual</option>
                        <option>Doble</option>
                        <option>Suite</option>
                    </select>
                </div>

                <div class="rooms-grid">
                    <div class="room-card available">
                        <div class="room-header">
                            <h3>Habitación 101</h3>
                            <span class="room-status">Disponible</span>
                        </div>
                        <div class="room-info">
                            <p><strong>Tipo:</strong> Individual</p>
                            <p><strong>Precio:</strong> $80/noche</p>
                            <p><strong>Capacidad:</strong> 1 persona</p>
                        </div>
                        <div class="room-actions">
                            <button class="btn btn-small btn-primary">Reservar</button>
                            <button class="btn btn-small btn-secondary">Editar</button>
                        </div>
                    </div>

                    <div class="room-card occupied">
                        <div class="room-header">
                            <h3>Habitación 102</h3>
                            <span class="room-status">Ocupada</span>
                        </div>
                        <div class="room-info">
                            <p><strong>Tipo:</strong> Doble</p>
                            <p><strong>Precio:</strong> $120/noche</p>
                            <p><strong>Huésped:</strong> Ana Martínez</p>
                        </div>
                        <div class="room-actions">
                            <button class="btn btn-small btn-warning">Check-out</button>
                            <button class="btn btn-small btn-secondary">Ver Detalles</button>
                        </div>
                    </div>

                    <div class="room-card available">
                        <div class="room-header">
                            <h3>Habitación 201</h3>
                            <span class="room-status">Disponible</span>
                        </div>
                        <div class="room-info">
                            <p><strong>Tipo:</strong> Suite</p>
                            <p><strong>Precio:</strong> $200/noche</p>
                            <p><strong>Capacidad:</strong> 4 personas</p>
                        </div>
                        <div class="room-actions">
                            <button class="btn btn-small btn-primary">Reservar</button>
                            <button class="btn btn-small btn-secondary">Editar</button>
                        </div>
                    </div>

                    <div class="room-card maintenance">
                        <div class="room-header">
                            <h3>Habitación 202</h3>
                            <span class="room-status">Mantenimiento</span>
                        </div>
                        <div class="room-info">
                            <p><strong>Tipo:</strong> Doble</p>
                            <p><strong>Precio:</strong> $120/noche</p>
                            <p><strong>Estado:</strong> Reparación AC</p>
                        </div>
                        <div class="room-actions">
                            <button class="btn btn-small btn-success">Marcar Lista</button>
                            <button class="btn btn-small btn-secondary">Editar</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal para agregar habitación -->
    <div id="addRoomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Nueva Habitación</h2>
                <span class="close" onclick="closeModal('addRoomModal')">&times;</span>
            </div>
            <form class="modal-form">
                <div class="form-group">
                    <label>Número de Habitación</label>
                    <input type="text" required>
                </div>
                <div class="form-group">
                    <label>Tipo</label>
                    <select required>
                        <option>Individual</option>
                        <option>Doble</option>
                        <option>Suite</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Precio por Noche</label>
                    <input type="number" required>
                </div>
                <div class="form-group">
                    <label>Capacidad</label>
                    <input type="number" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addRoomModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
