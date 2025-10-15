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
                <h1>Gestión de Reservas</h1>
                <button class="btn btn-primary" onclick="openModal('addReservationModal')">+ Nueva Reserva</button>
            </header>

            <div class="content-area">
                <div class="filters">
                    <input type="date" class="filter-input" placeholder="Fecha de entrada">
                    <input type="date" class="filter-input" placeholder="Fecha de salida">
                    <select class="filter-select">
                        <option>Todas las reservas</option>
                        <option>Confirmadas</option>
                        <option>Pendientes</option>
                        <option>Canceladas</option>
                    </select>
                    <input type="text" class="filter-input" placeholder="Buscar por huésped...">
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
                                <th>Estado</th>
                                <th>Total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#001</td>
                                <td>Juan Pérez</td>
                                <td>101</td>
                                <td>2024-01-15</td>
                                <td>2024-01-18</td>
                                <td><span class="status confirmed">Confirmada</span></td>
                                <td>$240</td>
                                <td>
                                    <button class="btn btn-small btn-primary">Ver</button>
                                    <button class="btn btn-small btn-secondary">Editar</button>
                                </td>
                            </tr>
                            <tr>
                                <td>#002</td>
                                <td>María García</td>
                                <td>205</td>
                                <td>2024-01-16</td>
                                <td>2024-01-20</td>
                                <td><span class="status pending">Pendiente</span></td>
                                <td>$480</td>
                                <td>
                                    <button class="btn btn-small btn-primary">Ver</button>
                                    <button class="btn btn-small btn-success">Confirmar</button>
                                </td>
                            </tr>
                            <tr>
                                <td>#003</td>
                                <td>Carlos López</td>
                                <td>301</td>
                                <td>2024-01-14</td>
                                <td>2024-01-17</td>
                                <td><span class="status cancelled">Cancelada</span></td>
                                <td>$600</td>
                                <td>
                                    <button class="btn btn-small btn-primary">Ver</button>
                                    <button class="btn btn-small btn-warning">Restaurar</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal para nueva reserva -->
    <div id="addReservationModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2>Nueva Reserva</h2>
                <span class="close" onclick="closeModal('addReservationModal')">&times;</span>
            </div>
            <form class="modal-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Huésped</label>
                        <select required>
                            <option>Seleccionar huésped...</option>
                            <option>Juan Pérez</option>
                            <option>María García</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Habitación</label>
                        <select required>
                            <option>Seleccionar habitación...</option>
                            <option>101 - Individual</option>
                            <option>201 - Suite</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha de Entrada</label>
                        <input type="date" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha de Salida</label>
                        <input type="date" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea rows="3"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addReservationModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Reserva</button>
                </div>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
