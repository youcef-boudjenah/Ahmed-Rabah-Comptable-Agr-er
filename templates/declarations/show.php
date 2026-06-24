<?php
$cf = $declaration['computed_fields'];
$statusSteps = ['DRAFT_CALCULATED' => 'Brouillon calculé', 'APPROVED' => 'Approuvé', 'SUBMITTED' => 'Déposé'];
$currentIdx = array_search($declaration['status'], array_keys($statusSteps), true);
$prevTotal = $previous ? (float) ($previous['computed_fields']['total'] ?? 0) : null;
$currTotal = (float) ($cf['total'] ?? 0);
$variance = $prevTotal !== null ? $currTotal - $prevTotal : null;
?>
<?php require ROOT_PATH . '/templates/_partials/workflow_panel.php'; ?>

<div class="flex flex-wrap justify-between items-center gap-4 mb-6">
    <div>
        <p class="text-sm text-teal-600 font-medium"><?= htmlspecialchars($typeLabel) ?></p>
        <h2 class="text-xl font-bold text-navy-900"><?= htmlspecialchars($declaration['raison_sociale']) ?></h2>
        <p class="text-slate-500 text-sm">Période: <?= htmlspecialchars($periodLabel) ?></p>
    </div>
    <div class="flex gap-2 flex-wrap">
        <a href="/declarations/<?= $declaration['id'] ?>/print" target="_blank"
           class="px-4 py-2 rounded-xl border border-slate-200 text-sm hover:bg-slate-50 flex items-center gap-2">
            🖨 Imprimer
        </a>
        <?php if (!empty($declaration['generated_pdf_path'])): ?>
        <a href="/declarations/<?= $declaration['id'] ?>/generated" target="_blank"
           class="px-4 py-2 rounded-xl bg-teal-50 border border-teal-200 text-sm text-teal-800 hover:bg-teal-100">
            📄 Bordereau généré
        </a>
        <?php else: ?>
        <form method="post" action="/declarations/<?= $declaration['id'] ?>/generate-pdf">
            <button type="submit" class="px-4 py-2 rounded-xl border border-slate-200 text-sm hover:bg-slate-50">Générer bordereau</button>
        </form>
        <?php endif; ?>
        <a href="/clients/<?= $declaration['client_id'] ?>/dossier?cat=<?= \App\Modules\Automation\WorkflowService::gedCategoryForDeclaration($declaration['type']) ?>" class="px-4 py-2 rounded-xl border border-slate-200 text-sm hover:bg-slate-50">Dossier GED</a>
    </div>
</div>

<div class="flex gap-3 mb-6">
    <?php $i = 0; foreach ($statusSteps as $code => $label): ?>
    <div class="flex items-center gap-2 flex-1">
        <div class="flex-1 h-2 rounded-full <?= $i <= $currentIdx ? 'bg-teal-500' : 'bg-slate-200' ?>"></div>
        <span class="text-xs whitespace-nowrap <?= $i <= $currentIdx ? 'text-teal-700 font-medium' : 'text-slate-400' ?>"><?= $label ?></span>
    </div>
    <?php $i++; endforeach; ?>
</div>

<?php if ($variance !== null): ?>
<div class="mb-6 p-4 rounded-xl <?= $variance > 0 ? 'bg-amber-50 border border-amber-100' : 'bg-teal-50 border border-teal-100' ?> text-sm">
    vs période précédente:
    <span class="font-mono font-bold"><?= $variance >= 0 ? '+' : '' ?><?= number_format($variance, 2, ',', ' ') ?> DA</span>
    (<?= $prevTotal ? number_format((($variance / $prevTotal) * 100), 1) : 0 ?>%)
</div>
<?php endif; ?>

<?php if (!empty($aiReview)): ?>
<div class="mb-6 p-5 rounded-2xl bg-violet-50 border border-violet-100 text-sm">
    <h3 class="font-semibold text-violet-900 mb-2">🤖 Analyse IA</h3>
    <p class="text-slate-700"><?= nl2br(htmlspecialchars($aiReview['summary'] ?? '')) ?></p>
    <?php if (!empty($aiReview['risks'])): ?>
    <p class="font-medium text-red-700 mt-3 text-xs uppercase">Risques</p>
    <ul class="list-disc list-inside text-slate-600"><?php foreach ($aiReview['risks'] as $r): ?><li><?= htmlspecialchars($r) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <?php if (!empty($aiReview['actions'])): ?>
    <p class="font-medium text-teal-700 mt-3 text-xs uppercase">Actions recommandées</p>
    <ul class="list-disc list-inside text-slate-600"><?php foreach ($aiReview['actions'] as $a): ?><li><?= htmlspecialchars($a) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
