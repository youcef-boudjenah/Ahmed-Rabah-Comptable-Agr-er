<?php
$extracted = $ocr['extracted_json'] ?? [];
$categories = \App\Modules\Documents\ClientFolderService::CATEGORIES;
?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
        <h3 class="font-semibold text-navy-900 mb-2"><?= htmlspecialchars($document['title'] ?? $document['original_name']) ?></h3>
        <p class="text-sm text-slate-500">OCR: <span class="font-mono"><?= htmlspecialchars($document['doc_type'] ?? '—') ?></span></p>
        <p class="text-sm text-slate-500 mt-1">GED: <span class="font-mono"><?= $document['ged_status'] ?? 'a_traiter' ?></span></p>
        <?php if ($ocr): ?>
        <p class="text-sm mt-2">Confiance OCR: <span class="font-medium text-teal-600"><?= $ocr['confidence'] ?>%</span></p>
        <?php endif; ?>
        <div class="flex gap-2 mt-4">
            <a href="/documents/<?= $document['id'] ?>/download" target="_blank" class="px-4 py-2 rounded-xl bg-slate-100 text-sm hover:bg-slate-200">Voir fichier</a>
            <form method="post" action="/documents/<?= $document['id'] ?>/process">
                <button type="submit" class="px-4 py-2 rounded-xl border border-slate-200 text-sm hover:bg-slate-50">Relancer OCR</button>
            </form>
        </div>
        <?php if ($document['client_id']): ?>
        <a href="/clients/<?= $document['client_id'] ?>/dossier" class="inline-block mt-3 text-sm text-teal-600 hover:underline">← Retour dossier client</a>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
        <h3 class="font-semibold text-navy-900 mb-4">Métadonnées GED</h3>
        <form method="post" action="/documents/<?= $document['id'] ?>/ged" class="space-y-3 text-sm">
            <div>
                <label class="text-slate-600 block mb-1">Titre</label>
                <input name="title" value="<?= htmlspecialchars($document['title'] ?? $document['original_name']) ?>" class="w-full px-3 py-2 rounded-lg border">
            </div>
            <div>
                <label class="text-slate-600 block mb-1">Dossier</label>
                <select name="category" class="w-full px-3 py-2 rounded-lg border">
                    <?php foreach ($categories as $k => $label): ?>
                    <option value="<?= $k ?>" <?= ($document['category'] ?? '') === $k ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-slate-600 block mb-1">Statut traitement</label>
                <select name="ged_status" class="w-full px-3 py-2 rounded-lg border">
                    <?php foreach (['a_traiter','en_cours','traite','archive'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($document['ged_status'] ?? 'a_traiter') === $s ? 'selected' : '' ?>><?= str_replace('_', ' ', $s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-slate-600 block mb-1">Tags</label>
                <input name="tags" value="<?= htmlspecialchars($document['tags'] ?? '') ?>" placeholder="cnas, mai2026" class="w-full px-3 py-2 rounded-lg border">
            </div>
            <div>
                <label class="text-slate-600 block mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full px-3 py-2 rounded-lg border"><?= htmlspecialchars($document['notes'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="w-full py-2.5 bg-slate-800 text-white rounded-xl">Enregistrer GED</button>
        </form>
    </div>

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
        <h3 class="font-semibold text-navy-900 mb-4">Importer en comptabilité</h3>
        <p class="text-xs text-slate-500 mb-3">Les données importées recalculent automatiquement les déclarations et vous redirigent vers le brouillon.</p>
        <?php if ($document['status'] === 'awaiting_review' && $ocr):
            $entryType = $extracted['entry_type'] ?? (($extracted['ca_biens'] ?? $extracted['ca_services'] ?? null) ? 'sales' : 'payroll');
        ?>
        <form method="post" action="/documents/<?= $document['id'] ?>/commit" class="space-y-4">
            <div>
                <?php
                $selectedId = (int) ($document['client_id'] ?? 0) ?: null;
                require ROOT_PATH . '/templates/_partials/client_picker.php';
                ?>
            </div>
            <div>
                <label class="text-sm text-slate-600 block mb-1">Type de données</label>
                <select id="entry-type" class="w-full px-3 py-2 rounded-lg border text-sm" onchange="document.getElementById('payroll-fields').classList.toggle('hidden', this.value==='sales'); document.getElementById('sales-fields').classList.toggle('hidden', this.value!=='sales');">
                    <option value="payroll" <?= $entryType === 'payroll' ? 'selected' : '' ?>>Paie (CNAS / CACOBATPH)</option>
                    <option value="sales" <?= $entryType === 'sales' ? 'selected' : '' ?>>Ventes / CA (G50 / G12)</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="text-sm text-slate-600">Année</label><input name="period_year" value="<?= $extracted['period_year'] ?? date('Y') ?>" class="w-full px-3 py-2 rounded-lg border"></div>
                <div><label class="text-sm text-slate-600">Mois</label><input name="period_month" value="<?= $extracted['period_month'] ?? '' ?>" class="w-full px-3 py-2 rounded-lg border"></div>
            </div>
            <div id="payroll-fields" class="<?= $entryType === 'sales' ? 'hidden' : '' ?>">
                <label class="text-sm text-slate-600">Masse salariale</label>
                <input name="masse_salariale" value="<?= $extracted['masse_salariale'] ?? $extracted['salaire_base'] ?? '' ?>" class="w-full px-3 py-2 rounded-lg border font-mono">
            </div>
            <div id="sales-fields" class="space-y-2 <?= $entryType === 'payroll' ? 'hidden' : '' ?>">
                <div><label class="text-sm text-slate-600">CA biens</label><input name="ca_biens" value="<?= $extracted['ca_biens'] ?? '' ?>" class="w-full px-3 py-2 rounded-lg border font-mono"></div>
                <div><label class="text-sm text-slate-600">CA services</label><input name="ca_services" value="<?= $extracted['ca_services'] ?? '' ?>" class="w-full px-3 py-2 rounded-lg border font-mono"></div>
            </div>
            <button type="submit" class="w-full py-3 bg-teal-600 text-white rounded-xl font-medium">Importer → déclaration</button>
        </form>
        <?php elseif ($document['status'] === 'done'): ?>
        <p class="text-teal-600">Importé. <a href="/declarations" class="underline">Voir déclarations</a></p>
        <?php else: ?>
        <p class="text-slate-400 text-sm">OCR en cours ou en attente...</p>
        <?php endif; ?>
    </div>
</div>

<?php if ($ocr && !empty($ocr['extracted_json'])): ?>
<div class="mt-6 bg-slate-900 rounded-2xl p-6 text-slate-300 text-xs font-mono overflow-x-auto">
    <pre><?= htmlspecialchars(json_encode($ocr['extracted_json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
</div>
<?php endif; ?>
