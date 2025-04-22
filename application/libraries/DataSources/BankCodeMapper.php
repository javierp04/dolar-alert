<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mapeador de códigos de bancos entre diferentes fuentes
 * 
 * Esta clase es responsable de traducir los códigos de bancos entre
 * las diferentes fuentes de datos, permitiendo la unificación de la información.
 */
class BankCodeMapper {
    
    // Mapa de equivalencias entre las fuentes
    private static $bankMapping = [
        'criptoya' => [
            'bbva' => ['infodolar' => 'frances'],
            'bna' => ['infodolar' => 'nacion'],
            'galicia' => ['infodolar' => 'galicia'],
            'santander' => ['infodolar' => 'santander'],
            'brubank' => ['infodolar' => 'brubank'],
            'hsbc' => ['infodolar' => 'hsbc'],
            'icbc' => ['infodolar' => 'icbc'],
            'macro' => ['infodolar' => 'macro'],
            'ciudad' => ['infodolar' => 'ciudad'],
            'supervielle' => ['infodolar' => 'supervielle'],
            'hipotecario' => ['infodolar' => 'hipotecario'],
            'patagonia' => ['infodolar' => 'patagonia'],
            'reba' => ['infodolar' => null], // No disponible en InfoDolar
            'provincia' => ['infodolar' => 'bapro'],
            'comafi' => ['infodolar' => null], // No disponible en InfoDolar
            'bind' => ['infodolar' => null] // No disponible en InfoDolar
        ],
        'infodolar' => [
            'frances' => ['criptoya' => 'bbva'],
            'nacion' => ['criptoya' => 'bna'],
            'galicia' => ['criptoya' => 'galicia'],
            'santander' => ['criptoya' => 'santander'],
            'brubank' => ['criptoya' => 'brubank'],
            'hsbc' => ['criptoya' => 'hsbc'],
            'icbc' => ['criptoya' => 'icbc'],
            'macro' => ['criptoya' => 'macro'],
            'ciudad' => ['criptoya' => 'ciudad'],
            'supervielle' => ['criptoya' => 'supervielle'],
            'hipotecario' => ['criptoya' => 'hipotecario'],
            'patagonia' => ['criptoya' => 'patagonia'],
            'bapro' => ['criptoya' => 'provincia'],
            'itau' => ['criptoya' => null], // No disponible en CriptoYa
            'credicoop' => ['criptoya' => null] // No disponible en CriptoYa
        ]
    ];
    
    /**
     * Convierte un código de banco de una fuente a otra
     * 
     * @param string $codigo Código original del banco
     * @param string $fuenteOrigen Fuente de origen
     * @param string $fuenteDestino Fuente de destino
     * @return string|null Código equivalente en la fuente destino o NULL si no existe
     */
    public static function convertirCodigo($codigo, $fuenteOrigen, $fuenteDestino) {
        // Si las fuentes son iguales, devolver el mismo código
        if ($fuenteOrigen === $fuenteDestino) {
            return $codigo;
        }
        
        // Verificar si existe el mapeo
        if (isset(self::$bankMapping[$fuenteOrigen][$codigo][$fuenteDestino])) {
            return self::$bankMapping[$fuenteOrigen][$codigo][$fuenteDestino];
        }
        
        // No existe mapeo
        return null;
    }
    
    /**
     * Obtiene todos los códigos disponibles en una fuente específica
     * 
     * @param string $fuente Nombre de la fuente
     * @return array Lista de códigos de banco disponibles
     */
    public static function getCodigosDisponibles($fuente) {
        if (isset(self::$bankMapping[$fuente])) {
            return array_keys(self::$bankMapping[$fuente]);
        }
        return [];
    }
    
    /**
     * Unifica cotizaciones de diferentes fuentes con un formato estándar
     * 
     * @param array $cotizacionesCriptoYa Cotizaciones de CriptoYa
     * @param array $cotizacionesInfoDolar Cotizaciones de InfoDolar
     * @return array Cotizaciones unificadas con formato estándar
     */
    public static function unificarCotizaciones($cotizacionesCriptoYa, $cotizacionesInfoDolar) {
        $resultado = [];
        
        // Procesar cotizaciones de CriptoYa
        if ($cotizacionesCriptoYa && is_array($cotizacionesCriptoYa)) {
            foreach ($cotizacionesCriptoYa as $codigo => $cotizacion) {
                if (isset($cotizacion['totalBid']) && isset($cotizacion['totalAsk'])) {
                    $resultado[$codigo] = [
                        'totalBid' => $cotizacion['totalBid'],
                        'totalAsk' => $cotizacion['totalAsk'],
                        'source' => 'criptoya',
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                }
            }
        }
        
        // Procesar cotizaciones de InfoDolar
        if ($cotizacionesInfoDolar && is_array($cotizacionesInfoDolar)) {
            foreach ($cotizacionesInfoDolar as $codigo => $cotizacion) {
                // Convertir código de InfoDolar a formato estándar (CriptoYa)
                $codigoEstandar = self::convertirCodigo($codigo, 'infodolar', 'criptoya');
                
                if ($codigoEstandar && isset($cotizacion['compra']) && isset($cotizacion['venta'])) {
                    $resultado[$codigoEstandar . '_infodolar'] = [
                        'totalBid' => $cotizacion['compra'],
                        'totalAsk' => $cotizacion['venta'],
                        'source' => 'infodolar',
                        'timestamp' => date('Y-m-d H:i:s'),
                        'codigo_original' => $codigo
                    ];
                }
            }
        }
        
        return $resultado;
    }
}