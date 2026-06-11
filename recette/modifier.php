<?php
// recettes/modifier.php
session_start();
require_once '../config/db.php';

// SÉCURITÉ : Vérification stricte du rôle d'administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Vérification de la présence de l'ID de la recette à modifier
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../admin/dashboard.php");
    exit;
}

$id_recette = (int)$_GET['id'];
$message = "";

// 1. Récupération des données actuelles de la recette
try {
    $stmt = $pdo->prepare("SELECT * FROM recette WHERE id_recette = ?");
    $stmt->execute([$id_recette]);
    $recette = $stmt->fetch();

    if (!$recette) {
        die("Recette introuvable.");
    }
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// 2. Récupération des catégories pour le menu déroulant
try {
    $stmtCat = $pdo->query("SELECT * FROM categorie ORDER BY nom_categorie ASC");
    $categories = $stmtCat->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors du chargement des catégories : " . $e->getMessage());
}

// 3. Traitement de la soumission du formulaire (Mise à jour)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $ingredients = trim($_POST['ingredients']);
    $preparation = trim($_POST['preparation']);
    $id_categorie = $_POST['id_categorie'];
    
    // Par défaut, on garde l'ancienne photo
    $nom_photo = $recette['photo'];

    // Gestion de l'upload si une nouvelle photo est soumise
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo']['tmp_name'];
        $fileName = $_FILES['photo']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'webp', 'gif','avif'];
        
        if (in_array($fileExtension, $extensionsAutorisees)) {
            // Génération d'un nouveau nom unique
            $nom_photo = uniqid('recette_', true) . '.' . $fileExtension;
            $dossierDestination = '../public/images/';
            
            if (move_uploaded_file($fileTmpPath, $dossierDestination . $nom_photo)) {
                // Optionnel : Supprimer l'ancienne photo du serveur pour ne pas encombrer le stockage
                if (!empty($recette['photo']) && file_exists($dossierDestination . $recette['photo'])) {
                    unlink($dossierDestination . $recette['photo']);
                }
            }
        } else {
            $message = '<div class="alert alert-danger">Format d\'image non valide (JPG, JPEG, PNG, WEBP uniquement).</div>';
        }
    }

    // Mise à jour de la base de données si tout est valide
    if (!empty($titre) && !empty($description) && !empty($ingredients) && !empty($preparation) && !empty($id_categorie)) {
        if (empty($message)) {
            try {
                $stmtUpdate = $pdo->prepare("
                    UPDATE recette 
                    SET titre = ?, description = ?, ingredients = ?, preparation = ?, photo = ?, id_categorie = ? 
                    WHERE id_recette = ?
                ");
                $stmtUpdate->execute([$titre, $description, $ingredients, $preparation, $nom_photo, $id_categorie, $id_recette]);
                
                // Rafraîchir les données locales de la recette pour répercuter les changements dans le formulaire
                $recette['titre'] = $titre;
                $recette['description'] = $description;
                $recette['ingredients'] = $ingredients;
                $recette['preparation'] = $preparation;
                $recette['photo'] = $nom_photo;
                $recette['id_categorie'] = $id_categorie;

                $message = '<div class="alert alert-success">La recette a été modifiée avec succès ! <a href="../admin/dashboard.php" class="alert-link">Retour au tableau de bord</a></div>';
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger">Erreur lors de la mise à jour : ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    } else {
        $message = '<div class="alert alert-warning">Veuillez remplir tous les champs obligatoires.</div>';
    }
}

require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm p-4 mb-5 bg-white rounded">
            <div class="card-body">
                <h2 class="card-title mb-4 text-center">Modifier la recette</h2>
                
                <?= $message; ?>

                <form action="modifier.php?id=<?= $id_recette ?>" method="POST" enctype="multipart/form-data">
                    
                    <div class="mb-3">
                        <label for="titre" class="form-label">Titre de la recette <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="titre" name="titre" value="<?= htmlspecialchars($recette['titre']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="id_categorie" class="form-label">Catégorie <span class="text-danger">*</span></label>
                        <select class="form-select" id="id_categorie" name="id_categorie" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id_categorie'] ?>" <?= $cat['id_categorie'] == $recette['id_categorie'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nom_categorie']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description courte <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="2" required><?= htmlspecialchars($recette['description']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="ingredients" class="form-label">Ingrédients <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="ingredients" name="ingredients" rows="4" required><?= htmlspecialchars($recette['ingredients']) ?></textarea>
                    </div>

                    <div class="mb-3 position-relative">
                        <div class="d-flex justify-content-between align-items-end mb-2">
                            <label for="preparation" class="form-label mb-0">Étapes de préparation <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-emoji" title="Ajouter un émoji">
                                😀 Émojis
                            </button>
                        </div>
                        
                        <div id="emoji-picker-container" style="display: none; position: absolute; right: 0; top: 40px; z-index: 1000; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 8px;">
                            <emoji-picker class="light"></emoji-picker>
                        </div>

                        <textarea class="form-control" id="preparation" name="preparation" rows="5" required><?= htmlspecialchars($recette['preparation']) ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="photo" class="form-label">Illustration (Photo)</label>
                        <?php if (!empty($recette['photo'])): ?>
                            <div class="mb-2">
                                <small class="text-muted d-block mb-1">Image actuelle :</small>
                                <img src="../public/images/<?= htmlspecialchars($recette['photo']) ?>" alt="Aperçu" class="img-thumbnail" style="max-height: 120px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="photo" name="photo">
                        <div class="form-text">Laissez vide si vous ne souhaitez pas modifier l'image actuelle.</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="../admin/dashboard.php" class="btn btn-outline-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary-cuisine px-4">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@1/index.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const btnEmoji = document.getElementById('btn-emoji');
    const pickerContainer = document.getElementById('emoji-picker-container');
    const picker = document.querySelector('emoji-picker');
    const textarea = document.getElementById('preparation');

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
});
</script>

<?php require_once '../includes/footer.php'; ?>