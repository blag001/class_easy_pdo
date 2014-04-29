class_easy_pdo
==============

*classe de gestion de connexion a mysql en PDO*

**Voir [l'onglet Releases](https://github.com/blag001/class_easy_pdo/releases) pour télécharger la dernière version stable**

D'abord, la classe en question :
-------------------------------

`/toolSql/Bdd.class.php`

Elle a deux méthodes principales à utiliser :
- `$_SESSION['bdd']->query(string $requete_sql[, array $argument[, bool $retour_mono_line]])`
- `$_SESSION['bdd']->exec(string $requete_sql[, array $argument])`

Et deux méthodes avancées :
- `$_SESSION['bdd']->prepare(string $requete_sql)`
- `$_SESSION['bdd']->execute([array $argument[, int $format_retour]])`

**La documentation complète est disponible en ouvrant avec votre navigateur `doc/index.html`**

### ->query()

Passe une requête SQL avec ou sans variable (type SELECT)

Retourne
- soit **un objet** si `$mono_line` à `Bdd::SINGLE_RES` (ou TRUE),
- soit **un array d'objet** si `$mono_line` à FALSE ou NULL (par defaut)

On lui passe la **requête SQL** avec le(s) marqueur(s).
 Un marqueur est une string avec `:` devant.

- ex : `SELECT * FROM table WHERE tab_code = :mon_marqueur`

On lui donne **les arguments** dans un tableau (aussi nommé array).
 L'array doit être associatif `marqueur => valeur`.

- ex : `array('mon_marqueur' => $codeTable)`
- ex : `array('marqueur1' => $var1, 'marqueur2'=> $var2)`

Si vous savez que vous allez avoir un seul résultat *(par ex, un `COUNT()`, un `getUn...()` )*,
utilisez en 3ème paramètre de `query()` **la constante** `Bdd::SINGLE_RES` (ou TRUE).

La méthode vous retournera alors directement un **objet**

La requête prend donc ces formes :
```php
<?php
$sql  = 'SELECT * FROM `maTable`';
$data = $_SESSION['bdd']->query( $sql );

$sql  = 'SELECT * FROM `maTable` WHERE `tab_id` = :code';
$data = $_SESSION['bdd']->query( $sql , array('code'=>$codeTable) );

$sql  = 'SELECT COUNT(*) AS `alias_nombre` FROM `maTable` WHERE `tab_id` = :code';
$data = $_SESSION['bdd']->query(
	$sql ,
	array('code'=>$codeTable) ,
	Bdd::SINGLE_RES );

$sql  = 'SELECT * FROM `maTable`
	WHERE `tab_id` = :code
		AND `tab_pays` = :pays
	LIMIT :start, :nb_total';
$data = $_SESSION['bdd']->query(
	$sql ,
	array(
		'code'=>$codeTable ,
		'pays'=> $pays,
		'start'=> intval($start),
		'nb_total'=> intval($nb_total),
		)
	); ?>
```

*Attention, pour les `LIMIT` il faut forcer la variable en integer, via `intval()`*

On recupère les valeurs en utilisant le **nom de la colonne** dans la table (ou l'alias via `AS mon_alias`)
- Dans le cas du `Bdd::SINGLE_RES`, on a directement un OBJET dans data :

```php
<?php
echo $data->tab_colonne_1;
echo $data->mon_alias; ?>
```

- Sinon il faut faire une boucle dans le tableau (array) :

```php
<?php
foreach($data as $unObjet){
	echo $unObjet->tab_colonne_2;
} ?>
```

### ->exec()

Exécute une requête SQL (type DELETE, INSERT INTO, UPDATE)

On lui passe la **requête SQL** avec les marqueurs.
Un marqueur est une string avec `:` devant :
- ex : `DELETE FROM table WHERE tab_code = :mon_marqueur `
- ex : `DELETE FROM table WHERE tab_val > :marqueur1 AND tab_type = :marqueur2 `

On lui donne **les arguments** dans un tableau.
 L'array doit être associatif `marqueur => valeur` :
- ex : `array('mon_marqueur' => $codeTable)`
- ex : `array('marqueur1' => $clause1, 'marqueur2'=>$clause2)`

La requête prend donc ces formes :
```php
<?php
$sql  = 'DELETE FROM `table` WHERE `tab_connexion` < 6';
$data = $_SESSION['bdd']->exec( $sql );

$sql  = 'DELETE FROM `table` WHERE `tab_val` = :code';
$data = $_SESSION['bdd']->exec( $sql , array('code'=>$codeTable) );

$sql  = 'INSERT INTO `table` (`tab_colonne_1`,`tab_colonne_2`)
	VALUES (:valeur1,:valeur2)';
$data = $_SESSION['bdd']->exec(
	$sql ,
	array(
		'valeur1'=>$val1,
		'valeur2'=> $val2,
		)
	); ?>
```

Cette méthode retourne le **nombre de lignes** affectées.


Ensuite, la page d'instanciation :
----------------------------------

`/inc/connexion.inc.php`

Juste les valeurs dans `new Bdd()` à changer suivant votre configuration :
- passez `null,null,null,null,false` si vous voulez utiliser les valeurs par défaut,
- Sinon remplacez comme indiqué dans les commentaires
- une fois en production utilisez `TRUE` en 5eme paramètre

Le bout d'index qui va bien :
--------------------------------------------------------------

`/index.php`

En gros vous avez trois lignes à ajouter en haut de l'index principal.

** /!\ Attention à l'ordre de ces lignes, sinon GROS BUG... /!\ **

Autres exemples :
---------------

J'ajoute aussi un bout de code de démo (c'est un model tiré du MVC [VLyon](https://github.com/blag001/Vlyon_Reparation_Mobile) ) :

```php
<?php
class OdbBonIntervention
{
	public function __construct()
	{
	}
		/**
		 * verifie si le code correspond à un bon d'intervention
		 * @param  integer $code le code du bon d'intervention
		 * @return bool       TRUE/FALSE si est ou n'est pas un bon
		 */
	public function estBonInter($code)
	{
		if(!empty($code))
		{
			$req = "SELECT COUNT(*) AS nb
					FROM BONINTERV
					WHERE BI_Num = :code";

			$data = $_SESSION['bdd']->query($req , array('code'=>$code), Bdd::SINGLE_RES);

			return (bool) $data->nb;
		}

		return false;
	}
		/**
		 * test si le n° de bon est bien un bon réalisé par le technicien
		 * @param  integer $code     le code du bon
		 * @param  integer $techCode le code du technicien
		 * @return bool           TRUE/FALSE si est/n'est pas un bon du technicien
		 */
	public function estMonBonInter($code, $techCode)
	{
		if(!empty($code) and !empty($techCode))
		{
			$req = "SELECT COUNT(*) AS nb
					FROM BONINTERV
					WHERE BI_Num = :code
						AND BI_Technicien = :techCode";

			$data = $_SESSION['bdd']->query($req ,
				array('code'=>$code, 'techCode'=>$techCode),
				Bdd::SINGLE_RES);

			return (bool) $data->nb;
		}

		return false;
	}
		/**
		 * récupère les bons d'interventions et formate la date en Fr
		 * @return array tableau d'objet avec le contenu des bons
		 */
	public function getLesBonsInter()
	{
		$req = "SELECT *,
					DATE_FORMAT(BI_DatDebut, '%d/%m/%Y') AS BI_DatDebut,
					DATE_FORMAT(BI_DatFin, '%d/%m/%Y') AS BI_DatFin
				FROM BONINTERV";

		$lesBonsInter = $_SESSION['bdd']->query($req);

		return $lesBonsInter;
	}
		/**
		 * récupère une intervention par son code
		 * @param  integer $code     le code du bon
		 * @param  integer $techCode le code du technicien
		 * @return object           un objet qui contient les données du bon
		 */
	public function getMonBonInter($code, $techCode)
	{
		$req = "SELECT *,
					DATE_FORMAT(BI_DatDebut, '%d/%m/%Y') AS BI_DatDebut,
					DATE_FORMAT(BI_DatFin, '%d/%m/%Y') AS BI_DatFin
				FROM BONINTERV
				WHERE BI_Num = :code
					AND BI_Technicien = :techCode";

		$leBonInter = $_SESSION['bdd']->query($req,
			array('code'=>$code, 'techCode'=>$techCode),
			Bdd::SINGLE_RES);

		return $leBonInter;
	}

		/**
		 * récupère les interventions d'un technicien via son matricule
		 * @param  integer $techCode matricule du technicien
		 * @return array           tableau d'objets des bons d'interventions
		 */
	public function getMesInterventions($techCode)
	{
		$req = "SELECT *,
					DATE_FORMAT(BI_DatDebut, '%d/%m/%Y') AS BI_DatDebut,
					DATE_FORMAT(BI_DatFin, '%d/%m/%Y') AS BI_DatFin
				FROM BONINTERV, VELO
				WHERE BI_Technicien = :techCode
					AND BONINTERV.BI_Velo = VELO.Vel_Num";

		$lesBonsInter = $_SESSION['bdd']->query($req, array('techCode'=>$techCode));

		return $lesBonsInter;
	}

		/**
		 * On crée une intervention
		 * @param  integer $code code du bon à créer
		 * @return integer       nombre de lignes créées
		 */
	public function creerUnBonInter($code)
	{
		$req = 'INSERT INTO BONINTERV (
					 `BI_Num`,
					 `BI_Velo`,
					 `BI_DatDebut`,
					 `BI_DatFin`,
					 `BI_CpteRendu`,
					 `BI_Reparable`,
					 `BI_Demande`,
					 `BI_Technicien`,
					 `BI_SurPlace`,
					 `BI_Duree`
					)
				VALUES (
					 :code,
					 :veloCode,
					 :dateDebut,
					 :dateFin,
					 :cpteRendu,
					 :reparable,
					 :demande,
					 :technicienCode,
					 :surPlace,
					 :duree
				 	)';

		$out = $_SESSION['bdd']->exec($req, array(
				 'code'=>$code,
				 'veloCode'=>$_POST['veloCode'],
				 'dateDebut'=>$_POST['dateDebut'],
				 'dateFin'=>$_POST['dateFin'],
				 'cpteRendu'=>$_POST['cpteRendu'],
				 'reparable'=>$_POST['reparable'],
				 'demande'=>$_POST['demande'],
				 'technicienCode'=>$_POST['technicienCode'],
				 'surPlace'=>$_POST['surPlace'],
				 'duree'=>$_POST['duree'],
				));
		return $out;
	}
		/**
		 * recherche les interventions d'un technicien via une valeur
		 * @param  string $valeur   terme recherché
		 * @param  integer $techCode le code du technicien
		 * @return array           tableau d'objet des bons d'interventions
		 */
	public function searchMesBonIntervention($valeur, $techCode)
	{
		$req = "SELECT *
				FROM `BONINTERV`
				WHERE
					(
						`BI_Num` LIKE :valeur
						OR `BI_Velo` LIKE :valeur
						OR `BI_Demande` LIKE :valeur
					)
					AND BI_Technicien = :techCode"
					;

		$lesBonsInter = $_SESSION['bdd']->query($req,
			array('valeur'=>'%'.$valeur.'%', 'techCode'=>$techCode));

		return $lesBonsInter;
	}
}
```
