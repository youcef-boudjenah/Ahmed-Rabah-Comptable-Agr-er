<?php
$run = $run ?? null;
if (!$run) {
    echo '<p class="text-slate-400">' . htmlspecialchars(__('automation.report_not_found')) . '</p>';
    return;
}
?>
<div class="mb-4">
    <a href="/automation" class="text-sm text-accent hover:underline"><?= htmlspecialchars(__('common.back_automation')) ?></a>
</div>
<?php require __DIR__ . '/_run_report.php'; ?>
