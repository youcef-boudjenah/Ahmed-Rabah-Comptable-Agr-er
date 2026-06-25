<?php
$s = $settings;
$tabs = [
    'settings' => __('admin.tab_settings'),
    'users' => __('admin.tab_users'),
    'rates' => __('admin.tab_rates'),
    'deadlines' => __('admin.tab_deadlines'),
    'system' => __('admin.tab_system'),
];
$tab = $tab ?? 'settings';
?>
<div class="mb-6">
    <h2 class="text-lg font-semibold text-navy-900"><?= htmlspecialchars(__('admin.title')) ?></h2>
    <p class="text-sm text-slate-500"><?= htmlspecialchars(__('admin.subtitle')) ?></p>
</div>

<div class="flex flex-wrap gap-2 mb-6 border-b border-slate-200 pb-4">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="/admin?tab=<?= $key ?>" class="btn btn-sm <?= $tab === $key ? 'btn-primary' : 'btn-secondary' ?>">
        <?= htmlspecialchars($label) ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if ($tab === 'settings'): ?>
<form method="post" action="/admin/settings" class="max-w-3xl space-y-6">
    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm space-y-4">
        <h3 class="font-semibold"><?= htmlspecialchars(__('admin.cabinet')) ?></h3>
        <div>
            <label class="text-sm text-slate-600 block mb-1"><?= htmlspecialchars(__('admin.cabinet_name')) ?></label>
            <input name="cabinet_name" value="<?= htmlspecialchars($cabinet['name'] ?? '') ?>" class="w-full px-4 py-2.5 rounded-xl border">
        </div>
    </div>

    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm space-y-3">
        <h3 class="font-semibold"><?= htmlspecialchars(__('admin.automation')) ?></h3>
        <?php
        $toggles = [
            'auto_ocr_on_upload' => __('admin.auto_ocr'),
            'auto_ai_classify' => __('admin.auto_classify'),
            'auto_pdf_on_approve' => __('admin.auto_pdf'),
            'auto_sync_tasks' => __('admin.auto_tasks'),
            'auto_ai_review_pipeline' => __('admin.auto_ai_review'),
            'pipeline_with_ai_default' => __('admin.pipeline_ai_default'),
        ];
        foreach ($toggles as $key => $label):
        ?>
        <label class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 cursor-pointer">
            <input type="checkbox" name="<?= $key ?>" value="1" <?= !empty($s[$key]) ? 'checked' : '' ?> class="w-4 h-4 rounded">
            <span class="text-sm"><?= htmlspecialchars($label) ?></span>
        </label>
        <?php endforeach; ?>
        <div class="pt-2">
            <label class="text-sm text-slate-600"><?= htmlspecialchars(__('admin.alert_days')) ?></label>
            <input type="number" name="alert_days_before" min="1" max="60" value="<?= (int)($s['alert_days_before'] ?? 7) ?>" class="w-24 px-3 py-2 rounded-lg border mt-1">
        </div>
        <p class="text-xs text-slate-500 pt-2"><?= htmlspecialchars(__('admin.automation_hint')) ?> <a href="/production?panel=automation" class="text-accent-700 hover:underline"><?= htmlspecialchars(__('admin.automation_link')) ?></a>.</p>
    </div>

    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm space-y-3">
        <h3 class="font-semibold"><?= htmlspecialchars(__('admin.permissions')) ?></h3>
        <label class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 cursor-pointer">
            <input type="checkbox" name="collaborateur_can_approve" value="1" <?= !empty($s['collaborateur_can_approve']) ? 'checked' : '' ?> class="w-4 h-4">
            <span class="text-sm"><?= htmlspecialchars(__('admin.can_approve')) ?></span>
        </label>
        <label class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 cursor-pointer">
            <input type="checkbox" name="collaborateur_can_submit" value="1" <?= !empty($s['collaborateur_can_submit']) ? 'checked' : '' ?> class="w-4 h-4">
            <span class="text-sm"><?= htmlspecialchars(__('admin.can_submit')) ?></span>
        </label>
    </div>

    <button type="submit" class="px-6 py-3 bg-accent-600 text-white rounded-xl font-medium"><?= htmlspecialchars(__('common.save')) ?></button>
</form>

