<?php
/** @var array<string, mixed>|null $nextStep */
/** @var array<int, array<string, mixed>> $gedDocs */
$nextStep = $nextStep ?? null;
$gedDocs = $gedDocs ?? [];
$steps = [
    1 => ['label' => __('common.workflow_step_entry'), 'desc' => __('common.workflow_step_entry_desc')],
    2 => ['label' => __('common.workflow_step_calc'), 'desc' => __('common.workflow_step_calc_desc')],
    3 => ['label' => __('common.workflow_step_approve'), 'desc' => __('common.workflow_step_approve_desc')],
    4 => ['label' => __('common.workflow_step_bordereau'), 'desc' => __('common.workflow_step_bordereau_desc')],
    5 => ['label' => __('common.workflow_step_deposit'), 'desc' => __('common.workflow_step_deposit_desc')],
];
$current = $nextStep['step'] ?? 1;
?>
<div class="card mb-6">
    <div class="card-header">
        <h3><?= htmlspecialchars(__('common.workflow_title')) ?></h3>
        <?php if ($nextStep): ?>
        <span class="badge badge-info"><?= htmlspecialchars(__('common.workflow_step', ['current' => (string) $current, 'total' => (string) $nextStep['total']])) ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="flex gap-1 mb-4">
            <?php foreach ($steps as $num => $s): ?>
            <div class="flex-1 text-center">
                <div class="h-1.5 rounded-full mb-1 <?= $num <= $current ? 'bg-accent' : 'bg-slate-200' ?>"></div>
                <p class="text-[10px] font-medium <?= $num <= $current ? 'text-slate-800' : 'text-slate-400' ?>"><?= htmlspecialchars($s['label']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($nextStep): ?>
        <div class="p-4 rounded-md bg-accent-muted border border-blue-100">
            <p class="font-semibold text-sm text-slate-900"><?= htmlspecialchars($nextStep['label']) ?></p>
            <p class="text-sm text-slate-600 mt-1"><?= htmlspecialchars($nextStep['description']) ?></p>
            <?php if ($nextStep['action_method'] === 'get'): ?>
            <a href="<?= htmlspecialchars($nextStep['action_url']) ?>" class="btn btn-primary btn-sm mt-3"><?= htmlspecialchars($nextStep['action_label']) ?></a>
            <?php elseif ($nextStep['action_method'] === 'anchor'): ?>
            <a href="<?= htmlspecialchars($nextStep['action_url']) ?>" class="btn btn-primary btn-sm mt-3"><?= htmlspecialchars($nextStep['action_label']) ?></a>
            <?php elseif ($nextStep['action_method'] === 'post'): ?>
            <form method="post" action="<?= htmlspecialchars($nextStep['action_url']) ?>" class="mt-3 inline">
                <button type="submit" class="btn btn-primary btn-sm"><?= htmlspecialchars($nextStep['action_label']) ?></button>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($gedDocs)): ?>
        <div class="mt-4 pt-4 border-t border-slate-100">
            <p class="text-xs font-semibold uppercase text-slate-400 mb-2"><?= htmlspecialchars(__('common.workflow_ged_docs')) ?></p>
            <ul class="space-y-1 text-sm">
                <?php foreach ($gedDocs as $doc): ?>
                <li class="flex justify-between items-center">
                    <span><?= htmlspecialchars($doc['title'] ?? $doc['original_name']) ?></span>
                    <a href="/documents/<?= $doc['id'] ?>/download" target="_blank" class="text-xs text-accent hover:underline"><?= htmlspecialchars(__('common.view')) ?></a>
                </li>
                <?php endforeach; ?>
            </ul>
            <a href="/clients/<?= (int) ($declaration['client_id'] ?? 0) ?>/dossier" class="text-xs text-accent hover:underline mt-2 inline-block"><?= htmlspecialchars(__('common.see_complete_folder')) ?></a>
        </div>
        <?php endif; ?>
    </div>
</div>
