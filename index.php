<?php
// index.php
session_start();
require_once 'config/db.php';

$query = "
    SELECT r.id_recette, r.titre, r.description, r.date_publication, r.photo,
           U.prenom, U.nom, c.nom_categorie
    FROM recette r
    INNER JOIN utilisateur U ON r.id_utilisateur = U.id_utilisateur
    INNER JOIN categorie c ON r.id_categorie = c.id_categorie
    ORDER BY r.date_publication DESC
";

try {
    $stmt = $pdo->query($query);
    $recettes = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Recettes Partagées</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-5">
    <div class="container">
        <a class="navbar-brand" href="index.php">🍳 Recettes Partagées</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">Accueil</a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="recettes/ajouter.php">Ajouter une recette</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger fw-bold" href="admin/dashboard.php">Administration</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item ms-3">
                        <span class="navbar-text me-2">Bonjour, <strong><?= htmlspecialchars($_SESSION['user_prenom']) ?></strong></span>
                        <a class="btn btn-outline-secondary btn-sm" href="auth/deconnexion.php">Déconnexion</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/connexion.php">Connexion</a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-primary-cuisine btn-sm" href="auth/inscription.php">S'inscrire</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="p-5 mb-5 bg-cuisine rounded-3 text-center shadow-sm">
        <h1 class="display-5 fw-bold">Bienvenue sur Recettes Partagées</h1>
        <p class="col-md-8 mx-auto fs-5">
            Découvrez les meilleures recettes sélectionnées par notre équipe. Créez un compte pour partager votre avis et noter vos plats préférés !
        </p>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="auth/inscription.php" class="btn btn-primary-cuisine btn-lg mt-3">S'inscrire pour noter</a>
        <?php endif; ?>
    </div>

    <h2 class="mb-4">Dernières recettes</h2>
    
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if (count($recettes) > 0): ?>
            <?php foreach ($recettes as $recette): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($recette['photo'])): ?>
                            <img src="public/images/<?= htmlspecialchars($recette['photo']) ?>" class="card-img-top" alt="<?= htmlspecialchars($recette['titre']) ?>" style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center text-white" style="height: 200px;">
                                <span>Aucune photo</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-warning text-dark"><?= htmlspecialchars($recette['nom_categorie']) ?></span>
                                <small class="text-muted"><?= date('d/m/Y', strtotime($recette['date_publication'])) ?></small>
                            </div>
                            <h5 class="card-title"><?= htmlspecialchars($recette['titre']) ?></h5>
                            <p class="card-text text-muted">
                                <?= htmlspecialchars(mb_strimwidth($recette['description'], 0, 100, "...")) ?>
                            </p>
                            <div class="mt-auto pt-3 border-top">
                                <small class="text-muted d-block mb-2">Publiée par : <?= htmlspecialchars($recette['prenom'] . ' ' . $recette['nom']) ?></small>
                                <a href="recettes/voir.php?id=<?= $recette['id_recette'] ?>" class="btn btn-outline-primary btn-sm w-100">Voir et Noter</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted fs-5">Aucune recette n'a été publiée pour le moment.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer class="bg-white text-center text-muted py-4 mt-5 border-top">
    <div class="container">
        <p class="mb-0">&copy; <?= date('Y') ?> Recettes Partagées - Projet Atelier Informatique.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>