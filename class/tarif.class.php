<?php

if (!class_exists('SeedObject'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}


class Tarif extends SeedObject
{
	public $table_element = 'tarif_conditionnement';

	public $element = 'tarif';

	/** @var array */
	public static $TPriceType = array(
		'PERCENT' => 'PERCENT'
		,'PRICE' => 'PRICE'
		,'PERCENT/PRICE' => 'PERCENT/PRICE'
	);

	/** @var string */
	public $unite;

	/** @var int */
	public $unite_value;

	/** @var string */
	public $price_base_type;

	/** @var string */
	public $type_price;

	/** @var string */
	public $currency_code;

	/** @var double */
	public $prix;

	/** @var double */
	public $tva_tx;

	/** @var double */
	public $quantite;

	/** @var double */
	public $remise_percent;

	/** @var int */
	public $fk_user_author;

	/** @var int */
	public $fk_product;

	/** @var int */
	public $fk_country;

	/** @var int */
	public $fk_categorie_client;

	/** @var int */
	public $fk_soc;

	/** @var int */
	public $fk_project;

	/** @var int */
	public $date_debut;

	/** @var int */
	public $date_fin;


	public function __construct($db)
	{
		global $langs;
		
		$this->db = $db;
		
		$this->fields=array(
			'unite'=>array('type'=>'string')
			,'unite_value'=>array('type'=>'integer') // date, integer, string, float, array, text
			,'price_base_type'=>array('type'=>'string') // date, integer, string, float, array, text
			,'type_price'=>array('type'=>'string') // date, integer, string, float, array, text
			,'currency_code'=>array('type'=>'string') // date, integer, string, float, array, text
			,'prix'=>array('type'=>'double')
			,'tva_tx'=>array('type'=>'double')
			,'quantite'=>array('type'=>'double')
			,'remise_percent'=>array('type'=>'double')
			,'fk_user_author'=>array('type'=>'integer','index'=>true)
			,'fk_product'=>array('type'=>'integer','index'=>true)
			,'fk_country'=>array('type'=>'integer','index'=>true)
			,'fk_categorie_client'=>array('type'=>'integer','index'=>true)
			,'fk_soc'=>array('type'=>'integer','index'=>true)
			,'fk_project'=>array('type'=>'integer','index'=>true)
			,'date_debut'=>array('type'=>'date')
			,'date_fin'=>array('type'=>'date')
		);
		
		$this->init();

		$this->date_debut = null;
		$this->date_fin = null;
	}

	public function fetch($id, $loadChild = true)
	{
		$res = parent::fetch($id, $loadChild);

		if ($this->date_debut === 0) $this->date_debut = null;
		if ($this->date_fin === 0) $this->date_fin = null;

		return $res;
	}


	public static function getPriceType($code)
	{
		global $langs;

		return $langs->trans(self::$TPriceType[$code]);
	}

	public function save()
	{
		global $conf,$user;
		
		if (!$this->id) $this->fk_user_author = $user->id;

		if (empty($this->currency_code)) $this->currency_code = $conf->currency;

		$res = $this->id>0 ? $this->updateCommon($user) : $this->createCommon($user);
		
		return $res;
	}
	
	
	public function loadBy($value, $field, $annexe = false)
	{
		$res = parent::loadBy($value, $field, $annexe);
		
		return $res;
	}
	
	public function load($id, $ref, $loadChild = true)
	{
		$res = parent::fetchCommon($id, $ref);
		
		if ($loadChild) $this->fetchObjectLinked();
		
		return $res;
	}

	/**
	 * TODO ajouter les autres types d'objets possible
	 * @param Propal 		$parent
	 * @param PropaleLigne 	$object
	 * @param integer 		$fk_country
	 * @param array 		$TCategoryId
	 * @param double 		$qty
	 * @param double 		$weight
	 * @param integer 		$weight_units
	 * @param integer 		$fk_project
	 * @param string 		$currency_code
	 */
	public static function getTarif($parent, $object, $fk_country, $TCategoryId, $qty, $weight, $weight_units, $fk_project, $currency_code)
	{
		global $db;


		/********/


		$idProd = $object->fk_product;
		$devise = $currency_code;
		$fk_soc = $parent->thirdparty->id;
		$table = 'tarif_conditionnement';
		$TFk_categorie = $TCategoryId;

		$sql = "SELECT tarif.rowid,
                 prod_extra.type_remise as type_remise,
                 tarif.currency_code,
                 tarif.type_price,
                 tarif.quantite as quantite,
                 tarif.unite as unite,
                 tarif.prix as prix,
                 tarif.unite_value as unite_value,
                 tarif.tva_tx as tva_tx,
                 tarif.remise_percent as remise_percent,
                 tarif.date_debut as date_debut,
                 tarif.date_fin as date_fin,
                 product.weight
                FROM ".MAIN_DB_PREFIX.$table."                   AS tarif
                 LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields AS prod_extra ON prod_extra.fk_object = tarif.fk_product
                 LEFT JOIN ".MAIN_DB_PREFIX."product             AS product    ON prod_extra.fk_object = product.rowid
                WHERE fk_product = ".$idProd." AND (tarif.currency_code = '$devise' OR tarif.currency_code IS NULL OR tarif.currency_code = '')";

		$sql_additional_conditions = '';
		$sql_order_by = array();

		// TODO: make precedence order configurable?
		$fields_by_precedence = array (
		    // rules with this document's client have highest precedence
		    'fk_soc' => $fk_soc,
            // rules with any category of this document's client
            'fk_categorie_client' => $TFk_categorie,
            // rules with this document's client's country
            'fk_country' => $fk_country
        );

		foreach ($fields_by_precedence as $field_name => $field_content) {
		    // skip empty or undefined fields
		    if (empty($field_content) || $field_content <= 0) continue;

		    if (is_array($field_content)) { $sql_retained_values  = '(-1,0,' . implode(',', $field_content) . ')'; }
            else                          { $sql_retained_values = '(-1,0,' . $field_content . ')'; }
		    $sql_additional_conditions .= " AND tarif.$field_name IN $sql_retained_values ";
		    $sql_order_by[] = "tarif.$field_name DESC";
        }

		$sql_additional_conditions .= ' AND tarif.quantite <= ' . $qty;
		$sql_order_by[] = 'tarif.quantite DESC';
		$sql_order_by[] = 'tarif.fk_project DESC';

		$sql_order_by = ' ORDER BY ' . implode(', ', $sql_order_by);

		$sql .= $sql_additional_conditions;
		$sql .= $sql_order_by;
         prsql($sql);// exit;
        $matching_rates = array();
//echo $sql;exit;
		$resql = $db->query($sql);
		while($obj = $db->fetch_object($resql))
		{
			$tarif = new Tarif($db);
			$tarif->fetch($obj->rowid);

            // filter out rates whose date range doesn't encompass the document's date
			if ($tarif->date_debut !== '0000-00-00 00:00:00' && $tarif->date_debut !== '1000-01-01 00:00:00' && $tarif->date_debut !== null)
			{
				if (!empty($object->date_start) || !empty($parent->date) )
				{
					if (!empty($object->date_start) && $tarif->date_debut > $object->date_start) continue;
					// Test si j'ai pas de date de saisie sur la ligne dans ce cas la je test la date du document
					elseif (empty($object->date_start) && !empty($parent->date) && $tarif->date_debut > $parent->date) continue;
				}
			}

			if ($tarif->date_fin !== '0000-00-00 00:00:00' && $tarif->date_fin !== '1000-01-01 00:00:00' && $tarif->date_fin !== null)
			{
				if (is_object($object) && (!empty($object->date_start) || !empty($parent->date)))
				{
					if (!empty($object->date_start) && $tarif->date_fin <= $object->date_start) continue;
					// Test si j'ai pas de date de saisie sur la ligne dans ce cas la je test la date du document
					elseif (empty($object->date_start) && !empty($parent->date) && $tarif->date_fin <= $parent->date) continue;
				}
			}

			// TODO à quoi sert ce if ou à quoi sert ce code tout court ?
//			if(strpos($tarif->type_price,'PRICE') !== false)
//			{
//				if(($tarif->type_remise == "qte" || $tarif->type_remise == 0) && $qty >= $tarif->quantite)
//				{
//					//Ici on récupère le pourcentage correspondant et on arrête la boucle
//					return array(self::priceWithMultiprix($obj->prix, $parent->thirdparty->price_level), $tva_tx);
//				}
//				elseif($obj->type_remise == "conditionnement" && $weight >= $obj->quantite &&  $obj->unite_value == $weight_units)
//				{
//					return array(self::priceWithMultiprix($obj->prix * ($weight / (($obj->weight != 0) ? $obj->weight : 1 )), $parent->thirdparty->price_level), $tva_tx); // prise en compte unité produit et poid init produit
//				}
//			}

			return $tarif;

		}

		/******/
		return false;
//
//		$resql = $db->query($sql);
//		if ($resql)
//		{
//			$TTarif = array();
//			while ($obj = $db->fetch_object($resql))
//			{
//				$tarif = new Tarif($db);
//				$tarif->fetch($obj->rowid);
//				$TTarif[$tarif->id] = $tarif;
//			}
//		}
//		else
//		{
//			dol_print_error($db);
//			exit;
//		}
//
//
//		// TODO selected the appropriate tarif
//		foreach ($TTarif as $tarif)
//		{
//
//		}
//
////		echo $sql;exit;
//		echo $sql;exit;
	}

	public static function priceWithMultiprix($price, $price_level)
	{
		global $conf;

		if (!empty($conf->multipsrixcascade->enabled))
		{
			/*
			 * Si multiprix cascade est présent, on ajoute le pourcentage de réduction défini directement dans le multiprix
			 */

			$TNiveau  = unserialize($conf->global->MULTI_PRIX_CASCADE_LEVEL);

			if(isset($TNiveau[$price_level]))
			{
				$price = $price * ($TNiveau[$price_level] / 100);
			}
		}

		return $price;
	}

	public static function getRemise()
	{

	}

	public static function getPrix()
	{

	}

	public static function getCategTiers()
	{

	}

}

class TarifLog extends Tarif
{
	public $table_element = 'tarif_conditionnement_log';

	public $element = 'tarif_log';

	/** @var string */
	public $motif_changement;

	public function __construct($db)
	{
		parent::__construct($db);

		$this->fields['motif_changement'] = array('type'=>'text');

//		$this->init();
	}
}

/*
class TarifDet extends TObjetStd
{
	public $table_element = 'tarifdet';

	public $element = 'tarifdet';
	
	public function __construct($db)
	{
		global $conf,$langs;
		
		$this->db = $db;
		
		$this->init();
		
		$this->user = null;
	}
	
	
}
*/
