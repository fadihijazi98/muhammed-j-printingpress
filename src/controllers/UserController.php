<?php

declare(strict_types=1);

namespace App;

final class UserController extends Controller
{
    public function index(): void
    {
        $this->view(
            'pages/users',
            [
                'title'    => 'المستخدمون',
                'pageName' => 'users',
                'users'    => SettingsRepository::users(),
            ],
        );
    }

    public function store(): void
    {
        $validator = $this->validator();

        $name  = $validator->text('name', 'الاسم');
        $email = $validator->email('email', 'البريد الإلكتروني');
        $role  = $validator->choice('role', 'الصلاحية', [Auth::ROLE_STAFF, Auth::ROLE_ADMIN]);

        $password = (string) (Request::post()['password'] ?? '');

        if (mb_strlen($password) < 6) {
            $validator->fail('password', 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.');
        }

        if (! $validator->passes()) {
            $this->rejectForm($validator, '/users', 'user-create');
        }

        Database::transaction(
            static function () use ($name, $email, $role, $password): void {
                $id = Database::insert(
                    'users',
                    [
                        'name'          => $name,
                        'email'         => $email,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'role'          => $role,
                    ],
                );

                Audit::log(Audit::CREATED, 'مستخدم', $id, 'إضافة مستخدم: ' . $name . ' (' . Auth::roleLabel($role) . ')');
            },
        );

        $this->saved('تمت إضافة المستخدم.', '/users');
    }

    public function update(): void
    {
        $id   = $this->postInt('id');
        $user = SettingsRepository::findUser($id);

        if ($user === null) {
            $this->notFound('المستخدم غير موجود.');
        }

        $validator = $this->validator();

        $name     = $validator->text('name', 'الاسم');
        $email    = $validator->email('email', 'البريد الإلكتروني', $id);
        $role     = $validator->choice('role', 'الصلاحية', [Auth::ROLE_STAFF, Auth::ROLE_ADMIN]);
        $isActive = isset(Request::post()['is_active']) ? 1 : 0;

        $password = (string) (Request::post()['password'] ?? '');

        if ($password !== '' && mb_strlen($password) < 6) {
            $validator->fail('password', 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.');
        }

        $losesAdminAccess = $user['role'] === Auth::ROLE_ADMIN
            && ($role !== Auth::ROLE_ADMIN || $isActive === 0);

        if ($losesAdminAccess && SettingsRepository::countActiveAdmins($id) === 0) {
            $validator->fail('role', 'لا يمكن إزالة آخر حساب مدير فعّال.');
        }

        if (! $validator->passes()) {
            $this->rejectForm($validator, '/users', 'user-' . $id);
        }

        $values = [
            'name'       => $name,
            'email'      => $email,
            'role'       => $role,
            'is_active'  => $isActive,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($password !== '') {
            $values['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        Database::transaction(
            static function () use ($id, $values, $name, $password): void {
                Database::update('users', $id, $values);

                Audit::log(
                    Audit::UPDATED,
                    'مستخدم',
                    $id,
                    'تعديل مستخدم: ' . $name . ($password !== '' ? ' (مع تغيير كلمة المرور)' : ''),
                );
            },
        );

        $this->saved('تم حفظ بيانات المستخدم.', '/users');
    }

    public function destroy(): void
    {
        $id   = $this->postInt('id');
        $user = SettingsRepository::findUser($id);

        if ($user === null) {
            $this->notFound('المستخدم غير موجود.');
        }

        if ($id === Auth::id()) {
            Session::flash('لا يمكنك حذف حسابك أنت.', 'error');

            Response::redirect('/users');
        }

        if ($user['role'] === Auth::ROLE_ADMIN && SettingsRepository::countActiveAdmins($id) === 0) {
            Session::flash('لا يمكن حذف آخر حساب مدير.', 'error');

            Response::redirect('/users');
        }

        /* Records keep pointing at the user who made them, so the account is
           switched off rather than removed. */
        Database::transaction(
            static function () use ($id, $user): void {
                Database::update(
                    'users',
                    $id,
                    [
                        'is_active'  => 0,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ],
                );

                Audit::log(Audit::DELETED, 'مستخدم', $id, 'إيقاف حساب: ' . $user['name']);
            },
        );

        $this->saved('تم إيقاف الحساب. سجل حركاته محفوظ.', '/users');
    }
}
