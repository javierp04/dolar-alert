<!-- application/views/configuracion/dolares.php -->
<div class="pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-currency-exchange"></i> Configuración de Dólares</h1>
    <p class="lead">Administre los tipos de dólares a monitorear.</p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-cash-coin"></i> Dólares Configurados
                </h5>
                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modalDolar" onclick="limpiarFormulario()">
                    <i class="bi bi-plus-circle"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> El dólar Cocos siempre está activo como referencia y no aparece en esta tabla.
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover dolares-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Último Precio</th>
                                <th>Umbral (%)</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($dolares)): ?>
                                <?php foreach ($dolares as $dolar): ?>
                                    <tr class="<?= $dolar->habilitado ? '' : 'table-secondary' ?>">
                                        <td><?= $dolar->codigo ?></td>
                                        <td><?= $dolar->nombre ?></td>
                                        <td>
                                            <?php if (isset($dolar->precio_actual) && $dolar->precio_actual < PHP_FLOAT_MAX): ?>
                                                $<?= number_format($dolar->precio_actual, 2, ',', '.') ?>
                                            <?php else: ?>
                                                <span class="text-muted">Sin datos</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= number_format($dolar->umbral_diferencia, 2, ',', '.') ?>%</td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="switch-<?= $dolar->id ?>" 
                                                    <?= $dolar->habilitado ? 'checked' : '' ?> 
                                                    onchange="cambiarEstado(<?= $dolar->id ?>, this.checked)">
                                                <label class="form-check-label" for="switch-<?= $dolar->id ?>">
                                                    <?= $dolar->habilitado ? 'Habilitado' : 'Deshabilitado' ?>
                                                </label>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-primary" onclick="editarDolar(<?= $dolar->id ?>, '<?= $dolar->codigo ?>', '<?= $dolar->nombre ?>', <?= $dolar->umbral_diferencia ?>)" data-bs-toggle="modal" data-bs-target="#modalDolar">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="confirmarEliminar(<?= $dolar->id ?>, '<?= $dolar->nombre ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php if (empty($dolares)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No hay dólares de bancos configurados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle"></i> Información
                </h5>
            </div>
            <div class="card-body">
                <h5 class="card-title">Dólar de Referencia</h5>
                <p>El dólar Cocos de DolarYa es utilizado como base para todas las comparaciones:</p>
                <?php 
                // Mostrar los valores actuales del dólar Cocos
                if ($cocos && isset($cotizaciones['cocos'])):
                ?>
                <div class="alert alert-success">
                    <strong>Dólar Cocos:</strong><br>
                    Compra: $<?= number_format($cotizaciones['cocos_compra'] ?? 0, 2, ',', '.') ?><br>
                    Venta: $<?= number_format($cotizaciones['cocos'] ?? 0, 2, ',', '.') ?>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">No hay datos recientes del dólar Cocos.</div>
                <?php endif; ?>

                <h5 class="card-title mt-3">Alertas de Oportunidad</h5>
                <p>El sistema enviará alertas por Telegram <strong>únicamente</strong> cuando un dólar bancario esté <strong>más barato</strong> que el Cocos, según el umbral configurado.</p>
                
                <h5 class="card-title mt-3">Ordenamiento</h5>
                <p>La tabla muestra primero los dólares habilitados ordenados de menor a mayor precio, seguidos por los deshabilitados.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal para agregar/editar dólar -->
<div class="modal fade" id="modalDolar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nuevo Dólar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formDolar" action="<?= site_url('configuracion/dolar/guardar') ?>" method="post">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="mb-3">
                        <label for="codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" required>
                        <div class="form-text">Código exacto como aparece en la API (sensible a mayúsculas/minúsculas).</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                        <div class="form-text">Nombre descriptivo para mostrar en el dashboard.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="umbral_diferencia" class="form-label">Umbral de Diferencia (%)</label>
                        <input type="number" class="form-control" id="umbral_diferencia" name="umbral_diferencia" 
                               min="0.01" step="0.01" value="1.00" required>
                        <div class="form-text">Porcentaje mínimo de diferencia para enviar alertas.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('formDolar').submit()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para confirmar eliminación -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar el dólar <strong id="nombreDolar"></strong>?</p>
                <p class="text-danger">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="btnEliminar" class="btn btn-danger">Eliminar</a>
            </div>
        </div>
    </div>
</div>

<script>
function limpiarFormulario() {
    document.getElementById('modalTitle').textContent = 'Nuevo Dólar';
    document.getElementById('formDolar').reset();
    document.getElementById('id').value = '';
    document.getElementById('codigo').disabled = false;
}

function editarDolar(id, codigo, nombre, umbral) {
    document.getElementById('modalTitle').textContent = 'Editar Dólar';
    document.getElementById('id').value = id;
    document.getElementById('codigo').value = codigo;
    document.getElementById('nombre').value = nombre;
    document.getElementById('umbral_diferencia').value = umbral;
}

function confirmarEliminar(id, nombre) {
    document.getElementById('nombreDolar').textContent = nombre;
    document.getElementById('btnEliminar').href = "<?= site_url('configuracion/eliminar_dolar/') ?>" + id;
    
    var modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
    modal.show();
}

function cambiarEstado(id, estado) {
    // Redirigir a la misma URL que usábamos antes
    window.location.href = "<?= site_url('configuracion/dolar/estado/') ?>" + id;
}
</script>