<div class="pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-sliders"></i> Configuración General</h1>
    <p class="lead">Configure los parámetros generales del sistema.</p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-gear"></i> Parámetros de Configuración
                </h5>
            </div>
            <div class="card-body">
                <form action="<?= site_url('configuracion/guardar') ?>" method="post">
                    <div class="mb-3">
                        <label for="telegram_bot_token" class="form-label">Token del Bot de Telegram</label>
                        <input type="text" class="form-control" id="telegram_bot_token" name="telegram_bot_token" 
                               value="<?= isset($config['telegram_bot_token']) ? $config['telegram_bot_token'] : '' ?>" required>
                        <div class="form-text">Token proporcionado por BotFather para su bot de Telegram.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="telegram_chat_id" class="form-label">Chat ID de Telegram</label>
                        <input type="text" class="form-control" id="telegram_chat_id" name="telegram_chat_id" 
                               value="<?= isset($config['telegram_chat_id']) ? $config['telegram_chat_id'] : '' ?>" required>
                        <div class="form-text">ID del chat o grupo donde se enviarán las alertas.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="intervalo_consulta" class="form-label">Intervalo de Consulta (minutos)</label>
                        <input type="number" class="form-control" id="intervalo_consulta" name="intervalo_consulta" 
                               value="<?= isset($config['intervalo_consulta']) ? $config['intervalo_consulta'] : '30' ?>" 
                               min="1" required>
                        <div class="form-text">Intervalo de tiempo entre consultas automáticas.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="umbral_global" class="form-label">Umbral Global de Diferencia (%)</label>
                        <input type="number" class="form-control" id="umbral_global" name="umbral_global" 
                               value="<?= isset($config['umbral_global']) ? $config['umbral_global'] : '2.00' ?>" 
                               min="0.01" step="0.01" required>
                        <div class="form-text">
                            <strong>Este valor se aplicará a todos los dólares, sobrescribiendo sus valores individuales.</strong>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="probar_telegram" name="probar_telegram">
                        <label class="form-check-label" for="probar_telegram">Enviar mensaje de prueba al guardar</label>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Configuración
                        </button>
                    </div>
                </form>
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
                <h5 class="card-title">Configuración de Telegram</h5>
                <p>Para configurar las alertas por Telegram, necesita:</p>
                <ol>
                    <li>Crear un bot de Telegram con <a href="https://t.me/BotFather" target="_blank">@BotFather</a></li>
                    <li>Obtener el token del bot</li>
                    <li>Agregar el bot a un grupo o iniciar un chat con él</li>
                    <li>Obtener el ID del chat usando <a href="https://t.me/getidsbot" target="_blank">@getidsbot</a></li>
                </ol>
                
                <h5 class="card-title mt-4">Umbral Global</h5>
                <p>Al guardar, el umbral global se aplicará a todos los dólares configurados en el sistema.</p>
                
                <h5 class="card-title mt-4">Configuración del Cron</h5>
                <p>Para automatizar las consultas, configure un cron job en su servidor:</p>
                <div class="alert alert-secondary">
                    <code>*/<?= isset($config['intervalo_consulta']) ? $config['intervalo_consulta'] : '30' ?> * * * * php <?= FCPATH ?>index.php CronController consultar_cotizaciones</code>
                </div>
            </div>
        </div>
    </div>
</div>