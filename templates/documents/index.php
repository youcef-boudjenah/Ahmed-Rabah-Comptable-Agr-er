<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        <form method="post" action="/documents/upload" enctype="multipart/form-data"
              class="card"
              x-data="{ dragging: false }"
              @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
              @drop.prevent="dragging = false; $refs.file.files = $event.dataTransfer.files">
            <div class="card-body space-y-4">
                <h3 class="font-semibold text-slate-900">Upload document</h3>
                <div :class="dragging ? 'border-teal-500 bg-teal-50' : 'border-slate-200'"
                     class="border-2 border-dashed rounded-lg p-8 text-center transition bg-slate-50">
                    <p class="text-slate-500 text-sm">PDF, image — fiche paie, CNAS, G50</p>
                    <input type="file" name="document" required accept=".pdf,.png,.jpg,.jpeg" x-ref="file" class="mt-4 text-sm w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Client (optionnel)</label>
                    <?php $name = 'client_id'; $required = false; $compact = false; $selectedClientId = $selectedClientId ?? null; require ROOT_PATH . '/templates/_partials/client_picker.php'; ?>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="process_now" value="1" checked> Traiter OCR immédiatement
                </label>
                <button type="submit" class="btn btn-primary btn-block">Uploader</button>
            </div>
        </form>
    </div>
    <div class="lg:col-span-2 card overflow-hidden">
        <table class="data-table">
            <thead>
                <tr><th>Fichier</th><th>Type</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (empty($documents)): ?>
                <tr><td colspan="4" class="text-center py-10 text-slate-500">Aucun document.</td></tr>
                <?php else: foreach ($documents as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['original_name']) ?></td>
                    <td class="font-mono text-xs"><?= htmlspecialchars($d['doc_type'] ?? '—') ?></td>
                    <td>
                        <span class="badge <?= match($d['status']) {
                            'done' => 'badge-success',
                            'awaiting_review' => 'badge-warning',
                            'failed' => 'badge-danger',
                            'processing' => 'badge-info',
                            default => 'badge-neutral'
                        } ?>"><?= $d['status'] ?></span>
                    </td>
                    <td class="text-right"><a href="/documents/<?= $d['id'] ?>" class="text-teal-700 hover:underline text-sm">Détail</a></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
