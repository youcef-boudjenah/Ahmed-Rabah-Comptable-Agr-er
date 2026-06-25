<?php

return [
    'demarrage' => [
        'title' => 'Démarrage rapide',
        'step1' => ['label' => 'Connexion', 'text' => 'Utilisez votre email et mot de passe cabinet.'],
        'step2' => ['label' => 'Tableau de bord', 'text' => 'Le briefing résume la production du mois, les priorités et les alertes.'],
        'step3' => ['label' => 'Navigation', 'text' => 'Menu latéral : Production, Clients, Saisie, Conformité, GED, Outils.'],
    ],
    'production' => [
        'title' => 'Production mensuelle',
        'step1' => ['label' => 'Import paie', 'text' => 'Import Excel ou saisie manuelle.'],
        'step2' => ['label' => 'Traiter cabinet', 'text' => 'Recalcule CNAS, CACOBATPH, G50/G12 pour tous les clients.'],
        'step3' => ['label' => 'Approuver', 'text' => 'Valide en masse les brouillons prêts.'],
        'step4' => ['label' => 'Relances', 'text' => 'Export CSV des clients en retard.'],
        'step5' => ['label' => 'Options avancées', 'text' => 'OCR, pipeline personnalisé, historique.'],
    ],
    'faq1' => ['q' => 'Quel est le workflow mensuel type ?', 'a' => 'Import paie → Traiter cabinet → Vérifier production → Approuver → Imprimer bordereaux → Relancer → Confirmer dépôts.'],
    'faq2' => ['q' => 'Où sont stockés les fichiers ?', 'a' => 'storage/clients/{id}/{catégorie}/ pour la GED.'],
    'faq3' => ['q' => 'Comment exporter les relances ?', 'a' => 'Production → Export relances (CSV).'],
    'faq4' => ['q' => 'Collaborateur peut-il approuver ?', 'a' => 'Si l\'admin active « Peut approuver » dans Paramètres.'],
];
