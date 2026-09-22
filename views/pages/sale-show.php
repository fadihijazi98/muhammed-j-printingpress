<?php

use App\Auth;
use App\Form;
use App\Quantity;
use App\View;

/** @var array $sale */
/** @var array $materialLines */
/** @var array $workerLines */

$isAdmin = Auth::isAdmin();

?>
<div class="card">
    <h2>
        <?= View::e($sale['job_type_name'] ?? $sale['description']) ?>
        —
        <?= View::merchantLink((int) $sale['merchant_id'], $sale['merchant_name']) ?>
    </h2>

    <div class="table-wrap">
        <table class="breakdown">
            <tbody>
            <tr class="group-row">
                <td colspan="3">البيع — <?= View::date($sale['sale_date']) ?></td>
            </tr>

            <tr>
                <td>
                    <?= View::e($sale['job_type_name'] ?? $sale['description']) ?>
                </td>
                <td class="num">
                    <?= View::e(Quantity::format($sale['quantity'])) ?>
                    <?= View::e($sale['job_unit'] ?? 'قطعة') ?>
                    × <?= View::money($sale['unit_price']) ?>
                </td>
                <td class="money in">
                    <?= View::money($sale['total']) ?>

                    <?php if ($sale['is_adjusted']) : ?>
                        <span class="adjusted-note">
                            المحسوب <?= View::money($sale['calculated_total']) ?>
                        </span>
                    <?php endif; ?>
                </td>
            </tr>

            <?php if ($materialLines !== []) : ?>
                <tr class="group-row">
                    <td colspan="3">تكلفة المواد</td>
                </tr>

                <?php foreach ($materialLines as $line) : ?>
                    <tr>
                        <td><?= View::e($line['material_name']) ?></td>
                        <td class="num">
                            <?= View::e(Quantity::format((float) $line['quantity'])) ?>
                            <?= View::e($line['material_unit']) ?>
                            × <?= View::money((int) $line['unit_cost']) ?>
                        </td>
                        <td class="money out">− <?= View::money((int) $line['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($workerLines !== []) : ?>
                <tr class="group-row">
                    <td colspan="3">أجور العمال</td>
                </tr>

                <?php foreach ($workerLines as $line) : ?>
                    <tr>
                        <td><?= View::e($line['worker_name']) ?></td>
                        <td class="num">
                            <?= View::e(Quantity::format((float) $line['hours'])) ?> ساعة
                            × <?= View::money((int) $line['hourly_rate']) ?>
                        </td>
                        <td class="money out">− <?= View::money((int) $line['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($sale['total_costs'] > 0) : ?>
                <tr>
                    <td colspan="2">إجمالي التكلفة</td>
                    <td class="money out">− <?= View::money($sale['total_costs']) ?></td>
                </tr>
            <?php endif; ?>

            <tr class="net-row">
                <td colspan="2">صافي الربح</td>
                <td class="<?= $sale['net_profit'] < 0 ? 'negative' : '' ?>">
                    <?= View::money($sale['net_profit']) ?>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="body">
        <?php if (! empty($sale['note'])) : ?>
            <p class="muted">ملاحظة: <?= View::e($sale['note']) ?></p>
        <?php endif; ?>

        <p class="who">
            سجّلها: <?= View::e($sale['created_by_name'] ?? '—') ?>
            <?php if (! empty($sale['updated_by_name'])) : ?>
                — آخر تعديل: <?= View::e($sale['updated_by_name']) ?>
            <?php endif; ?>
        </p>

        <div class="actions">
            <a class="btn" href="/sales/edit?id=<?= (int) $sale['id'] ?>">تعديل</a>
            <?php if ($isAdmin) : ?>
                <a class="btn secondary" href="/merchants/show?id=<?= (int) $sale['merchant_id'] ?>">حساب التاجر</a>
            <?php endif; ?>
            <a class="btn secondary" href="/sales">كل المبيعات</a>
        </div>

        <?php if ($isAdmin) : ?>
            <?= Form::open('/sales/delete', ['confirm' => 'حذف عملية البيع وكل تكاليفها نهائياً؟']) ?>
            <?= Form::hidden('id', (int) $sale['id']) ?>

            <div class="actions">
                <button class="btn danger" type="submit">حذف عملية البيع</button>
            </div>

            <?= Form::close() ?>
        <?php endif; ?>
    </div>
</div>
