<?php
/**
 * ce fichier contient la déclaration de la class
 *
 * Si vous le renommez, pensez aussi à changer son appelle dans `index.php`
 */
	/**
	 * class de gestion PDO simplifiee
	 *
	 * - method query() :
	 * 		array|object = query(string $sql[, array $arg[, bool $mono_line]])
	 *
	 * lance une recherche qui attend un ou plusieurs resultats
	 * (retour en **objet** ou **array d'objet**)
	 *
	 * - method exec() :
	 * 		int = exec(string $sql[, array $arg])
	 *
	 * execute une commande et retourne le **nombre de lignes** affectees
	 *
	 * @global boolean SINGLE_RES
	 * @author Benoit <benoitelie1@gmail.com>
	 * @version v.4.0.1
	 * @link https://github.com/blag001/class_easy_pdo depot GitHub
	 */
class Bdd
{
		// valeur par defaut en cas d'instanciation sans valeur
		/** @var string l'host où se trouve la BDD */
	private $host       = 'localhost';
		/** @var string nom de la base visée par la connexion */
	private $db_name    = 'test';
		/** @var string utilisateur a passer lors de la connexion */
	private $user       = 'root';
		/** @var string mot de passe à utiliser avec le nom d'utilisateur */
	private $mdp        = '';
		/** @var boolean mode d'affichage des erreurs */
	private $production = false;

		/** @var PDO variable avec l'instance PDO */
	private $oBdd  = null;

		/** constante à utiliser en cas de resultat unique attendu */
	const SINGLE_RES = true;

	////////////////////
	// CONSTRUCTEUR  //
	////////////////////

