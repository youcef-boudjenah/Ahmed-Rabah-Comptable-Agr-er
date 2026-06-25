<form method="post" action="/entries/payroll" class="max-w-2xl card">
    <div class="card-body space-y-5">
    <?php
    $selectedId = $selectedClientId ?? null;
    require ROOT_PATH . '/templates/_partials/client_picker.php';
    ?>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1"><?= htmlspecialchars(__('common.year')) ?></label>
            <input type="number" name="period_year" value="<?= (int)($periodYear ?? date('Y')) ?>" required class="input w-full">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1"><?= htmlspecialchars(__('common.month')) ?></label>
            <input type="number" name="period_month" min="1" max="12" value="<?= (int)($periodMonth ?? max(1, (int)date('n')-1)) ?>" required class="input w-full">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1"><?= htmlspecialchars(__('entries.payroll_mass')) ?></label>
        <input type="text" name="masse_salariale" required placeholder="Ex. 173781.80" value=""
               class="input w-full text-lg font-mono">
        <p class="text-xs text-slate-400 mt-1"><?= htmlspecialchars(__('entries.payroll_mass_hint')) ?></p>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1"><?= htmlspecialchars(__('entries.headcount')) ?></label>
            <input type="number" name="effectif" min="0" placeholder="0" class="input w-full">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1"><?= htmlspecialchars(__('entries.assurees')) ?></label>
            <input type="number" name="nombre_assurees" min="0" placeholder="0" class="input w-full">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1"><?= htmlspecialchars(__('entries.entries')) ?></label>
            <input type="number" name="entrees" min="0" placeholder="0" class="input w-full">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1"><?= htmlspecialchars(__('entries.exits')) ?></label>
            <input type="number" name="sorties" min="0" placeholder="0" class="input w-full">
        </div>
    </div>
    <button type="submit" class="px-6 py-3 bg-accent-600 hover:bg-accent-500 text-white rounded-xl font-medium transition w-full">
        <?= htmlspecialchars(__('entries.save_calc')) ?>
    </button>
    <a href="/entries/payroll/import" class="block text-center py-3 rounded-xl border border-slate-200 text-sm hover:bg-slate-50">
        <?= htmlspecialchars(__('common.import_csv_link')) ?>
    </a>
    </div>
</form>
