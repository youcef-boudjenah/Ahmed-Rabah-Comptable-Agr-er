<?php
$active = $activeSection ?? 'demarrage';
$current = null;
foreach ($sections as $s) {
    if ($s['id'] === $active) {
        $current = $s;
        break;
    }
}
$current = $current ?? $sections[0];
?>
<div class="page-intro flex flex-wrap justify-between items-start gap-4 mb-6">
    <div>
        <p class="eyebrow"><?= htmlspecialchars(__('help.eyebrow')) ?></p>
        <h2><?= htmlspecialchars(__('help.title')) ?></h2>
        <p><?= htmlspecialchars(__('help.subtitle')) ?></p>
    </div>
    <a href="/logs" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('common.activity_logs_link')) ?></a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-5">
    <nav class="card p-3 h-fit lg:sticky lg:top-20">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 px-2 py-2"><?= htmlspecialchars(__('help.toc')) ?></p>
        <ul class="space-y-0.5">
            <?php foreach ($sections as $s): ?>
            <li>
                <a href="/aide?section=<?= urlencode($s['id']) ?>"
                   class="block px-3 py-2 rounded-md text-sm <?= $s['id'] === $active ? 'bg-accent-50 text-accent-800 font-medium' : 'text-slate-600 hover:bg-slate-50' ?>">
                    <?= htmlspecialchars($s['title']) ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="lg:col-span-3 space-y-5">
        <div class="card">
            <div class="card-header"><h3><?= htmlspecialchars($current['title']) ?></h3></div>
            <div class="card-body space-y-4">
                <?php foreach ($current['steps'] as $i => $step): ?>
                <div class="flex gap-4">
                    <span class="flex-shrink-0 w-8 h-8 rounded-full bg-slate-100 text-slate-600 text-sm font-semibold flex items-center justify-center"><?= $i + 1 ?></span>
                    <div>
                        <p class="font-medium text-slate-900"><?= htmlspecialchars($step['label']) ?></p>
                        <p class="text-sm text-slate-600 mt-1 leading-relaxed"><?= htmlspecialchars($step['text']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($active === 'demarrage'): ?>
        <div class="card border-accent-200 bg-accent-50/30">
            <div class="card-body">
                <h4 class="font-semibold text-accent-900 mb-2"><?= htmlspecialchars(__('common.month_end_title')) ?></h4>
                <ol class="text-sm text-accent-900/90 space-y-2 list-decimal list-inside">
                    <li><?= htmlspecialchars(__('common.month_end_1')) ?></li>
                    <li><?= htmlspecialchars(__('common.month_end_2')) ?></li>
                    <li><?= htmlspecialchars(__('help.step_production')) ?></li>
                    <li><?= htmlspecialchars(__('common.month_end_4')) ?></li>
                    <li><?= htmlspecialchars(__('common.month_end_5')) ?></li>
                    <li><?= htmlspecialchars(__('common.month_end_6')) ?></li>
                </ol>
                <a href="/production" class="btn btn-primary btn-sm mt-4"><?= htmlspecialchars(__('common.open_production_link')) ?></a>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><h3><?= htmlspecialchars(__('help.faq')) ?></h3></div>
            <div class="card-body divide-y divide-slate-100">
                <?php foreach ($faq as $item): ?>
                <details class="py-3 group">
                    <summary class="font-medium text-sm text-slate-800 cursor-pointer list-none flex justify-between items-center">
                        <?= htmlspecialchars($item['q']) ?>
                        <span class="text-slate-400 text-xs group-open:rotate-180 transition">▼</span>
                    </summary>
                    <p class="text-sm text-slate-600 mt-2 pl-0"><?= htmlspecialchars($item['a']) ?></p>
                </details>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
