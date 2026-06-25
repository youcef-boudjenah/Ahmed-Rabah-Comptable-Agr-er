<?php

declare(strict_types=1);

namespace App\Modules\Outils;

final class LegalReferenceService
{
    /** @return list<array<string, mixed>> */
    public static function categories(): array
    {
        return [
            [
                'id' => 'fiscal',
                'title' => 'Fiscalité directe & indirecte',
                'icon' => 'FI',
                'items' => [
                    [
                        'ref' => 'Code des impôts directs et indirects',
                        'texte' => 'Cadre général IRG, IBS, TVA, retenues à la source, IFU et obligations déclaratives.',
                        'articles' => ['Art. 126 bis (IFU)', 'Art. 84-87 (TVA)', 'Art. 26-34 (IRG)'],
                        'lien' => 'https://www.mfdgi.gov.dz',
                    ],
                    [
                        'ref' => 'Loi de Finances 2025',
                        'texte' => 'Mesures annuelles : barème IRG, seuils IFU, taux et exonérations. Vérifier le texte officiel JORADP à chaque exercice.',
                        'articles' => ['Titre I — Dispositions fiscales'],
                        'lien' => 'https://www.joradp.dz',
                    ],
                    [
                        'ref' => 'G50 — Déclaration mensuelle',
                        'texte' => 'Télédéclaration mensuelle : TVA collectée (9 % / 19 %), acompte provisionnel IRG (30 % de la base déclarée), autres retenues.',
                        'articles' => ['Instruction DGI — G50'],
                        'lien' => 'https://www.mfdgi.gov.dz',
                    ],
                    [
                        'ref' => 'G12 / G12 bis — IFU annuelle',
                        'texte' => 'G12 prévisionnelle (30 juin) et G12 bis définitive (20 janvier N+1). IFU : 5 % biens, 12 % services, 0,5 % auto-entrepreneur. Seuils minimum d\'imposition.',
                        'articles' => ['Art. 126 bis CGI'],
                        'lien' => null,
                    ],
                ],
            ],
            [
                'id' => 'social',
                'title' => 'Sécurité sociale & BTP',
                'icon' => 'SO',
                'items' => [
                    [
                        'ref' => 'Loi n° 83-11 — CNAS',
                        'texte' => 'Affiliation, déclaration DAC/DAS, cotisations patronales et salariales. Régime mensuel ou trimestriel selon l\'activité.',
                        'articles' => ['R22 régime général', 'R98 FNPOS', 'R38 OPREBAT (BTP)'],
                        'lien' => 'https://www.cnas.dz',
                    ],
                    [
                        'ref' => 'Décret présidentiel 07-138 — CACOBATPH',
                        'texte' => 'Cotisations sectorielles BTP : congés payés (CP), chômage intempéries (CI). Déclaration trimestrielle sur masse salariale.',
                        'articles' => ['CP 12,21 %', 'CI 0,75 %'],
                        'lien' => null,
                    ],
                    [
                        'ref' => 'Déclaration CNAS mensuelle / trimestrielle',
                        'texte' => 'Dépôt au plus tard le 20 du mois suivant la période (règle usuelle — confirmer via échéancier cabinet).',
                        'articles' => ['Échéance J+20'],
                        'lien' => 'https://teledeclaration.cnas.dz',
                    ],
                ],
            ],
            [
                'id' => 'comptable',
                'title' => 'Comptabilité & normes',
                'icon' => 'CO',
                'items' => [
                    [
                        'ref' => 'Loi n° 07-11 — Comptabilité financière',
                        'texte' => 'Obligation de tenue de comptabilité, inventaire, documents justificatifs et conservation 10 ans.',
                        'articles' => ['Art. 12-18'],
                        'lien' => 'https://www.joradp.dz',
                    ],
                    [
                        'ref' => 'SCF — Système Comptable Financier',
                        'texte' => 'Plan comptable algérien, nomenclature des comptes, règles d\'évaluation et d\'amortissement.',
                        'articles' => ['Classe 1 à 8', 'Comptes de tiers 40/41/42/43'],
                        'lien' => null,
                    ],
                    [
                        'ref' => 'Amortissements',
                        'texte' => 'Amortissement linéaire par défaut. Durées selon nature du bien (matériel, bâtiment, véhicule). Dotations déductibles fiscalement dans les limites SCF / CGI.',
                        'articles' => ['SCF — compte 28'],
                        'lien' => null,
                    ],
                    [
                        'ref' => 'Facturation & TVA',
                        'texte' => 'Facture obligatoire avec NIF, mention TVA, timbre fiscal selon cas. Tenue registre des achats et ventes.',
                        'articles' => ['Art. 84-87 CGI'],
                        'lien' => null,
                    ],
                ],
            ],
            [
                'id' => 'penalites',
                'title' => 'Retards, pénalités & contrôles',
                'icon' => 'PE',
                'items' => [
                    [
                        'ref' => 'Majorations de retard',
                        'texte' => 'Retard de dépôt : majoration + intérés de retard (DGI / CNAS). Appliquer relances clients dès J-7.',
                        'articles' => ['CGI — majorations', 'CNAS — pénalités'],
                        'lien' => null,
                    ],
                    [
                        'ref' => 'Conservation des pièces',
                        'texte' => 'Bulletins de paie, déclarations, quittances, factures : archivage minimum 10 ans (comptable) / contrôle fiscal.',
                        'articles' => ['Loi 07-11', 'Code commerce'],
                        'lien' => null,
                    ],
                    [
                        'ref' => 'Ordre des experts-comptables',
                        'texte' => 'Exercice de la profession, secret professionnel, déontologie du cabinet agréé.',
                        'articles' => ['Loi 88-16'],
                        'lien' => null,
                    ],
                ],
            ],
        ];
    }

    /** @return list<array<string, string>> */
    public static function quickLinks(): array
    {
        return [
            ['label' => 'Ministère des Finances (DGI)', 'url' => 'https://www.mfdgi.gov.dz', 'desc' => 'Télédéclarations G50, G12'],
            ['label' => 'CNAS — Télédéclaration', 'url' => 'https://teledeclaration.cnas.dz', 'desc' => 'DAC / DAS employeur'],
            ['label' => 'JORADP — Journal officiel', 'url' => 'https://www.joradp.dz', 'desc' => 'Textes de loi publiés'],
            ['label' => 'ANADE', 'url' => 'https://www.anade.dz', 'desc' => 'Accompagnement PME / création'],
        ];
    }

    /** @return list<array<string, string>> */
    public static function fiscalCalendarNotes(): array
    {
        return [
            ['periode' => 'Mensuel', 'obligations' => 'CNAS (si régime mensuel), G50, paie & charges'],
            ['periode' => 'Trimestriel', 'obligations' => 'CNAS trimestrielle, CACOBATPH (BTP)'],
            ['periode' => 'Annuel', 'obligations' => 'G12 (30 juin), G12 bis (20 janv.), liasse / états financiers'],
            ['periode' => 'Continu', 'obligations' => 'TVA sur facturation, retenues à la source, GED & quittances'],
        ];
    }
}
