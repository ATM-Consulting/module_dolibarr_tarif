<?php

class TTarif extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'tarif_conditionnement');
		parent::add_champs('unite','type=chaine;');
		parent::add_champs('unite_value','type=entier;');
		parent::add_champs('price_base_type,type_price,currency_code','type=chaine;');
		parent::add_champs('fk_categorie_client','type=entier;');
		parent::add_champs('prix,tva_tx,quantite,remise_percent','type=float;');
		parent::add_champs('fk_user_author,fk_product,fk_country','type=entier;index;');
		
		parent::_init_vars();
		parent::start();
		
		//$this->fk_categorie_client = 0;
		
		$this->TType_price = array(
			'PERCENT'=>$langs->trans('PERCENT')
			,'PRICE'=>$langs->trans('PRICE')
			,'PERCENT/PRICE'=>$langs->trans('PERCENT/PRICE')
		);
	}
	
	static function getRemise(&$db, $idProd,$qty,$conditionnement,$weight_units, $fk_country=0, $TFk_categorie=array(), $remise_ligne_de_depart = 0){
		
		//chargement des prix par conditionnement associé au produit (LISTE des tarifs pour le produit testé & TYPE_REMISE grâce à la jointure !!!)
		$sql = "SELECT p.type_remise as type_remise, tc.quantite as quantite, tc.type_price, tc.unite as unite, tc.prix as prix, tc.unite_value as unite_value, tc.tva_tx as tva_tx, tc.remise_percent as remise_percent";
		$sql.= " FROM ".MAIN_DB_PREFIX."tarif_conditionnement as tc";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as p on p.fk_object = tc.fk_product";
		$sql.= " WHERE fk_product = ".$idProd;
		
		if($fk_country>0) {
			
			$sql.=" AND tc.fk_country IN (0, $fk_country)";
			
		}
		if(!empty($TFk_categorie)) {
			
			$sql.=" AND tc.fk_categorie_client IN (-1,0, ".implode(',', $TFk_categorie).")";

			
		}		
		
		$sql.= " ORDER BY quantite DESC, tc.fk_country DESC, tc.fk_categorie_client DESC";
		
		$resql = $db->query($sql);
//exit($sql);		
		if($resql->num_rows > 0) {
			$pallier = 0;
			while($res = $db->fetch_object($resql)) {
				
				if( strpos($res->type_price,'PERCENT')!==false ){
					
					if($res->type_remise == "qte" && $qty >= $res->quantite){
						return array($res->remise_percent, $res->type_price, $res->tva_tx);
					} 
					else if($res->type_remise == "conditionnement" && $conditionnement >= $res->quantite && $res->unite_value == $weight_units) {
						return array($res->remise_percent, $res->type_price, $res->tva_tx);
					}
				}
			}
		}
		
		return array($remise_ligne_de_depart, 0);
	}
	
	
	static function getPrix(&$db, $idProd,$qty,$conditionnement,$weight_units,$subprice,$coef,$devise,$price_level=1,$fk_country=0, $TFk_categorie=array()){
	global $conf;
		
		//chargement des prix par conditionnement associé au produit (LISTE des tarifs pour le produit testé & TYPE_REMISE grâce à la jointure)
		$sql = "SELECT p.type_remise as type_remise, tc.type_price, tc.quantite as quantite, tc.unite as unite, tc.prix as prix, tc.unite_value as unite_value, tc.tva_tx as tva_tx, tc.remise_percent as remise_percent, pr.weight";
		$sql.= " FROM ".MAIN_DB_PREFIX."tarif_conditionnement as tc";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as p on p.fk_object = tc.fk_product
				 LEFT JOIN ".MAIN_DB_PREFIX."product pr ON p.fk_object=pr.rowid ";
		$sql.= " WHERE fk_product = ".$idProd." AND (tc.currency_code = '".$devise."' OR tc.currency_code IS NULL)";
		
		if($fk_country>0) {
			
			$sql.=" AND tc.fk_country IN (0, $fk_country)";
			
		}
		if(!empty($TFk_categorie)) {
			
			$sql.=" AND tc.fk_categorie_client IN (-1,0, ".implode(',', $TFk_categorie).")";

			
		}
		
		
		$sql.= " ORDER BY quantite DESC, tc.fk_country DESC, tc.fk_categorie_client DESC";
		
		$resql = $db->query($sql);
		
		
		if($resql->num_rows > 0) {
			while($res = $db->fetch_object($resql)) {
				
				if(strpos($res->type_price,'PRICE') !== false){
					
					if($res->type_remise == "qte" && $qty >= $res->quantite){
						//Ici on récupère le pourcentage correspondant et on arrête la boucle
						return TTarif::price_with_multiprix($res->prix, $price_level);
					} 
					else if($res->type_remise == "conditionnement" && $conditionnement >= $res->quantite &&  $res->unite_value == $weight_units) {
						return TTarif::price_with_multiprix($res->prix * ($conditionnement / (($res->weight != 0) ? $res->weight : 1 )), $price_level); // prise en compte unité produit et poid init produit
					}
				}
			}
		}
		
		
		
		
		return $subprice * $coef;

	}
	
	function price_with_multiprix($price, $price_level) {
		global $conf;
		if($conf->multiprixcascade->enabled) {
		/*
		 * Si multiprix cascade est présent, on ajoute le pourcentage de réduction défini directement dans le multiprix
		 */	
			
			$TNiveau  = unserialize($conf->global->MULTI_PRIX_CASCADE_LEVEL);
			
			if(isset($TNiveau[$price_level])) {
				
				$price = $price * ($TNiveau[$price_level] / 100);
				
			}
			
			
		}
		
		return $price;
	}
	
}

class TTarifCommandedet extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'commandedet');
		parent::add_champs('poids','type=entier;');
		parent::add_champs('tarif_poids','type=float;');
		
		parent::_init_vars();
		parent::start();
	}
}

class TTarifPropaldet extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'propaldet');
		parent::add_champs('poids','type=entier;');
		parent::add_champs('tarif_poids','type=float;');
		
		parent::_init_vars();
		parent::start();
	}
}

class TTarifFacturedet extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'facturedet');
		parent::add_champs('poids','type=entier;');
		parent::add_champs('tarif_poids','type=float;');
		
		parent::_init_vars();
		parent::start();
	}
}
