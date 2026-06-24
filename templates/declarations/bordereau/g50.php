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
<title>G50 — <?= htmlspecialchars($declaration['raison_sociale']) ?></title>
<style><?= $styles ?></style>
</head>
<body>
<button class="no-print btn-print" onclick="window.print()">Imprimer / PDF</button>

<div class="form-box">
    <div class="form-header">
        <div class="org-block">
            <div class="org-logo g50">G50</div>
            <div>
                <strong>Direction Générale des Impôts</strong><br>
                <span class="small">Série G N° 50 — Déclaration tenant lieu de bordereau de versement</span>
            </div>
        </div>
        <div class="form-ref">
            <div class="ref-box">Impôts & taxes<br><strong>Versement périodique</strong></div>
            <div class="small"><?= htmlspecialchars($periodLabel) ?></div>
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td><span class="lbl">Contribuable</span><br><strong><?= htmlspecialchars($declaration['raison_sociale']) ?></strong></td>
            <td><span class="lbl">NIF</span><br><strong class="mono"><?= htmlspecialchars($declaration['nif'] ?? '—') ?></strong></td>
        </tr>
        <tr>
            <td colspan="2"><span class="lbl">Activité / Wilaya</span><br><?= htmlspecialchars(($declaration['activite'] ?? '') . ' — ' . ($declaration['wilaya'] ?? '')) ?></td>
        </tr>
    </table>

    <table class="lines-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Impôt / taxe</th>
                <th>Base imposable (DA)</th>
                <th>Taux</th>
                <th>Montant (DA)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cf['lines'] ?? [] as $line): ?>
            <tr>
                <td class="center"><?= htmlspecialchars($line['code'] ?? '') ?></td>
                <td><?= htmlspecialchars($line['label'] ?? '') ?></td>
                <td class="num"><?= isset($line['ca']) ? number_format((float)$line['ca'], 2, ',', ' ') : (isset($line['assiette']) ? number_format((float)$line['assiette'], 2, ',', ' ') : '—') ?></td>
                <td class="center"><?= isset($line['taux']) ? $line['taux'] . ' %' : '—' ?></td>
                <td class="num"><?= number_format((float)($line['montant'] ?? 0), 2, ',', ' ') ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="4" class="right"><strong>TOTAL À VERSER</strong></td>
                <td class="num"><strong><?= number_format((float)($cf['total'] ?? 0), 2, ',', ' ') ?></strong></td>
            </tr>
        </tbody>
    </table>

    <div class="sign-block">
        <p>Certifiée exacte — Date : <?= date('d/m/Y') ?></p>
        <p class="sign-line">Signature et cachet du contribuable</p>
    </div>
    <div class="footer-meta">
        <span>Réf. #<?= (int)$declaration['id'] ?> — Cabinet Comptable</span>
    </div>
</div>
</body>
</html>
