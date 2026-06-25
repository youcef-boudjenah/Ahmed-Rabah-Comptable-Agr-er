<?php $p = $profile; $isArchived = empty($client['is_active']); ?>
<?php if ($isArchived): ?>
<div class="alert alert-warning mb-4 flex flex-wrap justify-between items-center gap-3">
    <span><?= htmlspecialchars(__('common.client_archived')) ?></span>
    <form method="post" action="/clients/<?= $client['id'] ?>/restore" class="inline">
        <button type="submit" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('common.restore')) ?></button>
    </form>
</div>
<?php endif; ?>
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
    <div class="lg:col-span-3 bg-white rounded-2xl p-8 shadow-sm border border-slate-100">
        <div class="flex flex-wrap justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-navy-900"><?= htmlspecialchars($client['raison_sociale']) ?></h2>
                <p class="text-slate-500 mt-1"><?= htmlspecialchars($client['activite'] ?? '') ?> — <?= htmlspecialchars($client['wilaya'] ?? '') ?></p>
            </div>
            <div class="flex gap-6 text-sm">
                <div class="text-center">
                    <p class="text-3xl font-bold <?= $p['compliance'] >= 80 ? 'text-accent-600' : 'text-amber-600' ?>"><?= $p['compliance'] ?>%</p>
                    <p class="text-slate-400 text-xs"><?= htmlspecialchars(__('common.compliance')) ?></p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold font-mono text-navy-900"><?= number_format($p['ytd_cotisations'], 0, ',', ' ') ?></p>
                    <p class="text-slate-400 text-xs"><?= htmlspecialchars(__('common.ytd_cotisations')) ?></p>
                </div>
            </div>
        </div>
        <dl class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm border-t border-slate-100 pt-6">
            <div><dt class="text-slate-400"><?= htmlspecialchars(__('common.nif')) ?></dt><dd class="font-mono font-medium mt-1"><?= htmlspecialchars($client['nif'] ?? __('common.unassigned')) ?></dd></div>
            <div><dt class="text-slate-400"><?= htmlspecialchars(__('common.cotisant')) ?></dt><dd class="font-mono font-medium mt-1"><?= htmlspecialchars($client['numero_cotisant'] ?? __('common.unassigned')) ?></dd></div>
            <div><dt class="text-slate-400"><?= htmlspecialchars(__('common.sector')) ?></dt><dd class="mt-1"><span class="px-2 py-0.5 bg-navy-900/10 rounded text-navy-800 text-xs"><?= $client['secteur'] ?></span></dd></div>
            <div><dt class="text-slate-400"><?= htmlspecialchars(__('common.regime_cnas')) ?></dt><dd class="mt-1"><?= $client['cnas_regime'] ?></dd></div>
        </dl>
        <?php if (!empty($client['contact_phone']) || !empty($client['contact_email'])): ?>
        <dl class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm border-t border-slate-100 pt-4">
            <?php if (!empty($client['contact_name'])): ?>
            <div><dt class="text-slate-400"><?= htmlspecialchars(__('common.contact')) ?></dt><dd class="mt-1 font-medium"><?= htmlspecialchars($client['contact_name']) ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($client['contact_phone'])): ?>
            <div><dt class="text-slate-400"><?= htmlspecialchars(__('common.tel_whatsapp')) ?></dt><dd class="mt-1 font-mono"><?= htmlspecialchars($client['contact_phone']) ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($client['contact_email'])): ?>
            <div><dt class="text-slate-400"><?= htmlspecialchars(__('common.email')) ?></dt><dd class="mt-1"><a href="mailto:<?= htmlspecialchars($client['contact_email']) ?>" class="text-accent-700 hover:underline"><?= htmlspecialchars($client['contact_email']) ?></a></dd></div>
            <?php endif; ?>
        </dl>
        <?php else: ?>
        <p class="mt-4 text-xs text-amber-700 border-t border-slate-100 pt-4"><a href="/clients/<?= $client['id'] ?>/edit" class="underline"><?= htmlspecialchars(__('common.add_phone_email')) ?></a> <?= htmlspecialchars(__('common.for_whatsapp_relance')) ?></p>
        <?php endif; ?>
    </div>
    <div class="action-stack">
        <a href="/clients/<?= $client['id'] ?>/dossier" class="btn btn-primary"><?= htmlspecialchars(__('common.open_ged_folder')) ?></a>
        <a href="/entries/payroll?client=<?= $client['id'] ?>" class="btn btn-secondary"><?= htmlspecialchars(__('common.enter_payroll')) ?></a>
        <a href="/entries/sales?client=<?= $client['id'] ?>" class="btn btn-secondary"><?= htmlspecialchars(__('common.enter_sales')) ?></a>
        <a href="/production?year=<?= date('Y') ?>&month=<?= max(1, (int)date('n')-1) ?>&q=<?= urlencode($client['raison_sociale']) ?>" class="btn btn-secondary"><?= htmlspecialchars(__('common.monthly_production')) ?></a>
        <a href="/clients/<?= $client['id'] ?>/edit" class="btn btn-ghost"><?= htmlspecialchars(__('common.modify_sheet')) ?></a>
        <?php if (!$isArchived): ?>
        <form method="post" action="/clients/<?= $client['id'] ?>/duplicate">
            <button type="submit" class="btn btn-ghost w-full"><?= htmlspecialchars(__('common.duplicate_sheet')) ?></button>
        </form>
        <form method="post" action="/clients/<?= $client['id'] ?>/archive" onsubmit="return confirm(<?= json_encode(__('common.confirm_archive_client')) ?>);">
            <button type="submit" class="btn btn-ghost w-full text-red-600"><?= htmlspecialchars(__('common.archive_client')) ?></button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php
