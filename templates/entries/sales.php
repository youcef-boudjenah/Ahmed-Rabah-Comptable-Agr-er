<form method="post" action="/entries/sales" class="max-w-2xl card">
    <div class="card-body space-y-5">
    <?php
    $selectedId = $selectedClientId ?? null;
    require ROOT_PATH . '/templates/_partials/client_picker.php';
    ?>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1"><?= htmlspecialchars(__('common.year')) ?></label>
            <input type="number" name="period_year" value="2026" required class="input">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1"><?= htmlspecialchars(__('common.month_g50')) ?></label>
            <input type="number" name="period_month" min="1" max="12" value="5" class="input">
        </div>
    </div>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1"><?= htmlspecialchars(__('documents.ca_biens')) ?></label>
            <input type="text" name="ca_biens" value="0" class="input font-mono">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1"><?= htmlspecialchars(__('documents.ca_services')) ?></label>
            <input type="text" name="ca_services" value="0" class="input font-mono">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1"><?= htmlspecialchars(__('common.ca_auto_entrepreneur')) ?></label>
            <input type="text" name="ca_auto_entrepreneur" value="0" class="input font-mono">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1"><?= htmlspecialchars(__('entries.irg_base')) ?></label>
        <input type="text" name="irg_acompte_base" value="" placeholder="702522" class="input font-mono">
        <p class="text-xs text-slate-400 mt-1"><?= htmlspecialchars(__('entries.irg_hint')) ?></p>
    </div>
    <button type="submit" class="btn btn-primary btn-block btn-lg"><?= htmlspecialchars(__('entries.save_g50')) ?></button>
    </div>
</form>
