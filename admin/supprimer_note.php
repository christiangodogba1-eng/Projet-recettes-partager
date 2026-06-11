<?php
// admin/supprimer_note.php
session_start();
require_once '../config/db.php';

// SÉCURITÉ : Vérification stricte du rôle d'administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Vérification de la présence de l'ID de la note dans l'URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_note = (int)$_GET['id'];

    try {
        // Suppression de la note dans la base de données
        $stmt = $pdo->prepare("DELETE FROM note WHERE id_note = ?");
        $stmt->execute([$id_note]);
    } catch (PDOException $e) {
        // En cas d'erreur, on peut simplement rediriger ou afficher un message
        // Pour un script de suppression, la redirection est souvent suffisante
        die("Erreur lors de la suppression : " . $e->getMessage());
    }
}

// Redirection vers le tableau de bord après l'action
header("Location: dashboard.php");
exit;
?>