<!-- application/views/configuracion/fiabilidad_fuentes.php -->
<div class="pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-bar-chart"></i> Fiabilidad de Fuentes</h1>
    <p class="lead">Estadísticas detalladas de rendimiento de las diferentes fuentes de datos.</p>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-graph-up"></i> Resumen de Fiabilidad
                </h5>
                <a href="<?= site_url('configuracion/optimizar_fuentes') ?>" class="btn btn-sm btn-light">
                    <i class="bi bi-magic"></i> Optimizar Automáticamente
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (!empty($resumen_fiabilidad)): ?>
                        <?php foreach ($resumen_fiabilidad as $fuente): ?>
                            <div class="col-md-6">
                                <div class="card mb-3 <?= $fuente->tasa_exito_promedio > 0.9 ? 'border-success' : 'border-warning' ?>">
                                    <div class="card-header">
                                        <h6 class="mb-0"><?= ucfirst($fuente->fuente) ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Tasa de Éxito: <?= number_format($fuente->tasa_exito_promedio * 100, 1) ?>%</label>
                                            <div class="progress">
                                                <div class="progress-bar <?= $fuente->tasa_exito_promedio > 0.9 ? 'bg-success' : 'bg-warning' ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= $fuente->tasa_exito_promedio * 100 ?>%"
                                                     aria-valuenow="<?= $fuente->tasa_exito_promedio * 100 ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Precisión: <?= number_format($fuente->precision_promedio * 100, 1) ?>%</label>
                                            <div class="progress">
                                                <div class="progress-bar <?= $fuente->precision_promedio > 0.9 ? 'bg-success' : 'bg-warning' ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= $fuente->precision_promedio * 100 ?>%"
                                                     aria-valuenow="<?= $fuente->precision_promedio * 100 ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Tiempo de Respuesta: <?= number_format($fuente->tiempo_respuesta_promedio, 0) ?>ms</label>
                                            <div class="progress">
                                                <div class="progress-bar <?= $fuente->tiempo_respuesta_promedio < 500 ? 'bg-success' : 'bg-warning' ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= min(100, ($fuente->tiempo_respuesta_promedio / 2000) * 100) ?>%"
                                                     aria-valuenow="<?= min(100, ($fuente->tiempo_respuesta_promedio / 2000) * 100) ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <p><strong>Bancos disponibles:</strong> <?= $fuente->total_bancos ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                No hay datos de fiabilidad disponibles. Se generarán automáticamente con las consultas.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-table"></i> Fiabilidad Detallada por Banco
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Banco</th>
                                <th>Fuente Preferida</th>
                                <th>Tasa de Éxito CriptoYa</th>
                                <th>Tasa de Éxito InfoDolar</th>
                                <th>Precisión CriptoYa</th>
                                <th>Precisión InfoDolar</th>
                                <th>Tiempo CriptoYa</th>
                                <th>Tiempo InfoDolar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($fiabilidad_detallada)): ?>
                                <?php foreach ($fiabilidad_detallada as $detalle): ?>
                                    <tr>
                                        <td><strong><?= $detalle['nombre'] ?></strong> <small class="text-muted">(<?= $detalle['codigo'] ?>)</small></td>
                                        <td>
                                            <span class="badge <?= $detalle['fuente_preferida'] == 'criptoya' ? 'bg-primary' : 'bg-success' ?>">
                                                <?= ucfirst($detalle['fuente_preferida']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($detalle['fiabilidad_criptoya']): ?>
                                                <?= number_format(($detalle['fiabilidad_criptoya']->consultas_exitosas / $detalle['fiabilidad_criptoya']->consultas_totales) * 100, 1) ?>%
                                            <?php else: ?>
                                                <span class="text-muted">Sin datos</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($detalle['fiabilidad_infodolar']): ?>
                                                <?= number_format(($detalle['fiabilidad_infodolar']->consultas_exitosas / $detalle['fiabilidad_infodolar']->consultas_totales) * 100, 1) ?>%
                                            <?php else: ?>
                                                <span class="text-muted">Sin datos</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($detalle['fiabilidad_criptoya']): ?>
                                                <?= number_format($detalle['fiabilidad_criptoya']->precision_historica * 100, 1) ?>%
                                            <?php else: ?>
                                                <span class="text-muted">Sin datos</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($detalle['fiabilidad_infodolar']): ?>
                                                <?= number_format($detalle['fiabilidad_infodolar']->precision_historica * 100, 1) ?>%
                                            <?php else: ?>
                                                <span class="text-muted">Sin datos</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($detalle['fiabilidad_criptoya']): ?>
                                                <?= number_format($detalle['fiabilidad_criptoya']->tiempo_respuesta_avg, 0) ?>ms
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($detalle['fiabilidad_infodolar']): ?>
                                                <?= number_format($detalle['fiabilidad_infodolar']->tiempo_respuesta_avg, 0) ?>ms
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No hay datos de fiabilidad detallados disponibles.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle"></i> Información
                </h5>
            </div>
            <div class="card-body">
                <h5>¿Cómo funciona el Sistema de Auto-aprendizaje?</h5>
                <p>El sistema registra y analiza el rendimiento de cada fuente de datos para cada banco. Se evalúan tres factores principales:</p>
                <ul>
                    <li><strong>Tasa de Éxito (50%):</strong> Porcentaje de consultas exitosas sobre el total de intentos.</li>
                    <li><strong>Precisión Histórica (30%):</strong> Qué tan precisos son los datos proporcionados.</li>
                    <li><strong>Tiempo de Respuesta (20%):</strong> Velocidad con la que la fuente proporciona los datos.</li>
                </ul>
                
                <h5>Optimización Automática</h5>
                <p>Al hacer clic en "Optimizar Automáticamente", el sistema:
                <ol>
                    <li>Calcula una puntuación ponderada para cada banco en cada fuente</li>
                    <li>Actualiza la fuente preferida de cada banco según la mejor puntuación</li>
                    <li>Mantiene la configuración actual si ambas fuentes tienen rendimiento similar</li>
                </ol>
                
                <h5>Sistema de Respaldo</h5>
                <p>Incluso si una fuente está marcada como preferida, el sistema utilizará la otra automáticamente como respaldo si la preferida:
                <ul>
                    <li>No está disponible temporalmente</li>
                    <li>No contiene datos para ese banco específico</li>
                    <li>Devuelve un error al ser consultada</li>
                </ul>
                <p>En el log de cotizaciones, se registra qué fuente se utilizó finalmente y si se activó el sistema de respaldo.</p>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 mb-4 text-center">
    <a href="<?= site_url('configuracion/dolares') ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Volver a Configuración de Dólares
    </a>
</div>