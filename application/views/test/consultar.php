<div class="pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-search"></i> Resultado de Consulta</h1>
    <p class="lead">Resultados de la consulta a DolarYa y CriptoYa.</p>
    <a href="<?= site_url('test') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-cash-coin"></i> Dólar Cocos (DolarYa)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($cocos): ?>
                        <div class="row">
                            <div class="col-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-subtitle mb-2 text-muted">Compra</h6>
                                        <h3 class="card-title">$<?= number_format($cocos['compra'], 2, ',', '.') ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-subtitle mb-2 text-muted">Venta</h6>
                                        <h3 class="card-title">$<?= number_format($cocos['venta'], 2, ',', '.') ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <p class="card-text">
                                <small class="text-muted">Consultado el <?= date('d/m/Y H:i:s') ?></small>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            No se pudo obtener la cotización del dólar Cocos.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-bank"></i> Dólares Bancarios (CriptoYa)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($otros_dolares): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Banco</th>
                                        <th>Compra</th>
                                        <th>Venta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($diferencias as $dif): ?>
                                        <tr>
                                            <td><?= $dif['nombre'] ?></td>
                                            <td>$<?= number_format($dif['compra'], 2, ',', '.') ?></td>
                                            <td>$<?= number_format($dif['venta'], 2, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <p class="card-text">
                                <small class="text-muted">Consultado el <?= date('d/m/Y H:i:s') ?></small>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            No se pudieron obtener las cotizaciones de dólares bancarios.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up-arrow"></i> Análisis de Diferencias
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($diferencias)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Banco</th>
                                        <th>Venta Banco</th>
                                        <th>Compra Cocos</th>
                                        <th>Diferencia</th>
                                        <th>Umbral</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($diferencias as $dif): ?>
                                        <tr>
                                            <td><?= $dif['nombre'] ?></td>
                                            <td>$<?= number_format($dif['venta'], 2, ',', '.') ?></td>
                                            <td>$<?= number_format($cocos['compra'], 2, ',', '.') ?></td>
                                            <td>
                                                <span class="badge <?= $dif['diferencia'] > 0 ? 'bg-danger' : 'bg-success' ?>">
                                                    <?= number_format(abs($dif['diferencia']), 2, ',', '.') ?>% 
                                                    <?= $dif['diferencia'] > 0 ? '↑' : '↓' ?>
                                                </span>
                                            </td>
                                            <td><?= number_format($dif['umbral'], 2, ',', '.') ?>%</td>
                                            <td>
                                                <?php if ($dif['alerta']): ?>
                                                    <span class="badge bg-danger">Alerta</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Normal</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle"></i> 
                            La diferencia se calcula como: ((Venta Banco - Compra Cocos) / Compra Cocos) * 100
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            No se pudieron calcular las diferencias.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>