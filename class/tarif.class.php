<?php

class TTarif extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'tarif_conditionnement');
		parent::add_champs('unite','type=chaine;');
		parent::add_champs('unite_value','type=entier;');
		parent::add_champs('price_base_type,type_price,currency_code','type=chaine;');
		parent::add_champs('prix,tva_tx,quantite,remise_percent,poids_unite','type=float;');
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
	
	function save(&$PDOdb, $save_linked_tarif=true, $log_tarif=true) {
		global $conf;
		
		if(empty($this->currency_code)) $this->currency_code = $conf->currency;
		// Avant le save sinon on ne peut plus récupérer l'ancien tarif
		if(in_array(get_class($this), array('TTarif', 'TTarifFournisseur'))) TTarifTools::logTarif($PDOdb, $this, $log_tarif,$this->date_debut);
		
		parent::save($PDOdb);

		// Enregistrement tarif linked uniquement si c'est un objet TTarif ou TTarifFournisseur
		if(in_array(get_class($this), array('TTarif', 'TTarifFournisseur'))) TTarifTools::saveTarifLinked($PDOdb, $this, $save_linked_tarif, $log_tarif);
		
	}
	
	function delete(&$PDOdb, $delete_linked_tarif=true) {
		
		parent::delete($PDOdb);
		
		// Suppression du tarif linked uniquement si c'est un objet TTarif ou TTarifFournisseur
		if(in_array(get_class($this), array('TTarif', 'TTarifFournisseur'))) TTarifTools::deleteTarifLinked($PDOdb, $this, $delete_linked_tarif);
		
	}
	
}

class TTarifFournisseur extends TTarif{
	
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'tarif_conditionnement_fournisseur');
		parent::add_champs('unite','type=chaine;');
		parent::add_champs('unite_value','type=entier;');
		parent::add_champs('price_base_type,type_price,currency_code','type=chaine;');
		parent::add_champs('prix,tva_tx,quantite,remise_percent,poids_unite','type=float;');
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

	function save(&$PDOdb, $save_linked_tarif=true, $log_tarif=true) {
		parent::save($PDOdb, $save_linked_tarif, $log_tarif);
	}

	function delete(&$PDOdb, $delete_linked_tarif=true) {
		parent::delete($PDOdb, $delete_linked_tarif);
	}
	
}

class TTarifLog extends TTarif{
	
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'tarif_conditionnement_log');
		parent::add_champs('unite','type=chaine;');
		parent::add_champs('unite_value','type=entier;');
		parent::add_champs('price_base_type,type_price,currency_code','type=chaine;');
		parent::add_champs('prix,tva_tx,quantite,remise_percent,poids_unite','type=float;');
		parent::add_champs('fk_user_author,fk_product,fk_country,fk_categorie_client,fk_soc,fk_project','type=entier;index;');
		parent::add_champs('date_debut,date_fin','type=date;');
		
		parent::_init_vars();
		parent::start();

	}
	
}

class TTarifFournisseurLog extends TTarif{
	
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'tarif_conditionnement_fournisseur_log');
		parent::add_champs('unite','type=chaine;');
		parent::add_champs('unite_value','type=entier;');
		parent::add_champs('price_base_type,type_price,currency_code','type=chaine;');
		parent::add_champs('prix,tva_tx,quantite,remise_percent,poids_unite','type=float;');
		parent::add_champs('fk_user_author,fk_product,fk_country,fk_categorie_client,fk_soc,fk_project','type=entier;index;');
		parent::add_champs('date_debut,date_fin','type=date;');
		
		parent::_init_vars();
		parent::start();

	}
	
}

class TTarifCommandedet extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'commandedet');
		parent::add_champs('poids,fk_tarif,nb_colis','type=entier;');
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
		parent::add_champs('poids,fk_tarif,nb_colis','type=entier;');
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
		parent::add_champs('poids,fk_tarif,nb_colis','type=entier;');
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
		parent::add_champs('poids,fk_tarif,nb_colis','type=entier;');
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
		parent::add_champs('poids,fk_tarif,nb_colis','type=entier;');
		parent::add_champs('tarif_poids','type=float;');
		parent::add_champs('metre');
		
		parent::_init_vars();
		parent::start();
	}
}

class TTarifSupplierProposaldet extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'supplier_proposaldet');
		parent::add_champs('poids,fk_tarif,nb_colis,pppp_id','type=entier;');
		parent::add_champs('tarif_poids','type=float;');
		parent::add_champs('metre');
		
		parent::_init_vars();
		parent::start();
	}
	
}

class TTarifTools {
	
