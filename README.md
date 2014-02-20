class_easy_pdo
==============

*class de gestion de connexion a mysql en PDO*
Voir [l'onglet Releases](https://github.com/blag001/class_easy_pdo/releases) pour télécharger la dernière version stable

D'abord, la class en question :
-------------------------------

`/toolSql/Bdd.class.php`

Elle a deux méthodes à utiliser :
- `$_SESSION['bdd']->query(string $requete_sql[, array $argument[, bool $retour_mono_line]])`
- `$_SESSION['bdd']->exec(string $requete_sql[, array $argument])`

### ->query()

Passe une requete SQL avec ou sans variable (SELECT)

Retourne
- soit **un OBJET** si `$mono_line` a TRUE ou `Bdd::SINGLE_RES`,
- soit **un ARRAY d'OBJET** si `$mono_line` a FALSE ou NULL

On lui passe la **requete SQL** avec le(s) marqueur(s).
 Un marqueur est une string avec ':' devant.

- ex : `SELECT * FROM table WHERE tab_code = :mon_marqueur`

On lui donne **les arguments** dans un tableau (aussi nomme array).
 L'array doit etre associatif `marqueur => valeur`.

- ex : `array('mon_marqueur' => $codeTable)`
- ex : `array('marqueur1' => $var1, 'marqueur2'=> $var2)`

Si vous savez que vous allez avoir un seul resultat *(par ex, un `COUNT()`, un `getUn...()` )*,
utilisez en 3ème parametre de `query()` **la constante** `Bdd::SINGLE_RES` (ou TRUE).

La methode vous retourneras alors directement un **OBJET**

La requête prend donc ces formes :
```php
<?php
$data = $_SESSION['bdd']->query( "SELECT * FROM table" );
$data = $_SESSION['bdd']->query( "SELECT * FROM table WHERE tab_code = :code" , array('code'=>$codeTable) );
$data = $_SESSION['bdd']->query( "SELECT COUNT(*) AS alias_nombre FROM table WHERE tab_code = :code" ,
	array('code'=>$codeTable) , Bdd::SINGLE_RES );
$data = $_SESSION['bdd']->query( "SELECT * FROM table WHERE tab_code = :code AND tab_pays = :pays" ,
	array('code'=>$codeTable , 'pays'=> $pays) );?>
```

On recupere les valeurs en utilisent le nom de la colonne dans la table (ou l'alias via `AS mon_alias`), dans le cas du `Bdd::SINGLE_RES`, on a directement un OBJET dans data :
- `echo $data->tab_colonne_1;`
- `echo $data->mon_alias;`

Sinon il faut faire une boucle dans le tableau (array) :
```php
<?php
foreach($data as $unObjet){
	echo $unObjet->tab_colonne_2;
} ?>
```

### ->exec()

Execute une requete SQL (DELETE, INSERT INTO, UPDATE)

On lui passe la **requete SQL** avec les marqueurs.
Un marqueur est une string avec `:` devant :
- ex : `DELETE FROM table WHERE tab_code = :mon_marqueur `
- ex : `DELETE FROM table WHERE tab_val > :marqueur1 AND tab_type = :marqueur2 `

On lui donne **les arguments** dans un tableau.
 L'array doit etre associatif `marqueur => valeur` :
- ex : `array('mon_marqueur' => $codeTable)`
- ex : `array('marqueur1' => $clause1, 'marqueur2'=>$clause2)`

La requete prend donc ces formes :
```php
<?php
$data = $_SESSION['bdd']->exec( "DELETE FROM table WHERE tab_connexion < 6" );
$data = $_SESSION['bdd']->exec( "DELETE FROM table WHERE tab_val = :code" , array('code'=>$codeTable) );
$data = $_SESSION['bdd']->exec( "INSERT INTO table (`tab_colonne_1`,`tab_colonne_2`) VALUES (:valeur1,:valeur2)" ,
 			array('valeur1'=>$val1 , 'valeur2'=> $val2) ); ?>
```

Cette methode retourne le **nombre de ligne** affectée.


Ensuite, la page d'instanciation :
----------------------------------

`/inc/connexion.inc.php`

Juste le code dans `new Bdd()` à changer suivant votre configuration :
- passez `null,null,null,null` si vous voulez utiliser les valeurs par défaut,
- Sinon remplacez comme indiqué dans les commentaires

Et le bout d'index qui va bien :
--------------------------------------------------------------

`/index.php`

En gros vous avez trois lignes à ajouter à en haut de l'index principal.

**/!\ Attention à l'ordre de ces lignes, sinon GROS BUG... /!\**

Autre exemple :
---------------

J'ajoute aussi un bout de code de démo (c'est un model tiré du MVC [VLyon](https://github.com/blag001/Vlyon_Reparation_Mobile) ) :

```php
<?php
class OdbBonIntervention
{
	private $oBdd;

	public function __construct()
	{
		$this->oBdd = $_SESSION['bdd'];
	}

	public function estBonInter($code)
	{
		if(!empty($code))
		{
			$req = "SELECT COUNT(*) AS nb
					FROM BONINTERV
					WHERE BI_Num = :code";

			$data = $this->oBdd->query($req , array('code'=>$code), Bdd::SINGLE_RES);

			return (bool) $data->nb;
		}

		return false;
	}

	public function estMonBonInter($code, $techCode)
	{
		if(!empty($code) and !empty($techCode))
		{
			$req = "SELECT COUNT(*) AS nb
					FROM BONINTERV
					WHERE BI_Num = :code
						AND BI_Technicien = :techCode";

			$data = $this->oBdd->query($req ,
				array('code'=>$code, 'techCode'=>$techCode),
				Bdd::SINGLE_RES);

			return (bool) $data->nb;
		}

		return false;
	}

	public function getLesBonsInter()
	{
		$req = "SELECT *,
					DATE_FORMAT(BI_DatDebut, '%d/%m/%Y') AS BI_DatDebut,
					DATE_FORMAT(BI_DatFin, '%d/%m/%Y') AS BI_DatFin
				FROM BONINTERV";

		$lesBonsInter = $this->oBdd->query($req);

		return $lesBonsInter;
	}

	public function getMonBonInter($code, $techCode)
	{
		$req = "SELECT *,
					DATE_FORMAT(BI_DatDebut, '%d/%m/%Y') AS BI_DatDebut,
					DATE_FORMAT(BI_DatFin, '%d/%m/%Y') AS BI_DatFin
				FROM BONINTERV
				WHERE BI_Num = :code
					AND BI_Technicien = :techCode";

		$leBonInter = $this->oBdd->query($req,
			array('code'=>$code, 'techCode'=>$techCode),
			Bdd::SINGLE_RES);

		return $leBonInter;
	}

	/**
	 * on visualise les interventions effectuees par un technicien gràce à son matricule
	 * @param  int $techCode matricule du technincient
	 * @return array           tableau d'objets
	 */
	public function getMesInterventions($techCode)
	{
		$req = "SELECT *,
					DATE_FORMAT(BI_DatDebut, '%d/%m/%Y') AS BI_DatDebut,
					DATE_FORMAT(BI_DatFin, '%d/%m/%Y') AS BI_DatFin
				FROM BONINTERV, VELO
				WHERE BI_Technicien = :techCode
					AND BONINTERV.BI_Velo = VELO.Vel_Num";

		$lesBonsInter = $this->oBdd->query($req, array('techCode'=>$techCode));

		return $lesBonsInter;
	}

	/**
	 * on cree une intervention
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

		$out = $this->oBdd->exec($req, array(
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

		$lesBonsInter = $this->oBdd->query($req,
			array('valeur'=>'%'.$valeur.'%', 'techCode'=>$techCode));

		return $lesBonsInter;
	}
}
```