		/**
		 * cree une instance PDO avec les valeurs en argument
		 *
		 * @api
		 *
		 * @param string $host l'host a utiliser (localhost par defaut)
		 * @param string $db_name nom de la base de donnee
		 * @param string $user utilisateur de la BDD
		 * @param string $mdp mot de passe de l'utilisateur
		 * @param string $production desactive les messages d'erreurs
		 */
	public function __construct($host=false, $db_name=false, $user=false, $mdp=false, $production=false)
	{
			// sauvegarde des variables si on en passe au constructeur
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

		/** reconnect à la BDD au chargement de la page */
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
		 * Active :
		 * - le mode de recherche en retour d'OBJET
		 * - le mode d'erreur en exception
		 * - l'encodage UTF-8 pour les transactions
		 *
		 * *Il est conseillé de faire tout votre site en UTF8*
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
		 * Gère l'affichage des erreurs via exception
		 *
		 * @param  Exception $e une exception capturee
		 * @return void    pas de retour : termine le script et affiche un message
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
		 * Passe une requete SQL avec ou sans variable (type SELECT)
		 *
		 * Retourne :
		 * - soit **un objet** si $mono_line a `Bdd::SINGLE_RES` (ou TRUE),
		 * - soit **un array d'objet** si $mono_line a FALSE ou NULL (par defaut)
		 *
		 * On lui passe en 1er parametre la requete SQL avec le(s) marqueur(s).
		 * Un marqueur est une string avec `:` devant
		 * 		ex : 'SELECT * FROM `maTable` WHERE `tab_id` = :mon_marqueur '
		 *
		 * On lui donne en 2nd parametre les arguments dans un tableau (aussi nommé array).
		 * L'array doit etre associatif `marqueur => valeur`
		 * 		ex : 'array('mon_marqueur' => $maVariable)'
		 * 		ex : 'array('marqueur1' => $var1, 'marqueur2'=> $var2)'
		 *
		 * Si vous savez que vous allez avoir un seul resultat
		 * (par ex, un `COUNT(*)`, un `getUn...()` )
		 * utilisez en 3eme parametre de query() `Bdd::SINGLE_RES` (ou TRUE)
		 * la methode vous retourneras directement un **objet**
		 *
		 * La requete prend donc ces formes :
		 * 		$sql  = 'SELECT * FROM `maTable`';
		 * 		$data = $_SESSION['bdd']->query( $sql );
		 *
		 * 		$sql  = 'SELECT * FROM `maTable` WHERE `tab_id` = :code';
		 * 		$data = $_SESSION['bdd']->query( $sql , array('code'=>$codeTable) );
		 *
		 * 		$sql  = 'SELECT COUNT(*) AS alias_nombre FROM `maTable` WHERE `tab_id` = :code';
		 * 		$data = $_SESSION['bdd']->query( $sql ,
		 * 			array('code'=>$codeTable) ,
		 * 			Bdd::SINGLE_RES );
		 *
		 * 		$sql  = 'SELECT * FROM `maTable`
		 * 			WHERE `tab_id` = :code
		 * 				AND `tab_pays` = :pays
		 * 			LIMIT :start, :nb_total';
		 * 		$data = $_SESSION['bdd']->query(
		 * 			$sql ,
		 * 			array(
		 * 				'code'=>$codeTable ,
		 * 				'pays'=> $pays,
		 * 				'start'=> intval($start),
		 * 				'nb_total'=> intval($nb_total),
		 * 				)
		 * 			);
		 *
		 * *Attention, pour les `LIMIT` il faut forcer la variable en integer, via `intval()`*
		 *
		 * On recupere les valeurs en utilisent le nom de la colonne dans la table (ou l'alias via `AS mon_alias`)
		 * - Dans le cas du `Bdd::SINGLE_RES`, on a directement un **objet** dans data :
		 * 		echo $data->tab_colonne_1;
		 * 		echo $data->mon_alias;
		 * - Sinon il faut faire une boucle dans le tableau (**array**) :
		 * 		foreach($data as $unObjet){
		 * 			echo $unObjet->tab_colonne_2;
		 * 		}
		 *
		 * @api
		 *
		 * @param  string  $sql la requete SQL a executer
		 * @param  array  $arg facultatif : le tableau d'arguments
		 * @param  boolean $mono_line facultatif : si le resultat doit etre un objet
		 * @return array|object retourne un objet ou un tableau (array) d'objets
		 */
	public function query($sql, array $arg = null, $mono_line = false)
	{
		try {
				// on regarde si on a des variables en arguments
			if(!empty($arg))
			{
					// on prepare la requete SQL
				$req = $this->oBdd->prepare($sql);
					// on lie les elements a la requete
				foreach ($arg as $key => &$value){
						// on regarde si il y a type integer a forcer
					if (is_int($value))
						$req->bindParam($key, $value, PDO::PARAM_INT);
					else
						$req->bindParam($key, $value, PDO::PARAM_STR);
				}
					// on evite les bug lie a la reference
				unset($value);

					// on l'execute avec les variables
				$req->execute();
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
		 * execute une requete SQL (type DELETE, INSERT INTO, UPDATE)
		 *
		 * On lui passe en 1er parametre la requete SQL avec le(s) marqueur(s).
		 * Un marqueur est une string avec `:` devant
		 * 		ex : 'DELETE FROM table WHERE tab_code = :mon_marqueur '
		 * 		ex : 'DELETE FROM table WHERE tab_val > :marqueur1 AND tab_type = :marqueur2 '
		 *
		 * On lui donne les arguments dans un tableau.
		 * L'array doit etre associatif `marqueur => valeur`
		 * 		ex : 'array('mon_marqueur' => $codeTable)'
		 * 		ex : 'array('marqueur1' => $clause1, 'marqueur2'=>$clause2)'
		 *
		 * La requete prend donc ces formes :
		 * 		$sql  = 'DELETE FROM table WHERE tab_connexion < 6';
		 * 		$data = $_SESSION['bdd']->exec( $sql );
		 *
		 * 		$sql  = 'DELETE FROM table WHERE tab_val = :code';
		 * 		$data = $_SESSION['bdd']->exec( $sql , array('code'=>$codeTable) );
		 *
		 * 		$sql  = 'INSERT INTO table (`tab_colonne_1`,`tab_colonne_2`)
		 * 			VALUES (:valeur1,:valeur2)';
		 * 		$data = $_SESSION['bdd']->exec(
		 * 			$sql ,
		 * 			array(
		 * 				'valeur1'=>$val1 ,
		 * 				'valeur2'=> $val2
		 * 				)
		 * 			);
		 *
		 * retourne le nombre de ligne affectee
		 *
		 * @api
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
					// on prepare la requete SQL
				$req = $this->oBdd->prepare($sql);
					// on lie les elements a la requete
				foreach ($arg as $key => &$value){
						// on regarde si il y a type integer a forcer
					if (is_int($value))
						$req->bindParam($key, $value, PDO::PARAM_INT);
					else
						$req->bindParam($key, $value, PDO::PARAM_STR);
				}
					// on evite les bug lie a la reference
				unset($value);

					// on l'execute
				if($out = $req->execute()){
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
