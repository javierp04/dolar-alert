<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * WebScraper Library
 * Obtiene cotizaciones de dólar de diferentes fuentes:
 * - DolarYa.info para Dólar Cocos (mediante scraping)
 * - CriptoYa API para otros dólares de bancos
 */
class WebScraper
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('Cotizacion_model');
        $this->CI->load->model('Configuracion_model');
        $this->CI->load->model('Dolar_model');
        $this->CI->load->helper('telegram');
        $this->CI->load->database();
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

        // 2. Obtener otros dólares habilitados de CriptoYa
        $otros_dolares = $this->consultar_criptoya();

        if (!$otros_dolares) {
            log_message('error', "No se pudo obtener cotizaciones de CriptoYa");
            return FALSE;
        }

        // 3. Guardar dólar Cocos en base de datos
        $datos_cocos = [
            'fuente' => 'dolarya',
            'tipo' => 'cocos',
            'compra' => $cocos['compra'],
            'venta' => $cocos['venta'],
            'fecha_hora' => date('Y-m-d H:i:s')
        ];

        $this->CI->Cotizacion_model->guardar_cotizacion($datos_cocos);

        // 4. Comparar Cocos con los otros dólares habilitados
        $this->verificar_diferencias($cocos, $otros_dolares);

        return TRUE;
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
     * Consulta las cotizaciones de dólares desde la API de CriptoYa
     * @return array|bool Array con cotizaciones o FALSE si hubo un error
     */
    public function consultar_criptoya()
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://criptoya.com/api/bancostodos",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "User-Agent: DolarMonitor/1.0"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            log_message('error', "Error al consultar API CriptoYa: " . $err);
            return false;
        }

        // Decodificar la respuesta JSON
        $data = json_decode($response, true);

        if ($data === null) {
            log_message('error', "Error al decodificar respuesta JSON de CriptoYa");
            return false;
        }

        return $data;
    }

    /**
     * Verifica las diferencias entre el dólar Cocos y los otros dólares habilitados
     */
    private function verificar_diferencias($cocos, $otros_dolares)
    {
        // Obtener dólares habilitados de la base de datos
        $dolares_habilitados = $this->CI->Dolar_model->obtener_dolares_habilitados();

        // Precio base (Cocos punta compradora)
        $precio_cocos = $cocos['compra'];

        $alertas = [];

        foreach ($dolares_habilitados as $dolar) {
            $codigo = $dolar->codigo;

            // Verificar que el dólar esté disponible en la API
            if (!isset($otros_dolares[$codigo])) {
                log_message('info', "El dólar $codigo no está disponible en la API");
                continue;
            }

            // Datos del dólar a comparar (punta vendedora totalAsk)
            $dolar_info = $otros_dolares[$codigo];
            $compra = isset($dolar_info['totalBid']) ? $dolar_info['totalBid'] : 0;
            $venta = isset($dolar_info['totalAsk']) ? $dolar_info['totalAsk'] : 0;

            // Calcular diferencia porcentual entre precio de venta del banco y compra de Cocos
            $diferencia = (($venta - $precio_cocos) / $precio_cocos) * 100;
            $diferencia_abs = abs($diferencia);

            // Guardar en base de datos
            $datos_comparacion = [
                'fuente' => 'criptoya',
                'tipo' => $codigo,
                'compra' => $compra,
                'venta' => $venta,
                'fecha_hora' => date('Y-m-d H:i:s')
            ];

            $this->CI->Cotizacion_model->guardar_cotizacion($datos_comparacion);

            // Verificar si supera el umbral para enviar alerta - SOLO cuando el dólar es más barato
            if ($diferencia_abs >= $dolar->umbral_diferencia && $diferencia < 0) { // Diferencia negativa = dólar más barato
                $alertas[] = [
                    'codigo' => $codigo,
                    'nombre' => $dolar->nombre,
                    'compra' => $compra,
                    'venta' => $venta,
                    'diferencia' => $diferencia,
                    'umbral' => $dolar->umbral_diferencia
                ];
            }
        }

        // Enviar alertas si hay diferencias significativas
        if (!empty($alertas)) {
            $this->enviar_alertas_telegram($alertas, $cocos);
        }

        return TRUE;
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

        foreach ($alertas as $alerta) {
            $diferencia_abs = abs($alerta['diferencia']);

            $mensaje .= "📊 *{$alerta['nombre']}*\n";
            $mensaje .= "💵 Venta: $" . number_format($alerta['venta'], 2, ',', '.') . "\n";
            $mensaje .= "💰 Ahorro: *" . number_format($diferencia_abs, 2, ',', '.') . "%*\n\n";
        }

        $mensaje .= "📅 *Fecha*: " . date('d/m/Y H:i:s');

        // Enviar mensaje por Telegram
        enviar_mensaje_telegram($mensaje);

        log_message('info', "Alerta enviada por oportunidades de arbitraje");

        return TRUE;
    }
}
