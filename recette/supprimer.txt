<?php
// recettes/supprimer.php
session_start();
require_once '../config/db.php';

// SÉCURITÉ : Vérification stricte du rôle d'administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Vérification de la présence de l'ID de la recette dans l'URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_recette = (int)$_GET['id'];

    try {
        // 1. Récupérer le nom de la photo pour la supprimer du dossier public/images/
        $stmt = $pdo->prepare("SELECT photo FROM recette WHERE id_recette = ?");
        $stmt->execute([$id_recette]);
        $recette = $stmt->fetch();

        if ($recette) {
            // 2. Supprimer le fichier image physique s'il existe
            if (!empty($recette['photo'])) {
                $cheminPhoto = '../public/images/' . $recette['photo'];
                if (file_exists($cheminPhoto)) {
                    unlink($cheminPhoto); // La fonction unlink() supprime le fichier
                }
            }

            // 3. Supprimer la recette de la base de données
            // (Les notes liées seront supprimées automatiquement par la contrainte ON DELETE CASCADE)
            $stmtDelete = $pdo->prepare("DELETE FROM recette WHERE id_recette = ?");
            $stmtDelete->execute([$id_recette]);
        }
    } catch (PDOException $e) {
        die("Erreur de base de données lors de la suppression : " . $e->getMessage());
    }
}

// Redirection immédiate vers l'onglet Recettes du tableau de bord
header("Location: ../admin/dashboard.php");
exit;
?>