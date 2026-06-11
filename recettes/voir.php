<?php
// recettes/voir.php
session_start();
require_once '../config/db.php';

// Vérification de la présence de l'ID de la recette dans l'URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../index.php");
    exit;
}

$id_recette = (int)$_GET['id'];
$message = "";

// 1. Traitement du formulaire d'ajout de note (Réservé aux membres connectés)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $valeur = (int)$_POST['valeur'];
    $commentaire = trim($_POST['commentaire']);
    $id_membre = $_SESSION['user_id'];

    if ($valeur >= 1 && $valeur <= 5) {
        try {
            $stmt = $pdo->prepare("INSERT INTO note (valeur, commentaire, id_utilisateur, id_recette) VALUES (?, ?, ?, ?)");
            $stmt->execute([$valeur, $commentaire, $id_membre, $id_recette]);
            $message = '<div class="alert alert-success">Votre avis a été publié avec succès !</div>';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Erreur lors de la publication de l\'avis : ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">La note doit être comprise entre 1 et 5.</div>';
    }
}

// 2. Récupération des détails de la recette
try {
    $stmtRecette = $pdo->prepare("
        SELECT r.*, m.prenom, m.nom, c.nom_categorie 
        FROM recette r
        INNER JOIN utilisateur m ON r.id_utilisateur = m.id_utilisateur
        INNER JOIN categorie c ON r.id_categorie = c.id_categorie
        WHERE r.id_recette = ?
    ");
    $stmtRecette->execute([$id_recette]);
    $recette = $stmtRecette->fetch();

    if (!$recette) {
        die("Recette introuvable.");
    }
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// 3. Récupération des notes et commentaires de cette recette
try {
    $stmtNotes = $pdo->prepare("
        SELECT n.*, m.prenom, m.nom 
        FROM note n
        INNER JOIN utilisateur m ON n.id_utilisateur = m.id_utilisateur
        WHERE n.id_recette = ?
        ORDER BY n.date_note DESC
    ");
    $stmtNotes->execute([$id_recette]);
    $notes = $stmtNotes->fetchAll();
    
    // Calcul de la note moyenne
    $moyenne = 0;
    if (count($notes) > 0) {
        $total = 0;
        foreach ($notes as $n) {
            $total += $n['valeur'];
        }
        $moyenne = round($total / count($notes), 1);
    }
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm mb-4">
            <?php if (!empty($recette['photo'])): ?>
                <img src="../public/images/<?= htmlspecialchars($recette['photo']) ?>" class="card-img-top" alt="<?= htmlspecialchars($recette['titre']) ?>" style="max-height: 400px; object-fit: cover;">
            <?php endif; ?>
            
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-warning text-dark fs-6"><?= htmlspecialchars($recette['nom_categorie']) ?></span>
                    <?php if ($moyenne > 0): ?>
                        <span class="badge bg-success fs-6">⭐ <?= $moyenne ?> / 5 (<?= count($notes) ?> avis)</span>
                    <?php endif; ?>
                </div>
                
                <h1 class="card-title fw-bold text-primary-cuisine mb-3"><?= htmlspecialchars($recette['titre']) ?></h1>
                
                <p class="text-muted">
                    Publiée le <?= date('d/m/Y à H:i', strtotime($recette['date_publication'])) ?> par 
                    <strong><?= htmlspecialchars($recette['prenom'] . ' ' . $recette['nom']) ?></strong>
                </p>
                
                <hr>
                
                <p class="lead"><?= nl2br(htmlspecialchars($recette['description'])) ?></p>
                
                <div class="row mt-4">
                    <div class="col-md-5 mb-3">
                        <div class="p-3 bg-light rounded">
                            <h4 class="h5 fw-bold border-bottom pb-2">🛒 Ingrédients</h4>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($recette['ingredients'])) ?></p>
                        </div>
                    </div>
                    <div class="col-md-7 mb-3">
                        <div class="p-3 bg-white border rounded">
                            <h4 class="h5 fw-bold border-bottom pb-2">👨‍🍳 Préparation</h4>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($recette['preparation'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h3 class="h5 fw-bold">Avis et Commentaires</h3>
            </div>
            <div class="card-body">
                
                <?= $message; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="voir.php?id=<?= $id_recette ?>" method="POST" class="mb-4 bg-light p-3 rounded">
                        <h6 class="fw-bold mb-3">Laissez votre avis</h6>
                        <div class="mb-3">
                            <label for="valeur" class="form-label">Note sur 5 <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="valeur" name="valeur" min="1" max="5" required>
                        </div>
                        
                        <div class="mb-3 position-relative">
                            <div class="d-flex justify-content-between align-items-end mb-2">
                                <label for="commentaire" class="form-label mb-0">Commentaire</label>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-emoji-comment" title="Ajouter un émoji">
                                    😀 Émojis
                                </button>
                            </div>
                            
                            <div id="emoji-picker-container-comment" style="display: none; position: absolute; right: 0; top: 40px; z-index: 1000; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 8px;">
                                <emoji-picker class="light"></emoji-picker>
                            </div>

                            <textarea class="form-control" id="commentaire" name="commentaire" rows="3" placeholder="Qu'avez-vous pensé de cette recette ?"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary-cuisine btn-sm w-100">Publier mon avis</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <small>Vous devez être connecté pour noter cette recette.</small><br>
                        <a href="../auth/connexion.php" class="btn btn-outline-primary btn-sm mt-2">Se connecter</a>
                    </div>
                <?php endif; ?>

                <hr>

                <div class="commentaires-list" style="max-height: 500px; overflow-y: auto;">
                    <?php if (count($notes) > 0): ?>
                        <?php foreach ($notes as $note): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong><?= htmlspecialchars($note['prenom'] . ' ' . $note['nom']) ?></strong>
                                    <span class="badge bg-secondary">⭐ <?= $note['valeur'] ?> / 5</span>
                                </div>
                                <small class="text-muted d-block mb-2">Le <?= date('d/m/Y', strtotime($note['date_note'])) ?></small>
                                <?php if (!empty($note['commentaire'])): ?>
                                    <p class="mb-0 text-break fst-italic">« <?= nl2br(htmlspecialchars($note['commentaire'])) ?> »</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center fst-italic">Aucun avis pour le moment. Soyez le premier !</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@1/index.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // Vérifier si le formulaire est affiché (utilisateur connecté)
    const btnEmoji = document.getElementById('btn-emoji-comment');
    if (btnEmoji) {
        const pickerContainer = document.getElementById('emoji-picker-container-comment');
        const picker = document.querySelector('emoji-picker');
        const textarea = document.getElementById('commentaire');

        btnEmoji.addEventListener('click', (e) => {
            e.preventDefault();
            if (pickerContainer.style.display === 'none') {
                pickerContainer.style.display = 'block';
            } else {
                pickerContainer.style.display = 'none';
            }
        });

        picker.addEventListener('emoji-click', event => {
            const emoji = event.detail.unicode;
            const startPos = textarea.selectionStart;
            const endPos = textarea.selectionEnd;
            
            textarea.value = textarea.value.substring(0, startPos) 
                           + emoji 
                           + textarea.value.substring(endPos, textarea.value.length);
            
            textarea.selectionStart = textarea.selectionEnd = startPos + emoji.length;
            textarea.focus();
        });

        document.addEventListener('click', (event) => {
            if (!pickerContainer.contains(event.target) && event.target !== btnEmoji) {
                pickerContainer.style.display = 'none';
            }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>