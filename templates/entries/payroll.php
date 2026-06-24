<form method="post" action="/entries/payroll" class="max-w-2xl card">
    <div class="card-body space-y-5">
    <?php
    $selectedId = $selectedClientId ?? null;
    require ROOT_PATH . '/templates/_partials/client_picker.php';
    ?>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Année</label>
            <input type="number" name="period_year" value="2026" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Mois</label>
            <input type="number" name="period_month" min="1" max="12" value="1" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Masse salariale (DA) *</label>
        <input type="text" name="masse_salariale" required placeholder="173781.80" value="173781.80"
               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-lg font-mono">
        <p class="text-xs text-slate-400 mt-1">Ex. BOUALAM MOHAMED Jan 2026 → 173 781,80 DA</p>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Effectif</label>
            <input type="number" name="effectif" value="7" class="w-full px-4 py-2.5 rounded-xl border border-slate-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">N° assurés (CACOBATPH)</label>
            <input type="number" name="nombre_assurees" value="22" class="w-full px-4 py-2.5 rounded-xl border border-slate-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Entrées</label>
            <input type="number" name="entrees" value="1" class="w-full px-4 py-2.5 rounded-xl border border-slate-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Sorties</label>
            <input type="number" name="sorties" value="1" class="w-full px-4 py-2.5 rounded-xl border border-slate-200">
        </div>
    </div>
    <button type="submit" class="px-6 py-3 bg-teal-600 hover:bg-teal-500 text-white rounded-xl font-medium transition w-full">
        Enregistrer & calculer déclarations
    </button>
    <a href="/entries/payroll/import" class="block text-center py-3 rounded-xl border border-slate-200 text-sm hover:bg-slate-50">
        Import CSV multi-clients →
    </a>
    </div>
</form>
