<?php
$extracted = $ocr['extracted_json'] ?? [];
$categories = \App\Modules\Documents\ClientFolderService::categoryLabels();
$gedStatusLabels = [
    'a_traiter' => __('common.ged_status_a_traiter'),
    'en_cours' => __('common.ged_status_en_cours'),
    'traite' => __('common.ged_status_traite'),
    'archive' => __('common.ged_status_archive'),
];
?>
<div class="page-intro flex flex-wrap justify-between gap-4 mb-6">
    <div>
        <p class="eyebrow"><?= htmlspecialchars(__('documents.eyebrow')) ?></p>
        <h2><?= htmlspecialchars($document['title'] ?? $document['original_name']) ?></h2>
        <p class="text-sm text-slate-500">OCR: <?= htmlspecialchars($document['doc_type'] ?? __('common.unassigned')) ?> · GED: <?= htmlspecialchars($gedStatusLabels[$document['ged_status'] ?? 'a_traiter'] ?? ($document['ged_status'] ?? 'a_traiter')) ?></p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="/documents/<?= $document['id'] ?>/download" target="_blank" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('documents.see_file')) ?></a>
        <form method="post" action="/documents/<?= $document['id'] ?>/process" class="inline">
            <button type="submit" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('documents.rerun_ocr')) ?></button>
        </form>
        <form method="post" action="/documents/<?= $document['id'] ?>/delete" class="inline" onsubmit="return confirm(<?= json_encode(__('documents.delete_confirm')) ?>);">
            <button type="submit" class="btn btn-ghost btn-sm text-red-600"><?= htmlspecialchars(__('common.delete')) ?></button>
        </form>
        <?php if ($document['client_id']): ?>
        <a href="/clients/<?= $document['client_id'] ?>/dossier" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('documents.back_folder')) ?></a>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="card">
        <div class="card-header"><h3><?= htmlspecialchars(__('documents.metadata')) ?></h3></div>
        <div class="card-body">
        <form method="post" action="/documents/<?= $document['id'] ?>/ged" class="space-y-3 text-sm">
            <div>
                <label class="text-slate-600 block mb-1"><?= htmlspecialchars(__('documents.title')) ?></label>
                <input name="title" value="<?= htmlspecialchars($document['title'] ?? $document['original_name']) ?>" class="input w-full">
            </div>
            <div>
                <label class="text-slate-600 block mb-1"><?= htmlspecialchars(__('common.folder')) ?></label>
                <select name="category" class="select w-full">
                    <?php foreach ($categories as $k => $label): ?>
                    <option value="<?= $k ?>" <?= ($document['category'] ?? '') === $k ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-slate-600 block mb-1"><?= htmlspecialchars(__('documents.processing_status')) ?></label>
                <select name="ged_status" class="select w-full">
                    <?php foreach (array_keys($gedStatusLabels) as $s): ?>
                    <option value="<?= $s ?>" <?= ($document['ged_status'] ?? 'a_traiter') === $s ? 'selected' : '' ?>><?= htmlspecialchars($gedStatusLabels[$s]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-slate-600 block mb-1"><?= htmlspecialchars(__('common.tags')) ?></label>
                <input name="tags" value="<?= htmlspecialchars($document['tags'] ?? '') ?>" placeholder="<?= htmlspecialchars(__('documents.tags_placeholder')) ?>" class="input w-full">
            </div>
            <div>
                <label class="text-slate-600 block mb-1"><?= htmlspecialchars(__('common.notes')) ?></label>
                <textarea name="notes" rows="2" class="input w-full"><?= htmlspecialchars($document['notes'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars(__('common.save')) ?></button>
        </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3><?= htmlspecialchars(__('documents.reassign_client')) ?></h3></div>
        <div class="card-body">
        <form method="post" action="/documents/<?= $document['id'] ?>/reassign" class="space-y-3 text-sm">
            <div>
                <label class="text-slate-600 block mb-1"><?= htmlspecialchars(__('common.client')) ?></label>
                <?php $name = 'client_id'; $required = true; $compact = false; $selectedClientId = (int) ($document['client_id'] ?? 0) ?: null; require ROOT_PATH . '/templates/_partials/client_picker.php'; ?>
            </div>
            <div>
                <label class="text-slate-600 block mb-1"><?= htmlspecialchars(__('documents.target_folder')) ?></label>
                <select name="category" class="select w-full">
                    <?php foreach ($categories as $k => $label): ?>
                    <option value="<?= $k ?>" <?= ($document['category'] ?? 'divers') === $k ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary btn-block"><?= htmlspecialchars(__('documents.move_to_client')) ?></button>
        </form>
        <?php if ($ocr): ?>
        <p class="text-xs text-slate-500 mt-4"><?= htmlspecialchars(__('documents.ocr_confidence')) ?> <span class="font-medium text-accent-600"><?= $ocr['confidence'] ?>%</span></p>
        <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3><?= htmlspecialchars(__('documents.import_accounting')) ?></h3></div>
        <div class="card-body">
        <p class="text-xs text-slate-500 mb-3"><?= htmlspecialchars(__('documents.import_hint')) ?></p>
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
                <label class="text-sm text-slate-600 block mb-1"><?= htmlspecialchars(__('documents.data_type')) ?></label>
                <select id="entry-type" class="select w-full" onchange="document.getElementById('payroll-fields').classList.toggle('hidden', this.value==='sales'); document.getElementById('sales-fields').classList.toggle('hidden', this.value!=='sales');">
                    <option value="payroll" <?= $entryType === 'payroll' ? 'selected' : '' ?>><?= htmlspecialchars(__('documents.payroll_option')) ?></option>
                    <option value="sales" <?= $entryType === 'sales' ? 'selected' : '' ?>><?= htmlspecialchars(__('documents.sales_option')) ?></option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="text-sm text-slate-600"><?= htmlspecialchars(__('common.year')) ?></label><input name="period_year" value="<?= $extracted['period_year'] ?? date('Y') ?>" class="input w-full"></div>
                <div><label class="text-sm text-slate-600"><?= htmlspecialchars(__('common.month')) ?></label><input name="period_month" value="<?= $extracted['period_month'] ?? '' ?>" class="input w-full"></div>
            </div>
            <div id="payroll-fields" class="<?= $entryType === 'sales' ? 'hidden' : '' ?>">
                <label class="text-sm text-slate-600"><?= htmlspecialchars(__('documents.payroll_mass')) ?></label>
                <input name="masse_salariale" value="<?= $extracted['masse_salariale'] ?? $extracted['salaire_base'] ?? '' ?>" class="input w-full font-mono">
            </div>
            <div id="sales-fields" class="space-y-2 <?= $entryType === 'payroll' ? 'hidden' : '' ?>">
                <div><label class="text-sm text-slate-600"><?= htmlspecialchars(__('documents.ca_biens')) ?></label><input name="ca_biens" value="<?= $extracted['ca_biens'] ?? '' ?>" class="input w-full font-mono"></div>
                <div><label class="text-sm text-slate-600"><?= htmlspecialchars(__('documents.ca_services')) ?></label><input name="ca_services" value="<?= $extracted['ca_services'] ?? '' ?>" class="input w-full font-mono"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars(__('documents.import_btn')) ?></button>
        </form>
        <?php elseif ($document['status'] === 'done'): ?>
        <p class="text-accent-600 text-sm"><?= htmlspecialchars(__('documents.imported')) ?> <a href="/declarations" class="underline"><?= htmlspecialchars(__('common.see_declarations')) ?></a></p>
        <?php else: ?>
        <p class="text-slate-400 text-sm"><?= htmlspecialchars(__('documents.ocr_pending')) ?></p>
        <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($ocr && !empty($ocr['extracted_json'])): ?>
<div class="card mt-5">
    <div class="card-header"><h3><?= htmlspecialchars(__('documents.ocr_extracted')) ?></h3></div>
    <div class="card-body bg-slate-900 rounded-b-lg text-slate-300 text-xs font-mono overflow-x-auto">
        <pre class="m-0"><?= htmlspecialchars(json_encode($ocr['extracted_json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
    </div>
</div>
<?php endif; ?>
