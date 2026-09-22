<?php

use App\Auth;
use App\Form;
use App\Quantity;
use App\View;

/** @var array $purchases */
/** @var array $materials */
/** @var array $filters */
/** @var \App\FormState $form */

$errors  = $form->errors();
$old     = $form->old();
$isAdmin = Auth::isAdmin();

$materialOptions = array_map(
    static fn (array $material): array => [
        'value' => $material['id'],
        'label' => $material['name'] . ' (' . $material['unit'] . ')',
        'price' => $material['default_unit_cost'],
        'unit'  => $material['unit'],
    ],
    $materials,
);

?>
<div class="card">
    <h2>تسجيل مصروف مواد</h2>

    <div class="body">
        <?php if ($materialOptions === []) : ?>
            <div class="empty">لا توجد مواد مُفعّلة. اطلب من المدير إضافتها من «الإعدادات».</div>
        <?php else : ?>
            <?= Form::open('/material-expenses/store', ['live_total' => true]) ?>

            <div class="form-grid">
                <?= Form::select('material_id', 'المادة', $materialOptions, $errors, $old, [
                    'required'    => true,
                    'fills_price' => '#unit_cost',
                ]) ?>

                <?= Form::input('quantity', 'الكمية', $errors, $old, [
                    'required'  => true,
                    'attrs'     => 'data-quantity inputmode="decimal"',
                    'hint_html' => 'الوحدة: <span data-unit-for="material_id"></span>',
                ]) ?>

                <?= Form::input('unit_cost', 'سعر الوحدة', $errors, $old, [
                    'required' => true,
                    'attrs'    => 'data-price inputmode="decimal"',
                ]) ?>

                <?= Form::input('purchase_date', 'التاريخ', $errors, $old, [
                    'type'     => 'date',
                    'required' => true,
                    'value'    => date('Y-m-d'),
                ]) ?>

                <?= Form::totalBox('إجمالي المصروف') ?>
            </div>

            <div class="form-grid">
                <?= Form::input('supplier', 'اسم المورّد', $errors, $old, ['placeholder' => 'اختياري']) ?>
                <?= Form::input('note', 'ملاحظة', $errors, $old, ['placeholder' => 'اختياري']) ?>
            </div>

            <div class="actions">
                <?= Form::submit('حفظ المصروف') ?>
            </div>

            <?= Form::close() ?>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h2>سجل مصاريف المواد</h2>

    <div class="body">
        <form method="get" action="/material-expenses" class="filters">
            <div class="field">
                <label for="material_filter">المادة</label>
                <select id="material_filter" name="material_id">
                    <option value="">كل المواد</option>
                    <?php foreach ($materials as $material) : ?>
                        <option value="<?= (int) $material['id'] ?>" <?= (int) $material['id'] === (int) ($filters['material_id'] ?? 0) ? 'selected' : '' ?>>
                            <?= View::e($material['name']) ?>
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
                <a class="btn secondary" href="/material-expenses">إلغاء التصفية</a>
            </div>
        </form>
    </div>

    <?php if ($purchases === []) : ?>
        <div class="empty">لا توجد مصاريف مطابقة.</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>المادة</th>
                    <th class="num">الكمية</th>
                    <th class="num">سعر الوحدة</th>
                    <th class="num">الإجمالي</th>
                    <th>المورّد</th>
                    <th>يخص</th>
                    <th>سجّله</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($purchases as $purchase) : ?>
                    <tr>
                        <td class="num"><?= View::date($purchase['purchase_date']) ?></td>
                        <td>
                            <?= View::e($purchase['material_name']) ?>
                            <?php if (! empty($purchase['note'])) : ?>
                                <div class="who"><?= View::e($purchase['note']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="num"><?= View::e(Quantity::format((float) $purchase['quantity'])) ?> <?= View::e($purchase['material_unit']) ?></td>
                        <td class="num"><?= View::money((int) $purchase['unit_cost']) ?></td>
                        <td class="money out"><?= View::money((int) $purchase['total']) ?></td>
                        <td><?= View::e($purchase['supplier'] ?? '—') ?></td>
                        <td>
                            <?php if (! empty($purchase['sale_id'])) : ?>
                                <a href="/sales/show?id=<?= (int) $purchase['sale_id'] ?>">
                                    بيع <?= View::e($purchase['sale_merchant_name'] ?? '') ?>
                                </a>
                            <?php else : ?>
                                <span class="muted">مصروف عام</span>
                            <?php endif; ?>
                        </td>
                        <td class="who"><?= View::e($purchase['created_by_name'] ?? '—') ?></td>
                        <td>
                            <?php if ($isAdmin) : ?>
                                <?= Form::open('/material-expenses/delete', [
                                    'class'   => 'inline-form',
                                    'confirm' => 'حذف المصروف نهائياً؟',
                                ]) ?>
                                <?= Form::hidden('id', (int) $purchase['id']) ?>
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
