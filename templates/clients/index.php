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
            <h2 class="text-lg font-semibold text-slate-900">Portefeuille clients</h2>
            <p class="text-sm text-slate-500 mt-0.5"><?= number_format($st['total'], 0, ',', ' ') ?> client(s) actifs</p>
        </div>
        <a href="/clients/create" class="btn btn-primary">+ Nouveau client</a>
    </div>
    <div class="stat-chips mt-4">
        <a href="/clients" class="stat-chip <?= ($f['status'] ?? '') === '' ? 'active' : '' ?>">
            <span class="num"><?= number_format($st['total'], 0, ',', ' ') ?></span><span class="lbl">Total</span>
        </a>
        <a href="<?= $queryBase(['status' => 'critical', 'page' => 1]) ?>" class="stat-chip chip-danger <?= ($f['status'] ?? '') === 'critical' ? 'active' : '' ?>">
            <span class="num"><?= $st['critical'] ?></span><span class="lbl">Critiques</span>
        </a>
        <a href="<?= $queryBase(['status' => 'warning', 'page' => 1]) ?>" class="stat-chip chip-warning <?= ($f['status'] ?? '') === 'warning' ? 'active' : '' ?>">
            <span class="num"><?= $st['warning'] ?></span><span class="lbl">Attention</span>
        </a>
        <a href="<?= $queryBase(['status' => 'drafts', 'page' => 1]) ?>" class="stat-chip chip-info <?= ($f['status'] ?? '') === 'drafts' ? 'active' : '' ?>">
            <span class="lbl">Brouillons</span>
        </a>
        <a href="<?= $queryBase(['status' => 'ok', 'page' => 1]) ?>" class="stat-chip chip-success <?= ($f['status'] ?? '') === 'ok' ? 'active' : '' ?>">
            <span class="lbl">Conformes</span>
        </a>
    </div>
</div>

