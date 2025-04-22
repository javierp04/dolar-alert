<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Configuracion_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Obtiene todas las configuraciones
     */
    public function obtener_configuraciones() {
        $query = $this->db->get('configuraciones');
        $result = [];
        
        foreach ($query->result() as $row) {
            $result[$row->clave] = $row->valor;
        }
        
        return $result;
    }
    
    /**
     * Obtiene una configuración específica
     */
    public function obtener_configuracion($clave, $default = NULL) {
        $this->db->where('clave', $clave);
        $query = $this->db->get('configuraciones');
        
        if ($query->num_rows() > 0) {
            return $query->row()->valor;
        }
        
        return $default;
    }
    
    /**
     * Guarda una configuración
     */
    public function guardar_configuracion($clave, $valor) {
        $this->db->where('clave', $clave);
        $query = $this->db->get('configuraciones');
        
        if ($query->num_rows() > 0) {
            $this->db->where('clave', $clave);
            $this->db->update('configuraciones', ['valor' => $valor]);
        } else {
            $this->db->insert('configuraciones', [
                'clave' => $clave,
                'valor' => $valor
            ]);
        }
        
        return TRUE;
    }
    
    /**
     * Guarda múltiples configuraciones
     */
    public function guardar_configuraciones($datos) {
        foreach ($datos as $clave => $valor) {
            $this->guardar_configuracion($clave, $valor);
        }
        
        return TRUE;
    }
}