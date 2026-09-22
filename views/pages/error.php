<?php

use App\Auth;
use App\View;

/** @var string $title */
/** @var string $message */

?>
<div class="card">
    <div class="body" style="text-align:center;padding:50px 20px">
        <div style="font-size:44px">⚠️</div>
        <h2 style="margin:10px 0 6px"><?= View::e($title) ?></h2>
        <p class="muted"><?= View::e($message) ?></p>

        <div class="actions" style="justify-content:center">
            <a class="btn" href="<?= Auth::check() ? Auth::homePath() : '/login' ?>">العودة</a>
        </div>
    </div>
</div>
