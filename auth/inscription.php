<?php
// auth/inscription.php
require_once '../config/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];
    // Récupération du rôle choisi (par défaut 'membre' si non défini)
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'membre';

    // Validation de sécurité pour s'assurer que le rôle est soit 'admin' soit 'membre'
    if (!in_array($role, ['admin', 'membre'])) {
        $role = 'membre';
    }

    if (!empty($nom) && !empty($prenom) && !empty($email) && !empty($mot_de_passe)) {
        $password_hashed = password_hash($mot_de_passe, PASSWORD_DEFAULT);

        try {
            // Insertion du membre avec le rôle sélectionné
            $stmt = $pdo->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $prenom, $email, $password_hashed, $role]);
            
            $message = '<div class="alert alert-success" role="alert">Inscription réussie en tant que ' . ($role === 'admin' ? 'Créateur (Admin)' : 'Membre standard') . ' ! <a href="connexion.php" class="alert-link">Connectez-vous ici</a></div>';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = '<div class="alert alert-danger" role="alert">Cette adresse email est déjà utilisée.</div>';
            } else {
                $message = '<div class="alert alert-danger" role="alert">Une erreur est survenue : ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    } else {
        $message = '<div class="alert alert-warning" role="alert">Veuillez remplir tous les champs obligatoires.</div>';
    }
}

// Inclusion de l'en-tête graphique
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card card-auth p-4">
            <div class="card-body">
                <h3 class="card-title text-center mb-4">Créer un compte</h3>
                
                <?= $message; ?>

                <form action="inscription.php" method="POST">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="prenom" class="form-label">Prénom</label>
                        <input type="text" class="form-control" id="prenom" name="prenom" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="mot_de_passe" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="role" class="form-label">Type de profil souhaité</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="membre" selected>Membre standard (Consulter et noter uniquement)</option>
                            <option value="admin">Créateur de recettes (Administrateur du site)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary-cuisine w-100 py-2">S'inscrire</button>
                </form>
                
                <div class="text-center mt-3">
                    <p class="mb-0 text-muted">Déjà membre ? <a href="connexion.php" class="text-decoration-none">Connectez-vous</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>