<?php

use App\Auth;
use App\Form;
use App\Quantity;
use App\View;

/** @var array $merchant */
/** @var array $ledger */
/** @var \App\FormState $form */

$merchantId = (int) $merchant['id'];
$isAdmin    = Auth::isAdmin();

$payErrors   = $form->errors('payment');
$payOld      = $form->old('payment');
$discErrors  = $form->errors('discount');
$discOld     = $form->old('discount');
$editErrors  = $form->errors('merchant-edit');
$editOld     = $form->old('merchant-edit');

$kindLabels = ['sale' => 'بيع', 'payment' => 'دفعة', 'discount' => 'خصم'];

?>
<div class="stat-grid">
    <div class="stat">
        <div class="label">إجمالي المبيعات</div>
        <div class="value"><?= View::money($merchant['total_sales']) ?></div>
    </div>

    <div class="stat good">
        <div class="label">المدفوع</div>
        <div class="value"><?= View::money($merchant['total_paid']) ?></div>
    </div>

    <div class="stat warn">
        <div class="label">الخصومات</div>
        <div class="value"><?= View::money($merchant['total_discount']) ?></div>
    </div>

    <div class="stat <?= $merchant['balance'] > 0 ? 'bad' : 'good' ?>">
        <div class="label">المتبقي عليه</div>
        <div class="value"><?= View::money($merchant['balance']) ?></div>
    </div>
</div>

<div class="card">
    <h2>تسجيل بيع لهذا التاجر</h2>

    <div class="body">
        <p class="muted">
            صفحة البيع تسجّل العدد والسعر وتكلفة المواد وأجور العمال معاً، وتعرض صافي الربح مباشرة.
        </p>

        <div class="actions">
            <a class="btn" href="/sales/new?merchant_id=<?= $merchantId ?>" style="font-size:19px;padding:14px 22px">
                ＋ تسجيل عملية بيع
            </a>
        </div>
    </div>
</div>

<div class="card">
    <h2>تسجيل دفعة من هذا التاجر</h2>

    <div class="body">
        <?= Form::open('/payments/store') ?>
        <?= Form::hidden('merchant_id', $merchantId) ?>
        <?= Form::hidden('return_merchant_id', $merchantId) ?>

        <div class="form-grid">
            <?= Form::input('amount', 'المبلغ المدفوع', $payErrors, $payOld, [
                'required' => true,
                'attrs'    => 'inputmode="decimal"',
                'hint'     => 'يمكن تسجيل دفعة جزئية. المتبقي حالياً: ' . View::money($merchant['balance']),
            ]) ?>

            <?= Form::input('payment_date', 'التاريخ', $payErrors, $payOld, [
                'type'     => 'date',
                'required' => true,
                'value'    => date('Y-m-d'),
            ]) ?>

            <?= Form::input('method', 'طريقة الدفع', $payErrors, $payOld, ['placeholder' => 'نقداً / شيك / تحويل']) ?>
            <?= Form::input('note', 'ملاحظة', $payErrors, $payOld, ['placeholder' => 'اختياري']) ?>
        </div>

        <div class="actions">
            <?= Form::submit('حفظ الدفعة') ?>
        </div>

        <?= Form::close() ?>
    </div>
</div>

