<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|    example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
*/

// Rutas por defecto de CodeIgniter
$route['default_controller'] = 'dashboard';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Rutas del Dashboard
$route['consultar-ahora'] = 'dashboard/consultar_ahora';
$route['actualizar-todas'] = 'dashboard/actualizar_todas';
$route['api/grafico/(:any)/(:num)'] = 'dashboard/api_datos_grafico/$1/$2';
$route['api/diferencias/(:num)'] = 'dashboard/api_datos_diferencias/$1';

// Rutas de Configuración
$route['configuracion'] = 'configuracion/index';
$route['configuracion/dolares'] = 'configuracion/dolares';
$route['configuracion/guardar'] = 'configuracion/guardar';
$route['configuracion/dolar/guardar'] = 'configuracion/guardar_dolar';
$route['configuracion/dolar/estado/(:num)'] = 'configuracion/cambiar_estado_dolar/$1';
$route['configuracion/eliminar_dolar/(:num)'] = 'configuracion/eliminar_dolar/$1';
$route['configuracion/actualizar_fuente_dolar'] = 'configuracion/actualizar_fuente_dolar';
$route['configuracion/fiabilidad_fuentes'] = 'configuracion/fiabilidad_fuentes';
$route['configuracion/optimizar_fuentes'] = 'configuracion/optimizar_fuentes';

// Rutas para el controlador Cron
$route['cron/consultar'] = 'CronController/consultar_cotizaciones';

// Rutas para el controlador de pruebas
$route['test'] = 'test/index';
$route['test/consultar'] = 'test/consultar';
$route['test/consultar_criptoya'] = 'test/consultar_criptoya';
$route['test/consultar_infodolar'] = 'test/consultar_infodolar';
$route['test/consultar_multifuente'] = 'test/consultar_multifuente';
$route['test/ejecutar'] = 'test/ejecutar';
$route['test/telegram'] = 'test/telegram';