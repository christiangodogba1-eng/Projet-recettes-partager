<?php
// recettes/ajouter.php
session_start();
require_once '../config/db.php'; // Assurez-vous d'avoir passé le charset à utf8mb4 dans db.php

// SÉCURITÉ : Vérification stricte du rôle d'administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Redirection immédiate si l'utilisateur n'est pas admin
    header("Location: ../index.php");
    exit;
}

$message = "";

// Récupération des catégories pour alimenter dynamiquement le menu déroulant
try {
    $stmtCat = $pdo->query("SELECT * FROM categorie ORDER BY nom_categorie ASC");
    $categories = $stmtCat->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors du chargement des catégories : " . $e->getMessage());
}

// Traitement du formulaire lors de la soumission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $ingredients = trim($_POST['ingredients']);
    $preparation = trim($_POST['preparation']);
    $id_categorie = $_POST['id_categorie'];
    $id_membre = $_SESSION['user_id']; // L'admin connecté est l'auteur
    $nom_photo = null;

    // Gestion de l'upload de la photo
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo']['tmp_name'];
        $fileName = $_FILES['photo']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Extensions autorisées
        $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'webp', 'gif','avif' ];
        
        if (in_array($fileExtension, $extensionsAutorisees)) {
            // Génération d'un nom unique pour éviter les doublons
            $nom_photo = uniqid('recette_', true) . '.' . $fileExtension;
            $dossierDestination = '../public/images/';
            
            // Création du dossier s'il n'existe pas encore
            if (!is_dir($dossierDestination)) {
                mkdir($dossierDestination, 0755, true);
            }
            
            move_uploaded_file($fileTmpPath, $dossierDestination . $nom_photo);
        } else {
            $message = '<div class="alert alert-danger">Format d\'image non valide (JPG, JPEG, PNG, WEBP uniquement).</div>';
        }
    }

    // Insertion si les champs obligatoires sont remplis
    if (!empty($titre) && !empty($description) && !empty($ingredients) && !empty($preparation) && !empty($id_categorie)) {
        if (empty($message)) { // S'il n'y a pas eu d'erreur d'extension d'image
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO recette (titre, description, ingredients, preparation, photo, id_utilisateur, id_categorie) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$titre, $description, $ingredients, $preparation, $nom_photo, $id_membre, $id_categorie]);
                
                $message = '<div class="alert alert-success">La recette a été ajoutée avec succès ! <a href="../index.php" class="alert-link">Retour à l\'accueil</a></div>';
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger">Erreur lors de l\'enregistrement : ' . htmlspecialchars($e->getMessage()) . '</div>';
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
                <h2 class="card-title mb-4 text-center">Ajouter une nouvelle recette</h2>
                
                <?= $message; ?>

                <form action="ajouter.php" method="POST" enctype="multipart/form-data">
                    
                    <div class="mb-3">
                        <label for="titre" class="form-label">Titre de la recette <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="titre" name="titre" required placeholder="Ex: Tarte au Citron Meringuée">
                    </div>

                    <div class="mb-3">
                        <label for="id_categorie" class="form-label">Catégorie <span class="text-danger">*</span></label>
                        <select class="form-select" id="id_categorie" name="id_categorie" required>
                            <option value="" disabled selected>Choisir une catégorie...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id_categorie'] ?>"><?= htmlspecialchars($cat['nom_categorie']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description courte <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="2" required placeholder="Une brève présentation qui donnera envie de lire la recette..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="ingredients" class="form-label">Ingrédients <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="ingredients" name="ingredients" rows="4" required placeholder="Ex:&#10;- 200g de farine&#10;- 3 œufs&#10;- 100g de sucre"></textarea>
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

                        <textarea class="form-control" id="preparation" name="preparation" rows="5" required placeholder="Ex:&#10;1. Préchauffer le four... ⏲️&#10;2. Mélanger les ingrédients... 🥣"></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="photo" class="form-label">Illustration (Photo)</label>
                        <input type="file" class="form-control" id="photo" name="photo">
                        <div class="form-text">Formats acceptés : JPG, PNG, WEBP, GIF.</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="../index.php" class="btn btn-outline-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary-cuisine px-4">Publier la recette</button>
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

    // Afficher / Masquer le sélecteur d'émojis au clic sur le bouton
    btnEmoji.addEventListener('click', (e) => {
        e.preventDefault(); // Évite de soumettre le formulaire accidentellement
        if (pickerContainer.style.display === 'none') {
            pickerContainer.style.display = 'block';
        } else {
            pickerContainer.style.display = 'none';
        }
    });

    // Insérer l'émoji dans le texte
    picker.addEventListener('emoji-click', event => {
        const emoji = event.detail.unicode;
        
        // Permet d'insérer l'émoji exactement là où est le curseur dans le texte
        const startPos = textarea.selectionStart;
        const endPos = textarea.selectionEnd;
        
        textarea.value = textarea.value.substring(0, startPos) 
                       + emoji 
                       + textarea.value.substring(endPos, textarea.value.length);
        
        // Replace le curseur juste après l'émoji inséré
        textarea.selectionStart = textarea.selectionEnd = startPos + emoji.length;
        
        // Redonne le focus à la zone de texte
        textarea.focus();
    });

    // Masquer le sélecteur si on clique ailleurs sur la page
    document.addEventListener('click', (event) => {
        if (!pickerContainer.contains(event.target) && event.target !== btnEmoji) {
            pickerContainer.style.display = 'none';
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>