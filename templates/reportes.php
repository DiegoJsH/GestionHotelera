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
                    </select>
                    <button class="btn btn-primary" onclick="exportarPDF()">Exportar PDF</button>
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
            </div>
        </main>
    </div>

    <script>
        let reportesData = null;
        
        // Cargar reportes al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarReportes();
        });
        
        // Cargar reportes según el periodo seleccionado
        function cargarReportes() {
            const periodo = document.getElementById('periodoSelect').value;
            
            fetch(`../includes/reportes_handler.php?periodo=${periodo}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        reportesData = data;
                        mostrarIngresos(data.ingresos);
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