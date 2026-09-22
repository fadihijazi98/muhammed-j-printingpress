<?php

use App\Auth;
use App\Form;
use App\View;

/** @var array $merchants */
/** @var string|null $search */
/** @var \App\FormState $form */

$errors = $form->errors('merchant-create');
$old    = $form->old('merchant-create');

?>
<div class="card">
    <h2>إضافة تاجر جديد</h2>

    <div class="body">
        <?= Form::open('/merchants/store') ?>

        <div class="form-grid">
            <?= Form::input('name', 'اسم التاجر', $errors, $old, ['required' => true, 'placeholder' => 'مثال: أبو أحمد']) ?>
            <?= Form::input('phone', 'رقم الهاتف', $errors, $old, ['placeholder' => 'اختياري']) ?>
            <?= Form::input('note', 'ملاحظة', $errors, $old, ['placeholder' => 'اختياري']) ?>
        </div>

        <div class="actions">
            <?= Form::submit('حفظ التاجر') ?>
        </div>

        <?= Form::close() ?>
    </div>
</div>

<div class="card">
    <h2>قائمة التجار</h2>

    <div class="body">
        <form method="get" action="/merchants" class="filters">
            <div class="field">
                <label for="q">بحث بالاسم أو الهاتف</label>
                <input type="search" id="q" name="q" value="<?= View::e((string) $search) ?>">
            </div>

            <div class="actions" style="margin:0">
                <button class="btn secondary" type="submit">بحث</button>
                <?php if ($search !== null && $search !== '') : ?>
                    <a class="btn secondary" href="/merchants">إلغاء البحث</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if ($merchants === []) : ?>
        <div class="empty">لا يوجد تجار مطابقون.</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>التاجر</th>
                    <th>الهاتف</th>
                    <th class="num">المبيعات</th>
                    <th class="num">الخصومات</th>
                    <th class="num">المدفوع</th>
                    <th class="num">المتبقي عليه</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($merchants as $merchant) : ?>
                    <tr>
                        <td>
                            <?= View::e($merchant['name']) ?>
                            <?php if ((int) $merchant['is_active'] === 0) : ?>
                                <span class="badge off">موقوف</span>
                            <?php endif; ?>
                        </td>
                        <td class="num"><?= View::e($merchant['phone'] ?? '—') ?></td>
                        <td class="num"><?= View::money($merchant['total_sales']) ?></td>
                        <td class="num"><?= View::money($merchant['total_discount']) ?></td>
                        <td class="num"><?= View::money($merchant['total_paid']) ?></td>
                        <td class="money <?= $merchant['balance'] > 0 ? 'out' : 'in' ?>">
                            <?= View::money($merchant['balance']) ?>
                        </td>
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
