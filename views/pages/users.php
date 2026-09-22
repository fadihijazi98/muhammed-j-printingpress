<?php

use App\Auth;
use App\Form;
use App\View;

/** @var array $users */
/** @var \App\FormState $form */

$roleOptions = [
    ['value' => Auth::ROLE_STAFF, 'label' => 'موظف — يسجّل المبيعات والمصاريف ويحدّث الأسعار'],
    ['value' => Auth::ROLE_ADMIN, 'label' => 'مدير — صلاحية كاملة'],
];

?>
<div class="card">
    <h2>إضافة مستخدم</h2>

    <div class="body">
        <?php $errors = $form->errors('user-create'); $old = $form->old('user-create'); ?>

        <?= Form::open('/users/store') ?>

        <div class="form-grid">
            <?= Form::input('name', 'الاسم', $errors, $old, ['required' => true]) ?>
            <?= Form::input('email', 'البريد الإلكتروني', $errors, $old, ['type' => 'email', 'required' => true]) ?>
            <?= Form::input('password', 'كلمة المرور', $errors, $old, [
                'type'     => 'password',
                'required' => true,
                'hint'     => '6 أحرف على الأقل.',
            ]) ?>
            <?= Form::select('role', 'الصلاحية', $roleOptions, $errors, $old, ['required' => true]) ?>
        </div>

        <div class="actions">
            <?= Form::submit('إضافة المستخدم') ?>
        </div>

        <?= Form::close() ?>
    </div>
</div>

<div class="card">
    <h2>المستخدمون</h2>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>الاسم</th>
                <th>البريد الإلكتروني</th>
                <th>الصلاحية</th>
                <th>الحالة</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $account) : ?>
                <?php
                $id        = (int) $account['id'];
                $rowErrors = $form->errors('user-' . $id);
                $rowOld    = $form->old('user-' . $id);
                ?>
                <tr>
                    <td>
                        <?= View::e($account['name']) ?>
                        <?php if ($id === Auth::id()) : ?>
                            <span class="badge">أنت</span>
                        <?php endif; ?>
                    </td>
                    <td><?= View::e($account['email']) ?></td>
                    <td>
                        <span class="badge <?= $account['role'] === Auth::ROLE_ADMIN ? '' : 'staff' ?>">
                            <?= View::e(Auth::roleLabel($account['role'])) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= (int) $account['is_active'] === 1 ? 'on' : 'off' ?>">
                            <?= (int) $account['is_active'] === 1 ? 'فعّال' : 'موقوف' ?>
                        </span>
                    </td>
                    <td>
                        <details class="editor" <?= $rowErrors === [] ? '' : 'open' ?>>
                            <summary>تعديل</summary>

                            <?= Form::open('/users/update') ?>
                            <?= Form::hidden('id', $id) ?>

                            <div class="form-grid">
                                <?= Form::input('name', 'الاسم', $rowErrors, $rowOld, ['value' => $account['name'], 'required' => true]) ?>
                                <?= Form::input('email', 'البريد الإلكتروني', $rowErrors, $rowOld, [
                                    'type'     => 'email',
                                    'value'    => $account['email'],
                                    'required' => true,
                                ]) ?>
                                <?= Form::input('password', 'كلمة مرور جديدة', $rowErrors, $rowOld, [
                                    'type' => 'password',
                                    'hint' => 'اتركها فارغة لإبقاء كلمة المرور الحالية.',
                                ]) ?>
                                <?= Form::select('role', 'الصلاحية', $roleOptions, $rowErrors, $rowOld, ['value' => $account['role']]) ?>
                                <?= Form::checkbox('is_active', 'الحساب فعّال', (int) $account['is_active'] === 1) ?>
                            </div>

                            <div class="actions">
                                <?= Form::submit('حفظ') ?>
                            </div>

                            <?= Form::close() ?>

                            <?php if ($id !== Auth::id()) : ?>
                                <?= Form::open('/users/delete', ['confirm' => 'إيقاف هذا الحساب؟']) ?>
                                <?= Form::hidden('id', $id) ?>

                                <div class="actions">
                                    <button class="btn danger" type="submit">إيقاف الحساب</button>
                                </div>

                                <?= Form::close() ?>
                            <?php endif; ?>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
