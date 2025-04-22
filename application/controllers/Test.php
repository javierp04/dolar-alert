<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'libraries/DataSources/DataSourceAggregator.php';
require_once APPPATH . 'libraries/DataSources/CriptoYaDataSource.php';
require_once APPPATH . 'libraries/DataSources/InfoDolarDataSource.php';

/**
 * Controlador de Pruebas
 * Este controlador proporciona endpoints para probar manualmente las funcionalidades del sistema
 */
class Test extends CI_Controller {
    
    protected $dataSourceAggregator;
    
    public function __construct() {
        parent::__construct();
        $this->load->library('WebScraper');
        $this->load->helper('telegram');
        
        // Inicializar el agregador de fuentes de datos
        $this->dataSourceAggregator = new DataSourceAggregator();
    }
    
    /**
     * Página principal de pruebas
     */
    public function index() {
        $data['title'] = 'Pruebas del Sistema - Monitor de Dólares';
        
        // Obtener últimas cotizaciones
        $this->load->model('Cotizacion_model');
        $data['ultimas_cotizaciones'] = $this->Cotizacion_model->obtener_ultimas_por_tipo();
        
        // Obtener información de fiabilidad
        $this->load->model('SourceReliability_model');
        $data['resumen_fiabilidad'] = $this->SourceReliability_model->obtenerResumenFiabilidad();
        
        // Cargar vista
        $this->load->view('templates/header', $data);
        $this->load->view('test/index', $data);
        $this->load->view('templates/footer');
    }
    
