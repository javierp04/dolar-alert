<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'libraries/DataSources/DataSourceInterface.php';

/**
 * Implementación de DataSourceInterface para InfoDolar
 * 
 * Se encarga de obtener las cotizaciones de dólares del sitio InfoDolar mediante web scraping
 */
class InfoDolarDataSource implements DataSourceInterface {
    
    protected $CI;
    private $bancosDisponibles = [];
    private $tiempoConsulta = 0;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('SourceReliability_model');
        
        // Bancos disponibles en InfoDolar
        $this->bancosDisponibles = BankCodeMapper::getCodigosDisponibles('infodolar');
    }
    
    /**
     * Obtiene las cotizaciones de dólares de InfoDolar
     * 
     * @return array|bool Datos de cotizaciones en formato unificado o FALSE en caso de error
     */
    public function obtenerCotizaciones() {
        $inicio = microtime(true);
        
        // Crear directorio de logs si no existe
        $log_dir = APPPATH . 'logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        
        // Verificar permisos de escritura
        if (!is_writable($log_dir)) {
            error_log("ERROR: No se puede escribir en el directorio de logs: $log_dir");
        }
        
        $debug_log = fopen(APPPATH . 'logs/infodolar_debug.txt', 'a');
        fwrite($debug_log, "\n\n--- INICIO DEBUG " . date('Y-m-d H:i:s') . " ---\n");
        fwrite($debug_log, "Directorio de logs: $log_dir\n");
        
        $curl = curl_init();
        
        // URL CORREGIDA - página principal
        $url = "https://www.infodolar.com/";
        fwrite($debug_log, "URL a consultar: $url\n");

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Accept: text/html,application/xhtml+xml,application/xml",
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
                "Accept-Language: es-ES,es;q=0.9,en;q=0.8",
                "Connection: keep-alive",
                "Cache-Control: no-cache"
            ],
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        $curl_info = curl_getinfo($curl);
        
        fwrite($debug_log, "Código HTTP: $httpcode\n");
        fwrite($debug_log, "Error CURL: " . ($err ? $err : "Ninguno") . "\n");
        fwrite($debug_log, "Tamaño de respuesta: " . strlen($response) . " bytes\n");
        fwrite($debug_log, "Tiempo de conexión: " . $curl_info['connect_time'] . " seg\n");
        fwrite($debug_log, "Tiempo total: " . $curl_info['total_time'] . " seg\n");

        curl_close($curl);
        
        $this->tiempoConsulta = round((microtime(true) - $inicio) * 1000); // Tiempo en milisegundos
        
        // Actualizar estadísticas de fiabilidad para todos los bancos
        $this->actualizarEstadisticas($httpcode == 200 && !$err);
        
        if ($err || $httpcode != 200) {
            fwrite($debug_log, "Error al consultar InfoDolar: " . $err . " (HTTP: $httpcode)\n");
            fclose($debug_log);
            return false;
        }

        // Para debug/diagnóstico
        $response_file = APPPATH . 'logs/infodolar_response.html';
        $bytes_written = file_put_contents($response_file, $response);
        fwrite($debug_log, "Archivo de respuesta escrito: $response_file ($bytes_written bytes)\n");
        
        // Guardar una muestra del HTML para análisis
        $html_sample = substr($response, 0, 500) . "...\n...\n" . substr($response, -500);
        fwrite($debug_log, "Muestra del HTML recibido:\n" . $html_sample . "\n");

        // Procesar el HTML con DOMDocument
        $dom = new DOMDocument();
        @$dom->loadHTML($response);
        $xpath = new DOMXPath($dom);
        
        // Tabla de cotizaciones en la página principal
        $tabla_entidades = $xpath->query('//div[@id="cotizacionesBancos"]//table');
        fwrite($debug_log, "Tabla de entidades encontrada: " . ($tabla_entidades->length > 0 ? "SÍ" : "NO") . "\n");
        
        // Buscar todas las filas de la tabla (tr)
        $filas = $xpath->query('//div[@id="cotizacionesBancos"]//table//tr');
        fwrite($debug_log, "Filas en tabla de entidades: " . $filas->length . "\n");
        
        if ($filas->length == 0) {
            // Intentar una búsqueda más genérica
            $filas = $xpath->query('//table//tr');
            fwrite($debug_log, "Filas en cualquier tabla: " . $filas->length . "\n");
            
            if ($filas->length == 0) {
                fwrite($debug_log, "No se encontraron filas de datos\n");
                fclose($debug_log);
                return false;
            }
        }
        
        $resultado = [];
        
        // Procesar cada fila
        foreach ($filas as $index => $fila) {
            fwrite($debug_log, "Procesando fila #$index\n");
            
            // Verificar si es una fila de encabezado
            $es_encabezado = $xpath->query('.//th', $fila)->length > 0;
            if ($es_encabezado) {
                fwrite($debug_log, "  Es fila de encabezado, saltando\n");
                continue;
            }
            
            // Extraer todas las celdas de la fila
            $celdas = $xpath->query('.//td', $fila);
            if ($celdas->length < 3) {
                fwrite($debug_log, "  Insuficientes celdas, saltando (tiene: " . $celdas->length . ")\n");
                continue;
            }
            
            // Extraer el nombre del banco (diferentes enfoques)
            $nombreBanco = "";
            
            // Intento 1: Buscar span con clase "nombre"
            $nombreNodes = $xpath->query('.//span[@class="nombre"]', $fila);
            if ($nombreNodes->length > 0) {
                $nombreBanco = trim($nombreNodes->item(0)->textContent);
                fwrite($debug_log, "  Nombre del banco (de span.nombre): '$nombreBanco'\n");
            } 
            // Intento 2: Buscar en la primera celda
            else {
                $nombreBanco = trim($celdas->item(0)->textContent);
                fwrite($debug_log, "  Nombre del banco (de primera celda): '$nombreBanco'\n");
            }
            
            $nombreBanco = preg_replace('/\s+/', ' ', $nombreBanco); // Normalizar espacios
            
            // Determinar el código correspondiente al banco
            $codigoBanco = $this->obtenerCodigoBanco($nombreBanco);
            
            if (!$codigoBanco) {
                fwrite($debug_log, "  No se pudo determinar código del banco, saltando\n");
                continue;
            }
            
            fwrite($debug_log, "  Código del banco: '$codigoBanco'\n");
            
            // Buscar valores de compra y venta
            // Intento 1: Buscar celdas con clase específica
            $compraNodes = $xpath->query('.//td[@class="colCompraVenta"]', $fila);
            
            if ($compraNodes->length >= 2) {
                $compraStr = trim($compraNodes->item(0)->textContent);
                $ventaStr = trim($compraNodes->item(1)->textContent);
                fwrite($debug_log, "  Usando celdas con clase colCompraVenta\n");
            } 
            // Intento 2: Usar segunda y tercera celda directamente
            else if ($celdas->length >= 3) {
                $compraStr = trim($celdas->item(1)->textContent);
                $ventaStr = trim($celdas->item(2)->textContent);
                fwrite($debug_log, "  Usando segunda y tercera celda\n");
            } else {
                fwrite($debug_log, "  No se pudieron encontrar valores de compra/venta\n");
                continue;
            }
            
            fwrite($debug_log, "  Compra (raw): '$compraStr'\n");
            fwrite($debug_log, "  Venta (raw): '$ventaStr'\n");
            
            // Extraer solo el valor principal sin las variaciones
            $compraStr = preg_replace('/\s+.*$/', '', $compraStr);
            $ventaStr = preg_replace('/\s+.*$/', '', $ventaStr);
            
            fwrite($debug_log, "  Compra (limpio): '$compraStr'\n");
            fwrite($debug_log, "  Venta (limpio): '$ventaStr'\n");
            
            // Limpiar y convertir valores
            $compra = $this->limpiarValor($compraStr);
            $venta = $this->limpiarValor($ventaStr);
            
            fwrite($debug_log, "  Compra (final): $compra\n");
            fwrite($debug_log, "  Venta (final): $venta\n");
            
            if ($compra > 0 && $venta > 0) {
                $resultado[$codigoBanco] = [
                    'compra' => $compra,
                    'venta' => $venta,
                    'source' => 'infodolar',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'nombre_original' => $nombreBanco
                ];
                
                fwrite($debug_log, "  Éxito: Banco añadido a resultados\n");
            } else {
                fwrite($debug_log, "  Error: Valores inválidos, saltando\n");
            }
        }
        
        if (empty($resultado)) {
            fwrite($debug_log, "No se extrajeron cotizaciones\n");
            fclose($debug_log);
            return false;
        }
        
        fwrite($debug_log, "Total cotizaciones extraídas: " . count($resultado) . "\n");
        fwrite($debug_log, "--- FIN DEBUG ---\n");
        fclose($debug_log);
        
        return $resultado;
    }
    
    /**
     * Obtiene el nombre de la fuente
     * 
     * @return string Nombre identificador de la fuente
     */
    public function getNombre() {
        return 'infodolar';
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
     * Determina el código de banco a partir del nombre mostrado en la tabla
     * 
     * @param string $nombreBanco Nombre del banco como aparece en InfoDolar
     * @return string|null Código del banco o NULL si no se reconoce
     */
    private function obtenerCodigoBanco($nombreBanco) {
        // Mapeo de nombres específicos a códigos
        $mapaNombres = [
            'BANCO NACIÓN' => 'nacion',
            'BANCO DE LA NACIÓN ARGENTINA' => 'nacion',
            'NACIÓN' => 'nacion',
            'BANCO GALICIA' => 'galicia',
            'GALICIA' => 'galicia',
            'BANCO FRANCÉS' => 'frances',
            'BBVA' => 'frances',
            'FRANCÉS' => 'frances',
            'BANCO SANTANDER' => 'santander',
            'SANTANDER' => 'santander',
            'BANCO MACRO' => 'macro',
            'MACRO' => 'macro',
            'BANCO SUPERVIELLE' => 'supervielle',
            'SUPERVIELLE' => 'supervielle',
            'BANCO HIPOTECARIO' => 'hipotecario',
            'HIPOTECARIO' => 'hipotecario',
            'BANCO CIUDAD' => 'ciudad',
            'CIUDAD' => 'ciudad',
            'BANCO HSBC' => 'hsbc',
            'HSBC' => 'hsbc',
            'BANCO ICBC' => 'icbc',
            'ICBC' => 'icbc',
            'BANCO PROVINCIA' => 'bapro',
            'BANCO DE LA PROVINCIA DE BUENOS AIRES' => 'bapro',
            'PROVINCIA' => 'bapro',
            'BAPRO' => 'bapro',
            'BANCO PATAGONIA' => 'patagonia',
            'PATAGONIA' => 'patagonia',
            'BANCO ITAÚ' => 'itau',
            'ITAÚ' => 'itau',
            'ITAU' => 'itau',
            'BANCO CREDICOOP' => 'credicoop',
            'CREDICOOP' => 'credicoop',
            'BRUBANK' => 'brubank',
            'BANCO PIANO' => null,
            'PIANO' => null,
            'CAMBIO POSADAS' => null,
            'BANCO COMAFI' => 'comafi',
            'COMAFI' => 'comafi',
            'BANCO PROVINCIA DEL NEUQUÉN' => null,
            'PROVINCIA DEL NEUQUÉN' => null,
            'BPN' => null,
            'PLAZA CAMBIO' => null,
        ];
        
        // Normalizar nombre (todo mayúsculas y sin acentos)
        $nombreNormalizado = mb_strtoupper(
            iconv('UTF-8', 'ASCII//TRANSLIT', $nombreBanco),
            'UTF-8'
        );
        $nombreNormalizado = trim($nombreNormalizado);
        
        // Eliminar texto no deseado (texto entre paréntesis, añadidos, etc.)
        $nombreNormalizado = preg_replace('/\([^)]+\)/', '', $nombreNormalizado);
        $nombreNormalizado = preg_replace('/\bCAMBIO ONLINE\b/', '', $nombreNormalizado);
        $nombreNormalizado = trim($nombreNormalizado);
        
        // Buscar coincidencia exacta
        if (isset($mapaNombres[$nombreNormalizado])) {
            return $mapaNombres[$nombreNormalizado];
        }
        
        // Quitar "BANCO" del principio para normalizar
        $nombreSinBanco = preg_replace('/^BANCO\s+/', '', $nombreNormalizado);
        
        if (isset($mapaNombres[$nombreSinBanco])) {
            return $mapaNombres[$nombreSinBanco];
        }
        
        // Buscar coincidencia parcial
        foreach ($mapaNombres as $nombre => $codigo) {
            if ($codigo === null) continue;
            
            $nombreMapaNormalizado = mb_strtoupper(
                iconv('UTF-8', 'ASCII//TRANSLIT', $nombre),
                'UTF-8'
            );
            
            if (
                ($nombreNormalizado && strpos($nombreNormalizado, $nombreMapaNormalizado) !== false) ||
                ($nombreMapaNormalizado && strpos($nombreMapaNormalizado, $nombreNormalizado) !== false) ||
                ($nombreSinBanco && strpos($nombreSinBanco, $nombreMapaNormalizado) !== false) ||
                ($nombreMapaNormalizado && strpos($nombreMapaNormalizado, $nombreSinBanco) !== false)
            ) {
                return $codigo;
            }
        }
        
        // Detección basada en palabras clave
        if (strpos($nombreNormalizado, 'NACIO') !== false) return 'nacion';
        if (strpos($nombreNormalizado, 'GALI') !== false) return 'galicia';
        if (strpos($nombreNormalizado, 'FRANC') !== false || strpos($nombreNormalizado, 'BBVA') !== false) return 'frances';
        if (strpos($nombreNormalizado, 'SANTAN') !== false) return 'santander';
        if (strpos($nombreNormalizado, 'MACRO') !== false) return 'macro';
        if (strpos($nombreNormalizado, 'SUPER') !== false) return 'supervielle';
        if (strpos($nombreNormalizado, 'HIPO') !== false) return 'hipotecario';
        if (strpos($nombreNormalizado, 'CIUDAD') !== false) return 'ciudad';
        if (strpos($nombreNormalizado, 'HSBC') !== false) return 'hsbc';
        if (strpos($nombreNormalizado, 'ICBC') !== false) return 'icbc';
        if (strpos($nombreNormalizado, 'PROVIN') !== false && strpos($nombreNormalizado, 'NEUQ') === false) return 'bapro';
        if (strpos($nombreNormalizado, 'PATAG') !== false) return 'patagonia';
        if (strpos($nombreNormalizado, 'ITAU') !== false || strpos($nombreNormalizado, 'ITAÚ') !== false) return 'itau';
        if (strpos($nombreNormalizado, 'CREDI') !== false) return 'credicoop';
        if (strpos($nombreNormalizado, 'BRUBANK') !== false) return 'brubank';
        if (strpos($nombreNormalizado, 'COMAFI') !== false) return 'comafi';
        if (strpos($nombreNormalizado, 'NEUQ') !== false) return null; // Banco Provincia del Neuquén
        
        error_log("Banco no reconocido en InfoDolar: '$nombreNormalizado' (original: '$nombreBanco')");
        return null;
    }
    
    /**
     * Limpia y convierte un valor de cotización de texto a número
     * 
     * @param string $valor Valor en texto (ej: "$150,25")
     * @return float Valor numérico
     */
    private function limpiarValor($valor) {
        // Eliminar el símbolo de peso y espacios
        $valor = str_replace(['$', ' '], '', $valor);
        
        // Reemplazar punto por nada (separador de miles)
        $valor = str_replace('.', '', $valor);
        
        // Reemplazar coma por punto para decimales
        $valor = str_replace(',', '.', $valor);
        
        // Eliminar cualquier caracter no numérico, excepto punto decimal
        $valor = preg_replace('/[^0-9.]/', '', $valor);
        
        return (float) $valor;
    }
    
    /**
     * Actualiza las estadísticas de fiabilidad de la fuente
     * 
     * @param bool $exitoso TRUE si la consulta fue exitosa, FALSE en caso contrario
     */
    private function actualizarEstadisticas($exitoso) {
        foreach ($this->bancosDisponibles as $codigo) {
            $this->CI->SourceReliability_model->registrarConsulta(
                'infodolar',
                $codigo,
                $exitoso,
                $this->tiempoConsulta
            );
        }
    }
}