<?php
$tab = $tab ?? 'calculateurs';
$calcResult = $calcResult ?? null;
?>
<div class="page-intro">
    <p class="eyebrow"><?= htmlspecialchars(__('outils.eyebrow')) ?></p>
    <h2><?= htmlspecialchars(__('outils.title')) ?></h2>
    <p><?= htmlspecialchars(__('outils.subtitle')) ?></p>
</div>

<div class="flex flex-wrap gap-2 mb-6 border-b border-slate-200 pb-4">
    <a href="/outils?tab=calculateurs" class="btn btn-sm <?= $tab === 'calculateurs' ? 'btn-primary' : 'btn-secondary' ?>"><?= htmlspecialchars(__('outils.tab_calculators')) ?></a>
    <a href="/outils?tab=referentiel" class="btn btn-sm <?= $tab === 'referentiel' ? 'btn-primary' : 'btn-secondary' ?>"><?= htmlspecialchars(__('outils.tab_referentiel')) ?></a>
    <a href="/outils?tab=taux" class="btn btn-sm <?= $tab === 'taux' ? 'btn-primary' : 'btn-secondary' ?>"><?= htmlspecialchars(__('outils.tab_rates')) ?></a>
</div>

<?php if ($tab === 'calculateurs'): ?>

<?php if ($calcResult): $cr = $calcResult; ?>
<div class="card-elevated mb-6 border-accent-200">
    <div class="card-header">
        <h3><?= htmlspecialchars(__('common.result_type', ['type' => $cr['type']])) ?></h3>
        <a href="/outils?tab=calculateurs" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('outils.new_calc')) ?></a>
    </div>
    <div class="card-body">
        <?php $d = $cr['data']; ?>
        <?php if ($cr['type'] === 'tva'): ?>
        <div class="metric-grid mb-4">
            <div class="metric-box"><p class="num"><?= number_format($d['ht'], 2, ',', ' ') ?></p><p class="lbl"><?= htmlspecialchars(__('outils.ht')) ?></p></div>
            <div class="metric-box"><p class="num text-accent-700"><?= number_format($d['tva'], 2, ',', ' ') ?></p><p class="lbl"><?= htmlspecialchars(__('outils.tva')) ?> <?= $d['taux'] ?> %</p></div>
            <div class="metric-box"><p class="num"><?= number_format($d['ttc'], 2, ',', ' ') ?></p><p class="lbl"><?= htmlspecialchars(__('outils.ttc')) ?></p></div>
        </div>
        <?php elseif ($cr['type'] === 'irg'): ?>
        <div class="metric-grid mb-4">
            <div class="metric-box"><p class="num"><?= number_format($d['salaire_brut'], 2, ',', ' ') ?></p><p class="lbl"><?= htmlspecialchars(__('outils.gross')) ?></p></div>
            <div class="metric-box"><p class="num"><?= number_format($d['cotisation_salarie_9'], 2, ',', ' ') ?></p><p class="lbl"><?= htmlspecialchars(__('outils.cnas_employee')) ?></p></div>
            <div class="metric-box"><p class="num text-amber-700"><?= number_format($d['irg'], 2, ',', ' ') ?></p><p class="lbl"><?= htmlspecialchars(__('outils.irg_estimated')) ?></p></div>
            <div class="metric-box"><p class="num text-accent-700"><?= number_format($d['net_estime'], 2, ',', ' ') ?></p><p class="lbl"><?= htmlspecialchars(__('outils.net_estimated')) ?></p></div>
        </div>
        <?php if (!empty($d['detail'])): ?>
        <table class="data-table text-sm"><thead><tr><th><?= htmlspecialchars(__('outils.bracket')) ?></th><th><?= htmlspecialchars(__('common.rate')) ?></th><th>IRG</th></tr></thead><tbody>
            <?php foreach ($d['detail'] as $row): ?>
            <tr><td><?= $row['tranche'] ?></td><td><?= $row['taux'] ?> %</td><td class="font-mono"><?= number_format($row['montant'], 2, ',', ' ') ?></td></tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php endif; ?>
        <p class="text-xs text-amber-700 mt-3"><?= htmlspecialchars($d['disclaimer']) ?></p>
        <?php elseif ($cr['type'] === 'amortissement'): ?>
        <p class="text-sm mb-3"><?= htmlspecialchars(__('outils.annual_depreciation')) ?> <strong class="font-mono"><?= number_format($d['dotation_annuelle'], 2, ',', ' ') ?> <?= htmlspecialchars(__('common.currency')) ?></strong> <?= htmlspecialchars(__('outils.over_years', ['n' => $d['duree']])) ?></p>
        <table class="data-table text-sm"><thead><tr><th><?= htmlspecialchars(__('outils.year_col')) ?></th><th><?= htmlspecialchars(__('outils.depreciation')) ?></th><th><?= htmlspecialchars(__('outils.cumulative')) ?></th></tr></thead><tbody>
            <?php foreach ($d['plan'] as $row): ?>
            <tr><td><?= $row['annee'] ?></td><td class="font-mono"><?= number_format($row['dotation'], 2, ',', ' ') ?></td><td class="font-mono"><?= number_format($row['cumul'], 2, ',', ' ') ?></td></tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php elseif (!empty($d['lines'])): ?>
        <table class="data-table text-sm mb-4">
            <thead><tr><th><?= htmlspecialchars(__('common.code')) ?></th><th><?= htmlspecialchars(__('common.label')) ?></th><th><?= htmlspecialchars(__('outils.base')) ?></th><th><?= htmlspecialchars(__('common.rate')) ?></th><th><?= htmlspecialchars(__('common.amount')) ?></th></tr></thead>
            <tbody>
                <?php foreach ($d['lines'] as $line): ?>
                <tr>
                    <td class="font-mono text-xs"><?= htmlspecialchars($line['code'] ?? '') ?></td>
                    <td><?= htmlspecialchars($line['label']) ?></td>
                    <td class="font-mono"><?= number_format($line['assiette'] ?? $line['base'] ?? $line['ca'] ?? 0, 2, ',', ' ') ?></td>
                    <td><?= isset($line['taux']) ? $line['taux'] . ' %' : __('common.unassigned') ?></td>
                    <td class="font-mono font-medium"><?= number_format($line['montant'], 2, ',', ' ') ?> <?= htmlspecialchars(__('common.currency')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="text-lg font-semibold"><?= htmlspecialchars(__('common.total')) ?> : <span class="font-mono text-accent-700"><?= number_format($d['total'], 2, ',', ' ') ?> <?= htmlspecialchars(__('common.currency')) ?></span></p>
        <?php if (!empty($d['minimum_imposition'])): ?>
        <p class="text-xs text-slate-500 mt-2"><?= htmlspecialchars(__('outils.ifu_threshold')) ?> <?= number_format($d['minimum_imposition'], 0, ',', ' ') ?> <?= htmlspecialchars(__('common.currency')) ?></p>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <form method="post" action="/outils/calculate" class="card">
        <div class="card-header"><h3><?= htmlspecialchars(__('outils.cnas_title')) ?></h3></div>
        <div class="card-body space-y-3">
            <input type="hidden" name="calc" value="cnas">
            <div>
                <label class="text-sm font-medium text-slate-700"><?= htmlspecialchars(__('outils.payroll_mass')) ?></label>
                <input type="text" name="assiette" required placeholder="173781.80" class="input w-full mt-1 font-mono">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-sm"><?= htmlspecialchars(__('common.sector')) ?></label>
                    <select name="secteur" class="input w-full mt-1">
                        <option value="BTP">BTP</option>
                        <option value="SERVICES">Services</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm"><?= htmlspecialchars(__('common.regime')) ?></label>
                    <select name="cnas_type" class="input w-full mt-1">
                        <option value="CNAS_MENSUELLE"><?= htmlspecialchars(__('outils.monthly')) ?></option>
                        <option value="CNAS_TRIMESTRIELLE"><?= htmlspecialchars(__('outils.quarterly')) ?></option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars(__('outils.calculate_cnas')) ?></button>
        </div>
    </form>

    <form method="post" action="/outils/calculate" class="card">
        <div class="card-header"><h3><?= htmlspecialchars(__('outils.cacobatph_title')) ?></h3></div>
        <div class="card-body space-y-3">
            <input type="hidden" name="calc" value="cacobatph">
            <div>
                <label class="text-sm font-medium"><?= htmlspecialchars(__('outils.quarterly_mass')) ?></label>
                <input type="text" name="assiette" required class="input w-full mt-1 font-mono">
            </div>
            <div>
                <label class="text-sm"><?= htmlspecialchars(__('outils.assurees_count')) ?></label>
                <input type="number" name="assurees" min="0" class="input w-full mt-1">
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars(__('outils.calculate_cacobatph')) ?></button>
        </div>
    </form>

    <form method="post" action="/outils/calculate" class="card">
        <div class="card-header"><h3><?= htmlspecialchars(__('outils.tva_title')) ?></h3></div>
        <div class="card-body space-y-3">
            <input type="hidden" name="calc" value="tva">
            <div>
                <label class="text-sm font-medium"><?= htmlspecialchars(__('outils.amount')) ?></label>
                <input type="text" name="montant" required class="input w-full mt-1 font-mono">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-sm"><?= htmlspecialchars(__('common.rate')) ?></label>
                    <select name="taux" class="input w-full mt-1">
                        <option value="19"><?= htmlspecialchars(__('outils.rate_19')) ?></option>
                        <option value="9"><?= htmlspecialchars(__('outils.rate_9')) ?></option>
                    </select>
                </div>
                <div>
                    <label class="text-sm"><?= htmlspecialchars(__('outils.mode')) ?></label>
                    <select name="mode" class="input w-full mt-1">
                        <option value="ht"><?= htmlspecialchars(__('outils.ht_to_ttc')) ?></option>
                        <option value="ttc"><?= htmlspecialchars(__('outils.ttc_to_ht')) ?></option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars(__('outils.calculate_tva')) ?></button>
        </div>
    </form>

    <form method="post" action="/outils/calculate" class="card">
        <div class="card-header"><h3><?= htmlspecialchars(__('outils.g50_title')) ?></h3></div>
        <div class="card-body space-y-3">
            <input type="hidden" name="calc" value="g50">
            <div>
                <label class="text-sm"><?= htmlspecialchars(__('outils.ca_biens_ht')) ?></label>
                <input type="text" name="ca_biens" placeholder="0" class="input w-full mt-1 font-mono">
            </div>
            <div>
                <label class="text-sm"><?= htmlspecialchars(__('outils.ca_services_ht')) ?></label>
                <input type="text" name="ca_services" placeholder="0" class="input w-full mt-1 font-mono">
            </div>
            <div>
                <label class="text-sm"><?= htmlspecialchars(__('outils.irg_base_optional')) ?></label>
                <input type="text" name="irg_base" placeholder="0" class="input w-full mt-1 font-mono">
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars(__('outils.estimate_g50')) ?></button>
        </div>
    </form>

    <form method="post" action="/outils/calculate" class="card">
        <div class="card-header"><h3><?= htmlspecialchars(__('outils.g12_title')) ?></h3></div>
        <div class="card-body space-y-3">
            <input type="hidden" name="calc" value="g12">
            <div>
                <label class="text-sm"><?= htmlspecialchars(__('outils.ca_biens_5')) ?></label>
                <input type="text" name="ca_biens" placeholder="0" class="input w-full mt-1 font-mono">
            </div>
            <div>
                <label class="text-sm"><?= htmlspecialchars(__('outils.ca_services_12')) ?></label>
                <input type="text" name="ca_services" placeholder="0" class="input w-full mt-1 font-mono">
            </div>
            <div>
                <label class="text-sm"><?= htmlspecialchars(__('outils.ca_auto_05')) ?></label>
                <input type="text" name="ca_auto" placeholder="0" class="input w-full mt-1 font-mono">
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars(__('outils.calculate_ifu')) ?></button>
        </div>
    </form>

    <form method="post" action="/outils/calculate" class="card">
        <div class="card-header"><h3><?= htmlspecialchars(__('outils.irg_title')) ?></h3></div>
        <div class="card-body space-y-3">
            <input type="hidden" name="calc" value="irg">
            <div>
                <label class="text-sm font-medium"><?= htmlspecialchars(__('outils.gross_monthly')) ?></label>
                <input type="text" name="salaire_brut" required class="input w-full mt-1 font-mono">
            </div>
            <p class="text-xs text-slate-500"><?= htmlspecialchars(__('outils.irg_disclaimer')) ?></p>
            <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars(__('outils.estimate_irg')) ?></button>
        </div>
    </form>

    <form method="post" action="/outils/calculate" class="card lg:col-span-2">
        <div class="card-header"><h3><?= htmlspecialchars(__('outils.depreciation_title')) ?></h3></div>
        <div class="card-body grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
            <input type="hidden" name="calc" value="amortissement">
            <div>
                <label class="text-sm"><?= htmlspecialchars(__('outils.original_value')) ?></label>
                <input type="text" name="valeur" required class="input w-full mt-1 font-mono">
            </div>
            <div>
                <label class="text-sm"><?= htmlspecialchars(__('outils.duration_years')) ?></label>
                <input type="number" name="duree" value="5" min="1" max="40" class="input w-full mt-1">
            </div>
            <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('outils.depreciation_plan')) ?></button>
        </div>
    </form>
