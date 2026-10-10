<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index', ['filter' => ['session', 'tenant']]);
$routes->group('', ['filter' => ['session', 'tenant']], static function ($routes): void {
    $routes->get('dashboard', '\\App\\Modules\\Dashboard\\Controllers\\DashboardController::index');
    $routes->get('dashboard/exportar', '\\App\\Modules\\Dashboard\\Controllers\\DashboardController::export');
    $routes->get('entregas', '\\App\\Modules\\Deliveries\\Controllers\\DeliveriesController::index');
    $routes->get('entregas/(:num)', '\\App\\Modules\\Deliveries\\Controllers\\DeliveriesController::show/$1');
    $routes->post('entregas/(:num)/status', '\\App\\Modules\\Deliveries\\Controllers\\DeliveriesController::updateStatus/$1');
    $routes->post('entregas/(:num)/recebimento', '\\App\\Modules\\Deliveries\\Controllers\\DeliveriesController::receive/$1');
    $routes->get('financeiro', '\\App\\Modules\\Finance\\Controllers\\FinanceController::index');
    $routes->get('financeiro/pagamentos', '\\App\\Modules\\Finance\\Controllers\\FinanceController::payments');
    $routes->get('financeiro/contas/(:num)/pagamento', '\\App\\Modules\\Finance\\Controllers\\PaymentsController::create/$1');
    $routes->post('financeiro/contas/(:num)/pagamento', '\\App\\Modules\\Finance\\Controllers\\PaymentsController::store/$1');
    $routes->post('financeiro/pagamentos/(:num)/estorno', '\\App\\Modules\\Finance\\Controllers\\PaymentsController::refund/$1');
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
