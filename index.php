<?php
//////////////////////////////////
// chargement et initialisation //
//////////////////////////////////

// on charge la class de gestion de la BDD
require_once ('toolSql/Bdd.class.php');

// on lance la session qui contiendra la connexion
session_start();

// on cree l'objet de connexion SQL dans $_SESSION['bdd']
require_once 'inc/connexion.inc.php';

//////////////////////////
// Fin d'initialisation //
//////////////////////////
// mettre votre code ici
