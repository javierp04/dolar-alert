<?php
// URL de origen
$url = 'https://www.dolarya.info/';

// Inicializar cURL
$ch = curl_init();

// Opciones de cURL
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Opcional: definir headers para simular navegador real
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 ' .
    '(KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36'
]);

// Evitar errores por SSL (solo si da problemas)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// Ejecutar y obtener resultado
$html = curl_exec($ch);

// Verificar errores
if (curl_errno($ch)) {
    echo 'Error: ' . curl_error($ch);
} else {
    echo $html;
}

// Cerrar conexión
curl_close($ch);
?>