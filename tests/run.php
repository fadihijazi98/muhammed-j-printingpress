<?php

declare(strict_types=1);

namespace App;

/**
 * Plain-PHP checks for the parts where a mistake costs money: the arithmetic,
 * the account balance, and who is allowed to do what.
 *
 * Run with:  php tests/run.php
 */

$databaseFile = sys_get_temp_dir() . '/mjpress-test-' . getmypid() . '.sqlite';

foreach ([$databaseFile, $databaseFile . '-wal', $databaseFile . '-shm'] as $stale) {
    if (is_file($stale)) {
        unlink($stale);
    }
}

putenv('APP_DATABASE=' . $databaseFile);

require_once dirname(__DIR__) . '/src/bootstrap.php';

$passed = 0;
$failed = 0;

function check(string $description, mixed $actual, mixed $expected): void
{
    global $passed, $failed;

    if ($actual === $expected) {
        $passed++;

        echo "  ✓ " . $description . "\n";

        return;
    }

    $failed++;

    echo "  ✗ " . $description . "\n";
    echo "      expected: " . var_export($expected, true) . "\n";
    echo "      actual:   " . var_export($actual, true) . "\n";
}

function group(string $title): void
{
    echo "\n" . $title . "\n";
}

/* ---------------------------------------------------------------- money --- */

group('Money');

check('parses a plain amount', Money::parse('300'), 30000);
check('parses decimals', Money::parse('12.34'), 1234);
check('rounds to the nearest agora', Money::parse('0.005'), 1);
check('strips thousands separators', Money::parse('1,250.50'), 125050);
check('reads Arabic-Indic digits', Money::parse('٢٥٫٥٠'), 2550);
check('rejects letters', Money::parse('abc'), null);
check('rejects an empty value', Money::parse(''), null);

check('formats with two decimals', Money::format(30000), '300.00');
check('formats an odd amount', Money::format(1234), '12.34');
check('formats zero', Money::format(0), '0.00');
check('formats a negative amount', Money::format(-1250), '-12.50');
check('groups thousands', Money::format(125050), '1,250.50');
check('shows the shekel symbol', Money::display(30000), '₪ 300.00');

check('multiplies whole units', Money::multiply(12.0, 2500), 30000);
check('multiplies fractional units', Money::multiply(7.5, 2000), 15000);
check('rounds the product', Money::multiply(3.333, 1000), 3333);

/* ------------------------------------------------------------- quantity --- */

group('Quantity');

check('parses a whole quantity', Quantity::parse('12'), 12.0);
check('parses a fractional quantity', Quantity::parse('7.5'), 7.5);
check('reads Arabic-Indic digits', Quantity::parse('٨٫٥'), 8.5);
check('rejects a negative quantity', Quantity::parse('-3'), null);
check('drops trailing zeros when showing', Quantity::format(12.0), '12');
check('keeps a meaningful decimal', Quantity::format(7.5), '7.5');

/* ------------------------------------------------------------- database --- */

group('Database and first-run data');

