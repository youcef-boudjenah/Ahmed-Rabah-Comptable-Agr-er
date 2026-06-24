<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 h-[calc(100vh-12rem)]">
    <div class="lg:col-span-1 bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
        <h3 class="font-semibold text-navy-900 mb-4">Contexte</h3>
        <form method="get" class="mb-4">
            <label class="text-xs text-slate-500 block mb-1">Client (optionnel)</label>
            <select name="client" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-lg border text-sm">
                <option value="">Tout le cabinet</option>
                <?php foreach ($clients as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($clientId ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['raison_sociale']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <form method="post" action="/assistant/new">
            <button type="submit" class="w-full py-2 text-sm border border-slate-200 rounded-lg hover:bg-slate-50">Nouvelle conversation</button>
        </form>
        <div class="mt-6 space-y-2 text-xs text-slate-500">
            <p class="font-medium text-slate-700">Exemples :</p>
            <button type="button" onclick="askExample(this)" class="block text-left w-full p-2 rounded hover:bg-slate-50">Quelles déclarations sont en retard ?</button>
            <button type="button" onclick="askExample(this)" class="block text-left w-full p-2 rounded hover:bg-slate-50">CNAS BOUALAM ce mois ?</button>
            <button type="button" onclick="askExample(this)" class="block text-left w-full p-2 rounded hover:bg-slate-50">Documents à traiter ?</button>
        </div>
    </div>

    <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-100 shadow-sm flex flex-col" id="chat-panel"
         data-client="<?= (int)($clientId ?? 0) ?>">
        <div class="px-6 py-4 border-b bg-gradient-to-r from-navy-900 to-teal-700 text-white rounded-t-2xl">
            <h2 class="font-semibold">Assistant IA comptable</h2>
            <p class="text-xs text-white/70 mt-1">CNAS, G50, échéances, dossiers — données live du cabinet</p>
        </div>
        <div id="chat-messages" class="flex-1 overflow-y-auto p-6 space-y-4">
            <?php foreach ($history as $msg): ?>
            <div class="<?= $msg['role'] === 'user' ? 'ml-8' : 'mr-8' ?>">
                <div class="inline-block max-w-[85%] px-4 py-3 rounded-2xl text-sm <?= $msg['role'] === 'user' ? 'bg-teal-600 text-white float-right' : 'bg-slate-100 text-slate-800' ?>">
                    <?= nl2br(htmlspecialchars($msg['content'])) ?>
                </div>
                <div class="clear-both"></div>
            </div>
            <?php endforeach; ?>
        </div>
        <form id="chat-form" class="p-4 border-t flex gap-2">
            <input type="text" id="chat-input" placeholder="Posez votre question..." autocomplete="off"
                   class="flex-1 px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-teal-500 outline-none">
            <button type="submit" class="px-6 py-3 bg-teal-600 text-white rounded-xl font-medium hover:bg-teal-500 disabled:opacity-50" id="chat-send">Envoyer</button>
        </form>
    </div>
</div>

<script>
function askExample(btn) {
    document.getElementById('chat-input').value = btn.textContent.trim();
    document.getElementById('chat-form').dispatchEvent(new Event('submit'));
}
document.getElementById('chat-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const input = document.getElementById('chat-input');
    const msg = input.value.trim();
    if (!msg) return;
    const panel = document.getElementById('chat-panel');
    const box = document.getElementById('chat-messages');
    const send = document.getElementById('chat-send');
    input.value = '';
    send.disabled = true;
    box.innerHTML += `<div class="ml-8"><div class="inline-block max-w-[85%] px-4 py-3 rounded-2xl text-sm bg-teal-600 text-white float-right">${msg.replace(/</g,'&lt;')}</div><div class="clear-both"></div></div>`;
    box.innerHTML += `<div class="mr-8" id="typing"><div class="inline-block px-4 py-3 rounded-2xl bg-slate-100 text-slate-400 text-sm">Réflexion...</div></div>`;
    box.scrollTop = box.scrollHeight;
    try {
        const res = await fetch('/assistant/chat', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({message: msg, client_id: panel.dataset.client || null})
        });
        const data = await res.json();
        document.getElementById('typing')?.remove();
        const reply = data.reply || data.error || 'Erreur';
        box.innerHTML += `<div class="mr-8"><div class="inline-block max-w-[85%] px-4 py-3 rounded-2xl text-sm bg-slate-100 text-slate-800">${reply.replace(/</g,'&lt;').replace(/\n/g,'<br>')}</div><div class="clear-both"></div></div>`;
    } catch {
        document.getElementById('typing')?.remove();
        box.innerHTML += `<div class="mr-8"><div class="inline-block px-4 py-3 rounded-2xl bg-red-50 text-red-600 text-sm">Erreur réseau</div></div>`;
    }
    send.disabled = false;
    box.scrollTop = box.scrollHeight;
});
</script>
