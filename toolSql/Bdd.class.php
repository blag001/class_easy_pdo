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

		/**
		 * constante en cas de resultat unique
		 *
		 * Si vous savez que vous allez avoir un seul resultat
		 * (par exemple: un COUNT(*), un getUn...() )
		 * utilisez en 3eme param de query() "Bdd::SINGLE_RES"
		 * la methode vous retourneras directement un OBJET
		 */
	const SINGLE_RES = true;

	////////////////////
	// CONSTRUCTEUR  //
	////////////////////

		/**
		 * cree une instance PDO avec les valeurs en argument
		 *
		 * @param STRING $host le host de votre base de donnee mysql
		 * @param STRING $db_name le nom de la base de donnee
		 * @param STRING $user le nom d'utilisateur
		 * @param STRING $mdp le mot de passe a utiliser
		 * @param STRING $production desactive les messages d'erreurs
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
		$this->connexion();
	}

		/**
		 * variable a sauver a la fin du chargement de page
		 *
		 * a la fin du chargement d'une page, les variable _SESSION
		 * sont toute convertie en STRING, le lien PDO doit donc etre detruit
		 * on ne concerve que les variables pour le chargement de la page suivante
		 *
		 * @return ARRAY
		 */
	public function __sleep()
	{
		return array('host', 'db_name', 'user', 'mdp', 'production');
	}

		/**
		 * on reconnect au chargement de la page
		 */
	public function __wakeup()
	{
		$this->connexion();
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
	protected function connexion()
	{
		try{
				// on appelle le constructeur POD
			$this->oBdd = new PDO(
				'mysql:host='.$this->host.';dbname='.$this->db_name,
				$this->user,
				$this->mdp
				);
				// on active le mode retour d'OBJET
			$this->oBdd->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
				// on active le mode erreur par exception
			$this->oBdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				// on force l'utilisation d'UTF-8
			$this->oBdd->exec("SET CHARACTER SET utf8");
		}
			catch (Exception $e){
				die('Erreur : ' . $e->getMessage());
		}
	}

	/////////////
	// PUBLIC //
	/////////////

		/**
		 * Passe les requetes avec ou sans variable
		 *
		 * Retourne soit **un OBJET** si $mono_line a TRUE (ou "Bdd::SINGLE_RES"),
		 * soit **un ARRAY d'OBJET** si $mono_line a FALSE ou NULL
		 *
		 * On lui passe la requete SQL avec le(s) marqueur(s).
		 * 	un marqueur est une string avec ':' devant
		 * 		ex : 'SELECT * FROM Table WHERE Tab_code = :mon_marqueur '
		 * On lui donne les arguments dans un tableau.
		 * 	l'array doit etre associatif marqueur => valeur
		 * 		ex : 'array('mon_marqueur' => $codeTable)'
		 * 		ex : 'array('marqueur1' => $var1, 'marqueur2'=> $var2)'
		 *
		 * Si vous savez que vous allez avoir un seul resultat
		 * (par ex, un COUNT(*), un getUn...() )
		 * utilisez en 3eme param "Bdd::SINGLE_RES" (ou TRUE)
		 * la methode vous retourneras directement un OBJET
		 *
		 * (Expoite a la fois les query() et les prepare() de PDO)
		 *
		 * La requete prend donc ces formes :
		 * 		$data = $_SESSION['bdd']->query( 'SELECT * FROM table' );
		 * 		$data = $_SESSION['bdd']->query( 'SELECT * FROM table WHERE tblCode = :code' , array('code'=>$codeTable) );
		 * 		$data = $_SESSION['bdd']->query( 'SELECT COUNT(*) AS nb FROM table WHERE tblCode = :code' ,
		 * 			array('code'=>$codeTable) ,
		 * 			Bdd::SINGLE_RES );
		 * 		$data = $_SESSION['bdd']->query( 'SELECT * FROM table WHERE tblCode = :code AND tblPays = :pays' ,
		 * 			array('code'=>$codeTable , 'pays'=> $pays) );
		 *
		 * On recupere les valeur en utilisent le nom de la colonne dans la table (ou l'alias via "AS ...")
		 * dans le cas du SINGLE_RES, on a directement un objet dans data :
		 * 		echo $data->tblColonne1;
		 * 		echo $data->nb;
		 * Sinon il faut faire une boucle dans le tableau (array) retourne
		 * 		foreach($data as $unObjet){
		 * 			echo $unObjet->tblColonne2;
		 * 		}
		 *
		 * @param  string  $sql
		 * @param  array  $arg
		 * @param  boolean $mono_line
		 * @return mixed
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

				// si on demande une mono-ligne, simple fetch
			if($mono_line)
				$data = $req->fetch();
			else // sinon on cherche tout les obj en array
				$data = $req->fetchAll();

				// on ferme la requete en cours
			$req->closeCursor();

			return $data;
		}
		catch (PDOException $e) {
			if($this->production)
				echo 'ERREUR : Merci de contacter le Webmaster.';
			else{
				echo '<h1 style="color:#a33">ERROR SQL WITH PDO</h1>'."\n";
				echo '<strong>'.$e->getMessage().'</strong><br />'."\n";
				echo '<h2 style="color:#a33">In this :</h2>'."\n";
				echo '<pre style="color:#fff; background-color:#333">'.$e->getTraceAsString().'</pre>';
			}
			die();
		}

	}

		/**
		 * execute une requete SQL
		 *
		 * On lui passe la requete SQL avec les marqueurs.
		 * 	un marqueur est une string avec ':' devant
		 * 		ex : 'DELETE FROM Table WHERE tblCode = :mon_marqueur '
		 * 		ex : 'DELETE FROM Table WHERE tblVal > :marqueur1 AND tblType = :marqueur2 '
		 * on lui donne les arguments dans un tableau.
		 * 	l'array doit etre associatif marqueur => valeur
		 * 		ex : 'array('mon_marqueur' => $codeTable)'
		 * 		ex2 : 'array('marqueur1' => $clause1, 'marqueur2'=>$clause2)'
		 *
		 * La requete prend donc ces formes :
		 * 		$data = $_SESSION['bdd']->exec( 'DELETE FROM Table WHERE tblInactivite > 60' );
		 * 		$data = $_SESSION['bdd']->exec( 'DELETE FROM Table WHERE tblVal > :code' , array('code'=>$codeTable) );
		 * 		$data = $_SESSION['bdd']->exec( 'DELETE FROM Table WHERE tblVal > :code AND tblType = :type' ,
		 * 			array('code'=>$codeTable , 'type'=> $typeInfo) );
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
		catch (PDOException $e) {
			if($this->production)
				echo 'ERREUR : Merci de contacter le Webmaster.';
			else{
				echo '<h1 style="color:#a33">ERROR SQL WITH PDO</h1>'."\n";
				echo '<strong>'.$e->getMessage().'</strong><br />'."\n";
				echo '<h2 style="color:#a33">In this :</h2>'."\n";
				echo '<pre style="color:#fff; background-color:#333">'.$e->getTraceAsString().'</pre>';
			}
			die();
		}
	}
}