	static function addline(&$object, &$tarif, $remise, $fk_product, $nb_colis, $desc, $fk_unit, $notrigger=1, $pa_ht='', $array_options=0, $pppp_id=0) {
		
		global $conf;
		
		if(!empty($fk_product) && $nb_colis > 0 && $tarif->rowid >0) {
			
			$old_triggers = $conf->modules_parts['triggers'];
			$conf->modules_parts['triggers'] = array(); // Nécessité de vider les triggers car on ne peut pas indiquer de no trigger dans le addline et updateline facture client (les triggers sont qd même conservés pour être réappliqués après les addline)
			
			if(get_class($object) === 'FactureFournisseur') $res = $object->addline($desc, $tarif->prix, $tarif->tva_tx, $txlocaltax1, $txlocaltax2, $nb_colis*$tarif->quantite, $fk_product, $remise, '', '', 0, '', 'HT', 0, -1, $notrigger, 0, $fk_unit);
			elseif(get_class($object) === 'CommandeFournisseur') {
				// Spécificité côté commandes fournisseur pour ne pas recalculer le tarif fourn
				$conf->global->SUPPLIERORDER_WITH_NOPRICEDEFINED=1;
				$res = $object->addline($desc, $tarif->prix, $nb_colis*$tarif->quantite, $tarif->tva_tx, $txlocaltax1, $txlocaltax2, $fk_product, 0, '', $remise, 'HT', 0, 0, 0, $notrigger, null, null, $array_options, $fk_unit);
			} elseif(get_class($object) === 'SupplierProposal') {
				$res = $object->addline($desc, $tarif->prix, $nb_colis*$tarif->quantite, $tarif->tva_tx, $txlocaltax1, $txlocaltax2, $fk_product, $remise, 'HT', 0, 0, 0, -1, 0, 0, 0, $pa_ht, '', $array_options, '', $fk_unit);
			} elseif(get_class($object) === 'Facture') {
				$res = $object->addline($desc, $tarif->prix, $nb_colis*$tarif->quantite, $tarif->tva_tx, 0, 0, $fk_product, $remise, '', '', 0, 0, '', 'HT', 0, 0, -1, 0, '', 0, 0, null, $pa_ht, '', 0, 100, '', $fk_unit);
			} elseif(get_class($object) === 'Propal') {
				$res = $object->addline($desc, $tarif->prix, $nb_colis*$tarif->quantite, $tarif->tva_tx, 0, 0, $fk_product, $remise, 'HT', 0, 0, 0, -1, 0, 0, 0, $pa_ht, '', '', '', $array_options, $fk_unit);
			} elseif(get_class($object) === 'Commande') {
				$res = $object->addline($desc, $tarif->prix, $nb_colis*$tarif->quantite, $tarif->tva_tx, 0, 0, $fk_product, $remise, 0, 0, 'HT', 0, '', '', 0, -1, 0, 0, null, $pa_ht, '', $array_options, $fk_unit);
			}
			
			self::updateNBColisAndTarif($object, $nb_colis, $tarif->rowid, $res, $pppp_id);
			
			$conf->modules_parts['triggers'] = $old_triggers;
			
			return $res;
			
		} else setEventMessage('Donnée manquante pour ajout de ligne (hook module tarif)', 'warnings');
		
	}
	
