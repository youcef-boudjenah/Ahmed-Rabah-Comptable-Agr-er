<?php $ds = $deadlineStats; $ap = $automationPreview ?? []; $b = $briefing ?? null; ?>
<?php if (!empty($highlightRun)): ?>
<?php $run = $highlightRun; require ROOT_PATH . '/templates/automation/_run_report.php'; ?>
<?php endif; ?>

<?php if ($b): ?>
<div class="card-elevated mb-6">
    <div class="card-body">
        <div class="flex flex-wrap justify-between gap-4 items-start">
            <div class="flex-1 min-w-0">
                <p class="eyebrow">Briefing du jour — <?= date('d/m/Y') ?></p>
                <h2 class="text-lg font-semibold text-slate-900 mt-1">Production <?= htmlspecialchars($b['production']['month_label']) ?></h2>
                <div class="mt-3 max-w-md">
                    <div class="flex justify-between text-xs text-slate-600 mb-1">
                        <span>Déclarations déposées</span>
                        <span class="font-semibold"><?= $b['progress'] ?>%</span>
                    </div>
                    <div class="progress-bar"><span style="width:<?= $b['progress'] ?>%; background: var(--accent);"></span></div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="/production?year=<?= $b['prod_year'] ?>&month=<?= $b['prod_month'] ?>" class="btn btn-primary btn-sm">Ouvrir production</a>
                <a href="/entries/payroll/import?year=<?= $b['prod_year'] ?>&month=<?= $b['prod_month'] ?>&redirect=/production" class="btn btn-secondary btn-sm">Import paie</a>
            </div>
        </div>
        <ul class="mt-4 space-y-2">
            <?php foreach ($b['actions'] as $action): ?>
            <li>
                <a href="<?= htmlspecialchars($action['url']) ?>" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:border-teal-500 hover:bg-teal-50/50 transition text-sm">
                    <span class="badge <?= match($action['priority']) { 'critical' => 'badge-danger', 'high' => 'badge-warning', default => 'badge-neutral' } ?>"><?= match($action['priority']) { 'critical' => '!', 'high' => '→', default => '·' } ?></span>
                    <span class="font-medium text-slate-800"><?= htmlspecialchars($action['label']) ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<div class="hero-strip mb-6">
    <div class="flex-1 min-w-0">
        <p class="meta">Tableau de bord cabinet</p>
        <h2>CNAS · CACOBATPH · G50</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 max-w-2xl">
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <p class="text-xs text-slate-500">Retards</p>
                <p class="text-xl font-bold text-red-600"><?= $ds['overdue_count'] ?></p>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <p class="text-xs text-slate-500">Données manquantes</p>
                <p class="text-xl font-bold text-amber-600"><?= $ds['missing_data_count'] ?></p>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <p class="text-xs text-slate-500">Brouillons prêts</p>
                <p class="text-xl font-bold text-teal-700"><?= $ds['draft_ready_count'] ?></p>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <p class="text-xs text-slate-500">Montant 30j</p>
                <p class="text-lg font-bold font-mono"><?= number_format($ds['total_amount_due'], 0, ',', ' ') ?></p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 mt-4">
            <a href="/production" class="btn btn-primary">Production mensuelle</a>
            <form method="post" action="/dashboard/automation" class="inline">
                <button type="submit" class="btn btn-secondary btn-sm">Recalcul rapide</button>
            </form>
            <a href="/tasks" class="btn btn-ghost btn-sm">Tâches</a>
        </div>
    </div>
</div>

