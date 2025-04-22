<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Envía un mensaje a un chat de Telegram
 * 
 * @param string $mensaje Mensaje a enviar (soporta Markdown)
 * @param string $token Token del bot (opcional, si no se proporciona se obtiene de la configuración)
 * @param string $chat_id ID del chat (opcional, si no se proporciona se obtiene de la configuración)
 * @return bool True si se envió correctamente, False en caso contrario
 */
function enviar_mensaje_telegram($mensaje, $token = NULL, $chat_id = NULL) {
    $CI =& get_instance();
    $CI->load->model('Configuracion_model');
    
    // Si no se proporcionan token o chat_id, obtenerlos de la configuración
    if (!$token) {
        $token = $CI->Configuracion_model->obtener_configuracion('telegram_bot_token');
    }
    
    if (!$chat_id) {
        $chat_id = $CI->Configuracion_model->obtener_configuracion('telegram_chat_id');
    }
    
    // Verificar que se tengan los datos necesarios
    if (empty($token) || empty($chat_id)) {
        log_message('error', 'Error al enviar mensaje Telegram: Token o Chat ID no configurados');
        return FALSE;
    }
    
    // Preparar la URL y los parámetros
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $params = [
        'chat_id' => $chat_id,
        'text' => $mensaje,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => TRUE
    ];
    
    // Inicializar cURL
    $ch = curl_init();
    
    // Configurar la petición
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    
    // Ejecutar la petición
    $response = curl_exec($ch);
    $err = curl_error($ch);
    
    // Cerrar cURL
    curl_close($ch);
    
    // Verificar si hubo errores
    if ($err) {
        log_message('error', 'Error al enviar mensaje Telegram: ' . $err);
        return FALSE;
    }
    
    // Decodificar la respuesta
    $result = json_decode($response, TRUE);
    
    // Verificar si la petición fue exitosa
    if (isset($result['ok']) && $result['ok'] === TRUE) {
        log_message('info', 'Mensaje enviado correctamente a Telegram');
        return TRUE;
    } else {
        log_message('error', 'Error al enviar mensaje Telegram: ' . json_encode($result));
        return FALSE;
    }
}

/**
 * Envía un mensaje de prueba a Telegram
 * 
 * @param string $token Token del bot
 * @param string $chat_id ID del chat
 * @return bool True si se envió correctamente, False en caso contrario
 */
function enviar_mensaje_prueba_telegram($token, $chat_id) {
    $mensaje = "🔔 *Mensaje de prueba* 🔔\n\n";
    $mensaje .= "Este es un mensaje de prueba desde el sistema de monitoreo de dólares.\n\n";
    $mensaje .= "📅 *Fecha*: " . date('d/m/Y H:i:s');
    
    return enviar_mensaje_telegram($mensaje, $token, $chat_id);
}