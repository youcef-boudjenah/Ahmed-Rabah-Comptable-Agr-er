<div class="login-page">
    <div class="login-brand">
        <div class="max-w-sm">
            <a href="/" class="brand-logo-wrap inline-block mb-8 p-3 shadow-lg">
                <img src="/assets/logo.png" alt="<?= htmlspecialchars(__('common.logo_alt')) ?>" class="brand-logo brand-logo-lg">
            </a>
            <p class="text-white/55 mt-3 text-sm leading-relaxed">
                <?= htmlspecialchars(__('auth.tagline')) ?>
            </p>
            <ul class="mt-8 space-y-2 text-sm text-white/40">
                <li class="flex items-center gap-2"><span class="w-1 h-1 rounded-full bg-accent"></span> <?= htmlspecialchars(__('auth.feature_declarations')) ?></li>
                <li class="flex items-center gap-2"><span class="w-1 h-1 rounded-full bg-accent"></span> <?= htmlspecialchars(__('auth.feature_ged')) ?></li>
                <li class="flex items-center gap-2"><span class="w-1 h-1 rounded-full bg-accent"></span> <?= htmlspecialchars(__('auth.feature_echeancier')) ?></li>
            </ul>
        </div>
    </div>
    <div class="login-form-side">
        <div class="absolute top-4 <?= \App\Core\Lang::isRtl() ? 'left-4' : 'right-4' ?>">
            <?php require ROOT_PATH . '/templates/_partials/lang_switcher.php'; ?>
        </div>
        <div class="login-card">
            <h2 class="text-lg font-semibold text-slate-900"><?= htmlspecialchars(__('auth.title')) ?></h2>
            <p class="text-sm text-slate-500 mt-1 mb-6"><?= htmlspecialchars(__('auth.subtitle')) ?></p>
            <form method="post" action="/login" class="space-y-4">
                <?php if ($flash): ?>
                <div class="alert alert-error"><?= htmlspecialchars($flash['message']) ?></div>
                <?php endif; ?>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5"><?= htmlspecialchars(__('auth.email')) ?></label>
                    <input type="email" name="email" required value="admin@cabinet.dz" class="input">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5"><?= htmlspecialchars(__('auth.password')) ?></label>
                    <input type="password" name="password" required value="admin123" class="input">
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg mt-2"><?= htmlspecialchars(__('auth.submit')) ?></button>
            </form>
        </div>
    </div>
</div>
