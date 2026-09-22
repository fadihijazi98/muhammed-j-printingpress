<?php

use App\Form;
use App\Money;
use App\View;

/** @var array $jobTypes */
/** @var array $materials */
/** @var array $workers */
/** @var \App\FormState $form */

?>
<div class="card">
    <h2>أنواع العمل وأسعار البيع</h2>

    <div class="body">
        <?php $errors = $form->errors('job-type-create'); $old = $form->old('job-type-create'); ?>

        <?= Form::open('/settings/job-types/store') ?>

        <div class="form-grid">
            <?= Form::input('name', 'اسم نوع العمل', $errors, $old, ['required' => true, 'placeholder' => 'مثال: طباعة فليكس']) ?>
            <?= Form::input('unit', 'الوحدة', $errors, $old, ['required' => true, 'placeholder' => 'مثال: متر']) ?>
            <?= Form::input('default_unit_price', 'سعر الوحدة', $errors, $old, ['required' => true, 'attrs' => 'inputmode="decimal"']) ?>
        </div>

        <div class="actions">
            <?= Form::submit('إضافة نوع عمل') ?>
        </div>

        <?= Form::close() ?>
    </div>

    <?php if ($jobTypes === []) : ?>
        <div class="empty">لا توجد أنواع عمل بعد.</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الوحدة</th>
                    <th class="num">سعر الوحدة</th>
                    <th>الحالة</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($jobTypes as $jobType) : ?>
                    <?php
                    $id        = (int) $jobType['id'];
                    $rowErrors = $form->errors('job-type-' . $id);
                    $rowOld    = $form->old('job-type-' . $id);
                    ?>
                    <tr>
                        <td><?= View::e($jobType['name']) ?></td>
                        <td><?= View::e($jobType['unit']) ?></td>
                        <td class="money"><?= View::money((int) $jobType['default_unit_price']) ?></td>
                        <td>
                            <span class="badge <?= (int) $jobType['is_active'] === 1 ? 'on' : 'off' ?>">
                                <?= (int) $jobType['is_active'] === 1 ? 'مُفعّل' : 'موقوف' ?>
                            </span>
                        </td>
                        <td>
                            <details class="editor" <?= $rowErrors === [] ? '' : 'open' ?>>
                                <summary>تعديل</summary>

                                <?= Form::open('/settings/job-types/update') ?>
                                <?= Form::hidden('id', $id) ?>

                                <div class="form-grid">
                                    <?= Form::input('name', 'الاسم', $rowErrors, $rowOld, ['value' => $jobType['name'], 'required' => true]) ?>
                                    <?= Form::input('unit', 'الوحدة', $rowErrors, $rowOld, ['value' => $jobType['unit'], 'required' => true]) ?>
                                    <?= Form::input('default_unit_price', 'سعر الوحدة', $rowErrors, $rowOld, [
                                        'value'    => Money::format((int) $jobType['default_unit_price']),
                                        'required' => true,
                                        'attrs'    => 'inputmode="decimal"',
                                    ]) ?>
                                    <?= Form::checkbox('is_active', 'مُفعّل', (int) $jobType['is_active'] === 1) ?>
                                </div>

                                <div class="actions">
                                    <?= Form::submit('حفظ') ?>
                                </div>

                                <?= Form::close() ?>

                                <?= Form::open('/settings/job-types/delete', ['confirm' => 'حذف نوع العمل نهائياً؟']) ?>
                                <?= Form::hidden('id', $id) ?>

                                <div class="actions">
                                    <button class="btn danger" type="submit">حذف</button>
                                </div>

                                <?= Form::close() ?>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>المواد ومصاريفها</h2>

    <div class="body">
        <?php $errors = $form->errors('material-create'); $old = $form->old('material-create'); ?>

        <?= Form::open('/settings/materials/store') ?>

        <div class="form-grid">
            <?= Form::input('name', 'اسم المادة', $errors, $old, ['required' => true, 'placeholder' => 'مثال: دهان']) ?>
            <?= Form::input('unit', 'الوحدة', $errors, $old, ['required' => true, 'placeholder' => 'مثال: لتر']) ?>
            <?= Form::input('default_unit_cost', 'سعر الوحدة', $errors, $old, ['required' => true, 'attrs' => 'inputmode="decimal"']) ?>
        </div>

        <div class="actions">
            <?= Form::submit('إضافة مادة') ?>
        </div>

        <?= Form::close() ?>
    </div>

    <?php if ($materials === []) : ?>
        <div class="empty">لا توجد مواد بعد.</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الوحدة</th>
                    <th class="num">سعر الوحدة</th>
                    <th>الحالة</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($materials as $material) : ?>
                    <?php
                    $id        = (int) $material['id'];
                    $rowErrors = $form->errors('material-' . $id);
                    $rowOld    = $form->old('material-' . $id);
                    ?>
                    <tr>
                        <td><?= View::e($material['name']) ?></td>
                        <td><?= View::e($material['unit']) ?></td>
                        <td class="money"><?= View::money((int) $material['default_unit_cost']) ?></td>
                        <td>
                            <span class="badge <?= (int) $material['is_active'] === 1 ? 'on' : 'off' ?>">
                                <?= (int) $material['is_active'] === 1 ? 'مُفعّلة' : 'موقوفة' ?>
                            </span>
                        </td>
                        <td>
                            <details class="editor" <?= $rowErrors === [] ? '' : 'open' ?>>
                                <summary>تعديل</summary>

                                <?= Form::open('/settings/materials/update') ?>
                                <?= Form::hidden('id', $id) ?>

                                <div class="form-grid">
                                    <?= Form::input('name', 'الاسم', $rowErrors, $rowOld, ['value' => $material['name'], 'required' => true]) ?>
                                    <?= Form::input('unit', 'الوحدة', $rowErrors, $rowOld, ['value' => $material['unit'], 'required' => true]) ?>
                                    <?= Form::input('default_unit_cost', 'سعر الوحدة', $rowErrors, $rowOld, [
                                        'value'    => Money::format((int) $material['default_unit_cost']),
                                        'required' => true,
                                        'attrs'    => 'inputmode="decimal"',
                                    ]) ?>
                                    <?= Form::checkbox('is_active', 'مُفعّلة', (int) $material['is_active'] === 1) ?>
                                </div>

                                <div class="actions">
                                    <?= Form::submit('حفظ') ?>
                                </div>

                                <?= Form::close() ?>

                                <?= Form::open('/settings/materials/delete', ['confirm' => 'حذف المادة نهائياً؟']) ?>
                                <?= Form::hidden('id', $id) ?>

                                <div class="actions">
                                    <button class="btn danger" type="submit">حذف</button>
                                </div>

                                <?= Form::close() ?>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>العمال وأجرة الساعة</h2>

    <div class="body">
        <?php $errors = $form->errors('worker-create'); $old = $form->old('worker-create'); ?>

        <?= Form::open('/settings/workers/store') ?>

        <div class="form-grid">
            <?= Form::input('name', 'اسم العامل', $errors, $old, ['required' => true]) ?>
            <?= Form::input('phone', 'رقم الهاتف', $errors, $old, ['placeholder' => 'اختياري']) ?>
            <?= Form::input('hourly_rate', 'أجرة الساعة', $errors, $old, ['required' => true, 'attrs' => 'inputmode="decimal"']) ?>
        </div>

        <div class="actions">
            <?= Form::submit('إضافة عامل') ?>
        </div>

        <?= Form::close() ?>
    </div>

    <?php if ($workers === []) : ?>
        <div class="empty">لا يوجد عمال بعد.</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th class="num">أجرة الساعة</th>
                    <th>الحالة</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($workers as $worker) : ?>
                    <?php
                    $id        = (int) $worker['id'];
                    $rowErrors = $form->errors('worker-' . $id);
                    $rowOld    = $form->old('worker-' . $id);
                    ?>
                    <tr>
                        <td><?= View::e($worker['name']) ?></td>
                        <td class="num"><?= View::e($worker['phone'] ?? '—') ?></td>
                        <td class="money"><?= View::money((int) $worker['hourly_rate']) ?></td>
                        <td>
                            <span class="badge <?= (int) $worker['is_active'] === 1 ? 'on' : 'off' ?>">
                                <?= (int) $worker['is_active'] === 1 ? 'مُفعّل' : 'موقوف' ?>
                            </span>
                        </td>
                        <td>
                            <details class="editor" <?= $rowErrors === [] ? '' : 'open' ?>>
                                <summary>تعديل</summary>

                                <?= Form::open('/settings/workers/update') ?>
                                <?= Form::hidden('id', $id) ?>

                                <div class="form-grid">
                                    <?= Form::input('name', 'الاسم', $rowErrors, $rowOld, ['value' => $worker['name'], 'required' => true]) ?>
                                    <?= Form::input('phone', 'رقم الهاتف', $rowErrors, $rowOld, ['value' => $worker['phone']]) ?>
                                    <?= Form::input('hourly_rate', 'أجرة الساعة', $rowErrors, $rowOld, [
                                        'value'    => Money::format((int) $worker['hourly_rate']),
                                        'required' => true,
                                        'attrs'    => 'inputmode="decimal"',
                                    ]) ?>
                                    <?= Form::checkbox('is_active', 'مُفعّل', (int) $worker['is_active'] === 1) ?>
                                </div>

                                <div class="actions">
                                    <?= Form::submit('حفظ') ?>
                                </div>

                                <?= Form::close() ?>

                                <?= Form::open('/settings/workers/delete', ['confirm' => 'حذف العامل نهائياً؟']) ?>
                                <?= Form::hidden('id', $id) ?>

                                <div class="actions">
                                    <button class="btn danger" type="submit">حذف</button>
                                </div>

                                <?= Form::close() ?>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
