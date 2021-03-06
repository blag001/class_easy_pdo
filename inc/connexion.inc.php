<?php
/**
 * fichier à inclure pour instancier la connexion
 *
 * Il crée une instance de la connexion à la BDD dans la SESSION
 */
	/**
	 * instanciation de la connexion BDD dans une variable de session
	 *
	 * @todo  /!\ changez ici par vos informations de connexion /!\
	 *
	 * Par défaut, si null ou false dans le "Bdd()" :
	 * 		$host       = 'localhost';
	 * 		$db_name    = 'test';
	 * 		$user       = 'root';
	 * 		$mdp        = '';
	 * 		$production = false;
	 *
	 * @param string $host l'host à utiliser (localhost par défaut)
	 * @param string $db_name nom de la base de données
	 * @param string $user utilisateur de la BDD
	 * @param string $mdp mot de passe de l'utilisateur
	 * @param bool $production (dés)active les messages d'erreurs
	 * @param string $mail mail à utiliser en cas de bug en mode production=true
	 * @param array  $freeErrorCode tableau des codes d'erreur à relâcher pour une gestion manuel
	 */
if (Bdd::needInstance())
	$_SESSION['bdd'] = new Bdd(null, null, 'root', '', false, null, null);
