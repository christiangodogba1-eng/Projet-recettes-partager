<?php 
  try{
       $con = new PDO("mysql:host=localhost;dbname=recettes_partagees",'root','');
  }catch(PDOException $e){
    die($e->getMessage());
  }
  ?>