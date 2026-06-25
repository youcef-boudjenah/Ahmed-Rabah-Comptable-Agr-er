<?php
$p = $pagination;
$st = $stats;
$f = $filters;
$queryBase = function (array $overrides = []) use ($f) {
    return '?' . http_build_query(array_filter(array_merge($f, $overrides), fn ($v) => $v !== '' && $v !== null));
};
?>
<div class="clients-hub mb-6">
    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900"><?= htmlspecialchars(__('clients.title')) ?></h2>
            <p class="text-sm text-slate-500 mt-0.5"><?= htmlspecialchars(__('clients.active_count', ['n' => number_format($st['total'], 0, ',', ' ')])) ?></p>
        </div>
        <a href="/clients/create" class="btn btn-primary"><?= htmlspecialchars(__('clients.new')) ?></a>
    </div>
    <div class="stat-chips mt-4">
        <a href="/clients" class="stat-chip <?= ($f['status'] ?? '') === '' ? 'active' : '' ?>">
            <span class="num"><?= number_format($st['total'], 0, ',', ' ') ?></span><span class="lbl"><?= htmlspecialchars(__('common.total')) ?></span>
        </a>
        <a href="<?= $queryBase(['status' => 'critical', 'page' => 1]) ?>" class="stat-chip chip-danger <?= ($f['status'] ?? '') === 'critical' ? 'active' : '' ?>">
            <span class="num"><?= $st['critical'] ?></span><span class="lbl"><?= htmlspecialchars(__('clients.critical')) ?></span>
        </a>
        <a href="<?= $queryBase(['status' => 'warning', 'page' => 1]) ?>" class="stat-chip chip-warning <?= ($f['status'] ?? '') === 'warning' ? 'active' : '' ?>">
            <span class="num"><?= $st['warning'] ?></span><span class="lbl"><?= htmlspecialchars(__('clients.warning')) ?></span>
        </a>
        <a href="<?= $queryBase(['status' => 'drafts', 'page' => 1]) ?>" class="stat-chip chip-info <?= ($f['status'] ?? '') === 'drafts' ? 'active' : '' ?>">
            <span class="lbl"><?= htmlspecialchars(__('clients.drafts')) ?></span>
        </a>
        <a href="<?= $queryBase(['status' => 'ok', 'page' => 1]) ?>" class="stat-chip chip-success <?= ($f['status'] ?? '') === 'ok' ? 'active' : '' ?>">
            <span class="lbl"><?= htmlspecialchars(__('clients.compliant')) ?></span>
        </a>
        <?php if ($st['archived'] > 0): ?>
        <a href="<?= $queryBase(['status' => 'archived', 'page' => 1]) ?>" class="stat-chip <?= ($f['status'] ?? '') === 'archived' ? 'active' : '' ?>">
            <span class="num"><?= $st['archived'] ?></span><span class="lbl"><?= htmlspecialchars(__('clients.archived')) ?></span>
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <form method="get" action="/clients" class="card-body flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="text-xs font-medium text-slate-500 block mb-1"><?= htmlspecialchars(__('clients.search')) ?></label>
            <input type="search" name="q" value="<?= htmlspecialchars($f['q'] ?? '') ?>" placeholder="<?= htmlspecialchars(__('clients.search_placeholder')) ?>" class="input">
        </div>
        <div class="w-36">
            <label class="text-xs font-medium text-slate-500 block mb-1"><?= htmlspecialchars(__('clients.secteur')) ?></label>
            <select name="secteur" class="select">
                <option value=""><?= htmlspecialchars(__('common.all')) ?></option>
                <?php foreach ($st['secteurs'] as $sec): ?>
                <option value="<?= htmlspecialchars($sec) ?>" <?= ($f['secteur'] ?? '') === $sec ? 'selected' : '' ?>><?= htmlspecialchars($sec) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="w-32">
            <label class="text-xs font-medium text-slate-500 block mb-1"><?= htmlspecialchars(__('clients.wilaya')) ?></label>
            <input type="text" name="wilaya" value="<?= htmlspecialchars($f['wilaya'] ?? '') ?>" class="input" placeholder="<?= htmlspecialchars(__('clients.wilaya_placeholder')) ?>">
        </div>
        <div class="w-36">
            <label class="text-xs font-medium text-slate-500 block mb-1"><?= htmlspecialchars(__('common.sort')) ?></label>
            <select name="sort" class="select">
                <option value="name" <?= ($f['sort'] ?? '') === 'name' ? 'selected' : '' ?>><?= htmlspecialchars(__('clients.sort_name')) ?></option>
                <option value="issues" <?= ($f['sort'] ?? '') === 'issues' ? 'selected' : '' ?>><?= htmlspecialchars(__('clients.sort_issues')) ?></option>
                <option value="drafts" <?= ($f['sort'] ?? '') === 'drafts' ? 'selected' : '' ?>><?= htmlspecialchars(__('clients.sort_drafts')) ?></option>
                <option value="secteur" <?= ($f['sort'] ?? '') === 'secteur' ? 'selected' : '' ?>><?= htmlspecialchars(__('clients.sort_sector')) ?></option>
                <option value="wilaya" <?= ($f['sort'] ?? '') === 'wilaya' ? 'selected' : '' ?>><?= htmlspecialchars(__('clients.sort_wilaya')) ?></option>
            </select>
        </div>
        <div class="w-24">
            <label class="text-xs font-medium text-slate-500 block mb-1"><?= htmlspecialchars(__('common.per_page')) ?></label>
            <select name="per_page" class="select">
                <?php foreach ($perPageOptions as $n): ?>
                <option value="<?= $n ?>" <?= ($p['per_page'] ?? 50) == $n ? 'selected' : '' ?>><?= $n ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if (!empty($f['status'])): ?>
        <input type="hidden" name="status" value="<?= htmlspecialchars($f['status']) ?>">
        <?php endif; ?>
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('common.filter')) ?></button>
        <?php if (array_filter($f, fn ($v, $k) => $v !== '' && !in_array($k, ['sort', 'page', 'per_page'], true), ARRAY_FILTER_USE_BOTH)): ?>
        <a href="/clients" class="btn btn-secondary"><?= htmlspecialchars(__('common.reset')) ?></a>
        <?php endif; ?>
    </form>
