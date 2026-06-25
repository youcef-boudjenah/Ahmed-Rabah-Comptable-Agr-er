<?php

declare(strict_types=1);

$root = dirname(__DIR__) . '/src/Modules';

$files = [
    'Auth/AuthController.php' => [
        ["View::flash('error', 'Identifiants invalides.');", "View::flashT('error', 'flash.auth_invalid');"],
    ],
    'Clients/ClientController.php' => [
        ["View::flash('success', 'Client créé.');", "View::flashT('success', 'flash.client_created');"],
        ["View::flash('success', 'Client mis à jour.');", "View::flashT('success', 'flash.client_updated');"],
        ["View::flash('success', 'Note ajoutée.');", "View::flashT('success', 'flash.client_note_added');"],
        ["View::flash('success', 'Note mise à jour.');", "View::flashT('success', 'flash.client_note_updated');"],
        ["View::flash('success', 'Client archivé.');", "View::flashT('success', 'flash.client_archived');"],
        ["View::flash('success', 'Client réactivé.');", "View::flashT('success', 'flash.client_restored');"],
        ["View::flash('success', 'Client dupliqué.');", "View::flashT('success', 'flash.client_duplicated');"],
        ["View::flash('error', 'Sélectionnez au moins un client.');", "View::flashT('error', 'flash.client_bulk_none');"],
    ],
    'Declarations/DeclarationController.php' => [
        ["View::flash('success', 'Brouillon mis à jour.');", "View::flashT('success', 'flash.declaration_draft_updated');"],
        ["View::flash('error', 'Vous n\\'avez pas la permission d\\'approuver.');", "View::flashT('error', 'flash.declaration_approve_denied');"],
        ["View::flash('error', 'Dépôt réservé aux administrateurs.');", "View::flashT('error', 'flash.declaration_submit_admin');"],
        ["View::flash('success', 'Déclaration déposée. Quittance et bordereau archivés dans le dossier client.');", "View::flashT('success', 'flash.declaration_submitted');"],
        ["View::flash('error', 'Analyse IA indisponible (OpenRouter).');", "View::flashT('error', 'flash.declaration_ai_unavailable');"],
        ["View::flash('success', 'Analyse IA enregistrée.');", "View::flashT('success', 'flash.declaration_ai_saved');"],
        ["View::flash('error', 'Seuls les brouillons peuvent être supprimés.');", "View::flashT('error', 'flash.declaration_delete_draft_only');"],
        ["View::flash('success', 'Brouillon supprimé.');", "View::flashT('success', 'flash.declaration_draft_deleted');"],
        ["View::flash('error', 'Sélectionnez au moins une déclaration.');", "View::flashT('error', 'flash.declaration_bulk_none');"],
        ["View::flash('error', 'Action non autorisée.');", "View::flashT('error', 'flash.declaration_bulk_unauthorized');"],
    ],
    'Documents/GedController.php' => [
        ["View::flash('error', 'Erreur upload.');", "View::flashT('error', 'flash.document_upload_error');"],
        ["View::flash('success', 'Document ajouté au dossier client.');", "View::flashT('success', 'flash.document_added');"],
        ["View::flash('success', 'Document mis à jour.');", "View::flashT('success', 'flash.document_updated');"],
        ["View::flash('error', 'Document introuvable.');", "View::flashT('error', 'flash.document_not_found');"],
        ["View::flash('success', 'Document supprimé.');", "View::flashT('success', 'flash.document_deleted');"],
        ["View::flash('error', 'Sélectionnez un client.');", "View::flashT('error', 'flash.document_client_required');"],
        ["View::flash('success', 'Document réassigné au dossier client.');", "View::flashT('success', 'flash.document_reassigned');"],
        ["View::flash('error', 'Sélectionnez au moins un document.');", "View::flashT('error', 'flash.document_bulk_none');"],
        ["View::flash('error', 'Confirmez la suppression.');", "View::flashT('error', 'flash.document_bulk_confirm_delete');"],
    ],
    'Documents/DocumentController.php' => [
        ["View::flash('error', 'Erreur upload.');", "View::flashT('error', 'flash.document_upload_error');"],
        ["View::flash('success', 'Document traité.');", "View::flashT('success', 'flash.document_processed');"],
        ["View::flash('success', 'Document uploadé. Traitement OCR en cours.');", "View::flashT('success', 'flash.document_uploaded_ocr');"],
        ["View::flash('success', 'OCR terminé.');", "View::flashT('success', 'flash.document_ocr_done');"],
        ["View::flash('error', 'Sélectionnez un client.');", "View::flashT('error', 'flash.document_client_required');"],
        ["View::flash('success', 'Données importées depuis OCR. Déclarations recalculées.');", "View::flashT('success', 'flash.document_imported');"],
    ],
    'Tasks/TaskController.php' => [
        ["View::flash('success', 'Tâche ajoutée.');", "View::flashT('success', 'flash.task_added');"],
        ["View::flash('error', 'Le titre est obligatoire.');", "View::flashT('error', 'flash.task_title_required');"],
        ["View::flash('success', 'Tâche mise à jour.');", "View::flashT('success', 'flash.task_updated');"],
        ["View::flash('success', 'Tâche supprimée.');", "View::flashT('success', 'flash.task_deleted');"],
        ["View::flash('success', 'Tâche rouverte.');", "View::flashT('success', 'flash.task_reopened');"],
    ],
    'Outils/OutilsController.php' => [
        ["View::flash('error', 'Calcul non reconnu ou données invalides.');", "View::flashT('error', 'flash.outils_calc_invalid');"],
    ],
    'Entries/EntryController.php' => [
        ["View::flash('success', 'Paie enregistrée. Déclarations CNAS/CACOBATPH recalculées.');", "View::flashT('success', 'flash.entry_payroll_saved');"],
        ["View::flash('error', 'Fichier Excel (.xlsx) ou CSV requis.');", "View::flashT('error', 'flash.entry_import_file');"],
        ["View::flash('success', 'Ventes enregistrées. Déclarations G50/G12 recalculées.');", "View::flashT('success', 'flash.entry_sales_saved');"],
    ],
    'Production/ProductionController.php' => [
        ["View::flash('error', 'Droits insuffisants pour approuver.');", "View::flashT('error', 'flash.production_approve_denied');"],
    ],
    'Admin/AdminController.php' => [
        ["View::flash('success', 'Paramètres enregistrés.');", "View::flashT('success', 'flash.admin_settings_saved');"],
        ["View::flash('error', 'Nom, email et mot de passe (6+ car.) requis.');", "View::flashT('error', 'flash.admin_user_fields');"],
        ["View::flash('error', 'Email déjà utilisé.');", "View::flashT('error', 'flash.admin_email_taken');"],
        ["View::flash('success', 'Utilisateur créé.');", "View::flashT('success', 'flash.admin_user_created');"],
        ["View::flash('error', 'Vous ne pouvez pas retirer votre propre rôle admin.');", "View::flashT('error', 'flash.admin_cannot_demote');"],
        ["View::flash('success', 'Utilisateur mis à jour.');", "View::flashT('success', 'flash.admin_user_updated');"],
        ["View::flash('success', 'Taux ajouté.');", "View::flashT('success', 'flash.admin_rate_added');"],
        ["View::flash('success', 'Taux mis à jour.');", "View::flashT('success', 'flash.admin_rate_updated');"],
        ["View::flash('success', 'Échéance mise à jour.');", "View::flashT('success', 'flash.admin_deadline_updated');"],
        ["View::flash('success', 'Règle mise à jour.');", "View::flashT('success', 'flash.admin_rule_updated');"],
    ],
];

foreach ($files as $rel => $replacements) {
    $path = $root . '/' . $rel;
    $content = file_get_contents($path);
    foreach ($replacements as [$from, $to]) {
        $content = str_replace($from, $to, $content);
    }
    file_put_contents($path, $content);
    echo "Patched $rel\n";
}

echo "Done.\n";
