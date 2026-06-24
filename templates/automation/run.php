<?php
$run = $run ?? null;
if (!$run) {
    echo '<p class="text-slate-400">Rapport introuvable.</p>';
    return;
}
?>
<div class="mb-4">
    <a href="/automation" class="text-sm text-accent hover:underline">← Retour au traitement automatique</a>
</div>
<?php require __DIR__ . '/_run_report.php'; ?>