	static function updateline(&$object, &$tarif, $remise, $fk_product, $nb_colis, $desc, $fk_unit, $notrigger=1, $lineid, $pa_ht='', $array_options=0) {
		
		global $conf;
		
		$old_triggers = $conf->modules_parts['triggers'];
		$conf->modules_parts['triggers'] = array(); // Nécessité de vider les triggers car on ne peut pas indiquer de no trigger dans le addline et updateline facture client (les triggers sont qd même conservés pour être réappliqués après les addline)
		
		if(get_class($object) === 'FactureFournisseur') $res = $object->updateline($lineid, $desc, $tarif->prix, $tarif->tva_tx, 0, 0, $nb_colis*$tarif->quantite, $fk_product, 'HT', 0, 0, $remise, $notrigger, '', '', 0, $fk_unit);
		elseif(get_class($object) === 'CommandeFournisseur') $res = $object->updateline($lineid, $desc, $tarif->prix, $nb_colis*$tarif->quantite, $remise, $tarif->tva_tx, 0, 0, 'HT', 0, 0, $notrigger, '', '', $array_options, $fk_unit);
		elseif(get_class($object) === 'SupplierProposal') $res = $object->updateline($lineid, $tarif->prix, $nb_colis*$tarif->quantite, $remise, $tarif->tva_tx, 0, 0, $desc, 'HT', 0, 0, 0, 0, 0, $pa_ht, '', 0, $array_options, '', $fk_unit);
		elseif(get_class($object) === 'Facture') $res = $object->updateline($lineid, $desc, $tarif->prix, $nb_colis*$tarif->quantite, $remise, '', '', $tarif->tva_tx, 0, 0, 'HT', 0, 0, 0, 0, null, $pa_ht, '', 0, 0, 0, $fk_unit);
		elseif(get_class($object) === 'Commande') $res = $object->updateline($lineid, $desc, $tarif->prix, $nb_colis*$tarif->quantite, $remise, $tarif->tva_tx, 0, 0, 'HT', 0, '', '', 0, 0, 0, null, $pa_ht, '', 0, 0, $fk_unit);
		elseif(get_class($object) === 'Propal') $res = $object->updateline($lineid, $tarif->prix, $nb_colis*$tarif->quantite, $remise, $tarif->tva_tx, 0, 0, $desc, 'HT', 0, 0, 0, 0, 0, $pa_ht, '', 0, '', '', $array_options, $fk_unit);
		if($res<0) {
			var_dump($res,get_class($object),$lineid, $object->error,$fk_unit);
		}
		
		if($lineid > 0) $res = $lineid;
		
		self::updateNBColisAndTarif($object, $nb_colis, $tarif->rowid, $lineid);
		
		$conf->modules_parts['triggers'] = $old_triggers;
		
		return $res;
		
	}

	// Enregistrement du nb colis et fk_tarif_fourn utilisés pour préselection lors de la modification de la ligne
	static function updateNBColisAndTarif(&$object, $nb_colis, $fk_tarif, $lineid, $pppp_id=0) {
		
		global $db;
		
		if(empty($nb_colis) || empty($fk_tarif) || empty($lineid)) return -1;
		
		if(get_class($object) === 'FactureFournisseur' || get_class($object) === 'SupplierInvoiceLine') {$tabledet = MAIN_DB_PREFIX.'facture_fourn_det';}
		elseif(get_class($object) === 'CommandeFournisseur' || get_class($object) === 'CommandeFournisseurLigne') {$tabledet = MAIN_DB_PREFIX.'commande_fournisseurdet';}
		elseif(get_class($object) === 'SupplierProposal') {$tabledet = MAIN_DB_PREFIX.'supplier_proposaldet';}
		elseif(get_class($object) === 'Facture' || get_class($object) === 'FactureLigne') {$tabledet = MAIN_DB_PREFIX.'facturedet';}
		elseif(get_class($object) === 'Commande' || get_class($object) === 'OrderLine') {$tabledet = MAIN_DB_PREFIX.'commandedet';}
		elseif(get_class($object) === 'Propal') {$tabledet = MAIN_DB_PREFIX.'propaldet';}
		
		$sql = 'UPDATE '.$tabledet.' SET nb_colis = '.$nb_colis;
		$sql.= ', fk_tarif = '.$fk_tarif;
		if($pppp_id > 0) $sql.= ', pppp_id = '.$pppp_id;
		$sql.= ' WHERE rowid = '.$lineid;
		$db->query($sql);
		
	}
	
	static function linkTarif($origin_id, $target_id) {
		
		global $db;
		
	    $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'element_element (fk_source, sourcetype, fk_target, targettype)
	    		VALUES ('.$origin_id.', "TTarif", '.$target_id.', "TTarifFournisseur")';
		
		$db->query($sql);
		
	}
	
	static function getIdLinkedTarif($type_tarif, $id) {
		
		global $db;
		
		if($type_tarif === 'TTarif') {
			
			$field_search = 'fk_source';
			$field_where = 'fk_target';
			
		} elseif($type_tarif === 'TTarifFournisseur') {
			
			$field_search = 'fk_target';
			$field_where = 'fk_source';
			
		}
		
	    $sql = 'SELECT rowid, '.$field_search.'
	    		FROM '.MAIN_DB_PREFIX.'element_element
	    		WHERE sourcetype="TTarif"
	    		AND targettype="TTarifFournisseur"
	    		AND '.$field_where.' = '.$id;
		
		$resql = $db->query($sql);
		$res = $db->fetch_object($resql);
		
		return array('id_tarif'=>$res->{$field_search}, 'rowid'=>$res->rowid);
		
	}

