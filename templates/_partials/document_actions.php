<?php
/** @var array $d */
/** @var int|null $clientId */
$clientId = $clientId ?? ($d['client_id'] ?? null);
?>
<div class="flex flex-wrap gap-1 justify-end items-center">
    <a href="/documents/<?= $d['id'] ?>/download" target="_blank" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('common.view')) ?></a>
    <a href="/documents/<?= $d['id'] ?>" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('common.edit')) ?></a>
    <form method="post" action="/documents/<?= $d['id'] ?>/delete" class="inline" onsubmit="return confirm('<?= addslashes(__('common.confirm_delete_document')) ?>');">
        <button type="submit" class="btn btn-ghost btn-sm text-red-600 hover:text-red-700"><?= htmlspecialchars(__('common.delete')) ?></button>
    </form>
</div>
