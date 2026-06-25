<?php
$folderIcons = ['paie' => 'PA', 'social' => 'SO', 'fiscal' => 'FI', 'factures' => 'FA', 'banque' => 'BN', 'juridique' => 'JU', 'divers' => 'DI'];
$gedStatusLabels = [
    'a_traiter' => __('common.ged_status_a_traiter'),
    'en_cours' => __('common.ged_status_en_cours'),
    'traite' => __('common.ged_status_traite'),
    'archive' => __('common.ged_status_archive'),
];
?>
<div class="flex flex-wrap justify-between items-start gap-4 mb-6">
    <div class="page-intro">
        <p class="eyebrow"><?= htmlspecialchars(__('ged.dossier_eyebrow')) ?></p>
        <h2><?= htmlspecialchars($client['raison_sociale']) ?></h2>
        <p class="font-mono text-xs">storage/clients/<?= $client['id'] ?>/</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="/clients/<?= $client['id'] ?>/dossier?view=list" class="btn btn-sm <?= ($view ?? 'list') === 'list' ? 'btn-primary' : 'btn-secondary' ?>"><?= htmlspecialchars(__('common.list')) ?></a>
        <a href="/clients/<?= $client['id'] ?>/dossier?view=kanban" class="btn btn-sm <?= ($view ?? '') === 'kanban' ? 'btn-primary' : 'btn-secondary' ?>"><?= htmlspecialchars(__('common.kanban')) ?></a>
        <a href="/assistant?client=<?= $client['id'] ?>" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('ged.ai_assistant')) ?></a>
        <a href="/clients/<?= $client['id'] ?>" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('ged.client_sheet')) ?></a>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-2 mb-6">
    <?php foreach ($structure as $key => $folder): ?>
    <a href="/clients/<?= $client['id'] ?>/dossier?cat=<?= $key ?>"
       class="folder-tile <?= ($category ?? '') === $key ? 'active' : '' ?>">
        <div class="folder-icon"><?= $folderIcons[$key] ?? 'DO' ?></div>
        <p class="text-xs font-medium text-slate-800"><?= $folder['label'] ?></p>
        <p class="text-xs text-slate-400 mt-0.5"><?= (int)$folder['count'] ?> <?= htmlspecialchars(__('common.files')) ?></p>
    </a>
    <?php endforeach; ?>
</div>

