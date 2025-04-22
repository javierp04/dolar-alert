<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cotizacion_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Guarda una cotización en la base de datos
     */
    public function guardar_cotizacion($datos)
    {
        $this->db->insert('cotizaciones', $datos);
        return $this->db->insert_id();
    }

    /**
     * Obtiene las últimas cotizaciones
     */
    public function obtener_ultimas_cotizaciones($limite = 10)
    {
        $this->db->select('c.*, d.nombre')
            ->from('cotizaciones c')
            ->join('dolares_habilitados d', 'c.tipo = d.codigo', 'left')
            ->order_by('c.fecha_hora', 'DESC')
            ->limit($limite);

        return $this->db->get()->result();
    }

    /**
     * Obtiene las últimas cotizaciones agrupadas por tipo
     */
    public function obtener_ultimas_por_tipo()
    {
        $subquery = $this->db->select('tipo, MAX(fecha_hora) as ultima_fecha')
            ->from('cotizaciones')
            ->group_by('tipo')
            ->get_compiled_select();

        $this->db->select('c.*, d.nombre')
            ->from('cotizaciones c')
            ->join("($subquery) u", 'c.tipo = u.tipo AND c.fecha_hora = u.ultima_fecha', 'inner')
            ->join('dolares_habilitados d', 'c.tipo = d.codigo', 'left')
            ->order_by('c.tipo');

        return $this->db->get()->result();
    }

    /**
     * Obtiene los datos para gráficos
     */
    public function obtener_datos_grafico($tipo, $dias = 7)
    {
        $fecha_limite = date('Y-m-d H:i:s', strtotime("-$dias days"));

        $this->db->select('DATE(fecha_hora) as fecha, AVG(compra) as compra, AVG(venta) as venta')
            ->from('cotizaciones')
            ->where('tipo', $tipo)
            ->where('fecha_hora >=', $fecha_limite)
            ->group_by('DATE(fecha_hora)')
            ->order_by('fecha');

        return $this->db->get()->result();
    }

    /**
     * Obtiene el historial de diferencias
     */
    public function obtener_historial_diferencias($dias = 7)
    {
        $fecha_limite = date('Y-m-d H:i:s', strtotime("-$dias days"));

        $query = $this->db->query("
            SELECT 
                DATE(c1.fecha_hora) as fecha,
                c1.tipo,
                d.nombre,
                AVG(((c1.venta - c2.compra) / c2.compra) * 100) as diferencia_promedio
            FROM 
                cotizaciones c1
            JOIN 
                (SELECT fecha_hora, compra FROM cotizaciones WHERE tipo = 'cocos') c2 
                ON DATE(c1.fecha_hora) = DATE(c2.fecha_hora)
            JOIN
                dolares_habilitados d ON c1.tipo = d.codigo
            WHERE 
                c1.fecha_hora >= ? AND
                c1.tipo != 'cocos' AND
                d.habilitado = 1
            GROUP BY 
                DATE(c1.fecha_hora), c1.tipo
            ORDER BY 
                fecha, c1.tipo
        ", [$fecha_limite]);

        return $query->result();
    }

    /**
     * Obtiene la última cotización para una fuente y tipo específicos
     * @param string $fuente Fuente de la cotización (dolarya, criptoya, etc.)
     * @param string $tipo Tipo de cotización (cocos, bbva, etc.)
     * @return object|null Último registro de cotización o NULL si no existe
     */
    public function obtener_ultima_cotizacion($fuente, $tipo)
    {
        $this->db->where('fuente', $fuente)
                 ->where('tipo', $tipo)
                 ->order_by('fecha_hora', 'DESC')
                 ->limit(1);
        
        $query = $this->db->get('cotizaciones');
        
        if ($query->num_rows() > 0) {
            return $query->row();
        }
        
        return null;
    }
}