$urgentObs = array_filter($p['obligations'], fn($o) => in_array($o['status'], ['overdue', 'missing_data', 'draft_ready'], true));
$urgentObs = array_slice($urgentObs, 0, 6);
?>
<?php if (!empty($urgentObs)): ?>
<div class="bg-amber-50 border border-amber-100 rounded-2xl p-5 mb-6">
    <h3 class="font-semibold text-amber-900 mb-3"><?= htmlspecialchars(__('common.obligations_to_process')) ?></h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($urgentObs as $ob):
            $row = array_merge($ob, [
                'raison_sociale' => $client['raison_sociale'],
                'client_id' => $client['id'],
                'contact_phone' => $client['contact_phone'] ?? null,
                'contact_email' => $client['contact_email'] ?? null,
            ]);
            $rel = \App\Modules\Relances\RelanceService::linksFor($row);
        ?>
        <div class="bg-white rounded-xl p-4 border border-amber-100/50 text-sm">
            <p class="font-medium"><?= htmlspecialchars($ob['type_label']) ?></p>
            <p class="text-xs text-slate-500"><?= htmlspecialchars($ob['period_label']) ?> — <?= $ob['due_label'] ?></p>
            <span class="inline-block mt-2 text-xs px-2 py-0.5 rounded-full <?= \App\Modules\Automation\DeadlineService::statusColor($ob['status']) ?>"><?= htmlspecialchars($ob['status_label']) ?></span>
            <div class="flex flex-wrap gap-2 mt-3 items-center">
                <?php if ($ob['declaration_id']): ?>
                <a href="/declarations/<?= $ob['declaration_id'] ?>" class="text-accent-600 text-xs hover:underline"><?= htmlspecialchars(__('common.open')) ?></a>
                <?php elseif ($ob['status'] === 'missing_data'): ?>
                <a href="<?= \App\Modules\Automation\WorkflowService::entryUrlForType($ob['type'] ?? 'CNAS_MENSUELLE', (int) $client['id']) ?>" class="text-accent text-xs hover:underline"><?= htmlspecialchars(__('common.enter_data')) ?></a>
                <?php endif; ?>
                <?php require ROOT_PATH . '/templates/_partials/relance_buttons.php'; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($p['cnas_trend'])): ?>
