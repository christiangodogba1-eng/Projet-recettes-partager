<?php
    include"connect.php";
    $message="";
    //clique sur bouton se connecter
    if(isset($_POST['connexion'])){ //si clic sur btn de connexion
        //récupération des données
        $email = $_POST['c_email'];
        $mdp = $_POST['c_mdp'];
        //vérification de saisie
        if(empty($email) || empty($mdp)){
            $message="veuillez entrer les données";
        }else{
            //vérification au niveau de la base de données
            $sql="SELECT * from membre where email='$email' And mot_de_passe ='$mdp'";
            $exe=$con->query($sql);
            $resultat=$exe->fetchAll();
            if($resultat){
                header('refresh: .2 url=index.php');
            }else{
                $message="Paramètres incorrects";
            }

        }
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="stylecon.css">
</head>
<body>
    <form action="index.php" method="post">
        <h2>Paramètres de connexion</h2>
        <p class="info"> <?php echo $message ?></p>
        <p>
            <input type="email" name="c_email" placeholder="Entrez votre email">
        </p>
        <p>
            <input type="mot_de_passe" name="c_mdp" placeholder="Entrez votre mot de passe">
        </p>
        <p class="bloc">
            <button type="submit" name="connexion">Se Connecter</button>
            <a href="inscription.php">Créer un Compte</a>
        </p>
    </form>
</body>
</html>