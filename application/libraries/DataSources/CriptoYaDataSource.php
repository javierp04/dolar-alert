<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'libraries/DataSources/DataSourceInterface.php';

/**
 * Implementación de DataSourceInterface para CriptoYa
 * 
 * Se encarga de obtener las cotizaciones de dólares de la API de CriptoYa
 */
class CriptoYaDataSource implements DataSourceInterface {
    
    protected $CI;
    private $bancosDisponibles = [];
    private $tiempoConsulta = 0;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('SourceReliability_model');
        
        // Bancos disponibles en CriptoYa
        $this->bancosDisponibles = BankCodeMapper::getCodigosDisponibles('criptoya');
    }
    
    /**
     * Obtiene las cotizaciones de dólares de CriptoYa
     * 
     * @return array|bool Datos de cotizaciones en formato unificado o FALSE en caso de error
     */
    public function obtenerCotizaciones() {
        $inicio = microtime(true);
        
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
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);

        curl_close($curl);
        
        $this->tiempoConsulta = round((microtime(true) - $inicio) * 1000); // Tiempo en milisegundos
        
        // Actualizar estadísticas de fiabilidad para todos los bancos
        $this->actualizarEstadisticas($httpcode == 200 && !$err);
        
        if ($err || $httpcode != 200) {
            log_message('error', "Error al consultar API CriptoYa: " . $err . " (HTTP: $httpcode)");
            return false;
        }

        // Decodificar la respuesta JSON
        $data = json_decode($response, true);

        if ($data === null) {
            log_message('error', "Error al decodificar respuesta JSON de CriptoYa");
            return false;
        }
        
        // Procesar los datos para un formato estándar
        $resultado = [];
        foreach ($data as $codigo => $cotizacion) {
            if (isset($cotizacion['totalBid']) && isset($cotizacion['totalAsk'])) {
                $resultado[$codigo] = [
                    'totalBid' => (float)$cotizacion['totalBid'],
                    'totalAsk' => (float)$cotizacion['totalAsk'],
                    'source' => 'criptoya',
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
        }

        return $resultado;
    }
    
    /**
     * Obtiene el nombre de la fuente
     * 
     * @return string Nombre identificador de la fuente
     */
    public function getNombre() {
        return 'criptoya';
    }
    
    /**
     * Obtiene los bancos disponibles en esta fuente
     * 
     * @return array Lista de códigos de bancos disponibles
     */
    public function getBancosDisponibles() {
        return $this->bancosDisponibles;
    }
    
    /**
     * Verifica si un banco específico está disponible en esta fuente
     * 
     * @param string $codigo Código del banco a verificar
     * @return bool TRUE si está disponible, FALSE en caso contrario
     */
    public function isBancoDisponible($codigo) {
        return in_array($codigo, $this->bancosDisponibles);
    }
    
    /**
     * Obtiene el tiempo que tardó la última consulta en milisegundos
     * 
     * @return int Tiempo en milisegundos
     */
    public function getTiempoConsulta() {
        return $this->tiempoConsulta;
    }
    
    /**
     * Actualiza las estadísticas de fiabilidad de la fuente
     * 
     * @param bool $exitoso TRUE si la consulta fue exitosa, FALSE en caso contrario
     */
    private function actualizarEstadisticas($exitoso) {
        foreach ($this->bancosDisponibles as $codigo) {
            $this->CI->SourceReliability_model->registrarConsulta(
                'criptoya',
                $codigo,
                $exitoso,
                $this->tiempoConsulta
            );
        }
    }
}