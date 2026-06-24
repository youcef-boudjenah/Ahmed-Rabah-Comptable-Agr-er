<form method="get" action="/search" class="mb-8">
    <div class="relative">
        <input type="search" name="q" value="<?= htmlspecialchars($query) ?>" autofocus
               placeholder="Rechercher client, déclaration, document..."
               class="w-full px-6 py-4 text-lg rounded-2xl border border-slate-200 shadow-sm focus:ring-2 focus:ring-teal-500 outline-none pl-12">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl">🔍</span>
    </div>
</form>

<?php if ($results === null): ?>
<p class="text-slate-400 text-center py-12">Tapez au moins 2 caractères pour rechercher dans tout le cabinet.</p>
<?php else: ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 bg-slate-50 font-semibold text-sm">Clients (<?= count($results['clients']) ?>)</div>
        <div class="divide-y divide-slate-50">
            <?php foreach ($results['clients'] as $c): ?>
            <a href="/clients/<?= $c['id'] ?>" class="block px-5 py-3 hover:bg-slate-50 text-sm">
                <p class="font-medium"><?= htmlspecialchars($c['raison_sociale']) ?></p>
                <p class="text-xs text-slate-400"><?= $c['secteur'] ?> — <?= htmlspecialchars($c['wilaya'] ?? '') ?></p>
            </a>
            <?php endforeach; ?>
            <?php if (empty($results['clients'])): ?><p class="px-5 py-4 text-slate-400 text-sm">Aucun</p><?php endif; ?>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 bg-slate-50 font-semibold text-sm">Déclarations (<?= count($results['declarations']) ?>)</div>
        <div class="divide-y divide-slate-50">
            <?php foreach ($results['declarations'] as $d): ?>
            <a href="/declarations/<?= $d['id'] ?>" class="block px-5 py-3 hover:bg-slate-50 text-sm">
                <p class="font-medium font-mono text-teal-700"><?= $d['type'] ?></p>
                <p class="text-xs text-slate-400"><?= htmlspecialchars($d['raison_sociale']) ?> — <?= $d['status'] ?></p>
            </a>
            <?php endforeach; ?>
            <?php if (empty($results['declarations'])): ?><p class="px-5 py-4 text-slate-400 text-sm">Aucune</p><?php endif; ?>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 bg-slate-50 font-semibold text-sm">Documents GED (<?= count($results['documents']) ?>)</div>
        <div class="divide-y divide-slate-50">
            <?php foreach ($results['documents'] as $d): ?>
            <a href="/documents/<?= $d['id'] ?>" class="block px-5 py-3 hover:bg-slate-50 text-sm">
                <p class="font-medium"><?= htmlspecialchars($d['title'] ?? $d['original_name']) ?></p>
                <p class="text-xs text-slate-400"><?= htmlspecialchars($d['raison_sociale'] ?? '') ?> — <?= $d['category'] ?? '' ?></p>
            </a>
            <?php endforeach; ?>
            <?php if (empty($results['documents'])): ?><p class="px-5 py-4 text-slate-400 text-sm">Aucun</p><?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
