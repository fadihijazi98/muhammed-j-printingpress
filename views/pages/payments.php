<?php

use App\Auth;
use App\Form;
use App\View;

/** @var array $payments */
/** @var array $merchants */
/** @var array $filters */
/** @var \App\FormState $form */

$errors  = $form->errors('payment');
$old     = $form->old('payment');
$isAdmin = Auth::isAdmin();

$merchantOptions = array_map(
    static fn (array $merchant): array => ['value' => $merchant['id'], 'label' => $merchant['name']],
    $merchants,
);

?>
<div class="card">
    <h2>تسجيل دفعة من تاجر</h2>

    <div class="body">
        <?php if ($merchantOptions === []) : ?>
            <div class="empty">أضف تاجراً أولاً من صفحة «التجار».</div>
        <?php else : ?>
            <?= Form::open('/payments/store') ?>

            <div class="form-grid">
                <?= Form::select('merchant_id', 'التاجر', $merchantOptions, $errors, $old, [
                    'required'    => true,
                    'placeholder' => '— اختر التاجر —',
                ]) ?>

                <?= Form::input('amount', 'المبلغ المدفوع', $errors, $old, [
                    'required' => true,
                    'attrs'    => 'inputmode="decimal"',
                    'hint'     => 'يمكن تسجيل دفعة جزئية.',
                ]) ?>

                <?= Form::input('payment_date', 'التاريخ', $errors, $old, [
                    'type'     => 'date',
                    'required' => true,
                    'value'    => date('Y-m-d'),
                ]) ?>

                <?= Form::input('method', 'طريقة الدفع', $errors, $old, ['placeholder' => 'نقداً / شيك / تحويل']) ?>
                <?= Form::input('note', 'ملاحظة', $errors, $old, ['placeholder' => 'اختياري']) ?>
            </div>

            <div class="actions">
                <?= Form::submit('حفظ الدفعة') ?>
            </div>

            <?= Form::close() ?>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h2>سجل الدفعات</h2>

    <div class="body">
        <form method="get" action="/payments" class="filters">
            <div class="field">
                <label for="merchant_filter">التاجر</label>
                <select id="merchant_filter" name="merchant_id">
                    <option value="">كل التجار</option>
                    <?php foreach ($merchants as $merchant) : ?>
                        <option value="<?= (int) $merchant['id'] ?>" <?= (int) $merchant['id'] === (int) ($filters['merchant_id'] ?? 0) ? 'selected' : '' ?>>
                            <?= View::e($merchant['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="from">من تاريخ</label>
                <input type="date" id="from" name="from" value="<?= View::e((string) ($filters['from'] ?? '')) ?>">
            </div>

            <div class="field">
                <label for="to">إلى تاريخ</label>
                <input type="date" id="to" name="to" value="<?= View::e((string) ($filters['to'] ?? '')) ?>">
            </div>

            <div class="actions" style="margin:0">
                <button class="btn secondary" type="submit">عرض</button>
                <a class="btn secondary" href="/payments">إلغاء التصفية</a>
            </div>
        </form>
    </div>

    <?php if ($payments === []) : ?>
        <div class="empty">لا توجد دفعات مطابقة.</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>التاجر</th>
                    <th>طريقة الدفع</th>
                    <th class="num">المبلغ</th>
                    <th>سجّله</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($payments as $payment) : ?>
                    <tr>
                        <td class="num"><?= View::date($payment['payment_date']) ?></td>
                        <td>
                            <a href="/merchants/show?id=<?= (int) $payment['merchant_id'] ?>"><?= View::e($payment['merchant_name']) ?></a>
                        </td>
                        <td>
                            <?= View::e($payment['method'] ?? '—') ?>
                            <?php if (! empty($payment['note'])) : ?>
                                <div class="who"><?= View::e($payment['note']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="money in"><?= View::money((int) $payment['amount']) ?></td>
                        <td class="who"><?= View::e($payment['created_by_name'] ?? '—') ?></td>
                        <td>
                            <?php if ($isAdmin) : ?>
                                <?= Form::open('/payments/delete', [
                                    'class'   => 'inline-form',
                                    'confirm' => 'حذف الدفعة نهائياً؟',
                                ]) ?>
                                <?= Form::hidden('id', (int) $payment['id']) ?>
                                <button class="btn small danger" type="submit">حذف</button>
                                <?= Form::close() ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
