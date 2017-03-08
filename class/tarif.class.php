<?php

class TTarif extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'tarif_conditionnement');
		parent::add_champs('unite','type=chaine;');
		parent::add_champs('unite_value','type=entier;');
		parent::add_champs('price_base_type,type_price,currency_code','type=chaine;');
		parent::add_champs('prix,tva_tx,quantite,remise_percent','type=float;');
		parent::add_champs('fk_user_author,fk_product,fk_country,fk_categorie_client,fk_soc,fk_project','type=entier;index;');
		parent::add_champs('date_debut,date_fin','type=date;');
		
		parent::_init_vars();
		parent::start();
		
		//$this->fk_categorie_client = 0;
		
		$this->TType_price = array(
			'PERCENT'=>$langs->trans('PERCENT')
			,'PRICE'=>$langs->trans('PRICE')
			,'PERCENT/PRICE'=>$langs->trans('PERCENT/PRICE')
		);
	}
	
	static function getRemise(&$db, &$line,$qty,$conditionnement,$weight_units, $devise,$fk_country=0, $TFk_categorie=array(), $fk_soc = 0, $fk_project = 0, $type='CLIENT'){
		global $mysoc;
		
		if (!is_object($line)) $idProd = $line; // Ancien comportement, le paramètre est en fait l'id du produit
		else {
			$idProd = $line->fk_product;
			$class = get_class($line);
			if($class == 'PropaleLigne'){ $parent = new Propal($db); $parent->fetch($line->fk_propal); }
			else if($class == 'OrderLine'){ $parent = new Commande($db); $parent->fetch($line->fk_commande); }
			else if($class == 'FactureLigne'){ $parent = new Facture($db); $parent->fetch($line->fk_facture); }
			else if($class == 'CommandeFournisseurLigne'){ $parent = new CommandeFournisseur($db); $parent->fetch($line->fk_commande); }
			else if($class == 'SupplierInvoiceLine'){ $parent = new FactureFournisseur($db); $parent->fetch($line->fk_facture_fourn); }
		}
		
		if($type === 'CLIENT') $table = 'tarif_conditionnement';
		else $table = 'tarif_conditionnement_fournisseur';
		
		//chargement des prix par conditionnement associé au produit (LISTE des tarifs pour le produit testé & TYPE_REMISE grâce à la jointure !!!)
		$sql = "SELECT p.type_remise as type_remise, tc.quantite as quantite, tc.type_price, tc.unite as unite, tc.prix as prix, tc.unite_value as unite_value, tc.tva_tx as tva_tx, tc.remise_percent as remise_percent, tc.date_debut as date_debut, tc.date_fin as date_fin";
		$sql.= " FROM ".MAIN_DB_PREFIX.$table." as tc";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as p on p.fk_object = tc.fk_product";
		$sql.= " WHERE fk_product = ".$idProd." AND (tc.currency_code = '".$devise."' OR tc.currency_code IS NULL)";
		
		if($fk_country>0) $sql.=" AND tc.fk_country IN (-1,0, $fk_country)";
		if(!empty($TFk_categorie) && is_array($TFk_categorie)) $sql.=" AND tc.fk_categorie_client IN (-1,0, ".implode(',', $TFk_categorie).")";
        if($fk_soc>0) $sql.=" AND tc.fk_soc IN (-1,0, $fk_soc)";
        if($fk_project>0) $sql.=" AND tc.fk_project IN (-1,0, $fk_project)";
		
		$sql .= 'ORDER BY ';
		if($fk_country>0) $sql .= 'tc.fk_country DESC, ';
		$sql.= 'quantite DESC, tc.fk_country DESC, tc.fk_categorie_client DESC, tc.fk_soc DESC, tc.fk_project DESC';
		
		
		$resql = $db->query($sql);
//exit($sql);		
		if($db->num_rows($resql) > 0) {
			$pallier = 0;
			
			while($res = $db->fetch_object($resql)) {
				
				if ($res->date_debut !== '0000-00-00 00:00:00' && $res->date_debut !== '1000-01-01 00:00:00')
				{
					$date_deb_remise = $db->jdate($res->date_debut);
					
					if (is_object($line) && (!empty($line->date_start) || !empty($parent->date)) )
					{
						if (!empty($line->date_start) && $date_deb_remise > $line->date_start) continue;
						// Test si j'ai pas de date de saisie sur la ligne dans ce cas la je test la date du document
						elseif (empty($line->date_start) && !empty($parent->date) && $date_deb_remise > $parent->date) continue;
					}
					// Keep old behavior
					elseif ($date_deb_remise > strtotime(date('Y-m-d')))
					{
						continue;
					}	
				}
					
				if ($res->date_fin !== '0000-00-00 00:00:00' && $res->date_fin !== '1000-01-01 00:00:00')
				{
					$date_fin_remise = $db->jdate($res->date_fin);
					if (is_object($line) && (!empty($line->date_start) || !empty($parent->date)))
					{
						if (!empty($line->date_start) && $date_fin_remise <= $line->date_start) continue;
						// Test si j'ai pas de date de saisie sur la ligne dans ce cas la je test la date du document
						elseif (empty($line->date_start) && !empty($parent->date) && $date_fin_remise <= $parent->date) continue;
					}
					// Keep old behavior
					elseif ($date_fin_remise <= strtotime(date('Y-m-d')))
					{
						continue;
					}
				}
				
				if(method_exists($parent, 'fetch_thirdparty')) {
					$parent->fetch_thirdparty();
					$soc = $parent->thirdparty;
					$tva_tx = get_default_tva($mysoc, $soc, $idprod);
					//if(empty($tva_tx)) $tva_tx=$res->tva_tx; TODO, ici en fait on réucpère jamais la TVA définie sur le tarif du produit !
				}
				
				if(strpos($res->type_price,'PERCENT')!==false ){
					
					if($res->type_remise == "qte" && $qty >= $res->quantite){
						return array($res->remise_percent, $res->type_price, $tva_tx);
					} 
					else if($res->type_remise == "conditionnement" && $conditionnement >= $res->quantite && $res->unite_value == $weight_units) {
						return array($res->remise_percent, $res->type_price, $tva_tx);
					}
				}
			}
			
			return array(0,'PRICE',0);
		}
		
		return array(false,false,false); // On ne fait pas de modification sur la ligne
	}
	
	
	static function getPrix(&$db, &$line,$qty,$conditionnement,$weight_units,$subprice,$coef,$devise,$price_level=1,$fk_country=0, $TFk_categorie=array(), $fk_soc = 0, $fk_project = 0, $type='CLIENT'){
	global $conf,$mysoc;
		
		if (!is_object($line)) $idProd = $line; // Ancien comportement, le paramètre est en fait l'id du produit
		else {
			$idProd = $line->fk_product;
			$class = get_class($line);
			if($class == 'PropaleLigne'){ $parent = new Propal($db); $parent->fetch($line->fk_propal); }
			else if($class == 'OrderLine'){ $parent = new Commande($db); $parent->fetch($line->fk_commande); }
			else if($class == 'FactureLigne'){ $parent = new Facture($db); $parent->fetch($line->fk_facture); }
			else if($class == 'CommandeFournisseurLigne'){ $parent = new CommandeFournisseur($db); $parent->fetch($line->fk_commande); }
			else if($class == 'SupplierInvoiceLine'){ $parent = new FactureFournisseur($db); $parent->fetch($line->fk_facture_fourn); }
		}
		
		if($type === 'CLIENT') $table = 'tarif_conditionnement';
		else $table = 'tarif_conditionnement_fournisseur';
		
		//chargement des prix par conditionnement associé au produit (LISTE des tarifs pour le produit testé & TYPE_REMISE grâce à la jointure)
		$sql = "SELECT p.type_remise as type_remise, tc.type_price, tc.quantite as quantite, tc.unite as unite, tc.prix as prix, tc.unite_value as unite_value, tc.tva_tx as tva_tx, tc.remise_percent as remise_percent, tc.date_debut as date_debut, tc.date_fin as date_fin, pr.weight";
		$sql.= " FROM ".MAIN_DB_PREFIX.$table." as tc";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as p on p.fk_object = tc.fk_product
				 LEFT JOIN ".MAIN_DB_PREFIX."product pr ON p.fk_object=pr.rowid ";
		$sql.= " WHERE fk_product = ".$idProd." AND (tc.currency_code = '".$devise."' OR tc.currency_code IS NULL)";
		
		if($fk_country>0) $sql.=" AND tc.fk_country IN (-1,0, $fk_country)";
		if(!empty($TFk_categorie) && is_array($TFk_categorie)) $sql.=" AND tc.fk_categorie_client IN (-1,0, ".implode(',', $TFk_categorie).")";
		if($fk_soc>0) $sql.=" AND tc.fk_soc IN (-1,0, $fk_soc)";
        if($fk_project>0) $sql.=" AND tc.fk_project IN (-1,0, $fk_project)";
		
		$sql .= 'ORDER BY ';
		if($fk_country>0) $sql .= 'tc.fk_country DESC, ';
		$sql.= 'quantite DESC, tc.fk_country DESC, tc.fk_categorie_client DESC, tc.fk_soc DESC, tc.fk_project DESC';
		
		$resql = $db->query($sql);
		//print ($sql);exit;
		if($db->num_rows($resql) > 0) {
			while($res = $db->fetch_object($resql)) {
					
				if ($res->date_debut !== '0000-00-00 00:00:00' && $res->date_debut !== '1000-01-01 00:00:00')
				{
					$date_deb_remise = $db->jdate($res->date_debut);
					
					if (is_object($line) && (!empty($line->date_start) || !empty($parent->date)) )
					{
						if (!empty($line->date_start) && $date_deb_remise > $line->date_start) continue;
						// Test si j'ai pas de date de saisie sur la ligne dans ce cas la je test la date du document
						elseif (empty($line->date_start) && !empty($parent->date) && $date_deb_remise > $parent->date) continue;
					}
					// Keep old behavior
					elseif ($date_deb_remise > strtotime(date('Y-m-d')))
					{
						continue;
					}	
				}
					
				if ($res->date_fin !== '0000-00-00 00:00:00' && $res->date_fin !== '1000-01-01 00:00:00')
				{
					$date_fin_remise = $db->jdate($res->date_fin);
					if (is_object($line) && (!empty($line->date_start) || !empty($parent->date)))
					{
						if (!empty($line->date_start) && $date_fin_remise <= $line->date_start) continue;
						// Test si j'ai pas de date de saisie sur la ligne dans ce cas la je test la date du document
						elseif (empty($line->date_start) && !empty($parent->date) && $date_fin_remise <= $parent->date) continue;
					}
					// Keep old behavior
					elseif ($date_fin_remise <= strtotime(date('Y-m-d')))
					{
						continue;
					}
				}

				if(method_exists($parent, 'fetch_thirdparty')) {
					$parent->fetch_thirdparty();
					$soc = $parent->thirdparty;
					$tva_tx = get_default_tva($mysoc, $soc, $idprod);
					//if(empty($tva_tx)) $tva_tx=$res->tva_tx; TODO, ici en fait on réucpère jamais la TVA définie sur le tarif du produit !
				}
				
				if(strpos($res->type_price,'PRICE') !== false){
					
					if(($res->type_remise == "qte" || $res->type_remise == 0) && $qty >= $res->quantite){
						//Ici on récupère le pourcentage correspondant et on arrête la boucle
						return array(TTarif::price_with_multiprix($res->prix, $price_level), $tva_tx);
					} 
					else if($res->type_remise == "conditionnement" && $conditionnement >= $res->quantite &&  $res->unite_value == $weight_units) {
						return array(TTarif::price_with_multiprix($res->prix * ($conditionnement / (($res->weight != 0) ? $res->weight : 1 )), $price_level), $tva_tx); // prise en compte unité produit et poid init produit
					}
				}
			}
		}
		
		
		
		
		//return $subprice * $coef;
		return array(false, false);

	}
	
	static function getCategTiers($socid) {
		global $db;
		
		// On récupère les catégories dont le client fait partie
		dol_include_once("/categories/class/categorie.class.php");
		
		$categ = new Categorie($db);
		$TFk_categorie = array();

		$Tab = $categ->containing($socid, 2);
		if(!empty($Tab) && is_array($Tab) ) {
			foreach($Tab as $cat) $TFk_categorie[] = $cat->id;
		}
		return $TFk_categorie;
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
	
	function save(&$ATMdb) {
		global $conf;
		
		if(empty($this->currency_code)) $this->currency_code = $conf->currency; 
		
		parent::save($ATMdb);
	}
	
}

