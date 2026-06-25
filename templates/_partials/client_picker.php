<?php
/** @var string $name */
/** @var int|null $selectedId */
/** @var array<string, mixed>|null $selectedClient */
/** @var bool $required */
$name = $name ?? 'client_id';
$selectedId = $selectedId ?? null;
$selectedClient = $selectedClient ?? ($selectedId ? \App\Modules\Clients\ClientRepository::findLight((int) $selectedId) : null);
$required = $required ?? true;
$inputId = 'client-picker-' . preg_replace('/[^a-z0-9]/', '', $name);
/** @var bool $compact */
$compact = $compact ?? false;
?>
<div class="client-picker<?= $compact ? ' client-picker-compact' : '' ?>" data-picker-id="<?= $inputId ?>">
    <input type="hidden" name="<?= htmlspecialchars($name) ?>" id="<?= $inputId ?>-value" value="<?= $selectedId ? (int) $selectedId : '' ?>" <?= $required ? 'required' : '' ?>>
    <?php if (!$compact): ?>
    <label class="block text-xs font-medium text-slate-600 mb-1.5"><?= htmlspecialchars(__('common.client')) ?><?= $required ? ' *' : '' ?></label>
    <?php endif; ?>
    <div class="relative">
        <input type="text" id="<?= $inputId ?>-search" autocomplete="off" placeholder="<?= htmlspecialchars(__('common.client_search_placeholder')) ?>"
            value="<?= $selectedClient ? htmlspecialchars($selectedClient['raison_sociale']) : '' ?>"
            class="input w-full pr-8">
        <div id="<?= $inputId ?>-results" class="client-picker-results hidden"></div>
    </div>
    <p id="<?= $inputId ?>-hint" class="text-xs text-slate-400 mt-1<?= $compact ? ' hidden' : '' ?>">
        <?= $selectedClient ? htmlspecialchars($selectedClient['secteur'] . ($selectedClient['wilaya'] ? ' · ' . $selectedClient['wilaya'] : '')) : htmlspecialchars(__('common.client_search_hint')) ?>
    </p>
</div>
<script>
(function() {
    const root = document.querySelector('[data-picker-id="<?= $inputId ?>"]');
    if (!root || root.dataset.bound) return;
    root.dataset.bound = '1';
    const search = document.getElementById('<?= $inputId ?>-search');
    const hidden = document.getElementById('<?= $inputId ?>-value');
    const results = document.getElementById('<?= $inputId ?>-results');
    const hint = document.getElementById('<?= $inputId ?>-hint');
    const noClientMsg = <?= json_encode(__('common.no_client_found'), JSON_UNESCAPED_UNICODE) ?>;
    let timer = null;

    function hide() { results.classList.add('hidden'); results.innerHTML = ''; }

    search.addEventListener('input', () => {
        hidden.value = '';
        clearTimeout(timer);
        const q = search.value.trim();
        if (q.length < 2) { hide(); return; }
        timer = setTimeout(async () => {
            const res = await fetch('/clients/search?q=' + encodeURIComponent(q) + '&limit=15');
            const data = await res.json();
            if (!data.results?.length) {
                results.innerHTML = '<p class="p-3 text-xs text-slate-400">' + noClientMsg + '</p>';
            } else {
                results.innerHTML = data.results.map(c =>
                    `<button type="button" class="client-picker-item" data-id="${c.id}" data-label="${c.raison_sociale.replace(/"/g,'&quot;')}" data-meta="${c.secteur}${c.wilaya ? ' · '+c.wilaya : ''}">
                        <span class="font-medium">${c.raison_sociale}</span>
                        <span class="text-slate-400">${c.secteur}${c.wilaya ? ' · '+c.wilaya : ''}</span>
                    </button>`
                ).join('');
            }
            results.classList.remove('hidden');
        }, 250);
    });

    results.addEventListener('click', e => {
        const btn = e.target.closest('.client-picker-item');
        if (!btn) return;
        hidden.value = btn.dataset.id;
        search.value = btn.dataset.label;
        hint.textContent = btn.dataset.meta;
        hide();
    });

    document.addEventListener('click', e => {
        if (!root.contains(e.target)) hide();
    });
})();
</script>