</div>
<?php elseif ($declaration['status'] === 'DRAFT_CALCULATED'): ?>
<form method="post" action="/declarations/<?= $declaration['id'] ?>/ai-review" class="mb-6">
    <button type="submit" class="px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-sm font-medium">
        🤖 Analyser ce brouillon avec l'IA
    </button>
</form>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
            <h3 class="font-semibold text-navy-900 mb-4">Données source</h3>
            <?php if ($source): ?>
            <dl class="space-y-3 text-sm">
                <?php if (isset($source['masse_salariale'])): ?>
                <div class="flex justify-between py-2 border-b border-slate-50">
                    <dt class="text-slate-500">Masse salariale (assiette)</dt>
                    <dd class="font-mono font-bold text-navy-900"><?= number_format((float)$source['masse_salariale'], 2, ',', ' ') ?> DA</dd>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-50"><dt class="text-slate-500">Effectif</dt><dd><?= $source['effectif'] ?> travailleurs</dd></div>
                <?php endif; ?>
                <?php if (isset($source['irg_acompte_base'])): ?>
                <div class="flex justify-between py-2 border-b border-slate-50"><dt class="text-slate-500">Base IRG acompte</dt><dd class="font-mono"><?= number_format((float)$source['irg_acompte_base'], 2, ',', ' ') ?> DA</dd></div>
                <?php endif; ?>
                <div class="flex justify-between py-2"><dt class="text-slate-500">Origine</dt><dd><span class="px-2 py-0.5 bg-slate-100 rounded text-xs"><?= $source['source'] ?? 'manual' ?></span></dd></div>
            </dl>
            <?php else: ?>
            <p class="text-slate-400 text-sm">Pas de donnée source liée.</p>
            <?php endif; ?>
        </div>
        <?php if (!empty($cf['source'])): ?>
        <div class="bg-slate-50 rounded-2xl p-4 text-sm border border-slate-100">
            <p class="text-slate-500">Cotisant: <span class="font-mono"><?= htmlspecialchars($cf['source']['numero_cotisant'] ?? $declaration['numero_cotisant'] ?? '—') ?></span></p>
        </div>
        <?php endif; ?>
    </div>

    <div class="lg:col-span-3">
        <div class="bg-white rounded-2xl shadow-sm border-2 border-slate-200 overflow-hidden">
            <div class="bg-navy-900 text-white px-6 py-4">
                <p class="text-xs text-white/60 uppercase tracking-widest">Bordereau de déclaration</p>
                <p class="text-lg font-bold mt-1"><?= htmlspecialchars($typeLabel) ?></p>
            </div>
            <div class="p-6">
                <?php if ($declaration['status'] === 'DRAFT_CALCULATED'): ?>
                <form method="post" action="/declarations/<?= $declaration['id'] ?>">
                    <?php if (!empty($cf['lines'])): ?>
                    <table class="w-full text-sm mb-4">
                        <thead class="border-b-2 border-navy-900">
                            <tr class="text-left text-slate-500">
                                <th class="pb-2 w-20">Code</th>
                                <th class="pb-2">Nature des cotisations</th>
                                <th class="pb-2 text-right">Assiette</th>
                                <th class="pb-2 text-right">Taux</th>
                                <th class="pb-2 text-right">Montant (DA)</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cf['lines'] as $i => $line): ?>
                        <tr class="border-b border-slate-100">
                            <td class="py-3 font-mono font-bold text-teal-700"><?= htmlspecialchars($line['code'] ?? '') ?></td>
                            <td class="py-3"><?= htmlspecialchars($line['label'] ?? '') ?></td>
                            <td class="py-3 text-right font-mono text-slate-500"><?= isset($line['assiette']) ? number_format($line['assiette'], 2, ',', ' ') : (isset($line['ca']) ? number_format($line['ca'], 2, ',', ' ') : '—') ?></td>
                            <td class="py-3 text-right"><?= isset($line['taux']) ? $line['taux'] . '%' : '—' ?></td>
                            <td class="py-3 text-right">
                                <input type="text" name="lines[<?= $i ?>]" value="<?= number_format($line['montant'] ?? 0, 2, '.', '') ?>"
                                       class="w-32 text-right font-mono font-bold px-2 py-1 rounded border border-slate-200 focus:ring-2 focus:ring-teal-500">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                    <div class="flex justify-between items-center py-4 border-t-2 border-navy-900">
                        <span class="font-bold text-navy-900 uppercase text-sm">Total des cotisations dues</span>
                        <input type="text" name="total" value="<?= number_format($cf['total'] ?? 0, 2, '.', '') ?>"
                               class="w-40 text-right font-mono font-bold text-xl px-3 py-2 rounded border-2 border-navy-900">
                    </div>
                    <div class="grid grid-cols-2 gap-3 mt-4">
                        <button type="submit" class="py-3 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm font-medium">Enregistrer modifications</button>
                    </div>
                </form>
                <form method="post" action="/declarations/<?= $declaration['id'] ?>/approve" class="mt-3">
                    <button type="submit" class="w-full py-4 bg-teal-600 hover:bg-teal-500 text-white rounded-xl font-semibold text-lg shadow-lg shadow-teal-500/20" <?= empty($canApprove) ? 'disabled title="Permission refusée"' : '' ?>>
                        ✓ Approuver la déclaration
                    </button>
                </form>
                <?php if (empty($canApprove)): ?>
                <p class="text-xs text-slate-400 mt-2 text-center">Approbation réservée — voir Paramètres admin</p>
                <?php endif; ?>
                <?php else: ?>
                <table class="w-full text-sm">
                    <thead class="border-b-2 border-navy-900">
                        <tr class="text-left text-slate-500"><th class="pb-2">Code</th><th class="pb-2">Libellé</th><th class="pb-2 text-right">Montant</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cf['lines'] ?? [] as $line): ?>
                    <tr class="border-b border-slate-50">
                        <td class="py-3 font-mono font-bold text-teal-700"><?= htmlspecialchars($line['code'] ?? '') ?></td>
                        <td class="py-3"><?= htmlspecialchars($line['label'] ?? '') ?></td>
                        <td class="py-3 text-right font-mono font-semibold"><?= number_format($line['montant'] ?? 0, 2, ',', ' ') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="flex justify-between items-center py-4 border-t-2 border-navy-900 mt-2">
                    <span class="font-bold uppercase text-sm">Total</span>
                    <span class="text-2xl font-mono font-bold text-navy-900"><?= number_format($cf['total'] ?? 0, 2, ',', ' ') ?> DA</span>
                </div>
                <?php if ($declaration['status'] === 'APPROVED' && !empty($canSubmit)): ?>
                <form method="post" action="/declarations/<?= $declaration['id'] ?>/submit" enctype="multipart/form-data" id="depot" class="mt-4 space-y-4 p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <p class="font-semibold text-sm text-navy-900">Checklist dépôt</p>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="bordereau_imprime" required> Bordereau imprimé et signé</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="montants_verifies" required> Montants vérifiés vs source</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="quittance_jointe"> Quittance disponible</label>
                    <div>
                        <label class="text-sm text-slate-600 block mb-1">Joindre quittance (PDF/image)</label>
                        <input type="file" name="receipt" accept=".pdf,.png,.jpg" class="text-sm w-full">
                    </div>
                    <button type="submit" class="w-full py-4 bg-navy-900 hover:bg-navy-800 text-white rounded-xl font-semibold">Confirmer le dépôt</button>
                </form>
                <?php elseif ($declaration['status'] === 'APPROVED' && empty($canSubmit)): ?>
                <p class="mt-4 p-4 bg-slate-50 rounded-xl text-sm text-slate-500">Dépôt réservé à l'administrateur.</p>
                <?php elseif ($declaration['status'] === 'SUBMITTED'): ?>
                <div class="mt-4 p-4 bg-teal-50 rounded-xl border border-teal-100 text-sm space-y-2">
                    <p class="font-semibold text-teal-800">✓ Déclaration déposée</p>
                    <?php $chk = json_decode($declaration['checklist_json'] ?? '[]', true) ?: []; ?>
                    <ul class="text-slate-600 space-y-1">
                        <li><?= !empty($chk['bordereau_imprime']) ? '✓' : '○' ?> Bordereau imprimé</li>
                        <li><?= !empty($chk['montants_verifies']) ? '✓' : '○' ?> Montants vérifiés</li>
                        <li><?= !empty($chk['quittance_jointe']) ? '✓' : '○' ?> Quittance jointe</li>
                    </ul>
                    <?php if (!empty($declaration['receipt_path'])): ?>
                    <a href="/declarations/<?= $declaration['id'] ?>/receipt" target="_blank" class="inline-block text-teal-700 hover:underline">Voir quittance →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
