<?php
try {
	/**
	 * instanciation de la connexion BDD dans une variable de session
	 *
	 * changez les null par vos informations.
	 * Par defaut (si null ou false) :
	 * 		$host    = 'localhost';
	 * 		$db_name = 'test';
	 * 		$user    = 'root';
	 * 		$mdp     = '';
	 *
	 * @param string $host l'host a utiliser (localhost by def)
	 * @param string $db_name nom de la base de donnee
	 * @param string $user utilisateur de la BDD
	 * @param string $mdp mot de passe de l'utilisateur
	 */
	if (empty($_SESSION['bdd']))
		$_SESSION['bdd'] = new Bdd(null, null, 'root', '');

} catch (Exception $e) {
	die('Une erreur avec la base de donnee c\'est produite');
}
