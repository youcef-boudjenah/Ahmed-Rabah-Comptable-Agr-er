<div class="page-intro flex flex-wrap justify-between items-start gap-4 mb-6">
    <div>
        <p class="eyebrow">Traçabilité</p>
        <h2>Journal d'audit</h2>
        <p>100 dernières actions enregistrées dans le cabinet.</p>
    </div>
    <a href="/rapports" class="btn btn-secondary btn-sm">← Rapports</a>
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <h3>Historique des actions</h3>
        <span class="badge badge-neutral"><?= count($logs) ?> entrées</span>
    </div>
    <div class="table-scroll" style="max-height: none;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Utilisateur</th>
                    <th>Action</th>
                    <th>Entité</th>
                    <th>ID</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr><td colspan="5" class="text-center text-slate-500 py-10">Aucune entrée d'audit.</td></tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="font-mono text-xs text-slate-600"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                    <td class="font-medium text-slate-800"><?= htmlspecialchars($log['user_name'] ?? '—') ?></td>
                    <td><span class="badge badge-accent"><?= htmlspecialchars($log['action']) ?></span></td>
                    <td class="text-slate-700"><?= htmlspecialchars($log['entity']) ?></td>
                    <td class="font-mono text-slate-600"><?= htmlspecialchars((string)($log['entity_id'] ?? '—')) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
