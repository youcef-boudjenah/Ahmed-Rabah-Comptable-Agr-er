<?php
$d = $data ?? [];
$year = (int)($d['year'] ?? date('Y'));
$byType = $d['by_type'] ?? [];
$byMonth = $d['by_month'] ?? [];
$statusBreakdown = $d['status_breakdown'] ?? [];
$topClients = $d['top_clients'] ?? [];
?>
<div class="page-intro flex flex-wrap justify-between items-start gap-4">
    <div>
        <p class="eyebrow"><?= htmlspecialchars(__('reports.eyebrow')) ?></p>
        <h2><?= htmlspecialchars(__('reports.title')) ?></h2>
        <p><?= htmlspecialchars(__('common.exercise_year', ['year' => $year])) ?></p>
    </div>
    <a href="/logs" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('common.activity_logs_link')) ?></a>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-hero md:col-span-1">
        <p class="label"><?= htmlspecialchars(__('common.total_contributions_year', ['year' => $year])) ?></p>
        <p class="value font-mono"><?= number_format((float)($d['total_ytd'] ?? 0), 0, ',', ' ') ?> <span class="text-lg font-semibold opacity-80"><?= htmlspecialchars(__('common.currency')) ?></span></p>
    </div>
    <?php foreach ($statusBreakdown as $s): ?>
    <div class="mini-stat">
        <p class="label"><?= htmlspecialchars($s['status']) ?></p>
        <p class="value"><?= (int)$s['c'] ?></p>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="card-elevated">
        <div class="card-header">
            <h3><?= htmlspecialchars(__('reports.by_type')) ?></h3>
        </div>
        <div class="card-body">
            <?php if (empty($byType)): ?>
            <p class="text-sm text-slate-500"><?= htmlspecialchars(__('reports.no_data_year')) ?></p>
            <?php else: ?>
            <?php $maxType = max(array_column($byType, 'total') ?: [1]); ?>
            <?php foreach ($byType as $row): ?>
            <div class="mb-4 last:mb-0">
                <div class="flex justify-between text-sm mb-1.5">
                    <span class="font-mono font-semibold text-accent-800"><?= htmlspecialchars($row['type']) ?></span>
                    <span class="font-mono font-medium text-slate-800"><?= number_format((float)$row['total'], 0, ',', ' ') ?> <?= htmlspecialchars(__('common.currency')) ?></span>
                </div>
                <div class="chart-bar-track">
                    <div class="chart-bar-fill" style="width:<?= min(100, round(($row['total']/$maxType)*100)) ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-elevated">
        <div class="card-header">
            <h3><?= htmlspecialchars(__('common.monthly_evolution_year', ['year' => $year])) ?></h3>
        </div>
        <div class="card-body">
            <div class="bar-chart">
                <?php
                $monthMap = array_column($byMonth, 'total', 'm');
                $maxM = max($monthMap ?: [1]);
                for ($m = 1; $m <= 12; $m++):
                    $val = (float)($monthMap[$m] ?? 0);
                    $pct = $maxM > 0 ? max(2, round(($val/$maxM)*100)) : 2;
                ?>
                <div class="bar-chart-col">
                    <div class="bar-chart-bar" style="height: <?= $pct ?>%" title="<?= number_format($val, 0, ',', ' ') ?> <?= htmlspecialchars(__('common.currency')) ?>"></div>
                    <span class="bar-chart-label"><?= htmlspecialchars(__("common.month_short_{$m}")) ?></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card">
        <div class="card-header">
            <h3><?= htmlspecialchars(__('reports.top_clients')) ?></h3>
        </div>
        <div class="table-scroll" style="max-height: none;">
            <table class="data-table">
                <thead>
                    <tr><th><?= htmlspecialchars(__('common.client')) ?></th><th class="text-right"><?= htmlspecialchars(__('common.amount')) ?></th></tr>
                </thead>
                <tbody>
                    <?php if (empty($topClients)): ?>
                    <tr><td colspan="2" class="text-center text-slate-500 py-8"><?= htmlspecialchars(__('reports.no_clients')) ?></td></tr>
                    <?php else: ?>
                    <?php foreach ($topClients as $c): ?>
                    <tr>
                        <td><a href="/clients/<?= $c['id'] ?>" class="font-medium text-slate-800 hover:text-accent-700"><?= htmlspecialchars($c['raison_sociale']) ?></a></td>
                        <td class="text-right font-mono font-medium text-slate-800"><?= number_format((float)$c['total'], 0, ',', ' ') ?> <?= htmlspecialchars(__('common.currency')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><?= htmlspecialchars(__('reports.ged_processing')) ?></h3>
        </div>
        <div class="card-body">
            <div class="flex gap-3">
                <div class="ged-stat-box orange">
                    <p class="num"><?= (int)($d['ged']['a_traiter'] ?? 0) ?></p>
                    <p class="lbl"><?= htmlspecialchars(__('ged.stat_pending')) ?></p>
                </div>
                <div class="ged-stat-box teal">
                    <p class="num"><?= (int)($d['ged']['traite'] ?? 0) ?></p>
                    <p class="lbl"><?= htmlspecialchars(__('ged.stat_done')) ?></p>
                </div>
                <div class="ged-stat-box neutral">
                    <p class="num"><?= (int)($d['ged']['total'] ?? 0) ?></p>
                    <p class="lbl"><?= htmlspecialchars(__('ged.stat_total')) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
