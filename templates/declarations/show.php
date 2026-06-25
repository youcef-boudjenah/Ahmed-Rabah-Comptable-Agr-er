<?php
$cf = $declaration['computed_fields'];
$statusSteps = ['DRAFT_CALCULATED' => __('common.decl_status_draft'), 'APPROVED' => __('common.decl_status_approved'), 'SUBMITTED' => __('common.decl_status_submitted')];
$currentIdx = array_search($declaration['status'], array_keys($statusSteps), true);
$prevTotal = $previous ? (float) ($previous['computed_fields']['total'] ?? 0) : null;
$currTotal = (float) ($cf['total'] ?? 0);
$variance = $prevTotal !== null ? $currTotal - $prevTotal : null;
?>
<?php require ROOT_PATH . '/templates/_partials/workflow_panel.php'; ?>

<div class="flex flex-wrap justify-between items-center gap-4 mb-6">
    <div>
        <p class="text-sm text-accent-600 font-medium"><?= htmlspecialchars($typeLabel) ?></p>
        <h2 class="text-xl font-bold text-navy-900"><?= htmlspecialchars($declaration['raison_sociale']) ?></h2>
        <p class="text-slate-500 text-sm"><?= htmlspecialchars(__('common.decl_period')) ?> <?= htmlspecialchars($periodLabel) ?></p>
    </div>
    <?php if ($declaration['status'] === 'DRAFT_CALCULATED'): ?>
    <form method="post" action="/declarations/<?= $declaration['id'] ?>/delete" onsubmit="return confirm(<?= json_encode(__('common.confirm_delete_draft')) ?>);">
        <button type="submit" class="px-4 py-2 rounded-xl border border-red-200 text-sm text-red-700 hover:bg-red-50"><?= htmlspecialchars(__('common.delete')) ?></button>
    </form>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/templates/_partials/declaration_documents.php'; ?>

<div class="flex gap-3 mb-6">
    <?php $i = 0; foreach ($statusSteps as $code => $label): ?>
    <div class="flex items-center gap-2 flex-1">
        <div class="flex-1 h-2 rounded-full <?= $i <= $currentIdx ? 'bg-accent-500' : 'bg-slate-200' ?>"></div>
        <span class="text-xs whitespace-nowrap <?= $i <= $currentIdx ? 'text-accent-700 font-medium' : 'text-slate-400' ?>"><?= htmlspecialchars($label) ?></span>
    </div>
    <?php $i++; endforeach; ?>
</div>

<?php if ($variance !== null): ?>
<div class="mb-6 p-4 rounded-xl <?= $variance > 0 ? 'bg-amber-50 border border-amber-100' : 'bg-accent-50 border border-accent-100' ?> text-sm">
    <?= htmlspecialchars(__('common.vs_previous')) ?>
    <span class="font-mono font-bold"><?= $variance >= 0 ? '+' : '' ?><?= number_format($variance, 2, ',', ' ') ?> <?= htmlspecialchars(__('common.currency')) ?></span>
    (<?= $prevTotal ? number_format((($variance / $prevTotal) * 100), 1) : 0 ?>%)
</div>
<?php endif; ?>

<?php if (!empty($aiReview)): ?>
<div class="mb-6 p-5 rounded-2xl bg-violet-50 border border-violet-100 text-sm">
    <h3 class="font-semibold text-violet-900 mb-2"><?= htmlspecialchars(__('common.decl_ai_analysis')) ?></h3>
    <p class="text-slate-700"><?= nl2br(htmlspecialchars($aiReview['summary'] ?? '')) ?></p>
    <?php if (!empty($aiReview['risks'])): ?>
    <p class="font-medium text-red-700 mt-3 text-xs uppercase"><?= htmlspecialchars(__('common.decl_ai_risks')) ?></p>
    <ul class="list-disc list-inside text-slate-600"><?php foreach ($aiReview['risks'] as $r): ?><li><?= htmlspecialchars($r) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <?php if (!empty($aiReview['actions'])): ?>
    <p class="font-medium text-accent-700 mt-3 text-xs uppercase"><?= htmlspecialchars(__('common.decl_ai_actions')) ?></p>
    <ul class="list-disc list-inside text-slate-600"><?php foreach ($aiReview['actions'] as $a): ?><li><?= htmlspecialchars($a) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
</div>
<?php elseif ($declaration['status'] === 'DRAFT_CALCULATED'): ?>
<form method="post" action="/declarations/<?= $declaration['id'] ?>/ai-review" class="mb-6">
    <button type="submit" class="px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-sm font-medium">
        <?= htmlspecialchars(__('common.decl_ai_analyze')) ?>
    </button>
