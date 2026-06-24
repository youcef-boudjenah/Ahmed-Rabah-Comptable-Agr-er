<?php
$months = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$importYear = $defaultYear ?? (int) date('Y');
$importMonth = $defaultMonth ?? max(1, (int) date('n') - 1);
$redirect = $redirect ?? '/production';
?>
<div class="max-w-3xl">
    <div class="page-intro mb-6">
        <p class="eyebrow">Import en masse</p>
        <h2>Paie Excel / CSV</h2>
        <p>Période par défaut : <strong><?= $months[$importMonth] ?? $importMonth ?> <?= $importYear ?></strong> (si colonnes mois/année absentes du fichier).</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <form method="post" action="/entries/payroll/import" enctype="multipart/form-data" class="card">
            <div class="card-body space-y-5">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                <input type="hidden" name="default_year" value="<?= $importYear ?>">
                <input type="hidden" name="default_month" value="<?= $importMonth ?>">
                <div class="border-2 border-dashed border-slate-200 rounded-lg p-8 text-center bg-slate-50">
                    <input type="file" name="file" accept=".xlsx,.csv,.txt" required class="text-sm w-full">
                    <p class="text-xs text-slate-500 mt-2">Excel .xlsx ou CSV — une ligne = un client/mois</p>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Importer & recalculer CNAS</button>
            </div>
        </form>

        <div class="card">
            <div class="card-body text-sm space-y-4">
                <h3 class="font-semibold text-slate-900">Colonnes minimales</h3>
                <ul class="list-disc list-inside text-slate-600 space-y-1 text-xs">
                    <li><code>raison_sociale</code> ou <code>client_id</code></li>
                    <li><code>masse_salariale</code>, <code>effectif</code></li>
                    <li><code>period_year</code>, <code>period_month</code> (optionnel si période ci-dessus)</li>
                </ul>
                <pre class="text-[11px] bg-slate-50 p-3 rounded border overflow-x-auto font-mono"><?= htmlspecialchars($sample) ?></pre>
                <p class="text-xs text-slate-500">Après import → retour production mensuelle avec déclarations calculées.</p>
            </div>
        </div>
    </div>
</div>