<?php elseif ($tab === 'users'): ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <form method="post" action="/admin/users" class="bg-white rounded-2xl p-6 border shadow-sm space-y-3">
        <h3 class="font-semibold"><?= htmlspecialchars(__('admin.new_user')) ?></h3>
        <input name="name" required placeholder="<?= htmlspecialchars(__('common.name')) ?>" class="w-full px-3 py-2 rounded-lg border text-sm">
        <input name="email" type="email" required placeholder="<?= htmlspecialchars(__('common.email')) ?>" class="w-full px-3 py-2 rounded-lg border text-sm">
        <input name="password" type="password" required minlength="6" placeholder="<?= htmlspecialchars(__('common.password')) ?>" class="w-full px-3 py-2 rounded-lg border text-sm">
        <select name="role" class="w-full px-3 py-2 rounded-lg border text-sm">
            <option value="collaborateur"><?= htmlspecialchars(__('admin.role_collab')) ?></option>
            <option value="admin"><?= htmlspecialchars(__('admin.role_admin')) ?></option>
        </select>
        <button type="submit" class="w-full py-2.5 bg-accent-600 text-white rounded-xl text-sm"><?= htmlspecialchars(__('common.create')) ?></button>
    </form>
    <div class="lg:col-span-2 space-y-4">
        <?php foreach ($users as $u): ?>
        <form method="post" action="/admin/users/<?= $u['id'] ?>" class="bg-white rounded-2xl p-5 border flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[140px]"><label class="text-xs text-slate-400"><?= htmlspecialchars(__('common.name')) ?></label><input name="name" value="<?= htmlspecialchars($u['name']) ?>" class="w-full px-3 py-2 rounded-lg border text-sm"></div>
            <div class="flex-1 min-w-[180px]"><label class="text-xs text-slate-400"><?= htmlspecialchars(__('common.email_readonly')) ?></label><input value="<?= htmlspecialchars($u['email']) ?>" disabled class="w-full px-3 py-2 rounded-lg border bg-slate-50 text-sm"></div>
            <div><label class="text-xs text-slate-400"><?= htmlspecialchars(__('common.role')) ?></label><select name="role" class="px-3 py-2 rounded-lg border text-sm"><option value="admin" <?= $u['role']==='admin'?'selected':'' ?>><?= htmlspecialchars(__('common.admin')) ?></option><option value="collaborateur" <?= $u['role']==='collaborateur'?'selected':'' ?>><?= htmlspecialchars(__('common.collab_short')) ?></option></select></div>
            <div class="flex-1 min-w-[120px]"><label class="text-xs text-slate-400"><?= htmlspecialchars(__('admin.new_password')) ?></label><input name="password" type="password" placeholder="<?= htmlspecialchars(__('common.unassigned')) ?>" class="w-full px-3 py-2 rounded-lg border text-sm"></div>
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-sm"><?= htmlspecialchars(__('common.save')) ?></button>
        </form>
        <?php endforeach; ?>
    </div>
</div>

<?php elseif ($tab === 'rates'): ?>
<div class="bg-white rounded-2xl border overflow-hidden mb-6">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500"><tr><th class="px-4 py-2 text-left"><?= htmlspecialchars(__('common.code')) ?></th><th><?= htmlspecialchars(__('common.label')) ?></th><th><?= htmlspecialchars(__('common.rate')) ?> %</th><th><?= htmlspecialchars(__('common.type')) ?></th><th><?= htmlspecialchars(__('common.sector')) ?></th><th><?= htmlspecialchars(__('common.validity')) ?></th><th></th></tr></thead>
        <tbody class="divide-y">
        <?php foreach ($rates as $r): ?>
        <tr>
            <form method="post" action="/admin/rates/<?= $r['id'] ?>">
            <td class="px-2 py-2"><input name="code" value="<?= htmlspecialchars($r['code']) ?>" class="w-16 px-2 py-1 border rounded text-xs font-mono"></td>
            <td class="px-2"><input name="label" value="<?= htmlspecialchars($r['label']) ?>" class="w-full min-w-[120px] px-2 py-1 border rounded text-xs"></td>
            <td class="px-2"><input name="taux" value="<?= $r['taux'] ?>" class="w-16 px-2 py-1 border rounded text-xs font-mono"></td>
            <td class="px-2"><input name="declaration_type" value="<?= htmlspecialchars($r['declaration_type']) ?>" class="w-28 px-2 py-1 border rounded text-xs"></td>
            <td class="px-2"><input name="secteur" value="<?= htmlspecialchars($r['secteur'] ?? '') ?>" class="w-20 px-2 py-1 border rounded text-xs"></td>
            <td class="px-2 text-xs"><input type="date" name="valid_from" value="<?= $r['valid_from'] ?>" class="border rounded px-1"> → <input type="date" name="valid_to" value="<?= $r['valid_to'] ?? '' ?>" class="border rounded px-1"></td>
            <td class="px-2"><button type="submit" class="text-accent-600 text-xs"><?= htmlspecialchars(__('common.ok')) ?></button></td>
            </form>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<form method="post" action="/admin/rates" class="bg-slate-50 rounded-2xl p-6 flex flex-wrap gap-3 items-end">
    <h3 class="w-full font-semibold text-sm"><?= htmlspecialchars(__('admin.add_rate')) ?></h3>
    <input name="code" placeholder="R22" required class="px-3 py-2 rounded-lg border text-sm w-20">
    <input name="label" placeholder="<?= htmlspecialchars(__('admin.rate_label')) ?>" required class="flex-1 min-w-[150px] px-3 py-2 rounded-lg border text-sm">
    <input name="taux" placeholder="34.5" required class="w-20 px-3 py-2 rounded-lg border text-sm">
    <input name="declaration_type" placeholder="CNAS_MENSUELLE" required class="w-36 px-3 py-2 rounded-lg border text-sm">
    <input name="secteur" placeholder="BTP" class="w-24 px-3 py-2 rounded-lg border text-sm">
    <input type="date" name="valid_from" value="<?= date('Y-m-d') ?>" class="px-3 py-2 rounded-lg border text-sm">
    <button type="submit" class="px-4 py-2 bg-accent-600 text-white rounded-lg text-sm">+ <?= htmlspecialchars(__('common.add')) ?></button>
