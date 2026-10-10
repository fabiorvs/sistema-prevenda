<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index', ['filter' => ['session', 'tenant']]);
$routes->group('', ['filter' => ['session', 'tenant']], static function ($routes): void {
    $routes->get('pre-vendas', '\\App\\Modules\\Preorders\\Controllers\\PreordersController::index');
    $routes->get('pre-vendas/nova', '\\App\\Modules\\Preorders\\Controllers\\PreordersController::create');
    $routes->post('pre-vendas', '\\App\\Modules\\Preorders\\Controllers\\PreordersController::store');
    $routes->get('vendedores', '\\App\\Modules\\Sellers\\Controllers\\SellersController::index');
    $routes->get('vendedores/novo', '\\App\\Modules\\Sellers\\Controllers\\SellersController::create');
    $routes->post('vendedores', '\\App\\Modules\\Sellers\\Controllers\\SellersController::store');
    $routes->get('vendedores/(:num)/editar', '\\App\\Modules\\Sellers\\Controllers\\SellersController::edit/$1');
    $routes->post('vendedores/(:num)', '\\App\\Modules\\Sellers\\Controllers\\SellersController::update/$1');
    $routes->post('vendedores/(:num)/excluir', '\\App\\Modules\\Sellers\\Controllers\\SellersController::delete/$1');
    $routes->get('imagens/vendedores/(:num)', '\\App\\Modules\\Sellers\\Controllers\\SellersController::image/$1');
    $routes->get('carrinhos', '\\App\\Modules\\Catalog\\Controllers\\ProductsController::index');
    $routes->get('carrinhos/novo', '\\App\\Modules\\Catalog\\Controllers\\ProductsController::create');
    $routes->post('carrinhos', '\\App\\Modules\\Catalog\\Controllers\\ProductsController::store');
    $routes->get('carrinhos/(:num)/editar', '\\App\\Modules\\Catalog\\Controllers\\ProductsController::edit/$1');
    $routes->post('carrinhos/(:num)', '\\App\\Modules\\Catalog\\Controllers\\ProductsController::update/$1');
    $routes->post('carrinhos/(:num)/excluir', '\\App\\Modules\\Catalog\\Controllers\\ProductsController::delete/$1');
    $routes->get('imagens/carrinhos/(:num)', '\\App\\Modules\\Catalog\\Controllers\\ProductsController::image/$1');
});

service('auth')->routes($routes);
