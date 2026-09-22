<?php

declare(strict_types=1);

namespace App;

abstract class Controller
{
    protected function view(string $template, array $data = []): void
    {
        $errors = Session::takeErrors();
        $old    = Session::takeOld();
        $form   = new FormState(Session::takeFormKey(), $errors, $old);

        View::render(
            $template,
            $data + [
                'errors' => $errors,
                'old'    => $old,
                'form'   => $form,
            ],
        );
    }

    protected function validator(): Validator
    {
        return new Validator(Request::post());
    }

    /** Sends the user back to the form with their input and the error messages. */
    protected function rejectForm(Validator $validator, string $backTo, string $formKey = ''): never
    {
        Session::flashForm($validator->errors(), Request::post(), $formKey);
        Session::flash('لم يتم الحفظ. الرجاء مراجعة الحقول المظللة بالأحمر.', 'error');

        Response::redirect($backTo);
    }

    protected function saved(string $message, string $redirectTo): never
    {
        Session::flash($message);

        Response::redirect($redirectTo);
    }

    protected function requireAdmin(): void
    {
        if (! Auth::isAdmin()) {
            http_response_code(403);

            View::render('pages/error', ['title' => 'غير مسموح', 'message' => 'هذا الإجراء متاح للمدير فقط.']);

            exit;
        }
    }

    protected function postInt(string $key): int
    {
        $value = Money::normaliseDigits((string) (Request::post()[$key] ?? ''));

        return ctype_digit($value) ? (int) $value : 0;
    }

    protected function notFound(string $message = 'السجل غير موجود.'): never
    {
        http_response_code(404);

        View::render('pages/error', ['title' => 'غير موجود', 'message' => $message]);

        exit;
    }
}
