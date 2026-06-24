<?php
$p = $production;
$stats = $p['stats'];
$months = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$year = (int)$p['year'];
$month = (int)$p['month'];
$f = $p['filters'];
$queryBase = http_build_query(array_filter(['year' => $year, 'month' => $month, 'status' => $f['statusFilter'], 'type' => $f['typeFilter'], 'q' => $f['q']]));
?>
<?php if (!empty($_GET['run'])):
    $run = \App\Modules\Automation\AutomationPipeline::findRun((int)$_GET['run'], \App\Core\Auth::cabinetId());
    if ($run): require ROOT_PATH . '/templates/automation/_run_report.php'; endif;
endif; ?>

<div class="page-intro flex flex-wrap justify-between items-start gap-4">
    <div>
        <p class="eyebrow">Pilotage cabinet</p>
        <h2>Production mensuelle</h2>
        <p>Toutes les obligations du mois — statut par client, traitement en masse, relances.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="/entries/payroll/import?year=<?= $year ?>&month=<?= $month ?>&redirect=/production" class="btn btn-secondary btn-sm">① Import paie</a>
        <form method="post" action="/production/process" class="inline" onsubmit="return confirm('Recalculer et générer les bordereaux ?');">
            <input type="hidden" name="year" value="<?= $year ?>">
            <input type="hidden" name="month" value="<?= $month ?>">
            <input type="hidden" name="period_label" value="<?= htmlspecialchars($p['month_label']) ?>">
            <button type="submit" class="btn btn-secondary btn-sm">② Traiter cabinet</button>
        </form>
        <?php if ($stats['draft_ready'] > 0): ?>
        <form method="post" action="/production/approve-drafts" class="inline" onsubmit="return confirm('Approuver <?= (int)$stats['draft_ready'] ?> brouillon(s) ?');">
            <input type="hidden" name="year" value="<?= $year ?>">
            <input type="hidden" name="month" value="<?= $month ?>">
            <button type="submit" class="btn btn-primary btn-sm">③ Approuver <?= (int)$stats['draft_ready'] ?> brouillon(s)</button>
        </form>
        <?php endif; ?>
        <?php if ($stats['missing_data'] + $stats['overdue'] > 0): ?>
        <a href="/production/export-relances?year=<?= $year ?>&month=<?= $month ?>" class="btn btn-secondary btn-sm">④ Export relances CSV</a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-5">
    <div class="card-body">
        <div class="flex flex-wrap justify-between gap-3 items-center mb-2">
            <p class="text-sm font-medium text-slate-800">Avancement dépôts — <?= htmlspecialchars($p['month_label']) ?></p>
            <span class="text-sm font-bold text-teal-700"><?= $stats['completion_pct'] ?>% déposé</span>
        </div>
        <div class="progress-bar h-2"><span style="width:<?= $stats['completion_pct'] ?>%; background: linear-gradient(90deg, var(--accent), #14b8a6);"></span></div>
        <div class="flex flex-wrap gap-2 mt-3">
            <a href="?year=<?= $year ?>&month=<?= $month ?>&status=missing_data" class="badge badge-warning hover:opacity-90">Manquants (<?= $stats['missing_data'] ?>)</a>
            <a href="?year=<?= $year ?>&month=<?= $month ?>&status=draft_ready" class="badge badge-warning hover:opacity-90">Brouillons (<?= $stats['draft_ready'] ?>)</a>
            <a href="?year=<?= $year ?>&month=<?= $month ?>&status=approved" class="badge badge-info hover:opacity-90">À déposer (<?= $stats['approved'] ?>)</a>
            <a href="?year=<?= $year ?>&month=<?= $month ?>&status=submitted" class="badge badge-success hover:opacity-90">Déposés (<?= $stats['submitted'] ?>)</a>
            <?php if ($stats['without_contact'] > 0): ?>
            <span class="badge badge-danger"><?= $stats['without_contact'] ?> sans contact relance</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<form method="get" class="card mb-5">
    <div class="card-body flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Période déclarée</label>
            <div class="flex gap-2">
                <select name="month" class="select w-auto">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $month === $m ? 'selected' : '' ?>><?= $months[$m] ?></option>
                    <?php endfor; ?>
                </select>
                <select name="year" class="select w-auto">
                    <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 3; $y--): ?>
                    <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <div class="flex-1 min-w-[160px]">
            <label class="block text-xs font-medium text-slate-600 mb-1">Recherche client</label>
            <input type="search" name="q" value="<?= htmlspecialchars($f['q']) ?>" placeholder="Nom client…" class="input">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Type</label>
            <select name="type" class="select w-auto">
                <option value="">Tous</option>
                <?php foreach (['CNAS_MENSUELLE','CNAS_TRIMESTRIELLE','CACOBATPH','G50'] as $t): ?>
                <option value="<?= $t ?>" <?= $f['typeFilter'] === $t ? 'selected' : '' ?>><?= \App\Modules\Automation\DeadlineService::typeLabel($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Statut</label>
            <select name="status" class="select w-auto">
                <option value="">Tous</option>
                <?php foreach (['missing_data'=>'Données manquantes','draft_ready'=>'Brouillon prêt','approved'=>'Approuvée','submitted'=>'Déposée','overdue'=>'En retard'] as $k=>$lbl): ?>
                <option value="<?= $k ?>" <?= $f['statusFilter'] === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary btn-sm">Filtrer</button>
        <a href="/production?year=<?= $year ?>&month=<?= $month ?>" class="btn btn-ghost btn-sm">Reset</a>
    </div>
</form>

<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
    <div class="stat-tile neutral"><p class="label">Obligations</p><p class="value"><?= $stats['total_obligations'] ?></p></div>
    <div class="stat-tile danger"><p class="label">En retard</p><p class="value"><?= $stats['overdue'] ?></p></div>
    <div class="stat-tile warning"><p class="label">Données manquantes</p><p class="value"><?= $stats['missing_data'] ?></p></div>
    <div class="stat-tile info"><p class="label">Brouillons prêts</p><p class="value"><?= $stats['draft_ready'] ?></p></div>
    <div class="stat-tile neutral"><p class="label">Montant estimé</p><p class="value text-lg"><?= number_format($stats['total_amount'], 0, ',', ' ') ?> <span class="text-sm font-normal">DA</span></p></div>
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <h3><?= htmlspecialchars($p['month_label']) ?> — <?= count($p['rows']) ?> ligne(s)</h3>
        <span class="text-xs text-slate-500"><?= $stats['total_clients'] ?> clients actifs</span>
    </div>
    <div class="table-scroll">
        <table class="data-table production-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Obligation</th>
                    <th>Échéance dépôt</th>
                    <th>Statut</th>
                    <th class="text-right">Montant</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($p['rows'])): ?>
                <tr><td colspan="6" class="text-center py-12 text-slate-500">Aucune obligation pour cette période.</td></tr>
                <?php else: foreach ($p['rows'] as $row):
                    $rel = \App\Modules\Relances\RelanceService::linksFor($row);
                ?>
                <tr class="<?= $row['status'] === 'overdue' ? 'client-row-critical' : ($row['status'] === 'missing_data' ? 'client-row-warning' : '') ?>">
                    <td>
                        <a href="/clients/<?= $row['client_id'] ?>" class="font-medium text-slate-800 hover:text-teal-700"><?= htmlspecialchars($row['raison_sociale']) ?></a>
                        <span class="block text-xs text-slate-400"><?= htmlspecialchars($row['secteur']) ?></span>
                    </td>
                    <td>
                        <span class="font-mono text-xs text-teal-800"><?= htmlspecialchars($row['type_label']) ?></span>
                        <span class="block text-xs text-slate-500"><?= htmlspecialchars($row['period_label']) ?></span>
                    </td>
                    <td>
                        <span class="font-mono text-sm"><?= htmlspecialchars($row['due_label']) ?></span>
                        <?php if ($row['days_left'] < 0): ?>
                        <span class="badge badge-danger ml-1"><?= abs($row['days_left']) ?>j retard</span>
                        <?php elseif ($row['days_left'] <= 7): ?>
                        <span class="badge badge-warning ml-1">J-<?= $row['days_left'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $row['status_badge'] ?>"><?= htmlspecialchars($row['status_label']) ?></span></td>
                    <td class="text-right font-mono"><?= $row['amount'] !== null ? number_format((float)$row['amount'], 0, ',', ' ') . ' DA' : '—' ?></td>
                    <td>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <a href="<?= htmlspecialchars($row['action_url']) ?>" class="btn btn-secondary btn-sm">Ouvrir</a>
                            <?php if (in_array($row['status'], ['missing_data', 'overdue'], true)): ?>
                            <?php require ROOT_PATH . '/templates/_partials/relance_buttons.php'; ?>
                            <?php endif; ?>
                            <?php if ($row['declaration_id'] && in_array($row['status'], ['draft_ready', 'approved'], true)): ?>
                            <a href="/declarations/<?= $row['declaration_id'] ?>/print" target="_blank" class="btn btn-ghost btn-sm">Bordereau</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
document.querySelectorAll('.copy-relance').forEach(btn => {
    btn.addEventListener('click', () => {
        navigator.clipboard.writeText(btn.dataset.message || '');
        btn.textContent = 'Copié';
        setTimeout(() => { btn.textContent = 'Copier'; }, 1500);
    });
});
</script>
