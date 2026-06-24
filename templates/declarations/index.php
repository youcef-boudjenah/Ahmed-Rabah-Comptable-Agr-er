<?php
$kanbanLabels = [
    'DRAFT_CALCULATED' => ['Brouillons', 'bg-amber-50 border-amber-200', 'bg-amber-100 text-amber-800'],
    'APPROVED' => ['Approuvées', 'bg-teal-50 border-teal-200', 'bg-teal-100 text-teal-800'],
    'SUBMITTED' => ['Déposées', 'bg-slate-50 border-slate-200', 'bg-slate-100 text-slate-700'],
];
$view = $view ?? 'list';
$statusQuery = $filterStatus ? '?status=' . urlencode($filterStatus) : '';
?>
<div class="flex flex-wrap justify-between items-center gap-4 mb-6">
    <div class="flex gap-2 flex-wrap">
        <a href="/declarations" class="px-3 py-1.5 rounded-lg text-sm <?= !$filterStatus ? 'bg-navy-900 text-white' : 'bg-slate-100' ?>">Toutes</a>
        <a href="/declarations?status=DRAFT_CALCULATED" class="px-3 py-1.5 rounded-lg text-sm <?= $filterStatus === 'DRAFT_CALCULATED' ? 'bg-amber-500 text-white' : 'bg-slate-100' ?>">Brouillons</a>
        <a href="/declarations?status=APPROVED" class="px-3 py-1.5 rounded-lg text-sm <?= $filterStatus === 'APPROVED' ? 'bg-teal-600 text-white' : 'bg-slate-100' ?>">Approuvées</a>
        <a href="/declarations?status=SUBMITTED" class="px-3 py-1.5 rounded-lg text-sm <?= $filterStatus === 'SUBMITTED' ? 'bg-navy-700 text-white' : 'bg-slate-100' ?>">Déposées</a>
    </div>
    <div class="flex gap-2">
        <a href="/declarations<?= $statusQuery ?><?= $statusQuery ? '&' : '?' ?>view=list" class="px-3 py-1.5 rounded-lg text-xs <?= $view === 'list' ? 'bg-navy-900 text-white' : 'bg-slate-100' ?>">Liste</a>
        <a href="/declarations?view=kanban" class="px-3 py-1.5 rounded-lg text-xs <?= $view === 'kanban' ? 'bg-navy-900 text-white' : 'bg-slate-100' ?>">Kanban</a>
    </div>
</div>

<?php if ($view === 'kanban'): ?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <?php foreach ($kanbanLabels as $status => [$label, $colBg, $badge]): ?>
    <div class="rounded-2xl border p-4 min-h-[400px] <?= $colBg ?>">
        <h3 class="font-semibold text-sm mb-3 flex justify-between">
            <?= $label ?>
            <span class="text-xs px-2 py-0.5 rounded-full <?= $badge ?>"><?= count($kanban[$status] ?? []) ?></span>
        </h3>
        <?php foreach ($kanban[$status] ?? [] as $d):
            $cf = $d['computed_fields'];
            $period = $d['period_year'];
            if ($d['period_month']) $period .= '/' . str_pad((string)$d['period_month'], 2, '0', STR_PAD_LEFT);
            if ($d['period_quarter']) $period .= ' T' . $d['period_quarter'];
        ?>
        <a href="/declarations/<?= $d['id'] ?>" class="block bg-white rounded-xl p-4 mb-3 border border-white shadow-sm hover:shadow-md transition">
            <p class="font-mono text-teal-700 text-xs"><?= htmlspecialchars($d['type']) ?></p>
            <p class="font-medium text-sm mt-1"><?= htmlspecialchars($d['raison_sociale']) ?></p>
            <p class="text-xs text-slate-400 mt-1"><?= $period ?></p>
            <p class="font-mono font-bold text-navy-900 mt-2"><?= number_format($cf['total'] ?? 0, 0, ',', ' ') ?> DA</p>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr>
                <th class="text-left px-6 py-3">Type</th>
                <th class="text-left px-6 py-3">Client</th>
                <th class="text-left px-6 py-3">Période</th>
                <th class="text-right px-6 py-3">Total</th>
                <th class="text-left px-6 py-3">Statut</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
            <?php if (empty($declarations)): ?>
            <tr><td colspan="6" class="px-6 py-12 text-center text-slate-400">Aucune déclaration.</td></tr>
            <?php else: foreach ($declarations as $d):
                $cf = $d['computed_fields'];
                $period = $d['period_year'];
                if ($d['period_month']) $period .= '/' . str_pad((string)$d['period_month'], 2, '0', STR_PAD_LEFT);
                if ($d['period_quarter']) $period .= ' T' . $d['period_quarter'];
            ?>
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 font-mono text-teal-700"><?= htmlspecialchars($d['type']) ?></td>
                <td class="px-6 py-4"><?= htmlspecialchars($d['raison_sociale']) ?></td>
                <td class="px-6 py-4"><?= $period ?></td>
                <td class="px-6 py-4 text-right font-mono font-medium"><?= number_format($cf['total'] ?? 0, 2, ',', ' ') ?> DA</td>
                <td class="px-6 py-4">
                    <span class="text-xs px-2 py-1 rounded-full <?= match($d['status']) {
                        'DRAFT_CALCULATED' => 'bg-amber-100 text-amber-700',
                        'APPROVED' => 'bg-teal-100 text-teal-700',
                        default => 'bg-slate-100 text-slate-600'
                    } ?>"><?= $d['status'] ?></span>
                </td>
                <td class="px-6 py-4 text-right"><a href="/declarations/<?= $d['id'] ?>" class="text-teal-600 hover:underline">Ouvrir</a></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
