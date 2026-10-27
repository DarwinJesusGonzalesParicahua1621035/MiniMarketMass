<?php
// public/index.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Auth.php';

// Autenticación básica (Darwin)
if (!Auth::check() && $_SERVER['REQUEST_URI'] !== '/login') {
    header('Location: /login');
    exit;
}

$router = new Router();
// Rutas base para que el equipo trabaje
$router->get('/', 'DashboardController@index');
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

// Rutas de los módulos del equipo (ellos las expandirán)
$router->get('/productos', 'ProductoController@index'); // Jhon
$router->get('/stock', 'StockController@index');       // Bryan
$router->get('/ventas', 'VentaController@index');      // Anthony
$router->get('/caja', 'CajaController@index');         // Jean
$router->post('/caja/guardar', 'CajaController@save');
$router->post('/caja/eliminar', 'CajaController@delete');
$router->get('/historia-caja', 'HistoriaCajaController@index');
$router->post('/historia-caja/abrir', 'HistoriaCajaController@open');
$router->post('/historia-caja/cerrar', 'HistoriaCajaController@close');
$router->get('/incidentes', 'IncidenteController@index');
$router->post('/incidentes/crear', 'IncidenteController@create');
$router->post('/incidentes/estado', 'IncidenteController@updateState');
$router->get('/clientes', 'ClienteController@index');  // Franzua

$router->run($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);