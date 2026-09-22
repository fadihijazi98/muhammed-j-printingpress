<?php

declare(strict_types=1);

namespace App;

require_once dirname(__DIR__) . '/src/bootstrap.php';

try {
    /** @var Router $router */
    $router = require Config::basePath('src/routes.php');

    $router->dispatch(Request::method(), Request::path());
} catch (\Throwable $exception) {
    error_log((string) $exception);

    http_response_code(500);

    View::render(
        'pages/error',
        [
            'title'   => 'حدث خطأ',
            'message' => 'حدث خطأ غير متوقع. تم تسجيل التفاصيل في ملف data/error.log.',
        ],
    );
}
