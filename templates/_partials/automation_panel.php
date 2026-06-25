<?php
/** @var array $automationPreview */
/** @var array $automationStats */
/** @var list<array> $automationRuns */
/** @var bool $hasOpenRouter */
$p = $automationPreview ?? [];
$stats = $automationStats ?? [];
$recentRuns = $automationRuns ?? [];
$hasAi = $hasOpenRouter ?? false;
$stepDefs = [
    'recalc' => ['label' => __('automation.step_recalc'), 'hint' => __('automation.step_recalc_hint', ['payroll' => $p['payroll_entries'] ?? 0, 'sales' => $p['sales_entries'] ?? 0]), 'default' => true],
    'tasks' => ['label' => __('automation.step_tasks'), 'hint' => __('automation.step_tasks_hint'), 'default' => true],
    'pdfs' => ['label' => __('automation.step_pdfs'), 'hint' => __('automation.step_pdfs_hint', ['n' => $p['declarations_missing_pdf'] ?? 0]), 'default' => true],
    'ocr' => ['label' => __('automation.step_ocr'), 'hint' => __('automation.step_ocr_hint', ['queue' => $p['ocr_queue'] ?? 0, 'pending' => $p['documents_pending_ocr'] ?? 0]), 'default' => true],
    'ai_review' => ['label' => __('automation.step_ai_review'), 'hint' => __('automation.step_ai_review_hint', ['n' => $p['drafts_for_ai_review'] ?? 0]), 'default' => $hasAi, 'needs_ai' => true],
    'ai_classify' => ['label' => __('automation.step_ai_classify'), 'hint' => __('automation.step_ai_classify_hint', ['n' => $p['documents_to_classify'] ?? 0]), 'default' => $hasAi, 'needs_ai' => true],
];
?>
<?php if (!$hasAi): ?>
<div class="alert alert-warning mb-4">
    <?= htmlspecialchars(__('automation.ai_disabled')) ?> <code class="bg-white px-1 rounded text-xs">OPENROUTER_API_KEY</code> <?= htmlspecialchars(__('automation.ai_disabled_env')) ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <form method="post" action="/automation/run" class="card">
        <div class="card-header"><h3><?= htmlspecialchars(__('automation.step_control')) ?></h3></div>
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
            <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars(__('automation.launch_steps')) ?></button>
        </div>
    </form>

    <div class="space-y-3">
        <form method="post" action="/automation/batch-recalculate" class="card p-4 flex justify-between items-center gap-3">
            <div>
                <p class="font-medium text-sm"><?= htmlspecialchars(__('automation.recalc_only')) ?></p>
                <p class="text-xs text-slate-500"><?= htmlspecialchars(__('automation.recalc_after_import')) ?></p>
            </div>
            <button type="submit" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('common.execute')) ?></button>
        </form>
        <form method="post" action="/automation/generate-pdfs" class="card p-4 flex justify-between items-center gap-3">
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
    </div>
</div>

<?php if (!empty($recentRuns)): ?>
<div class="card mt-5">
    <div class="card-header">
        <h3><?= htmlspecialchars(__('automation.recent_history')) ?></h3>
        <span class="text-xs text-slate-400"><?= htmlspecialchars(__('common.runs_count', ['n' => count($recentRuns)])) ?></span>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>#</th><th><?= htmlspecialchars(__('common.date')) ?></th><th><?= htmlspecialchars(__('common.type')) ?></th><th><?= htmlspecialchars(__('common.result')) ?></th><th></th></tr></thead>
            <tbody>
                <?php foreach (array_slice($recentRuns, 0, 8) as $r):
                    $res = json_decode($r['result_json'] ?? '{}', true) ?: [];
                    $sum = $res['summary'] ?? [];
                ?>
                <tr>
                    <td class="font-mono text-xs"><?= $r['id'] ?></td>
                    <td class="font-mono text-xs"><?= date('d/m H:i', strtotime($r['created_at'])) ?></td>
                    <td class="text-xs"><?= htmlspecialchars($r['run_type']) ?></td>
                    <td class="text-xs"><?= !empty($sum['ok']) ? ($sum['ok'] . ' ' . __('common.ok')) : __('common.unassigned') ?></td>
                    <td class="text-right"><a href="/production?run=<?= $r['id'] ?>&panel=automation" class="text-xs text-accent hover:underline"><?= htmlspecialchars(__('common.detail')) ?></a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