    /**
     * Consulta cotizaciones y muestra los resultados directamente
     */
    public function consultar() {
        // Variables para almacenar resultados
        $data = [];
        $data['cocos'] = null;
        $data['otros_dolares'] = null;
        $data['diferencias'] = [];
        
        // 1. Obtener dólar Cocos
        $cocos = $this->webscraper->consultar_dolarya_cocos();
        $data['cocos'] = $cocos;
        
        if (!$cocos) {
            $data['error'] = "No se pudo obtener cotización de dólar Cocos de DolarYa";
        } else {
            // 2. Obtener otros dólares
            // Usar CriptoYaDataSource directamente en vez de webscraper->consultar_criptoya()
            $criptoya_source = new CriptoYaDataSource();
            $otros_dolares = $criptoya_source->obtenerCotizaciones();
            $data['otros_dolares'] = $otros_dolares;
            
            if (!$otros_dolares) {
                $data['error'] = "No se pudo obtener cotizaciones de CriptoYa";
            } else {
                // 3. Calcular diferencias
                $this->load->model('Dolar_model');
                $dolares_habilitados = $this->Dolar_model->obtener_dolares_habilitados();
                
                // Precio base (Cocos punta compradora)
                $precio_cocos = $cocos['compra'];
                
                foreach ($dolares_habilitados as $dolar) {
                    $codigo = $dolar->codigo;
                    
                    // Verificar que el dólar esté disponible en la API
                    if (!isset($otros_dolares[$codigo])) {
                        continue;
                    }
                    
                    // Datos del dólar a comparar (punta vendedora totalAsk)
                    $dolar_info = $otros_dolares[$codigo];
                    $compra = isset($dolar_info['totalBid']) ? $dolar_info['totalBid'] : 0;
                    $venta = isset($dolar_info['totalAsk']) ? $dolar_info['totalAsk'] : 0;
                    
                    // Calcular diferencia porcentual
                    $diferencia = (($venta - $precio_cocos) / $precio_cocos) * 100;
                    
                    // Agregar a lista de diferencias
                    $data['diferencias'][] = [
                        'codigo' => $codigo,
                        'nombre' => $dolar->nombre,
                        'compra' => $compra,
                        'venta' => $venta,
                        'diferencia' => $diferencia,
                        'umbral' => $dolar->umbral_diferencia,
                        'alerta' => abs($diferencia) >= $dolar->umbral_diferencia
                    ];
                }
            }
        }
        
        // Determinar tipo de respuesta según el parámetro
        $format = $this->input->get('format');
        
        if ($format === 'json') {
            // Respuesta JSON
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($data));
        } else {
            // Respuesta HTML
            $data['title'] = 'Resultado de Consulta - Monitor de Dólares';
            $this->load->view('templates/header', $data);
            $this->load->view('test/consultar', $data);
            $this->load->view('templates/footer');
        }
    }
    
    /**
     * Consulta datos de CriptoYa directamente
     */
    public function consultar_criptoya() {
        $data['title'] = 'Prueba de CriptoYa - Monitor de Dólares';
        
        $criptoya_source = new CriptoYaDataSource();
        $inicio = microtime(true);
        $data['cotizaciones'] = $criptoya_source->obtenerCotizaciones();
        $tiempo = round((microtime(true) - $inicio) * 1000); // Tiempo en milisegundos
        
        $data['tiempo_consulta'] = $tiempo;
        $data['fuente'] = 'criptoya';
        $data['bancos_disponibles'] = $criptoya_source->getBancosDisponibles();
        
        // Determinar tipo de respuesta según el parámetro
        $format = $this->input->get('format');
        
        if ($format === 'json') {
            // Respuesta JSON
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'cotizaciones' => $data['cotizaciones'],
                    'tiempo_consulta' => $data['tiempo_consulta'],
                    'bancos_disponibles' => $data['bancos_disponibles']
                ]));
        } else {
            // Respuesta HTML
            $this->load->view('templates/header', $data);
            $this->load->view('test/consultar_fuente', $data);
            $this->load->view('templates/footer');
        }
    }
    
    /**
     * Consulta datos de InfoDolar directamente
     */
    public function consultar_infodolar() {
        $data['title'] = 'Prueba de InfoDolar - Monitor de Dólares';
        
        $infodolar_source = new InfoDolarDataSource();
        $inicio = microtime(true);
        $data['cotizaciones'] = $infodolar_source->obtenerCotizaciones();
        $tiempo = round((microtime(true) - $inicio) * 1000); // Tiempo en milisegundos
        
        $data['tiempo_consulta'] = $tiempo;
        $data['fuente'] = 'infodolar';
        $data['bancos_disponibles'] = $infodolar_source->getBancosDisponibles();
        
        // Determinar tipo de respuesta según el parámetro
        $format = $this->input->get('format');
        
        if ($format === 'json') {
            // Respuesta JSON
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'cotizaciones' => $data['cotizaciones'],
                    'tiempo_consulta' => $data['tiempo_consulta'], 
                    'bancos_disponibles' => $data['bancos_disponibles']
                ]));
        } else {
            // Respuesta HTML
            $this->load->view('templates/header', $data);
            $this->load->view('test/consultar_fuente', $data);
            $this->load->view('templates/footer');
        }
    }
    
    /**
     * Consulta datos unificados de múltiples fuentes
     */
    public function consultar_multifuente() {
        $data['title'] = 'Prueba Multifuente - Monitor de Dólares';
        
        $inicio = microtime(true);
        
        // Consultar todas las fuentes
        $data['cotizaciones_por_fuente'] = $this->dataSourceAggregator->consultarTodasLasFuentes();
        
        // Obtener cotizaciones unificadas
        $data['cotizaciones_unificadas'] = $this->dataSourceAggregator->obtenerCotizacionesUnificadas();
        
        $tiempo = round((microtime(true) - $inicio) * 1000); // Tiempo en milisegundos
        
        $data['tiempo_consulta'] = $tiempo;
        
        // Obtener dólar Cocos para comparaciones
        $data['cocos'] = $this->webscraper->consultar_dolarya_cocos();
        
        // Determinar tipo de respuesta según el parámetro
        $format = $this->input->get('format');
        
        if ($format === 'json') {
            // Respuesta JSON
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'cotizaciones_por_fuente' => $data['cotizaciones_por_fuente'],
                    'cotizaciones_unificadas' => $data['cotizaciones_unificadas'],
                    'tiempo_consulta' => $data['tiempo_consulta'],
                    'cocos' => $data['cocos']
                ]));
        } else {
            // Respuesta HTML
            $this->load->view('templates/header', $data);
            $this->load->view('test/consultar_multifuente', $data);
            $this->load->view('templates/footer');
        }
    }
    
    /**
     * Ejecuta la consulta completa y guardado en base de datos
     */
    public function ejecutar() {
        $resultado = $this->webscraper->consultar_cotizaciones();
        
        $format = $this->input->get('format');
        
        if ($format === 'json') {
            // Respuesta JSON
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => $resultado,
                    'message' => $resultado 
                        ? 'Cotizaciones consultadas y guardadas correctamente.' 
                        : 'Error al consultar cotizaciones. Revise los logs para más información.'
                ]));
        } else {
            // Respuesta HTML con redirección
            if ($resultado) {
                $this->session->set_flashdata('success', 'Cotizaciones consultadas y guardadas correctamente.');
            } else {
                $this->session->set_flashdata('error', 'Error al consultar cotizaciones. Revise los logs para más información.');
            }
            
            redirect('test');
        }
    }
    
    /**
     * Prueba el envío de mensajes a Telegram
     */
    public function telegram() {
        $this->load->model('Configuracion_model');
        
        // Obtener configuración
        $token = $this->Configuracion_model->obtener_configuracion('telegram_bot_token');
        $chat_id = $this->Configuracion_model->obtener_configuracion('telegram_chat_id');
        
        // Validar que existan token y chat_id
        if (empty($token) || empty($chat_id)) {
            $this->session->set_flashdata('error', 'Falta configurar el token o chat ID de Telegram.');
            redirect('test');
            return;
        }
        
        // Enviar mensaje de prueba
        $resultado = enviar_mensaje_prueba_telegram($token, $chat_id);
        
        $format = $this->input->get('format');
        
        if ($format === 'json') {
            // Respuesta JSON
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => $resultado,
                    'message' => $resultado 
                        ? 'Mensaje de prueba enviado correctamente a Telegram.' 
                        : 'Error al enviar mensaje de prueba a Telegram. Revise los logs para más información.'
                ]));
        } else {
            // Respuesta HTML con redirección
            if ($resultado) {
                $this->session->set_flashdata('success', 'Mensaje de prueba enviado correctamente a Telegram.');
            } else {
                $this->session->set_flashdata('error', 'Error al enviar mensaje de prueba a Telegram. Revise los logs para más información.');
            }
            
            redirect('test');
        }
    }
}