<?php
// admin/dashboard.php
session_start();
require_once '../config/db.php';

// SÉCURITÉ : Accès strictement réservé aux administrateurs
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// 1. Récupération des statistiques rapides
$nbRecettes = $pdo->query("SELECT COUNT(*) FROM recette")->fetchColumn();
$nbMembres = $pdo->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn();
$nbNotes = $pdo->query("SELECT COUNT(*) FROM note")->fetchColumn();

// 2. Récupération des listes pour les tableaux de gestion
$recettes = $pdo->query("
    SELECT r.id_recette, r.titre, r.date_publication, c.nom_categorie, u.prenom, u.nom 
    FROM recette r 
    INNER JOIN categorie c ON r.id_categorie = c.id_categorie 
    INNER JOIN utilisateur u ON r.id_utilisateur = u. id_utilisateur
    ORDER BY r.date_publication DESC
")->fetchAll();

$membres = $pdo->query("SELECT id_utilisateur, prenom, nom, email, role FROM utilisateur ORDER BY nom ASC")->fetchAll();

$notes = $pdo->query("
    SELECT n.id_note, n.valeur, n.commentaire, n.date_note, u.prenom, r.titre as recette_titre 
    FROM note n 
    INNER JOIN utilisateur u ON n.id_utilisateur = u.id_utilisateur 
    INNER JOIN recette r ON n.id_recette = r.id_recette 
    ORDER BY n.date_note DESC
")->fetchAll();

require_once '../includes/header.php';
?>

<div class="container-fluid mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h1 class="h2 text-danger">⚙️ Administration Plateforme</h1>
        <a href="../index.php" class="btn btn-outline-secondary btn-sm">Retour au site</a>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Recettes publiées</h5>
                    <p class="display-6 fw-bold mb-0"><?= $nbRecettes ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Membres inscrits</h5>
                    <p class="display-6 fw-bold mb-0"><?= $nbMembres ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Avis & Notes</h5>
                    <p class="display-6 fw-bold mb-0"><?= $nbNotes ?></p>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="recettes-tab" data-bs-toggle="tab" data-bs-target="#recettes" type="button" role="tab">Gérer les Recettes</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="membres-tab" data-bs-toggle="tab" data-bs-target="#membres" type="button" role="tab">Gérer les Membres</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button" role="tab">Modérer les Avis</button>
        </li>
    </ul>

    <div class="tab-content bg-white p-4 border border-top-0 rounded-bottom shadow-sm" id="adminTabsContent">
        
        <div class="tab-pane fade show active" id="recettes" role="tabpanel">
            <div class="d-flex justify-content-between mb-3">
                <h4 class="h5">Liste des recettes</h4>
                <a href="../recettes/ajouter.php" class="btn btn-sm btn-primary-cuisine">+ Nouvelle Recette</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Catégorie</th>
                            <th>Auteur</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recettes as $r): ?>
                        <tr>
                            <td><?= $r['id_recette'] ?></td>
                            <td><strong><?= htmlspecialchars($r['titre']) ?></strong></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($r['nom_categorie']) ?></span></td>
                            <td><?= htmlspecialchars($r['prenom'] . ' ' . $r['nom']) ?></td>
                            <td><?= date('d/m/Y', strtotime($r['date_publication'])) ?></td>
                            <td class="text-end">
                                <a href="../recettes/modifier.php?id=<?= $r['id_recette'] ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
                                <a href="../recettes/supprimer.php?id=<?= $r['id_recette'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer définitivement cette recette ?');">Supprimer</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="membres" role="tabpanel">
            <h4 class="h5 mb-3">Liste des utilisateurs</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nom & Prénom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($membres as $m): ?>
                        <tr>
                            <td><?= $m['id_utilisateur'] ?></td>
                            <td><strong><?= htmlspecialchars($m['nom'] . ' ' . $m['prenom']) ?></strong></td>
                            <td><?= htmlspecialchars($m['email']) ?></td>
                            <td>
                                <?php if($m['role'] === 'admin'): ?>
                                    <span class="badge bg-danger">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark">Membre</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if($m['id_utilisateur'] !== $_SESSION['user_id']): ?>
                                    <a href="supprimer_membre.php?id=<?= $m['id_utilisateur'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bannir et supprimer ce membre ?');">Supprimer</a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled>C'est vous</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="notes" role="tabpanel">
            <h4 class="h5 mb-3">Modération des commentaires</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Recette concernée</th>
                            <th>Membre</th>
                            <th>Note</th>
                            <th>Commentaire</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notes as $n): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($n['recette_titre']) ?></strong></td>
                            <td><?= htmlspecialchars($n['prenom']) ?></td>
                            <td>⭐ <?= $n['valeur'] ?>/5</td>
                            <td class="text-break" style="max-width: 300px;">
                                <small><?= htmlspecialchars($n['commentaire'] ?: '(Aucun commentaire)') ?></small>
                            </td>
                            <td><?= date('d/m/Y', strtotime($n['date_note'])) ?></td>
                            <td class="text-end">
                                <a href="supprimer_note.php?id=<?= $n['id_note'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cet avis ?');">Supprimer</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>