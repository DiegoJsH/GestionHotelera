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