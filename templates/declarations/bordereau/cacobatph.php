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
<title>CACOBATPH — <?= htmlspecialchars($declaration['raison_sociale']) ?></title>
<style><?= $styles ?></style>
</head>
<body>
<button class="no-print btn-print" onclick="window.print()">Imprimer / PDF</button>

<div class="form-box">
    <div class="form-header">
        <div class="org-block">
            <div class="org-logo btp">CACOBATPH</div>
            <div>
                <strong>Caisse de Cautionnement et de Gestion — BTP</strong><br>
                <span class="small">Déclaration trimestrielle de cotisations</span>
            </div>
        </div>
        <div class="form-ref">
            <div class="ref-box">Secteur BTP<br><strong><?= htmlspecialchars($periodLabel) ?></strong></div>
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td><span class="lbl">Entreprise</span><br><strong><?= htmlspecialchars($declaration['raison_sociale']) ?></strong></td>
            <td><span class="lbl">N° cotisant</span><br><strong class="mono"><?= htmlspecialchars($declaration['numero_cotisant'] ?? '—') ?></strong></td>
        </tr>
        <tr>
            <td colspan="2"><span class="lbl">Assiette masse salariale (DA)</span><br><strong class="mono big"><?= number_format((float)($cf['assiette'] ?? 0), 2, ',', ' ') ?></strong></td>
        </tr>
    </table>

    <table class="lines-table">
        <thead><tr><th>Code</th><th>Cotisation</th><th>Assiette</th><th>Taux</th><th>Montant</th></tr></thead>
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
                <td colspan="4" class="right"><strong>TOTAL</strong></td>
                <td class="num"><strong><?= number_format((float)($cf['total'] ?? 0), 2, ',', ' ') ?></strong></td>
            </tr>
        </tbody>
    </table>
    <div class="sign-block"><p class="sign-line">Cachet entreprise — Date <?= date('d/m/Y') ?></p></div>
</div>
</body>
</html>
