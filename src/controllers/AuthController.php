<?php

declare(strict_types=1);

namespace App;

final class AuthController extends Controller
{
    /** The login page stands alone — it has no sidebar, so it skips the layout. */
    public function showLogin(): void
    {
        echo View::capture(
            'pages/login',
            [
                'errors'  => Session::takeErrors(),
                'old'     => Session::takeOld(),
                'flashes' => Session::takeFlashes(),
                'token'   => Session::csrfToken(),
            ],
        );
    }

    public function login(): void
    {
        $email    = trim((string) (Request::post()['email'] ?? ''));
        $password = (string) (Request::post()['password'] ?? '');

        if (! Auth::attempt($email, $password)) {
            Session::flashForm(
                ['email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'],
                ['email' => $email],
            );

            Response::redirect('/login');
        }

        Audit::log(Audit::CREATED, 'دخول', Auth::id(), 'تسجيل دخول');

        Response::redirect(Auth::homePath());
    }

    public function logout(): void
    {
        Audit::log(Audit::CREATED, 'خروج', Auth::id(), 'تسجيل خروج');

        Auth::logout();

        Response::redirect('/login');
    }
}
