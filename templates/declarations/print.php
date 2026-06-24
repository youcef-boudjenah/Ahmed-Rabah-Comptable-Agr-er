<?php
$cf = $declaration['computed_fields'];
$typeLabel = \App\Modules\Automation\DeadlineService::typeLabel($declaration['type']);
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau — <?= htmlspecialchars($declaration['raison_sociale']) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Times New Roman', serif; padding: 20mm; font-size: 11pt; color: #000; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .header h1 { font-size: 14pt; text-transform: uppercase; }
        .header p { font-size: 10pt; margin-top: 4px; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
        th { background: #f0f0f0; font-size: 9pt; text-transform: uppercase; }
        td.amount { text-align: right; font-family: monospace; }
        .total-row { font-weight: bold; font-size: 12pt; }
        .total-row td { border-top: 2px solid #000; }
        .footer { margin-top: 30px; display: flex; justify-content: space-between; font-size: 10pt; }
        .cert { margin-top: 40px; border-top: 1px solid #000; padding-top: 15px; }
        @media print { body { padding: 10mm; } .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()" style="margin-bottom:20px;padding:10px 20px;cursor:pointer">Imprimer</button>

    <div class="header">
        <?php if (str_contains($declaration['type'], 'CNAS')): ?>
        <h1>Sécurité Sociale — Déclaration de cotisations</h1>
        <?php elseif ($declaration['type'] === 'CACOBATPH'): ?>
        <h1>CACOBATPH — Déclaration de cotisations</h1>
        <?php elseif ($declaration['type'] === 'G50'): ?>
        <h1>Série G N°50 — Déclaration tenant lieu de bordereau</h1>
        <?php else: ?>
        <h1><?= htmlspecialchars($typeLabel) ?></h1>
        <?php endif; ?>
        <p>Période: <?= htmlspecialchars($periodLabel) ?></p>
    </div>

    <div class="meta">
        <div>
            <strong>Raison sociale:</strong> <?= htmlspecialchars($declaration['raison_sociale']) ?><br>
            <strong>N° cotisant:</strong> <?= htmlspecialchars($declaration['numero_cotisant'] ?? $cf['source']['numero_cotisant'] ?? '—') ?>
        </div>
        <div style="text-align:right">
            <strong>Statut:</strong> <?= $declaration['status'] ?><br>
            <strong>Date:</strong> <?= date('d/m/Y') ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Nature des cotisations / impôts</th>
                <th>Assiette (DA)</th>
                <th>Taux</th>
                <th>Montant (DA)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cf['lines'] ?? [] as $line): ?>
            <tr>
                <td><?= htmlspecialchars($line['code'] ?? '') ?></td>
                <td><?= htmlspecialchars($line['label'] ?? '') ?></td>
                <td class="amount"><?= isset($line['assiette']) ? number_format($line['assiette'], 2, ',', ' ') : (isset($line['ca']) ? number_format($line['ca'], 2, ',', ' ') : '—') ?></td>
                <td class="amount"><?= isset($line['taux']) ? $line['taux'] . '%' : '—' ?></td>
                <td class="amount"><?= number_format($line['montant'] ?? 0, 2, ',', ' ') ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="4">TOTAL DES COTISATIONS DUES</td>
                <td class="amount"><?= number_format($cf['total'] ?? 0, 2, ',', ' ') ?></td>
            </tr>
        </tbody>
    </table>

    <?php if (isset($cf['assiette'])): ?>
    <p style="margin-top:10px"><strong>Assiette globale:</strong> <?= number_format($cf['assiette'], 2, ',', ' ') ?> DA</p>
    <?php endif; ?>

    <div class="cert">
        <p>Certifiée exacte à: le <?= date('d/m/Y') ?></p>
        <p style="margin-top:30px">Cachet et signature du cotisant: _________________________</p>
    </div>

    <div class="footer">
        <span>Document généré par Cabinet Comptable</span>
        <span>Réf. #<?= $declaration['id'] ?></span>
    </div>
</body>
</html>
