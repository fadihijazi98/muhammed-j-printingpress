<?php

use App\Config;
use App\Session;
use App\View;

/** @var string $content */
/** @var array $flashes */
/** @var array|null $user */
/** @var string $pageName */
/** @var string $title */
/** @var array $scripts */

$isAdmin = ($user['role'] ?? '') === 'admin';

/* [key, href, label, icon, admin only] */
$links = [
    ['dashboard', '/', 'الرئيسية', '🏠', true],
    ['merchants', '/merchants', 'التجار', '👥', true],
    ['sales', '/sales', 'المبيعات', '🧾', false],
    ['payments', '/payments', 'الدفعات', '💵', true],
    ['discounts', '/discounts', 'الخصومات', '🏷️', true],
    ['materials', '/material-expenses', 'مصاريف المواد', '🪣', true],
    ['workers', '/worker-payments', 'أجور العمال', '⏱️', true],
    ['pricing', '/pricing', 'أسعار البيع', '💲', false],
    ['reports', '/reports', 'التقارير', '📊', true],
    ['settings', '/settings', 'الإعدادات', '⚙️', true],
    ['users', '/users', 'المستخدمون', '🔑', true],
    ['audit', '/audit', 'سجل العمليات', '📜', true],
];

?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title) ?> — <?= View::e(Config::APP_NAME) ?></title>

    <link rel="stylesheet" href="/assets/app.css">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/icon-192.png">
    <meta name="theme-color" content="#0b5349">
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand"><?= View::e(Config::APP_NAME) ?></div>

        <nav>
            <?php foreach ($links as [$key, $href, $label, $icon, $adminOnly]) : ?>
                <?php if ($adminOnly && ! $isAdmin) { continue; } ?>

                <?php if ($key === 'pricing' && $isAdmin) : ?>
                    <div class="group-label">الإعداد</div>
                <?php endif; ?>

                <a href="<?= $href ?>" class="<?= $pageName === $key ? 'active' : '' ?>">
                    <span aria-hidden="true"><?= $icon ?></span>
                    <span><?= View::e($label) ?></span>
                </a>
            <?php endforeach; ?>

            <?php if ($isAdmin) : ?>
                <a href="/backup">
                    <span aria-hidden="true">💾</span>
                    <span>نسخة احتياطية</span>
                </a>
            <?php endif; ?>
        </nav>
    </aside>

    <main class="main">
        <div class="topbar">
            <h1><?= View::e($title) ?></h1>

            <div class="whoami">
                <span class="name"><?= View::e($user['name'] ?? '') ?></span>
                <span class="badge <?= $isAdmin ? '' : 'staff' ?>"><?= $isAdmin ? 'مدير' : 'موظف' ?></span>

                <form method="post" action="/logout" class="inline-form">
                    <input type="hidden" name="_token" value="<?= View::e(Session::csrfToken()) ?>">
                    <button class="btn small secondary" type="submit">خروج</button>
                </form>
            </div>
        </div>

        <?php foreach ($flashes as $flash) : ?>
            <div class="flash <?= View::e($flash['type']) ?>"><?= View::e($flash['message']) ?></div>
        <?php endforeach; ?>

        <?= $content ?>
    </main>
</div>

<script src="/assets/app.js"></script>

<?php foreach (($scripts ?? []) as $script) : ?>
    <script src="<?= View::e($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
