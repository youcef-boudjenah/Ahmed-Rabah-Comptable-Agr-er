<?php
$kanbanLabels = [
    'DRAFT_CALCULATED' => [__('common.decl_drafts'), 'bg-amber-50 border-amber-200', 'bg-amber-100 text-amber-800'],
    'APPROVED' => [__('common.decl_approved'), 'bg-accent-50 border-accent-200', 'bg-accent-100 text-accent-800'],
    'SUBMITTED' => [__('common.decl_submitted'), 'bg-slate-50 border-slate-200', 'bg-slate-100 text-slate-700'],
];
$view = $view ?? 'list';
$statusQuery = $filterStatus ? '?status=' . urlencode($filterStatus) : '';
?>
<div class="flex flex-wrap justify-between items-center gap-4 mb-6">
    <div class="flex gap-2 flex-wrap">
        <a href="/declarations" class="px-3 py-1.5 rounded-lg text-sm <?= !$filterStatus ? 'bg-navy-900 text-white' : 'bg-slate-100' ?>"><?= htmlspecialchars(__('common.decl_all')) ?></a>
        <a href="/declarations?status=DRAFT_CALCULATED" class="px-3 py-1.5 rounded-lg text-sm <?= $filterStatus === 'DRAFT_CALCULATED' ? 'bg-amber-500 text-white' : 'bg-slate-100' ?>"><?= htmlspecialchars(__('common.decl_drafts')) ?></a>
        <a href="/declarations?status=APPROVED" class="px-3 py-1.5 rounded-lg text-sm <?= $filterStatus === 'APPROVED' ? 'bg-accent-600 text-white' : 'bg-slate-100' ?>"><?= htmlspecialchars(__('common.decl_approved')) ?></a>
        <a href="/declarations?status=SUBMITTED" class="px-3 py-1.5 rounded-lg text-sm <?= $filterStatus === 'SUBMITTED' ? 'bg-navy-700 text-white' : 'bg-slate-100' ?>"><?= htmlspecialchars(__('common.decl_submitted')) ?></a>
    </div>
    <div class="flex gap-2">
        <a href="/declarations<?= $statusQuery ?><?= $statusQuery ? '&' : '?' ?>view=list" class="px-3 py-1.5 rounded-lg text-xs <?= $view === 'list' ? 'bg-navy-900 text-white' : 'bg-slate-100' ?>"><?= htmlspecialchars(__('common.list')) ?></a>
        <a href="/declarations?view=kanban" class="px-3 py-1.5 rounded-lg text-xs <?= $view === 'kanban' ? 'bg-navy-900 text-white' : 'bg-slate-100' ?>"><?= htmlspecialchars(__('common.kanban')) ?></a>
    </div>
</div>

<?php if ($view === 'kanban'): ?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <?php foreach ($kanbanLabels as $status => [$label, $colBg, $badge]): ?>
    <div class="rounded-2xl border p-4 min-h-[400px] <?= $colBg ?>">
        <h3 class="font-semibold text-sm mb-3 flex justify-between">
            <?= htmlspecialchars($label) ?>
            <span class="text-xs px-2 py-0.5 rounded-full <?= $badge ?>"><?= count($kanban[$status] ?? []) ?></span>
        </h3>
        <?php foreach ($kanban[$status] ?? [] as $d):
            $cf = $d['computed_fields'];
            $period = $d['period_year'];
            if ($d['period_month']) $period .= '/' . str_pad((string)$d['period_month'], 2, '0', STR_PAD_LEFT);
            if ($d['period_quarter']) $period .= ' T' . $d['period_quarter'];
        ?>
        <a href="/declarations/<?= $d['id'] ?>" class="block bg-white rounded-xl p-4 mb-3 border border-white shadow-sm hover:shadow-md transition">
            <p class="font-mono text-accent-700 text-xs"><?= htmlspecialchars($d['type']) ?></p>
            <p class="font-medium text-sm mt-1"><?= htmlspecialchars($d['raison_sociale']) ?></p>
            <p class="text-xs text-slate-400 mt-1"><?= $period ?></p>
            <p class="font-mono font-bold text-navy-900 mt-2"><?= number_format($cf['total'] ?? 0, 0, ',', ' ') ?> <?= htmlspecialchars(__('common.currency')) ?></p>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>

