<?php

use App\Auth;
use App\Form;
use App\Money;
use App\View;

/** @var array $jobTypes */
/** @var \App\FormState $form */

?>
<div class="card">
    <h2>أسعار البيع</h2>

    <div class="body">
        <p class="muted">
            هنا تُحدَّث أسعار الوحدة المقترحة. السعر الجديد يظهر تلقائياً عند تسجيل بيع جديد،
            ولا يغيّر أي عملية بيع سابقة.
            <?php if (! Auth::isAdmin()) : ?>
                <br>إضافة أو حذف أنواع العمل من صلاحية المدير.
            <?php endif; ?>
        </p>
    </div>

    <?php if ($jobTypes === []) : ?>
        <div class="empty">لا توجد أنواع عمل بعد.</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>نوع العمل</th>
                    <th>الوحدة</th>
                    <th class="num">السعر الحالي</th>
                    <th>السعر الجديد</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($jobTypes as $jobType) : ?>
                    <?php
                    $id     = (int) $jobType['id'];
                    $errors = $form->errors('pricing-' . $id);
                    ?>
                    <tr>
                        <td>
                            <?= View::e($jobType['name']) ?>
                            <?php if ((int) $jobType['is_active'] === 0) : ?>
                                <span class="badge off">غير مُفعّل</span>
                            <?php endif; ?>
                        </td>
                        <td><?= View::e($jobType['unit']) ?></td>
                        <td class="money"><?= View::money((int) $jobType['default_unit_price']) ?></td>
                        <td colspan="2">
                            <?= Form::open('/pricing/update', ['class' => 'filters']) ?>
                            <?= Form::hidden('id', $id) ?>

                            <div class="field" style="min-width:140px;margin:0">
                                <input type="text" name="default_unit_price" inputmode="decimal"
                                       class="<?= isset($errors['default_unit_price']) ? 'error' : '' ?>"
                                       value="<?= View::e(Money::format((int) $jobType['default_unit_price'])) ?>">
                                <?php if (isset($errors['default_unit_price'])) : ?>
                                    <div class="msg"><?= View::e($errors['default_unit_price']) ?></div>
                                <?php endif; ?>
                            </div>

                            <button class="btn small" type="submit">تحديث السعر</button>

                            <?= Form::close() ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
