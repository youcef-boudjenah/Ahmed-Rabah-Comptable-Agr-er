<?php
$hasAi = $hasOpenRouter ?? false;
$p = $preview ?? [];
$stepDefs = [
    'recalc' => ['label' => __('automation.step_recalc'), 'hint' => __('automation.step_recalc_hint', ['payroll' => $p['payroll_entries'], 'sales' => $p['sales_entries']]), 'default' => true],
    'tasks' => ['label' => __('automation.step_tasks'), 'hint' => __('automation.step_tasks_hint'), 'default' => true],
    'pdfs' => ['label' => __('automation.step_pdfs'), 'hint' => __('automation.step_pdfs_hint', ['n' => $p['declarations_missing_pdf']]), 'default' => true],
    'ocr' => ['label' => __('automation.step_ocr'), 'hint' => __('automation.step_ocr_hint', ['queue' => $p['ocr_queue'], 'pending' => $p['documents_pending_ocr']]), 'default' => true],
    'ai_review' => ['label' => __('automation.step_ai_review'), 'hint' => __('automation.step_ai_review_hint', ['n' => $p['drafts_for_ai_review']]), 'default' => $hasAi, 'needs_ai' => true],
    'ai_classify' => ['label' => __('automation.step_ai_classify'), 'hint' => __('automation.step_ai_classify_hint', ['n' => $p['documents_to_classify']]), 'default' => $hasAi, 'needs_ai' => true],
];
?>
<div class="page-intro">
    <p class="eyebrow"><?= htmlspecialchars(__('automation.eyebrow')) ?></p>
    <h2><?= htmlspecialchars(__('automation.title')) ?></h2>
    <p><?= htmlspecialchars(__('automation.subtitle')) ?></p>
</div>

<?php if (!empty($highlightRun)): ?>
<?php $run = $highlightRun; require __DIR__ . '/_run_report.php'; ?>
<?php endif; ?>

<?php if (!$hasAi): ?>
<div class="alert alert-warning mb-6">
    <?= htmlspecialchars(__('automation.ai_disabled')) ?> <code class="bg-white px-1 rounded text-xs">OPENROUTER_API_KEY</code> <?= htmlspecialchars(__('automation.ai_disabled_env')) ?>
</div>
<?php endif; ?>