<div class="quick-links mb-6">
    <?php
    $links = [
        '/search' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
        '/ged' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
        '/rapports' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        '/echeancier' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        '/assistant' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
        '/documents' => 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12',
    ];
    $labels = ['Recherche', 'GED', 'Rapports', 'Échéancier', 'Assistant IA', 'OCR'];
    $i = 0;
    foreach ($links as $href => $icon):
    ?>
    <a href="<?= $href ?>" class="quick-link">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="<?= $icon ?>"/></svg>
        <?= $labels[$i++] ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="stat-grid grid-cols-2 md:grid-cols-5 mb-8">
    <div class="stat-tile danger">
        <p class="label">En retard</p>
        <p class="value"><?= $ds['overdue_count'] ?></p>
    </div>
    <div class="stat-tile warning">
        <p class="label">Données manquantes</p>
        <p class="value"><?= $ds['missing_data_count'] ?></p>
    </div>
    <div class="stat-tile warning">
        <p class="label">Brouillons prêts</p>
        <p class="value"><?= $ds['draft_ready_count'] ?></p>
    </div>
    <div class="stat-tile neutral">
        <p class="label">Échéances ce mois</p>
        <p class="value"><?= $ds['due_this_month'] ?></p>
    </div>
    <div class="stat-tile info">
        <p class="label">Brouillons total</p>
        <p class="value"><?= $stats['drafts'] ?></p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-6">
    <div class="card xl:col-span-2 overflow-hidden">
        <div class="card-header">
            <h2>File de travail — 30 prochains jours</h2>
            <a href="/echeancier" class="text-xs font-medium text-accent hover:underline">Échéancier complet</a>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Obligation</th>
                        <th>Échéance</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ds['upcoming'])): ?>
                    <tr><td colspan="6" class="text-center text-slate-400 py-10">Aucune obligation imminente.</td></tr>
                    <?php else: foreach ($ds['upcoming'] as $item): ?>
                    <tr>
                        <td class="font-medium"><?= htmlspecialchars($item['raison_sociale']) ?></td>
                        <td>
                            <span class="text-slate-900"><?= htmlspecialchars($item['type_label']) ?></span>
                            <span class="block text-xs text-slate-400"><?= htmlspecialchars($item['period_label']) ?></span>
                        </td>
                        <td class="font-mono text-xs">
                            <?= $item['due_label'] ?>
                            <?php if ($item['days_left'] < 0): ?>
                            <span class="text-red-600 block"><?= abs($item['days_left']) ?> j de retard</span>
                            <?php elseif ($item['days_left'] <= 7): ?>
                            <span class="text-amber-600 block">J-<?= $item['days_left'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="font-mono text-xs"><?= $item['amount'] ? number_format($item['amount'], 0, ',', ' ') . ' DA' : '—' ?></td>
                        <td>
                            <span class="badge <?= match(true) {
                                str_contains($item['status'] ?? '', 'overdue') || ($item['status'] ?? '') === 'overdue' => 'badge-danger',
                                ($item['status'] ?? '') === 'missing_data' => 'badge-warning',
                                default => 'badge-neutral'
                            } ?>"><?= htmlspecialchars($item['status_label']) ?></span>
                        </td>
                        <td class="text-right">
                            <?php if ($item['declaration_id']): ?>
                            <a href="/declarations/<?= $item['declaration_id'] ?>" class="text-xs font-medium text-accent hover:underline">Ouvrir</a>
                            <?php elseif ($item['status'] === 'missing_data'): ?>
                            <a href="<?= \App\Modules\Automation\WorkflowService::entryUrlForType($item['type'] ?? 'CNAS_MENSUELLE', (int) $item['client_id']) ?>" class="text-xs font-medium text-amber-700 hover:underline">Saisir</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Clients à surveiller</h3><a href="/clients?status=critical" class="text-xs text-accent hover:underline">Voir tout →</a></div>
        <div class="card-body space-y-3 pt-0">
            <?php foreach ($clientsStatus as $c): ?>
            <a href="/clients/<?= $c['id'] ?>" class="block p-3 rounded-md hover:bg-slate-50 border border-transparent hover:border-slate-200 transition">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-medium text-sm truncate"><?= htmlspecialchars($c['raison_sociale']) ?></span>
                    <span class="text-xs font-semibold <?= $c['compliance'] >= 80 ? 'text-emerald-700' : ($c['compliance'] >= 50 ? 'text-amber-700' : 'text-red-700') ?>"><?= $c['compliance'] ?>%</span>
                </div>
                <div class="progress-bar">
                    <span class="<?= $c['compliance'] >= 80 ? 'bg-emerald-500' : ($c['compliance'] >= 50 ? 'bg-amber-500' : 'bg-red-500') ?>" style="width: <?= $c['compliance'] ?>%"></span>
                </div>
                <?php if ($c['overdue'] || $c['missing']): ?>
                <p class="text-xs text-slate-400 mt-1.5">
                    <?php if ($c['overdue']): ?><span class="text-red-600"><?= $c['overdue'] ?> retard</span><?php endif; ?>
                    <?php if ($c['missing']): ?><span class="text-amber-600"><?= $c['missing'] ?> données manquantes</span><?php endif; ?>
                </p>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if (!empty($relances)): ?>
