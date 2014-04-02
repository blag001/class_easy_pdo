<?php
/**
 * ce fichier contient la déclaration de la class
 *
 * Si vous le renommez, pensez aussi à changer son appelle dans `index.php`
 */
	/**
	 * class de gestion PDO simplifiée
	 *
	 * - method query() :
	 * ```php
	 * 		array|object = query(string $sql[, array $arg[, bool $mono_line]])
	 * ```
	 *
	 * lance une recherche qui attend un ou plusieurs resultats
	 * (retour en **objet** ou **array d'objet**)
	 *
	 * - method exec() :
	 * ```php
	 * 		integer = exec(string $sql[, array $arg])
	 * ```
	 *
	 * *Méthodes avancées (facultatives) pour optimiser certaines requêtes*
	 *
	 * - method prepare() :
	 * ```php
	 * 		bool = prepare(string $sql)
	 * ```
	 *
	 * prepare une requête pour être exécuté plusieurs fois
	 *
	 * - method execute() :
	 * ```php
	 * 		array|object|integer = execute([array $arg[, int $retour]])
	 * ```
	 *
	 * (re)execute une requete avec les paramètres demandés
	 *
	 * @global boolean SINGLE_RES
	 * @global int NO_RES
	 * @author Benoit <benoitelie1@gmail.com>
	 * @version v.5.0.0
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
		/** @var object variable qui contient une requête préparée */
	private $oReq  = null;
		/** @var string url lors de l'instanciation de la class */
	private $callSource;

		/** constante à utiliser en cas de resultat unique attendu */
	const SINGLE_RES = true;
		/** constante à utiliser avec prepare/execute si il n'y a pas de résultat attendu */
	const NO_RES = -1;

	////////////////////
	// CONSTRUCTEUR  //
	////////////////////

		/**
		 * cree une instance PDO avec les valeurs en argument
		 *
		 * @api
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

			// on sauve la page d'instanciation
		$this->callSource = $_SERVER['PHP_SELF'];

			// on lance la connexion
		$this->_connexion();
	}

		/** variables a sauver a la fin du chargement de page */
	public function __sleep()
	{
		if (is_object($this->oReq)) {
				// on ferme la requete en cours
			$this->oReq->closeCursor();
		}

		return array('host', 'db_name', 'user', 'mdp', 'production');
	}

		/** reconnection à la BDD au chargement de la page */
	public function __wakeup()
	{
		$this->_connexion();
	}

	//////////////////////////////////////
	// fonction de gestion de la class //
	//////////////////////////////////////

		/**
		 * test le besoin de recharger la class pdo
		 * @param  string $session_index l'index de $_SESSION ou se trouve l'objet PDO (bdd par defaut)
		 * @return bool                true/false si oui ou non il faut une nouvelle instance
		 */
	public static function needInstance($session_index = 'bdd')
	{
		if(
			empty($_SESSION[$session_index])
			or !is_object($_SESSION[$session_index])
			or $this->callSource !== $_SERVER['PHP_SELF']
			)
			return true;
		else
			return false;
	}

	//////////////
	// PRIVATE //
	//////////////

		/**
		 * crée une instance PDO
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
				echo '<h1>ERREUR : Merci de contacter le Webmaster.</h1>';
				// sinon les infos de debugage
			else{
				echo '<h1 style="color:#a33">ERROR WITH PDO / SQL</h1>'."\n";
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
		 * ```php
		 * 		array('mon_marqueur' => $maVariable);
		 * 		array('marqueur1' => $var1, 'marqueur2'=> $var2);
		 * ```
		 *
		 * Si vous savez que vous allez avoir un seul resultat
		 * (par ex, un `COUNT(*)`, un `getUn...()` )
		 * utilisez en 3eme parametre de query() `Bdd::SINGLE_RES` (ou TRUE)
		 * la methode vous retourneras directement un **objet**
		 *
		 * La requete prend donc ces formes :
		 * ```php
		 * 		$sql  = 'SELECT * FROM `maTable`';
		 * 		$data = $_SESSION['bdd']->query( $sql );
		 *
		 * 		$sql  = 'SELECT * FROM `maTable` WHERE `tab_id` = :code';
		 * 		$data = $_SESSION['bdd']->query( $sql , array('code'=>$codeTable) );
		 *
		 * 		$sql  = 'SELECT COUNT(*) AS `alias_nombre` FROM `maTable` WHERE `tab_id` = :code';
		 * 		$data = $_SESSION['bdd']->query(
		 * 			$sql ,
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
		 * ```
		 *
		 * *Attention, pour les `LIMIT` il faut forcer la variable en integer, via `intval()`*
		 *
		 * On recupère les valeurs en utilisent le **nom de la colonne** dans la table (ou l'alias via `AS mon_alias`)
		 * - Dans le cas du `Bdd::SINGLE_RES`, on a directement un **objet** dans data :
		 * ```php
		 * 		echo $data->tab_colonne_1;
		 * 		echo $data->mon_alias;
		 * ```
		 * - Sinon il faut faire une boucle dans le tableau (**array**) :
		 * ```php
		 * 		foreach($data as $unObjet){
		 * 			echo $unObjet->tab_colonne_2;
		 * 		}
		 * ```
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
				// erreur si on passe autre chose qu'une string
			if(!is_string($sql))
				throw new InvalidArgumentException(__METHOD__.'(): String only: '.ucfirst(gettype($sql)).' found in parametre \'$sql = '.strtoupper(gettype($sql)).'\'' );

				// on regarde si on a des variables en arguments
			if(!empty($arg))
			{
					// on prepare la requete SQL
				$req = $this->oBdd->prepare($sql);
					// on lie les elements a la requete
				foreach ($arg as $key => &$value){
						// erreur si on passe un objet
					if(is_object($value))
						throw new InvalidArgumentException(__METHOD__.'(): String or Integer only: Object found in parametre \'array("'.$key.'"=>OBJECT,...)\'' );
						// erreur si on passe un array
					elseif(is_array($value))
						throw new InvalidArgumentException(__METHOD__.'(): String or Integer only: Array found in parametre \'array("'.$key.'"=>ARRAY,...)\'' );
						// on regarde si il y a type integer a forcer
					elseif (is_int($value))
						$req->bindParam($key, $value, PDO::PARAM_INT);
						// sinon on met en string
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
		catch (Exception $e) {
			$this->_showError($e);
		}

	}

		/**
		 * execute une requete SQL (type DELETE, INSERT INTO, UPDATE)
		 *
		 * On lui passe en 1er parametre la requete SQL avec le(s) marqueur(s).
		 * Un marqueur est une string avec `:` devant
		 * 		ex : 'DELETE FROM `table` WHERE `tab_code` = :mon_marqueur '
		 * 		ex : 'DELETE FROM `table` WHERE `tab_val` > :marqueur1 AND `tab_type` = :marqueur2 '
		 *
		 * On lui donne les arguments dans un tableau.
		 * L'array doit etre associatif `marqueur => valeur`
		 * ```php
		 * 		array('mon_marqueur' => $codeTable);
		 * 		array('marqueur1' => $clause1, 'marqueur2'=>$clause2);
		 * ```
		 *
		 * La requete prend donc ces formes :
		 * ```php
		 * 		$sql  = 'DELETE FROM `table` WHERE `tab_connexion` < 6';
		 * 		$data = $_SESSION['bdd']->exec( $sql );
		 *
		 * 		$sql  = 'DELETE FROM `table` WHERE `tab_val` = :code';
		 * 		$data = $_SESSION['bdd']->exec( $sql , array('code'=>$codeTable) );
		 *
		 * 		$sql  = 'INSERT INTO `table` (`tab_colonne_1`,`tab_colonne_2`)
		 * 			VALUES (:valeur1,:valeur2)';
		 * 		$data = $_SESSION['bdd']->exec(
		 * 			$sql ,
		 * 			array(
		 * 				'valeur1'=>$val1 ,
		 * 				'valeur2'=> $val2,
		 * 				)
		 * 			);
		 * ```
		 *
		 * retourne le nombre de ligne affectee
		 *
		 * @api
		 *
		 * @param  string $sql la requete SQL a executer
		 * @param  array $arg facultatif : l'array avec l(es) parametre(s)
		 * @return int le nombre de ligne affectee
		 */
	public function exec($sql, array $arg = null)
	{
		try {
				// erreur si on passe autre chose qu'une string
			if(!is_string($sql))
				throw new InvalidArgumentException(__METHOD__.'(): String only: '.ucfirst(gettype($sql)).' found in parametre \'$sql = '.strtoupper(gettype($sql)).'\'' );

				// on regarde si on a des variables en arguments
			if(!empty($arg))
			{
					// on prepare la requete SQL
				$req = $this->oBdd->prepare($sql);
					// on lie les elements a la requete
				foreach ($arg as $key => &$value){
						// erreur si on passe un objet
					if(is_object($value))
						throw new InvalidArgumentException(__METHOD__.'(): String or Integer only: Object found in parametre \'array("'.$key.'"=>OBJECT,...)\'' );
						// erreur si on passe un array
					elseif(is_array($value))
						throw new InvalidArgumentException(__METHOD__.'(): String or Integer only: Array found in parametre \'array("'.$key.'"=>ARRAY,...)\'' );
						// on regarde si il y a type integer a forcer
					elseif (is_int($value))
						$req->bindParam($key, $value, PDO::PARAM_INT);
						// sinon on met en string
					else
						$req->bindParam($key, $value, PDO::PARAM_STR);
				}
					// on evite les bug lie à la reference
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
		catch (Exception $e) {
			$this->_showError($e);
		}
	}

		/**
		 * prepare une requête pour une/des execution(s) ultérieure
		 *
		 * **AVANCÉE : Cette methode permet des gains de performance mais vous pouvez utiliser `->query()` et `->exec()` à la place**
		 *
		 * *Cette methode implique l'utilisation de la methode `->execute()`.
		 * Sans appelle à celle-ci, aucune action ne sera réalisée.*
		 *
		 * Utilisez cette methode dans les cas où vous auriez à réaliser plusieurs appelles d'une même requête sur la même page,
		 * mais en devant changer les paramètres à lui passer.
		 *
		 * *Attention, vous ne pouvez préparer qu'une requête à la fois, un nouveau `->prepare()` écraseras le précédent*
		 *
		 * @api
		 *
		 * @param  string $sql la requête SQL a préparer
		 * @return bool      retourne un TRUE/FALSE sur la preparation de la requête
		 */
	public function prepare($sql=null)
	{
		try {
				// erreur si on passe autre chose qu'une string
			if(!is_string($sql))
				throw new InvalidArgumentException(__METHOD__.'(): String only: '.ucfirst(gettype($sql)).' found in parametre \'$sql = '.strtoupper(gettype($sql)).'\'' );

				// on prepare la requete SQL
			$this->oReq = $this->oBdd->prepare($sql);

			return !empty($this->oReq);
		}
			// gestion des erreurs
		catch (Exception $e) {
			$this->_showError($e);
		}
	}
		/**
		 * execute une requête préparé à l'aide de `->prepare()`
		 *
		 * **AVANCÉE : Cette methode permet des gains de performance mais vous pouvez utiliser `->query()` et `->exec()` à la place**
		 *
		 * *Vous devez avant avoir préparé une requête à l'aide de `->prepare()`,
		 * Vous pouvez appeller autemps de fois que vous avez besoin la méthode `->execute()`.*
		 *
		 * Les arguments à passer devrais changer à chaque appelle pour exploiter pleinement cette méthode.
		 *
		 * Retourne :
		 * - soit **un integer** du nombre de ligne affectée si $retour a `Bdd::NO_RES` (ou -1),
		 * - soit **un objet** si $retour a `Bdd::SINGLE_RES` (ou TRUE),
		 * - soit **un array d'objet** si $retour a FALSE ou NULL (par defaut)
		 *
		 * On lui passe en 1er parametre les arguments dans un tableau (aussi nommé array).
		 * L'array doit etre associatif `marqueur => valeur`
		 * ```php
		 * 		array('mon_marqueur' => $maVariable);
		 * 		array('marqueur1' => $var1, 'marqueur2'=> $var2);
		 * ```
		 *
		 * Si vous savez que vous allez avoir un seul resultat
		 * (par ex, un `COUNT(*)`, un `getUn...()` )
		 * utilisez en 2nd parametre de execute() `Bdd::SINGLE_RES` (ou TRUE)
		 * la methode vous retourneras directement un **objet**
		 *
		 * Si vous n'attendais pas de retour
		 * (par ex, un  `DELETE`, `INSERT INTO`, `UPDATE`) )
		 * utilisez en 2nd parametre de execute() `Bdd::NO_RES` (ou -1)
		 * la methode vous retourneras directement un **integer** du nombre de ligne affectée
		 *
		 * La requete prend donc ces formes :
		 * ```php
		 * 			// compter un nombre de ligne pour des types définis
		 * 		$sql  = 'SELECT COUNT(*) AS `alias_nombre` FROM `maTable` WHERE `tab_type` = :type';
		 * 		$_SESSION['bdd']->prepare($sql);
		 * 		$oNbType1 = $_SESSION['bdd']->execute(
		 * 			array('type'=>$unType) ,
		 * 			Bdd::SINGLE_RES );
		 * 		$oNbType2 = $_SESSION['bdd']->execute(
		 * 			array('type'=>$unAutreType) ,
		 * 			Bdd::SINGLE_RES );
		 *
		 * 			// chercher des lignes à plusieurs date
		 * 		$sql  = 'SELECT * FROM `maTable` WHERE `tab_date` BETWEEN :start AND :end LIMIT :nb_total';
		 * 		$_SESSION['bdd']->prepare($sql);
		 * 		$lignesDate1 = $_SESSION['bdd']->execute(
		 * 			array(
		 * 				'start'=> $debutDate1,
		 * 				'end'=> $finDate1,
		 * 				'nb_total'=> intval($nb_total),
		 * 				)
		 * 			);
		 * 		$lignesDate2 = $_SESSION['bdd']->execute(
		 * 			array(
		 * 				'start'=> $debutDate2,
		 * 				'end'=> $finDate2,
		 * 				'nb_total'=> intval($nb_total),
		 * 				)
		 * 			);
		 * ```
		 *
		 * *Attention, pour les `LIMIT` il faut forcer la variable en integer, via `intval()`*
		 *
		 * En cas de retour (autre que `Bdd::NO_RES` donc),
		 * on recupère les valeurs en utilisent le **nom de la colonne** dans la table (ou l'alias via `AS mon_alias`)
		 * - Dans le cas du `Bdd::SINGLE_RES`, on a directement un **objet** dans data :
		 * ```php
		 * 		echo $data->tab_colonne_1;
		 * 		echo $data->mon_alias;
		 * ```
		 * - Sinon il faut faire une boucle dans le tableau (**array**) :
		 * ```php
		 * 		foreach($data as $unObjet){
		 * 			echo $unObjet->tab_colonne_2;
		 * 		}
		 * ```
		 *
		 * @api
		 *
		 * @param  array  $arg       facultatif : l(es) argument(s) à passer
		 * @param  int    $retour    facultatif : demande un resultat en objet, array d'objet, ou pas de résultat
		 * @return array|object|integer             retourne un objet, un tableau (array) d'objets ou le nombre de ligne affectée
		 */
	public function execute(array $arg = null, $retour = false)
	{
		try {
				// erreur si on a pas préparé d'objet
			if(!is_object($this->oReq))
				throw new InvalidArgumentException(__METHOD__.'(): No prepared statement found ! You should call the ->prepare() method before execute with this :<br />$arg = <br><pre>'.var_export($arg, true).'</pre>' );

				// on regarde si on a des variables en arguments
			if(!empty($arg))
			{
					// on lie les elements a la requete
				foreach ($arg as $key => &$value){
						// erreur si on passe un objet
					if(is_object($value))
						throw new InvalidArgumentException(__METHOD__.'(): String or Integer only: Object found in parametre \'array("'.$key.'"=>OBJECT,...)\'' );
						// erreur si on passe un array
					elseif(is_array($value))
						throw new InvalidArgumentException(__METHOD__.'(): String or Integer only: Array found in parametre \'array("'.$key.'"=>ARRAY,...)\'' );
						// on regarde si il y a type integer a forcer
					elseif(is_int($value))
						$this->oReq->bindParam($key, $value, PDO::PARAM_INT);
						// sinon on met en string
					else
						$this->oReq->bindParam($key, $value, PDO::PARAM_STR);
				}
					// on evite les bug lie a la reference
				unset($value);
			}

				// on execute la requete
			$out = $this->oReq->execute();
				// si on a une erreur dans le retour
			if(!$out)
				throw new RuntimeException(__METHOD__.'(): Error found : executed with this :<br />$arg = <br><pre>'.htmlspecialchars(var_export($arg, true)).'</pre>' );

				// si pas de retour demandé (NO_RES)
			if($retour === -1)
				return $this->oReq->rowCount(); // nombre de ligne affectee
				// si on demande une mono-ligne, un simple fetch
			elseif($retour)
				return $this->oReq->fetch();
			else // sinon on cherche tous les OBJET dans un ARRAY
				return $this->oReq->fetchAll();
		}
			// gestion des erreurs
		catch (Exception $e) {
			$this->_showError($e);
		}
	}

}
