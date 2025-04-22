<!-- application/views/test/consultar_multifuente.php -->
<div class="pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-layers"></i> Prueba Multifuente</h1>
    <p class="lead">Resultados de la consulta a múltiples fuentes de datos.</p>
    <div class="d-flex gap-2">
        <a href="<?= site_url('test') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <a href="<?= site_url('test/consultar_criptoya') ?>" class="btn btn-primary">
            <i class="bi bi-cloud-download"></i> Probar CriptoYa
        </a>
        <a href="<?= site_url('test/consultar_infodolar') ?>" class="btn btn-success">
            <i class="bi bi-cloud-download"></i> Probar InfoDolar
        </a>
        <a href="<?= site_url('test/consultar_multifuente') ?>" class="btn btn-info active">
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
                            <h6>Fuentes consultadas:</h6>
                            <div class="d-flex gap-2">
                                <span class="badge bg-primary p-2">
                                    CriptoYa: <?= count($cotizaciones_por_fuente['criptoya'] ?? []) ?> bancos
                                </span>
                                <span class="badge bg-success p-2">
                                    InfoDolar: <?= count($cotizaciones_por_fuente['infodolar'] ?? []) ?> bancos
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <h6>Tiempo de consulta total:</h6>
                            <span class="badge <?= $tiempo_consulta < 2000 ? 'bg-success' : 'bg-warning' ?> p-2">
                                <?= number_format($tiempo_consulta, 0) ?> ms
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <h6>Dólar Cocos (referencia):</h6>
                            <div>
                                <strong>Compra:</strong> $<?= number_format($cocos['compra'], 2, ',', '.') ?>
                                <strong>Venta:</strong> $<?= number_format($cocos['venta'], 2, ',', '.') ?>
                            </div>
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
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-shuffle"></i> Cotizaciones Unificadas
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($cotizaciones_unificadas)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Fuente</th>
                                    <th>Compra</th>
                                    <th>Venta</th>
                                    <th>Fallback</th>
                                    <th>Diferencia vs Cocos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cotizaciones_unificadas as $codigo => $cotizacion): 
                                    $diferencia = (($cotizacion['totalAsk'] - $cocos['compra']) / $cocos['compra']) * 100;
                                    $diferencia_clase = $diferencia < 0 ? 'text-success' : 'text-danger';
                                ?>
                                    <tr>
                                        <td><code><?= $codigo ?></code></td>
                                        <td>
                                            <span class="badge <?= $cotizacion['source'] == 'criptoya' ? 'bg-primary' : 'bg-success' ?>">
                                                <?= ucfirst($cotizacion['source']) ?>
                                            </span>
                                            <?php if (isset($cotizacion['fuente_preferida']) && $cotizacion['fuente_preferida'] != $cotizacion['source']): ?>
                                                <span class="badge bg-secondary" title="Fuente preferida">
                                                    Pref: <?= ucfirst($cotizacion['fuente_preferida']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>$<?= number_format($cotizacion['totalBid'], 2, ',', '.') ?></td>
                                        <td>$<?= number_format($cotizacion['totalAsk'], 2, ',', '.') ?></td>
                                        <td>
                                            <?php if (isset($cotizacion['fallback_usado']) && $cotizacion['fallback_usado']): ?>
                                                <span class="badge bg-warning">Sí</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="<?= $diferencia_clase ?>">
                                            <?= number_format(abs($diferencia), 2, ',', '.') ?>%
                                            <?= $diferencia < 0 ? '↓' : '↑' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> No se pudieron obtener cotizaciones unificadas.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-bank"></i> CriptoYa
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($cotizaciones_por_fuente['criptoya'])): ?>
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Compra</th>
                                    <th>Venta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cotizaciones_por_fuente['criptoya'] as $codigo => $cotizacion): ?>
                                    <tr>
                                        <td><code><?= $codigo ?></code></td>
                                        <td>$<?= number_format($cotizacion['totalBid'], 2, ',', '.') ?></td>
                                        <td>$<?= number_format($cotizacion['totalAsk'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> No se pudieron obtener cotizaciones de CriptoYa.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-bank"></i> InfoDolar
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($cotizaciones_por_fuente['infodolar'])): ?>
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Compra</th>
                                    <th>Venta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cotizaciones_por_fuente['infodolar'] as $codigo => $cotizacion): ?>
                                    <tr>
                                        <td><code><?= $codigo ?></code></td>
                                        <td>$<?= number_format($cotizacion['compra'], 2, ',', '.') ?></td>
                                        <td>$<?= number_format($cotizacion['venta'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> No se pudieron obtener cotizaciones de InfoDolar.
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
                <ul class="nav nav-tabs" id="jsonTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="unified-tab" data-bs-toggle="tab" data-bs-target="#unified-content" type="button" role="tab" aria-controls="unified-content" aria-selected="true">Unificado</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="criptoya-tab" data-bs-toggle="tab" data-bs-target="#criptoya-content" type="button" role="tab" aria-controls="criptoya-content" aria-selected="false">CriptoYa</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="infodolar-tab" data-bs-toggle="tab" data-bs-target="#infodolar-content" type="button" role="tab" aria-controls="infodolar-content" aria-selected="false">InfoDolar</button>
                    </li>
                </ul>
                <div class="tab-content" id="jsonTabsContent">
                    <div class="tab-pane fade show active" id="unified-content" role="tabpanel" aria-labelledby="unified-tab">
                        <pre class="bg-light p-3 rounded mt-3"><code><?= json_encode($cotizaciones_unificadas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></code></pre>
                    </div>
                    <div class="tab-pane fade" id="criptoya-content" role="tabpanel" aria-labelledby="criptoya-tab">
                        <pre class="bg-light p-3 rounded mt-3"><code><?= json_encode($cotizaciones_por_fuente['criptoya'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></code></pre>
                    </div>
                    <div class="tab-pane fade" id="infodolar-content" role="tabpanel" aria-labelledby="infodolar-tab">
                        <pre class="bg-light p-3 rounded mt-3"><code><?= json_encode($cotizaciones_por_fuente['infodolar'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></code></pre>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-3">
                    <a href="<?= site_url('test/consultar_multifuente') ?>?format=json" class="btn btn-primary" target="_blank">
                        <i class="bi bi-braces"></i> Ver respuesta JSON completa
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>