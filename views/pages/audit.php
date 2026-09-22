<?php

use App\View;

/** @var array $entries */

?>
<div class="card">
    <h2>سجل العمليات — آخر 300 حركة</h2>

    <?php if ($entries === []) : ?>
        <div class="empty">لا توجد حركات مسجلة بعد.</div>
    <?php else : ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>الوقت</th>
                    <th>المستخدم</th>
                    <th>الإجراء</th>
                    <th>النوع</th>
                    <th>التفاصيل</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $entry) : ?>
                    <tr>
                        <td class="num"><?= View::dateTime($entry['created_at']) ?></td>
                        <td><?= View::e($entry['user_name'] ?? '—') ?></td>
                        <td>
                            <span class="badge <?= $entry['action'] === 'حذف' ? 'off' : '' ?>">
                                <?= View::e($entry['action']) ?>
                            </span>
                        </td>
                        <td><?= View::e($entry['entity']) ?></td>
                        <td><?= View::e($entry['summary']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
