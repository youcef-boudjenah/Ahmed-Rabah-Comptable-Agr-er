<?php
/** @var array<string, mixed> $run */
$res = $run['result'] ?? [];
$steps = $res['steps'] ?? [];
$summary = $res['summary'] ?? [];
$statusIcon = ['ok' => '✓', 'skipped' => '—', 'error' => '✗', 'warning' => '!'];
?>
<div class="run-report">
    <div class="flex flex-wrap justify-between items-start gap-4 mb-4">
        <div>
            <p class="eyebrow">Rapport #<?= (int) $run['id'] ?></p>
            <h3 class="text-base font-semibold text-slate-900 mt-0.5">
                <?= htmlspecialchars($run['run_type']) ?> — <?= date('d/m/Y H:i:s', strtotime($run['created_at'])) ?>
            </h3>
            <p class="text-sm text-slate-500 mt-1">
                <?= htmlspecialchars($run['user_name'] ?? '—') ?>
                · Durée : <strong><?= number_format(($res['duration_ms'] ?? 0) / 1000, 1) ?> s</strong>
            </p>
        </div>
        <div class="flex gap-2 text-xs">
            <?php foreach (['ok' => 'OK', 'skipped' => 'Ignoré', 'warning' => 'Attention', 'error' => 'Erreur'] as $k => $lbl): ?>
            <?php if (!empty($summary[$k])): ?>
            <span class="badge badge-neutral"><?= $lbl ?> : <?= $summary[$k] ?></span>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="space-y-2">
        <?php foreach ($steps as $i => $step):
            $st = $step['status'] ?? 'ok';
        ?>
        <div class="step-item <?= $st ?>">
            <div class="flex flex-wrap justify-between gap-2">
                <p class="font-medium text-sm text-slate-800">
                    <span class="text-slate-400 mr-1"><?= $statusIcon[$st] ?? '·' ?></span>
                    Étape <?= $i + 1 ?> — <?= htmlspecialchars($step['label'] ?? '') ?>
                </p>
                <span class="text-xs font-mono text-slate-400"><?= $step['duration_ms'] ?? 0 ?> ms</span>
            </div>
            <p class="text-sm text-slate-600 mt-1.5"><?= htmlspecialchars($step['message'] ?? '') ?></p>
            <?php
            $details = $step['details'] ?? [];
            if (!empty($details['items']) && is_array($details['items'])): ?>
            <ul class="mt-2 text-xs space-y-1 max-h-28 overflow-y-auto font-mono bg-slate-50 rounded p-2 border border-slate-100">
                <?php foreach ($details['items'] as $item): ?>
                <li class="text-slate-600">
                    <?php if (isset($item['id'])): ?>#<?= $item['id'] ?> <?php endif; ?>
                    <?= htmlspecialchars($item['label'] ?? $item['name'] ?? '') ?>
                    <?php if (!empty($item['category'])): ?> → <?= $item['category'] ?><?php endif; ?>
                    <?php if (!empty($item['status']) && $item['status'] !== 'ok'): ?> (<?= $item['status'] ?><?= !empty($item['reason']) ? ': '.$item['reason'] : '' ?>)<?php endif; ?>
                    <?php if (!empty($item['id']) && ($step['id'] ?? '') === 'pdfs' && ($item['status'] ?? '') === 'ok'): ?>
                    — <a href="/declarations/<?= $item['id'] ?>/generated" target="_blank" class="text-accent hover:underline">voir</a>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php elseif (!empty($details['processed'])): ?>
            <p class="text-xs text-slate-500 mt-1.5"><?= count($details['processed']) ?> document(s) OCR traités</p>
            <?php elseif (!empty($details['errors'])): ?>
            <ul class="mt-2 text-xs text-red-700 space-y-0.5">
                <?php foreach ($details['errors'] as $err): ?>
                <li><?= htmlspecialchars(is_string($err) ? $err : ($err['error'] ?? json_encode($err))) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