<div class="card mb-6 border-amber-200">
    <div class="card-header">
        <h2>Relances clients</h2>
        <span class="badge badge-warning"><?= count($relances) ?> dossier(s)</span>
    </div>
    <div class="card-body grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 pt-0">
        <?php foreach ($relances as $r):
            $rel = $r['relance'] ?? \App\Modules\Relances\RelanceService::linksFor($r);
        ?>
        <div class="p-4 rounded-md border border-slate-200 bg-slate-50/50 text-sm">
            <p class="font-medium"><?= htmlspecialchars($r['raison_sociale']) ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($r['type_label']) ?> — <?= htmlspecialchars($r['period_label']) ?></p>
            <p class="mt-2"><span class="badge badge-warning"><?= htmlspecialchars($r['status_label']) ?></span></p>
            <div class="flex flex-wrap gap-2 mt-3 text-xs items-center">
                <a href="/clients/<?= $r['client_id'] ?>" class="font-medium text-accent hover:underline">Fiche</a>
                <?php if ($r['status'] === 'missing_data'): ?>
                <a href="<?= \App\Modules\Automation\WorkflowService::entryUrlForType($r['type'] ?? 'CNAS_MENSUELLE', (int) $r['client_id']) ?>" class="font-medium text-amber-700 hover:underline">Saisir</a>
                <?php elseif ($r['declaration_id']): ?>
                <a href="/declarations/<?= $r['declaration_id'] ?>" class="font-medium text-accent hover:underline">Déclaration</a>
                <?php endif; ?>
                <?php $row = $r; require ROOT_PATH . '/templates/_partials/relance_buttons.php'; ?>
                <button type="button" class="font-medium text-violet-700 hover:underline ai-relance"
                    data-client="<?= $r['client_id'] ?>"
                    data-obligation="<?= htmlspecialchars($r['type_label'] . ' — ' . $r['period_label']) ?>"
                    data-status="<?= htmlspecialchars($r['status_label']) ?>">IA</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="card mb-6">
    <div class="card-header flex-wrap">
        <h2>Tâches cabinet</h2>
        <a href="/tasks" class="btn btn-ghost btn-sm">Voir tout →</a>
        <form method="post" action="/tasks" class="flex flex-wrap gap-2 items-center flex-1 max-w-3xl justify-end">
            <input type="hidden" name="redirect" value="/">
            <input name="title" required placeholder="Nouvelle tâche…" class="input flex-1 min-w-[140px] max-w-xs">
            <div class="w-48 shrink-0"><?php $name = 'client_id'; $required = false; $compact = true; require ROOT_PATH . '/templates/_partials/client_picker.php'; ?></div>
            <input type="date" name="due_date" class="input w-auto">
            <select name="priority" class="select w-auto">
                <option value="normal">Normal</option>
                <option value="high">Urgent</option>
                <option value="low">Bas</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Ajouter</button>
        </form>
    </div>
    <div class="divide-y divide-slate-100">
        <?php if (empty($tasks)): ?>
        <p class="px-5 py-6 text-slate-400 text-sm">Aucune tâche ouverte.</p>
        <?php else: foreach ($tasks as $t): ?>
        <div class="flex items-center gap-3 px-5 py-3">
            <form method="post" action="/tasks/<?= $t['id'] ?>/complete">
                <input type="hidden" name="redirect" value="/">
                <button type="submit" class="w-4 h-4 rounded border-2 border-slate-300 hover:border-accent hover:bg-accent-muted" title="Terminer"></button>
            </form>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate"><?= htmlspecialchars($t['title']) ?></p>
                <p class="text-xs text-slate-400">
                    <?php if ($t['raison_sociale']): ?><?= htmlspecialchars($t['raison_sociale']) ?> · <?php endif; ?>
                    <?php if ($t['due_date']): ?>Échéance <?= date('d/m/Y', strtotime($t['due_date'])) ?><?php endif; ?>
                </p>
            </div>
            <span class="badge <?= $t['priority'] === 'high' ? 'badge-danger' : ($t['priority'] === 'low' ? 'badge-neutral' : 'badge-warning') ?>"><?= $t['priority'] ?></span>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <div class="card overflow-hidden">
        <div class="card-header"><h2>Brouillons à valider</h2></div>
        <div class="divide-y divide-slate-100">
            <?php if (empty($recentDrafts)): ?>
            <p class="px-5 py-6 text-slate-400 text-sm">Aucun brouillon en attente.</p>
            <?php else: foreach ($recentDrafts as $d):
                $cf = $d['computed_fields'];
            ?>
            <a href="/declarations/<?= $d['id'] ?>" class="flex justify-between items-center px-5 py-3.5 hover:bg-slate-50 transition">
                <div>
                    <p class="font-medium text-sm"><?= htmlspecialchars($d['type']) ?></p>
                    <p class="text-xs text-slate-500"><?= htmlspecialchars($d['raison_sociale']) ?></p>
                </div>
                <span class="font-mono text-sm font-semibold text-accent"><?= number_format($cf['total'] ?? 0, 0, ',', ' ') ?> DA</span>
            </a>
            <?php endforeach; endif; ?>
        </div>
    </div>
    <div class="card overflow-hidden">
        <div class="card-header"><h2>Alertes</h2></div>
        <div class="divide-y divide-slate-100 max-h-64 overflow-y-auto">
            <?php if (empty($alerts)): ?>
            <p class="px-5 py-6 text-slate-400 text-sm">Aucune alerte.</p>
            <?php else: foreach ($alerts as $a): ?>
            <div class="px-5 py-3 flex gap-3 items-start">
                <p class="text-sm text-slate-700 flex-1"><?= htmlspecialchars($a['message_fr']) ?></p>
                <form method="post" action="/alerts/<?= $a['id'] ?>/read"><button type="submit" class="text-xs text-slate-400 hover:text-accent">Marquer lu</button></form>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<div id="relance-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="card max-w-lg w-full shadow-xl">
        <div class="card-header"><h3>Message de relance (IA)</h3></div>
        <div class="card-body">
            <textarea id="relance-text" rows="8" class="textarea" readonly></textarea>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="document.getElementById('relance-modal').classList.add('hidden')" class="btn btn-secondary">Fermer</button>
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('relance-text').value)" class="btn btn-primary">Copier</button>
            </div>
        </div>
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
document.querySelectorAll('.ai-relance').forEach(btn => {
    btn.addEventListener('click', async () => {
        const label = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Génération…';
        const res = await fetch('/automation/ai-relance', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                client_id: btn.dataset.client,
                obligation: btn.dataset.obligation,
                status: btn.dataset.status
            })
        });
        const data = await res.json();
        document.getElementById('relance-text').value = data.message || data.error || '';
        const modal = document.getElementById('relance-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        btn.disabled = false;
        btn.textContent = label;
    });
});
</script>