<form method="post" action="/declarations/bulk" id="decl-bulk-form">
    <input type="hidden" name="redirect" value="/declarations<?= $filterStatus ? '?status=' . urlencode($filterStatus) : '' ?>">
    <?php if (!$filterStatus || $filterStatus === 'DRAFT_CALCULATED'): ?>
    <div class="card mb-3">
        <div class="card-body py-2 flex flex-wrap gap-2 items-center">
            <span class="text-xs text-slate-500"><?= htmlspecialchars(__('common.decl_select_drafts')) ?></span>
            <select name="bulk_action" class="select text-xs py-1">
                <option value="delete"><?= htmlspecialchars(__('common.delete')) ?></option>
                <?php if (\App\Core\Auth::canApprove()): ?>
                <option value="approve"><?= htmlspecialchars(__('common.approve')) ?></option>
                <?php endif; ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm" onclick="return document.querySelectorAll('.decl-check:checked').length > 0;"><?= htmlspecialchars(__('common.apply')) ?></button>
        </div>
    </div>
    <?php endif; ?>

<div class="card overflow-hidden">
    <div class="table-scroll">
    <table class="data-table">
        <thead>
            <tr>
                <?php if (!$filterStatus || $filterStatus === 'DRAFT_CALCULATED'): ?><th width="32"></th><?php endif; ?>
                <th><?= htmlspecialchars(__('common.type')) ?></th>
                <th><?= htmlspecialchars(__('common.client')) ?></th>
                <th><?= htmlspecialchars(__('common.period')) ?></th>
                <th class="text-right"><?= htmlspecialchars(__('common.total')) ?></th>
                <th><?= htmlspecialchars(__('common.status')) ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($declarations)): ?>
            <tr><td colspan="7" class="text-center py-12 text-slate-500"><?= htmlspecialchars(__('common.decl_no_declaration')) ?></td></tr>
            <?php else: foreach ($declarations as $d):
                $cf = $d['computed_fields'];
                $period = $d['period_year'];
                if ($d['period_month']) $period .= '/' . str_pad((string)$d['period_month'], 2, '0', STR_PAD_LEFT);
                if ($d['period_quarter']) $period .= ' T' . $d['period_quarter'];
            ?>
            <tr>
                <?php if (!$filterStatus || $filterStatus === 'DRAFT_CALCULATED'): ?>
                <td><?php if ($d['status'] === 'DRAFT_CALCULATED'): ?><input type="checkbox" class="decl-check" name="ids[]" value="<?= $d['id'] ?>" form="decl-bulk-form"><?php endif; ?></td>
                <?php endif; ?>
                <td class="font-mono text-accent-700 text-sm"><?= htmlspecialchars($d['type']) ?></td>
                <td><?= htmlspecialchars($d['raison_sociale']) ?></td>
                <td class="text-slate-600"><?= $period ?></td>
                <td class="text-right font-mono font-medium"><?= number_format($cf['total'] ?? 0, 2, ',', ' ') ?> <?= htmlspecialchars(__('common.currency')) ?></td>
                <td>
                    <span class="badge <?= match($d['status']) {
                        'DRAFT_CALCULATED' => 'badge-warning',
                        'APPROVED' => 'badge-success',
                        default => 'badge-neutral'
                    } ?>"><?= $d['status'] ?></span>
                </td>
                <td class="text-right whitespace-nowrap">
                    <a href="/declarations/<?= $d['id'] ?>" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('common.open')) ?></a>
                    <?php if ($d['status'] === 'DRAFT_CALCULATED'): ?>
                    <form method="post" action="/declarations/<?= $d['id'] ?>/delete" class="inline" onsubmit="return confirm(<?= json_encode(__('common.confirm_delete_draft')) ?>);">
                        <button type="submit" class="btn btn-ghost btn-sm text-red-600"><?= htmlspecialchars(__('common.decl_delete_short')) ?></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>
</form>
<?php endif; ?>
