<?php

use App\Quantity;
use App\View;

/** @var array $summary */
/** @var array $salesByJobType */
/** @var array $salesByMerchant */
/** @var array $materialBreakdown */
/** @var array $workerBreakdown */
/** @var array $debtors */
/** @var string $from */
/** @var string $to */

?>
<div class="card">
    <h2>اختر الفترة</h2>

    <div class="body">
        <form method="get" action="/reports" class="filters">
            <div class="field">
                <label for="from">من تاريخ</label>
                <input type="date" id="from" name="from" value="<?= View::e($from) ?>">
            </div>

            <div class="field">
                <label for="to">إلى تاريخ</label>
                <input type="date" id="to" name="to" value="<?= View::e($to) ?>">
            </div>

            <div class="actions" style="margin:0">
                <button class="btn" type="submit">عرض التقرير</button>
                <a class="btn secondary" href="/reports?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-t') ?>">هذا الشهر</a>
                <a class="btn secondary" href="/reports?from=<?= date('Y-01-01') ?>&to=<?= date('Y-12-31') ?>">هذه السنة</a>
            </div>
        </form>
    </div>
</div>

<div class="section-title">من <?= View::date($from) ?> إلى <?= View::date($to) ?></div>

<div class="stat-grid">
    <div class="stat good">
        <div class="label">المبيعات</div>
        <div class="value"><?= View::money($summary['sales']) ?></div>
    </div>

    <div class="stat warn">
        <div class="label">الخصومات</div>
        <div class="value"><?= View::money($summary['discounts']) ?></div>
    </div>

    <div class="stat bad">
        <div class="label">مصاريف المواد</div>
        <div class="value"><?= View::money($summary['material_expenses']) ?></div>
    </div>

    <div class="stat bad">
        <div class="label">أجور العمال</div>
        <div class="value"><?= View::money($summary['worker_expenses']) ?></div>
    </div>

    <div class="stat <?= $summary['profit'] >= 0 ? 'good' : 'bad' ?>">
        <div class="label">الربح</div>
        <div class="value"><?= View::money($summary['profit']) ?></div>
    </div>

    <div class="stat">
        <div class="label">المقبوض نقداً في الفترة</div>
        <div class="value"><?= View::money($summary['received']) ?></div>
    </div>
</div>

<div class="card">
    <div class="body muted">
        الربح = المبيعات − الخصومات − (مصاريف المواد + أجور العمال).
        يُحسب على أساس العمل المنفَّذ خلال الفترة، وليس على أساس المقبوض نقداً؛
        لذلك تبقى المبيعات غير المحصّلة محسوبة ضمن الربح وتظهر في جدول الذمم بالأسفل.
    </div>
</div>

<div class="card">
    <h2>المبيعات حسب نوع العمل</h2>

    <?php if ($salesByJobType === []) : ?>
        <div class="empty">لا توجد مبيعات في هذه الفترة.</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>نوع العمل</th>
                    <th class="num">إجمالي الكمية</th>
                    <th class="num">الإجمالي</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($salesByJobType as $row) : ?>
                    <tr>
                        <td><?= View::e($row['name']) ?></td>
                        <td class="num"><?= View::e(Quantity::format((float) $row['quantity'])) ?> <?= View::e($row['unit'] ?? '') ?></td>
                        <td class="money in"><?= View::money((int) $row['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>المبيعات حسب التاجر</h2>

    <?php if ($salesByMerchant === []) : ?>
        <div class="empty">لا توجد مبيعات في هذه الفترة.</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>التاجر</th>
                    <th class="num">الإجمالي</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($salesByMerchant as $row) : ?>
                    <tr>
                        <td><?= View::e($row['name']) ?></td>
                        <td class="money in"><?= View::money((int) $row['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>مصاريف المواد حسب النوع</h2>

    <?php if ($materialBreakdown === []) : ?>
        <div class="empty">لا توجد مصاريف مواد في هذه الفترة.</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>المادة</th>
                    <th class="num">الكمية</th>
                    <th class="num">الإجمالي</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($materialBreakdown as $row) : ?>
                    <tr>
                        <td><?= View::e($row['name']) ?></td>
                        <td class="num"><?= View::e(Quantity::format((float) $row['quantity'])) ?> <?= View::e($row['unit']) ?></td>
                        <td class="money out"><?= View::money((int) $row['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>أجور العمال</h2>

    <?php if ($workerBreakdown === []) : ?>
        <div class="empty">لا توجد أجور في هذه الفترة.</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>العامل</th>
                    <th class="num">مجموع الساعات</th>
                    <th class="num">الإجمالي</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($workerBreakdown as $row) : ?>
                    <tr>
                        <td><?= View::e($row['name']) ?></td>
                        <td class="num"><?= View::e(Quantity::format((float) $row['hours'])) ?> ساعة</td>
                        <td class="money out"><?= View::money((int) $row['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>الذمم المستحقة حالياً (كل الفترات)</h2>

    <?php if ($debtors === []) : ?>
        <div class="empty">لا توجد ذمم مستحقة. ✅</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>التاجر</th>
                    <th class="num">المبيعات</th>
                    <th class="num">الخصومات</th>
                    <th class="num">المدفوع</th>
                    <th class="num">المتبقي</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($debtors as $merchant) : ?>
                    <tr>
                        <td><?= View::e($merchant['name']) ?></td>
                        <td class="num"><?= View::money($merchant['total_sales']) ?></td>
                        <td class="num"><?= View::money($merchant['total_discount']) ?></td>
                        <td class="num"><?= View::money($merchant['total_paid']) ?></td>
                        <td class="money out"><?= View::money($merchant['balance']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
