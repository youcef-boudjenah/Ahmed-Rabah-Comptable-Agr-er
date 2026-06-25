<div class="flex gap-2 mb-6">
    <a href="/echeancier?view=list" class="px-4 py-2 rounded-lg text-sm <?= ($view ?? 'list') === 'list' ? 'bg-navy-900 text-white' : 'bg-slate-100' ?>"><?= htmlspecialchars(__('common.list')) ?></a>
    <a href="/echeancier?view=calendar" class="px-4 py-2 rounded-lg text-sm <?= ($view ?? '') === 'calendar' ? 'bg-navy-900 text-white' : 'bg-slate-100' ?>"><?= htmlspecialchars(__('common.calendar')) ?></a>
</div>

<?php if (($view ?? 'list') === 'calendar'): ?>
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    <?php if (empty($calendar)): ?>
    <p class="text-slate-400 col-span-full py-12 text-center"><?= htmlspecialchars(__('echeancier.no_deadlines')) ?></p>
    <?php else: foreach ($calendar as $date => $items): ?>
    <div class="bg-white rounded-xl border border-slate-100 p-4 <?= strtotime($date) < strtotime('today') ? 'border-red-200 bg-red-50/30' : '' ?>">
        <p class="font-mono font-bold text-sm mb-3"><?= date('d/m/Y', strtotime($date)) ?></p>
        <?php foreach ($items as $item): ?>
        <div class="text-sm mb-2 p-2 rounded-lg bg-slate-50">
            <p class="font-medium"><?= htmlspecialchars($item['raison_sociale']) ?></p>
            <p class="text-xs text-slate-500"><?= $item['type_label'] ?> — <?= $item['status_label'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; endif; ?>
</div>
<?php else: ?>
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-navy-900 text-white text-left">
            <tr>
                <th class="px-6 py-4"><?= htmlspecialchars(__('common.deadline')) ?></th>
                <th class="px-6 py-4"><?= htmlspecialchars(__('common.client')) ?></th>
                <th class="px-6 py-4"><?= htmlspecialchars(__('common.obligation')) ?></th>
                <th class="px-6 py-4"><?= htmlspecialchars(__('common.period')) ?></th>
                <th class="px-6 py-4"><?= htmlspecialchars(__('common.amount')) ?></th>
                <th class="px-6 py-4"><?= htmlspecialchars(__('common.status')) ?></th>
                <th class="px-6 py-4"><?= htmlspecialchars(__('common.action')) ?></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($upcoming as $item): ?>
            <tr class="hover:bg-slate-50 <?= $item['status'] === 'overdue' ? 'bg-red-50/50' : '' ?>">
                <td class="px-6 py-4 font-mono">
                    <?= $item['due_label'] ?>
                    <?php if ($item['days_left'] < 0): ?><span class="block text-red-600 text-xs"><?= htmlspecialchars(__('common.days_late', ['n' => abs($item['days_left'])])) ?></span><?php endif; ?>
                </td>
                <td class="px-6 py-4">
                    <a href="/clients/<?= $item['client_id'] ?>" class="font-medium hover:text-accent-600"><?= htmlspecialchars($item['raison_sociale']) ?></a>
                    <span class="block text-xs text-slate-400"><?= $item['secteur'] ?></span>
                </td>
                <td class="px-6 py-4"><?= htmlspecialchars($item['type_label']) ?></td>
                <td class="px-6 py-4 text-slate-500"><?= htmlspecialchars($item['period_label']) ?></td>
                <td class="px-6 py-4 font-mono"><?= $item['amount'] ? number_format($item['amount'], 2, ',', ' ') . ' ' . __('common.currency') : __('common.unassigned') ?></td>
                <td class="px-6 py-4">
                    <span class="text-xs px-2.5 py-1 rounded-full <?= \App\Modules\Automation\DeadlineService::statusColor($item['status']) ?>">
                        <?= htmlspecialchars($item['status_label']) ?>
                    </span>
                </td>
                <td class="px-6 py-4">
                    <?php if ($item['declaration_id']): ?>
                    <a href="/declarations/<?= $item['declaration_id'] ?>" class="text-accent-600 font-medium hover:underline"><?= htmlspecialchars(__('common.review_arrow')) ?></a>
                    <?php elseif ($item['status'] === 'missing_data'): ?>
                    <a href="<?= \App\Modules\Automation\WorkflowService::entryUrlForType($item['type'] ?? 'CNAS_MENSUELLE', (int) $item['client_id']) ?>" class="text-orange-600 font-medium hover:underline"><?= htmlspecialchars(__('echeancier.enter_data')) ?></a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
