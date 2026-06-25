<?php /** @var array $c */ $archived = ($f['status'] ?? '') === 'archived'; ?>
<div class="flex flex-wrap gap-1 justify-end items-center">
    <a href="/clients/<?= $c['id'] ?>" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('common.fiche')) ?></a>
    <a href="/clients/<?= $c['id'] ?>/edit" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('common.edit')) ?></a>
    <a href="/clients/<?= $c['id'] ?>/dossier" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('nav.ged')) ?></a>
    <?php if ($archived): ?>
    <form method="post" action="/clients/<?= $c['id'] ?>/restore" class="inline">
        <button type="submit" class="btn btn-ghost btn-sm text-accent-700"><?= htmlspecialchars(__('common.restore')) ?></button>
    </form>
    <?php else: ?>
    <form method="post" action="/clients/<?= $c['id'] ?>/duplicate" class="inline">
        <button type="submit" class="btn btn-ghost btn-sm" title="<?= htmlspecialchars(__('clients.duplicate_title')) ?>"><?= htmlspecialchars(__('common.copy')) ?></button>
    </form>
    <form method="post" action="/clients/<?= $c['id'] ?>/archive" class="inline" onsubmit="return confirm('<?= addslashes(__('common.confirm_archive_client')) ?>');">
        <button type="submit" class="btn btn-ghost btn-sm text-red-600"><?= htmlspecialchars(__('common.archive')) ?></button>
    </form>
    <?php endif; ?>
</div>
