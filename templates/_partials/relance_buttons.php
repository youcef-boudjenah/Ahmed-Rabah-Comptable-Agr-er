<?php
/** @var array $rel Relance links from RelanceService::linksFor() */
$rel = $rel ?? ($row['relance'] ?? \App\Modules\Relances\RelanceService::linksFor($row ?? []));
?>
<?php if (!empty($rel['whatsapp'])): ?>
<a href="<?= htmlspecialchars($rel['whatsapp']) ?>" target="_blank" rel="noopener" class="btn btn-sm" style="background:#25D366;color:#fff;border-color:#25D366" title="WhatsApp">WhatsApp</a>
<?php endif; ?>
<?php if (!empty($rel['email'])): ?>
<a href="<?= htmlspecialchars($rel['email']) ?>" class="btn btn-secondary btn-sm" title="Email">Email</a>
<?php endif; ?>
<?php if (empty($rel['whatsapp']) && empty($rel['email']) && in_array($row['status'] ?? '', ['missing_data', 'overdue'], true)): ?>
<button type="button" class="btn btn-ghost btn-sm copy-relance" data-message="<?= htmlspecialchars($rel['message']) ?>" title="Copier message">Copier</button>
<?php endif; ?>
