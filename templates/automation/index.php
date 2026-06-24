<?php
$hasAi = $hasOpenRouter ?? false;
$p = $preview ?? [];
$stepDefs = [
    'recalc' => ['label' => 'Recalcul déclarations', 'hint' => $p['payroll_entries'] . ' paie + ' . $p['sales_entries'] . ' ventes', 'default' => true],
    'tasks' => ['label' => 'Créer tâches', 'hint' => 'Retards et données manquantes', 'default' => true],
    'pdfs' => ['label' => 'Générer bordereaux', 'hint' => $p['declarations_missing_pdf'] . ' sans fichier', 'default' => true],
    'ocr' => ['label' => 'Traiter OCR', 'hint' => $p['ocr_queue'] . ' en file + ' . $p['documents_pending_ocr'] . ' en attente', 'default' => true],
    'ai_review' => ['label' => 'Analyse IA brouillons', 'hint' => $p['drafts_for_ai_review'] . ' à analyser', 'default' => $hasAi, 'needs_ai' => true],
    'ai_classify' => ['label' => 'Classer documents GED', 'hint' => $p['documents_to_classify'] . ' dans « divers »', 'default' => $hasAi, 'needs_ai' => true],
];
?>
<div class="page-intro">
    <p class="eyebrow">Traitement automatique</p>
    <h2>Automatiser les tâches répétitives du cabinet</h2>
    <p>Cette page permet de lancer manuellement ce que le système peut faire seul : recalculer les déclarations,
        créer des tâches, générer les PDF, traiter l'OCR et (optionnel) analyser avec l'IA.
        <strong>Après chaque lancement, le rapport détaillé apparaît ci-dessous.</strong></p>
</div>

<?php if (!empty($highlightRun)): ?>
<?php $run = $highlightRun; require __DIR__ . '/_run_report.php'; ?>
<?php endif; ?>

<?php if (!$hasAi): ?>
<div class="alert alert-warning mb-6">
    Étapes IA désactivées — configurez <code class="bg-white px-1 rounded text-xs">OPENROUTER_API_KEY</code> dans .env
</div>
<?php endif; ?>

