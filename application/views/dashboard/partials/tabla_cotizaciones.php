<!-- application/views/dashboard/partials/tabla_cotizaciones.php -->
<div class="mb-3">
    <?php if (!empty($fuentes_utilizadas)): ?>
        <div class="mb-3">
            <h6>Fuentes utilizadas:</h6>
            <div class="d-flex flex-wrap">
                <?php foreach ($fuentes_utilizadas as $fuente): ?>
                    <div class="badge bg-<?= $fuente->fuente_origen == 'criptoya' ? 'primary' : 'success' ?> me-2 p-2">
                        <?= ucfirst($fuente->fuente_origen) ?>: 
                        <?= $fuente->total_bancos ?> bancos
                        <?php if ($fuente->fallbacks_usados > 0): ?>
                            (<?= $fuente->fallbacks_usados ?> por fallback)
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<table class="table table-striped table-hover table-cotizaciones">
    <thead>
        <tr>
            <th>Fuente</th>
            <th>Tipo</th>
            <th>Compra</th>
            <th>Venta</th>
            <th>Diferencia</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        foreach ($ultimas_cotizaciones as $cotizacion): 
            // Calcular diferencia porcentual con Cocos (si existe)
            $diferencia = null;
            $diferencia_clase = '';
            
            if ($cocos && $cotizacion->tipo !== 'cocos') {
                // Comparar venta del banco con compra de Cocos
                $diferencia = (($cotizacion->venta - $cocos->compra) / $cocos->compra) * 100;
                $diferencia_clase = $diferencia > 0 ? 'bg-up' : 'bg-down';
            }
        ?>
            <tr>
                <td>
                    <?= ucfirst($cotizacion->fuente) ?>
                    <?php if (isset($cotizacion->fallback_usado) && $cotizacion->fallback_usado): ?>
                        <span class="badge bg-warning">Fallback</span>
                    <?php endif; ?>
                </td>
                <td>
                    <strong><?= isset($cotizacion->nombre) ? $cotizacion->nombre : ucfirst($cotizacion->tipo) ?></strong>
                </td>
                <td>$<?= number_format($cotizacion->compra, 2, ',', '.') ?></td>
                <td>$<?= number_format($cotizacion->venta, 2, ',', '.') ?></td>
                <td>
                    <?php if ($diferencia !== null): ?>
                        <span class="badge <?= $diferencia_clase ?>">
                            <?= number_format(abs($diferencia), 2, ',', '.') ?>% 
                            <?= $diferencia > 0 ? '↑' : '↓' ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Referencia</span>
                    <?php endif; ?>
                </td>
                <td><?= date('d/m/Y H:i', strtotime($cotizacion->fecha_hora)) ?></td>
            </tr>
        <?php endforeach; ?>
        
        <?php if (empty($ultimas_cotizaciones)): ?>
            <tr>
                <td colspan="6" class="text-center">No hay cotizaciones disponibles.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>