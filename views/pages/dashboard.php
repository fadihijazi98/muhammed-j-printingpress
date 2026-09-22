<?php

use App\View;

/** @var array $today */
/** @var array $month */
/** @var int $outstanding */
/** @var array $debtors */
/** @var array $recentSales */

?>
<div class="section-title">اليوم — <?= View::date(date('Y-m-d')) ?></div>

<div class="stat-grid">
    <div class="stat good">
        <div class="label">مبيعات اليوم</div>
        <div class="value"><?= View::money($today['sales']) ?></div>
    </div>

    <div class="stat good">
        <div class="label">المقبوض اليوم</div>
        <div class="value"><?= View::money($today['received']) ?></div>
    </div>

    <div class="stat bad">
        <div class="label">مصاريف اليوم</div>
        <div class="value"><?= View::money($today['expenses']) ?></div>
    </div>

    <div class="stat <?= $today['profit'] >= 0 ? 'good' : 'bad' ?>">
        <div class="label">ربح اليوم</div>
        <div class="value"><?= View::money($today['profit']) ?></div>
    </div>
</div>

<div class="section-title">هذا الشهر — <?= View::date($month['from']) ?> إلى <?= View::date($month['to']) ?></div>

<div class="stat-grid">
    <div class="stat good">
        <div class="label">مبيعات الشهر</div>
        <div class="value"><?= View::money($month['sales']) ?></div>
    </div>

    <div class="stat bad">
        <div class="label">مصاريف المواد</div>
        <div class="value"><?= View::money($month['material_expenses']) ?></div>
    </div>

    <div class="stat bad">
        <div class="label">أجور العمال</div>
        <div class="value"><?= View::money($month['worker_expenses']) ?></div>
    </div>

    <div class="stat <?= $month['profit'] >= 0 ? 'good' : 'bad' ?>">
        <div class="label">ربح الشهر</div>
        <div class="value"><?= View::money($month['profit']) ?></div>
    </div>

    <div class="stat warn">
        <div class="label">إجمالي الذمم (لنا عند التجار)</div>
        <div class="value"><?= View::money($outstanding) ?></div>
    </div>
</div>

<div class="card">
    <h2>أكثر التجار عليهم ذمم</h2>

    <?php if ($debtors === []) : ?>
        <div class="empty">لا توجد ذمم مستحقة. كل الحسابات مسددة. ✅</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>التاجر</th>
                    <th class="num">إجمالي المبيعات</th>
                    <th class="num">المدفوع</th>
                    <th class="num">المتبقي</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($debtors as $merchant) : ?>
                    <tr>
                        <td><?= View::e($merchant['name']) ?></td>
                        <td class="num"><?= View::money($merchant['total_sales']) ?></td>
                        <td class="num"><?= View::money($merchant['total_paid']) ?></td>
                        <td class="money out"><?= View::money($merchant['balance']) ?></td>
                        <td>
                            <a class="btn small secondary" href="/merchants/show?id=<?= (int) $merchant['id'] ?>">فتح الحساب</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>آخر المبيعات</h2>

    <?php if ($recentSales === []) : ?>
        <div class="empty">لا توجد مبيعات مسجلة بعد. ابدأ من صفحة «المبيعات».</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>التاجر</th>
                    <th>نوع العمل</th>
                    <th class="num">الكمية</th>
                    <th class="num">الإجمالي</th>
                    <th>سجّله</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentSales as $sale) : ?>
                    <tr>
                        <td class="num"><?= View::date($sale['sale_date']) ?></td>
                        <td><?= View::e($sale['merchant_name']) ?></td>
                        <td><?= View::e($sale['job_type_name'] ?? $sale['description']) ?></td>
                        <td class="num"><?= View::e(App\Quantity::format((float) $sale['quantity'])) ?> <?= View::e($sale['job_unit'] ?? '') ?></td>
                        <td class="money in"><?= View::money((int) $sale['total']) ?></td>
                        <td class="who"><?= View::e($sale['created_by_name'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