</form>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
            <h3 class="font-semibold text-navy-900 mb-4"><?= htmlspecialchars(__('common.decl_source_data')) ?></h3>
            <?php if ($source): ?>
            <dl class="space-y-3 text-sm">
                <?php if (isset($source['masse_salariale'])): ?>
                <div class="flex justify-between py-2 border-b border-slate-50">
                    <dt class="text-slate-500"><?= htmlspecialchars(__('common.decl_payroll_mass')) ?></dt>
                    <dd class="font-mono font-bold text-navy-900"><?= number_format((float)$source['masse_salariale'], 2, ',', ' ') ?> <?= htmlspecialchars(__('common.currency')) ?></dd>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-50"><dt class="text-slate-500"><?= htmlspecialchars(__('common.decl_headcount')) ?></dt><dd><?= $source['effectif'] ?> <?= htmlspecialchars(__('common.employees')) ?></dd></div>
                <?php endif; ?>
                <?php if (isset($source['irg_acompte_base'])): ?>
                <div class="flex justify-between py-2 border-b border-slate-50"><dt class="text-slate-500"><?= htmlspecialchars(__('common.decl_irg_base')) ?></dt><dd class="font-mono"><?= number_format((float)$source['irg_acompte_base'], 2, ',', ' ') ?> <?= htmlspecialchars(__('common.currency')) ?></dd></div>
                <?php endif; ?>
                <div class="flex justify-between py-2"><dt class="text-slate-500"><?= htmlspecialchars(__('common.origin')) ?></dt><dd><span class="px-2 py-0.5 bg-slate-100 rounded text-xs"><?= $source['source'] ?? 'manual' ?></span></dd></div>
            </dl>
            <?php else: ?>
            <p class="text-slate-400 text-sm"><?= htmlspecialchars(__('common.decl_no_source')) ?></p>
            <?php endif; ?>
        </div>
        <?php if (!empty($cf['source'])): ?>
        <div class="bg-slate-50 rounded-2xl p-4 text-sm border border-slate-100">
            <p class="text-slate-500"><?= htmlspecialchars(__('common.decl_cotisant')) ?> <span class="font-mono"><?= htmlspecialchars($cf['source']['numero_cotisant'] ?? $declaration['numero_cotisant'] ?? __('common.unassigned')) ?></span></p>
        </div>
        <?php endif; ?>
    </div>

    <div class="lg:col-span-3">
        <div class="bg-white rounded-2xl shadow-sm border-2 border-slate-200 overflow-hidden">
            <div class="bg-navy-900 text-white px-6 py-4">
                <p class="text-xs text-white/60 uppercase tracking-widest"><?= htmlspecialchars(__('common.decl_form_title')) ?></p>
                <p class="text-lg font-bold mt-1"><?= htmlspecialchars($typeLabel) ?></p>
            </div>
            <div class="p-6">
                <?php if ($declaration['status'] === 'DRAFT_CALCULATED'): ?>
                <form method="post" action="/declarations/<?= $declaration['id'] ?>">
                    <?php if (!empty($cf['lines'])): ?>
                    <table class="w-full text-sm mb-4">
                        <thead class="border-b-2 border-navy-900">
                            <tr class="text-left text-slate-500">
                                <th class="pb-2 w-20"><?= htmlspecialchars(__('common.decl_line_code')) ?></th>
                                <th class="pb-2"><?= htmlspecialchars(__('common.decl_line_nature')) ?></th>
                                <th class="pb-2 text-right"><?= htmlspecialchars(__('common.decl_line_base')) ?></th>
                                <th class="pb-2 text-right"><?= htmlspecialchars(__('common.decl_line_rate')) ?></th>
                                <th class="pb-2 text-right"><?= htmlspecialchars(__('common.decl_line_amount')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cf['lines'] as $i => $line): ?>
                        <tr class="border-b border-slate-100">
                            <td class="py-3 font-mono font-bold text-accent-700"><?= htmlspecialchars($line['code'] ?? '') ?></td>
                            <td class="py-3"><?= htmlspecialchars($line['label'] ?? '') ?></td>
                            <td class="py-3 text-right font-mono text-slate-500"><?= isset($line['assiette']) ? number_format($line['assiette'], 2, ',', ' ') : (isset($line['ca']) ? number_format($line['ca'], 2, ',', ' ') : __('common.unassigned')) ?></td>
                            <td class="py-3 text-right"><?= isset($line['taux']) ? $line['taux'] . '%' : __('common.unassigned') ?></td>
                            <td class="py-3 text-right">
                                <input type="text" name="lines[<?= $i ?>]" value="<?= number_format($line['montant'] ?? 0, 2, '.', '') ?>"
                                       class="w-32 text-right font-mono font-bold px-2 py-1 rounded border border-slate-200 focus:ring-2 focus:ring-accent-500">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                    <div class="flex justify-between items-center py-4 border-t-2 border-navy-900">
                        <span class="font-bold text-navy-900 uppercase text-sm"><?= htmlspecialchars(__('common.decl_total_due')) ?></span>
                        <input type="text" name="total" value="<?= number_format($cf['total'] ?? 0, 2, '.', '') ?>"
                               class="w-40 text-right font-mono font-bold text-xl px-3 py-2 rounded border-2 border-navy-900">
                    </div>
                    <div class="grid grid-cols-2 gap-3 mt-4">
                        <button type="submit" class="py-3 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm font-medium"><?= htmlspecialchars(__('common.decl_save_changes')) ?></button>
                    </div>
                </form>
                <form method="post" action="/declarations/<?= $declaration['id'] ?>/approve" class="mt-3">
                    <button type="submit" class="w-full py-4 bg-accent-600 hover:bg-accent-500 text-white rounded-xl font-semibold text-lg shadow-lg shadow-accent-500/20" <?= empty($canApprove) ? 'disabled title="' . htmlspecialchars(__('common.permission_denied')) . '"' : '' ?>>
                        <?= htmlspecialchars(__('common.decl_approve_btn')) ?>
                    </button>
                </form>
                <?php if (empty($canApprove)): ?>
                <p class="text-xs text-slate-400 mt-2 text-center"><?= htmlspecialchars(__('common.decl_approve_restricted')) ?></p>
                <?php endif; ?>
                <?php else: ?>
                <table class="w-full text-sm">
                    <thead class="border-b-2 border-navy-900">
                        <tr class="text-left text-slate-500"><th class="pb-2"><?= htmlspecialchars(__('common.decl_line_code')) ?></th><th class="pb-2"><?= htmlspecialchars(__('common.label')) ?></th><th class="pb-2 text-right"><?= htmlspecialchars(__('common.amount')) ?></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cf['lines'] ?? [] as $line): ?>
                    <tr class="border-b border-slate-50">
                        <td class="py-3 font-mono font-bold text-accent-700"><?= htmlspecialchars($line['code'] ?? '') ?></td>
                        <td class="py-3"><?= htmlspecialchars($line['label'] ?? '') ?></td>
                        <td class="py-3 text-right font-mono font-semibold"><?= number_format($line['montant'] ?? 0, 2, ',', ' ') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="flex justify-between items-center py-4 border-t-2 border-navy-900 mt-2">
                    <span class="font-bold uppercase text-sm"><?= htmlspecialchars(__('common.total')) ?></span>
                    <span class="text-2xl font-mono font-bold text-navy-900"><?= number_format($cf['total'] ?? 0, 2, ',', ' ') ?> <?= htmlspecialchars(__('common.currency')) ?></span>
                </div>
                <?php if ($declaration['status'] === 'APPROVED' && !empty($canSubmit)): ?>
                <form method="post" action="/declarations/<?= $declaration['id'] ?>/submit" enctype="multipart/form-data" id="depot" class="mt-4 space-y-4 p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <p class="font-semibold text-sm text-navy-900"><?= htmlspecialchars(__('common.decl_submit_checklist')) ?></p>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="bordereau_imprime" required> <?= htmlspecialchars(__('common.decl_check_printed')) ?></label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="montants_verifies" required> <?= htmlspecialchars(__('common.decl_check_amounts')) ?></label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="quittance_jointe"> <?= htmlspecialchars(__('common.decl_check_receipt')) ?></label>
                    <div>
                        <label class="text-sm text-slate-600 block mb-1"><?= htmlspecialchars(__('common.decl_attach_receipt')) ?></label>
                        <input type="file" name="receipt" accept=".pdf,.png,.jpg" class="text-sm w-full">
                    </div>
                    <button type="submit" class="w-full py-4 bg-navy-900 hover:bg-navy-800 text-white rounded-xl font-semibold"><?= htmlspecialchars(__('common.submit')) ?></button>
                </form>
                <?php elseif ($declaration['status'] === 'APPROVED' && empty($canSubmit)): ?>
                <p class="mt-4 p-4 bg-slate-50 rounded-xl text-sm text-slate-500"><?= htmlspecialchars(__('common.decl_submit_restricted')) ?></p>
                <?php elseif ($declaration['status'] === 'SUBMITTED'): ?>
                <div class="mt-4 p-4 bg-accent-50 rounded-xl border border-accent-100 text-sm space-y-2">
                    <p class="font-semibold text-accent-800"><?= htmlspecialchars(__('common.decl_submitted_title')) ?></p>
                    <?php $chk = json_decode($declaration['checklist_json'] ?? '[]', true) ?: []; ?>
                    <ul class="text-slate-600 space-y-1">
                        <li><?= !empty($chk['bordereau_imprime']) ? '✓' : '○' ?> <?= htmlspecialchars(__('common.decl_check_printed_short')) ?></li>
                        <li><?= !empty($chk['montants_verifies']) ? '✓' : '○' ?> <?= htmlspecialchars(__('common.decl_check_amounts_short')) ?></li>
                        <li><?= !empty($chk['quittance_jointe']) ? '✓' : '○' ?> <?= htmlspecialchars(__('common.decl_check_receipt_short')) ?></li>
                    </ul>
                    <?php if (!empty($declaration['receipt_path'])): ?>
                    <a href="/declarations/<?= $declaration['id'] ?>/receipt" target="_blank" class="inline-block text-accent-700 hover:underline"><?= htmlspecialchars(__('common.decl_see_receipt')) ?></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
