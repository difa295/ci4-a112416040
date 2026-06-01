<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index', ['filter' => 'auth']);

$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::login');
$routes->get('logout', 'AuthController::logout');

// Bagian grup produk di bawah ini sudah diperbaiki agar form_open_multipart('produk') dosen berjalan lancar
$routes->group('produk', ['filter' => 'auth'], function ($routes) { 
    $routes->get('/', 'ProdukController::index');
    $routes->post('/', 'ProdukController::create');
    $routes->post('edit/(:any)', 'ProdukController::edit/$1');
    $routes->get('delete/(:any)', 'ProdukController::delete/$1');
});

$routes->get('keranjang', 'TransaksiController::index', ['filter' => 'auth']);
$routes->get('profile', 'ProfileController::index', ['filter' => 'auth']);