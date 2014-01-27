<?php
	/**
	 * class de gestion PDO simplifiee
	 *
	 * @method mixed query(STRING $sql[, ARRAY $arg[, BOOL $mono_line]])
	 *         			lance une recherche qui attend un ou plusieurs resultats
	 *         			(retour en OBJET ou ARRAY d'OBJET)
	 *
	 * @method int exec(STRING $sql[, ARRAY $arg])
	 *         			execute une commande et retourne le nombre de lignes affectees
	 *
	 * @global boolean SINGLE_RES
	 * @author Benoit <benoitelie1@gmail.com>
	 * @version v.3.0.2
	 * @link https://github.com/blag001/class_easy_pdo depot GitHub
	 */
class Bdd
{
		// valeur par defaut en cas d'instanciation sans valeur
	private $host       = 'localhost';
	private $db_name    = 'test';
	private $user       = 'root';
	private $mdp        = '';
	private $production = false;

		/** @var PDO variable avec l'instance PDO */
	private $oBdd  = null;

		/** @global constante en cas de resultat unique */
	const SINGLE_RES = true;

	////////////////////
	// CONSTRUCTEUR  //
	////////////////////

		/**
		 * cree une instance PDO avec les valeurs en argument
		 *
		 * @param string $host l'host a utiliser (localhost par defaut)
		 * @param string $db_name nom de la base de donnee
		 * @param string $user utilisateur de la BDD
		 * @param string $mdp mot de passe de l'utilisateur
		 * @param string $mdp mot de passe de l'utilisateur
		 * @param string $production desactive les messages d'erreurs
		 */
	public function __construct($host=false, $db_name=false, $user=false, $mdp=false, $production=false)
	{
			// save des variable si on en passe au constructeur
		if(!empty($host))
			$this->host = $host;

		if(!empty($db_name))
			$this->db_name = $db_name;

		if(!empty($user))
			$this->user = $user;

		if(!empty($mdp))
			$this->mdp = $mdp;

		if(!empty($production))
			$this->production = $production;

			// on lance la connexion
		$this->_connexion();
	}

		/** variables a sauver a la fin du chargement de page */
	public function __sleep()
	{
		return array('host', 'db_name', 'user', 'mdp', 'production');
	}

		/** on reconnect au chargement de la page */
	public function __wakeup()
	{
		$this->_connexion();
	}

	//////////////
	// PRIVATE //
	//////////////

		/**
		 * cree une instance PDO
		 *
		 * passe le mode de recherche en retour d'OBJET
		 * passe le mode d'erreur en exception
		 * utilise l'UTF-8 pour les transactions :
		 * Il est conseille de faire tout votre site en UTF8
		 *
		 * @return void
		 */
	private function _connexion()
	{
		try{
				// on appelle le constructeur POD
			$this->oBdd = new PDO(
				'mysql:host='.$this->host.';dbname='.$this->db_name,
				$this->user,
				$this->mdp);
				// on active le mode retour d'OBJET
			$this->oBdd->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
				// on active le mode erreur par exception
			$this->oBdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				// on force l'utilisation d'UTF-8
			$this->oBdd->exec("SET CHARACTER SET utf8");
		}catch (Exception $e){
			$this->_showError($e);
		}
	}

		/**
		 * Gere l'affichage des erreur via exception
		 *
		 * @param  Exception $e une exception capturee
		 * @return void    pas de retour : termine le script
		 */
	private function _showError($e)
	{
				// si on est en production, on ne met pas de detail
			if($this->production)
				echo 'ERREUR : Merci de contacter le Webmaster.';
				// sinon les info de debugage
			else{
				echo '<h1 style="color:#a33">ERROR SQL WITH PDO</h1>'."\n";
				echo '<strong>'.$e->getMessage().'</strong><br />'."\n";
				echo '<h2 style="color:#a33">In this :</h2>'."\n";
				echo '<pre style="color:#fff; background-color:#333">'.$e->getTraceAsString().'</pre>';
			}
			die(); // en cas d'erreur, on stop le script
	}

	/////////////
	// PUBLIC //
	/////////////

