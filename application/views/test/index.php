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
                            <h5 class="mb-1"><i class="bi bi-search"></i> Consultar Cotizaciones</h5>
                        </div>
                        <p class="mb-1">Consulta las cotizaciones de DolarYa y CriptoYa sin guardarlas en la base de datos.</p>
                        <small class="text-muted">Útil para ver los datos en tiempo real y verificar el scraping.</small>
                    </a>
                    
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
                                            <small class="text-muted d-block"><?= ucfirst($cotizacion->fuente) ?></small>
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
    </div>
</div>