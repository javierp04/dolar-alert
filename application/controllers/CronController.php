<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controlador para ejecutar tareas programadas
 * Este controlador está diseñado para ser llamado por un cron job
 */
class CronController extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->library('WebScraper');
        $this->load->model('Configuracion_model');
        
        // Verificar que la solicitud sea desde CLI o con una clave de API válida
        if (!$this->input->is_cli_request() && !$this->validar_api_key()) {
            echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado']);
            exit;
        }
    }
    
    /**
     * Ejecuta la consulta de cotizaciones
     */
    public function consultar_cotizaciones() {
        $resultado = $this->webscraper->consultar_cotizaciones();
        
        if ($this->input->is_cli_request()) {
            echo $resultado ? "Cotizaciones consultadas correctamente.\n" : "Error al consultar cotizaciones.\n";
        } else {
            echo json_encode([
                'status' => $resultado ? 'success' : 'error',
                'message' => $resultado ? 'Cotizaciones consultadas correctamente.' : 'Error al consultar cotizaciones.'
            ]);
        }
    }
    
    /**
     * Valida la clave de API para acceso desde web
     */
    private function validar_api_key() {
        $api_key_config = $this->Configuracion_model->obtener_configuracion('api_key');
        $api_key_request = $this->input->get('api_key');
        
        if (empty($api_key_config) || empty($api_key_request)) {
            return FALSE;
        }
        
        return $api_key_config === $api_key_request;
    }
}