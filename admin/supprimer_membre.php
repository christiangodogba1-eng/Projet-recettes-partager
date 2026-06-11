<?php
// admin/supprimer_membre.php
session_start();
require_once '../config/db.php';

// SÉCURITÉ : Vérification stricte du rôle d'administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Vérification de la présence de l'ID du membre à supprimer dans l'URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_membre_a_supprimer = (int)$_GET['id'];

    // SÉCURITÉ : Empêcher l'administrateur de se supprimer lui-même
    if ($id_membre_a_supprimer === $_SESSION['user_id']) {
        // Redirection immédiate si tentative d'auto-suppression
        header("Location: dashboard.php");
        exit;
    }

    try {
        // 1. Récupérer les photos des recettes créées par ce membre pour nettoyer le serveur
        $stmtPhotos = $pdo->prepare("SELECT photo FROM recette WHERE id_membre = ? AND photo IS NOT NULL");
        $stmtPhotos->execute([$id_membre_a_supprimer]);
        $recettes = $stmtPhotos->fetchAll();

        // 2. Supprimer les fichiers physiques (images) du serveur
        foreach ($recettes as $recette) {
            if (!empty($recette['photo'])) {
                $cheminPhoto = '../public/images/' . $recette['photo'];
                if (file_exists($cheminPhoto)) {
                    unlink($cheminPhoto); // Suppression du fichier
                }
            }
        }

        // 3. Supprimer le membre de la base de données
        // (La contrainte ON DELETE CASCADE supprimera automatiquement ses recettes et ses notes de la BDD)
        $stmtDelete = $pdo->prepare("DELETE FROM membre WHERE id_membre = ?");
        $stmtDelete->execute([$id_membre_a_supprimer]);

    } catch (PDOException $e) {
        die("Erreur de base de données lors de la suppression : " . $e->getMessage());
    }
}

// Redirection immédiate vers l'onglet Membres du tableau de bord
header("Location: dashboard.php");
exit;
?>