<div class="card mb-6">
    <div class="card-header"><h3><?= htmlspecialchars(__('automation.preview')) ?></h3></div>
    <div class="card-body">
        <div class="metric-grid mb-4">
            <div class="metric-box"><p class="num"><?= $p['payroll_entries'] ?? 0 ?></p><p class="lbl"><?= htmlspecialchars(__('automation.payroll_entries')) ?></p></div>
            <div class="metric-box"><p class="num"><?= $p['sales_entries'] ?? 0 ?></p><p class="lbl"><?= htmlspecialchars(__('automation.sales_entries')) ?></p></div>
            <div class="metric-box"><p class="num"><?= $p['declarations_missing_pdf'] ?? 0 ?></p><p class="lbl"><?= htmlspecialchars(__('automation.bordereaux_to_gen')) ?></p></div>
            <div class="metric-box"><p class="num"><?= ($p['ocr_queue'] ?? 0) + ($p['documents_pending_ocr'] ?? 0) ?></p><p class="lbl"><?= htmlspecialchars(__('automation.ocr_pending')) ?></p></div>
        </div>
        <div class="metric-grid">
            <div class="metric-box"><p class="num text-amber-700"><?= $stats['draft_ready_count'] ?? 0 ?></p><p class="lbl"><?= htmlspecialchars(__('automation.drafts_ready')) ?></p></div>
            <div class="metric-box"><p class="num text-orange-700"><?= $stats['missing_data_count'] ?? 0 ?></p><p class="lbl"><?= htmlspecialchars(__('automation.missing_data')) ?></p></div>
            <div class="metric-box"><p class="num text-red-700"><?= $stats['overdue_count'] ?? 0 ?></p><p class="lbl"><?= htmlspecialchars(__('automation.overdue')) ?></p></div>
            <div class="metric-box"><p class="num"><?= $p['open_tasks'] ?? 0 ?></p><p class="lbl"><?= htmlspecialchars(__('automation.open_tasks')) ?></p></div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
    <form method="post" action="/automation/run" class="card">
        <div class="card-header">
            <h3><?= htmlspecialchars(__('automation.step_control')) ?></h3>
        </div>
        <div class="card-body">
            <div class="space-y-2 mb-5">
                <?php foreach ($stepDefs as $key => $def):
                    $disabled = !empty($def['needs_ai']) && !$hasAi;
                ?>
                <label class="flex items-start gap-3 p-3 rounded-md border border-slate-200 hover:bg-slate-50 cursor-pointer <?= $disabled ? 'opacity-40' : '' ?>">
                    <input type="checkbox" name="step_<?= $key ?>" value="1"
                        <?= $def['default'] && !$disabled ? 'checked' : '' ?>
                        <?= $disabled ? 'disabled' : '' ?>
                        class="mt-0.5 rounded">
                    <div>
                        <p class="font-medium text-sm"><?= htmlspecialchars($def['label']) ?></p>
                        <p class="text-xs text-slate-500"><?= htmlspecialchars($def['hint']) ?></p>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="flex gap-2 mb-5">
                <button type="button" onclick="document.querySelectorAll('input[name^=step_]:not(:disabled)').forEach(c=>c.checked=true)" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('automation.select_all')) ?></button>
                <button type="button" onclick="document.querySelectorAll('input[name^=step_]').forEach(c=>c.checked=false)" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('automation.deselect_all')) ?></button>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg"><?= htmlspecialchars(__('automation.launch_steps')) ?></button>
            <p class="text-center text-xs text-slate-400 mt-2"><?= htmlspecialchars(__('automation.report_after')) ?></p>
        </div>
    </form>

    <div class="space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?= htmlspecialchars(__('automation.shortcuts')) ?></p>

        <form method="post" action="/automation/batch-recalculate" class="card p-4 flex justify-between items-center">
            <div>
                <p class="font-medium text-sm"><?= htmlspecialchars(__('automation.recalc_only')) ?></p>
                <p class="text-xs text-slate-500"><?= htmlspecialchars(__('automation.recalc_hint')) ?></p>
            </div>
            <button type="submit" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('common.execute')) ?></button>
        </form>

        <form method="post" action="/automation/generate-pdfs" class="card p-4 flex justify-between items-center">
            <div>
                <p class="font-medium text-sm"><?= htmlspecialchars(__('automation.bordereaux_only')) ?></p>
                <p class="text-xs text-slate-500"><?= htmlspecialchars(__('automation.files_to_create', ['n' => $p['declarations_missing_pdf'] ?? 0])) ?></p>
            </div>
            <button type="submit" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('common.execute')) ?></button>
        </form>

        <form method="post" action="/automation/run-all" class="card p-5 bg-slate-900 text-white border-slate-800">
            <p class="font-semibold text-sm"><?= htmlspecialchars(__('automation.full_pipeline')) ?></p>
            <p class="text-xs text-white/60 mt-1 mb-4"><?= htmlspecialchars(__('automation.full_pipeline_hint')) ?></p>
            <?php if ($hasAi): ?>
            <label class="flex items-center gap-2 text-xs mb-4 text-white/80">
                <input type="checkbox" name="with_ai" value="1" checked class="rounded"> <?= htmlspecialchars(__('automation.include_ai')) ?>
            </label>
            <?php endif; ?>
            <button type="submit" class="btn btn-block bg-white text-slate-900 hover:bg-slate-100 border-0"><?= htmlspecialchars(__('automation.launch_all')) ?></button>
        </form>

        <?php if (($user['role'] ?? '') === 'admin'): ?>
        <a href="/admin?tab=settings" class="block text-center text-xs text-slate-500 hover:text-accent pt-1"><?= htmlspecialchars(__('automation.admin_settings')) ?></a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><?= htmlspecialchars(__('automation.run_history')) ?></h3>
        <span class="text-xs text-slate-400"><?= htmlspecialchars(__('common.entries_count', ['n' => count($recentRuns)])) ?></span>
    </div>
    <?php if (empty($recentRuns)): ?>
    <p class="text-slate-400 text-sm py-10 text-center"><?= htmlspecialchars(__('automation.no_runs')) ?></p>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th><th><?= htmlspecialchars(__('common.date')) ?></th><th><?= htmlspecialchars(__('common.type')) ?></th><th><?= htmlspecialchars(__('common.by')) ?></th><th><?= htmlspecialchars(__('common.duration')) ?></th><th><?= htmlspecialchars(__('common.result')) ?></th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentRuns as $r):
                    $res = json_decode($r['result_json'] ?? '{}', true) ?: [];
                    $sum = $res['summary'] ?? [];
                    $steps = $res['steps'] ?? [];
                ?>
                <tr>
                    <td class="font-mono text-xs"><?= $r['id'] ?></td>
                    <td class="font-mono text-xs"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                    <td class="text-xs"><?= htmlspecialchars($r['run_type']) ?></td>
                    <td><?= htmlspecialchars($r['user_name'] ?? __('common.unassigned')) ?></td>
                    <td class="text-xs"><?= isset($res['duration_ms']) ? round($res['duration_ms']/1000, 1).' s' : __('common.unassigned') ?></td>
                    <td class="text-xs">
                        <?php if ($sum): ?>
                        <span class="text-emerald-700"><?= $sum['ok'] ?? 0 ?> <?= htmlspecialchars(__('common.ok')) ?></span>
                        <?php if (!empty($sum['error'])): ?><span class="text-red-600 ml-1"><?= $sum['error'] ?> <?= htmlspecialchars(__('logs.errors_short')) ?></span><?php endif; ?>
                        <?php elseif (isset($res['recalc'])): ?>
                        <span class="text-slate-400"><?= htmlspecialchars(__('automation.legacy_format')) ?></span>
                        <?php else: ?>
                        <?= htmlspecialchars(__('automation.steps_count', ['n' => count($steps)])) ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <a href="/automation?run=<?= $r['id'] ?>" class="text-xs font-medium text-accent hover:underline"><?= htmlspecialchars(__('common.detail')) ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
