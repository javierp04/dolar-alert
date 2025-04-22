<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/DataSources/DataSourceAggregator.php';

/**
 * WebScraper Library
 * Obtiene cotizaciones de dólar de diferentes fuentes:
 * - DolarYa.info para Dólar Cocos (mediante scraping)
 * - Múltiples fuentes para otros dólares de bancos
 */
class WebScraper
{
    protected $CI;
    protected $dataSourceAggregator;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('Cotizacion_model');
        $this->CI->load->model('Configuracion_model');
        $this->CI->load->model('Dolar_model');
        $this->CI->load->helper('telegram');
        $this->CI->load->database();
        
        // Inicializar el agregador de fuentes de datos
        $this->dataSourceAggregator = new DataSourceAggregator();
    }

    /**
     * Consulta cotizaciones y verifica diferencias
     */
    public function consultar_cotizaciones()
    {
        // 1. Obtener dólar Cocos desde dolarya.info como base
        $cocos = $this->consultar_dolarya_cocos();

        if (!$cocos) {
            log_message('error', "No se pudo obtener cotización de dólar Cocos de DolarYa");
            return FALSE;
        }

        // 2. Obtener otros dólares habilitados de múltiples fuentes
        $cotizaciones_unificadas = $this->dataSourceAggregator->obtenerCotizacionesUnificadas();

        if (empty($cotizaciones_unificadas)) {
            log_message('error', "No se pudieron obtener cotizaciones de ninguna fuente");
            return FALSE;
        }

        // 3. Verificar si hay cambios en las cotizaciones
        $hay_cambios = $this->verificar_cambios_cotizaciones($cocos, $cotizaciones_unificadas);
        
        if ($hay_cambios) {
            log_message('info', "Se detectaron cambios en las cotizaciones. Actualizando todas las cotizaciones.");
            
            // 4. Guardar dólar Cocos en base de datos
            $datos_cocos = [
                'fuente' => 'dolarya',
                'tipo' => 'cocos',
                'compra' => $cocos['compra'],
                'venta' => $cocos['venta'],
                'fecha_hora' => date('Y-m-d H:i:s')
            ];

            $this->CI->Cotizacion_model->guardar_cotizacion($datos_cocos);

            // 5. Guardar todos los otros dólares
            $this->guardar_cotizaciones_bancarias($cotizaciones_unificadas);
            
            // 6. Verificar alertas SOLO cuando hay cambios en las cotizaciones
            $alertas = $this->recolectar_alertas($cocos, $cotizaciones_unificadas);
            
            // 7. Enviar alertas por Telegram si existen alertas
            if (!empty($alertas)) {
                $this->enviar_alertas_telegram($alertas, $cocos);
                log_message('info', "Se enviaron alertas por Telegram para " . count($alertas) . " dólares");
            } else {
                log_message('info', "No se enviaron alertas porque ningún dólar cumple con el umbral configurado");
            }
            
            // 8. Actualizar fuentes preferidas según fiabilidad (auto-aprendizaje)
            $this->dataSourceAggregator->actualizarFuentesPreferidas();
        } else {
            log_message('info', "No se detectaron cambios en las cotizaciones. No se actualizará la base de datos ni se enviarán alertas.");
        }

        return TRUE;
    }

    /**
     * Verifica si hay cambios en las cotizaciones comparando con las últimas registradas
     * @return bool TRUE si hay cambios, FALSE si no hay cambios
     */
    private function verificar_cambios_cotizaciones($cocos, $cotizaciones_unificadas)
    {
        // Verificar si hay cambios en Cocos
        $ultima_cocos = $this->CI->Cotizacion_model->obtener_ultima_cotizacion('dolarya', 'cocos');
        
        // Si no hay cotización previa de Cocos, consideramos que hay cambios
        if (!$ultima_cocos) {
            return TRUE;
        }
        
        // Verificar si cambió Cocos (con un pequeño margen de tolerancia para evitar decimales)
        $tolerancia = 0.01;
        if (abs($ultima_cocos->compra - $cocos['compra']) > $tolerancia || 
            abs($ultima_cocos->venta - $cocos['venta']) > $tolerancia) {
            return TRUE;
        }
        
        // Verificar si hay cambios en otros dólares
        $dolares_habilitados = $this->CI->Dolar_model->obtener_dolares_habilitados();
        
        foreach ($dolares_habilitados as $dolar) {
            $codigo = $dolar->codigo;
            
            // Omitir Cocos (ya verificado) y dólares no disponibles
            if ($codigo === 'cocos' || !isset($cotizaciones_unificadas[$codigo])) {
                continue;
            }
            
            $ultima_cotizacion = $this->CI->Cotizacion_model->obtener_ultima_cotizacion(
                $cotizaciones_unificadas[$codigo]['source'], 
                $codigo
            );
            
            // Si no hay cotización previa, consideramos que hay cambios
            if (!$ultima_cotizacion) {
                return TRUE;
            }
            
            // Datos del dólar actual
            $compra = $cotizaciones_unificadas[$codigo]['totalBid'];
            $venta = $cotizaciones_unificadas[$codigo]['totalAsk'];
            
            // Verificar si hubo cambios
            if (abs($ultima_cotizacion->compra - $compra) > $tolerancia || 
                abs($ultima_cotizacion->venta - $venta) > $tolerancia) {
                return TRUE;
            }
        }
        
        // Si llegamos aquí, no hubo cambios
        return FALSE;
    }

    /**
     * Guarda las cotizaciones de dólares bancarios en la base de datos
     */
    private function guardar_cotizaciones_bancarias($cotizaciones_unificadas)
    {
        $fecha_hora = date('Y-m-d H:i:s');
        
        foreach ($cotizaciones_unificadas as $codigo => $cotizacion) {
            // Guardar cotización en la tabla principal
            $datos_cotizacion = [
                'fuente' => $cotizacion['source'],
                'tipo' => $codigo,
                'compra' => $cotizacion['totalBid'],
                'venta' => $cotizacion['totalAsk'],
                'fecha_hora' => $fecha_hora
            ];
            
            $id_cotizacion = $this->CI->Cotizacion_model->guardar_cotizacion($datos_cotizacion);
            
            // Guardar información adicional en el log de cotizaciones
            $datos_log = [
                'fecha_hora' => $fecha_hora,
                'tipo' => $codigo,
                'fuente_origen' => $cotizacion['source'],
                'fuente_preferida' => $cotizacion['fuente_preferida'],
                'compra' => $cotizacion['totalBid'],
                'venta' => $cotizacion['totalAsk'],
                'tiempo_consulta' => 0, // No tenemos este dato aquí
                'fallback_usado' => $cotizacion['fallback_usado'] ? 1 : 0
            ];
            
            $this->CI->db->insert('cotizaciones_log', $datos_log);
        }
    }

    /**
     * Recolecta alertas de dólares que cumplen con el umbral de diferencia
     * @return array Alertas de dólares que cumplen la condición
     */
    private function recolectar_alertas($cocos, $cotizaciones_unificadas)
    {
        // Obtener dólares habilitados de la base de datos
        $dolares_habilitados = $this->CI->Dolar_model->obtener_dolares_habilitados();

        // Precio base (Cocos punta compradora)
        $precio_cocos = $cocos['compra'];

        $alertas = [];

        foreach ($dolares_habilitados as $dolar) {
            $codigo = $dolar->codigo;

            // Verificar que el dólar esté disponible en las cotizaciones unificadas
            if ($codigo === 'cocos' || !isset($cotizaciones_unificadas[$codigo])) {
                continue;
            }

            // Datos del dólar a comparar (punta vendedora totalAsk)
            $cotizacion = $cotizaciones_unificadas[$codigo];
            $compra = $cotizacion['totalBid'];
            $venta = $cotizacion['totalAsk'];

            // Calcular diferencia porcentual entre precio de venta del banco y compra de Cocos
            $diferencia = (($venta - $precio_cocos) / $precio_cocos) * 100;
            $diferencia_abs = abs($diferencia);

            // Verificar si supera el umbral para enviar alerta - SOLO cuando el dólar es más barato
            if ($diferencia_abs >= $dolar->umbral_diferencia && $diferencia < 0) { // Diferencia negativa = dólar más barato
                $alertas[] = [
                    'codigo' => $codigo,
                    'nombre' => $dolar->nombre,
                    'compra' => $compra,
                    'venta' => $venta,
                    'diferencia' => $diferencia,
                    'umbral' => $dolar->umbral_diferencia,
                    'fuente' => $cotizacion['source']
                ];
                
                log_message('debug', "Dólar $codigo ($dolar->nombre) cumple el umbral con diferencia de $diferencia%");
            }
        }

        return $alertas;
    }

    /**
     * Consulta el dólar Cocos en dolarya.info usando web scraping
     * @return array|bool Array con precios o FALSE si hubo un error
     */
    public function consultar_dolarya_cocos()
    {
        // Configuramos cURL para hacer el request
        $curl = curl_init();

        curl_setopt_array($curl, [
            //CURLOPT_URL => "https://www.dolarya.info/",
            CURLOPT_URL => "https://mastuco.com/cryptocot/dolar_proxy.php",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Accept: text/html,application/xhtml+xml,application/xml",
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            log_message('error', "Error al consultar DolarYa: " . $err);
            return FALSE;
        }

        // Procesar el HTML recibido con DOMDocument
        $dom = new DOMDocument();
        @$dom->loadHTML($response);
        $xpath = new DOMXPath($dom);

        // NUEVO SELECTOR: Buscar el enlace que contiene "Cocos"
        $cocos_section = $xpath->query("//a[contains(@href, '/cocos')]");

        if ($cocos_section->length > 0) {
            $section = $cocos_section->item(0);

            // Buscar los valores de compra y venta dentro de esta sección
            // En DolarYa, "Comprá" es el precio de venta (al que el usuario puede comprar)
            // y "Vendé" es el precio de compra (al que el usuario puede vender)

            // Buscar el valor de "Comprá" (valor de venta)
            $venta_nodes = $xpath->query(".//span[text()='Comprá']/following-sibling::p", $section);

            // Buscar el valor de "Vendé" (valor de compra)
            $compra_nodes = $xpath->query(".//span[text()='Vendé']/following-sibling::p", $section);

            if ($compra_nodes->length > 0 && $venta_nodes->length > 0) {
                // Limpiar y convertir a número
                $compra = str_replace(['.', ','], ['', '.'], trim($compra_nodes->item(0)->nodeValue));
                $venta = str_replace(['.', ','], ['', '.'], trim($venta_nodes->item(0)->nodeValue));

                log_message('debug', "DolarYa Cocos - Compra: $compra, Venta: $venta");

                return [
                    'compra' => floatval($compra),
                    'venta' => floatval($venta)
                ];
            }
        }

        log_message('error', "No se pudo encontrar la cotización de Dólar Cocos en DolarYa");
        return FALSE;
    }
    
    /**
     * Consulta las cotizaciones de CriptoYa (método para compatibilidad)
     * 
     * @return array|bool Datos de cotizaciones en formato de CriptoYa o FALSE en caso de error
     */
    public function consultar_criptoya() {
        $criptoya_source = new CriptoYaDataSource();
        return $criptoya_source->obtenerCotizaciones();
    }

    /**
     * Envía alertas por Telegram cuando hay diferencias significativas
     */
    private function enviar_alertas_telegram($alertas, $cocos)
    {
        // Construir mensaje de alerta
        $mensaje = "🔔 *ALERTA DE OPORTUNIDAD* 🔔\n\n";
        $mensaje .= "Dólar Cocos: $" . number_format($cocos['compra'], 2, ',', '.') . " (compra)\n\n";
        $mensaje .= "Se han detectado las siguientes oportunidades:\n\n";

        // Ordenar alertas por diferencia (de mayor a menor ahorro)
        usort($alertas, function($a, $b) {
            return abs($b['diferencia']) - abs($a['diferencia']);
        });

        foreach ($alertas as $alerta) {
            $diferencia_abs = abs($alerta['diferencia']);
            $fuente = ucfirst($alerta['fuente']);

            $mensaje .= "📊 *{$alerta['nombre']}*\n";
            $mensaje .= "💵 Venta: $" . number_format($alerta['venta'], 2, ',', '.') . "\n";
            $mensaje .= "💰 Ahorro: *" . number_format($diferencia_abs, 2, ',', '.') . "%*\n";
            $mensaje .= "📱 Fuente: _" . $fuente . "_\n\n";
        }

        $mensaje .= "📅 *Fecha*: " . date('d/m/Y H:i:s');

        // Enviar mensaje por Telegram
        $resultado = enviar_mensaje_telegram($mensaje);
        
        if ($resultado) {
            log_message('info', "Alerta enviada por Telegram exitosamente");
        } else {
            log_message('error', "Error al enviar alerta por Telegram");
        }

        return $resultado;
    }
}