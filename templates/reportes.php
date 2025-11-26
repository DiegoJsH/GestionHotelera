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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
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
                    <select class="filter-select" id="periodoSelect" onchange="cargarReportes()">
                        <option value="mes">Último mes</option>
                        <option value="tres_meses">Últimos 3 meses</option>
                        <option value="año">Último año</option>
                        <option value="personalizado">Personalizado</option>
                    </select>
                    <button class="btn btn-primary" onclick="exportarPDF()">Exportar PDF</button>
                </div>

                <div class="report-filters" id="personalizadoFilters" style="display:none; margin-top:10px;">
                    <label style="margin-right:8px;">Rango personalizado:</label>
                    <input type="date" id="reportFechaInicio" class="filter-input" onchange="cargarReportes()">
                    <span style="margin:0 8px;">a</span>
                    <input type="date" id="reportFechaFin" class="filter-input" onchange="cargarReportes()">
                </div>
            </header>
            <div class="content-area">
                <div class="reports-grid">
                    <div class="report-card">
                        <h3>Ingresos por Mes</h3>
                        <div class="revenue-chart" id="ingresosChart">
                            <div class="no-data">Cargando datos...</div>
                        </div>
                        <p class="chart-info" id="totalIngresos">Total: $0.00</p>
                    </div>

                    <div class="report-card">
                        <h3>Estadísticas Generales</h3>
                        <div class="stats-list" id="estadisticasGenerales">
                            <div class="no-data">Cargando estadísticas...</div>
                        </div>
                    </div>
                </div>
                
                <!-- NUEVA SECCIÓN: Ingresos por Día -->
                <div class="section" style="margin-top: 30px;">
                    <h2> Ingresos por Día</h2>
                        
                        
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Día</th>
                                        <th>Reservas</th>
                                        <th>Ingresos</th>
                                    </tr>
                                </thead>
                                <tbody id="ingresosDiariosTable">
                                    <tr>
                                        <td colspan="5" class="no-data">Cargando datos...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    
                </div>
                
                <div class="section">
                    <h2>Habitaciones Más Reservadas</h2>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Habitación</th>
                                    <th>Piso</th>
                                    <th>Tipo</th>
                                    <th>Reservas</th>
                                    <th>Precio/Noche</th>
                                    <th>Ingresos Generados</th>
                                </tr>
                            </thead>
                            <tbody id="habitacionesPopularesTable">
                                <tr>
                                    <td colspan="7" class="no-data">Cargando datos...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sección de Gráfico de Barras -->
                <div class="section">
                    <h2>Tipo de Habitaciones Más Reservadas</h2>
                    <div class="chart-card">
                        <div class="chart-controls">
                            <label for="periodoGrafico">Período:</label>
                            <select id="periodoGrafico" class="filter-select">
                                <option value="diario">Hoy</option>
                                <option value="semanal">Esta Semana</option>
                                <option value="mensual" selected>Este Mes</option>
                                <option value="anual">Este Año</option>
                            </select>
                            
                            <label for="tipoGrafico">Tipo:</label>
                            <select id="tipoGrafico" class="filter-select">
                                <option value="bar" selected>Barras</option>
                                <option value="line">Líneas</option>
                                <option value="pie">Circular</option>
                                <option value="doughnut">Dona</option>
                            </select>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="graficoHabitaciones"></canvas>
                        </div>
                        <div class="chart-legend" id="chartLegend">
                            <p class="no-data">Cargando datos del gráfico...</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        let reportesData = null;
        let chartInstance = null;
        
        // Cargar reportes al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarReportes();
            cargarGraficoHabitaciones();
            
            // Event listener para cambiar el período del gráfico
            document.getElementById('periodoGrafico').addEventListener('change', function() {
                cargarGraficoHabitaciones();
            });
            
            // Event listener para cambiar el tipo de gráfico
            document.getElementById('tipoGrafico').addEventListener('change', function() {
                cargarGraficoHabitaciones();
            });
            
            // Mostrar/ocultar filtros personalizados cuando se selecciona 'personalizado'
            const periodoSelect = document.getElementById('periodoSelect');
            const personalizadoFilters = document.getElementById('personalizadoFilters');
            periodoSelect.addEventListener('change', function() {
                if (this.value === 'personalizado') {
                    personalizadoFilters.style.display = 'flex';
                    // establecer min/max por defecto si están vacíos
                    const hoy = new Date();
                    hoy.setMinutes(hoy.getMinutes() - hoy.getTimezoneOffset());
                    const fechaHoy = hoy.toISOString().split('T')[0];
                    const inicio = document.getElementById('reportFechaInicio');
                    const fin = document.getElementById('reportFechaFin');
                    if (!inicio.value) inicio.value = fechaHoy;
                    if (!fin.value) fin.value = fechaHoy;
                } else {
                    personalizadoFilters.style.display = 'none';
                }
                // recargar reportes al cambiar el periodo
                cargarReportes();
            });
        });
        
        // Cargar datos del gráfico de habitaciones
        function cargarGraficoHabitaciones() {
            const periodo = document.getElementById('periodoGrafico').value;
            
            fetch(`../includes/reportes_handler.php?accion=grafico&periodo_grafico=${periodo}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.datos.labels.length > 0) {
                        renderizarGrafico(data.datos);
                    } else {
                        document.getElementById('chartLegend').innerHTML = '<p class="no-data">No hay datos</p>';
                        if (chartInstance) chartInstance.destroy();
                    }
                });
        }
        
        // Renderizar el gráfico con Chart.js
        function renderizarGrafico(datos) {
            const colores = ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#43e97b'];
            const colors = datos.labels.map((_, i) => colores[i % colores.length]);
            const tipoGrafico = document.getElementById('tipoGrafico').value;
            
            if (chartInstance) chartInstance.destroy();
            
            // Configuración específica según tipo de gráfico
            const config = {
                type: tipoGrafico,
                data: {
                    labels: datos.labels,
                    datasets: [{
                        label: 'Reservas',
                        data: datos.valores,
                        backgroundColor: colors,
                        borderColor: tipoGrafico === 'line' ? colors : undefined,
                        borderWidth: tipoGrafico === 'line' ? 2 : undefined,
                        borderRadius: tipoGrafico === 'bar' ? 6 : undefined,
                        tension: tipoGrafico === 'line' ? 0.4 : undefined
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { 
                            display: tipoGrafico === 'pie' || tipoGrafico === 'doughnut',
                            position: 'bottom'
                        } 
                    }
                }
            };
            
            // Agregar escalas solo para gráficos de barras y líneas
            if (tipoGrafico === 'bar' || tipoGrafico === 'line') {
                config.options.scales = { 
                    y: { beginAtZero: true, ticks: { stepSize: 1 } } 
                };
            }
            
            chartInstance = new Chart(document.getElementById('graficoHabitaciones'), config);
            
            // Generar leyenda
            const total = datos.valores.reduce((a, b) => a + b, 0);
            document.getElementById('chartLegend').innerHTML = datos.labels.map((label, i) => `
                <div class="legend-item">
                    <span class="legend-color" style="background: ${colors[i]}"></span>
                    <span class="legend-text">${label}:</span>
                    <span class="legend-value">${datos.valores[i]} (${((datos.valores[i]/total)*100).toFixed(1)}%)</span>
                </div>
            `).join('');
        }
        
        // Cargar reportes según el periodo seleccionado
        function cargarReportes() {
            const periodo = document.getElementById('periodoSelect').value;
            let url = `../includes/reportes_handler.php?periodo=${periodo}`;

            // Si el usuario eligió un periodo personalizado, enviar fechas
            if (periodo === 'personalizado') {
                const fechaInicio = document.getElementById('reportFechaInicio').value;
                const fechaFin = document.getElementById('reportFechaFin').value;
                
                if (new Date(fechaInicio) > new Date(fechaFin)) {
                    alert('La fecha de inicio debe ser anterior o igual a la fecha de fin.');
                    return;
                }

                url += `&fecha_inicio=${encodeURIComponent(fechaInicio)}&fecha_fin=${encodeURIComponent(fechaFin)}`;
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        reportesData = data;
                        mostrarIngresos(data.ingresos);
                        mostrarIngresosDiarios(data.ingresos_diarios);
                        mostrarEstadisticas(data.estadisticas);
                        mostrarHabitacionesPopulares(data.habitaciones_populares);
                    } else {
                        console.error('Error al cargar reportes:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('ingresosChart').innerHTML = 
                        '<div class="no-data">Error al cargar los datos</div>';
                    document.getElementById('estadisticasGenerales').innerHTML = 
                        '<div class="no-data">Error al cargar las estadísticas</div>';
                });
        }
        
        // Mostrar ingresos por mes
        function mostrarIngresos(ingresos) {
            const chart = document.getElementById('ingresosChart');
            const totalElement = document.getElementById('totalIngresos');
            
            if (ingresos.ingresos.length === 0) {
                chart.innerHTML = '<div class="no-data">No hay datos de ingresos para este periodo</div>';
                totalElement.textContent = 'Total: $0.00';
                return;
            }
            
            chart.innerHTML = '';
            ingresos.ingresos.forEach(item => {
                const div = document.createElement('div');
                div.className = 'revenue-item';
                div.innerHTML = `
                    <span>${item.mes}</span>
                    <span>$${item.total.toFixed(2)}</span>
                `;
                chart.appendChild(div);
            });
            
            totalElement.textContent = `Total: $${ingresos.total_general.toFixed(2)}`;
        }
        
        // NUEVA FUNCIÓN: Mostrar ingresos por día
        function mostrarIngresosDiarios(ingresosDiarios) {
            const tbody = document.getElementById('ingresosDiariosTable');
            
            
            tbody.innerHTML = '';
            ingresosDiarios.ingresos.forEach((dia) => {
                const porcentaje = ingresosDiarios.total_periodo > 0 
                    ? ((dia.total_ingresos / ingresosDiarios.total_periodo) * 100).toFixed(1)
                    : 0;
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><strong>${dia.fecha_formateada}</strong></td>
                    <td>${dia.dia_semana}</td>
                    <td><span style="color: black; padding: 4px 10px; border-radius: 12px; font-weight: 600;">${dia.num_reservas}</span></td>
                    <td><strong style="color: #27ae60; font-size: 15px;">$${dia.total_ingresos.toFixed(2)}</strong></td>
                `;
                tbody.appendChild(row);
            });
        }
        
        // Mostrar estadísticas generales
        function mostrarEstadisticas(estadisticas) {
            const container = document.getElementById('estadisticasGenerales');
            
            container.innerHTML = `
                <div class="stat-item">
                    <span>Total Reservas:</span>
                    <strong>${estadisticas.total_reservas}</strong>
                </div>
                <div class="stat-item">
                    <span>Huéspedes Únicos:</span>
                    <strong>${estadisticas.huespedes_unicos}</strong>
                </div>
                <div class="stat-item">
                    <span>Promedio Estancia:</span>
                    <strong>${estadisticas.promedio_estancia} días</strong>
                </div>
                <div class="stat-item">
                    <span>Tasa Cancelación:</span>
                    <strong>${estadisticas.tasa_cancelacion}%</strong>
                </div>
                <div class="stat-item">
                    <span>Ingreso Promedio/Noche:</span>
                    <strong>$${estadisticas.ingreso_promedio_noche.toFixed(2)}</strong>
                </div>
            `;
        }
        
        // Mostrar habitaciones más reservadas
        function mostrarHabitacionesPopulares(habitaciones) {
            const tbody = document.getElementById('habitacionesPopularesTable');
            
            if (!habitaciones || habitaciones.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="no-data">No hay datos de reservas en este periodo</td></tr>';
                return;
            }
            
            tbody.innerHTML = '';
            habitaciones.forEach((hab, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${index + 1}</td>
                    <td><strong>${hab.numero_habitacion}</strong></td>
                    <td>Piso ${hab.piso}</td>
                    <td>${hab.tipo}</td>
                    <td><strong>${hab.total_reservas}</strong> reservas</td>
                    <td>$${hab.precio_por_noche.toFixed(2)}</td>
                    <td><strong style="color: #27ae60;">$${hab.ingresos_generados.toFixed(2)}</strong></td>
                `;
                tbody.appendChild(row);
            });
        }
        
        // Exportar a PDF
        function exportarPDF() {
            if (!reportesData) {
                alert('No hay datos para exportar');
                return;
            }
            
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Título
            doc.setFontSize(18);
            doc.text('Reporte de Gestión Hotelera', 105, 20, { align: 'center' });
            
            // Fecha del reporte
            doc.setFontSize(10);
            const fechaActual = new Date().toLocaleDateString('es-ES');
            doc.text(`Fecha: ${fechaActual}`, 105, 30, { align: 'center' });
            
            // Periodo
            const periodo = document.getElementById('periodoSelect');
            const periodoTexto = periodo.options[periodo.selectedIndex].text;
            doc.text(`Periodo: ${periodoTexto}`, 105, 36, { align: 'center' });
            
            // Línea separadora
            doc.line(20, 40, 190, 40);
            
            // Sección: Ingresos por Mes
            doc.setFontSize(14);
            doc.text('Ingresos por Mes', 20, 50);
            
            doc.setFontSize(10);
            let y = 60;
            
            if (reportesData.ingresos.ingresos.length > 0) {
                reportesData.ingresos.ingresos.forEach(item => {
                    doc.text(`${item.mes}:`, 30, y);
                    doc.text(`$${item.total.toFixed(2)}`, 120, y);
                    y += 7;
                });
                
                doc.setFontSize(12);
                y += 5;
                doc.text(`Total General: $${reportesData.ingresos.total_general.toFixed(2)}`, 30, y);
            } else {
                doc.text('No hay datos de ingresos para este periodo', 30, y);
            }
            
            // Línea separadora
            y += 10;
            doc.line(20, y, 190, y);
            
            // Sección: Estadísticas Generales
            y += 10;
            doc.setFontSize(14);
            doc.text('Estadísticas Generales', 20, y);
            
            y += 10;
            doc.setFontSize(10);
            
            const stats = reportesData.estadisticas;
            doc.text(`Total de Reservas: ${stats.total_reservas}`, 30, y);
            y += 7;
            doc.text(`Huéspedes Únicos: ${stats.huespedes_unicos}`, 30, y);
            y += 7;
            doc.text(`Promedio de Estancia: ${stats.promedio_estancia} días`, 30, y);
            y += 7;
            doc.text(`Tasa de Cancelación: ${stats.tasa_cancelacion}%`, 30, y);
            y += 7;
            doc.text(`Ingreso Promedio por Noche: $${stats.ingreso_promedio_noche.toFixed(2)}`, 30, y);
            
            // Línea separadora
            y += 10;
            doc.line(20, y, 190, y);
            
            // Sección: Habitaciones Más Reservadas
            y += 10;
            doc.setFontSize(14);
            doc.text('Habitaciones Más Reservadas', 20, y);
            
            y += 10;
            doc.setFontSize(10);
            
            if (reportesData.habitaciones_populares && reportesData.habitaciones_populares.length > 0) {
                // Encabezados
                doc.setFont(undefined, 'bold');
                doc.text('Hab', 25, y);
                doc.text('Piso', 45, y);
                doc.text('Tipo', 65, y);
                doc.text('Reservas', 95, y);
                doc.text('Ingresos', 135, y);
                
                y += 7;
                doc.setFont(undefined, 'normal');
                
                // Máximo 10 habitaciones o las que haya
                const maxHab = Math.min(reportesData.habitaciones_populares.length, 10);
                
                for (let i = 0; i < maxHab; i++) {
                    const hab = reportesData.habitaciones_populares[i];
                    
                    // Si llegamos al final de la página, crear una nueva
                    if (y > 270) {
                        doc.addPage();
                        y = 20;
                    }
                    
                    doc.text(`${hab.numero_habitacion}`, 25, y);
                    doc.text(`${hab.piso}`, 45, y);
                    doc.text(`${hab.tipo}`, 65, y);
                    doc.text(`${hab.total_reservas}`, 95, y);
                    doc.text(`$${hab.ingresos_generados.toFixed(2)}`, 135, y);
                    
                    y += 7;
                }
            } else {
                doc.text('No hay datos de habitaciones reservadas en este periodo', 30, y);
            }
            
            // Pie de página
            doc.setFontSize(8);
            doc.text('Sistema de Gestión Hotelera', 105, 280, { align: 'center' });
            
            // Guardar el PDF
            const nombreArchivo = `reporte_hotelera_${new Date().toISOString().split('T')[0]}.pdf`;
            doc.save(nombreArchivo);
            
            alert('PDF generado exitosamente');
        }
    </script>
</body>
</html>