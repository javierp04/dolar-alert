<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| AUTO-LOADER
| -------------------------------------------------------------------
| This file specifies which systems should be loaded by default.
|
| In order to keep the framework as light-weight as possible only the
| absolute minimal resources are loaded by default. For example,
| the database is not connected to automatically since no assumption
| is made regarding whether you intend to use it.
|
*/

/*
| -------------------------------------------------------------------
| Libraries
| -------------------------------------------------------------------
*/
$autoload['libraries'] = array('database', 'session');

/*
| -------------------------------------------------------------------
| Drivers
| -------------------------------------------------------------------
*/
$autoload['drivers'] = array();

/*
| -------------------------------------------------------------------
| Helper Files
| -------------------------------------------------------------------
*/
$autoload['helper'] = array('url', 'form', 'date', 'telegram');

/*
| -------------------------------------------------------------------
| Config Files
| -------------------------------------------------------------------
*/
$autoload['config'] = array();

/*
| -------------------------------------------------------------------
| Language Files
| -------------------------------------------------------------------
*/
$autoload['language'] = array();

/*
| -------------------------------------------------------------------
| Models
| -------------------------------------------------------------------
*/
$autoload['model'] = array('Configuracion_model', 'Cotizacion_model', 'Dolar_model');