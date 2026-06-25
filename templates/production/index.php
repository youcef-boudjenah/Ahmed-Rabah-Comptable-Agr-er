<?php
$p = $production;
$stats = $p['stats'];
$year = (int)$p['year'];
$month = (int)$p['month'];
$f = $p['filters'];
$queryBase = http_build_query(array_filter(['year' => $year, 'month' => $month, 'status' => $f['statusFilter'], 'type' => $f['typeFilter'], 'q' => $f['q']]));
$statusLabels = [
    'missing_data' => __('production.status_missing'),
    'draft_ready' => __('production.status_draft'),
    'approved' => __('production.status_approved'),
    'submitted' => __('production.status_submitted'),
    'overdue' => __('production.status_overdue'),
];
?>
<?php if (!empty($_GET['run']) || !empty($highlightRun)):
    $run = $highlightRun ?? \App\Modules\Automation\AutomationPipeline::findRun((int)$_GET['run'], \App\Core\Auth::cabinetId());
    if ($run): require ROOT_PATH . '/templates/automation/_run_report.php'; endif;
endif; ?>

<div class="page-intro flex flex-wrap justify-between items-start gap-4">
    <div>
        <p class="eyebrow"><?= htmlspecialchars(__('production.eyebrow')) ?></p>
        <h2><?= htmlspecialchars(__('production.title')) ?></h2>
        <p><?= htmlspecialchars(__('production.subtitle')) ?></p>
    </div>
    <div class="workflow-strip">
        <a href="/entries/payroll/import?year=<?= $year ?>&month=<?= $month ?>&redirect=/production" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('production.import_payroll')) ?></a>
        <form method="post" action="/production/process" class="inline" onsubmit="return confirm(<?= json_encode(__('common.process_confirm')) ?>);">
            <input type="hidden" name="year" value="<?= $year ?>">
            <input type="hidden" name="month" value="<?= $month ?>">
            <input type="hidden" name="period_label" value="<?= htmlspecialchars($p['month_label']) ?>">
            <button type="submit" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('production.process_cabinet')) ?></button>
        </form>
        <?php if ($stats['draft_ready'] > 0): ?>
        <form method="post" action="/production/approve-drafts" class="inline" onsubmit="return confirm(<?= json_encode(__('common.approve_confirm', ['n' => (int)$stats['draft_ready']])) ?>);">
            <input type="hidden" name="year" value="<?= $year ?>">
            <input type="hidden" name="month" value="<?= $month ?>">
            <button type="submit" class="btn btn-primary btn-sm"><?= htmlspecialchars(__('common.approve_drafts', ['n' => (int)$stats['draft_ready']])) ?></button>
        </form>
        <?php endif; ?>
        <?php if ($stats['missing_data'] + $stats['overdue'] > 0): ?>
        <a href="/production/export-relances?year=<?= $year ?>&month=<?= $month ?>" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('production.export_relances')) ?></a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-5">
    <div class="card-body">
        <div class="flex flex-wrap justify-between gap-3 items-center mb-2">
            <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars(__('production.deposit_progress')) ?> — <?= htmlspecialchars($p['month_label']) ?></p>
            <span class="text-sm font-bold text-accent-700"><?= htmlspecialchars(__('common.deposited_pct', ['n' => $stats['completion_pct']])) ?></span>
        </div>
        <div class="progress-bar h-2"><span style="width:<?= $stats['completion_pct'] ?>%; background: linear-gradient(90deg, var(--accent), #e0c56a);"></span></div>
        <div class="flex flex-wrap gap-2 mt-3">
            <a href="?year=<?= $year ?>&month=<?= $month ?>&status=missing_data" class="badge badge-warning hover:opacity-90"><?= htmlspecialchars(__('common.missing_count', ['n' => $stats['missing_data']])) ?></a>
            <a href="?year=<?= $year ?>&month=<?= $month ?>&status=draft_ready" class="badge badge-warning hover:opacity-90"><?= htmlspecialchars(__('common.drafts_count', ['n' => $stats['draft_ready']])) ?></a>
            <a href="?year=<?= $year ?>&month=<?= $month ?>&status=approved" class="badge badge-info hover:opacity-90"><?= htmlspecialchars(__('common.to_deposit_count', ['n' => $stats['approved']])) ?></a>
            <a href="?year=<?= $year ?>&month=<?= $month ?>&status=submitted" class="badge badge-success hover:opacity-90"><?= htmlspecialchars(__('common.deposited_count', ['n' => $stats['submitted']])) ?></a>
            <?php if ($stats['without_contact'] > 0): ?>
            <span class="badge badge-danger"><?= htmlspecialchars(__('common.no_contact_count', ['n' => $stats['without_contact']])) ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<form method="get" class="card mb-5">
    <div class="card-body flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1"><?= htmlspecialchars(__('production.declared_period')) ?></label>
            <div class="flex gap-2">
                <select name="month" class="select w-auto">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $month === $m ? 'selected' : '' ?>><?= htmlspecialchars(__("common.month_{$m}")) ?></option>
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
            <label class="block text-xs font-medium text-slate-600 mb-1"><?= htmlspecialchars(__('production.search_client')) ?></label>
            <input type="search" name="q" value="<?= htmlspecialchars($f['q']) ?>" placeholder="<?= htmlspecialchars(__('production.search_placeholder')) ?>" class="input">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1"><?= htmlspecialchars(__('common.type')) ?></label>
            <select name="type" class="select w-auto">
                <option value=""><?= htmlspecialchars(__('common.all')) ?></option>
                <?php foreach (['CNAS_MENSUELLE','CNAS_TRIMESTRIELLE','CACOBATPH','G50'] as $t): ?>
                <option value="<?= $t ?>" <?= $f['typeFilter'] === $t ? 'selected' : '' ?>><?= \App\Modules\Automation\DeadlineService::typeLabel($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1"><?= htmlspecialchars(__('common.status')) ?></label>
            <select name="status" class="select w-auto">
                <option value=""><?= htmlspecialchars(__('common.all')) ?></option>
                <?php foreach ($statusLabels as $k => $lbl): ?>
                <option value="<?= $k ?>" <?= $f['statusFilter'] === $k ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('common.filter')) ?></button>
        <a href="/production?year=<?= $year ?>&month=<?= $month ?>" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('common.reset')) ?></a>
    </div>