<div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-6">
    <h3 class="font-semibold text-navy-900 mb-4"><?= htmlspecialchars(__('common.cnas_trend')) ?></h3>
    <div class="flex items-end gap-2 h-40">
        <?php $maxMasse = max(array_column($p['cnas_trend'], 'masse')) ?: 1; ?>
        <?php foreach ($p['cnas_trend'] as $bar): ?>
        <div class="flex-1 flex flex-col items-center gap-1 group">
            <div class="w-full flex flex-col justify-end h-32 gap-0.5">
                <div class="w-full bg-accent-200 rounded-t group-hover:bg-accent-300 transition" style="height: <?= round(($bar['cotisations'] / $maxMasse) * 100) ?>%" title="<?= htmlspecialchars(__('common.cotisations')) ?>: <?= number_format($bar['cotisations'], 0, ',', ' ') ?>"></div>
                <div class="w-full bg-navy-200 rounded-t" style="height: <?= round(($bar['masse'] / $maxMasse) * 100) ?>%" title="<?= htmlspecialchars(__('common.payroll_mass')) ?>: <?= number_format($bar['masse'], 0, ',', ' ') ?>"></div>
            </div>
            <span class="text-[10px] text-slate-400 -rotate-45 origin-top"><?= $bar['label'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="flex gap-4 mt-4 text-xs text-slate-500">
        <span class="flex items-center gap-1"><span class="w-3 h-3 bg-navy-200 rounded"></span> <?= htmlspecialchars(__('common.payroll_mass')) ?></span>
        <span class="flex items-center gap-1"><span class="w-3 h-3 bg-accent-200 rounded"></span> <?= htmlspecialchars(__('common.cotisations')) ?></span>
    </div>
</div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
        <h3 class="font-semibold text-navy-900"><?= htmlspecialchars(__('common.obligations_schedule')) ?> — <?= date('Y') ?></h3>
    </div>
    <table class="w-full text-sm">
        <thead class="text-slate-400 text-left">
            <tr><th class="px-6 py-3"><?= htmlspecialchars(__('common.obligation')) ?></th><th class="px-6 py-3"><?= htmlspecialchars(__('common.period')) ?></th><th class="px-6 py-3"><?= htmlspecialchars(__('common.due_date')) ?></th><th class="px-6 py-3"><?= htmlspecialchars(__('common.amount')) ?></th><th class="px-6 py-3"><?= htmlspecialchars(__('common.status')) ?></th><th class="px-6 py-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
            <?php foreach ($p['obligations'] as $ob): ?>
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-3 font-medium"><?= htmlspecialchars($ob['type_label']) ?></td>
                <td class="px-6 py-3 text-slate-500"><?= htmlspecialchars($ob['period_label']) ?></td>
                <td class="px-6 py-3 font-mono text-xs"><?= $ob['due_label'] ?></td>
                <td class="px-6 py-3 font-mono"><?= $ob['amount'] ? number_format($ob['amount'], 2, ',', ' ') . ' ' . __('common.currency') : __('common.unassigned') ?></td>
                <td class="px-6 py-3"><span class="text-xs px-2 py-1 rounded-full <?= \App\Modules\Automation\DeadlineService::statusColor($ob['status']) ?>"><?= $ob['status_label'] ?></span></td>
                <td class="px-6 py-3 text-right">
                    <?php if ($ob['declaration_id']): ?>
                    <a href="/declarations/<?= $ob['declaration_id'] ?>" class="text-accent-600 hover:underline"><?= htmlspecialchars(__('common.review')) ?></a>
                    <?php elseif ($ob['status'] === 'missing_data'): ?>
                    <a href="/entries/payroll?client=<?= $client['id'] ?>" class="text-orange-600 hover:underline"><?= htmlspecialchars(__('common.complete_data')) ?></a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100"><h3 class="font-semibold"><?= htmlspecialchars(__('common.payroll_history')) ?></h3></div>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($p['payroll'] as $pe): ?>
                <tr><td class="px-6 py-3"><?= sprintf('%02d/%d', $pe['period_month'], $pe['period_year']) ?></td>
                    <td class="px-6 py-3 font-mono"><?= number_format((float)$pe['masse_salariale'], 2, ',', ' ') ?> <?= htmlspecialchars(__('common.currency')) ?></td>
                    <td class="px-6 py-3 text-slate-400"><?= $pe['effectif'] ?> <?= htmlspecialchars(__('common.employees_short')) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100"><h3 class="font-semibold"><?= htmlspecialchars(__('common.recent_declarations')) ?></h3></div>
        <div class="divide-y divide-slate-50">
            <?php foreach (array_slice($p['declarations'], 0, 8) as $d): ?>
            <a href="/declarations/<?= $d['id'] ?>" class="flex justify-between px-6 py-3 hover:bg-slate-50 text-sm">
                <span><?= $d['type'] ?> <span class="text-slate-400"><?= $d['status'] ?></span></span>
                <span class="font-mono"><?= number_format($d['computed_fields']['total'] ?? 0, 0, ',', ' ') ?> <?= htmlspecialchars(__('common.currency')) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h3 class="font-semibold text-navy-900 mb-4"><?= htmlspecialchars(__('common.internal_notes')) ?></h3>
        <form method="post" action="/clients/<?= $client['id'] ?>/notes" class="mb-4 flex gap-2 flex-wrap">
            <input name="content" required placeholder="<?= htmlspecialchars(__('common.note_placeholder')) ?>" class="flex-1 min-w-[200px] px-3 py-2 rounded-lg border text-sm">
            <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="pin"> <?= htmlspecialchars(__('common.pin')) ?></label>
            <button type="submit" class="px-4 py-2 bg-accent-600 text-white rounded-lg text-sm">+</button>
        </form>
        <div class="space-y-2 max-h-48 overflow-y-auto">
            <?php foreach ($p['notes'] as $note): ?>
            <div class="p-3 rounded-lg <?= $note['is_pinned'] ? 'bg-amber-50 border border-amber-100' : 'bg-slate-50' ?> text-sm" x-data="{ edit: false }">
                <div x-show="!edit">
                    <p><?= nl2br(htmlspecialchars($note['content'])) ?></p>
                    <div class="flex flex-wrap justify-between items-center gap-2 mt-2">
                        <p class="text-xs text-slate-400"><?= htmlspecialchars($note['author']) ?> — <?= date('d/m/Y H:i', strtotime($note['created_at'])) ?></p>
                        <div class="flex gap-2">
                            <button type="button" @click="edit=true" class="text-xs text-accent-700 hover:underline"><?= htmlspecialchars(__('common.edit')) ?></button>
                            <form method="post" action="/clients/<?= $client['id'] ?>/notes/<?= $note['id'] ?>/delete" class="inline" onsubmit="return confirm(<?= json_encode(__('common.confirm_delete_note')) ?>);">
                                <button type="submit" class="text-xs text-red-600 hover:underline"><?= htmlspecialchars(__('common.delete')) ?></button>
                            </form>
                        </div>
                    </div>
                </div>
                <form x-show="edit" x-cloak method="post" action="/clients/<?= $client['id'] ?>/notes/<?= $note['id'] ?>" class="space-y-2">
                    <textarea name="content" rows="2" class="input w-full text-sm" required><?= htmlspecialchars($note['content']) ?></textarea>
                    <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="pin" value="1" <?= $note['is_pinned'] ? 'checked' : '' ?>> <?= htmlspecialchars(__('common.pin')) ?></label>
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm"><?= htmlspecialchars(__('common.save')) ?></button>
                        <button type="button" @click="edit=false" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('common.cancel')) ?></button>
                    </div>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h3 class="font-semibold text-navy-900 mb-4"><?= htmlspecialchars(__('common.recent_activity')) ?></h3>
        <div class="space-y-2 max-h-64 overflow-y-auto text-sm">
            <?php if (empty($p['activity'])): ?>
            <p class="text-slate-400"><?= htmlspecialchars(__('common.no_activity')) ?></p>
            <?php else: foreach ($p['activity'] as $act): ?>
            <div class="flex gap-3 py-2 border-b border-slate-50">
                <span class="text-xs px-2 py-0.5 bg-slate-100 rounded h-fit"><?= $act['action'] ?></span>
                <div>
                    <p class="text-slate-600"><?= $act['entity'] ?> #<?= $act['entity_id'] ?? '' ?></p>
                    <p class="text-xs text-slate-400"><?= htmlspecialchars($act['user_name'] ?? '') ?> — <?= date('d/m H:i', strtotime($act['created_at'])) ?></p>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
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