<div class="card mb-6">
    <div class="card-header"><h3>Aperçu avant lancement</h3></div>
    <div class="card-body">
        <div class="metric-grid mb-4">
            <div class="metric-box"><p class="num"><?= $p['payroll_entries'] ?? 0 ?></p><p class="lbl">Saisies paie</p></div>
            <div class="metric-box"><p class="num"><?= $p['sales_entries'] ?? 0 ?></p><p class="lbl">Saisies ventes</p></div>
            <div class="metric-box"><p class="num"><?= $p['declarations_missing_pdf'] ?? 0 ?></p><p class="lbl">Bordereaux à générer</p></div>
            <div class="metric-box"><p class="num"><?= ($p['ocr_queue'] ?? 0) + ($p['documents_pending_ocr'] ?? 0) ?></p><p class="lbl">Docs OCR en attente</p></div>
        </div>
        <div class="metric-grid">
            <div class="metric-box"><p class="num text-amber-700"><?= $stats['draft_ready_count'] ?? 0 ?></p><p class="lbl">Brouillons prêts</p></div>
            <div class="metric-box"><p class="num text-orange-700"><?= $stats['missing_data_count'] ?? 0 ?></p><p class="lbl">Données manquantes</p></div>
            <div class="metric-box"><p class="num text-red-700"><?= $stats['overdue_count'] ?? 0 ?></p><p class="lbl">En retard</p></div>
            <div class="metric-box"><p class="num"><?= $p['open_tasks'] ?? 0 ?></p><p class="lbl">Tâches ouvertes</p></div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
    <form method="post" action="/automation/run" class="card">
        <div class="card-header">
            <h3>Contrôle des étapes</h3>
        </div>
        <div class="card-body">
            <div class="space-y-2 mb-5">
                <?php foreach ($stepDefs as $key => $def):
                    $disabled = !empty($def['needs_ai']) && !$hasAi;
                ?>
                <label class="flex items-start gap-3 p-3 rounded-md border border-slate-200 hover:bg-slate-50 cursor-pointer <?= $disabled ? 'opacity-40' : '' ?>">
                    <input type="checkbox" name="step_<?= $key ?>" value="1"
                        <?= $def['default'] && !$disabled ? 'checked' : '' ?>
                        <?= $disabled ? 'disabled' : '' ?>
                        class="mt-0.5 rounded">
                    <div>
                        <p class="font-medium text-sm"><?= $def['label'] ?></p>
                        <p class="text-xs text-slate-500"><?= $def['hint'] ?></p>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="flex gap-2 mb-5">
                <button type="button" onclick="document.querySelectorAll('input[name^=step_]:not(:disabled)').forEach(c=>c.checked=true)" class="btn btn-secondary btn-sm">Tout cocher</button>
                <button type="button" onclick="document.querySelectorAll('input[name^=step_]').forEach(c=>c.checked=false)" class="btn btn-secondary btn-sm">Tout décocher</button>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Lancer les étapes cochées</button>
            <p class="text-center text-xs text-slate-400 mt-2">Le rapport s'affiche immédiatement après</p>
        </div>
    </form>

    <div class="space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Raccourcis</p>

        <form method="post" action="/automation/batch-recalculate" class="card p-4 flex justify-between items-center">
            <div>
                <p class="font-medium text-sm">Recalcul seulement</p>
                <p class="text-xs text-slate-500">Après import CSV ou correction paie</p>
            </div>
            <button type="submit" class="btn btn-secondary btn-sm">Exécuter</button>
        </form>

        <form method="post" action="/automation/generate-pdfs" class="card p-4 flex justify-between items-center">
            <div>
                <p class="font-medium text-sm">Bordereaux seulement</p>
                <p class="text-xs text-slate-500"><?= $p['declarations_missing_pdf'] ?? 0 ?> fichier(s) à créer</p>
            </div>
            <button type="submit" class="btn btn-secondary btn-sm">Exécuter</button>
        </form>

        <form method="post" action="/automation/run-all" class="card p-5 bg-slate-900 text-white border-slate-800">
            <p class="font-semibold text-sm">Pipeline complet</p>
            <p class="text-xs text-white/60 mt-1 mb-4">Toutes les étapes en une exécution</p>
            <?php if ($hasAi): ?>
            <label class="flex items-center gap-2 text-xs mb-4 text-white/80">
                <input type="checkbox" name="with_ai" value="1" checked class="rounded"> Inclure les étapes IA
            </label>
            <?php endif; ?>
            <button type="submit" class="btn btn-block bg-white text-slate-900 hover:bg-slate-100 border-0">Lancer tout</button>
        </form>

        <?php if (($user['role'] ?? '') === 'admin'): ?>
        <a href="/admin?tab=settings" class="block text-center text-xs text-slate-500 hover:text-accent pt-1">Paramètres administration</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Historique des exécutions</h3>
        <span class="text-xs text-slate-400"><?= count($recentRuns) ?> entrée(s)</span>
    </div>
    <?php if (empty($recentRuns)): ?>
    <p class="text-slate-400 text-sm py-10 text-center">Aucun traitement enregistré.</p>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th><th>Date</th><th>Type</th><th>Par</th><th>Durée</th><th>Résultat</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentRuns as $r):
                    $res = json_decode($r['result_json'] ?? '{}', true) ?: [];
                    $sum = $res['summary'] ?? [];
                    $steps = $res['steps'] ?? [];
                ?>
                <tr>
                    <td class="font-mono text-xs"><?= $r['id'] ?></td>
                    <td class="font-mono text-xs"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                    <td class="text-xs"><?= htmlspecialchars($r['run_type']) ?></td>
                    <td><?= htmlspecialchars($r['user_name'] ?? '—') ?></td>
                    <td class="text-xs"><?= isset($res['duration_ms']) ? round($res['duration_ms']/1000, 1).' s' : '—' ?></td>
                    <td class="text-xs">
                        <?php if ($sum): ?>
                        <span class="text-emerald-700"><?= $sum['ok'] ?? 0 ?> OK</span>
                        <?php if (!empty($sum['error'])): ?><span class="text-red-600 ml-1"><?= $sum['error'] ?> err.</span><?php endif; ?>
                        <?php elseif (isset($res['recalc'])): ?>
                        <span class="text-slate-400">format legacy</span>
                        <?php else: ?>
                        <?= count($steps) ?> étape(s)
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <a href="/automation?run=<?= $r['id'] ?>" class="text-xs font-medium text-accent hover:underline">Détail</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
