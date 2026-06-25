<?php
/** @var array $declaration */
/** @var string $periodLabel */
$cf = is_array($declaration['computed_fields'] ?? null)
    ? $declaration['computed_fields']
    : json_decode($declaration['computed_fields'] ?? '{}', true);
$styles = require ROOT_PATH . '/templates/declarations/bordereau/_styles.php';
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Bordereau CNAS — <?= htmlspecialchars($declaration['raison_sociale']) ?></title>
<style><?= $styles ?></style>
</head>
<body>
<button class="no-print btn-print" onclick="window.print()">Imprimer / PDF</button>

<div class="form-box">
    <div class="form-header">
        <div class="org-block">
            <div class="org-logo">CNAS</div>
            <div>
                <strong>Caisse Nationale des Assurances Sociales</strong><br>
                <span class="small">République Algérienne Démocratique et Populaire</span>
            </div>
        </div>
        <div class="form-ref">
            <div class="ref-box">Déclaration de cotisations<br><strong>Employeur</strong></div>
            <div class="small">Période : <?= htmlspecialchars($periodLabel) ?></div>
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td width="50%"><span class="lbl">Raison sociale / Nom</span><br><strong><?= htmlspecialchars($declaration['raison_sociale']) ?></strong></td>
            <td><span class="lbl">N° d'immatriculation (cotisant)</span><br><strong class="mono"><?= htmlspecialchars($declaration['numero_cotisant'] ?? $cf['source']['numero_cotisant'] ?? '—') ?></strong></td>
        </tr>
        <tr>
            <td><span class="lbl">Wilaya / Adresse</span><br><?= htmlspecialchars(trim(($declaration['wilaya'] ?? '') . ' ' . ($declaration['adresse'] ?? '')) ?: '—') ?></td>
            <td><span class="lbl">Effectif / Assurés</span><br><?= (int)($cf['effectif'] ?? $cf['source']['effectif'] ?? 0) ?> / <?= (int)($cf['nombre_assurees'] ?? $cf['source']['nombre_assurees'] ?? 0) ?></td>
        </tr>
        <tr>
            <td colspan="2"><span class="lbl">Assiette — Masse salariale soumise à cotisation (DA)</span><br><strong class="mono big"><?= number_format((float)($cf['assiette'] ?? $cf['source']['masse_salariale'] ?? 0), 2, ',', ' ') ?></strong></td>
        </tr>
    </table>

    <table class="lines-table">
        <thead>
            <tr>
                <th style="width:8%">Code</th>
                <th>Nature de la cotisation</th>
                <th style="width:14%">Assiette (DA)</th>
                <th style="width:8%">Taux</th>
                <th style="width:14%">Montant (DA)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cf['lines'] ?? [] as $line): ?>
            <tr>
                <td class="center"><?= htmlspecialchars($line['code'] ?? '') ?></td>
                <td><?= htmlspecialchars($line['label'] ?? '') ?></td>
                <td class="num"><?= isset($line['assiette']) ? number_format((float)$line['assiette'], 2, ',', ' ') : '—' ?></td>
                <td class="center"><?= isset($line['taux']) ? $line['taux'] . ' %' : '—' ?></td>
                <td class="num"><?= number_format((float)($line['montant'] ?? 0), 2, ',', ' ') ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="4" class="right"><strong>TOTAL COTISATIONS DUES</strong></td>
                <td class="num"><strong><?= number_format((float)($cf['total'] ?? 0), 2, ',', ' ') ?></strong></td>
            </tr>
        </tbody>
    </table>

    <div class="sign-block">
        <p>Fait à _________________, le <?= date('d/m/Y') ?></p>
        <p class="sign-line">Cachet et signature du cotisant</p>
    </div>

    <div class="footer-meta">
        <span>Document généré par le cabinet — Réf. décl. #<?= (int)$declaration['id'] ?></span>
        <span>Statut : <?= htmlspecialchars($declaration['status']) ?></span>
    </div>
</div>
</body>
</html>
