<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Interface para las fuentes de datos de cotizaciones
 * 
 * Define el contrato que deben cumplir todas las implementaciones de fuentes
 * de cotizaciones de dólares.
 */
interface DataSourceInterface {
    
    /**
     * Obtiene las cotizaciones de dólares
     * 
     * @return array|bool Datos de cotizaciones en formato unificado o FALSE en caso de error
     */
    public function obtenerCotizaciones();
    
    /**
     * Obtiene el nombre de la fuente
     * 
     * @return string Nombre identificador de la fuente
     */
    public function getNombre();
    
    /**
     * Obtiene los bancos disponibles en esta fuente
     * 
     * @return array Lista de códigos de bancos disponibles
     */
    public function getBancosDisponibles();
    
    /**
     * Verifica si un banco específico está disponible en esta fuente
     * 
     * @param string $codigo Código del banco a verificar
     * @return bool TRUE si está disponible, FALSE en caso contrario
     */
    public function isBancoDisponible($codigo);
}