<?php if ($isAdmin) : ?>
    <div class="card">
        <h2>خصم على الحساب</h2>

        <div class="body">
            <?= Form::open('/discounts/store') ?>
            <?= Form::hidden('merchant_id', $merchantId) ?>
            <?= Form::hidden('return_merchant_id', $merchantId) ?>

            <div class="form-grid">
                <?= Form::input('amount', 'قيمة الخصم', $discErrors, $discOld, [
                    'required' => true,
                    'attrs'    => 'inputmode="decimal"',
                ]) ?>

                <?= Form::input('discount_date', 'التاريخ', $discErrors, $discOld, [
                    'type'     => 'date',
                    'required' => true,
                    'value'    => date('Y-m-d'),
                ]) ?>

                <?= Form::input('reason', 'سبب الخصم', $discErrors, $discOld, ['placeholder' => 'اختياري']) ?>
            </div>

            <div class="actions">
                <?= Form::submit('حفظ الخصم') ?>
            </div>

            <?= Form::close() ?>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <h2>كشف حساب التاجر</h2>

    <?php if ($ledger === []) : ?>
        <div class="empty">لا توجد حركات على هذا الحساب بعد.</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>النوع</th>
                    <th>التفاصيل</th>
                    <th class="num">الكمية × السعر</th>
                    <th class="num">المبلغ</th>
                    <th>سجّله</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ledger as $entry) : ?>
                    <?php $isCharge = $entry['kind'] === 'sale'; ?>
                    <tr>
                        <td class="num"><?= View::date($entry['date']) ?></td>
                        <td><span class="badge <?= $isCharge ? '' : 'staff' ?>"><?= View::e($kindLabels[$entry['kind']]) ?></span></td>
                        <td>
                            <?= View::e($entry['label']) ?>
                            <?php if (! empty($entry['note'])) : ?>
                                <div class="who"><?= View::e($entry['note']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="num">
                            <?php if ($entry['quantity'] !== null) : ?>
                                <?= View::e(Quantity::format((float) $entry['quantity'])) ?>
                                <?= View::e($entry['unit'] ?? '') ?>
                                × <?= View::money((int) $entry['unit_price']) ?>
                            <?php else : ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="money <?= $isCharge ? 'out' : 'in' ?>">
                            <?= $isCharge ? '+' : '−' ?> <?= View::money((int) $entry['amount']) ?>
                        </td>
                        <td class="who"><?= View::e($entry['created_by_name'] ?? '—') ?></td>
                        <td>
                            <?php if ($entry['kind'] === 'sale') : ?>
                                <a class="btn small secondary" href="/sales/show?id=<?= (int) $entry['id'] ?>">تفاصيل</a>
                            <?php endif; ?>

                            <?php if ($isAdmin) : ?>
                                <?php
                                $deleteRoutes = [
                                    'sale'     => '/sales/delete',
                                    'payment'  => '/payments/delete',
                                    'discount' => '/discounts/delete',
                                ];
                                ?>
                                <?= Form::open($deleteRoutes[$entry['kind']], [
                                    'class'   => 'inline-form',
                                    'confirm' => 'هل أنت متأكد من الحذف؟ لا يمكن التراجع.',
                                ]) ?>
                                <?= Form::hidden('id', (int) $entry['id']) ?>
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

<div class="card">
    <h2>بيانات التاجر</h2>

    <div class="body">
        <?= Form::open('/merchants/update') ?>
        <?= Form::hidden('id', $merchantId) ?>

        <div class="form-grid">
            <?= Form::input('name', 'اسم التاجر', $editErrors, $editOld, ['required' => true, 'value' => $merchant['name']]) ?>
            <?= Form::input('phone', 'رقم الهاتف', $editErrors, $editOld, ['value' => $merchant['phone']]) ?>
            <?= Form::input('note', 'ملاحظة', $editErrors, $editOld, ['value' => $merchant['note']]) ?>
            <?= Form::checkbox('is_active', 'التاجر مُفعّل', (int) $merchant['is_active'] === 1) ?>
        </div>

        <div class="actions">
            <?= Form::submit('حفظ التعديلات') ?>
            <a class="btn secondary" href="/merchants">رجوع للقائمة</a>
        </div>

        <?= Form::close() ?>

        <?php if ($isAdmin) : ?>
            <div class="actions">
                <?= Form::open('/merchants/delete', ['confirm' => 'حذف التاجر نهائياً؟']) ?>
                <?= Form::hidden('id', $merchantId) ?>
                <button class="btn danger" type="submit">حذف التاجر</button>
                <?= Form::close() ?>
            </div>
        <?php endif; ?>
    </div>
</div>
