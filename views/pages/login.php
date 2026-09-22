<?php

use App\Config;
use App\View;

/** @var array $errors */
/** @var array $old */
/** @var array $flashes */
/** @var string $token */

?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الدخول — <?= View::e(Config::APP_NAME) ?></title>

    <link rel="stylesheet" href="/assets/app.css">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/icon-192.png">
    <meta name="theme-color" content="#0b5349">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <h1><?= View::e(Config::APP_NAME) ?></h1>
        <p class="sub">إدارة الحسابات والمصاريف</p>

        <?php foreach ($flashes as $flash) : ?>
            <div class="flash <?= View::e($flash['type']) ?>"><?= View::e($flash['message']) ?></div>
        <?php endforeach; ?>

        <form method="post" action="/login">
            <input type="hidden" name="_token" value="<?= View::e($token) ?>">

            <div class="field">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email" required
                       value="<?= View::e((string) ($old['email'] ?? '')) ?>"
                       class="<?= isset($errors['email']) ? 'error' : '' ?>">
                <?php if (isset($errors['email'])) : ?>
                    <div class="msg"><?= View::e($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="password">كلمة المرور</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button class="btn" type="submit">دخول</button>
        </form>

        <div class="hint-box">
            <strong>الحسابات الافتراضية</strong><br>
            المدير: muhammed@admin.com / admin123<br>
            الموظف: any@staff.com / staff_password
        </div>
    </div>
</div>

<script src="/assets/app.js"></script>
</body>
</html>
