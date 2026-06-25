<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        <form method="post" action="/documents/upload" enctype="multipart/form-data"
              class="card"
              x-data="{ dragging: false }"
              @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
              @drop.prevent="dragging = false; $refs.file.files = $event.dataTransfer.files">
            <div class="card-body space-y-4">
                <h3 class="font-semibold text-slate-900"><?= htmlspecialchars(__('documents.new_document')) ?></h3>
                <div :class="dragging ? 'border-accent-500 bg-accent-50' : 'border-slate-200'"
                     class="border-2 border-dashed rounded-lg p-8 text-center transition bg-slate-50">
                    <p class="text-slate-500 text-sm"><?= htmlspecialchars(__('documents.drop_hint')) ?></p>
                    <input type="file" name="document" required accept=".pdf,.png,.jpg,.jpeg,.xlsx,.xls" x-ref="file" class="mt-4 text-sm w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1"><?= htmlspecialchars(__('documents.client_optional')) ?></label>
                    <?php $name = 'client_id'; $required = false; $compact = false; $selectedClientId = $selectedClientId ?? null; require ROOT_PATH . '/templates/_partials/client_picker.php'; ?>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="process_now" value="1" checked> <?= htmlspecialchars(__('documents.ocr_immediate')) ?>
                </label>
                <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars(__('documents.create_upload')) ?></button>
            </div>
        </form>
    </div>
    <div class="lg:col-span-2 card overflow-hidden">
        <div class="card-header">
            <h3><?= htmlspecialchars(__('documents.all_documents')) ?></h3>
            <a href="/ged" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('common.ged_link')) ?></a>
        </div>
        <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr><th><?= htmlspecialchars(__('common.file')) ?></th><th><?= htmlspecialchars(__('common.client')) ?></th><th><?= htmlspecialchars(__('common.category')) ?></th><th><?= htmlspecialchars(__('common.statuses')) ?></th><th></th></tr>
            </thead>
            <tbody>
                <?php if (empty($documents)): ?>
                <tr><td colspan="5" class="text-center py-10 text-slate-500"><?= htmlspecialchars(__('documents.no_documents')) ?></td></tr>
                <?php else: foreach ($documents as $d): ?>
                <tr>
                    <td>
                        <p class="font-medium text-sm"><?= htmlspecialchars($d['title'] ?? $d['original_name']) ?></p>
                        <p class="text-xs text-slate-400"><?= date('d/m/Y', strtotime($d['created_at'])) ?></p>
                    </td>
                    <td class="text-sm">
                        <?php if ($d['raison_sociale']): ?>
                        <a href="/clients/<?= $d['client_id'] ?>/dossier" class="text-accent-700 hover:underline"><?= htmlspecialchars($d['raison_sociale']) ?></a>
                        <?php else: ?><span class="text-slate-400"><?= htmlspecialchars(__('common.not_assigned')) ?></span><?php endif; ?>
                    </td>
                    <td><span class="badge badge-neutral text-xs"><?= $d['category'] ?? __('common.unassigned') ?></span></td>
                    <td class="text-xs space-y-0.5">
                        <span class="badge <?= match($d['status']) {
                            'done' => 'badge-success',
                            'awaiting_review' => 'badge-warning',
                            'failed' => 'badge-danger',
                            'processing' => 'badge-info',
                            default => 'badge-neutral'
                        } ?>"><?= $d['status'] ?></span>
                        <?php if (!empty($d['ged_status'])): ?>
                        <span class="block text-slate-400"><?= $d['ged_status'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php require ROOT_PATH . '/templates/_partials/document_actions.php'; ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
