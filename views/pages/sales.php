<?php

use App\Auth;
use App\Form;
use App\Quantity;
use App\View;

/** @var array $sales */
/** @var array $merchants */
/** @var array $filters */

$isAdmin = Auth::isAdmin();

?>
<div class="card">
    <div class="body">
        <div class="actions" style="margin:0">
            <a class="btn" href="/sales/new" style="font-size:19px;padding:14px 22px">
                ＋ تسجيل عملية بيع
            </a>
        </div>
    </div>
</div>

<div class="card">
    <h2>سجل المبيعات</h2>

    <div class="body">
        <form method="get" action="/sales" class="filters">
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
                <a class="btn secondary" href="/sales">إلغاء التصفية</a>
            </div>
        </form>
    </div>

    <?php if ($sales === []) : ?>
        <div class="empty">لا توجد مبيعات مطابقة.</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>التاجر</th>
                    <th>الصنف</th>
                    <th class="num">العدد × السعر</th>
                    <th class="num">سعر البيع</th>
                    <th class="num">التكلفة</th>
                    <th class="num">صافي الربح</th>
                    <th>سجّله</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($sales as $sale) : ?>
                    <tr>
                        <td class="num"><?= View::date($sale['sale_date']) ?></td>
                        <td><?= View::merchantLink((int) $sale['merchant_id'], $sale['merchant_name']) ?></td>
                        <td>
                            <?= View::e($sale['job_type_name'] ?? $sale['description']) ?>
                            <?php if (! empty($sale['note'])) : ?>
                                <div class="who"><?= View::e($sale['note']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="num">
                            <?= View::e(Quantity::format($sale['quantity'])) ?>
                            <?= View::e($sale['job_unit'] ?? 'قطعة') ?>
                            × <?= View::money($sale['unit_price']) ?>
                        </td>
                        <td class="money in">
                            <?= View::money($sale['total']) ?>
                            <?php if ($sale['is_adjusted']) : ?>
                                <div class="who">بدل <?= View::money($sale['calculated_total']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="money <?= $sale['total_costs'] > 0 ? 'out' : '' ?>">
                            <?= $sale['total_costs'] > 0 ? View::money($sale['total_costs']) : '—' ?>
                        </td>
                        <td class="money <?= $sale['net_profit'] < 0 ? 'out' : 'in' ?>">
                            <?= View::money($sale['net_profit']) ?>
                        </td>
                        <td class="who"><?= View::e($sale['created_by_name'] ?? '—') ?></td>
                        <td>
                            <a class="btn small secondary" href="/sales/show?id=<?= (int) $sale['id'] ?>">تفاصيل</a>

                            <?php if ($isAdmin) : ?>
                                <?= Form::open('/sales/delete', [
                                    'class'   => 'inline-form',
                                    'confirm' => 'حذف عملية البيع وكل تكاليفها نهائياً؟',
                                ]) ?>
                                <?= Form::hidden('id', (int) $sale['id']) ?>
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
