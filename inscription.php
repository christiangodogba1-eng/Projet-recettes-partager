<?php
session_start();
require_once("includes/connexion.php");

$message = "";

if(isset($_POST['inscrire']))
{
    $nom = htmlspecialchars(trim($_POST['nom']));
    $prenom = htmlspecialchars(trim($_POST['prenom']));
    $email = htmlspecialchars(trim($_POST['email']));
    $mot_de_passe = $_POST['mot_de_passe'];
    $confirmation = $_POST['confirmation'];

    // Vérification des champs
    if(empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe) || empty($confirmation))
    {
        $message = "Tous les champs sont obligatoires.";
    }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        $message = "Adresse email invalide.";
    }
    elseif($mot_de_passe !== $confirmation)
    {
        $message = "Les mots de passe ne correspondent pas.";
    }
    else
    {
        // Vérifier si l'email existe déjà
        $verif = $pdo->prepare("SELECT * FROM membre WHERE email = ?");
        $verif->execute([$email]);

        if($verif->rowCount() > 0)
        {
            $message = "Cet email est déjà utilisé.";
        }
        else
        {
            // Cryptage du mot de passe
            $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

            // Insertion du membre
            $requete = $pdo->prepare(
                "INSERT INTO membre(nom, prenom, email, mot_de_passe, role)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $resultat = $requete->execute([
                $nom,
                $prenom,
                $email,
                $mot_de_passe_hash,
                'membre'
            ]);

            if($resultat)
            {
                $message = "Inscription réussie !";
            }
            else
            {
                $message = "Erreur lors de l'inscription.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - Recettes Partagées</title>

    <style>
        body{
            font-family: Arial, sans-serif;
            background-color:#f4f4f4;
        }

        .container{
            width:400px;
            margin:50px auto;
            background:white;
            padding:20px;
            border-radius:10px;
            box-shadow:0 0 10px rgba(0,0,0,0.2);
        }

        h2{
            text-align:center;
        }

        input{
            width:100%;
            padding:10px;
            margin-top:5px;
            margin-bottom:15px;
        }

        button{
            width:100%;
            padding:10px;
            cursor:pointer;
        }

        .message{
            text-align:center;
            margin-bottom:15px;
            font-weight:bold;
            color:green;
        }

        .erreur{
            color:red;
        }
    </style>
</head>
<body>

<div class="container">

    <h2>Inscription</h2>

    <?php if(!empty($message)) { ?>
        <p class="message"><?php echo $message; ?></p>
    <?php } ?>

    <form method="POST">

        <label>Nom</label>
        <input type="text" name="nom" required>

        <label>Prénom</label>
        <input type="text" name="prenom" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Mot de passe</label>
        <input type="password" name="mot_de_passe" required>

        <label>Confirmer le mot de passe</label>
        <input type="password" name="confirmation" required>

        <button type="submit" name="inscrire">
            S'inscrire
        </button>

    </form>

    <p style="text-align:center;">
        Déjà inscrit ?
        <a href="connexion.php">Se connecter</a>
    </p>

</div>

</body>
</html>