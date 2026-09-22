<?php

declare(strict_types=1);

namespace App;

$router = new Router();

$router->get('/login', [AuthController::class, 'showLogin'], 'guest');
$router->post('/login', [AuthController::class, 'login'], 'guest');
$router->post('/logout', [AuthController::class, 'logout']);

/* Staff are sent on to /sales by the controller; the dashboard is a manager view. */
$router->get('/', [DashboardController::class, 'index']);

$router->get('/merchants', [MerchantController::class, 'index'], 'admin');
$router->get('/merchants/show', [MerchantController::class, 'show'], 'admin');
$router->post('/merchants/store', [MerchantController::class, 'store'], 'admin');
$router->post('/merchants/update', [MerchantController::class, 'update'], 'admin');
$router->post('/merchants/delete', [MerchantController::class, 'destroy'], 'admin');

$router->get('/sales', [SaleController::class, 'index']);
$router->get('/sales/new', [SaleController::class, 'create']);
$router->get('/sales/show', [SaleController::class, 'show']);
$router->get('/sales/edit', [SaleController::class, 'edit']);
$router->post('/sales/store', [SaleController::class, 'store']);
$router->post('/sales/update', [SaleController::class, 'update']);
$router->post('/sales/delete', [SaleController::class, 'destroy'], 'admin');

$router->get('/payments', [PaymentController::class, 'index'], 'admin');
$router->post('/payments/store', [PaymentController::class, 'store'], 'admin');
$router->post('/payments/delete', [PaymentController::class, 'destroy'], 'admin');

$router->get('/discounts', [DiscountController::class, 'index'], 'admin');
$router->post('/discounts/store', [DiscountController::class, 'store'], 'admin');
$router->post('/discounts/delete', [DiscountController::class, 'destroy'], 'admin');

$router->get('/material-expenses', [MaterialExpenseController::class, 'index'], 'admin');
$router->post('/material-expenses/store', [MaterialExpenseController::class, 'store'], 'admin');
$router->post('/material-expenses/delete', [MaterialExpenseController::class, 'destroy'], 'admin');

$router->get('/worker-payments', [WorkerPaymentController::class, 'index'], 'admin');
$router->post('/worker-payments/store', [WorkerPaymentController::class, 'store'], 'admin');
$router->post('/worker-payments/delete', [WorkerPaymentController::class, 'destroy'], 'admin');

$router->get('/pricing', [PricingController::class, 'index']);
$router->post('/pricing/update', [PricingController::class, 'update']);

$router->get('/reports', [ReportController::class, 'index'], 'admin');

$router->get('/settings', [SettingsController::class, 'index'], 'admin');
$router->post('/settings/job-types/store', [SettingsController::class, 'storeJobType'], 'admin');
$router->post('/settings/job-types/update', [SettingsController::class, 'updateJobType'], 'admin');
$router->post('/settings/job-types/delete', [SettingsController::class, 'destroyJobType'], 'admin');
$router->post('/settings/materials/store', [SettingsController::class, 'storeMaterial'], 'admin');
$router->post('/settings/materials/update', [SettingsController::class, 'updateMaterial'], 'admin');
$router->post('/settings/materials/delete', [SettingsController::class, 'destroyMaterial'], 'admin');
$router->post('/settings/workers/store', [SettingsController::class, 'storeWorker'], 'admin');
$router->post('/settings/workers/update', [SettingsController::class, 'updateWorker'], 'admin');
$router->post('/settings/workers/delete', [SettingsController::class, 'destroyWorker'], 'admin');

$router->get('/users', [UserController::class, 'index'], 'admin');
$router->post('/users/store', [UserController::class, 'store'], 'admin');
$router->post('/users/update', [UserController::class, 'update'], 'admin');
$router->post('/users/delete', [UserController::class, 'destroy'], 'admin');

$router->get('/audit', [AuditController::class, 'index'], 'admin');
$router->get('/backup', [BackupController::class, 'download'], 'admin');

return $router;
