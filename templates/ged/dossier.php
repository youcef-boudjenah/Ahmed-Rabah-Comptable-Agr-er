<?php
$folderIcons = ['paie' => 'PA', 'social' => 'SO', 'fiscal' => 'FI', 'factures' => 'FA', 'banque' => 'BN', 'juridique' => 'JU', 'divers' => 'DI'];
?>
<div class="flex flex-wrap justify-between items-start gap-4 mb-6">
    <div class="page-intro">
        <p class="eyebrow">Dossier client GED</p>
        <h2><?= htmlspecialchars($client['raison_sociale']) ?></h2>
        <p class="font-mono text-xs">storage/clients/<?= $client['id'] ?>/</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="/clients/<?= $client['id'] ?>/dossier?view=list" class="btn btn-sm <?= ($view ?? 'list') === 'list' ? 'btn-primary' : 'btn-secondary' ?>">Liste</a>
        <a href="/clients/<?= $client['id'] ?>/dossier?view=kanban" class="btn btn-sm <?= ($view ?? '') === 'kanban' ? 'btn-primary' : 'btn-secondary' ?>">Kanban</a>
        <a href="/assistant?client=<?= $client['id'] ?>" class="btn btn-secondary btn-sm">Assistant IA</a>
        <a href="/clients/<?= $client['id'] ?>" class="btn btn-secondary btn-sm">Fiche client</a>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-2 mb-6">
    <?php foreach ($structure as $key => $folder): ?>
    <a href="/clients/<?= $client['id'] ?>/dossier?cat=<?= $key ?>"
       class="folder-tile <?= ($category ?? '') === $key ? 'active' : '' ?>">
        <div class="folder-icon"><?= $folderIcons[$key] ?? 'DO' ?></div>
        <p class="text-xs font-medium text-slate-800"><?= $folder['label'] ?></p>
        <p class="text-xs text-slate-400 mt-0.5"><?= $folder['count'] ?> fichier(s)</p>
    </a>
    <?php endforeach; ?>
</div>

<?php if (($view ?? 'list') === 'kanban'): ?>
<?php
$cols = ['a_traiter' => 'À traiter', 'en_cours' => 'En cours', 'traite' => 'Traité', 'archive' => 'Archivé'];
$byStatus = array_fill_keys(array_keys($cols), []);
foreach ($allDocuments as $doc) {
    $byStatus[$doc['ged_status'] ?? 'a_traiter'][] = $doc;
}
?>
<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <?php foreach ($cols as $key => $label): ?>
    <div class="bg-slate-100/80 rounded-lg p-4 min-h-[280px] border border-slate-200">
        <h4 class="font-semibold text-sm mb-3 flex justify-between text-slate-700">
            <?= $label ?> <span class="text-slate-400 font-normal"><?= count($byStatus[$key]) ?></span>
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
        <div class="card-header"><h3>Ajouter au dossier</h3></div>
        <div class="card-body">
            <form method="post" action="/clients/<?= $client['id'] ?>/dossier/upload" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="text-xs font-medium text-slate-600 block mb-1.5">Dossier</label>
                    <select name="category" class="select">
                        <?php foreach ($categories as $k => $label): ?>
                        <option value="<?= $k ?>" <?= ($category ?? '') === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 block mb-1.5">Titre (optionnel)</label>
                    <input name="title" class="input" placeholder="Ex. Bulletin paie Mai 2026">
                </div>
                <div class="border border-dashed border-slate-300 rounded-md p-5 text-center bg-slate-50/50">
                    <input type="file" name="document" required accept=".pdf,.png,.jpg,.jpeg,.xlsx,.xls" class="text-sm w-full">
                    <p class="text-xs text-slate-400 mt-2">PDF, images, Excel</p>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="process_now" value="1" checked class="rounded"> OCR + extraction automatique
                </label>
                <button type="submit" class="btn btn-primary btn-block">Uploader et traiter</button>
            </form>
        </div>
    </div>

    <div class="card lg:col-span-2 overflow-hidden">
        <div class="card-header">
            <h3><?= $category ? htmlspecialchars($categories[$category] ?? $category) : 'Tous les documents' ?></h3>
            <span class="text-xs text-slate-400"><?= count($documents) ?> document(s)</span>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Document</th><th>Catégorie</th><th>Statut GED</th><th>OCR</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documents)): ?>
                    <tr><td colspan="5" class="text-center text-slate-400 py-12">Dossier vide — uploadez un document.</td></tr>
                    <?php else: foreach ($documents as $d): ?>
                    <tr>
                        <td>
                            <p class="font-medium"><?= htmlspecialchars($d['title'] ?? $d['original_name']) ?></p>
                            <p class="text-xs text-slate-400"><?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></p>
                        </td>
                        <td><span class="badge badge-neutral"><?= $d['category'] ?? 'divers' ?></span></td>
                        <td>
                            <span class="badge <?= match($d['ged_status'] ?? 'a_traiter') {
                                'traite' => 'badge-success',
                                'en_cours' => 'badge-info',
                                'archive' => 'badge-neutral',
                                default => 'badge-warning'
                            } ?>"><?= str_replace('_', ' ', $d['ged_status'] ?? 'a_traiter') ?></span>
                        </td>
                        <td class="text-xs"><?= $d['status'] ?><?= $d['confidence'] ? " ({$d['confidence']}%)" : '' ?></td>
                        <td class="text-right space-x-3">
                            <a href="/documents/<?= $d['id'] ?>/download" target="_blank" class="text-xs text-slate-500 hover:text-accent">Voir</a>
                            <a href="/documents/<?= $d['id'] ?>" class="text-xs font-medium text-accent hover:underline">Traiter</a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
