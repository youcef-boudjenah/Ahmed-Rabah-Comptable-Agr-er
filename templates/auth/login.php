<div class="login-page">
    <div class="login-brand">
        <div class="max-w-sm">
            <div class="w-10 h-10 rounded-md bg-accent flex items-center justify-center text-white font-semibold mb-8">AR</div>
            <h1 class="text-2xl font-semibold tracking-tight">Cabinet Comptable</h1>
            <p class="text-white/55 mt-3 text-sm leading-relaxed">
                Plateforme de gestion des obligations fiscales et sociales — conformité CNAS, CACOBATPH, TVA et G12.
            </p>
            <ul class="mt-8 space-y-2 text-sm text-white/40">
                <li class="flex items-center gap-2"><span class="w-1 h-1 rounded-full bg-white/30"></span> Déclarations automatisées</li>
                <li class="flex items-center gap-2"><span class="w-1 h-1 rounded-full bg-white/30"></span> GED & OCR documentaire</li>
                <li class="flex items-center gap-2"><span class="w-1 h-1 rounded-full bg-white/30"></span> Échéancier & relances</li>
            </ul>
        </div>
    </div>
    <div class="login-form-side">
        <div class="login-card">
            <h2 class="text-lg font-semibold text-slate-900">Connexion</h2>
            <p class="text-sm text-slate-500 mt-1 mb-6">Accédez à votre espace cabinet</p>
            <form method="post" action="/login" class="space-y-4">
                <?php if ($flash): ?>
                <div class="alert alert-error"><?= htmlspecialchars($flash['message']) ?></div>
                <?php endif; ?>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Adresse e-mail</label>
                    <input type="email" name="email" required value="admin@cabinet.dz" class="input">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Mot de passe</label>
                    <input type="password" name="password" required value="admin123" class="input">
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">Se connecter</button>
            </form>
        </div>
    </div>
</div>
