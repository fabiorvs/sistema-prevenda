<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index', ['filter' => ['session', 'tenant']]);
$routes->group('admin', ['filter' => ['session', 'superadmin']], static function ($routes): void {
    $routes->get('/', '\\App\\Modules\\Administration\\Controllers\\AdministrationController::index');
    $routes->post('tenants/(:num)/status', '\\App\\Modules\\Administration\\Controllers\\AdministrationController::setTenantStatus/$1');
    $routes->post('users/(:num)/status', '\\App\\Modules\\Administration\\Controllers\\AdministrationController::setUserStatus/$1');
    $routes->post('users/(:num)/global-role', '\\App\\Modules\\Administration\\Controllers\\AdministrationController::setGlobalRole/$1');
    $routes->post('tenants/(:num)/users/(:num)/role', '\\App\\Modules\\Administration\\Controllers\\AdministrationController::setTenantRole/$1/$2');
    $routes->post('tenants/(:num)/plan', '\\App\\Modules\\Administration\\Controllers\\AdministrationController::assignPlan/$1');
});
$routes->group('', ['filter' => ['session', 'tenant']], static function ($routes): void {
    $routes->get('dashboard', '\\App\\Modules\\Dashboard\\Controllers\\DashboardController::index');
    $routes->get('perfil', '\\App\\Modules\\Profile\\Controllers\\ProfileController::index');
    $routes->post('perfil', '\\App\\Modules\\Profile\\Controllers\\ProfileController::update');
    $routes->post('perfil/senha', '\\App\\Modules\\Profile\\Controllers\\ProfileController::password');
    $routes->get('dashboard/exportar', '\\App\\Modules\\Dashboard\\Controllers\\DashboardController::export');
    $routes->get('colecao', '\\App\\Modules\\Collection\\Controllers\\CollectionController::index');
    $routes->get('colecao/(:num)', '\\App\\Modules\\Collection\\Controllers\\CollectionController::show/$1');
    $routes->post('colecao/(:num)/localizacao', '\\App\\Modules\\Collection\\Controllers\\CollectionController::updateLocation/$1');
    $routes->post('colecao/(:num)/fotos', '\\App\\Modules\\Collection\\Controllers\\CollectionController::addPhoto/$1');
    $routes->get('anexos/(:num)', '\\App\\Modules\\Attachments\\Controllers\\AttachmentsController::download/$1');
    $routes->get('historico', '\\App\\Modules\\History\\Controllers\\HistoryController::index');
    $routes->get('compras', '\\App\\Modules\\Purchases\\Controllers\\PurchasesController::index');
    $routes->get('compras/nova', '\\App\\Modules\\Purchases\\Controllers\\PurchasesController::create');
    $routes->post('compras', '\\App\\Modules\\Purchases\\Controllers\\PurchasesController::store');
    $routes->get('entregas', '\\App\\Modules\\Deliveries\\Controllers\\DeliveriesController::index');
    $routes->get('entregas/envios/novo', '\\App\\Modules\\Deliveries\\Controllers\\ShipmentsController::create');
    $routes->post('entregas/envios', '\\App\\Modules\\Deliveries\\Controllers\\ShipmentsController::store');
    $routes->get('entregas/envios/(:num)', '\\App\\Modules\\Deliveries\\Controllers\\ShipmentsController::show/$1');
    $routes->post('entregas/envios/(:num)/status', '\\App\\Modules\\Deliveries\\Controllers\\ShipmentsController::updateStatus/$1');
    $routes->get('entregas/(:num)', '\\App\\Modules\\Deliveries\\Controllers\\DeliveriesController::show/$1');
    $routes->post('entregas/(:num)/recebimento', '\\App\\Modules\\Deliveries\\Controllers\\DeliveriesController::receive/$1');
    $routes->get('financeiro', '\\App\\Modules\\Finance\\Controllers\\FinanceController::index');
    $routes->get('financeiro/pagamentos', '\\App\\Modules\\Finance\\Controllers\\FinanceController::payments');
    $routes->get('financeiro/contas/(:num)/pagamento', '\\App\\Modules\\Finance\\Controllers\\PaymentsController::create/$1');
    $routes->post('financeiro/contas/(:num)/pagamento', '\\App\\Modules\\Finance\\Controllers\\PaymentsController::store/$1');
    $routes->post('financeiro/pagamentos/(:num)/estorno', '\\App\\Modules\\Finance\\Controllers\\PaymentsController::refund/$1');
    $routes->get('garagem', '\\App\\Modules\\Garage\\Controllers\\GarageController::index');
    $routes->get('garagem/nova', '\\App\\Modules\\Garage\\Controllers\\GarageController::create');
    $routes->get('garagem/(:num)/editar', '\\App\\Modules\\Garage\\Controllers\\GarageController::edit/$1');
    $routes->post('garagem', '\\App\\Modules\\Garage\\Controllers\\GarageController::store');
    $routes->get('garagem/(:num)', '\\App\\Modules\\Garage\\Controllers\\GarageController::show/$1');
    $routes->post('garagem/(:num)/pagamentos', '\\App\\Modules\\Garage\\Controllers\\GarageController::addPayment/$1');
    $routes->post('garagem/(:num)/anexos', '\\App\\Modules\\Attachments\\Controllers\\AttachmentsController::storeGarage/$1');
    $routes->post('garagem/(:num)/status', '\\App\\Modules\\Garage\\Controllers\\GarageController::updateStatus/$1');
    $routes->post('garagem/(:num)/editar', '\\App\\Modules\\Garage\\Controllers\\GarageController::update/$1');
    $routes->get('pre-vendas', '\\App\\Modules\\Preorders\\Controllers\\PreordersController::index');
    $routes->get('pre-vendas/nova', '\\App\\Modules\\Preorders\\Controllers\\PreordersController::create');
    $routes->get('pre-vendas/(:num)/editar', '\\App\\Modules\\Preorders\\Controllers\\PreordersController::edit/$1');
    $routes->get('pre-vendas/(:num)', '\\App\\Modules\\Preorders\\Controllers\\PreordersController::show/$1');
    $routes->post('pre-vendas', '\\App\\Modules\\Preorders\\Controllers\\PreordersController::store');
    $routes->post('pre-vendas/(:num)/chegada-completa', '\\App\\Modules\\Preorders\\Controllers\\PreordersController::markCompleteArrival/$1');
    $routes->post('pre-vendas/(:num)/cancelar', '\\App\\Modules\\Preorders\\Controllers\\PreordersController::cancel/$1');
    $routes->post('pre-vendas/(:num)/editar', '\\App\\Modules\\Preorders\\Controllers\\PreordersController::update/$1');
    $routes->post('pre-vendas/(:num)/anexos', '\\App\\Modules\\Attachments\\Controllers\\AttachmentsController::storePreorder/$1');
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