check('created every table', count(Database::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'")), 12);
check('material purchases can belong to a sale', count(array_filter(Database::select('PRAGMA table_info(material_purchases)'), static fn (array $c): bool => $c['name'] === 'sale_id')), 1);
check('worker payments can belong to a sale', count(array_filter(Database::select('PRAGMA table_info(worker_payments)'), static fn (array $c): bool => $c['name'] === 'sale_id')), 1);
check('seeded two accounts', (int) Database::scalar('SELECT COUNT(*) FROM users'), 2);
check('seeded the admin account', Database::selectOne('SELECT role FROM users WHERE email = ?', ['muhammed@admin.com'])['role'], 'admin');
check('seeded the staff account', Database::selectOne('SELECT role FROM users WHERE email = ?', ['any@staff.com'])['role'], 'staff');
check('seeded دهان in litres', Database::selectOne('SELECT unit FROM materials WHERE name = ?', ['دهان'])['unit'], 'لتر');
check('sales are counted in items', Database::selectOne('SELECT unit FROM job_types LIMIT 1')['unit'], 'قطعة');
check('seeded a worker with an hourly rate', (int) Database::scalar('SELECT COUNT(*) FROM workers'), 1);

Database::migrate();
check('re-running migrations changes nothing', (int) Database::scalar('SELECT COUNT(*) FROM users'), 2);

/* ----------------------------------------------------------------- auth --- */

group('Login');

check('admin signs in', Auth::attempt('muhammed@admin.com', 'admin123'), true);
check('admin is recognised as admin', Auth::isAdmin(), true);

Auth::logout();

check('staff signs in', Auth::attempt('any@staff.com', 'staff_password'), true);
check('staff is not an admin', Auth::isAdmin(), false);
check('wrong password is refused', Auth::attempt('muhammed@admin.com', 'wrong'), false);
check('unknown email is refused', Auth::attempt('nobody@example.com', 'admin123'), false);

Auth::logout();

Auth::attempt('muhammed@admin.com', 'admin123');
$adminId = Auth::id();

/* -------------------------------------------------------------- balance --- */

group('Merchant balance');

$merchantId = Database::insert('merchants', ['name' => 'أبو أحمد', 'created_by' => $adminId]);
$jobTypeId  = (int) Database::scalar('SELECT id FROM job_types LIMIT 1');

$total = Money::multiply(12.0, 2500);

Database::insert(
    'sales',
    [
        'merchant_id' => $merchantId,
        'job_type_id' => $jobTypeId,
        'quantity'    => 12.0,
        'unit_price'  => 2500,
        'total'       => $total,
        'sale_date'   => '2026-09-01',
        'created_by'  => $adminId,
    ],
);

check('a 12 × 25.00 sale totals 300.00', Money::display($total), '₪ 300.00');
check('the whole amount is owed', MerchantRepository::find($merchantId)['balance'], 30000);

Database::insert(
    'payments',
    [
        'merchant_id'  => $merchantId,
        'amount'       => 10000,
        'payment_date' => '2026-09-02',
        'created_by'   => $adminId,
    ],
);

check('a partial payment leaves the rest owed', MerchantRepository::find($merchantId)['balance'], 20000);

Database::insert(
    'discounts',
    [
        'merchant_id'   => $merchantId,
        'amount'        => 5000,
        'discount_date' => '2026-09-03',
        'created_by'    => $adminId,
    ],
);

check('a discount reduces what is owed', MerchantRepository::find($merchantId)['balance'], 15000);
check('the discount is recorded separately', MerchantRepository::find($merchantId)['total_discount'], 5000);
check('sales are untouched by the discount', MerchantRepository::find($merchantId)['total_sales'], 30000);

Database::insert(
    'payments',
    [
        'merchant_id'  => $merchantId,
        'amount'       => 15000,
        'payment_date' => '2026-09-04',
        'created_by'   => $adminId,
    ],
);

check('paying the rest clears the account', MerchantRepository::find($merchantId)['balance'], 0);
check('a cleared account is not listed as a debt', ReportRepository::topDebtors(), []);
check('the account timeline holds every movement', count(MerchantRepository::ledger($merchantId)), 4);

/* -------------------------------------------------------------- profit --- */

group('Profit for a period');

$materialId = (int) Database::scalar('SELECT id FROM materials LIMIT 1');
$workerId   = (int) Database::scalar('SELECT id FROM workers LIMIT 1');

Database::insert(
    'material_purchases',
    [
        'material_id'   => $materialId,
        'quantity'      => 20.0,
        'unit_cost'     => 2500,
        'total'         => Money::multiply(20.0, 2500),
        'purchase_date' => '2026-09-05',
        'created_by'    => $adminId,
    ],
);

Database::insert(
    'worker_payments',
    [
        'worker_id'   => $workerId,
        'hours'       => 7.5,
        'hourly_rate' => 2000,
        'total'       => Money::multiply(7.5, 2000),
        'work_date'   => '2026-09-05',
        'created_by'  => $adminId,
    ],
);

$summary = ReportRepository::summary('2026-09-01', '2026-09-30');

check('sales are totalled', $summary['sales'], 30000);
check('discounts are totalled', $summary['discounts'], 5000);
check('20 لتر × 25.00 of material', $summary['material_expenses'], 50000);
check('7.5 hours × 20.00 of wages', $summary['worker_expenses'], 15000);
check('expenses add up', $summary['expenses'], 65000);
check('profit = sales − discounts − expenses', $summary['profit'], 30000 - 5000 - 65000);
check('cash received is reported separately', $summary['received'], 25000);

$outside = ReportRepository::summary('2026-10-01', '2026-10-31');

check('another month reports nothing', $outside['sales'], 0);
check('and no expenses', $outside['expenses'], 0);

/* ------------------------------------------------ sale with its own costs --- */

group('One sale, its costs, and its net profit');

$blouseId = Database::insert(
    'job_types',
    ['name' => 'بلوزة', 'unit' => 'قطعة', 'default_unit_price' => 3000, 'created_by' => $adminId],
);

/* 30 blouses at 30.00 each calculates to 900.00, but 850.00 was agreed. */
$calculated = Money::multiply(30.0, 3000);
$agreed     = Money::parse('850');

check('30 × 30.00 calculates to 900.00', Money::display($calculated), '₪ 900.00');

$blouseSaleId = Database::insert(
    'sales',
    [
        'merchant_id' => $merchantId,
        'job_type_id' => $blouseId,
        'description' => 'بلوزة',
        'quantity'    => 30.0,
        'unit_price'  => 3000,
        'total'       => $agreed,
        'sale_date'   => '2026-09-10',
        'created_by'  => $adminId,
    ],
);

SaleCostRepository::replaceFor(
    $blouseSaleId,
    [
        [
            'material_id' => $materialId,
            'quantity'    => 10.0,
            'unit_cost'   => 1500,
            'total'       => Money::multiply(10.0, 1500),
            'note'        => '',
        ],
    ],
    [
        [
            'worker_id'   => $workerId,
            'hours'       => 2.0,
            'hourly_rate' => 1000,
            'total'       => Money::multiply(2.0, 1000),
            'note'        => '',
        ],
    ],
    '2026-09-10',
);

$blouseSale = SaleRepository::find($blouseSaleId);

check('the agreed total is what is stored', $blouseSale['total'], 85000);
check('the calculated total is still known', $blouseSale['calculated_total'], 90000);
check('the sale is flagged as adjusted', $blouseSale['is_adjusted'], true);
check('10 لتر × 15.00 of material', $blouseSale['material_costs'], 15000);
check('2 hours × 10.00 of wages', $blouseSale['worker_costs'], 2000);
check('costs add up to 170.00', Money::display($blouseSale['total_costs']), '₪ 170.00');
check('net profit is 850 − 170 = 680', Money::display($blouseSale['net_profit']), '₪ 680.00');

check('the material line is attached to the sale', count(SaleCostRepository::materialsFor($blouseSaleId)), 1);
check('the worker line is attached to the sale', count(SaleCostRepository::workersFor($blouseSaleId)), 1);

/* A cost line with no discount keeps the plain multiplication. */
$unadjusted = Database::insert(
    'sales',
    [
        'merchant_id' => $merchantId,
        'job_type_id' => $blouseId,
        'quantity'    => 30.0,
        'unit_price'  => 3000,
        'total'       => $calculated,
        'sale_date'   => '2026-09-10',
        'created_by'  => $adminId,
    ],
);

$plainSale = SaleRepository::find($unadjusted);

check('an untouched total is not flagged', $plainSale['is_adjusted'], false);
check('a sale with no costs is all profit', $plainSale['net_profit'], 90000);

/* Editing replaces the old cost lines rather than adding to them. */
SaleCostRepository::replaceFor(
    $blouseSaleId,
    [
        [
            'material_id' => $materialId,
            'quantity'    => 10.0,
            'unit_cost'   => 1500,
            'total'       => Money::parse('120'),
            'note'        => 'خصم من المورّد',
        ],
    ],
    [],
    '2026-09-10',
);

$edited = SaleRepository::find($blouseSaleId);

check('the old worker line is gone', $edited['worker_costs'], 0);
check('a supplier discount is kept as typed', $edited['material_costs'], 12000);
check('net profit follows the new cost', Money::display($edited['net_profit']), '₪ 730.00');
check('replacing does not duplicate lines', count(SaleCostRepository::materialsFor($blouseSaleId)), 1);

/* Sale-linked costs must still count once, and only once, in the period totals. */
$september = ReportRepository::summary('2026-09-01', '2026-09-30');

check(
    'period material cost includes both general and sale-linked',
    $september['material_expenses'],
    50000 + 12000,
);

/* ----------------------------------------------------------- validation --- */

group('Form validation');

$validator = new Validator([
    'name'     => '',
    'amount'   => 'abc',
    'zero'     => '0',
    'quantity' => '-5',
    'date'     => '2026-13-45',
    'ok_money' => '25.50',
]);

$validator->text('name', 'الاسم');
$validator->money('amount', 'المبلغ');
$validator->money('zero', 'المبلغ');
$validator->quantity('quantity', 'الكمية');
$validator->date('date', 'التاريخ');
$okAmount = $validator->money('ok_money', 'المبلغ');

check('a missing name is caught', isset($validator->errors()['name']), true);
check('a non-numeric amount is caught', isset($validator->errors()['amount']), true);
check('a zero amount is caught', isset($validator->errors()['zero']), true);
check('a negative quantity is caught', isset($validator->errors()['quantity']), true);
check('an impossible date is caught', isset($validator->errors()['date']), true);
check('a valid amount still parses', $okAmount, 2550);
check('the form is marked as failed', $validator->passes(), false);

$good = new Validator(['name' => 'أبو أحمد', 'amount' => '100', 'date' => '2026-09-22']);
$good->text('name', 'الاسم');
$good->money('amount', 'المبلغ');
$good->date('date', 'التاريخ');

check('a correct form passes', $good->passes(), true);

$missing = new Validator(['merchant_id' => '9999']);
$missing->existingId('merchant_id', 'التاجر', 'merchants');

check('a merchant that does not exist is rejected', $missing->passes(), false);

/* -------------------------------------------------------- admin guards --- */

group('Last admin protection');

check('one active admin remains', SettingsRepository::countActiveAdmins(), 1);
check('excluding that admin leaves none', SettingsRepository::countActiveAdmins($adminId), 0);

$staffId = (int) Database::scalar("SELECT id FROM users WHERE role = 'staff'");

check('a staff account is not counted as admin', SettingsRepository::countActiveAdmins($staffId), 1);

/* --------------------------------------------------------- in-use guard --- */

group('Configuration in use');

check('a job type used by a sale is in use', SettingsRepository::isInUse('sales', 'job_type_id', $jobTypeId), true);
check('an unused job type is free', SettingsRepository::isInUse('sales', 'job_type_id', 9999), false);

/* ----------------------------------------------------- page permissions --- */

group('Staff reach the sales pages only');

/** @var Router $router */
$router = require Config::basePath('src/routes.php');

$staffPages = [
    ['GET', '/sales'],
    ['GET', '/sales/new'],
    ['GET', '/sales/show'],
    ['GET', '/sales/edit'],
    ['POST', '/sales/store'],
    ['POST', '/sales/update'],
    ['GET', '/pricing'],
    ['POST', '/pricing/update'],
];

foreach ($staffPages as [$method, $path]) {
    check('staff may open ' . $path, $router->accessFor($method, $path), 'user');
}

$managerPages = [
    ['GET', '/merchants'],
    ['GET', '/merchants/show'],
    ['POST', '/merchants/store'],
    ['POST', '/merchants/update'],
    ['GET', '/payments'],
    ['POST', '/payments/store'],
    ['GET', '/discounts'],
    ['GET', '/material-expenses'],
    ['POST', '/material-expenses/store'],
    ['GET', '/worker-payments'],
    ['POST', '/worker-payments/store'],
    ['GET', '/reports'],
    ['GET', '/settings'],
    ['GET', '/users'],
    ['GET', '/audit'],
    ['GET', '/backup'],
    ['POST', '/sales/delete'],
];

foreach ($managerPages as [$method, $path]) {
    check('staff are blocked from ' . $path, $router->accessFor($method, $path), 'admin');
}

check('the login page stays open to guests', $router->accessFor('GET', '/login'), 'guest');

check('the admin lands on the dashboard', Auth::isAdmin() ? Auth::homePath() : null, '/');

Auth::logout();
Auth::attempt('any@staff.com', 'staff_password');

check('staff land on the sales page', Auth::homePath(), '/sales');
check('staff are not admins', Auth::isAdmin(), false);

Auth::logout();
Auth::attempt('muhammed@admin.com', 'admin123');

/* ---------------------------------------------------------------- audit --- */

group('Audit trail');

Audit::log(Audit::CREATED, 'اختبار', 1, 'سجل اختبار');

$entry = Database::selectOne('SELECT * FROM audit_log ORDER BY id DESC LIMIT 1');

check('the entry records the action', $entry['action'], Audit::CREATED);
check('the entry records the user name', $entry['user_name'], 'محمد (مدير)');

/* --------------------------------------------------------------- report --- */

echo "\n";
echo str_repeat('─', 52) . "\n";
echo $failed === 0
    ? "  كل الاختبارات نجحت — " . $passed . " اختبار\n"
    : "  " . $passed . " نجح / " . $failed . " فشل\n";
echo str_repeat('─', 52) . "\n";

foreach ([$databaseFile, $databaseFile . '-wal', $databaseFile . '-shm'] as $file) {
    if (is_file($file)) {
        unlink($file);
    }
}

exit($failed === 0 ? 0 : 1);
