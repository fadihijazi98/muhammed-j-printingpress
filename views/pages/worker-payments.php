<?php

use App\Auth;
use App\Form;
use App\Quantity;
use App\View;

/** @var array $payments */
/** @var array $workers */
/** @var array $filters */
/** @var \App\FormState $form */

$errors  = $form->errors();
$old     = $form->old();
$isAdmin = Auth::isAdmin();

$workerOptions = array_map(
    static fn (array $worker): array => [
        'value' => $worker['id'],
        'label' => $worker['name'],
        'price' => $worker['hourly_rate'],
        'unit'  => 'ساعة',
    ],
    $workers,
);

?>
<div class="card">
    <h2>تسجيل أجرة عامل</h2>

    <div class="body">
        <?php if ($workerOptions === []) : ?>
            <div class="empty">لا يوجد عمال مُفعّلون. اطلب من المدير إضافتهم من «الإعدادات».</div>
        <?php else : ?>
            <?= Form::open('/worker-payments/store', ['live_total' => true]) ?>

            <div class="form-grid">
                <?= Form::select('worker_id', 'العامل', $workerOptions, $errors, $old, [
                    'required'    => true,
                    'fills_price' => '#hourly_rate',
                ]) ?>

                <?= Form::input('hours', 'عدد الساعات', $errors, $old, [
                    'required' => true,
                    'attrs'    => 'data-quantity inputmode="decimal"',
                    'hint'     => 'يمكن إدخال كسور، مثال: 7.5',
                ]) ?>

                <?= Form::input('hourly_rate', 'أجرة الساعة', $errors, $old, [
                    'required' => true,
                    'attrs'    => 'data-price inputmode="decimal"',
                ]) ?>

                <?= Form::input('work_date', 'التاريخ', $errors, $old, [
                    'type'     => 'date',
                    'required' => true,
                    'value'    => date('Y-m-d'),
                ]) ?>

                <?= Form::totalBox('إجمالي الأجرة') ?>
            </div>

            <div class="form-grid">
                <?= Form::input('note', 'ملاحظة', $errors, $old, ['placeholder' => 'اختياري']) ?>
            </div>

            <div class="actions">
                <?= Form::submit('حفظ الأجرة') ?>
            </div>

            <?= Form::close() ?>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h2>سجل أجور العمال</h2>

    <div class="body">
        <form method="get" action="/worker-payments" class="filters">
            <div class="field">
                <label for="worker_filter">العامل</label>
                <select id="worker_filter" name="worker_id">
                    <option value="">كل العمال</option>
                    <?php foreach ($workers as $worker) : ?>
                        <option value="<?= (int) $worker['id'] ?>" <?= (int) $worker['id'] === (int) ($filters['worker_id'] ?? 0) ? 'selected' : '' ?>>
                            <?= View::e($worker['name']) ?>
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
                <a class="btn secondary" href="/worker-payments">إلغاء التصفية</a>
            </div>
        </form>
    </div>

    <?php if ($payments === []) : ?>
        <div class="empty">لا توجد أجور مطابقة.</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>العامل</th>
                    <th class="num">الساعات</th>
                    <th class="num">أجرة الساعة</th>
                    <th class="num">الإجمالي</th>
                    <th>يخص</th>
                    <th>سجّله</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($payments as $payment) : ?>
                    <tr>
                        <td class="num"><?= View::date($payment['work_date']) ?></td>
                        <td>
                            <?= View::e($payment['worker_name']) ?>
                            <?php if (! empty($payment['note'])) : ?>
                                <div class="who"><?= View::e($payment['note']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="num"><?= View::e(Quantity::format((float) $payment['hours'])) ?> ساعة</td>
                        <td class="num"><?= View::money((int) $payment['hourly_rate']) ?></td>
                        <td class="money out"><?= View::money((int) $payment['total']) ?></td>
                        <td>
                            <?php if (! empty($payment['sale_id'])) : ?>
                                <a href="/sales/show?id=<?= (int) $payment['sale_id'] ?>">
                                    بيع <?= View::e($payment['sale_merchant_name'] ?? '') ?>
                                </a>
                            <?php else : ?>
                                <span class="muted">مصروف عام</span>
                            <?php endif; ?>
                        </td>
                        <td class="who"><?= View::e($payment['created_by_name'] ?? '—') ?></td>
                        <td>
                            <?php if ($isAdmin) : ?>
                                <?= Form::open('/worker-payments/delete', [
                                    'class'   => 'inline-form',
                                    'confirm' => 'حذف السجل نهائياً؟',
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
