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
                <h1>Reportes y Estadísticas</h1>
                <div class="report-filters">
                    <select class="filter-select">
                        <option>Último mes</option>
                        <option>Últimos 3 meses</option>
                        <option>Último año</option>
                    </select>
                    <button class="btn btn-primary">Exportar PDF</button>
                </div>
            </header>

            <div class="content-area">
                <div class="reports-grid">
                    <div class="report-card">
                        <h3>Ocupación Mensual</h3>
                        <div class="chart-placeholder">
                            <div class="chart-bar" style="height: 60%">Ene</div>
                            <div class="chart-bar" style="height: 75%">Feb</div>
                            <div class="chart-bar" style="height: 80%">Mar</div>
                            <div class="chart-bar" style="height: 65%">Abr</div>
                            <div class="chart-bar" style="height: 90%">May</div>
                            <div class="chart-bar" style="height: 85%">Jun</div>
                        </div>
                        <p class="chart-info">Promedio: 76% de ocupación</p>
                    </div>

                    <div class="report-card">
                        <h3>Ingresos por Mes</h3>
                        <div class="revenue-chart">
                            <div class="revenue-item">
                                <span>Enero</span>
                                <span>$15,240</span>
                            </div>
                            <div class="revenue-item">
                                <span>Febrero</span>
                                <span>$18,750</span>
                            </div>
                            <div class="revenue-item">
                                <span>Marzo</span>
                                <span>$22,100</span>
                            </div>
                            <div class="revenue-item">
                                <span>Abril</span>
                                <span>$19,850</span>
                            </div>
                        </div>
                        <p class="chart-info">Total: $75,940</p>
                    </div>

                    <div class="report-card">
                        <h3>Tipos de Habitación Más Populares</h3>
                        <div class="popularity-chart">
                            <div class="popularity-item">
                                <span>Suite</span>
                                <div class="popularity-bar">
                                    <div class="popularity-fill" style="width: 85%"></div>
                                </div>
                                <span>85%</span>
                            </div>
                            <div class="popularity-item">
                                <span>Doble</span>
                                <div class="popularity-bar">
                                    <div class="popularity-fill" style="width: 70%"></div>
                                </div>
                                <span>70%</span>
                            </div>
                            <div class="popularity-item">
                                <span>Individual</span>
                                <div class="popularity-bar">
                                    <div class="popularity-fill" style="width: 45%"></div>
                                </div>
                                <span>45%</span>
                            </div>
                        </div>
                    </div>

                    <div class="report-card">
                        <h3>Estadísticas Generales</h3>
                        <div class="stats-list">
                            <div class="stat-item">
                                <span>Total Reservas:</span>
                                <strong>1,247</strong>
                            </div>
                            <div class="stat-item">
                                <span>Huéspedes Únicos:</span>
                                <strong>892</strong>
                            </div>
                            <div class="stat-item">
                                <span>Promedio Estancia:</span>
                                <strong>3.2 días</strong>
                            </div>
                            <div class="stat-item">
                                <span>Tasa Cancelación:</span>
                                <strong>8.5%</strong>
                            </div>
                            <div class="stat-item">
                                <span>Ingreso Promedio/Noche:</span>
                                <strong>$127</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detailed-reports">
                    <h2>Reportes Detallados</h2>
                    <div class="report-buttons">
                        <button class="btn btn-outline">Reporte de Ocupación</button>
                        <button class="btn btn-outline">Reporte de Ingresos</button>
                        <button class="btn btn-outline">Reporte de Huéspedes</button>
                        <button class="btn btn-outline">Reporte de Cancelaciones</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
</body>
</html>
