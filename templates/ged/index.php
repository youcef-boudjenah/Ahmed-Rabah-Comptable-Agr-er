<div class="page-intro mb-6">
    <p class="eyebrow"><?= htmlspecialchars(__('ged.eyebrow_archives')) ?></p>
    <h2><?= htmlspecialchars(__('ged.title')) ?></h2>
    <p><?= htmlspecialchars(__('ged.subtitle')) ?></p>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-tile neutral"><p class="label"><?= htmlspecialchars(__('ged.stat_total')) ?></p><p class="value"><?= $stats['total'] ?></p></div>
    <div class="stat-tile warning"><p class="label"><?= htmlspecialchars(__('ged.stat_pending')) ?></p><p class="value"><?= $stats['a_traiter'] ?></p></div>
    <div class="stat-tile info"><p class="label"><?= htmlspecialchars(__('ged.stat_done')) ?></p><p class="value"><?= $stats['traite'] ?></p></div>
    <div class="stat-tile neutral"><p class="label"><?= htmlspecialchars(__('ged.stat_folders')) ?></p><p class="value"><?= count($clients) ?></p></div>
</div>

<form method="get" class="mb-6 flex gap-2">
    <input type="search" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="<?= htmlspecialchars(__('ged.search_placeholder')) ?>"
           class="input flex-1">
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('common.search')) ?></button>
    <?php if ($query !== ''): ?><a href="/ged" class="btn btn-secondary"><?= htmlspecialchars(__('common.clear')) ?></a><?php endif; ?>
</form>

<?php if ($query !== ''): ?>
<div class="card mb-6 overflow-hidden">
    <div class="card-header"><h3><?= htmlspecialchars(__('common.results_for', ['q' => $query])) ?></h3></div>
    <?php if (empty($results)): ?>
    <p class="p-6 text-slate-400 text-sm"><?= htmlspecialchars(__('ged.no_document')) ?></p>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th><?= htmlspecialchars(__('common.document')) ?></th><th><?= htmlspecialchars(__('common.client')) ?></th><th><?= htmlspecialchars(__('common.category')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($results as $d): ?>
        <tr>
            <td class="font-medium text-sm"><?= htmlspecialchars($d['title'] ?? $d['original_name']) ?></td>
            <td class="text-sm"><?= htmlspecialchars($d['raison_sociale'] ?? __('common.unassigned')) ?></td>
            <td><span class="badge badge-neutral"><?= $d['category'] ?? 'divers' ?></span></td>
            <td><?php require ROOT_PATH . '/templates/_partials/document_actions.php'; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($recent) && $query === ''): ?>
<div class="card mb-8 overflow-hidden">
    <div class="card-header">
        <h3><?= htmlspecialchars(__('ged.recent')) ?></h3>
        <a href="/documents" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('ged.ocr_all')) ?></a>
    </div>
    <table class="data-table">
        <thead><tr><th><?= htmlspecialchars(__('common.document')) ?></th><th><?= htmlspecialchars(__('common.client')) ?></th><th><?= htmlspecialchars(__('common.status')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recent as $d): ?>
        <tr>
            <td>
                <p class="font-medium text-sm"><?= htmlspecialchars($d['title'] ?? $d['original_name']) ?></p>
                <p class="text-xs text-slate-400"><?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></p>
            </td>
            <td class="text-sm">
                <?php if ($d['client_id']): ?>
                <a href="/clients/<?= $d['client_id'] ?>/dossier" class="text-accent-700 hover:underline"><?= htmlspecialchars($d['raison_sociale'] ?? '') ?></a>
                <?php else: ?><?= htmlspecialchars(__('common.unassigned')) ?><?php endif; ?>
            </td>
            <td><span class="badge badge-neutral text-xs"><?= $d['ged_status'] ?? $d['status'] ?></span></td>
            <td><?php require ROOT_PATH . '/templates/_partials/document_actions.php'; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<h2 class="text-base font-semibold text-slate-900 mb-4"><?= htmlspecialchars(__('ged.client_folders')) ?></h2>
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    <?php foreach ($clients as $c): ?>
    <a href="/clients/<?= $c['id'] ?>/dossier" class="card p-5 hover:border-accent-400 transition group">
        <div class="flex items-start gap-4">
            <div class="w-11 h-11 rounded-md bg-slate-900 flex items-center justify-center text-white font-bold shrink-0">
                <?= strtoupper(substr($c['raison_sociale'], 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-slate-900 group-hover:text-accent-700 truncate"><?= htmlspecialchars($c['raison_sociale']) ?></h3>
                <p class="text-xs text-slate-400 mt-1"><?= $c['secteur'] ?> — <?= htmlspecialchars(__('common.docs_count', ['n' => (int)$c['doc_count']])) ?></p>
                <?php if ((int)$c['a_traiter'] > 0): ?>
                <span class="badge badge-warning mt-2"><?= htmlspecialchars(__('ged.pending_count', ['n' => $c['a_traiter']])) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