</form>

<?php elseif ($tab === 'deadlines'): ?>
<div class="space-y-4">
    <?php foreach ($deadlines as $d): ?>
    <form method="post" action="/admin/deadlines/<?= $d['id'] ?>" class="bg-white rounded-2xl p-5 border flex flex-wrap gap-4 items-center">
        <span class="font-mono text-accent-700 w-36"><?= htmlspecialchars($d['declaration_type']) ?></span>
        <input name="label_fr" value="<?= htmlspecialchars($d['label_fr']) ?>" class="flex-1 min-w-[200px] px-3 py-2 rounded-lg border text-sm">
        <label class="text-sm"><?= htmlspecialchars(__('common.day')) ?> <input name="due_day" type="number" min="1" max="31" value="<?= $d['due_day'] ?>" class="w-16 px-2 py-1 border rounded ml-1"></label>
        <label class="text-sm"><?= htmlspecialchars(__('common.month')) ?> <input name="due_month" type="number" min="1" max="12" value="<?= $d['due_month'] ?? '' ?>" class="w-16 px-2 py-1 border rounded ml-1" placeholder="<?= htmlspecialchars(__('common.unassigned')) ?>"></label>
        <span class="text-xs text-slate-400"><?= $d['frequency'] ?></span>
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-sm"><?= htmlspecialchars(__('common.save')) ?></button>
    </form>
    <?php endforeach; ?>
</div>

<?php elseif ($tab === 'system'): ?>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <?php foreach ($systemStats as $k => $v): ?>
    <div class="bg-white rounded-2xl p-5 border text-center">
        <p class="text-2xl font-bold text-navy-900"><?= $v ?></p>
        <p class="text-xs text-slate-500 uppercase mt-1"><?= str_replace('_', ' ', $k) ?></p>
    </div>
    <?php endforeach; ?>
</div>
<div class="bg-white rounded-2xl p-6 border text-sm space-y-2">
    <p><strong><?= htmlspecialchars(__('admin.openrouter')) ?></strong> <?= ($config['openrouter_api_key'] ?? '') !== '' ? htmlspecialchars(__('admin.configured')) . ' (' . htmlspecialchars($config['openrouter_model'] ?? '') . ')' : htmlspecialchars(__('admin.not_configured')) ?></p>
    <p><strong><?= htmlspecialchars(__('admin.timezone')) ?></strong> <?= htmlspecialchars($config['timezone'] ?? '') ?></p>
    <p><strong><?= htmlspecialchars(__('admin.tesseract')) ?></strong> <code class="text-xs bg-slate-100 px-1 rounded"><?= htmlspecialchars($config['tesseract_path'] ?? '') ?></code></p>
</div>
<div class="mt-6 bg-slate-50 rounded-2xl p-6">
    <h3 class="font-semibold mb-3"><?= htmlspecialchars(__('admin.recent_pipeline')) ?></h3>
    <?php if (empty($recentRuns)): ?>
    <p class="text-slate-400 text-sm"><?= htmlspecialchars(__('admin.no_runs')) ?></p>
    <?php else: foreach ($recentRuns as $run): ?>
    <p class="text-xs text-slate-600 py-1 border-b"><?= $run['run_type'] ?> — <?= date('d/m/Y H:i', strtotime($run['created_at'])) ?> — <?= htmlspecialchars($run['user_name'] ?? '') ?></p>
    <?php endforeach; endif; ?>
</div>
<?php endif; ?>
