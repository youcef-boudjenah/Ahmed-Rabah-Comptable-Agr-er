<div class="page-intro flex flex-wrap justify-between items-start gap-4 mb-6">
    <div>
        <p class="eyebrow"><?= htmlspecialchars(__('reports.audit_eyebrow')) ?></p>
        <h2><?= htmlspecialchars(__('reports.audit_title')) ?></h2>
        <p><?= htmlspecialchars(__('reports.audit_subtitle')) ?></p>
    </div>
    <a href="/rapports" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('common.back_reports')) ?></a>
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <h3><?= htmlspecialchars(__('reports.action_history')) ?></h3>
        <span class="badge badge-neutral"><?= htmlspecialchars(__('common.entries_count', ['n' => count($logs)])) ?></span>
    </div>
    <div class="table-scroll" style="max-height: none;">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= htmlspecialchars(__('common.date')) ?></th>
                    <th><?= htmlspecialchars(__('common.user')) ?></th>
                    <th><?= htmlspecialchars(__('common.action')) ?></th>
                    <th><?= htmlspecialchars(__('common.entity')) ?></th>
                    <th><?= htmlspecialchars(__('reports.id')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr><td colspan="5" class="text-center text-slate-500 py-10"><?= htmlspecialchars(__('reports.no_audit')) ?></td></tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="font-mono text-xs text-slate-600"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                    <td class="font-medium text-slate-800"><?= htmlspecialchars($log['user_name'] ?? __('common.unassigned')) ?></td>
                    <td><span class="badge badge-accent"><?= htmlspecialchars($log['action']) ?></span></td>
                    <td class="text-slate-700"><?= htmlspecialchars($log['entity']) ?></td>
                    <td class="font-mono text-slate-600"><?= htmlspecialchars((string)($log['entity_id'] ?? __('common.unassigned'))) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
