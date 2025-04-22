<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'libraries/DataSources/DataSourceInterface.php';
require_once APPPATH . 'libraries/DataSources/CriptoYaDataSource.php';
require_once APPPATH . 'libraries/DataSources/InfoDolarDataSource.php';
require_once APPPATH . 'libraries/DataSources/BankCodeMapper.php';

/**
 * Agregador de fuentes de datos de cotizaciones
 * 
 * Coordina las múltiples fuentes de datos y unifica los resultados
 */
class DataSourceAggregator {
    
    protected $CI;
    private $fuentesDisponibles = [];
    private $cotizacionesPorFuente = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Dolar_model');
        $this->CI->load->model('SourceReliability_model');
        
        // Inicializar fuentes disponibles
        $this->fuentesDisponibles = [
            'criptoya' => new CriptoYaDataSource(),
            'infodolar' => new InfoDolarDataSource()
        ];
    }
    
    /**
     * Consulta todas las fuentes de datos disponibles
     * 
     * @return array Resultados combinados de todas las fuentes
     */
    public function consultarTodasLasFuentes() {
        $this->cotizacionesPorFuente = [];
        
        // Consultar cada fuente
        foreach ($this->fuentesDisponibles as $nombre => $fuente) {
            log_message('info', "Consultando fuente: $nombre");
            $cotizaciones = $fuente->obtenerCotizaciones();
            
            if ($cotizaciones !== false) {
                $this->cotizacionesPorFuente[$nombre] = $cotizaciones;
                log_message('info', "Fuente $nombre consultada correctamente. Bancos obtenidos: " . count($cotizaciones));
            } else {
                $this->cotizacionesPorFuente[$nombre] = [];
                log_message('error', "Error al consultar fuente: $nombre");
            }
        }
        
        return $this->cotizacionesPorFuente;
    }
    
    /**
     * Obtiene los datos de cotizaciones unificados según las preferencias
     * 
     * @return array Cotizaciones unificadas con la fuente preferida para cada banco
     */
    public function obtenerCotizacionesUnificadas() {
        // Si no se han consultado las fuentes, hacerlo ahora
        if (empty($this->cotizacionesPorFuente)) {
            $this->consultarTodasLasFuentes();
        }
        
        // Obtener dólares habilitados con sus preferencias de fuente
        $dolares_habilitados = $this->CI->Dolar_model->obtener_dolares_habilitados();
        
        $resultado = [];
        $log_fallbacks = [];
        
        foreach ($dolares_habilitados as $dolar) {
            $codigo = $dolar->codigo;
            $fuente_preferida = $dolar->fuente_preferida;
            
            // Omitir Cocos - se maneja por separado
            if ($codigo === 'cocos') {
                continue;
            }
            
            // Intentar obtener de la fuente preferida
            $cotizacion = $this->obtenerCotizacionDeFuente($codigo, $fuente_preferida);
            $fallback_usado = false;
            
            // Si no se encuentra en la fuente preferida, usar la otra como fallback
            if ($cotizacion === null) {
                $fuente_fallback = ($fuente_preferida === 'criptoya') ? 'infodolar' : 'criptoya';
                $cotizacion = $this->obtenerCotizacionDeFuente($codigo, $fuente_fallback);
                
                if ($cotizacion !== null) {
                    $fallback_usado = true;
                    $log_fallbacks[] = "Usado fallback para $codigo: de $fuente_preferida a $fuente_fallback";
                }
            }
            
            // Si se encontró la cotización en alguna fuente, agregarla al resultado
            if ($cotizacion !== null) {
                $cotizacion['fallback_usado'] = $fallback_usado;
                $cotizacion['fuente_preferida'] = $fuente_preferida;
                $resultado[$codigo] = $cotizacion;
            }
        }
        
        // Registrar información sobre fallbacks
        if (!empty($log_fallbacks)) {
            log_message('info', "Fallbacks utilizados: " . implode(', ', $log_fallbacks));
        }
        
        return $resultado;
    }
    
    /**
     * Obtiene la cotización de un dólar de una fuente específica
     * 
     * @param string $codigo Código del dólar
     * @param string $fuente Nombre de la fuente
     * @return array|null Datos de la cotización o NULL si no está disponible
     */
    private function obtenerCotizacionDeFuente($codigo, $fuente) {
        // Verificar si tenemos datos de esa fuente
        if (!isset($this->cotizacionesPorFuente[$fuente]) || empty($this->cotizacionesPorFuente[$fuente])) {
            return null;
        }
        
        // Para CriptoYa, el formato es directo
        if ($fuente === 'criptoya') {
            if (isset($this->cotizacionesPorFuente['criptoya'][$codigo])) {
                return $this->cotizacionesPorFuente['criptoya'][$codigo];
            }
        }
        // Para InfoDolar, necesitamos convertir el código
        else if ($fuente === 'infodolar') {
            $codigo_infodolar = BankCodeMapper::convertirCodigo($codigo, 'criptoya', 'infodolar');
            
            if ($codigo_infodolar && isset($this->cotizacionesPorFuente['infodolar'][$codigo_infodolar])) {
                $cotizacion = $this->cotizacionesPorFuente['infodolar'][$codigo_infodolar];
                
                // Normalizar formato
                return [
                    'totalBid' => $cotizacion['compra'],
                    'totalAsk' => $cotizacion['venta'],
                    'source' => 'infodolar',
                    'timestamp' => $cotizacion['timestamp'],
                    'codigo_original' => $codigo_infodolar
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Actualiza la fuente preferida automáticamente según el histórico de fiabilidad
     * 
     * @return int Número de bancos actualizados
     */
    public function actualizarFuentesPreferidas() {
        $dolares = $this->CI->Dolar_model->obtener_dolares();
        $contador = 0;
        
        foreach ($dolares as $dolar) {
            // Omitir Cocos - se maneja por separado
            if ($dolar->codigo === 'cocos') {
                continue;
            }
            
            // Obtener estadísticas de fiabilidad para ambas fuentes
            $fiabilidad_criptoya = $this->CI->SourceReliability_model->obtenerFiabilidad('criptoya', $dolar->codigo);
            $fiabilidad_infodolar = $this->CI->SourceReliability_model->obtenerFiabilidad('infodolar', $dolar->codigo);
            
            // Si no hay datos de fiabilidad, continuar
            if (!$fiabilidad_criptoya && !$fiabilidad_infodolar) {
                continue;
            }
            
            // Determinar la mejor fuente según el índice de fiabilidad
            $mejor_fuente = $this->determinarMejorFuente($fiabilidad_criptoya, $fiabilidad_infodolar);
            
            // Si la mejor fuente es diferente a la actual, actualizarla
            if ($mejor_fuente && $mejor_fuente !== $dolar->fuente_preferida) {
                $this->CI->Dolar_model->actualizar_fuente_preferida($dolar->id, $mejor_fuente);
                log_message('info', "Actualizada fuente preferida para {$dolar->codigo}: de {$dolar->fuente_preferida} a {$mejor_fuente}");
                $contador++;
            }
        }
        
        return $contador;
    }
    
    /**
     * Determina la mejor fuente de datos según las estadísticas de fiabilidad
     * 
     * @param array|null $fiabilidad_criptoya Datos de fiabilidad de CriptoYa
     * @param array|null $fiabilidad_infodolar Datos de fiabilidad de InfoDolar
     * @return string|null Nombre de la mejor fuente o NULL si no se puede determinar
     */
    private function determinarMejorFuente($fiabilidad_criptoya, $fiabilidad_infodolar) {
        // Si solo hay datos de una fuente, usar esa
        if ($fiabilidad_criptoya && !$fiabilidad_infodolar) {
            return 'criptoya';
        }
        if (!$fiabilidad_criptoya && $fiabilidad_infodolar) {
            return 'infodolar';
        }
        
        // Factor de peso para cada métrica
        $pesos = [
            'tasa_exito' => 0.5,  // 50% - Tasa de éxito (consultas exitosas / total)
            'precision' => 0.3,   // 30% - Precisión histórica
            'velocidad' => 0.2    // 20% - Velocidad de respuesta
        ];
        
        // Calcular puntuación ponderada para CriptoYa
        $tasa_exito_criptoya = $fiabilidad_criptoya->consultas_exitosas / $fiabilidad_criptoya->consultas_totales;
        $puntuacion_criptoya = ($tasa_exito_criptoya * $pesos['tasa_exito']) +
                              ($fiabilidad_criptoya->precision_historica * $pesos['precision']) +
                              ((1 - min(1, $fiabilidad_criptoya->tiempo_respuesta_avg / 1000)) * $pesos['velocidad']);
        
        // Calcular puntuación ponderada para InfoDolar
        $tasa_exito_infodolar = $fiabilidad_infodolar->consultas_exitosas / $fiabilidad_infodolar->consultas_totales;
        $puntuacion_infodolar = ($tasa_exito_infodolar * $pesos['tasa_exito']) +
                               ($fiabilidad_infodolar->precision_historica * $pesos['precision']) +
                               ((1 - min(1, $fiabilidad_infodolar->tiempo_respuesta_avg / 1000)) * $pesos['velocidad']);
        
        // Determinar la mejor fuente según la puntuación
        if ($puntuacion_criptoya >= $puntuacion_infodolar) {
            return 'criptoya';
        } else {
            return 'infodolar';
        }
    }
}