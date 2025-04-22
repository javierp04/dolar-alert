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
                                <th>Fuente</th>
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
                                            <select class="form-select form-select-sm fuente-preferida" 
                                                    data-id="<?= $dolar->id ?>"
                                                    <?= $dolar->habilitado ? '' : 'disabled' ?>>
                                                <option value="criptoya" <?= $dolar->fuente_preferida == 'criptoya' ? 'selected' : '' ?>>CriptoYa</option>
                                                <option value="infodolar" <?= $dolar->fuente_preferida == 'infodolar' ? 'selected' : '' ?>>InfoDolar</option>
                                            </select>
                                        </td>
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
                                                <button class="btn btn-sm btn-primary" onclick="editarDolar(<?= $dolar->id ?>, '<?= $dolar->codigo ?>', '<?= $dolar->nombre ?>', <?= $dolar->umbral_diferencia ?>, '<?= $dolar->fuente_preferida ?>')" data-bs-toggle="modal" data-bs-target="#modalDolar">
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
                                    <td colspan="7" class="text-center">No hay dólares de bancos configurados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-graph-up"></i> Fiabilidad de Fuentes
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (!empty($fiabilidad_fuentes)): ?>
                        <?php foreach ($fiabilidad_fuentes as $fuente): ?>
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
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                No hay datos de fiabilidad disponibles. Se generarán automáticamente con las consultas.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="mt-3">
                    <a href="<?= site_url('configuracion/fiabilidad_fuentes') ?>" class="btn btn-primary">
                        <i class="bi bi-bar-chart"></i> Ver Estadísticas Detalladas
                    </a>
                    <a href="<?= site_url('configuracion/optimizar_fuentes') ?>" class="btn btn-success">
                        <i class="bi bi-magic"></i> Optimizar Fuentes Automáticamente
                    </a>
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
                if ($cocos):
                    $cocos_compra = 0;
                    $cocos_venta = 0;
                    
                    // Obtener última cotización de Cocos
                    $query = $this->db->select('compra, venta')
                        ->from('cotizaciones')
                        ->where('tipo', 'cocos')
                        ->order_by('fecha_hora', 'DESC')
                        ->limit(1)
                        ->get();
                    
                    if ($query->num_rows() > 0) {
                        $cocos_row = $query->row();
                        $cocos_compra = $cocos_row->compra;
                        $cocos_venta = $cocos_row->venta;
                    }
                ?>
                <div class="alert alert-success">
                    <strong>Dólar Cocos:</strong><br>
                    Compra: $<?= number_format($cocos_compra, 2, ',', '.') ?><br>
                    Venta: $<?= number_format($cocos_venta, 2, ',', '.') ?>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">No hay datos recientes del dólar Cocos.</div>
                <?php endif; ?>

                <h5 class="card-title mt-3">Alertas de Oportunidad</h5>
                <p>El sistema enviará alertas por Telegram <strong>únicamente</strong> cuando un dólar bancario esté <strong>más barato</strong> que el Cocos, según el umbral configurado.</p>
                
                <h5 class="card-title mt-3">Ordenamiento</h5>
                <p>La tabla muestra primero los dólares habilitados ordenados de menor a mayor precio, seguidos por los deshabilitados.</p>
                
                <h5 class="card-title mt-3">Múltiples Fuentes</h5>
                <p>El sistema ahora soporta múltiples fuentes de datos:</p>
                <ul>
                    <li><strong>CriptoYa</strong>: La fuente original que ofrece datos en tiempo real de la mayoría de los bancos.</li>
                    <li><strong>InfoDolar</strong>: Nueva fuente alternativa que puede ofrecer datos diferentes para algunos bancos.</li>
                </ul>
                <p>Puede seleccionar la fuente preferida para cada banco. El sistema usará automáticamente la otra fuente como respaldo si la preferida falla.</p>
                
                <h5 class="card-title mt-3">Auto-aprendizaje</h5>
                <p>El sistema registra la fiabilidad de cada fuente y puede optimizar automáticamente la selección de fuentes según el rendimiento histórico.</p>
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
                    
                    <div class="mb-3">
                        <label for="fuente_preferida" class="form-label">Fuente Preferida</label>
                        <select class="form-select" id="fuente_preferida" name="fuente_preferida" required>
                            <option value="criptoya">CriptoYa (API)</option>
                            <option value="infodolar">InfoDolar (Web)</option>
                        </select>
                        <div class="form-text">Fuente de datos principal para este banco. Se usará la otra como respaldo.</div>
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

function editarDolar(id, codigo, nombre, umbral, fuente_preferida) {
    document.getElementById('modalTitle').textContent = 'Editar Dólar';
    document.getElementById('id').value = id;
    document.getElementById('codigo').value = codigo;
    document.getElementById('nombre').value = nombre;
    document.getElementById('umbral_diferencia').value = umbral;
    document.getElementById('fuente_preferida').value = fuente_preferida;
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

// Inicializar evento para selectores de fuente preferida
document.addEventListener('DOMContentLoaded', function() {
    const selectores = document.querySelectorAll('.fuente-preferida');
    
    selectores.forEach(selector => {
        selector.addEventListener('change', function() {
            const dolarId = this.getAttribute('data-id');
            const fuentePreferida = this.value;
            
            // Llamada AJAX para actualizar la fuente preferida
            fetch('<?= site_url('configuracion/actualizar_fuente_dolar') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + dolarId + '&fuente_preferida=' + fuentePreferida
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar notificación de éxito
                    const toast = document.createElement('div');
                    toast.className = 'position-fixed bottom-0 end-0 p-3';
                    toast.style.zIndex = 5;
                    toast.innerHTML = `
                        <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="d-flex">
                                <div class="toast-body">
                                    <i class="bi bi-check-circle"></i> Fuente actualizada correctamente
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(toast);
                    
                    const toastEl = document.querySelector('.toast');
                    const bsToast = new bootstrap.Toast(toastEl, { autohide: true, delay: 3000 });
                    bsToast.show();
                    
                    // Eliminar el toast del DOM después de ocultarse
                    toastEl.addEventListener('hidden.bs.toast', function () {
                        document.body.removeChild(toast);
                    });
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar la fuente preferida');
            });
        });
    });
});
</script>