	static function saveTarifLinked(&$PDOdb, &$TTarif, $save_linked_tarif=false, $log_tarif=false) {
		
		global $conf;
		
		if(!empty($conf->global->TARIF_PERCENT_AUTO_CREATE) && $save_linked_tarif) {
			
			if(get_class($TTarif) === 'TTarif') $class_tarif_linked = 'TTarifFournisseur';
			else $class_tarif_linked = 'TTarif';
			
			$TTarifLinked = new $class_tarif_linked;
			$TRes = TTarifTools::getIdLinkedTarif($class_tarif_linked, $TTarif->rowid);
			$id_tarif_linked = $TRes['id_tarif'];
			$id_lien = $TRes['rowid'];
			
			if(!empty($id_tarif_linked)) $TTarifLinked->load($PDOdb, $id_tarif_linked); // Si existant, on charge pour MAJ

			foreach($TTarif as $k=>$v) {
				
				if($k=='prix') {
					if(get_class($TTarif) === 'TTarif') {
						$newprice=$v*(1-($conf->global->TARIF_PERCENT_AUTO_CREATE/100));
						$TTarifLinked->{$k}=floor($newprice*100)/100;
					}
					else $TTarifLinked->{$k}=$v/(1-($conf->global->TARIF_PERCENT_AUTO_CREATE/100));
				}
				else if($k == 'table' || $k == 'rowid') continue;
				else $TTarifLinked->{$k}=$v;
				
			}
			
			$TTarifLinked->save($PDOdb, false, $log_tarif);
			
			if(empty($id_lien)) {
				if(get_class($TTarif) === 'TTarif') TTarifTools::linkTarif($TTarif->rowid, $TTarifLinked->rowid);
				else TTarifTools::linkTarif($TTarifLinked->rowid, $TTarif->rowid);
			}
		}
		
	}

	static function deleteTarifLinked(&$PDOdb, &$TTarif, $delete_linked_tarif=false) {
		
		global $db, $conf;
		
		if($delete_linked_tarif) {
			
			if(get_class($TTarif) === 'TTarif') $class_tarif_linked = 'TTarifFournisseur';
			else $class_tarif_linked = 'TTarif';
			
			$TTarifLinked = new $class_tarif_linked;
			$TRes = TTarifTools::getIdLinkedTarif($class_tarif_linked, $TTarif->rowid);
			$id_tarif_linked = $TRes['id_tarif'];
			$id_lien = $TRes['rowid'];
			
			if(!empty($id_tarif_linked)) {
				$TTarifLinked->load($PDOdb, $id_tarif_linked);
				$TTarifLinked->delete($PDOdb, false);
				$db->query('DELETE FROM '.MAIN_DB_PREFIX.'element_element WHERE rowid = '.$id_lien);
			}
			
		}
		
	}
	
	static function get_line_field($line_id, $field, $table='supplier_proposaldet', $id_field='rowid') {
		
		global $db;
		$sql = 'SELECT '.$field.'
				FROM '.MAIN_DB_PREFIX.$table.'
				WHERE '.$id_field.' = '.(int)$line_id;
		
		$resql = $db->query($sql);
		
		if(!$resql) {
			echo $sql.' '.$db->error;
		}
		
		$res = $db->fetch_object($resql);
		
		return $res->{$field};
		
	}
	
	static function logTarif(&$PDOdb, &$TTarif, $log_tarif=false,$date_fin='') {
		
		global $db, $conf;
		
		if($log_tarif && !empty($conf->global->TARIF_LOG_TARIF_UPDATE)) {
			
			if(get_class($TTarif) === 'TTarif') $class_tarif = 'TTarifLog';
			else $class_tarif = 'TTarifFournisseurLog';

			// Si changement de prix et conf activée, on log l'ancien tarif
			$old_class = get_class($TTarif);
			$old_tarif = new $old_class;
			$old_tarif->load($PDOdb, $TTarif->rowid);

			//var_dump(round($old_tarif->prix, 2), round($TTarif->prix, 2), round($old_tarif->prix, 2) != round($TTarif->prix, 2));
			if(!empty($old_tarif->rowid)&& round($old_tarif->prix, 2) != round($TTarif->prix, 2)) {
			
				$TTarifLog = new $class_tarif;
				$TTarifLog->prix = $old_tarif->prix;
				$TTarifLog->poids_unite = $old_tarif->poids_unite;
				foreach($TTarif as $k=>$v) {
					if($k != 'table' && $k != 'rowid' && $k != 'prix' && $k != 'poids_unite') $TTarifLog->{$k} = $v;
				}
				$TTarifLog->date_debut = $old_tarif->date_debut;
				
				
				if(empty($date_fin)) $date_fin =  strtotime(date('Y-m-d'));
				$TTarifLog->date_fin = $date_fin;
				$TTarifLog->save($PDOdb, false, false);
			
			}
		}
		
	}

}
