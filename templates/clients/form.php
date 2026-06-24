<?php $isEdit = $client !== null; ?>
<form method="post" action="<?= $isEdit ? '/clients/' . $client['id'] : '/clients' ?>" class="max-w-2xl bg-white rounded-2xl p-8 shadow-sm border border-slate-100 space-y-5">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Raison sociale *</label>
        <input name="raison_sociale" required value="<?= htmlspecialchars($client['raison_sociale'] ?? '') ?>"
               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">NIF</label>
            <input name="nif" value="<?= htmlspecialchars($client['nif'] ?? '') ?>"
                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">N° cotisant CNAS</label>
            <input name="numero_cotisant" value="<?= htmlspecialchars($client['numero_cotisant'] ?? '') ?>"
                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
    </div>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Secteur</label>
            <select name="secteur" class="w-full px-4 py-2.5 rounded-xl border border-slate-200">
                <?php foreach (['BTP','SERVICES','COMMERCE','AUTO_ENTREPRENEUR','AUTRE'] as $s): ?>
                <option value="<?= $s ?>" <?= ($client['secteur'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Régime fiscal</label>
            <select name="regime_fiscal" class="w-full px-4 py-2.5 rounded-xl border border-slate-200">
                <?php foreach (['MENSUEL','TRIMESTRIEL','ANNUEL'] as $r): ?>
                <option value="<?= $r ?>" <?= ($client['regime_fiscal'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">CNAS</label>
            <select name="cnas_regime" class="w-full px-4 py-2.5 rounded-xl border border-slate-200">
                <option value="MENSUEL" <?= ($client['cnas_regime'] ?? '') === 'MENSUEL' ? 'selected' : '' ?>>Mensuel</option>
                <option value="TRIMESTRIEL" <?= ($client['cnas_regime'] ?? '') === 'TRIMESTRIEL' ? 'selected' : '' ?>>Trimestriel</option>
            </select>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Wilaya</label>
        <input name="wilaya" value="<?= htmlspecialchars($client['wilaya'] ?? '') ?>"
               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-teal-500 outline-none">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Adresse</label>
        <textarea name="adresse" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-slate-200"><?= htmlspecialchars($client['adresse'] ?? '') ?></textarea>
    </div>
    <div class="border-t border-slate-100 pt-5 mt-2">
        <p class="text-sm font-semibold text-slate-800 mb-3">Contact relances</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nom contact</label>
                <input name="contact_name" value="<?= htmlspecialchars($client['contact_name'] ?? '') ?>"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Téléphone / WhatsApp</label>
                <input name="contact_phone" value="<?= htmlspecialchars($client['contact_phone'] ?? '') ?>" placeholder="05 XX XX XX XX"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="contact_email" value="<?= htmlspecialchars($client['contact_email'] ?? '') ?>"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Activité</label>
        <input name="activite" value="<?= htmlspecialchars($client['activite'] ?? '') ?>"
               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-teal-500 outline-none">
    </div>
    <button type="submit" class="px-6 py-2.5 bg-teal-600 hover:bg-teal-500 text-white rounded-xl font-medium transition">
        <?= $isEdit ? 'Enregistrer' : 'Créer le client' ?>
    </button>
</form>
