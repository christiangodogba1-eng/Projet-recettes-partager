<?php
// Fonctions.php

function validerNote($note) {
    if ($note >= 1 && $note <= 5) {
        return true;
    }
    return false;
}


function validerEmail($email) {
    // filter_var est une fonction PHP interne qui valide les emails
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return true;
    }
    return false;
}

function validerMotDePasse($password) {
    if (strlen($password) >= 8) {
        return true;
    }
    return false;
}