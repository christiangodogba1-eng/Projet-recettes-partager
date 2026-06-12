<?php
// tests/RecetteTest.php

use PHPUnit\Framework\TestCase;

// On inclut le fichier qui contient notre fonction à tester
require_once __DIR__ . '/../Fonctions.php';

class RecetteTest extends TestCase {
    
    // Test 1 : On vérifie qu'une note de 4 est acceptée
    public function testNoteValide() {
        $resultat = validerNote(4);
        $this->assertTrue($resultat); // PHPUnit vérifie si le résultat est VRAI
    }

    // Test 2 : On vérifie qu'une note de 6 est rejetée
    public function testNoteTropHaute() {
        $resultat = validerNote(6);
        $this->assertFalse($resultat); // PHPUnit vérifie si le résultat est FAUX
    }
        // test 3 : verifier la   validation de l'email
        public function testEmailValide() {
        $this->assertTrue(validerEmail("etudiant@gmail.com"));
    }

    public function testEmailInvalide() {
        $this->assertFalse(validerEmail("adresse-fausse.com")); // Il manque le @
    }

    #Tester la sécuriter du mot de passe
        public function testMotDePasseAssezLong() {
        $this->assertTrue(validerMotDePasse("123456789")); // 9 caractères -> OK
    }

    public function testMotDePasseTropCourt() {
        $this->assertFalse(validerMotDePasse("12345")); // 5 caractères -> Trop court !
    }
}

 