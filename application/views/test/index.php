<div class="pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-wrench-adjustable"></i> Pruebas del Sistema</h1>
    <p class="lead">Utilice estos enlaces para probar manualmente las funcionalidades del sistema.</p>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-check"></i> Pruebas Disponibles
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="<?= site_url('test/consultar') ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><i class="bi bi-search"></i> Consultar Cotizaciones (Clásico)</h5>
                        </div>
                        <p class="mb-1">Consulta las cotizaciones de DolarYa y CriptoYa sin guardarlas en la base de datos.</p>
                        <small class="text-muted">Útil para ver los datos en tiempo real y verificar el scraping.</small>
                    </a>
                    
                    <div class="list-group-item list-group-item-primary">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><i class="bi bi-cloud-download"></i> Pruebas de Múltiples Fuentes</h5>
                        </div>
                        <div class="btn-group w-100 mt-2">
                            <a href="<?= site_url('test/consultar_criptoya') ?>" class="btn btn-primary">
                                <i class="bi bi-cloud"></i> CriptoYa
                            </a>
                            <a href="<?= site_url('test/consultar_infodolar') ?>" class="btn btn-success">
                                <i class="bi bi-cloud"></i> InfoDolar
                            </a>
                            <a href="<?= site_url('test/consultar_multifuente') ?>" class="btn btn-info">
                                <i class="bi bi-layers"></i> Multifuente
                            </a>
                        </div>
                    </div>
                    
                    <a href="<?= site_url('test/ejecutar') ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><i class="bi bi-play-circle"></i> Ejecutar Monitoreo Completo</h5>
                        </div>
                        <p class="mb-1">Ejecuta el proceso completo de consulta, guardado y verificación de diferencias.</p>
                        <small class="text-muted">Envía alertas por Telegram si se detectan diferencias significativas.</small>
                    </a>
                    
                    <a href="<?= site_url('test/telegram') ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><i class="bi bi-send"></i> Probar Telegram</h5>
                        </div>
                        <p class="mb-1">Envía un mensaje de prueba al chat configurado en Telegram.</p>
                        <small class="text-muted">Útil para verificar que la configuración de Telegram es correcta.</small>
                    </a>
                </div>
                
                <div class="mt-3">
                    <h6>Endpoints JSON:</h6>
                    <ul>
                        <li><code><?= site_url('test/consultar?format=json') ?></code></li>
                        <li><code><?= site_url('test/consultar_criptoya?format=json') ?></code></li>
                        <li><code><?= site_url('test/consultar_infodolar?format=json') ?></code></li>
                        <li><code><?= site_url('test/consultar_multifuente?format=json') ?></code></li>
                        <li><code><?= site_url('test/ejecutar?format=json') ?></code></li>
                        <li><code><?= site_url('test/telegram?format=json') ?></code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-currency-dollar"></i> Últimas Cotizaciones
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($ultimas_cotizaciones)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Fuente</th>
                                    <th>Compra</th>
                                    <th>Venta</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimas_cotizaciones as $cotizacion): ?>
                                    <tr>
                                        <td>
                                            <strong><?= isset($cotizacion->nombre) ? $cotizacion->nombre : ucfirst($cotizacion->tipo) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge <?= $cotizacion->fuente == 'criptoya' ? 'bg-primary' : ($cotizacion->fuente == 'infodolar' ? 'bg-success' : 'bg-secondary') ?>">
                                                <?= ucfirst($cotizacion->fuente) ?>
                                            </span>
                                        </td>
                                        <td>$<?= number_format($cotizacion->compra, 2, ',', '.') ?></td>
                                        <td>$<?= number_format($cotizacion->venta, 2, ',', '.') ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($cotizacion->fecha_hora)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        No hay cotizaciones registradas en la base de datos.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($resumen_fiabilidad)): ?>
        <div class="card mt-4">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-graph-up"></i> Fiabilidad de Fuentes
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($resumen_fiabilidad as $fuente): ?>
                        <div class="col-md-6">
                            <div class="card mb-3 <?= $fuente->tasa_exito_promedio > 0.9 ? 'border-success' : 'border-warning' ?>">
                                <div class="card-header">
                                    <h6 class="mb-0"><?= ucfirst($fuente->fuente) ?></h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Tasa de éxito:</strong> <?= number_format($fuente->tasa_exito_promedio * 100, 1) ?>%</p>
                                    <p><strong>Precisión:</strong> <?= number_format($fuente->precision_promedio * 100, 1) ?>%</p>
                                    <p><strong>Tiempo de respuesta:</strong> <?= number_format($fuente->tiempo_respuesta_promedio, 0) ?>ms</p>
                                    <p><strong>Bancos disponibles:</strong> <?= $fuente->total_bancos ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-3 text-center">
                    <a href="<?= site_url('configuracion/fiabilidad_fuentes') ?>" class="btn btn-primary">
                        <i class="bi bi-bar-chart"></i> Ver Estadísticas Detalladas
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>