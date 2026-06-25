<?php $c = $counts; ?>
<div class="page-intro flex flex-wrap justify-between items-start gap-4 mb-6">
    <div>
        <p class="eyebrow"><?= htmlspecialchars(__('tasks.eyebrow')) ?></p>
        <h2><?= htmlspecialchars(__('tasks.title')) ?></h2>
        <p><?= htmlspecialchars(__('tasks.subtitle')) ?></p>
    </div>
    <a href="/production" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('common.monthly_production')) ?></a>
</div>

<div class="stat-chips mb-5">
    <a href="/tasks?filter=open" class="stat-chip <?= $filter === 'open' ? 'active chip-info' : '' ?>"><span class="num"><?= $c['open'] ?></span><span class="lbl"><?= htmlspecialchars(__('tasks.open')) ?></span></a>
    <a href="/tasks?filter=high" class="stat-chip chip-danger <?= $filter === 'high' ? 'active' : '' ?>"><span class="num"><?= $c['high'] ?></span><span class="lbl"><?= htmlspecialchars(__('tasks.urgent')) ?></span></a>
    <a href="/tasks?filter=done" class="stat-chip <?= $filter === 'done' ? 'active chip-success' : '' ?>"><span class="num"><?= $c['done'] ?></span><span class="lbl"><?= htmlspecialchars(__('tasks.done')) ?></span></a>
    <a href="/tasks?filter=all" class="stat-chip <?= $filter === 'all' ? 'active' : '' ?>"><span class="num"><?= $c['open'] + $c['done'] ?></span><span class="lbl"><?= htmlspecialchars(__('tasks.all')) ?></span></a>
</div>

<div class="card mb-6">
    <div class="card-header"><h3><?= htmlspecialchars(__('tasks.new_task')) ?></h3></div>
    <div class="card-body">
        <form method="post" action="/tasks" class="flex flex-wrap gap-3 items-end">
            <input type="hidden" name="redirect" value="/tasks">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs text-slate-600 mb-1"><?= htmlspecialchars(__('tasks.title_label')) ?></label>
                <input name="title" required class="input" placeholder="<?= htmlspecialchars(__('tasks.title_placeholder')) ?>">
            </div>
            <div class="w-56">
                <label class="block text-xs text-slate-600 mb-1"><?= htmlspecialchars(__('common.client')) ?></label>
                <?php $name = 'client_id'; $required = false; $compact = true; require ROOT_PATH . '/templates/_partials/client_picker.php'; ?>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1"><?= htmlspecialchars(__('tasks.due_date')) ?></label>
                <input type="date" name="due_date" class="input w-auto">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1"><?= htmlspecialchars(__('common.priority')) ?></label>
                <select name="priority" class="select w-auto">
                    <option value="normal"><?= htmlspecialchars(__('common.priority_normal')) ?></option>
                    <option value="high"><?= htmlspecialchars(__('common.priority_high')) ?></option>
                    <option value="low"><?= htmlspecialchars(__('common.priority_low')) ?></option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('common.add')) ?></button>
        </form>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="table-scroll" style="max-height:none">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="40"></th>
                    <th><?= htmlspecialchars(__('tasks.task_col')) ?></th>
                    <th><?= htmlspecialchars(__('common.client')) ?></th>
                    <th><?= htmlspecialchars(__('tasks.due_date')) ?></th>
                    <th><?= htmlspecialchars(__('common.priority')) ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                <tr><td colspan="6" class="text-center py-12 text-slate-500"><?= htmlspecialchars(__('tasks.no_tasks')) ?></td></tr>
                <?php else: foreach ($tasks as $t): ?>
                <tr class="<?= $t['is_done'] ? 'opacity-60' : '' ?>" x-data="{ edit: false }">
                    <td>
                        <?php if (!$t['is_done']): ?>
                        <form method="post" action="/tasks/<?= $t['id'] ?>/complete">
                            <input type="hidden" name="redirect" value="/tasks?filter=<?= htmlspecialchars($filter) ?>">
                            <button type="submit" class="w-4 h-4 rounded border-2 border-slate-300 hover:border-accent-600" title="<?= htmlspecialchars(__('common.complete')) ?>"></button>
                        </form>
                        <?php else: ?>
                        <form method="post" action="/tasks/<?= $t['id'] ?>/reopen">
                            <input type="hidden" name="redirect" value="/tasks?filter=<?= htmlspecialchars($filter) ?>">
                            <button type="submit" class="text-xs text-slate-400 hover:text-accent-700" title="<?= htmlspecialchars(__('common.reopen')) ?>">↺</button>
                        </form>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div x-show="!edit">
                            <span class="font-medium <?= $t['is_done'] ? 'line-through text-slate-500' : '' ?>"><?= htmlspecialchars($t['title']) ?></span>
                        </div>
                        <form x-show="edit" x-cloak method="post" action="/tasks/<?= $t['id'] ?>/update" class="space-y-2">
                            <input type="hidden" name="redirect" value="/tasks?filter=<?= htmlspecialchars($filter) ?>">
                            <input name="title" value="<?= htmlspecialchars($t['title']) ?>" class="input text-sm" required>
                            <div class="flex gap-2">
                                <input type="date" name="due_date" value="<?= $t['due_date'] ?? '' ?>" class="input text-xs flex-1">
                                <select name="priority" class="select text-xs">
                                    <option value="low" <?= $t['priority'] === 'low' ? 'selected' : '' ?>><?= htmlspecialchars(__('common.priority_low')) ?></option>
                                    <option value="normal" <?= $t['priority'] === 'normal' ? 'selected' : '' ?>><?= htmlspecialchars(__('common.priority_normal')) ?></option>
                                    <option value="high" <?= $t['priority'] === 'high' ? 'selected' : '' ?>><?= htmlspecialchars(__('common.priority_high')) ?></option>
                                </select>
                            </div>
                            <input type="hidden" name="client_id" value="<?= (int)($t['client_id'] ?? 0) ?>">
                            <button type="submit" class="btn btn-primary btn-sm"><?= htmlspecialchars(__('common.save')) ?></button>
                        </form>
                    </td>
                    <td><?php if ($t['raison_sociale']): ?><a href="/clients/<?= $t['client_id'] ?>" class="text-accent-700 hover:underline text-sm"><?= htmlspecialchars($t['raison_sociale']) ?></a><?php else: ?><?= htmlspecialchars(__('common.unassigned')) ?><?php endif; ?></td>
                    <td class="font-mono text-sm"><?= $t['due_date'] ? date('d/m/Y', strtotime($t['due_date'])) : __('common.unassigned') ?></td>
                    <td><span class="badge <?= $t['priority'] === 'high' ? 'badge-danger' : ($t['priority'] === 'low' ? 'badge-neutral' : 'badge-warning') ?>"><?= $t['priority'] ?></span></td>
                    <td class="text-right">
                        <button type="button" @click="edit = !edit" class="btn btn-ghost btn-sm" x-text="edit ? <?= json_encode(__('common.cancel')) ?> : <?= json_encode(__('common.edit')) ?>"></button>
                        <form method="post" action="/tasks/<?= $t['id'] ?>/delete" class="inline" onsubmit="return confirm(<?= json_encode(__('common.confirm_delete_task')) ?>);">
                            <input type="hidden" name="redirect" value="/tasks?filter=<?= htmlspecialchars($filter) ?>">
                            <button type="submit" class="btn btn-ghost btn-sm text-red-600"><?= htmlspecialchars(__('tasks.delete_short')) ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