</div>

<?php elseif ($tab === 'referentiel'): ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
    <div class="card lg:col-span-2">
        <div class="card-header"><h3><?= htmlspecialchars(__('outils.calendar')) ?></h3></div>
        <div class="card-body pt-0">
            <table class="data-table text-sm">
                <thead><tr><th><?= htmlspecialchars(__('outils.periodicity')) ?></th><th><?= htmlspecialchars(__('outils.main_obligations')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($calendarNotes as $note): ?>
                    <tr>
                        <td class="font-medium whitespace-nowrap"><?= htmlspecialchars($note['periode']) ?></td>
                        <td class="text-slate-600"><?= htmlspecialchars($note['obligations']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3><?= htmlspecialchars(__('outils.official_links')) ?></h3></div>
        <div class="card-body pt-0 space-y-3">
            <?php foreach ($quickLinks as $link): ?>
            <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener" class="block p-3 rounded-lg border border-slate-200 hover:border-accent-500 hover:bg-accent-50/40 transition">
                <p class="text-sm font-medium text-accent-800"><?= htmlspecialchars($link['label']) ?></p>
                <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($link['desc']) ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php foreach ($legal as $cat): ?>
<div class="card mb-5">
    <div class="card-header">
        <h3><span class="inline-flex w-8 h-8 rounded bg-slate-100 text-xs font-bold items-center justify-center mr-2"><?= $cat['icon'] ?></span><?= htmlspecialchars($cat['title']) ?></h3>
    </div>
    <div class="card-body pt-0 divide-y divide-slate-100">
        <?php foreach ($cat['items'] as $item): ?>
        <div class="py-4">
            <p class="font-semibold text-slate-900"><?= htmlspecialchars($item['ref']) ?></p>
            <p class="text-sm text-slate-600 mt-1"><?= htmlspecialchars($item['texte']) ?></p>
            <?php if (!empty($item['articles'])): ?>
            <p class="text-xs text-slate-400 mt-2"><?= htmlspecialchars(implode(' · ', $item['articles'])) ?></p>
            <?php endif; ?>
            <?php if (!empty($item['lien'])): ?>
            <a href="<?= htmlspecialchars($item['lien']) ?>" target="_blank" rel="noopener" class="text-xs text-accent-700 hover:underline mt-2 inline-block"><?= htmlspecialchars(__('outils.official_text')) ?></a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<p class="text-xs text-slate-400 text-center py-4"><?= htmlspecialchars(__('outils.referentiel_disclaimer')) ?></p>

<?php else: /* taux */ ?>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
    <div class="card overflow-hidden">
        <div class="card-header">
            <h3><?= htmlspecialchars(__('outils.contribution_rates')) ?></h3>
            <?php if (($user['role'] ?? '') === 'admin'): ?>
            <a href="/admin?tab=rates" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('outils.modify')) ?></a>
            <?php endif; ?>
        </div>
        <div class="table-scroll">
            <table class="data-table text-sm">
                <thead><tr><th><?= htmlspecialchars(__('common.code')) ?></th><th><?= htmlspecialchars(__('common.label')) ?></th><th><?= htmlspecialchars(__('common.rate')) ?></th><th><?= htmlspecialchars(__('common.sector')) ?></th><th><?= htmlspecialchars(__('common.type')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($rates as $r): ?>
                    <tr>
                        <td class="font-mono text-xs"><?= htmlspecialchars($r['code']) ?></td>
                        <td><?= htmlspecialchars($r['label']) ?></td>
                        <td class="font-mono font-medium"><?= $r['taux'] ?> %</td>
                        <td class="text-xs"><?= htmlspecialchars($r['secteur'] ?? __('common.unassigned')) ?></td>
                        <td class="text-xs"><?= htmlspecialchars($r['declaration_type']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card overflow-hidden">
        <div class="card-header">
            <h3><?= htmlspecialchars(__('outils.decl_deadlines')) ?></h3>
            <?php if (($user['role'] ?? '') === 'admin'): ?>
            <a href="/admin?tab=deadlines" class="btn btn-ghost btn-sm"><?= htmlspecialchars(__('outils.modify')) ?></a>
            <?php endif; ?>
        </div>
        <div class="table-scroll">
            <table class="data-table text-sm">
                <thead><tr><th><?= htmlspecialchars(__('common.type')) ?></th><th><?= htmlspecialchars(__('common.frequency')) ?></th><th><?= htmlspecialchars(__('common.due_date')) ?></th><th><?= htmlspecialchars(__('common.label')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($deadlines as $d): ?>
                    <tr>
                        <td class="font-mono text-xs"><?= htmlspecialchars($d['declaration_type']) ?></td>
                        <td><?= htmlspecialchars($d['frequency']) ?></td>
                        <td class="font-mono"><?= htmlspecialchars(__('outils.due_on', ['day' => $d['due_day']])) ?><?= $d['due_month'] ? ' ' . htmlspecialchars(__('outils.due_month', ['month' => $d['due_month']])) : '' ?></td>
                        <td class="text-slate-600"><?= htmlspecialchars($d['label_fr']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-body border-t border-slate-100">
            <a href="/echeancier" class="btn btn-secondary btn-sm"><?= htmlspecialchars(__('outils.see_echeancier')) ?></a>
        </div>
    </div>
</div>

<div class="card mt-5">
    <div class="card-header"><h3><?= htmlspecialchars(__('outils.tva_ifu_reminder')) ?></h3></div>
    <div class="card-body grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div class="p-3 rounded-lg bg-slate-50"><p class="font-semibold"><?= htmlspecialchars(__('outils.tva_goods')) ?></p><p class="text-2xl font-bold text-accent-700 mt-1">9 %</p></div>
        <div class="p-3 rounded-lg bg-slate-50"><p class="font-semibold"><?= htmlspecialchars(__('outils.tva_services')) ?></p><p class="text-2xl font-bold text-accent-700 mt-1">19 %</p></div>
        <div class="p-3 rounded-lg bg-slate-50"><p class="font-semibold"><?= htmlspecialchars(__('outils.ifu_goods')) ?></p><p class="text-2xl font-bold mt-1">5 %</p></div>
        <div class="p-3 rounded-lg bg-slate-50"><p class="font-semibold"><?= htmlspecialchars(__('outils.ifu_services')) ?></p><p class="text-2xl font-bold mt-1">12 %</p></div>
    </div>
</div>

<?php endif; ?>
