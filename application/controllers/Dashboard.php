<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->library('WebScraper');
    }
    
    public function index() {
        $data['title'] = 'Dashboard - Monitor de Dólares';
        
        // Obtener dólares habilitados (incluyendo códigos y nombres)
        $dolares_habilitados = $this->Dolar_model->obtener_dolares_habilitados();
        $data['dolares_habilitados'] = $dolares_habilitados;
        
        // Crear array de códigos habilitados para facilitar la búsqueda
        $codigos_habilitados = ['cocos']; // Siempre incluimos cocos
        foreach ($dolares_habilitados as $dolar) {
            $codigos_habilitados[] = $dolar->codigo;
        }
        $data['codigos_habilitados'] = $codigos_habilitados;
        
        // Obtener últimas cotizaciones pero filtrar solo las habilitadas
        $todas_cotizaciones = $this->Cotizacion_model->obtener_ultimas_por_tipo();
        $cotizaciones_filtradas = [];
        
        foreach ($todas_cotizaciones as $cotizacion) {
            if (in_array($cotizacion->tipo, $codigos_habilitados)) {
                $cotizaciones_filtradas[] = $cotizacion;
            }
        }
        
        // Encontrar el dólar cocos para comparar
        $cocos = null;
        foreach ($cotizaciones_filtradas as $cotizacion) {
            if ($cotizacion->tipo === 'cocos') {
                $cocos = $cotizacion;
                break;
            }
        }
        $data['cocos'] = $cocos;
        
        // Separar cocos de los otros dólares (igual que en ajax_cotizaciones)
        $otros_dolares = [];
        foreach ($cotizaciones_filtradas as $cotizacion) {
            if ($cotizacion->tipo !== 'cocos') {
                $otros_dolares[] = $cotizacion;
            }
        }
        
        // Ordenar por precio de venta (de menor a mayor)
        usort($otros_dolares, function($a, $b) {
            return $a->venta - $b->venta;
        });
        
        // Reconstruir el array con cocos primero y luego los otros ordenados
        $todas_ordenadas = [];
        if ($cocos) {
            $todas_ordenadas[] = $cocos;
        }
        $todas_ordenadas = array_merge($todas_ordenadas, $otros_dolares);
        
        // Actualizar las cotizaciones con las ordenadas
        $data['ultimas_cotizaciones'] = $todas_ordenadas;
        
        // Obtener configuraciones
        $data['config'] = $this->Configuracion_model->obtener_configuraciones();
        
        // Cargar vistas
        $this->load->view('templates/header', $data);
        $this->load->view('dashboard/index', $data);
        $this->load->view('templates/footer');
    }
    
    /**
     * Endpoint AJAX para obtener los datos actualizados del gráfico
     */
    public function ajax_chart_data() {
        // Devolver como JSON
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($this->get_chart_data()));
    }
    
    /**
     * Endpoint AJAX para obtener la tabla de cotizaciones actualizada
     */
    public function ajax_cotizaciones() {
        // Obtener dólares habilitados
        $dolares_habilitados = $this->Dolar_model->obtener_dolares_habilitados();
        
        // Crear array de códigos habilitados para filtrar
        $codigos_habilitados = ['cocos']; // Siempre incluimos cocos
        foreach ($dolares_habilitados as $dolar) {
            $codigos_habilitados[] = $dolar->codigo;
        }
        
        // Obtener últimas cotizaciones
        $todas_cotizaciones = $this->Cotizacion_model->obtener_ultimas_por_tipo();
        $cotizaciones_filtradas = [];
        
        foreach ($todas_cotizaciones as $cotizacion) {
            if (in_array($cotizacion->tipo, $codigos_habilitados)) {
                $cotizaciones_filtradas[] = $cotizacion;
            }
        }
        
        // Encontrar el dólar cocos para comparar
        $cocos = null;
        foreach ($cotizaciones_filtradas as $cotizacion) {
            if ($cotizacion->tipo === 'cocos') {
                $cocos = $cotizacion;
                break;
            }
        }
        
        // Separar cocos de los otros dólares
        $otros_dolares = [];
        foreach ($cotizaciones_filtradas as $cotizacion) {
            if ($cotizacion->tipo !== 'cocos') {
                $otros_dolares[] = $cotizacion;
            }
        }
        
        // Ordenar por precio de venta (de menor a mayor)
        usort($otros_dolares, function($a, $b) {
            return $a->venta - $b->venta;
        });
        
        // Reconstruir el array con cocos primero y luego los otros ordenados
        $todas_ordenadas = [];
        if ($cocos) {
            $todas_ordenadas[] = $cocos;
        }
        $todas_ordenadas = array_merge($todas_ordenadas, $otros_dolares);
        
        // Preparar datos para la vista
        $data['ultimas_cotizaciones'] = $todas_ordenadas;
        $data['cocos'] = $cocos;
        
        // Cargar vista parcial y devolverla como HTML
        $html = $this->load->view('dashboard/partials/tabla_cotizaciones', $data, TRUE);
        
        // Devolver como JSON
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['html' => $html]));
    }
    
    /**
     * Obtiene los datos de dólares habilitados para el gráfico
     */
    private function get_chart_data() {
        $limite = 30;
        $labels = [];
        $datasets = [];
    
        // Obtener todos los dólares
        $dolares = $this->Dolar_model->obtener_dolares();
        $nombres = [];
        foreach ($dolares as $dolar) {
            $nombres[$dolar->codigo] = $dolar->nombre;
        }
    
        // Obtener el valor de compra de cocos
        $cocos_compra = $this->db->select('compra')
            ->from('cotizaciones')
            ->where('tipo', 'cocos')
            ->order_by('fecha_hora', 'DESC')
            ->limit(1)
            ->get()
            ->row('compra');
    
        if (!$cocos_compra) {
            return ['labels' => [], 'datasets' => []]; // No hay datos de cocos
        }
    
        // Definir colores por tipo
        $colores = [
            'cocos' => ['rgba(75, 192, 192, 1)', 'rgba(75, 192, 192, 0.2)'],
            'bbva' => ['rgba(153, 102, 255, 1)', 'rgba(153, 102, 255, 0.2)'],
            'santander' => ['rgba(255, 99, 132, 1)', 'rgba(255, 99, 132, 0.2)'],
            'galicia' => ['rgba(255, 206, 86, 1)', 'rgba(255, 206, 86, 0.2)'],
            'brubank' => ['rgba(201, 203, 207, 1)', 'rgba(201, 203, 207, 0.2)'],
            'macro' => ['rgba(54, 162, 235, 1)', 'rgba(54, 162, 235, 0.2)'],
            'bna' => ['rgba(255, 159, 64, 1)', 'rgba(255, 159, 64, 0.2)'],
            'hsbc' => ['rgba(75, 75, 75, 1)', 'rgba(75, 75, 75, 0.2)'],
            'ciudad' => ['rgba(50, 168, 82, 1)', 'rgba(50, 168, 82, 0.2)'],
            'bapro' => ['rgba(138, 43, 226, 1)', 'rgba(138, 43, 226, 0.2)']
        ];
    
        // Obtener los tipos con su último precio de venta
        $tipos_validos = ['cocos'];
        $ultimos_precios = ['cocos' => $cocos_compra];
    
        foreach ($nombres as $codigo => $nombre) {
            if ($codigo === 'cocos') continue;
    
            $venta = $this->db->select('venta')
                ->from('cotizaciones')
                ->where('tipo', $codigo)
                ->order_by('fecha_hora', 'DESC')
                ->limit(1)
                ->get()
                ->row('venta');
    
            if ($venta && $venta < $cocos_compra) {
                $tipos_validos[] = $codigo;
                $ultimos_precios[$codigo] = $venta;
            }
        }
    
        // Ordenar por valor (cocos siempre primero)
        $tipos_sin_cocos = array_diff($tipos_validos, ['cocos']);
        usort($tipos_sin_cocos, fn($a, $b) => $ultimos_precios[$a] <=> $ultimos_precios[$b]);
        $tipos_ordenados = array_merge(['cocos'], $tipos_sin_cocos);
    
        // Construir datasets
        foreach ($tipos_ordenados as $tipo) {
            $campo = ($tipo === 'cocos') ? 'compra' : 'venta';
    
            $resultados = $this->db->select("fecha_hora, $campo as valor")
                ->from('cotizaciones')
                ->where('tipo', $tipo)
                ->order_by('fecha_hora', 'DESC')
                ->limit($limite)
                ->get()
                ->result();
    
            if (!empty($resultados)) {
                $resultados = array_reverse($resultados);
    
                if (empty($labels)) {
                    foreach ($resultados as $row) {
                        $labels[] = date('H:i', strtotime($row->fecha_hora));
                    }
                }
    
                $data = array_map(fn($r) => $r->valor, $resultados);
    
                $color_borde = $colores[$tipo][0] ?? $this->generar_color_rgb(1);
                $color_fondo = $colores[$tipo][1] ?? $this->generar_color_rgb(0.2);
    
                $datasets[] = [
                    'label' => $nombres[$tipo] ?? ucfirst($tipo),
                    'data' => $data,
                    'backgroundColor' => $color_fondo,
                    'borderColor' => $color_borde,
                    'borderWidth' => 2,
                    'tension' => 0.1,
                    'fill' => false
                ];
            }
        }
    
        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }
    
    private function generar_color_rgb($alpha = 1) {
        $r = rand(0, 255);
        $g = rand(0, 255);
        $b = rand(0, 255);
        
        return "rgba($r, $g, $b, $alpha)";
    }
    
    public function consultar_ahora() {
        // Ejecutar consulta de cotizaciones
        $resultado = $this->webscraper->consultar_cotizaciones();
        
        if ($resultado) {
            $this->session->set_flashdata('success', 'Cotizaciones consultadas correctamente.');
        } else {
            $this->session->set_flashdata('error', 'Error al consultar cotizaciones. Revise los logs para más información.');
        }
        
        redirect('dashboard');
    }
}