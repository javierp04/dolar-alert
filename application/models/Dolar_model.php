<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dolar_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Obtiene todos los dólares habilitados
     */
    public function obtener_dolares() {
        $this->db->select('*')
                 ->from('dolares_habilitados')
                 ->order_by('nombre');
                 
        return $this->db->get()->result();
    }
    
    /**
     * Obtiene un dólar por su ID
     */
    public function obtener_dolar($id) {
        $this->db->where('id', $id);
        return $this->db->get('dolares_habilitados')->row();
    }
    
    /**
     * Obtiene un dólar por su código
     */
    public function obtener_dolar_por_codigo($codigo) {
        $this->db->where('codigo', $codigo);
        return $this->db->get('dolares_habilitados')->row();
    }
    
    /**
     * Guarda un dólar (insertar o actualizar)
     */
    public function guardar_dolar($datos, $id = NULL) {
        if ($id) {
            $this->db->where('id', $id);
            $this->db->update('dolares_habilitados', $datos);
            return $id;
        } else {
            $this->db->insert('dolares_habilitados', $datos);
            return $this->db->insert_id();
        }
    }
    
    /**
     * Cambia el estado de un dólar (habilitar/deshabilitar)
     */
    public function cambiar_estado($id, $estado) {
        $this->db->where('id', $id);
        $this->db->update('dolares_habilitados', ['habilitado' => $estado]);
        return $this->db->affected_rows() > 0;
    }
    
    /**
     * Elimina un dólar
     */
    public function eliminar_dolar($id) {
        $this->db->where('id', $id);
        $this->db->delete('dolares_habilitados');
        return $this->db->affected_rows() > 0;
    }
    
    /**
     * Obtiene los dólares habilitados (para consultas)
     */
    public function obtener_dolares_habilitados() {
        $this->db->where('habilitado', 1);
        return $this->db->get('dolares_habilitados')->result();
    }
}