class TTarifFournisseur extends TTarif{
	
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'tarif_conditionnement_fournisseur');
		parent::add_champs('unite','type=chaine;');
		parent::add_champs('unite_value','type=entier;');
		parent::add_champs('price_base_type,type_price,currency_code','type=chaine;');
		parent::add_champs('prix,tva_tx,quantite,remise_percent','type=float;');
		parent::add_champs('fk_user_author,fk_product,fk_country,fk_categorie_client,fk_soc,fk_project','type=entier;index;');
		parent::add_champs('date_debut,date_fin','type=date;');
		
		parent::_init_vars();
		parent::start();
		
		//$this->fk_categorie_client = 0;
		
		$this->TType_price = array(
			'PERCENT'=>$langs->trans('PERCENT')
			,'PRICE'=>$langs->trans('PRICE')
			,'PERCENT/PRICE'=>$langs->trans('PERCENT/PRICE')
		);
	}
	
	static function getRemise(&$db, &$line,$qty,$conditionnement,$weight_units, $devise,$fk_country=0, $TFk_categorie=array(), $fk_soc = 0, $fk_project = 0, $type='CLIENT'){
		return parent::getRemise($db, $line, $qty, $conditionnement, $weight_units, $devise, $fk_country, $TFk_categorie, $fk_soc, $fk_project, 'FOURNISSEUR');
	}
	
	static function getPrix(&$db, &$line,$qty,$conditionnement,$weight_units,$subprice,$coef,$devise,$price_level=1,$fk_country=0, $TFk_categorie=array(), $fk_soc = 0, $fk_project = 0, $type='CLIENT'){
		return parent::getPrix($db, $line, $qty, $conditionnement, $weight_units, $subprice, $coef, $devise, $price_level, $fk_country, $TFk_categorie, $fk_soc, $fk_project, 'FOURNISSEUR');
	}
	
}

class TTarifCommandedet extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'commandedet');
		parent::add_champs('poids','type=entier;');
		parent::add_champs('tarif_poids','type=float;');
		parent::add_champs('metre');
		
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
		parent::add_champs('metre');
		
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
		parent::add_champs('metre');
		
		parent::_init_vars();
		parent::start();
	}
}

class TTarifCommandeFourndet extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'commande_fournisseurdet');
		parent::add_champs('poids,fk_tarif_fournisseur,nb_colis','type=entier;');
		parent::add_champs('tarif_poids','type=float;');
		parent::add_champs('metre');
		
		parent::_init_vars();
		parent::start();
	}
}

class TTarifFactureFourndet extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'facture_fourn_det');
		parent::add_champs('poids,fk_tarif_fournisseur,nb_colis','type=entier;');
		parent::add_champs('tarif_poids','type=float;');
		parent::add_champs('metre');
		
		parent::_init_vars();
		parent::start();
	}
}