		/**
		 * Passe une requete SQL avec ou sans variable (SELECT)
		 *
		 * Retourne soit **un OBJET** si $mono_line a TRUE ou "Bdd::SINGLE_RES",
		 * soit **un ARRAY d'OBJET** si $mono_line a FALSE ou NULL
		 *
		 * On lui passe la requete SQL avec le(s) marqueur(s).
		 * 	un marqueur est une string avec ':' devant
		 * 		ex : 'SELECT * FROM table WHERE tab_code = :mon_marqueur '
		 * On lui donne les arguments dans un tableau (aussi nomme array).
		 * 	l'array doit etre associatif marqueur => valeur
		 * 		ex : 'array('mon_marqueur' => $codeTable)'
		 * 		ex : 'array('marqueur1' => $var1, 'marqueur2'=> $var2)'
		 *
		 * Si vous savez que vous allez avoir un seul resultat
		 * (par ex, un COUNT(*), un getUn...() )
		 * utilisez en 3eme parametre de query() "Bdd::SINGLE_RES" (ou TRUE)
		 * la methode vous retourneras directement un OBJET
		 *
		 * La requete prend donc ces formes :
		 * 		$data = $_SESSION['bdd']->query( 'SELECT * FROM table' );
		 * 		$data = $_SESSION['bdd']->query( 'SELECT * FROM table WHERE tab_code = :code' , array('code'=>$codeTable) );
		 * 		$data = $_SESSION['bdd']->query( 'SELECT COUNT(*) AS alias_nombre FROM table WHERE tab_code = :code' ,
		 * 			array('code'=>$codeTable) ,
		 * 			Bdd::SINGLE_RES );
		 * 		$data = $_SESSION['bdd']->query( 'SELECT * FROM table WHERE tab_code = :code AND tab_pays = :pays' ,
		 * 			array('code'=>$codeTable , 'pays'=> $pays) );
		 *
		 * On recupere les valeurs en utilisent le nom de la colonne dans la table (ou l'alias via "AS mon_alias")
		 * dans le cas du SINGLE_RES, on a directement un OBJET dans data :
		 * 		echo $data->tab_colonne_1;
		 * 		echo $data->mon_alias;
		 * Sinon il faut faire une boucle dans le tableau (array) :
		 * 		foreach($data as $unObjet){
		 * 			echo $unObjet->tab_colonne_2;
		 * 		}
		 *
		 * @param  string  $sql la requete SQL a executer
		 * @param  array  $arg facultatif : le tableau d'arguments
		 * @param  boolean $mono_line facultatif : si le resultat doit etre un objet
		 * @return mixed retourne un objet ou un tableau (array) d'objets
		 */
	public function query($sql, array $arg = null, $mono_line = false)
	{
		try {
				// on regarde si on a des variables en arguments
			if(!empty($arg))
			{
					// on prepare la requete SQL
				$req = $this->oBdd->prepare($sql);
					// on l'execute avec les variables
				$req->execute($arg);
			}
			else
			{
					// on fait une query simple
				$req = $this->oBdd->query($sql);
			}

				// si on demande une mono-ligne, un simple fetch
			if($mono_line)
				$data = $req->fetch();
			else // sinon on cherche tous les OBJET dans un ARRAY
				$data = $req->fetchAll();

				// on ferme la requete en cours
			$req->closeCursor();

			return $data;
		}
			// gestion des erreurs
		catch (PDOException $e) {
			$this->_showError($e);
		}

	}

		/**
		 * execute une requete SQL (DELETE, INSERT INTO, UPDATE)
		 *
		 * On lui passe la requete SQL avec les marqueurs.
		 * 	un marqueur est une string avec ':' devant
		 * 		ex : 'DELETE FROM table WHERE tab_code = :mon_marqueur '
		 * 		ex : 'DELETE FROM table WHERE tab_val > :marqueur1 AND tab_type = :marqueur2 '
		 * on lui donne les arguments dans un tableau.
		 * 	l'array doit etre associatif marqueur => valeur
		 * 		ex : 'array('mon_marqueur' => $codeTable)'
		 * 		ex2 : 'array('marqueur1' => $clause1, 'marqueur2'=>$clause2)'
		 *
		 * La requete prend donc ces formes :
		 * 		$data = $_SESSION['bdd']->exec( 'DELETE FROM table WHERE tab_connexion < 6' );
		 * 		$data = $_SESSION['bdd']->exec( 'DELETE FROM table WHERE tab_val = :code' , array('code'=>$codeTable) );
		 * 		$data = $_SESSION['bdd']->exec( 'INSERT INTO table (`tab_colonne_1`,`tab_colonne_2`) VALUES (:valeur1,:valeur2)' ,
		 * 			array('valeur1'=>$val1 , 'valeur2'=> $val2) );
		 *
		 * retourne le nombre de ligne affectee
		 *
		 * @param  string $sql la requete SQL a executer
		 * @param  array $arg l'array avec les parametres
		 * @return int le nombre de ligne affectee
		 */
	public function exec($sql, array $arg = null)
	{
		try {
				// on regarde si on a des variable en arguments
			if(!empty($arg))
			{
					// on prepare la requete
				$req = $this->oBdd->prepare($sql);

					// on l'execute avec les arguments
				if($out = $req->execute($arg)){
						// si pas de probleme, on compte le nombre de ligne affectee
					$out = $req->rowCount();
				}

					// on ferme la requete en cours
				$req->closeCursor();
			}
			else{
					// si pas de variable, on fait une requete simple
				$out = $this->oBdd->exec($sql);
			}

			return $out;
		}
			// gestion des erreurs
		catch (PDOException $e) {
			$this->_showError($e);
		}
	}
}
