<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controlador de Pruebas
 * Este controlador proporciona endpoints para probar manualmente las funcionalidades del sistema
 */
class Test extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->library('WebScraper');
        $this->load->helper('telegram');
    }
    
    /**
     * Página principal de pruebas
     */
    public function index() {
        $data['title'] = 'Pruebas del Sistema - Monitor de Dólares';
        
        // Obtener últimas cotizaciones
        $this->load->model('Cotizacion_model');
        $data['ultimas_cotizaciones'] = $this->Cotizacion_model->obtener_ultimas_por_tipo();
        
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
            $otros_dolares = $this->webscraper->consultar_criptoya();
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