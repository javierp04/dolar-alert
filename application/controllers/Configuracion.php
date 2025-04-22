<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Configuracion extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('telegram');
    }

    public function index()
    {
        $data['title'] = 'Configuración - Monitor de Dólares';

        // Obtener configuraciones
        $data['config'] = $this->Configuracion_model->obtener_configuraciones();

        // Cargar vistas
        $this->load->view('templates/header', $data);
        $this->load->view('configuracion/general', $data);
        $this->load->view('templates/footer');
    }

    public function dolares()
    {
        $data['title'] = 'Configuración de Dólares - Monitor de Dólares';

        // Obtener dólares
        $dolares = $this->Dolar_model->obtener_dolares();

        // Obtener las últimas cotizaciones de manera directa con una sola consulta
        $this->db->select('tipo, venta')
            ->from('cotizaciones c1')
            ->where('fecha_hora = (SELECT MAX(fecha_hora) FROM cotizaciones c2 WHERE c2.tipo = c1.tipo)');
        $query = $this->db->get();

        // Formatear resultados en un array asociativo
        $cotizaciones = [];
        foreach ($query->result() as $row) {
            $cotizaciones[$row->tipo] = $row->venta;
        }

        // Separar el dólar Cocos (no se muestra en la tabla pero se usa como referencia)
        $cocos = null;
        $dolares_bancos = [];

        foreach ($dolares as $dolar) {
            if ($dolar->codigo === 'cocos') {
                $cocos = $dolar;
            } else {
                // Asignar el precio actual si existe, o valor alto si no tiene cotización
                $dolar->precio_actual = isset($cotizaciones[$dolar->codigo])
                    ? $cotizaciones[$dolar->codigo]
                    : PHP_FLOAT_MAX;

                $dolares_bancos[] = $dolar;
            }
        }

        // ORDENAMIENTO CORREGIDO: primero por habilitado (1) y luego por precio (menor a mayor)
        usort($dolares_bancos, function ($a, $b) {
            // Primero ordenar por estado de habilitación
            if ($a->habilitado != $b->habilitado) {
                return $b->habilitado - $a->habilitado; // Habilitados primero (orden descendente)
            }

            // Si ambos tienen el mismo estado, ordenar por precio actual
            return $a->precio_actual - $b->precio_actual; // Menor a mayor (orden ascendente)
        });

        $data['dolares'] = $dolares_bancos;
        $data['cotizaciones'] = $cotizaciones;
        $data['cocos'] = $cocos;

        // Cargar vistas
        $this->load->view('templates/header', $data);
        $this->load->view('configuracion/dolares', $data);
        $this->load->view('templates/footer');
    }

    public function guardar()
    {
        // Validar formulario
        $this->load->library('form_validation');

        $this->form_validation->set_rules('telegram_bot_token', 'Token de Telegram', 'required|trim');
        $this->form_validation->set_rules('telegram_chat_id', 'Chat ID de Telegram', 'required|trim');
        $this->form_validation->set_rules('intervalo_consulta', 'Intervalo de consulta', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('umbral_global', 'Umbral global', 'required|numeric|greater_than[0]');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('configuracion');
            return;
        }

        // Obtener datos del formulario
        $configuraciones = [
            'telegram_bot_token' => $this->input->post('telegram_bot_token'),
            'telegram_chat_id' => $this->input->post('telegram_chat_id'),
            'intervalo_consulta' => $this->input->post('intervalo_consulta'),
            'umbral_global' => $this->input->post('umbral_global')
        ];

        // Guardar configuraciones
        $resultado = $this->Configuracion_model->guardar_configuraciones($configuraciones);

        // Actualizar el umbral en todos los dólares
        $umbral_global = $this->input->post('umbral_global');
        $this->db->update('dolares_habilitados', ['umbral_diferencia' => $umbral_global]);

        if ($resultado) {
            $this->session->set_flashdata('success', 'Configuración guardada correctamente y umbral aplicado a todos los dólares.');

            // Probar configuración de Telegram si se solicita
            if ($this->input->post('probar_telegram')) {
                $resultado_telegram = enviar_mensaje_prueba_telegram(
                    $configuraciones['telegram_bot_token'],
                    $configuraciones['telegram_chat_id']
                );

                if ($resultado_telegram) {
                    $this->session->set_flashdata('success', 'Configuración guardada y mensaje de prueba enviado correctamente.');
                } else {
                    $this->session->set_flashdata('warning', 'Configuración guardada pero hubo un error al enviar el mensaje de prueba. Revise las credenciales de Telegram.');
                }
            }
        } else {
            $this->session->set_flashdata('error', 'Error al guardar la configuración.');
        }

        redirect('configuracion');
    }

    public function guardar_dolar()
    {
        // Validar formulario
        $this->load->library('form_validation');

        $this->form_validation->set_rules('codigo', 'Código', 'required|trim|alpha_dash');
        $this->form_validation->set_rules('nombre', 'Nombre', 'required|trim');
        $this->form_validation->set_rules('umbral_diferencia', 'Umbral de diferencia', 'required|numeric|greater_than[0]');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('configuracion/dolares');
            return;
        }

        // Obtener datos del formulario
        $dolar = [
            'codigo' => $this->input->post('codigo'),
            'nombre' => $this->input->post('nombre'),
            'umbral_diferencia' => $this->input->post('umbral_diferencia'),
            'habilitado' => 1
        ];

        // Verificar si es edición o nuevo
        $id = $this->input->post('id');

        // Guardar dólar
        $resultado = $this->Dolar_model->guardar_dolar($dolar, $id);

        if ($resultado) {
            $this->session->set_flashdata('success', 'Dólar guardado correctamente.');
        } else {
            $this->session->set_flashdata('error', 'Error al guardar el dólar.');
        }

        redirect('configuracion/dolares');
    }

    public function cambiar_estado_dolar($id)
    {
        // Obtener dólar
        $dolar = $this->Dolar_model->obtener_dolar($id);

        if (!$dolar) {
            $this->session->set_flashdata('error', 'Dólar no encontrado.');
            redirect('configuracion/dolares');
            return;
        }

        // Cambiar estado
        $nuevo_estado = $dolar->habilitado ? 0 : 1;
        $resultado = $this->Dolar_model->cambiar_estado($id, $nuevo_estado);

        if ($resultado) {
            $estado_texto = $nuevo_estado ? 'habilitado' : 'deshabilitado';
            $this->session->set_flashdata('success', "Dólar $estado_texto correctamente.");
        } else {
            $this->session->set_flashdata('error', 'Error al cambiar el estado del dólar.');
        }

        redirect('configuracion/dolares');
    }

    public function eliminar_dolar($id)
    {
        $resultado = $this->Dolar_model->eliminar_dolar($id);

        if ($resultado) {
            $this->session->set_flashdata('success', 'Dólar eliminado correctamente.');
        } else {
            $this->session->set_flashdata('error', 'Error al eliminar el dólar.');
        }

        redirect('configuracion/dolares');
    }
}
