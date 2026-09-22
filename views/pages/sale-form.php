<?php

use App\Auth;
use App\Form;
use App\Money;
use App\Quantity;
use App\View;

/** @var array|null $sale */
/** @var int|null $merchantId */
/** @var array $merchants */
/** @var array $jobTypes */
/** @var array $materials */
/** @var array $workers */
/** @var array $materialLines */
/** @var array $workerLines */
/** @var \App\FormState $form */

$errors = $form->errors('sale');
$old    = $form->old('sale');

$isEdit = $sale !== null;
$action = $isEdit ? '/sales/update' : '/sales/store';

$merchantOptions = array_map(
    static fn (array $merchant): array => ['value' => $merchant['id'], 'label' => $merchant['name']],
    $merchants,
);

$jobOptions = array_map(
    static fn (array $jobType): array => [
        'value' => $jobType['id'],
        'label' => $jobType['name'],
        'price' => $jobType['default_unit_price'],
        'unit'  => $jobType['unit'],
    ],
    $jobTypes,
);

/** Rows already typed but rejected by validation win over rows loaded from the database. */
$materialRows = $old['materials'] ?? array_map(
    static fn (array $line): array => [
        'material_id' => (string) $line['material_id'],
        'quantity'    => Quantity::format((float) $line['quantity']),
        'unit_cost'   => Money::format((int) $line['unit_cost']),
        'total'       => Money::format((int) $line['total']),
        'note'        => (string) ($line['note'] ?? ''),
    ],
    $materialLines,
);

$workerRows = $old['workers'] ?? array_map(
    static fn (array $line): array => [
        'worker_id'   => (string) $line['worker_id'],
        'hours'       => Quantity::format((float) $line['hours']),
        'hourly_rate' => Money::format((int) $line['hourly_rate']),
        'total'       => Money::format((int) $line['total']),
        'note'        => (string) ($line['note'] ?? ''),
    ],
    $workerLines,
);

$value = static function (string $field, string $fallback = '') use ($old, $sale): string {
    if (array_key_exists($field, $old)) {
        return (string) $old[$field];
    }

    return $fallback;
};

?>
<?= Form::open($action, ['class' => 'sale-form']) ?>
<?php if ($isEdit) : ?>
    <?= Form::hidden('id', (int) $sale['id']) ?>
<?php endif; ?>

<div class="sale-layout">
    <div class="sale-main">

        <section class="card step">
            <h2><span class="step-no">١</span> بيانات البيع</h2>

            <div class="body">
                <?php if ($merchantOptions === [] || $jobOptions === []) : ?>
                    <div class="empty">
                        <?php if ($merchantOptions === []) : ?>
                            <?= Auth::isAdmin()
                                ? 'أضف تاجراً أولاً من صفحة «التجار».'
                                : 'لا يوجد تجار بعد. اطلب من المدير إضافة تاجر.' ?>
                        <?php else : ?>
                            لا توجد أصناف مُفعّلة. اطلب من المدير إضافتها من «الإعدادات».
                        <?php endif; ?>
                    </div>
                <?php else : ?>
                    <div class="form-grid">
                        <?= Form::select('merchant_id', 'التاجر', $merchantOptions, $errors, $old, [
                            'required'    => true,
                            'placeholder' => '— اختر التاجر —',
                            'value'       => $isEdit ? $sale['merchant_id'] : $merchantId,
                        ]) ?>

                        <?= Form::select('job_type_id', 'الصنف', $jobOptions, $errors, $old, [
                            'required'    => true,
                            'placeholder' => '— اختر الصنف —',
                            'fills_price' => '#unit_price',
                            'value'       => $isEdit ? $sale['job_type_id'] : null,
                        ]) ?>

                        <?= Form::input('sale_date', 'التاريخ', $errors, $old, [
                            'type'     => 'date',
                            'required' => true,
                            'value'    => $isEdit ? $sale['sale_date'] : date('Y-m-d'),
                        ]) ?>
                    </div>

                    <div class="calc-row" data-calc="sale">
                        <?= Form::input('quantity', 'العدد', $errors, $old, [
                            'required'  => true,
                            'value'     => $isEdit ? Quantity::format((float) $sale['quantity']) : '',
                            'attrs'     => 'data-quantity inputmode="decimal" placeholder="30"',
                            'hint_html' => 'الوحدة: <span data-unit-for="job_type_id">قطعة</span>',
                        ]) ?>

                        <div class="calc-sign">×</div>

                        <?= Form::input('unit_price', 'سعر القطعة', $errors, $old, [
                            'required' => true,
                            'value'    => $isEdit ? Money::format((int) $sale['unit_price']) : '',
                            'attrs'    => 'data-price inputmode="decimal" placeholder="30"',
                        ]) ?>

                        <div class="calc-sign">=</div>

                        <div class="field calc-result">
                            <label>المحسوب</label>
                            <div class="computed" data-computed>₪ 0.00</div>
                        </div>
                    </div>

                    <div class="form-grid final-row">
                        <?= Form::input('total', 'الإجمالي النهائي على التاجر', $errors, $old, [
                            'required'  => true,
                            'value'     => $isEdit ? Money::format((int) $sale['total']) : '',
                            'attrs'     => 'data-final inputmode="decimal"',
                            'wrapper_class' => 'field-strong',
                            'hint_html' => 'يُعبّأ تلقائياً من العدد × السعر. عدّله إذا اتفقت على مبلغ آخر. '
                                . '<button type="button" class="link-btn" data-reset-final>إعادة الحساب</button>',
                        ]) ?>

                        <?= Form::input('note', 'ملاحظة', $errors, $old, [
                            'value'       => $isEdit ? $sale['note'] : '',
                            'placeholder' => 'اختياري',
                        ]) ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card step">
            <h2>
                <span class="step-no">٢</span> تكلفة المواد
                <span class="optional">اختياري</span>
            </h2>

            <div class="body">
                <?php if ($materials === []) : ?>
                    <div class="empty">لا توجد مواد مُفعّلة. اطلب من المدير إضافتها من «الإعدادات».</div>
                <?php else : ?>
                    <div class="cost-lines" data-lines="materials"></div>

                    <button type="button" class="btn secondary add-line" data-add="materials">
                        ＋ إضافة مادة
                    </button>
                <?php endif; ?>
            </div>
        </section>

        <section class="card step">
            <h2>
                <span class="step-no">٣</span> أجور العمال
                <span class="optional">اختياري</span>
            </h2>

            <div class="body">
                <?php if ($workers === []) : ?>
                    <div class="empty">لا يوجد عمال مُفعّلون. اطلب من المدير إضافتهم من «الإعدادات».</div>
                <?php else : ?>
                    <div class="cost-lines" data-lines="workers"></div>

                    <button type="button" class="btn secondary add-line" data-add="workers">
                        ＋ إضافة عامل
                    </button>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <aside class="sale-summary">
        <div class="card summary-card">
            <h2>الملخص</h2>

            <div class="body">
                <div class="summary-line">
                    <span>سعر البيع</span>
                    <strong data-summary="sale">₪ 0.00</strong>
                </div>

                <div class="summary-line cost">
                    <span>تكلفة المواد</span>
                    <strong data-summary="materials">₪ 0.00</strong>
                </div>

                <div class="summary-line cost">
                    <span>أجور العمال</span>
                    <strong data-summary="workers">₪ 0.00</strong>
                </div>

                <div class="summary-line total-cost">
                    <span>إجمالي التكلفة</span>
                    <strong data-summary="costs">₪ 0.00</strong>
                </div>

                <div class="summary-net">
                    <span>صافي الربح</span>
                    <strong data-summary="net">₪ 0.00</strong>
                </div>

                <button class="btn save-btn" type="submit">
                    <?= $isEdit ? 'حفظ التعديلات' : 'حفظ عملية البيع' ?>
                </button>

                <a class="btn secondary cancel-btn" href="<?= $isEdit ? '/sales/show?id=' . (int) $sale['id'] : '/sales' ?>">
                    إلغاء
                </a>
            </div>
        </div>
    </aside>
