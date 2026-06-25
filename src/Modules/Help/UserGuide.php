<?php

declare(strict_types=1);

namespace App\Modules\Help;

final class UserGuide
{
    /** @return list<array<string, mixed>> */
    public static function sections(): array
    {
        $map = [
            'demarrage' => 3,
            'production' => 5,
            'clients' => 3,
            'ged' => 4,
            'saisie' => 3,
            'declarations' => 3,
            'relances' => 3,
            'ocr' => 3,
            'outils' => 2,
            'admin' => 3,
            'depannage' => 4,
        ];

        $legacy = self::legacySections();
        $sections = [];

        foreach ($map as $id => $stepCount) {
            $legacySection = null;
            foreach ($legacy as $s) {
                if ($s['id'] === $id) {
                    $legacySection = $s;
                    break;
                }
            }

            $title = __("guide.{$id}.title");
            if ($title === "guide.{$id}.title" && $legacySection) {
                $title = $legacySection['title'];
            }

            $steps = [];
            for ($i = 1; $i <= $stepCount; $i++) {
                $label = __("guide.{$id}.step{$i}.label");
                $text = __("guide.{$id}.step{$i}.text");
                if ($label === "guide.{$id}.step{$i}.label" && isset($legacySection['steps'][$i - 1])) {
                    $label = $legacySection['steps'][$i - 1]['label'];
                    $text = $legacySection['steps'][$i - 1]['text'];
                }
                $steps[] = ['label' => $label, 'text' => $text];
            }

            $sections[] = ['id' => $id, 'title' => $title, 'steps' => $steps];
        }

        return $sections;
    }

