<?php $currentLocale = \App\Core\Lang::locale(); ?>
<div class="lang-switcher flex items-center gap-0.5 rounded-md border border-slate-200 bg-white p-0.5 text-xs font-medium">
    <a href="/locale/fr"
       class="px-2 py-1 rounded <?= $currentLocale === 'fr' ? 'bg-accent text-brand-900' : 'text-slate-500 hover:text-slate-800' ?>"
       title="<?= htmlspecialchars(__('common.lang_fr')) ?>">FR</a>
    <a href="/locale/ar"
       class="px-2 py-1 rounded <?= $currentLocale === 'ar' ? 'bg-accent text-brand-900' : 'text-slate-500 hover:text-slate-800' ?>"
       title="<?= htmlspecialchars(__('common.lang_ar')) ?>">ع</a>
</div>