<?php if (($view ?? 'list') === 'kanban'): ?>
<?php
$cols = [
    'a_traiter' => __('ged.kanban_pending'),
    'en_cours' => __('ged.kanban_in_progress'),
    'traite' => __('ged.kanban_done'),
    'archive' => __('ged.kanban_archived'),
];
$byStatus = array_fill_keys(array_keys($cols), []);
foreach ($allDocuments as $doc) {
    $byStatus[$doc['ged_status'] ?? 'a_traiter'][] = $doc;
}
?>
<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <?php foreach ($cols as $key => $label): ?>
    <div class="bg-slate-100/80 rounded-lg p-4 min-h-[280px] border border-slate-200">
        <h4 class="font-semibold text-sm mb-3 flex justify-between text-slate-700">
            <?= htmlspecialchars($label) ?> <span class="text-slate-400 font-normal"><?= count($byStatus[$key]) ?></span>
        </h4>
        <?php foreach ($byStatus[$key] as $d): ?>
        <a href="/documents/<?= $d['id'] ?>" class="block card p-3 mb-2 text-sm hover:shadow transition">
            <p class="font-medium truncate"><?= htmlspecialchars($d['title'] ?? $d['original_name']) ?></p>
            <p class="text-xs text-slate-400 mt-1"><?= $d['category'] ?? '' ?></p>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="card">
        <div class="card-header"><h3><?= htmlspecialchars(__('ged.add_to_folder')) ?></h3></div>
        <div class="card-body">
            <form method="post" action="/clients/<?= $client['id'] ?>/dossier/upload" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="text-xs font-medium text-slate-600 block mb-1.5"><?= htmlspecialchars(__('common.folder')) ?></label>
                    <select name="category" class="select">
                        <?php foreach ($categories as $k => $label): ?>
                        <option value="<?= $k ?>" <?= ($category ?? '') === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 block mb-1.5"><?= htmlspecialchars(__('ged.title_optional')) ?></label>
                    <input name="title" class="input" placeholder="<?= htmlspecialchars(__('ged.title_placeholder')) ?>">
                </div>
                <div class="border border-dashed border-slate-300 rounded-md p-5 text-center bg-slate-50/50">
                    <input type="file" name="document" required accept=".pdf,.png,.jpg,.jpeg,.xlsx,.xls" class="text-sm w-full">
                    <p class="text-xs text-slate-400 mt-2"><?= htmlspecialchars(__('ged.file_types')) ?></p>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="process_now" value="1" checked class="rounded"> <?= htmlspecialchars(__('ged.ocr_auto')) ?>
                </label>
                <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars(__('ged.upload_process')) ?></button>
            </form>
        </div>
    </div>

    <div class="card lg:col-span-2 overflow-hidden">
        <div class="card-header">
            <h3><?= $category ? htmlspecialchars($categories[$category] ?? $category) : htmlspecialchars(__('ged.all_documents')) ?></h3>
            <span class="text-xs text-slate-400"><?= htmlspecialchars(__('common.docs_count', ['n' => count($documents)])) ?></span>
        </div>
        <form method="post" action="/clients/<?= $client['id'] ?>/dossier/bulk" id="bulk-form">
            <input type="hidden" name="return_cat" value="<?= htmlspecialchars($category ?? '') ?>">
            <div class="px-4 py-2 border-b border-slate-100 flex flex-wrap gap-2 items-center bg-slate-50/80" x-data="{ action: '' }">
                <span class="text-xs text-slate-500"><?= htmlspecialchars(__('common.selection')) ?></span>
                <select name="bulk_action" x-model="action" class="select text-xs py-1">
                    <option value=""><?= htmlspecialchars(__('common.action_placeholder')) ?></option>
                    <option value="archive"><?= htmlspecialchars(__('common.archive')) ?></option>
                    <option value="status"><?= htmlspecialchars(__('common.change_status')) ?></option>
                    <option value="move"><?= htmlspecialchars(__('common.move_folder')) ?></option>
                    <option value="delete"><?= htmlspecialchars(__('common.delete')) ?></option>
                </select>
                <select name="ged_status" class="select text-xs py-1" x-show="action === 'status'" x-cloak>
                    <?php foreach (array_keys($gedStatusLabels) as $s): ?>
                    <option value="<?= $s ?>"><?= htmlspecialchars($gedStatusLabels[$s]) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="bulk_category" class="select text-xs py-1" x-show="action === 'move'" x-cloak>
                    <?php foreach ($categories as $k => $label): ?>
                    <option value="<?= $k ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="text-xs flex items-center gap-1" x-show="action === 'delete'" x-cloak>
                    <input type="checkbox" name="confirm_delete" value="1" required> <?= htmlspecialchars(__('common.confirm_delete_bulk')) ?>
                </label>
                <button type="submit" class="btn btn-secondary btn-sm" onclick="return document.querySelectorAll('.doc-check:checked').length > 0;"><?= htmlspecialchars(__('common.apply')) ?></button>
            </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="32"><input type="checkbox" onclick="document.querySelectorAll('.doc-check').forEach(c=>c.checked=this.checked)" title="<?= htmlspecialchars(__('common.select_all')) ?>"></th>
                        <th><?= htmlspecialchars(__('common.document')) ?></th><th><?= htmlspecialchars(__('common.category')) ?></th><th><?= htmlspecialchars(__('common.ged_status_col')) ?></th><th><?= htmlspecialchars(__('ged.ocr_col')) ?></th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documents)): ?>
                    <tr><td colspan="6" class="text-center text-slate-400 py-12"><?= htmlspecialchars(__('ged.empty_folder')) ?></td></tr>
                    <?php else: foreach ($documents as $d):
                        $gedKey = $d['ged_status'] ?? 'a_traiter';
                    ?>
                    <tr>
                        <td><input type="checkbox" class="doc-check" name="ids[]" value="<?= $d['id'] ?>" form="bulk-form"></td>
                        <td>
                            <p class="font-medium"><?= htmlspecialchars($d['title'] ?? $d['original_name']) ?></p>
                            <p class="text-xs text-slate-400"><?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></p>
                        </td>
                        <td><span class="badge badge-neutral"><?= $d['category'] ?? 'divers' ?></span></td>
                        <td>
                            <span class="badge <?= match($gedKey) {
                                'traite' => 'badge-success',
                                'en_cours' => 'badge-info',
                                'archive' => 'badge-neutral',
                                default => 'badge-warning'
                            } ?>"><?= htmlspecialchars($gedStatusLabels[$gedKey] ?? $gedKey) ?></span>
                        </td>
                        <td class="text-xs"><?= $d['status'] ?><?= $d['confidence'] ? " ({$d['confidence']}%)" : '' ?></td>
                        <td><?php require ROOT_PATH . '/templates/_partials/document_actions.php'; ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        </form>
    </div>
</div>
<?php endif; ?>