</div>

<div class="card overflow-hidden">
    <form method="post" action="/clients/bulk" id="clients-bulk-form">
        <input type="hidden" name="return_query" value="<?= htmlspecialchars(http_build_query(array_filter($f, fn ($v) => $v !== '' && $v !== null))) ?>">
        <div class="px-4 py-2 border-b border-slate-100 flex flex-wrap gap-2 items-center bg-slate-50/80">
            <span class="text-xs text-slate-500"><?= htmlspecialchars(__('common.selection')) ?></span>
            <select name="bulk_action" class="select text-xs py-1" required>
                <option value=""><?= htmlspecialchars(__('common.action_placeholder')) ?></option>
                <?php if ($archived = ($f['status'] ?? '') === 'archived'): ?>
                <option value="restore"><?= htmlspecialchars(__('common.restore')) ?></option>
                <?php else: ?>
                <option value="archive"><?= htmlspecialchars(__('common.archive')) ?></option>
                <?php endif; ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm" onclick="return document.querySelectorAll('.client-check:checked').length > 0;"><?= htmlspecialchars(__('common.apply')) ?></button>
        </div>
    <div class="table-scroll">
        <table class="data-table clients-table">
            <thead>
                <tr>
                    <th width="32"><input type="checkbox" title="<?= htmlspecialchars(__('common.select_all')) ?>" onclick="document.querySelectorAll('.client-check').forEach(c=>c.checked=this.checked)"></th>
                    <th><?= htmlspecialchars(__('common.client')) ?></th>
                    <th><?= htmlspecialchars(__('clients.secteur')) ?></th>
                    <th><?= htmlspecialchars(__('clients.wilaya')) ?></th>
                    <th><?= htmlspecialchars(__('common.regime')) ?></th>
                    <th class="text-center"><?= htmlspecialchars(__('clients.alerts')) ?></th>
                    <th class="text-center"><?= htmlspecialchars(__('clients.drafts_col')) ?></th>
                    <th class="text-center"><?= htmlspecialchars(__('clients.docs')) ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clients)): ?>
                <tr><td colspan="9" class="text-center py-12 text-slate-400"><?= htmlspecialchars(__('clients.no_match')) ?></td></tr>
                <?php else: foreach ($clients as $c):
                    $level = $c['status_level'] ?? 'ok';
                ?>
                <tr class="client-row client-row-<?= $level ?>">
                    <td><input type="checkbox" class="client-check" name="ids[]" value="<?= $c['id'] ?>" form="clients-bulk-form"></td>
                    <td>
                        <a href="/clients/<?= $c['id'] ?>" class="font-medium text-slate-900 hover:text-accent"><?= htmlspecialchars($c['raison_sociale']) ?></a>
                        <?php if ($c['numero_cotisant']): ?>
                        <span class="block text-xs text-slate-400 font-mono mt-0.5"><?= htmlspecialchars($c['numero_cotisant']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-sector"><?= htmlspecialchars($c['secteur']) ?></span></td>
                    <td class="text-slate-600"><?= htmlspecialchars($c['wilaya'] ?? __('common.unassigned')) ?></td>
                    <td class="text-xs text-slate-500"><?= htmlspecialchars($c['cnas_regime']) ?></td>
                    <td class="text-center">
                        <?php if ($c['critical_count']): ?><span class="badge badge-danger"><?= $c['critical_count'] ?></span><?php endif; ?>
                        <?php if ($c['warning_count']): ?><span class="badge badge-warning ml-1"><?= $c['warning_count'] ?></span><?php endif; ?>
                        <?php if (!$c['critical_count'] && !$c['warning_count']): ?><span class="text-slate-300"><?= htmlspecialchars(__('common.unassigned')) ?></span><?php endif; ?>
                    </td>
                    <td class="text-center"><?= $c['draft_count'] ? '<span class="badge badge-info">' . $c['draft_count'] . '</span>' : '<span class="text-slate-300">' . htmlspecialchars(__('common.unassigned')) . '</span>' ?></td>
                    <td class="text-center text-xs text-slate-500"><?= (int) $c['doc_count'] ?></td>
                    <td><?php require ROOT_PATH . '/templates/_partials/client_row_actions.php'; ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    </form>

    <?php if ($p['pages'] > 1): ?>
    <div class="pagination-bar">
        <p class="text-xs text-slate-500">
            <?= (($p['page'] - 1) * $p['per_page']) + 1 ?>–<?= min($p['page'] * $p['per_page'], $p['total']) ?>
            <?= htmlspecialchars(__('common.on')) ?> <?= number_format($p['total'], 0, ',', ' ') ?>
        </p>
        <div class="flex gap-1">
            <?php if ($p['page'] > 1): ?>
            <a href="<?= $queryBase(['page' => $p['page'] - 1]) ?>" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('common.prev')) ?></a>
            <?php endif; ?>
            <?php
            $start = max(1, $p['page'] - 2);
            $end = min($p['pages'], $p['page'] + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
            <a href="<?= $queryBase(['page' => $i]) ?>" class="btn btn-sm <?= $i === $p['page'] ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($p['page'] < $p['pages']): ?>
            <a href="<?= $queryBase(['page' => $p['page'] + 1]) ?>" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('common.next')) ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
