<?php

declare(strict_types=1);

namespace App;

final class Router
{
    /** @var array<string, array<string, array{0: class-string, 1: string, 2: string}>> */
    private array $routes = ['GET' => [], 'POST' => []];

    /** $access is one of: guest, user, admin. */
    public function get(string $path, array $handler, string $access = 'user'): void
    {
        $this->routes['GET'][$path] = [$handler[0], $handler[1], $access];
    }

    public function post(string $path, array $handler, string $access = 'user'): void
    {
        $this->routes['POST'][$path] = [$handler[0], $handler[1], $access];
    }

    /** The access level a route requires: guest, user or admin. Null when unrouted. */
    public function accessFor(string $method, string $path): ?string
    {
        return $this->routes[$method][$path][2] ?? null;
    }

    public function dispatch(string $method, string $path): void
    {
        $route = $this->routes[$method][$path] ?? null;

        if ($route === null) {
            http_response_code(404);

            View::render('pages/error', ['title' => 'الصفحة غير موجودة', 'message' => 'الرابط المطلوب غير موجود.']);

            return;
        }

        [$class, $action, $access] = $route;

        if ($access !== 'guest' && ! Auth::check()) {
            Response::redirect('/login');
        }

        if ($access === 'guest' && Auth::check()) {
            Response::redirect(Auth::homePath());
        }

        if ($access === 'admin' && ! Auth::isAdmin()) {
            http_response_code(403);

            View::render('pages/error', ['title' => 'غير مسموح', 'message' => 'هذه الصفحة متاحة للمدير فقط.']);

            return;
        }

        if ($method === 'POST' && ! Session::csrfMatches($_POST['_token'] ?? null)) {
            http_response_code(419);

            View::render('pages/error', ['title' => 'انتهت الجلسة', 'message' => 'انتهت صلاحية الصفحة. الرجاء تسجيل الدخول من جديد ثم إعادة المحاولة.']);

            return;
        }

        (new $class())->{$action}();
    }
}
