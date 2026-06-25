<?php
$importYear = $defaultYear ?? (int) date('Y');
$importMonth = $defaultMonth ?? max(1, (int) date('n') - 1);
$redirect = $redirect ?? '/production';
$periodLabel = (__("common.month_{$importMonth}") ?: $importMonth) . ' ' . $importYear;
?>
<div class="max-w-3xl">
    <div class="page-intro mb-6">
        <p class="eyebrow"><?= htmlspecialchars(__('entries.import_eyebrow')) ?></p>
        <h2><?= htmlspecialchars(__('entries.import_title')) ?></h2>
        <p><?= htmlspecialchars(__('common.default_period', ['period' => $periodLabel])) ?></p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <form method="post" action="/entries/payroll/import" enctype="multipart/form-data" class="card">
            <div class="card-body space-y-5">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                <input type="hidden" name="default_year" value="<?= $importYear ?>">
                <input type="hidden" name="default_month" value="<?= $importMonth ?>">
                <div class="border-2 border-dashed border-slate-200 rounded-lg p-8 text-center bg-slate-50">
                    <input type="file" name="file" accept=".xlsx,.csv,.txt" required class="text-sm w-full">
                    <p class="text-xs text-slate-500 mt-2"><?= htmlspecialchars(__('entries.import_file_hint')) ?></p>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg"><?= htmlspecialchars(__('entries.import_btn')) ?></button>
            </div>
        </form>

        <div class="card">
            <div class="card-body text-sm space-y-4">
                <h3 class="font-semibold text-slate-900"><?= htmlspecialchars(__('entries.columns_min')) ?></h3>
                <ul class="list-disc list-inside text-slate-600 space-y-1 text-xs">
                    <li><code>raison_sociale</code> ou <code>client_id</code></li>
                    <li><code>masse_salariale</code>, <code>effectif</code></li>
                    <li><code>period_year</code>, <code>period_month</code> (<?= htmlspecialchars(__('common.optional')) ?>)</li>
                </ul>
                <pre class="text-[11px] bg-slate-50 p-3 rounded border overflow-x-auto font-mono"><?= htmlspecialchars($sample) ?></pre>
                <p class="text-xs text-slate-500"><?= htmlspecialchars(__('entries.import_after')) ?></p>
            </div>
        </div>
    </div>
</div>
