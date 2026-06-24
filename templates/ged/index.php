<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <p class="text-xs text-slate-500 uppercase">Total documents</p>
        <p class="text-3xl font-bold text-navy-900 mt-1"><?= $stats['total'] ?></p>
    </div>
    <div class="bg-orange-50 rounded-2xl p-5 border border-orange-100">
        <p class="text-xs text-orange-600 uppercase">À traiter</p>
        <p class="text-3xl font-bold text-orange-700 mt-1"><?= $stats['a_traiter'] ?></p>
    </div>
    <div class="bg-teal-50 rounded-2xl p-5 border border-teal-100">
        <p class="text-xs text-teal-700 uppercase">Traités</p>
        <p class="text-3xl font-bold text-teal-800 mt-1"><?= $stats['traite'] ?></p>
    </div>
    <div class="bg-navy-900 rounded-2xl p-5 text-white">
        <p class="text-xs text-white/60 uppercase">Dossiers clients</p>
        <p class="text-3xl font-bold mt-1"><?= count($clients) ?></p>
    </div>
</div>

<form method="get" class="mb-6 flex gap-2">
    <input type="search" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Rechercher document, tag, note..."
           class="flex-1 px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-teal-500 outline-none">
    <button type="submit" class="px-6 py-3 bg-teal-600 text-white rounded-xl font-medium hover:bg-teal-500">Rechercher</button>
</form>

<?php if ($query !== ''): ?>
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm mb-8 overflow-hidden">
    <div class="px-6 py-4 border-b bg-slate-50"><h3 class="font-semibold">Résultats « <?= htmlspecialchars($query) ?> »</h3></div>
    <?php if (empty($results)): ?>
    <p class="p-6 text-slate-400">Aucun document trouvé.</p>
    <?php else: foreach ($results as $d): ?>
    <a href="/documents/<?= $d['id'] ?>" class="flex justify-between px-6 py-3 hover:bg-slate-50 border-b border-slate-50 text-sm">
        <span><?= htmlspecialchars($d['title'] ?? $d['original_name']) ?> — <span class="text-slate-400"><?= htmlspecialchars($d['raison_sociale'] ?? '') ?></span></span>
        <span class="text-xs px-2 py-0.5 bg-slate-100 rounded"><?= $d['category'] ?? 'divers' ?></span>
    </a>
    <?php endforeach; endif; ?>
</div>
<?php endif; ?>

<h2 class="text-lg font-semibold text-navy-900 mb-4">Dossiers clients</h2>
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    <?php foreach ($clients as $c): ?>
    <a href="/clients/<?= $c['id'] ?>/dossier" class="group bg-white rounded-2xl p-6 border border-slate-100 hover:border-teal-300 hover:shadow-lg transition">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-navy-800 to-teal-600 flex items-center justify-center text-white font-bold text-lg shrink-0">
                <?= strtoupper(substr($c['raison_sociale'], 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-navy-900 group-hover:text-teal-700 truncate"><?= htmlspecialchars($c['raison_sociale']) ?></h3>
                <p class="text-xs text-slate-400 mt-1"><?= $c['secteur'] ?> — <?= (int)$c['doc_count'] ?> document(s)</p>
                <?php if ((int)$c['a_traiter'] > 0): ?>
                <span class="inline-block mt-2 text-xs px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full"><?= $c['a_traiter'] ?> à traiter</span>
                <?php endif; ?>
            </div>
        </div>
        <p class="text-xs text-slate-400 mt-4 font-mono truncate">📁 storage/clients/<?= $c['id'] ?>/</p>
    </a>
    <?php endforeach; ?>
</div>
