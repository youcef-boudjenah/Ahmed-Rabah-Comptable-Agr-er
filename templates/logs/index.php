<?php $c = $counts; $tab = $tab ?? 'audit'; ?>
<div class="page-intro flex flex-wrap justify-between items-start gap-4 mb-6">
    <div>
        <p class="eyebrow"><?= htmlspecialchars(__('logs.eyebrow')) ?></p>
        <h2><?= htmlspecialchars(__('logs.title')) ?></h2>
        <p><?= htmlspecialchars(__('logs.subtitle')) ?></p>
    </div>
    <a href="/aide" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('common.guide_link')) ?></a>
</div>

<div class="stat-chips mb-5">
    <a href="/logs?tab=audit" class="stat-chip <?= $tab === 'audit' ? 'active chip-info' : '' ?>"><span class="num"><?= $c['audit'] ?></span><span class="lbl"><?= htmlspecialchars(__('logs.audit')) ?></span></a>
    <a href="/logs?tab=automation" class="stat-chip <?= $tab === 'automation' ? 'active' : '' ?>"><span class="num"><?= $c['automation'] ?></span><span class="lbl"><?= htmlspecialchars(__('logs.automations')) ?></span></a>
    <a href="/logs?tab=jobs" class="stat-chip <?= $tab === 'jobs' ? 'active' : '' ?>"><span class="num"><?= $c['jobs_pending'] ?></span><span class="lbl"><?= htmlspecialchars(__('logs.jobs_pending')) ?></span></a>
    <?php if ($c['jobs_failed'] > 0): ?>
    <span class="stat-chip chip-danger"><span class="num"><?= $c['jobs_failed'] ?></span><span class="lbl"><?= htmlspecialchars(__('logs.jobs_failed')) ?></span></span>
    <?php endif; ?>
</div>

<?php if ($tab === 'audit'): ?>
<div class="card overflow-hidden">
    <div class="card-header">
        <h3><?= htmlspecialchars(__('logs.audit_log')) ?></h3>
        <span class="badge badge-neutral"><?= htmlspecialchars(__('common.entries_count', ['n' => count($auditLogs)])) ?></span>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr><th><?= htmlspecialchars(__('common.date')) ?></th><th><?= htmlspecialchars(__('common.user')) ?></th><th><?= htmlspecialchars(__('common.action')) ?></th><th><?= htmlspecialchars(__('common.entity')) ?></th><th><?= htmlspecialchars(__('common.detail')) ?></th></tr>
            </thead>
            <tbody>
                <?php if (empty($auditLogs)): ?>
                <tr><td colspan="5" class="text-center text-slate-500 py-10"><?= htmlspecialchars(__('logs.no_audit')) ?></td></tr>
                <?php else: foreach ($auditLogs as $log):
                    $link = \App\Modules\Logs\ActivityLogService::entityLink($log['entity'], isset($log['entity_id']) ? (int) $log['entity_id'] : null);
                    $meta = $log['meta'] ? json_decode($log['meta'], true) : null;
                ?>
                <tr>
                    <td class="font-mono text-xs whitespace-nowrap"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                    <td class="text-sm"><?= htmlspecialchars($log['user_name'] ?? __('common.system')) ?></td>
                    <td><span class="badge badge-accent"><?= htmlspecialchars($log['action']) ?></span></td>
                    <td class="text-sm">
                        <?php if ($link): ?>
                        <a href="<?= $link ?>" class="text-accent-700 hover:underline"><?= htmlspecialchars($log['entity']) ?> #<?= (int) $log['entity_id'] ?></a>
                        <?php else: ?>
                        <?= htmlspecialchars($log['entity']) ?><?= $log['entity_id'] ? ' #' . (int) $log['entity_id'] : '' ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-xs text-slate-500 max-w-xs truncate"><?= $meta ? htmlspecialchars(json_encode($meta, JSON_UNESCAPED_UNICODE)) : __('common.unassigned') ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($tab === 'automation'): ?>