</div>

<?= Form::close() ?>

<template id="tpl-materials">
    <div class="cost-line" data-calc="line">
        <div class="field line-pick">
            <label>المادة</label>
            <select name="materials[__i__][material_id]" data-line-select>
                <option value="">— اختر المادة —</option>
                <?php foreach ($materials as $material) : ?>
                    <option value="<?= (int) $material['id'] ?>"
                            data-price="<?= View::e(Money::format((int) $material['default_unit_cost'])) ?>"
                            data-unit="<?= View::e($material['unit']) ?>">
                        <?= View::e($material['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>الكمية <span class="line-unit"></span></label>
            <input type="text" name="materials[__i__][quantity]" inputmode="decimal" data-quantity placeholder="10">
        </div>

        <div class="calc-sign">×</div>

        <div class="field">
            <label>سعر الوحدة</label>
            <input type="text" name="materials[__i__][unit_cost]" inputmode="decimal" class="line-price" data-price placeholder="15">
        </div>

        <div class="calc-sign">=</div>

        <div class="field field-strong">
            <label>الإجمالي</label>
            <input type="text" name="materials[__i__][total]" inputmode="decimal" data-final>
        </div>

        <button type="button" class="btn small danger remove-line" data-remove title="حذف السطر">✕</button>
    </div>
</template>

<template id="tpl-workers">
    <div class="cost-line" data-calc="line">
        <div class="field line-pick">
            <label>العامل</label>
            <select name="workers[__i__][worker_id]" data-line-select>
                <option value="">— اختر العامل —</option>
                <?php foreach ($workers as $worker) : ?>
                    <option value="<?= (int) $worker['id'] ?>"
                            data-price="<?= View::e(Money::format((int) $worker['hourly_rate'])) ?>"
                            data-unit="ساعة">
                        <?= View::e($worker['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>عدد الساعات</label>
            <input type="text" name="workers[__i__][hours]" inputmode="decimal" data-quantity placeholder="2">
        </div>

        <div class="calc-sign">×</div>

        <div class="field">
            <label>أجرة الساعة</label>
            <input type="text" name="workers[__i__][hourly_rate]" inputmode="decimal" class="line-price" data-price placeholder="10">
        </div>

        <div class="calc-sign">=</div>

        <div class="field field-strong">
            <label>الإجمالي</label>
            <input type="text" name="workers[__i__][total]" inputmode="decimal" data-final>
        </div>

        <button type="button" class="btn small danger remove-line" data-remove title="حذف السطر">✕</button>
    </div>
</template>

<script type="application/json" id="existing-lines">
    <?= json_encode(
        ['materials' => array_values($materialRows), 'workers' => array_values($workerRows)],
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP,
    ) ?>
</script>

<script type="application/json" id="line-errors">
    <?= json_encode($errors, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>
</script>
