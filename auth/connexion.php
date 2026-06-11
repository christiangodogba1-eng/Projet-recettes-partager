<?php
// auth/connexion.php
session_start();
require_once '../config/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];

    if (!empty($email) && !empty($mot_de_passe)) {
        $stmt = $pdo->prepare("SELECT * from utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id_utilisateur'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_role'] = $user['role'];

            header("Location: ../index.php");
            exit;
        } else {
            $message = '<div class="alert alert-danger" role="alert">Identifiants incorrects.</div>';
        }
    } else {
        $message = '<div class="alert alert-warning" role="alert">Veuillez remplir tous les champs.</div>';
    }
}

require_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card card-auth p-4">
            <div class="card-body">
                <h3 class="card-title text-center mb-4">Connexion</h3>
                
                <?= $message; ?>

                <form action="connexion.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-4">
                        <label for="mot_de_passe" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
                    </div>
                    <button type="submit" class="btn btn-primary-cuisine w-100 py-2">Se connecter</button>
                </form>
                
                <div class="text-center mt-3">
                    <p class="mb-0 text-muted">Pas encore inscrit ? <a href="inscription.php" class="text-decoration-none">Créez un compte</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
</body>
</html>