<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Modelo para gestionar la fiabilidad de las fuentes de datos
 * 
 * Este modelo se encarga de registrar y analizar el rendimiento de las diferentes
 * fuentes de datos para permitir el aprendizaje automático del sistema.
 */
class SourceReliability_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Registra una consulta a una fuente para un código de dólar específico
     * 
     * @param string $fuente Nombre de la fuente
     * @param string $codigo_dolar Código del dólar
     * @param bool $exitosa Indica si la consulta fue exitosa
     * @param int $tiempo_respuesta Tiempo de respuesta en milisegundos
     * @return bool Resultado de la operación
     */
    public function registrarConsulta($fuente, $codigo_dolar, $exitosa, $tiempo_respuesta) {
        // Verificar si ya existe un registro para esta fuente y código
        $this->db->where('fuente', $fuente);
        $this->db->where('codigo_dolar', $codigo_dolar);
        $query = $this->db->get('source_reliability');
        
        $now = date('Y-m-d H:i:s');
        
        // Si existe, actualizar el registro
        if ($query->num_rows() > 0) {
            $registro = $query->row();
            
            $consultas_totales = $registro->consultas_totales + 1;
            $consultas_exitosas = $registro->consultas_exitosas + ($exitosa ? 1 : 0);
            
            // Actualizar tiempo de respuesta promedio
            $tiempo_respuesta_avg = $registro->tiempo_respuesta_avg;
            if ($exitosa) {
                // Media móvil ponderada para suavizar cambios
                $tiempo_respuesta_avg = ($tiempo_respuesta_avg * 0.7) + ($tiempo_respuesta * 0.3);
            }
            
            $this->db->where('id', $registro->id);
            $this->db->update('source_reliability', [
                'consultas_totales' => $consultas_totales,
                'consultas_exitosas' => $consultas_exitosas,
                'ultima_actualizacion' => $now,
                'tiempo_respuesta_avg' => $tiempo_respuesta_avg
            ]);
        }
        // Si no existe, crear un nuevo registro
        else {
            $this->db->insert('source_reliability', [
                'fuente' => $fuente,
                'codigo_dolar' => $codigo_dolar,
                'consultas_totales' => 1,
                'consultas_exitosas' => $exitosa ? 1 : 0,
                'ultima_actualizacion' => $now,
                'precision_historica' => 0.0,
                'tiempo_respuesta_avg' => $exitosa ? $tiempo_respuesta : 0
            ]);
        }
        
        return $this->db->affected_rows() > 0;
    }
    
    /**
     * Obtiene los datos de fiabilidad para una fuente y dólar específicos
     * 
     * @param string $fuente Nombre de la fuente
     * @param string $codigo_dolar Código del dólar
     * @return object|null Objeto con datos de fiabilidad o NULL si no existe
     */
    public function obtenerFiabilidad($fuente, $codigo_dolar) {
        $this->db->where('fuente', $fuente);
        $this->db->where('codigo_dolar', $codigo_dolar);
        $query = $this->db->get('source_reliability');
        
        if ($query->num_rows() > 0) {
            return $query->row();
        }
        
        return null;
    }
    
    /**
     * Actualiza la precisión histórica comparando con datos reales
     * 
     * @param string $fuente Nombre de la fuente
     * @param string $codigo_dolar Código del dólar
     * @param float $valor_predicho Valor predicho por la fuente
     * @param float $valor_real Valor real verificado
     * @return bool Resultado de la operación
     */
    public function actualizarPrecision($fuente, $codigo_dolar, $valor_predicho, $valor_real) {
        // Obtener registro actual
        $fiabilidad = $this->obtenerFiabilidad($fuente, $codigo_dolar);
        
        if (!$fiabilidad) {
            return false;
        }
        
        // Calcular error relativo
        $error_relativo = abs(($valor_predicho - $valor_real) / $valor_real);
        $precision = 1 - min(1, $error_relativo); // Entre 0 y 1, siendo 1 perfecto
        
        // Actualizar precisión histórica con una media móvil ponderada
        $precision_historica = ($fiabilidad->precision_historica * 0.8) + ($precision * 0.2);
        
        // Guardar nueva precisión
        $this->db->where('id', $fiabilidad->id);
        $this->db->update('source_reliability', [
            'precision_historica' => $precision_historica
        ]);
        
        return $this->db->affected_rows() > 0;
    }
    
    /**
     * Obtiene un resumen de la fiabilidad de todas las fuentes
     * 
     * @return array Resumen de fiabilidad por fuente
     */
    public function obtenerResumenFiabilidad() {
        $query = $this->db->query("
            SELECT 
                fuente,
                COUNT(*) as total_bancos,
                AVG(consultas_exitosas / consultas_totales) as tasa_exito_promedio,
                AVG(precision_historica) as precision_promedio,
                AVG(tiempo_respuesta_avg) as tiempo_respuesta_promedio
            FROM source_reliability
            GROUP BY fuente
        ");
        
        return $query->result();
    }
    
    /**
     * Obtiene la fuente más fiable para un dólar específico
     * 
     * @param string $codigo_dolar Código del dólar
     * @return string|null Nombre de la fuente más fiable o NULL si no hay datos
     */
    public function obtenerFuenteMasConfiable($codigo_dolar) {
        $this->db->where('codigo_dolar', $codigo_dolar);
        $this->db->order_by('precision_historica', 'DESC');
        $this->db->order_by('consultas_exitosas / consultas_totales', 'DESC');
        $this->db->limit(1);
        
        $query = $this->db->get('source_reliability');
        
        if ($query->num_rows() > 0) {
            return $query->row()->fuente;
        }
        
        return null;
    }
}