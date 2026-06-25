<?php $ds = $deadlineStats; $b = $briefing ?? null; ?>
<?php if (!empty($highlightRun)): ?>
<?php $run = $highlightRun; require ROOT_PATH . '/templates/automation/_run_report.php'; ?>
<?php endif; ?>

<?php if ($b): ?>
<div class="card-elevated mb-6">
    <div class="card-body">
        <div class="flex flex-wrap justify-between gap-4 items-start">
            <div class="flex-1 min-w-0">
                <p class="eyebrow"><?= htmlspecialchars(__('dashboard.briefing')) ?> — <?= date('d/m/Y') ?></p>
                <h2 class="text-lg font-semibold text-slate-900 mt-1"><?= htmlspecialchars(__('dashboard.production')) ?> <?= htmlspecialchars($b['production']['month_label']) ?></h2>
                <div class="mt-3 max-w-md">
                    <div class="flex justify-between text-xs text-slate-600 mb-1">
                        <span><?= htmlspecialchars(__('dashboard.declarations_filed')) ?></span>
                        <span class="font-semibold"><?= $b['progress'] ?>%</span>
                    </div>
                    <div class="progress-bar h-2"><span style="width:<?= $b['progress'] ?>%; background: var(--accent);"></span></div>
                </div>
            </div>
            <a href="/production?year=<?= $b['prod_year'] ?>&month=<?= $b['prod_month'] ?>" class="btn btn-primary"><?= htmlspecialchars(__('dashboard.open_production')) ?></a>
        </div>
        <ul class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-2">
            <?php foreach ($b['actions'] as $action): ?>
            <li>
                <a href="<?= htmlspecialchars($action['url']) ?>" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:border-accent-500 hover:bg-accent-50/50 transition text-sm">
                    <span class="badge <?= match($action['priority']) { 'critical' => 'badge-danger', 'high' => 'badge-warning', default => 'badge-neutral' } ?>"><?= match($action['priority']) { 'critical' => '!', 'high' => '→', default => '·' } ?></span>
                    <span class="font-medium text-slate-800"><?= htmlspecialchars($action['label']) ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    <div class="card xl:col-span-2 overflow-hidden">
        <div class="card-header">
            <h2><?= htmlspecialchars(__('dashboard.priorities_30')) ?></h2>
            <a href="/production" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('nav.production')) ?> →</a>
        </div>
        <div class="table-scroll" style="max-height:none">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?= htmlspecialchars(__('common.client')) ?></th>
                        <th><?= htmlspecialchars(__('common.obligation')) ?></th>
                        <th><?= htmlspecialchars(__('common.due_date')) ?></th>
                        <th><?= htmlspecialchars(__('common.status')) ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ds['upcoming'])): ?>
                    <tr><td colspan="5" class="text-center text-slate-500 py-10"><?= htmlspecialchars(__('dashboard.nothing_urgent')) ?> <a href="/production" class="text-accent-700 hover:underline"><?= htmlspecialchars(__('common.see_production')) ?></a></td></tr>
                    <?php else: foreach (array_slice($ds['upcoming'], 0, 12) as $item): ?>
                    <tr>
                        <td class="font-medium"><?= htmlspecialchars($item['raison_sociale']) ?></td>
                        <td>
                            <span><?= htmlspecialchars($item['type_label']) ?></span>
                            <span class="block text-xs text-slate-500"><?= htmlspecialchars($item['period_label']) ?></span>
                        </td>
                        <td class="font-mono text-xs"><?= $item['due_label'] ?></td>
                        <td><span class="badge <?= ($item['status'] ?? '') === 'overdue' ? 'badge-danger' : (($item['status'] ?? '') === 'missing_data' ? 'badge-warning' : 'badge-neutral') ?>"><?= htmlspecialchars($item['status_label']) ?></span></td>
                        <td class="text-right">
                            <?php if ($item['declaration_id']): ?>
                            <a href="/declarations/<?= $item['declaration_id'] ?>" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('common.open')) ?></a>
                            <?php elseif ($item['status'] === 'missing_data'): ?>
                            <a href="<?= \App\Modules\Automation\WorkflowService::entryUrlForType($item['type'] ?? 'CNAS_MENSUELLE', (int) $item['client_id']) ?>" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('common.enter_data')) ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-5">
        <div class="card">
            <div class="card-header"><h3><?= htmlspecialchars(__('common.alerts')) ?></h3></div>
            <div class="card-body pt-0 divide-y divide-slate-100 max-h-52 overflow-y-auto">
                <?php if (empty($alerts)): ?>
                <p class="text-sm text-slate-500 py-4"><?= htmlspecialchars(__('common.no_alerts')) ?></p>
                <?php else: foreach (array_slice($alerts, 0, 6) as $a): ?>
                <div class="py-3 flex gap-2 items-start text-sm">
                    <p class="flex-1 text-slate-700"><?= htmlspecialchars($a['message_fr']) ?></p>
                    <form method="post" action="/alerts/<?= $a['id'] ?>/read"><button type="submit" class="text-xs text-slate-400 hover:text-accent-700"><?= htmlspecialchars(__('common.mark_read')) ?></button></form>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3><?= htmlspecialchars(__('dashboard.tasks')) ?></h3><a href="/tasks" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('common.all_view')) ?></a></div>
            <div class="card-body pt-0 divide-y divide-slate-100">
                <?php if (empty($tasks)): ?>
                <p class="text-sm text-slate-500 py-4"><?= htmlspecialchars(__('common.no_tasks')) ?></p>
                <?php else: foreach (array_slice($tasks, 0, 5) as $t): ?>
                <div class="flex items-center gap-2 py-2.5 text-sm">
                    <form method="post" action="/tasks/<?= $t['id'] ?>/complete"><input type="hidden" name="redirect" value="/"><button type="submit" class="w-4 h-4 rounded border-2 border-slate-300 hover:border-accent-600" title="<?= htmlspecialchars(__('common.complete')) ?>"></button></form>
                    <span class="truncate flex-1"><?= htmlspecialchars($t['title']) ?></span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>
