<?php $c = $counts; ?>
<div class="page-intro flex flex-wrap justify-between items-start gap-4 mb-6">
    <div>
        <p class="eyebrow">Équipe cabinet</p>
        <h2>Tâches</h2>
        <p>Suivi des actions à faire — par client, échéance et priorité.</p>
    </div>
    <a href="/production" class="btn btn-secondary btn-sm">Production mensuelle</a>
</div>

<div class="stat-chips mb-5">
    <a href="/tasks?filter=open" class="stat-chip <?= $filter === 'open' ? 'active chip-info' : '' ?>"><span class="num"><?= $c['open'] ?></span><span class="lbl">Ouvertes</span></a>
    <a href="/tasks?filter=high" class="stat-chip chip-danger <?= $filter === 'high' ? 'active' : '' ?>"><span class="num"><?= $c['high'] ?></span><span class="lbl">Urgentes</span></a>
    <a href="/tasks?filter=done" class="stat-chip <?= $filter === 'done' ? 'active chip-success' : '' ?>"><span class="num"><?= $c['done'] ?></span><span class="lbl">Terminées</span></a>
    <a href="/tasks?filter=all" class="stat-chip <?= $filter === 'all' ? 'active' : '' ?>"><span class="num"><?= $c['open'] + $c['done'] ?></span><span class="lbl">Toutes</span></a>
</div>

<div class="card mb-6">
    <div class="card-header"><h3>Nouvelle tâche</h3></div>
    <div class="card-body">
        <form method="post" action="/tasks" class="flex flex-wrap gap-3 items-end">
            <input type="hidden" name="redirect" value="/tasks">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs text-slate-600 mb-1">Titre</label>
                <input name="title" required class="input" placeholder="Ex. Relancer client pour paie janvier">
            </div>
            <div class="w-56">
                <label class="block text-xs text-slate-600 mb-1">Client</label>
                <?php $name = 'client_id'; $required = false; $compact = true; require ROOT_PATH . '/templates/_partials/client_picker.php'; ?>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Échéance</label>
                <input type="date" name="due_date" class="input w-auto">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Priorité</label>
                <select name="priority" class="select w-auto">
                    <option value="normal">Normal</option>
                    <option value="high">Urgent</option>
                    <option value="low">Bas</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Ajouter</button>
        </form>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="table-scroll" style="max-height:none">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="40"></th>
                    <th>Tâche</th>
                    <th>Client</th>
                    <th>Assigné</th>
                    <th>Échéance</th>
                    <th>Priorité</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                <tr><td colspan="6" class="text-center py-12 text-slate-500">Aucune tâche.</td></tr>
                <?php else: foreach ($tasks as $t): ?>
                <tr class="<?= $t['is_done'] ? 'opacity-60' : '' ?>">
                    <td>
                        <?php if (!$t['is_done']): ?>
                        <form method="post" action="/tasks/<?= $t['id'] ?>/complete">
                            <input type="hidden" name="redirect" value="/tasks?filter=<?= htmlspecialchars($filter) ?>">
                            <button type="submit" class="w-4 h-4 rounded border-2 border-slate-300 hover:border-teal-600" title="Terminer"></button>
                        </form>
                        <?php endif; ?>
                    </td>
                    <td class="font-medium <?= $t['is_done'] ? 'line-through text-slate-500' : '' ?>"><?= htmlspecialchars($t['title']) ?></td>
                    <td><?php if ($t['raison_sociale']): ?><a href="/clients/<?= $t['client_id'] ?>" class="text-teal-700 hover:underline text-sm"><?= htmlspecialchars($t['raison_sociale']) ?></a><?php else: ?>—<?php endif; ?></td>
                    <td class="text-sm text-slate-600"><?= htmlspecialchars($t['assignee']) ?></td>
                    <td class="font-mono text-sm"><?= $t['due_date'] ? date('d/m/Y', strtotime($t['due_date'])) : '—' ?></td>
                    <td><span class="badge <?= $t['priority'] === 'high' ? 'badge-danger' : ($t['priority'] === 'low' ? 'badge-neutral' : 'badge-warning') ?>"><?= $t['priority'] ?></span></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
