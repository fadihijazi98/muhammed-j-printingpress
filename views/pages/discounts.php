<?php

use App\Form;
use App\View;

/** @var array $discounts */
/** @var array $merchants */
/** @var \App\FormState $form */

$errors = $form->errors('discount');
$old    = $form->old('discount');

$merchantOptions = array_map(
    static fn (array $merchant): array => ['value' => $merchant['id'], 'label' => $merchant['name']],
    $merchants,
);

?>
<div class="card">
    <h2>خصم على حساب تاجر</h2>

    <div class="body">
        <?php if ($merchantOptions === []) : ?>
            <div class="empty">أضف تاجراً أولاً من صفحة «التجار».</div>
        <?php else : ?>
            <?= Form::open('/discounts/store') ?>

            <div class="form-grid">
                <?= Form::select('merchant_id', 'التاجر', $merchantOptions, $errors, $old, [
                    'required'    => true,
                    'placeholder' => '— اختر التاجر —',
                ]) ?>

                <?= Form::input('amount', 'قيمة الخصم', $errors, $old, [
                    'required' => true,
                    'attrs'    => 'inputmode="decimal"',
                    'hint'     => 'الخصم يُنقص المبلغ المستحق على التاجر.',
                ]) ?>

                <?= Form::input('discount_date', 'التاريخ', $errors, $old, [
                    'type'     => 'date',
                    'required' => true,
                    'value'    => date('Y-m-d'),
                ]) ?>

                <?= Form::input('reason', 'سبب الخصم', $errors, $old, ['placeholder' => 'اختياري']) ?>
            </div>

            <div class="actions">
                <?= Form::submit('حفظ الخصم') ?>
            </div>

            <?= Form::close() ?>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h2>سجل الخصومات</h2>

    <?php if ($discounts === []) : ?>
        <div class="empty">لا توجد خصومات مسجلة.</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>التاجر</th>
                    <th>السبب</th>
                    <th class="num">قيمة الخصم</th>
                    <th>سجّله</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($discounts as $discount) : ?>
                    <tr>
                        <td class="num"><?= View::date($discount['discount_date']) ?></td>
                        <td>
                            <a href="/merchants/show?id=<?= (int) $discount['merchant_id'] ?>"><?= View::e($discount['merchant_name']) ?></a>
                        </td>
                        <td><?= View::e($discount['reason'] ?? '—') ?></td>
                        <td class="money in"><?= View::money((int) $discount['amount']) ?></td>
                        <td class="who"><?= View::e($discount['created_by_name'] ?? '—') ?></td>
                        <td>
                            <?= Form::open('/discounts/delete', [
                                'class'   => 'inline-form',
                                'confirm' => 'حذف الخصم نهائياً؟',
                            ]) ?>
                            <?= Form::hidden('id', (int) $discount['id']) ?>
                            <button class="btn small danger" type="submit">حذف</button>
                            <?= Form::close() ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