<div class="card mb-4">
    <form method="get" action="/clients" class="card-body flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="text-xs font-medium text-slate-500 block mb-1">Recherche</label>
            <input type="search" name="q" value="<?= htmlspecialchars($f['q'] ?? '') ?>" placeholder="Nom, wilaya, n° cotisant…" class="input">
        </div>
        <div class="w-36">
            <label class="text-xs font-medium text-slate-500 block mb-1">Secteur</label>
            <select name="secteur" class="select">
                <option value="">Tous</option>
                <?php foreach ($st['secteurs'] as $sec): ?>
                <option value="<?= htmlspecialchars($sec) ?>" <?= ($f['secteur'] ?? '') === $sec ? 'selected' : '' ?>><?= htmlspecialchars($sec) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="w-32">
            <label class="text-xs font-medium text-slate-500 block mb-1">Wilaya</label>
            <input type="text" name="wilaya" value="<?= htmlspecialchars($f['wilaya'] ?? '') ?>" class="input" placeholder="Ex. Alger">
        </div>
        <div class="w-36">
            <label class="text-xs font-medium text-slate-500 block mb-1">Tri</label>
            <select name="sort" class="select">
                <option value="name" <?= ($f['sort'] ?? '') === 'name' ? 'selected' : '' ?>>Nom A→Z</option>
                <option value="issues" <?= ($f['sort'] ?? '') === 'issues' ? 'selected' : '' ?>>Problèmes</option>
                <option value="drafts" <?= ($f['sort'] ?? '') === 'drafts' ? 'selected' : '' ?>>Brouillons</option>
                <option value="secteur" <?= ($f['sort'] ?? '') === 'secteur' ? 'selected' : '' ?>>Secteur</option>
                <option value="wilaya" <?= ($f['sort'] ?? '') === 'wilaya' ? 'selected' : '' ?>>Wilaya</option>
            </select>
        </div>
        <div class="w-24">
            <label class="text-xs font-medium text-slate-500 block mb-1">Par page</label>
            <select name="per_page" class="select">
                <?php foreach ($perPageOptions as $n): ?>
                <option value="<?= $n ?>" <?= ($p['per_page'] ?? 50) == $n ? 'selected' : '' ?>><?= $n ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if (!empty($f['status'])): ?>
        <input type="hidden" name="status" value="<?= htmlspecialchars($f['status']) ?>">
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <?php if (array_filter($f, fn ($v, $k) => $v !== '' && !in_array($k, ['sort', 'page', 'per_page'], true), ARRAY_FILTER_USE_BOTH)): ?>
        <a href="/clients" class="btn btn-secondary">Réinitialiser</a>
        <?php endif; ?>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="table-scroll">
        <table class="data-table clients-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Secteur</th>
                    <th>Wilaya</th>
                    <th>Régime</th>
                    <th class="text-center">Alertes</th>
                    <th class="text-center">Brouillons</th>
                    <th class="text-center">Docs</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clients)): ?>
                <tr><td colspan="8" class="text-center py-12 text-slate-400">Aucun client ne correspond aux filtres.</td></tr>
                <?php else: foreach ($clients as $c):
                    $level = $c['status_level'] ?? 'ok';
                ?>
                <tr class="client-row client-row-<?= $level ?>">
                    <td>
                        <a href="/clients/<?= $c['id'] ?>" class="font-medium text-slate-900 hover:text-accent"><?= htmlspecialchars($c['raison_sociale']) ?></a>
                        <?php if ($c['numero_cotisant']): ?>
                        <span class="block text-xs text-slate-400 font-mono mt-0.5"><?= htmlspecialchars($c['numero_cotisant']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-sector"><?= htmlspecialchars($c['secteur']) ?></span></td>
                    <td class="text-slate-600"><?= htmlspecialchars($c['wilaya'] ?? '—') ?></td>
                    <td class="text-xs text-slate-500"><?= htmlspecialchars($c['cnas_regime']) ?></td>
                    <td class="text-center">
                        <?php if ($c['critical_count']): ?><span class="badge badge-danger"><?= $c['critical_count'] ?></span><?php endif; ?>
                        <?php if ($c['warning_count']): ?><span class="badge badge-warning ml-1"><?= $c['warning_count'] ?></span><?php endif; ?>
                        <?php if (!$c['critical_count'] && !$c['warning_count']): ?><span class="text-slate-300">—</span><?php endif; ?>
                    </td>
                    <td class="text-center"><?= $c['draft_count'] ? '<span class="badge badge-info">' . $c['draft_count'] . '</span>' : '<span class="text-slate-300">—</span>' ?></td>
                    <td class="text-center text-xs text-slate-500"><?= (int) $c['doc_count'] ?></td>
                    <td class="text-right whitespace-nowrap">
                        <a href="/clients/<?= $c['id'] ?>/dossier" class="text-xs text-accent hover:underline mr-2">GED</a>
                        <a href="/clients/<?= $c['id'] ?>" class="text-xs font-medium text-accent hover:underline">Fiche</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($p['pages'] > 1): ?>
    <div class="pagination-bar">
        <p class="text-xs text-slate-500">
            <?= (($p['page'] - 1) * $p['per_page']) + 1 ?>–<?= min($p['page'] * $p['per_page'], $p['total']) ?>
            sur <?= number_format($p['total'], 0, ',', ' ') ?>
        </p>
        <div class="flex gap-1">
            <?php if ($p['page'] > 1): ?>
            <a href="<?= $queryBase(['page' => $p['page'] - 1]) ?>" class="btn btn-secondary btn-sm">← Préc.</a>
            <?php endif; ?>
            <?php
            $start = max(1, $p['page'] - 2);
            $end = min($p['pages'], $p['page'] + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
            <a href="<?= $queryBase(['page' => $i]) ?>" class="btn btn-sm <?= $i === $p['page'] ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($p['page'] < $p['pages']): ?>
            <a href="<?= $queryBase(['page' => $p['page'] + 1]) ?>" class="btn btn-secondary btn-sm">Suiv. →</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
