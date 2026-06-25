<?php
/** @var array<string, mixed> $declaration */
/** @var array<string, mixed> $bordereauMeta */
/** @var string $typeLabel */
/** @var string $periodLabel */
$id = (int) $declaration['id'];
$meta = $bordereauMeta ?? [];
$hasArchive = !empty($meta['has_archive']);
$hasPdf = !empty($meta['has_pdf']);
$gedCat = \App\Modules\Automation\WorkflowService::gedCategoryForDeclaration($declaration['type']);
$generatedLabel = !empty($meta['generated_at'])
    ? date('d/m/Y H:i', (int) $meta['generated_at'])
    : null;
?>
<div class="card mb-6">
    <div class="card-header">
        <h3><?= htmlspecialchars(__('common.doc_exports')) ?></h3>
        <?php if ($hasArchive): ?>
        <span class="badge badge-accent"><?= htmlspecialchars(__('common.doc_archived')) ?><?= $generatedLabel ? ' · ' . $generatedLabel : '' ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body space-y-4">
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <a href="/declarations/<?= $id ?>/print" target="_blank" rel="noopener"
               class="doc-action doc-action-preview group">
                <span class="doc-action-icon">🖨</span>
                <span>
                    <span class="doc-action-title"><?= htmlspecialchars(__('common.doc_preview_print')) ?></span>
                    <span class="doc-action-desc"><?= htmlspecialchars(__('common.doc_preview_desc')) ?></span>
                </span>
            </a>

            <?php if ($hasArchive): ?>
            <a href="/declarations/<?= $id ?>/generated" target="_blank" rel="noopener"
               class="doc-action doc-action-archive group">
                <span class="doc-action-icon">📄</span>
                <span>
                    <span class="doc-action-title"><?= $hasPdf ? htmlspecialchars(__('common.doc_pdf_archived')) : htmlspecialchars(__('common.doc_bordereau_archived')) ?></span>
                    <span class="doc-action-desc"><?= htmlspecialchars(__('common.doc_frozen_version')) ?><?= $generatedLabel ? ' ' . htmlspecialchars(__('common.doc_frozen_on', ['date' => $generatedLabel])) : '' ?></span>
                </span>
            </a>

            <?php if ($hasPdf): ?>
            <a href="/declarations/<?= $id ?>/generated?download=1"
               class="doc-action doc-action-download group">
                <span class="doc-action-icon">⬇</span>
                <span>
                    <span class="doc-action-title"><?= htmlspecialchars(__('common.doc_download_pdf')) ?></span>
                    <span class="doc-action-desc"><?= htmlspecialchars(__('common.doc_pdf_ged')) ?></span>
                </span>
            </a>
            <?php endif; ?>

            <form method="post" action="/declarations/<?= $id ?>/generate-pdf" class="contents">
                <button type="submit" class="doc-action doc-action-regen group w-full text-left">
                    <span class="doc-action-icon">↻</span>
                    <span>
                        <span class="doc-action-title"><?= $hasArchive ? htmlspecialchars(__('common.doc_regenerate')) : htmlspecialchars(__('common.doc_generate_archive')) ?></span>
                        <span class="doc-action-desc"><?= $hasArchive ? htmlspecialchars(__('common.doc_regenerate_desc')) : htmlspecialchars(__('common.doc_generate_desc')) ?></span>
                    </span>
                </button>
            </form>

            <a href="/declarations/<?= $id ?>/export-csv"
               class="doc-action doc-action-export group">
                <span class="doc-action-icon">📊</span>
                <span>
                    <span class="doc-action-title"><?= htmlspecialchars(__('common.doc_export_csv')) ?></span>
                    <span class="doc-action-desc"><?= htmlspecialchars(__('common.doc_export_csv_desc')) ?></span>
                </span>
            </a>

            <?php if (!empty($declaration['receipt_path']) && is_file($declaration['receipt_path'])): ?>
            <a href="/declarations/<?= $id ?>/receipt" target="_blank" rel="noopener"
               class="doc-action doc-action-receipt group">
                <span class="doc-action-icon">🧾</span>
                <span>
                    <span class="doc-action-title"><?= htmlspecialchars(__('common.doc_receipt')) ?></span>
                    <span class="doc-action-desc"><?= htmlspecialchars(__('common.doc_receipt_desc')) ?></span>
                </span>
            </a>
            <a href="/declarations/<?= $id ?>/receipt?download=1"
               class="doc-action doc-action-download group">
                <span class="doc-action-icon">⬇</span>
                <span>
                    <span class="doc-action-title"><?= htmlspecialchars(__('common.doc_download_receipt')) ?></span>
                    <span class="doc-action-desc"><?= htmlspecialchars(__('common.doc_receipt_copy')) ?></span>
                </span>
            </a>
            <?php endif; ?>

            <a href="/clients/<?= (int) $declaration['client_id'] ?>/dossier?cat=<?= htmlspecialchars($gedCat) ?>"
               class="doc-action doc-action-ged group">
                <span class="doc-action-icon">📁</span>
                <span>
                    <span class="doc-action-title"><?= htmlspecialchars(__('common.doc_ged_folder')) ?></span>
                    <span class="doc-action-desc"><?= htmlspecialchars(__('common.doc_ged_all', ['type' => $typeLabel])) ?></span>
                </span>
            </a>
        </div>

        <?php if ($hasArchive && $hasPdf): ?>
        <p class="text-xs text-slate-500 border-t border-slate-100 pt-3">
            <strong class="text-slate-600"><?= htmlspecialchars(__('common.doc_preview_vs_archived')) ?></strong>
            <?= htmlspecialchars(__('common.doc_preview_vs_archived_desc')) ?>
            <?= $generatedLabel ? '(' . $generatedLabel . ')' : '' ?>.
        </p>
        <?php endif; ?>
    </div>
</div>