<div class="card overflow-hidden">
    <div class="card-header">
        <h3><?= htmlspecialchars(__('logs.pipeline_runs')) ?></h3>
        <a href="/production?panel=automation" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('common.see_pipeline_link')) ?></a>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th><?= htmlspecialchars(__('common.date')) ?></th><th><?= htmlspecialchars(__('common.type')) ?></th><th><?= htmlspecialchars(__('common.by')) ?></th><th><?= htmlspecialchars(__('common.duration')) ?></th><th><?= htmlspecialchars(__('common.result')) ?></th><th></th></tr>
            </thead>
            <tbody>
                <?php if (empty($automationRuns)): ?>
                <tr><td colspan="7" class="text-center text-slate-500 py-10"><?= htmlspecialchars(__('logs.no_runs')) ?></td></tr>
                <?php else: foreach ($automationRuns as $r):
                    $res = json_decode($r['result_json'] ?? '{}', true) ?: [];
                    $sum = $res['summary'] ?? [];
                ?>
                <tr>
                    <td class="font-mono text-xs"><?= $r['id'] ?></td>
                    <td class="font-mono text-xs"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                    <td class="text-xs"><?= htmlspecialchars($r['run_type']) ?></td>
                    <td class="text-sm"><?= htmlspecialchars($r['user_name'] ?? __('common.unassigned')) ?></td>
                    <td class="text-xs"><?= isset($res['duration_ms']) ? round($res['duration_ms'] / 1000, 1) . ' s' : __('common.unassigned') ?></td>
                    <td class="text-xs">
                        <?php if ($sum): ?>
                        <span class="text-emerald-700"><?= $sum['ok'] ?? 0 ?> <?= htmlspecialchars(__('common.ok')) ?></span>
                        <?php if (!empty($sum['error'])): ?><span class="text-red-600 ml-1"><?= $sum['error'] ?> <?= htmlspecialchars(__('logs.errors_short')) ?></span><?php endif; ?>
                        <?php else: ?><?= htmlspecialchars(__('common.unassigned')) ?><?php endif; ?>
                    </td>
                    <td class="text-right">
                        <a href="/production?run=<?= $r['id'] ?>&panel=automation" class="text-xs text-accent-700 hover:underline"><?= htmlspecialchars(__('common.detail')) ?></a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<div class="card overflow-hidden">
    <div class="card-header">
        <h3><?= htmlspecialchars(__('logs.job_queue')) ?></h3>
        <a href="/documents" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('common.ocr_docs_link')) ?></a>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th><?= htmlspecialchars(__('common.type')) ?></th><th><?= htmlspecialchars(__('common.status')) ?></th><th><?= htmlspecialchars(__('common.attempts')) ?></th><th><?= htmlspecialchars(__('common.created')) ?></th><th><?= htmlspecialchars(__('logs.error_col')) ?></th></tr>
            </thead>
            <tbody>
                <?php if (empty($jobs)): ?>
                <tr><td colspan="6" class="text-center text-slate-500 py-10"><?= htmlspecialchars(__('logs.queue_empty')) ?></td></tr>
                <?php else: foreach ($jobs as $j): ?>
                <tr>
                    <td class="font-mono text-xs"><?= $j['id'] ?></td>
                    <td class="text-xs font-mono"><?= htmlspecialchars($j['type']) ?></td>
                    <td>
                        <span class="badge <?= match($j['status']) {
                            'done' => 'badge-success',
                            'failed' => 'badge-danger',
                            'processing' => 'badge-info',
                            default => 'badge-warning'
                        } ?>"><?= $j['status'] ?></span>
                    </td>
                    <td class="text-center text-sm"><?= (int) $j['attempts'] ?></td>
                    <td class="font-mono text-xs"><?= date('d/m/Y H:i', strtotime($j['created_at'])) ?></td>
                    <td class="text-xs text-red-600 max-w-xs truncate"><?= htmlspecialchars($j['error_message'] ?? __('common.unassigned')) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