</form>

<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
    <div class="stat-tile neutral"><p class="label"><?= htmlspecialchars(__('production.stat_obligations')) ?></p><p class="value"><?= $stats['total_obligations'] ?></p></div>
    <div class="stat-tile danger"><p class="label"><?= htmlspecialchars(__('production.stat_overdue')) ?></p><p class="value"><?= $stats['overdue'] ?></p></div>
    <div class="stat-tile warning"><p class="label"><?= htmlspecialchars(__('production.stat_missing')) ?></p><p class="value"><?= $stats['missing_data'] ?></p></div>
    <div class="stat-tile info"><p class="label"><?= htmlspecialchars(__('production.stat_drafts')) ?></p><p class="value"><?= $stats['draft_ready'] ?></p></div>
    <div class="stat-tile neutral"><p class="label"><?= htmlspecialchars(__('production.stat_amount')) ?></p><p class="value text-lg"><?= number_format($stats['total_amount'], 0, ',', ' ') ?> <span class="text-sm font-normal"><?= htmlspecialchars(__('common.currency')) ?></span></p></div>
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <h3><?= htmlspecialchars($p['month_label']) ?> — <?= htmlspecialchars(__('common.lines_count', ['n' => count($p['rows'])])) ?></h3>
        <span class="text-xs text-slate-500"><?= htmlspecialchars(__('common.active_clients_count', ['n' => $stats['total_clients']])) ?></span>
    </div>
    <div class="table-scroll">
        <table class="data-table production-table">
            <thead>
                <tr>
                    <th><?= htmlspecialchars(__('common.client')) ?></th>
                    <th><?= htmlspecialchars(__('common.obligation')) ?></th>
                    <th><?= htmlspecialchars(__('common.due_date_deposit')) ?></th>
                    <th><?= htmlspecialchars(__('common.status')) ?></th>
                    <th class="text-right"><?= htmlspecialchars(__('common.amount')) ?></th>
                    <th><?= htmlspecialchars(__('common.actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($p['rows'])): ?>
                <tr><td colspan="6" class="text-center py-12 text-slate-500"><?= htmlspecialchars(__('production.no_obligations')) ?></td></tr>
                <?php else: foreach ($p['rows'] as $row):
                    $rel = \App\Modules\Relances\RelanceService::linksFor($row);
                ?>
                <tr class="<?= $row['status'] === 'overdue' ? 'client-row-critical' : ($row['status'] === 'missing_data' ? 'client-row-warning' : '') ?>">
                    <td>
                        <a href="/clients/<?= $row['client_id'] ?>" class="font-medium text-slate-800 hover:text-accent-700"><?= htmlspecialchars($row['raison_sociale']) ?></a>
                        <span class="block text-xs text-slate-400"><?= htmlspecialchars($row['secteur']) ?></span>
                    </td>
                    <td>
                        <span class="font-mono text-xs text-accent-800"><?= htmlspecialchars($row['type_label']) ?></span>
                        <span class="block text-xs text-slate-500"><?= htmlspecialchars($row['period_label']) ?></span>
                    </td>
                    <td>
                        <span class="font-mono text-sm"><?= htmlspecialchars($row['due_label']) ?></span>
                        <?php if ($row['days_left'] < 0): ?>
                        <span class="badge badge-danger ml-1"><?= htmlspecialchars(__('common.late_badge', ['n' => abs($row['days_left'])])) ?></span>
                        <?php elseif ($row['days_left'] <= 7): ?>
                        <span class="badge badge-warning ml-1"><?= htmlspecialchars(__('common.days_left', ['n' => $row['days_left']])) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $row['status_badge'] ?>"><?= htmlspecialchars($row['status_label']) ?></span></td>
                    <td class="text-right font-mono"><?= $row['amount'] !== null ? number_format((float)$row['amount'], 0, ',', ' ') . ' ' . __('common.currency') : __('common.unassigned') ?></td>
                    <td>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <a href="<?= htmlspecialchars($row['action_url']) ?>" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('common.open')) ?></a>
                            <?php if (in_array($row['status'], ['missing_data', 'overdue'], true)): ?>
                            <?php require ROOT_PATH . '/templates/_partials/relance_buttons.php'; ?>
                            <?php endif; ?>
                            <?php if ($row['declaration_id'] && in_array($row['status'], ['draft_ready', 'approved'], true)): ?>
                            <a href="/declarations/<?= $row['declaration_id'] ?>/print" target="_blank" class="btn btn-ghost btn-sm" title="<?= htmlspecialchars(__('production.preview_title')) ?>"><?= htmlspecialchars(__('common.preview')) ?></a>
                            <a href="/declarations/<?= $row['declaration_id'] ?>" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('common.documents')) ?></a>
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
        btn.textContent = <?= json_encode(__('common.copied')) ?>;
        setTimeout(() => { btn.textContent = <?= json_encode(__('common.copy')) ?>; }, 1500);
    });
});
</script>

<details class="card mt-6" id="automation-panel" <?= !empty($showAutomation) ? 'open' : '' ?>>
    <summary class="card-header cursor-pointer select-none list-none flex items-center justify-between">
        <div>
            <h3><?= htmlspecialchars(__('production.advanced_options')) ?></h3>
            <p class="text-xs text-slate-500 font-normal mt-0.5"><?= htmlspecialchars(__('production.advanced_desc')) ?></p>
        </div>
        <span class="text-xs text-slate-400"><?= htmlspecialchars(__('common.show')) ?></span>
    </summary>
    <div class="card-body border-t border-slate-100">
        <?php require ROOT_PATH . '/templates/_partials/automation_panel.php'; ?>
    </div>
</details>