    /** @return list<array{q: string, a: string}> */
    public static function faq(): array
    {
        $items = [];
        for ($i = 1; $i <= 4; $i++) {
            $q = __("guide.faq{$i}.q");
            $a = __("guide.faq{$i}.a");
            if ($q === "guide.faq{$i}.q") {
                $legacy = self::legacyFaq();
                return $legacy;
            }
            $items[] = ['q' => $q, 'a' => $a];
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    private static function legacySections(): array
    {
        return [
            [
                'id' => 'demarrage',
                'title' => 'Démarrage rapide',
                'steps' => [
                    ['label' => 'Connexion', 'text' => 'Utilisez votre email et mot de passe cabinet. Compte démo : admin@cabinet.dz / admin123 (environnement local).'],
                    ['label' => 'Tableau de bord', 'text' => 'Le briefing résume la production du mois, les priorités à 30 jours, les alertes et les tâches ouvertes.'],
                    ['label' => 'Navigation', 'text' => 'Menu latéral : Production, Clients, Saisie, Conformité, GED, Outils. Sur mobile, bouton ☰ en haut.'],
                ],
            ],
            [
                'id' => 'production',
                'title' => 'Production mensuelle (cœur du cabinet)',
                'steps' => [
                    ['label' => '① Import paie', 'text' => 'Import Excel (.xlsx) ou saisie manuelle. Redirige vers Production après import.'],
                    ['label' => '② Traiter cabinet', 'text' => 'Recalcule CNAS, CACOBATPH, G50/G12 pour tous les clients ayant des données. Génère les bordereaux PDF.'],
                    ['label' => '③ Approuver brouillons', 'text' => 'Valide en masse les déclarations en statut « prêt ». PDF auto si activé dans Admin.'],
                    ['label' => '④ Export relances', 'text' => 'CSV des clients en retard ou données manquantes — pour WhatsApp / email.'],
                    ['label' => 'Options avancées', 'text' => 'OCR, pipeline personnalisé, historique des traitements (bas de page Production).'],
                ],
            ],
            [
                'id' => 'clients',
                'title' => 'Clients & fiches',
                'steps' => [
                    ['label' => 'Créer un client', 'text' => 'Clients → Nouveau. Renseignez NIF, secteur (BTP/Services), régime CNAS, contacts (tél/email pour relances).'],
                    ['label' => 'Fiche client', 'text' => 'Vue conformité, obligations urgentes, actions rapides : GED, paie, ventes, production filtrée.'],
                    ['label' => 'Notes', 'text' => 'Ajoutez des notes internes sur la fiche — visibles par l\'équipe cabinet.'],
                ],
            ],
            [
                'id' => 'ged',
                'title' => 'GED — Gestion documentaire',
                'steps' => [
                    ['label' => 'Dossiers', 'text' => '7 catégories par client : Paie, Social, Fiscal, Factures, Banque, Juridique, Divers.'],
                    ['label' => 'Upload', 'text' => 'Glissez un PDF/image dans le dossier client. Cochez OCR pour extraction automatique.'],
                    ['label' => 'CRUD', 'text' => 'Modifier métadonnées, déplacer de dossier, réassigner à un autre client, supprimer. Sélection multiple pour actions groupées.'],
                    ['label' => 'Kanban', 'text' => 'Vue par statut GED : à traiter, en cours, traité, archivé.'],
                ],
            ],
            [
                'id' => 'saisie',
                'title' => 'Saisie & import',
                'steps' => [
                    ['label' => 'Paie', 'text' => 'Masse salariale, effectif, assurés CACOBATPH. Déclenche calcul CNAS + CACOBATPH (BTP).'],
                    ['label' => 'Import Excel', 'text' => 'Colonnes : client, année, mois, masse salariale, effectif… Recalcul automatique du cabinet.'],
                    ['label' => 'Ventes / CA', 'text' => 'CA biens et services pour G50 (TVA) et G12 (IFU). Base IRG optionnelle pour acompte G50.'],
                ],
            ],
            [
                'id' => 'declarations',
                'title' => 'Déclarations & bordereaux',
                'steps' => [
                    ['label' => 'Cycle de vie', 'text' => 'Brouillon calculé → Approuvé → Déposé (quittance). Chaque étape est tracée dans les journaux.'],
                    ['label' => 'Bordereau PDF', 'text' => 'Impression CNAS, G50, CACOBATPH depuis la fiche déclaration. Archivé dans le GED si lié.'],
                    ['label' => 'Quittance', 'text' => 'Après dépôt télé-déclaration, uploadez la quittance et confirmez le dépôt.'],
                ],
            ],
            [
                'id' => 'relances',
                'title' => 'Relances & échéancier',
                'steps' => [
                    ['label' => 'Échéancier', 'text' => 'Vue liste ou calendrier des obligations à 90 jours. Filtre par statut.'],
                    ['label' => 'WhatsApp / Email', 'text' => 'Boutons relance sur fiche client et production — message pré-rempli si contact renseigné.'],
                    ['label' => 'Alertes', 'text' => 'Synchronisées automatiquement. Marquez « Lu » depuis le tableau de bord.'],
                ],
            ],
            [
                'id' => 'ocr',
                'title' => 'OCR & documents',
                'steps' => [
                    ['label' => 'File d\'attente', 'text' => 'Documents uploadés passent par OCR (Tesseract requis en local). Voir onglet Jobs dans Journaux.'],
                    ['label' => 'Revue', 'text' => 'Statut « awaiting_review » : vérifiez les champs extraits puis « Importer → déclaration ».'],
                    ['label' => 'IA', 'text' => 'Assistant FAB (bas droite) et revue IA si OPENROUTER_API_KEY configurée dans .env.'],
                ],
            ],
            [
                'id' => 'outils',
                'title' => 'Outils comptables & lois',
                'steps' => [
                    ['label' => 'Calculateurs', 'text' => 'CNAS, CACOBATPH, TVA, G50, G12/IFU, IRG salarié, amortissement linéaire.'],
                    ['label' => 'Référentiel', 'text' => 'Synthèse CGI, CNAS, SCF, échéances et liens officiels DGI / JORADP.'],
                ],
            ],
            [
                'id' => 'admin',
                'title' => 'Administration',
                'steps' => [
                    ['label' => 'Paramètres', 'text' => 'Auto OCR, PDF à l\'approbation, alertes J-7, permissions collaborateurs.'],
                    ['label' => 'Taux & échéances', 'text' => 'Tables cotisations et dates limites déclaratives — utilisées par le moteur de calcul.'],
                    ['label' => 'Utilisateurs', 'text' => 'Rôles admin / collaborateur. Seul l\'admin accède aux paramètres.'],
                ],
            ],
            [
                'id' => 'depannage',
                'title' => 'Dépannage',
                'steps' => [
                    ['label' => 'OCR échoue', 'text' => 'Installez Tesseract. Vérifiez l\'onglet Jobs (erreurs). Relancez depuis la fiche document.'],
                    ['label' => 'IA indisponible', 'text' => 'Ajoutez OPENROUTER_API_KEY dans .env. Les étapes IA du pipeline restent désactivées sans clé.'],
                    ['label' => 'Brouillons vides', 'text' => 'Vérifiez saisie paie/ventes du mois → Production → Traiter cabinet.'],
                    ['label' => 'Migrations', 'text' => 'Nouvelle base : php scripts/migrate.php puis apply-002 à 009 et seed.php.'],
                ],
            ],
        ];
    }

    /** @return list<array{q: string, a: string}> */
    private static function legacyFaq(): array
    {
        return [
            ['q' => 'Quel est le workflow mensuel type ?', 'a' => 'Import paie → Traiter cabinet → Vérifier production → Approuver → Imprimer bordereaux → Relancer manquants → Confirmer dépôts.'],
            ['q' => 'Où sont stockés les fichiers ?', 'a' => 'storage/clients/{id}/{catégorie}/ pour la GED. Ne pas supprimer ce dossier manuellement.'],
            ['q' => 'Comment exporter les relances ?', 'a' => 'Production mensuelle → bouton Export relances (CSV) quand il y a des manquants ou retards.'],
            ['q' => 'Collaborateur peut-il approuver ?', 'a' => 'Uniquement si l\'admin active « Peut approuver » dans Paramètres.'],
        ];
    }
}
