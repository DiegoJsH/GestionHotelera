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
                <button class="btn btn-primary" onclick="openModal('addGuestModal')">+ Nuevo Huésped</button>
            </header>

            <div class="content-area">
                <div class="filters">
                    <input type="text" class="filter-input" placeholder="Buscar por nombre...">
                    <input type="text" class="filter-input" placeholder="Buscar por documento...">
                    <select class="filter-select">
                        <option>Todos los estados</option>
                        <option>Activos</option>
                        <option>Check-out</option>
                    </select>
                </div>

                <div class="guests-grid">
                    <div class="guest-card">
                        <div class="guest-avatar">JP</div>
                        <div class="guest-info">
                            <h3>Juan Pérez</h3>
                            <p><strong>Documento:</strong> 12345678</p>
                            <p><strong>Email:</strong> juan@email.com</p>
                            <p><strong>Teléfono:</strong> +1234567890</p>
                            <p><strong>Habitación:</strong> 101</p>
                            <span class="guest-status active">Activo</span>
                        </div>
                        <div class="guest-actions">
                            <button class="btn btn-small btn-primary">Ver Historial</button>
                            <button class="btn btn-small btn-secondary">Editar</button>
                        </div>
                    </div>

                    <div class="guest-card">
                        <div class="guest-avatar">MG</div>
                        <div class="guest-info">
                            <h3>María García</h3>
                            <p><strong>Documento:</strong> 87654321</p>
                            <p><strong>Email:</strong> maria@email.com</p>
                            <p><strong>Teléfono:</strong> +0987654321</p>
                            <p><strong>Última visita:</strong> 2024-01-10</p>
                            <span class="guest-status inactive">Check-out</span>
                        </div>
                        <div class="guest-actions">
                            <button class="btn btn-small btn-primary">Ver Historial</button>
                            <button class="btn btn-small btn-success">Nueva Reserva</button>
                        </div>
                    </div>

                    <div class="guest-card">
                        <div class="guest-avatar">CL</div>
                        <div class="guest-info">
                            <h3>Carlos López</h3>
                            <p><strong>Documento:</strong> 11223344</p>
                            <p><strong>Email:</strong> carlos@email.com</p>
                            <p><strong>Teléfono:</strong> +1122334455</p>
                            <p><strong>Habitación:</strong> 205</p>
                            <span class="guest-status active">Activo</span>
                        </div>
                        <div class="guest-actions">
                            <button class="btn btn-small btn-primary">Ver Historial</button>
                            <button class="btn btn-small btn-secondary">Editar</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal para nuevo huésped -->
    <div id="addGuestModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2>Nuevo Huésped</h2>
                <span class="close" onclick="closeModal('addGuestModal')">&times;</span>
            </div>
            <form class="modal-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" required>
                    </div>
                    <div class="form-group">
                        <label>Apellido</label>
                        <input type="text" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Documento</label>
                        <input type="text" required>
                    </div>
                    <div class="form-group">
                        <label>Tipo de Documento</label>
                        <select required>
                            <option>DNI</option>
                            <option>Pasaporte</option>
                            <option>Cédula</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" required>
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="tel" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Dirección</label>
                    <textarea rows="2"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addGuestModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Huésped</button>
                </div>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
