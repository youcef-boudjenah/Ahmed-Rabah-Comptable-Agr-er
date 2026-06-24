<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Cabinet') ?> — Cabinet Comptable</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/app.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: { 900: '#0f172a', 800: '#1e293b', 700: '#334155', 200: '#cbd5e1' },
                        navy: { 900: '#0f172a', 800: '#1e293b', 700: '#334155', 200: '#cbd5e1' },
                        accent: { DEFAULT: '#0d9488', hover: '#0f766e', muted: '#ecfdf5' },
                    }
                }
            }
        }
    </script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="h-full font-sans antialiased text-slate-900">
<?php if ($user): ?>
<div class="app-shell flex min-h-full" x-data="{ sidebar: false }">
    <aside class="app-sidebar hidden lg:flex lg:flex-col fixed inset-y-0 z-30">
        <div class="px-5 py-5 border-b border-white/8">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-md bg-accent flex items-center justify-center text-white text-sm font-semibold tracking-tight">AR</div>
                <div class="min-w-0">
                    <p class="font-semibold text-sm text-white leading-tight truncate">Cabinet Comptable</p>
                    <p class="text-xs text-white/45 truncate mt-0.5"><?= htmlspecialchars($user['cabinet_name']) ?></p>
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-3">
            <?php
            $current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            $nav = [
                ['Principal', [
                    '/' => ['Tableau de bord', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    '/production' => ['Production mensuelle', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                    '/clients' => ['Clients', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                    '/tasks' => ['Tâches', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ]],
                ['Saisie', [
                    '/entries/payroll' => ['Saisie paie', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    '/entries/payroll/import' => ['Import Excel paie', 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12'],
                    '/entries/sales' => ['Saisie ventes', 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                ]],
                ['Conformité', [
                    '/echeancier' => ['Échéancier', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    '/declarations' => ['Déclarations', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ]],
                ['Documents', [
                    '/ged' => ['GED', 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'],
                    '/documents' => ['OCR', 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12'],
                ]],
                ['Outils', [
                    '/rapports' => ['Rapports', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    '/search' => ['Recherche', 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
                    '/assistant' => ['Assistant IA', 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
                    '/automation' => ['Traitement auto', 'M13 10V3L4 14h7v7l9-11h-7z'],
                ]],
            ];
            foreach ($nav as [$section, $links]):
                if ($section !== ''): ?>
            <div class="nav-section">
                <p class="nav-section-label"><?= $section ?></p>
            <?php endif;
                foreach ($links as $href => [$label, $icon]):
                    $active = $current === $href || ($href !== '/' && str_starts_with($current, $href));
            ?>
                <a href="<?= $href ?>" class="nav-link <?= $active ? 'active' : '' ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="<?= $icon ?>"/></svg>
                    <?= $label ?>
                </a>
            <?php endforeach;
                if ($section !== ''): ?>
            </div>
            <?php endif; endforeach;
            if (($user['role'] ?? '') === 'admin'):
                $active = str_starts_with($current, '/admin');
            ?>
            <div class="nav-section">
                <p class="nav-section-label">Administration</p>
                <a href="/admin" class="nav-link <?= $active ? 'active' : '' ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Paramètres
                </a>
            </div>
            <?php endif; ?>
        </nav>

        <div class="px-4 py-4 border-t border-white/8">
            <p class="text-xs text-white/70 truncate font-medium"><?= htmlspecialchars($user['name']) ?></p>
            <p class="text-[10px] text-white/35 uppercase tracking-wide mt-0.5"><?= htmlspecialchars($user['role'] ?? '') ?></p>
            <a href="/logout" class="text-xs text-white/50 hover:text-white mt-2 inline-block transition">Déconnexion</a>
        </div>
    </aside>

    <div class="app-main">
        <header class="app-header px-6 py-3.5 flex flex-wrap gap-4 items-center justify-between">
            <h1 class="text-base font-semibold text-slate-900"><?= htmlspecialchars($title ?? '') ?></h1>
            <form method="get" action="/search" class="flex-1 max-w-sm">
                <input type="search" name="q" placeholder="Rechercher clients, déclarations…" class="input input-search w-full">
            </form>
        </header>
        <main class="app-content">
            <?php if ($flash): ?>
            <div class="alert <?= $flash['type'] === 'error' ? 'alert-error' : 'alert-success' ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
            <?php endif; ?>
            <?php require $path; ?>
        </main>
    </div>

    <div x-data="{ open: false, msg: '', loading: false, messages: [] }" class="fixed bottom-5 right-5 z-50">
        <div x-show="open" x-cloak class="chat-panel mb-3 w-96 max-w-[calc(100vw-2.5rem)]" style="height: 26rem">
            <div class="chat-panel-header">
                <span>Assistant IA</span>
                <button type="button" @click="open = false" class="text-white/60 hover:text-white text-lg leading-none">&times;</button>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-3 text-sm">
                <template x-for="(m, i) in messages" :key="i">
                    <div :class="m.role === 'user' ? 'text-right' : 'text-left'">
                        <span :class="m.role === 'user' ? 'bg-accent text-white' : 'bg-slate-100 text-slate-800'" class="inline-block px-3 py-2 rounded-md max-w-[90%] text-[0.8125rem]" x-text="m.content"></span>
                    </div>
                </template>
                <p x-show="loading" class="text-slate-400 text-xs">En cours…</p>
            </div>
            <form @submit.prevent="
                if (!msg.trim()) return;
                messages.push({role:'user',content:msg});
                loading=true;
                const q=msg; msg='';
                fetch('/assistant/chat',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({message:q})})
                .then(r=>r.json()).then(d=>{messages.push({role:'assistant',content:d.reply||d.error||'Erreur'});loading=false;})
                .catch(()=>{loading=false;});
            " class="p-3 border-t border-slate-200 flex gap-2">
                <input x-model="msg" type="text" placeholder="Posez une question…" class="input flex-1">
                <button type="submit" class="btn btn-primary btn-sm">Envoyer</button>
            </form>
        </div>
        <button type="button" @click="open = !open" class="chat-fab" title="Assistant IA">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
        </button>
    </div>
</div>
<?php else: ?>
    <?php require $path; ?>
<?php endif; ?>
</body>
</html>
