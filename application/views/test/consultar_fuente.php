<!-- application/views/test/consultar_fuente.php -->
<div class="pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-search"></i> Prueba de <?= ucfirst($fuente) ?></h1>
    <p class="lead">Resultados de la consulta directa a <?= ucfirst($fuente) ?>.</p>
    <div class="d-flex gap-2">
        <a href="<?= site_url('test') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <a href="<?= site_url('test/consultar_criptoya') ?>" class="btn btn-primary <?= $fuente == 'criptoya' ? 'active' : '' ?>">
            <i class="bi bi-cloud-download"></i> Probar CriptoYa
        </a>
        <a href="<?= site_url('test/consultar_infodolar') ?>" class="btn btn-success <?= $fuente == 'infodolar' ? 'active' : '' ?>">
            <i class="bi bi-cloud-download"></i> Probar InfoDolar
        </a>
        <a href="<?= site_url('test/consultar_multifuente') ?>" class="btn btn-info">
            <i class="bi bi-layers"></i> Probar Multifuente
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle"></i> Información de la Consulta
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <h6>Fuente de datos:</h6>
                            <span class="badge <?= $fuente == 'criptoya' ? 'bg-primary' : 'bg-success' ?> p-2">
                                <?= ucfirst($fuente) ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <h6>Tiempo de consulta:</h6>
                            <span class="badge <?= $tiempo_consulta < 1000 ? 'bg-success' : 'bg-warning' ?> p-2">
                                <?= number_format($tiempo_consulta, 0) ?> ms
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <h6>Bancos disponibles:</h6>
                            <span class="badge bg-primary p-2">
                                <?= count($bancos_disponibles) ?> bancos
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header <?= $fuente == 'criptoya' ? 'bg-primary' : 'bg-success' ?> text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-bank"></i> Cotizaciones obtenidas de <?= ucfirst($fuente) ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($cotizaciones)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Banco</th>
                                    <?php if ($fuente == 'criptoya'): ?>
                                    <th>Compra (totalBid)</th>
                                    <th>Venta (totalAsk)</th>
                                    <?php else: ?>
                                    <th>Compra</th>
                                    <th>Venta</th>
                                    <?php endif; ?>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cotizaciones as $codigo => $cotizacion): ?>
                                    <tr>
                                        <td><code><?= $codigo ?></code></td>
                                        <td>
                                            <?php if ($fuente == 'criptoya'): ?>
                                                <?= ucfirst($codigo) ?>
                                            <?php else: ?>
                                                <?= isset($cotizacion['nombre_original']) ? $cotizacion['nombre_original'] : ucfirst($codigo) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($fuente == 'criptoya'): ?>
                                                $<?= number_format($cotizacion['totalBid'], 2, ',', '.') ?>
                                            <?php else: ?>
                                                $<?= number_format($cotizacion['compra'], 2, ',', '.') ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($fuente == 'criptoya'): ?>
                                                $<?= number_format($cotizacion['totalAsk'], 2, ',', '.') ?>
                                            <?php else: ?>
                                                $<?= number_format($cotizacion['venta'], 2, ',', '.') ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= isset($cotizacion['timestamp']) ? $cotizacion['timestamp'] : date('Y-m-d H:i:s') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> No se pudieron obtener cotizaciones de <?= ucfirst($fuente) ?>.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-code-slash"></i> Datos JSON
                </h5>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3 rounded"><code><?= json_encode($cotizaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></code></pre>
                
                <div class="d-flex justify-content-end mt-3">
                    <a href="<?= site_url('test/' . ($fuente == 'criptoya' ? 'consultar_criptoya' : 'consultar_infodolar')) ?>?format=json" class="btn btn-primary" target="_blank">
                        <i class="bi bi-braces"></i> Ver respuesta JSON completa
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>