<!-- application/views/dashboard/index.php -->
<div class="pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-speedometer2"></i> Dashboard</h1>
    <div class="d-flex justify-content-between">
        <p class="lead">Monitoreo de cotizaciones de dólares en tiempo real.</p>
        <div>
            <span id="ultimo-refresco" class="badge bg-secondary me-2">Última actualización: <?= date('H:i:s') ?></span>
            <button id="btn-refresh" class="btn btn-sm btn-primary">
                <i class="bi bi-arrow-clockwise"></i> Actualizar ahora
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-graph-up"></i> Cotizaciones en tiempo real
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chart-comparacion"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-currency-dollar"></i> Últimas Cotizaciones
                </h5>
            </div>
            <div class="card-body" id="tabla-cotizaciones-container">
                <div class="table-responsive">
                    <!-- La tabla se reemplazará mediante AJAX -->
                    <?php $this->load->view('dashboard/partials/tabla_cotizaciones', ['ultimas_cotizaciones' => $ultimas_cotizaciones, 'cocos' => $cocos]); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cotizacionesChart = null;
let actualizacionAutomatica = true;
let intervalId = null;
const INTERVALO_ACTUALIZACION = 60000; // 30 segundos

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar gráfico de cotizaciones
    inicializarGrafico();
    
    // Configurar actualización automática
    iniciarActualizacionAutomatica();
    
    // Botón de actualización manual
    document.getElementById('btn-refresh').addEventListener('click', function() {
        actualizarDashboard();
    });
});

function inicializarGrafico() {
    // Mostrar mensaje de carga
    const ctx = document.getElementById('chart-comparacion').getContext('2d');
    ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
    ctx.font = '16px Arial';
    ctx.fillStyle = '#666';
    ctx.textAlign = 'center';
    ctx.fillText('Cargando datos...', ctx.canvas.width / 2, ctx.canvas.height / 2);
    
    // Realizar la petición AJAX
    fetch('<?= site_url('dashboard/ajax_chart_data') ?>')
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);
            
            // Verificar si hay datos
            if (!data.labels || data.labels.length === 0 || !data.datasets || data.datasets.length === 0) {
                throw new Error('No hay datos suficientes para mostrar el gráfico');
            }
            
            // Resaltar el dólar Cocos haciéndolo más grueso, con borde punteado y z-index más alto
            data.datasets.forEach(dataset => {
                if (dataset.label.includes('Cocos')) {
                    dataset.borderWidth = 4; // Línea más gruesa
                    dataset.borderDash = []; // Sin línea punteada
                    dataset.zIndex = 10; // Traer al frente
                    dataset.borderColor = 'rgba(255, 0, 0, 1)'; // Cambiar a rojo
                    dataset.backgroundColor = 'rgba(255, 0, 0, 0.2)';
                    dataset.pointRadius = 4; // Puntos más grandes
                    dataset.pointHoverRadius = 6;
                }
            });
            
            // Si ya existe un gráfico, destruirlo
            if (cotizacionesChart) {
                cotizacionesChart.destroy();
            }
            
            // Crear un nuevo gráfico
            cotizacionesChart = new Chart(ctx, {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                usePointStyle: true,
                                generateLabels: function(chart) {
                                    const originalLabels = Chart.defaults.plugins.legend.labels.generateLabels(chart);
                                    // Poner Cocos primero en la leyenda
                                    originalLabels.sort((a, b) => {
                                        const aIsCocos = a.text.includes('Cocos');
                                        const bIsCocos = b.text.includes('Cocos');
                                        return bIsCocos - aIsCocos;
                                    });
                                    return originalLabels;
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += '$' + context.parsed.y.toLocaleString('es-AR');
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: false,
                            grace: '10%', // Añadir espacio extra arriba y abajo
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString('es-AR');
                                }
                            },
                            // Usar suggestedMin y suggestedMax para dar margen
                            suggestedMin: function() {
                                // Encontrar el valor mínimo y restarle un 5%
                                const minValues = data.datasets.map(dataset => 
                                    Math.min(...dataset.data.filter(val => val !== null && val !== undefined))
                                );
                                const globalMin = Math.min(...minValues);
                                return globalMin * 0.99;
                            }(),
                            suggestedMax: function() {
                                // Encontrar el valor máximo y sumarle un 5%
                                const maxValues = data.datasets.map(dataset => 
                                    Math.max(...dataset.data.filter(val => val !== null && val !== undefined))
                                );
                                const globalMax = Math.max(...maxValues);
                                return globalMax * 1.01;
                            }()
                        }
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error al cargar datos del gráfico:', error);
            mostrarErrorGrafico(error.message);
        });
}

function actualizarTablaCotizaciones() {
    fetch('<?= site_url('dashboard/ajax_cotizaciones') ?>')
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Actualizar el contenido de la tabla
            document.getElementById('tabla-cotizaciones-container').innerHTML = data.html;
        })
        .catch(error => {
            console.error('Error al actualizar tabla de cotizaciones:', error);
            document.getElementById('tabla-cotizaciones-container').innerHTML = '<div class="alert alert-danger">Error al cargar datos: ' + error.message + '</div>';
        });
}

function actualizarDashboard() {
    // Actualizar gráfico
    inicializarGrafico();
    
    // Actualizar tabla de cotizaciones
    actualizarTablaCotizaciones();
    
    // Actualizar timestamp de última actualización
    document.getElementById('ultimo-refresco').innerHTML = 'Última actualización: ' + new Date().toLocaleTimeString();
}

function iniciarActualizacionAutomatica() {
    // Limpiar intervalo existente si lo hay
    if (intervalId) {
        clearInterval(intervalId);
    }
    
    // Establecer nuevo intervalo
    intervalId = setInterval(function() {
        if (actualizacionAutomatica) {
            console.log('Actualizando datos automáticamente...');
            actualizarDashboard();
        }
    }, INTERVALO_ACTUALIZACION);
    
    console.log('Actualización automática iniciada. Intervalo: ' + INTERVALO_ACTUALIZACION + 'ms');
}

function mostrarErrorGrafico(mensaje) {
    const ctx = document.getElementById('chart-comparacion').getContext('2d');
    ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
    ctx.font = '16px Arial';
    ctx.fillStyle = '#dc3545';
    ctx.textAlign = 'center';
    ctx.fillText('Error al cargar datos del gráfico: ' + mensaje, ctx.canvas.width / 2, ctx.canvas.height / 2);
}
</script>