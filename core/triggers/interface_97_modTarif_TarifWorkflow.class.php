<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/core/triggers/interface_90_all_Demo.class.php
 *  \ingroup    core
 *  \brief      Fichier de demo de personalisation des actions du workflow
 *  \remarks    Son propre fichier d'actions peut etre cree par recopie de celui-ci:
 *              - Le nom du fichier doit etre: interface_99_modMymodule_Mytrigger.class.php
 *				                           ou: interface_99_all_Mytrigger.class.php
 *              - Le fichier doit rester stocke dans core/triggers
 *              - Le nom de la classe doit etre InterfaceMytrigger
 *              - Le nom de la methode constructeur doit etre InterfaceMytrigger
 *              - Le nom de la propriete name doit etre Mytrigger
 */


/**
 *  Class of triggers for Mantis module
 */
 
class InterfaceTarifWorkflow
{
    var $db;
    
    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    
        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "ATM";
        $this->description = "Trigger du module de tarif par conditionnement";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'technic';
    }
    
    
    /**
     *   Return name of trigger file
     *
     *   @return     string      Name of trigger file
     */
    function getName()
    {
        return $this->name;
    }
    
    /**
     *   Return description of trigger file
     *
     *   @return     string      Description of trigger file
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *
     *   @return     string      Version of trigger file
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("Development");
        elseif ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }
	
	

	function _updateLineProduct(&$object,&$user,$idProd,$conditionnement,$weight_units,$remise, $prix ,$prix_devise,$tvatx){
		
		global $conf;
		
		if(!defined('INC_FROM_DOLIBARR'))define('INC_FROM_DOLIBARR',true);
		dol_include_once('/tarif/config.php');
		//print $prix.'<br />';
		$product = new Product($this->db);
		$product->fetch($idProd);
		
		$object_parent = $this->_getObjectParent($object);
		
		$conditionnement = $conditionnement * pow(10, ($weight_units - $product->weight_units ));
		
		//echo $product->price; exit;
		$object->remise_percent = $remise;
		
		$object->subprice = $prix ;
		if(empty($conf->global->TARIF_DONT_USE_TVATX)) $object->tva_tx = $tvatx;
		
		$object->price = $object->subprice; // TODO qu'est-ce ? Due à un deprecated incertain, dans certains cas price est utilisé et dans d'autres c'est subprice
		//echo $object->subprice; exit;
		
 		if(get_class($object_parent) == "Facture" && $object_parent->type == 2){ // facture d'avoir
 			$object->remise_percent = -$object->remise_percent;
			$object->subprice = -$object->subprice;
			
			$object->price = $object->subprice;
		}
		//print $object->subprice; exit;
		
		if(get_class($object) == 'FactureLigne') $object->update($user, true);
		else $object->update(true);
		
		//Cas multidevise
		if($conf->multidevise->enabled){
			
			if(get_class($object) == "OrderLine"){
				$tabledet = "commandedet";
				//$object->update(true);
			}
			elseif(get_class($object) == 'PropaleLigne'){
				$tabledet = "propaldet";
				//$object->update(true);
			}
			elseif(get_class($object) == 'FactureLigne'){
				$tabledet = "facturedet";
			}
			
			//pre($object,true);
			//exit('UPDATE '.MAIN_DB_PREFIX.$tabledet.' SET devise_pu = '.$prix_devise.', devise_mt_ligne = '.(($prix_devise * $object->qty) * ( 1 - ($object->remise_percent/100))).' WHERE rowid = '.$object->rowid);
			$this->db->query('UPDATE '.MAIN_DB_PREFIX.$tabledet.' SET devise_pu = '.(float)$prix_devise.', devise_mt_ligne = '.(($prix_devise * $object->qty) * ( 1 - ($object->remise_percent/100))).' WHERE rowid = '.$object->rowid);
			//exit("$prix $prix_devise");
		}
		//exit;
	}
	
	function _updateTotauxLine(&$object,$qty){
		//MAJ des totaux de la ligne
		$object->total_ht  = price2num($object->subprice * $qty * (1 - $object->remise_percent / 100), 'MT');
		$object->total_tva = price2num(($object->total_ht * (1 + ($object->tva_tx/100))) - $object->total_ht, 'MT');
		$object->total_ttc = price2num($object->total_ht + $object->total_tva, 'MT');
		$object->update_total();
	}
	
	function _getObjectParent(&$object){
		switch (get_class($object)) {
			case 'PropaleLigne':
				$object_parent = new Propal($this->db);
				$object_parent->fetch((!empty($object->fk_propal)) ? $object->fk_propal : $object->oldline->fk_propal);
				$object_parent->fetch_thirdparty();
				return $object_parent;
				break;
			case 'OrderLine':
				$object_parent = new Commande($this->db);
				$object_parent->fetch((!empty($object->fk_commande)) ? $object->fk_commande : $object->oldline->fk_commande);
				$object_parent->fetch_thirdparty();
				return $object_parent;
				break;
			case 'FactureLigne':
				$object_parent = new Facture($this->db);
				$object_parent->fetch((!empty($object->fk_facture)) ? $object->fk_facture : $object->oldline->fk_facture);
				$object_parent->fetch_thirdparty();
				return $object_parent;
				break;
			case 'CommandeFournisseurLigne':
				$object_parent = new CommandeFournisseur($this->db);
				$object_parent->fetch((!empty($object->fk_commande)) ? $object->fk_commande : $object->oldline->fk_commande);
				$object_parent->fetch_thirdparty();
				return $object_parent;
				break;
		}
		return $object_parent;
	}
	
	
	//Calcule le prix de la ligne de facture
	private function calcule_prix_facture(&$res,&$object){
		$poids_exedie = ($res->weight * pow(10, $res->weight_unit))* $res->price;
		$poids_commande = ($res->tarif_poids * pow(10, $res->poids)) * $object->qty;
		$prix = $poids_exedie / $poids_commande;
		return floatval($prix);
	}
	
	/**
	 * Cherche et retourne le premier prix différent de zéro dans le cas ou le multiprix est actif
	 * Dans l'ordre décroissant, si le prix courant est à 0
	 */
	private function _getFirstPriceDifferentDeZero(&$object) {
		
		global $db;

		if(stripos(get_class($object), "PropaleLigne") !== false) {
			$obj_parent = new Propal($db);
			$obj_parent->fetch($object->fk_propal);
		}
		if(stripos(get_class($object), "OrderLine") !== false){
			$obj_parent = new Commande($db); 
			$obj_parent->fetch($object->fk_commande);
		}
		if(stripos(get_class($object), "FactureLigne") !== false){
			$obj_parent = new Facture($db); 
			$obj_parent->fetch($object->fk_facture);
		}
		
		$prod = new product($db);
		$prod->fetch($object->fk_product);
		
		$soc = new Societe($db);
		$soc->fetch($obj_parent->socid);
		
		$trouve = false;
		
		$price_level = $soc->price_level;
		
		if(!empty($prod->multiprices)) {
			
			while($price_level > 0) {
				if($obj_parent->type == 2) $prod->multiprices[$price_level] *= -1;
				if($prod->multiprices[$price_level] != 0) {
					return array($prod->multiprices[$price_level], $prod->multiprices_tva_tx[$price_level]);
				}
				$price_level--;
			}
			
		}
		
		return $prod->price;

	}
	
    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
     *
     *      @param	string		$action		Event action code
     *      @param  Object		$object     Object
     *      @param  User		$user       Object user
     *      @param  Translate	$langs      Object langs
     *      @param  conf		$conf       Object conf
     *      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
     */
	function run_trigger($action,$object,$user,$langs,$conf)
	{
		if(!defined('INC_FROM_DOLIBARR'))define('INC_FROM_DOLIBARR',true);
		dol_include_once('/tarif/config.php');
		dol_include_once('/tarif/class/tarif.class.php');
		dol_include_once('/commande/class/commande.class.php');
		dol_include_once('/fourn/class/fournisseur.commande.class.php');
		dol_include_once('/compta/facture/class/facture.class.php');
		dol_include_once('/comm/propal/class/propal.class.php');
		dol_include_once('/dispatch/class/dispatchdetail.class.php');
		
		global $user, $db,$conf;

		//Création d'une ligne de facture, propale ou commande, ou commande fournisseur
		if (($action === 'LINEORDER_INSERT' || $action === 'LINEPROPAL_INSERT' || $action === 'LINEBILL_INSERT' || $action === 'LINEORDER_SUPPLIER_CREATE') 
			&& (!isset($_REQUEST['notrigger']) || $_REQUEST['notrigger'] != 1)
			&& (!empty($object->fk_product) || !empty($_REQUEST['idprodfournprice']))
			&& (!empty($_REQUEST['addline_predefined']) || !empty($_REQUEST['addline_libre'])  || !empty($_REQUEST['prod_entry_mode']))) {
			//print_r($object);
			$qtyline = $object->qty;
			
			//prendre le tarif par quantité correspondant à la sommes des quantités facturé pour ce produit au client
			if($conf->global->TARIF_TOTAL_QTY_ON_TOTAL_INVOICE_QTY){
				
				$element_parent = strtr($object->element,array('det'=>''));
				
				$sql = "SELECT SUM(fd.qty) as totalQty
						FROM ".MAIN_DB_PREFIX."facturedet as fd
							LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON (f.rowid = fd.fk_facture)
						WHERE f.fk_soc = (SELECT s.rowid 
										  FROM ".MAIN_DB_PREFIX."societe as s
										  	LEFT JOIN ".MAIN_DB_PREFIX.$element_parent." as ep ON (s.rowid = ep.fk_soc )
										  WHERE ep.rowid = ".$object->{"fk_".$element_parent}.")
							AND f.fk_statut > 0
							AND fd.fk_product = ".$object->fk_product;

				if($resql = $this->db->query($sql)){
					$res = $this->db->fetch_object($resql);
					$qtyline = $res->totalQty + $object->qty;
				}

			}
			
			if($action == 'LINEORDER_SUPPLIER_CREATE') { // Gestion commande fournisseur
				$tmpObject = $object;
				$object = new CommandeFournisseurLigne($db);
				$object->fetch($tmpObject->rowid);
			}
			
			$idProd = $object->fk_product;
			
			if($conf->declinaison->enabled) {
				$sql = "SELECT fk_parent FROM ".MAIN_DB_PREFIX."declinaison WHERE fk_declinaison = ".$idProd;

				$res = $this->db->query($sql);
				$resql = $this->db->fetch_object($res);
				$idParent = $resql->fk_parent;

				if(!empty($idParent)) {
					$idProd = $idParent;
				}
			}

			// Définition des tables. Attention pour commande fournisseur, l'objet commande est passé et non l'objet ligne
			if(get_class($object) == 'PropaleLigne'){ $table = "propal"; $tabledet = 'propaldet'; $parentfield = 'fk_propal';}
			else if(get_class($object) == 'OrderLine'){$table = "commande"; $tabledet = 'commandedet'; $parentfield = 'fk_commande';}
			else if(get_class($object) == 'FactureLigne'){ $table = "facture"; $tabledet = 'facturedet'; $parentfield = 'fk_facture';}
			else if(get_class($object) == 'CommandeFournisseurLigne'){ $table = "commande_fournisseur"; $tabledet = 'commande_fournisseurdet'; $parentfield = 'fk_commande';}
				
			//Gestion du poids et de l'unité transmise
			if(!empty($_REQUEST['poidsAff_product'])){ //Si un poids produit a été transmis
				$poids = ($_REQUEST['poidsAff_product'] > 0) ? $_REQUEST['poidsAff_product'] : 1;
			}
			elseif(!empty($_REQUEST['poidsAff_libre'])){ //Si un poids ligne libre a été transmis
				$poids = ($_REQUEST['poidsAff_libre'] > 0) ? $_REQUEST['poidsAff_libre'] : 1;
			}
			else{ //Aucun poids transmis = poids reçois 1
				$poids = 1;
			}
			
			if(isset($_REQUEST['weight_unitsAff_product'])){ //Si on a un unité produit transmise
				$weight_units = $_REQUEST['weight_unitsAff_product'];
			}
			else{ //Sinon on est sur un tarif à l'unité donc pas de gestion de poids => 69 chiffre pris au hasard
				$weight_units = 69;
			}

			if($idProd>0) {
				
				$product =new Product($db);
				$product->fetch($idProd);

				if($product->type==1 && empty($conf->global->TARIF_KEEP_FIELD_CONDITIONNEMENT_FOR_SERVICES))$poids=1;

			}
			
			//echo $poids." ".$weight_units;
			//pre($product,true);
			//exit;
			// Si on a un poids passé en $_POST alors on viens d'une facture, propale ou commande
			if($poids > 0 && $idProd > 0 && !isset($_REQUEST['origin'])){
				
				if($conf->multidevise->enabled){
				
					$sql = "SELECT devise_code as code, devise_taux as coef FROM ".MAIN_DB_PREFIX.$table." WHERE rowid = ".$object->{$parentfield}; //Récup devise du parent + taux de conv 
					
					$res = $db->query($sql);
					$resql = $db->fetch_object($res);
					
					$coef_conv = $resql->coef;
					$devise = $resql->code;
				}
				
				
				if(empty($coef_conv)){ //devise = a celle du système
				
					$coef_conv = 1;
					$devise = $conf->currency;
				}
				
				// Chargement de l'objet parent
				$object_parent = $this->_getObjectParent($object);
				$price_level = $object_parent->client->price_level;
				$fk_country = $object_parent->client->country_id;
				
				// On récupère les catégories dont le client fait partie
				$TFk_categorie = TTarif::getCategClient($object_parent->thirdparty->id); // $this->getCategClient($object_parent);

				$prix_devise = $remise = false;
				
				list($remise, $type_prix, $tvatx) = TTarif::getRemise($this->db,$idProd,$qtyline,$poids,$weight_units,$devise, $fk_country, $TFk_categorie, $object_parent->thirdparty->id, $object_parent->fk_project);
				if($type_prix == '') {
					$tvatx = $object->tva_tx;
				}
				$prix = __val($object->subprice,$object->price,'float',true);
				
				// La saisie d'une réduction manuellement prévaut sur la devise renseignée dans tarif
				if (empty($_REQUEST['remise_percent']) === false) {
					$remise = $_REQUEST['remise_percent'];
					$type_prix = 'PERCENT/PRICE';
				}
				
				if($remise !== false || $type_prix!='PERCENT') {
				
					if($remise == 0 || $type_prix == 'PERCENT/PRICE'){
						//exit('1');
						/*$object_parent = $this->_getObjectParent($object);
						$price_level = $object_parent->client->price_level;
						$fk_country = $object_parent->client->country_id;*/
						//echo $devise;exit;					
						$TRes = TTarif::getPrix($this->db,$idProd,$qtyline*$poids,$poids,$weight_units,$prix,$coef_conv,$devise,$price_level,$fk_country, $TFk_categorie, $object_parent->thirdparty->id, $object_parent->fk_project);
						if(is_array($TRes)) {
							$prix_devise = $TRes[0];
							$tvatx = $TRes[1];
						} else {
							$prix_devise = $TRes;
							$tvatx = $object->tva_tx;
						}
						
						$prix = $prix_devise / $coef_conv;
					}
					
					//var_dump( $TRes);exit;
					if($prix_devise !== false) {
						
						$this->_updateLineProduct($object,$user,$idProd,$poids,$weight_units,$remise,$prix,$prix_devise,$tvatx); //--- $poids = conditionnement !
						$this->_updateTotauxLine($object,$qtyline);

					} 
					
				}
				
				if($remise === false && $prix_devise ===false && $conf->global->TARIF_USE_PRICE_OF_PRECEDENT_LEVEL_IF_ZERO) {
					$TFirst_price_diff_zero = $this->_getFirstPriceDifferentDeZero($object);
					if(is_array($TFirst_price_diff_zero)){
						$object->price = $TFirst_price_diff_zero[0];
						$object->subprice = $TFirst_price_diff_zero[0];
						$object->total_ht = $TFirst_price_diff_zero[0] * $object->qty;
						$object->tva_tx = $TFirst_price_diff_zero[1];
						$object->update($user,1);
					}
				}
				
				$dolibarr_version = (float) DOL_VERSION;
				if($dolibarr_version < 3.8 && ($remise === false && $prix_devise === false && $conf->global->TARIF_ONLY_UPDATE_LINE_PRICE)) {
					$prix_devise = $object->subprice * $poids;
					$prix = $prix_devise;
					$tvatx = $object->tva_tx;
					$this->_updateLineProduct($object,$user,$idProd,$poids,$weight_units,$remise,$prix,$prix_devise,$tvatx); //--- $poids = conditionnement !
					$this->_updateTotauxLine($object,$qtyline);
				} 
				//MAJ du poids et de l'unité de la ligne
				$sql = "UPDATE ".MAIN_DB_PREFIX.$tabledet." SET tarif_poids = ".$poids.", poids = ".$weight_units." WHERE rowid = ".$object->rowid;
				$this->db->query($sql);

			} 
			
			// Sinon, Si l'object origine est renseigné et est soit une propale soit une commande
			// => filtre sur propale ou commande car confli éventuel avec le trigger sur expédition
			elseif(   ((!empty($object->origin) && !empty($object->origin_id)) 
					|| (!empty($_POST['origin']) && !empty($_POST['originid'])))
					&& ($_POST['origin'] == "propal" || $object->origin == "commande" || $object->origin == "shipping")){

				//Cas propal on charge la ligne correspondante car non passé dans le post
				if($_POST['origin'] == "propal"){
					
					if(isset($_POST['facnumber']))
						$table = "facturedet";
					else
						$table = "commandedet";
					
					$propal = new Propal($this->db);
					$propal->fetch($_POST['originid']);
					
					foreach($propal->lines as $line){
						if($line->rang == $object->rang)
							$originid = $line->rowid;
					}
					$sql = "SELECT tarif_poids as weight, 1 as qty, poids as weight_unit 
							FROM ".MAIN_DB_PREFIX."propaldet
							WHERE rowid = ".$originid;
	        	}
				//Cas commande la ligne d'origine est déjà chargé dans l'objet
				elseif($object->origin == "commande"){
					$table = "facturedet";
					$originid = $object->origin_id;
					//$sql = "SELECT tarif_poids as weight, 1 as qty, poids as weight_unit
					$sql = "SELECT tarif_poids as weight, qty, poids as weight_unit 
							FROM ".MAIN_DB_PREFIX."commandedet
							WHERE rowid = ".$originid;
				}
				
				elseif($object->origin === "shipping" && $conf->dispatch->enabled){
					
					//SI TU AS UNE ERREUR ICI C'EST QUE TU AS OUBLIE LE README DU MODULE TARIF
					$table = "facturedet";
					$originid = $object->origin_id;
					
					if(FACTURE_DISPATCH_ON_EXPEDITION && $conf->dispatch->enabled){
						$sql = "SELECT eda.weight as weight, eda.weight_unit as weight_unit, cd.price, cd.tarif_poids, cd.poids, ed.qty as qty";
					}
					else{
						$sql = "SELECT SUM(eda.weight) as weight, eda.weight_unit as weight_unit, cd.price, cd.tarif_poids, cd.poids, COUNT(eda.weight_unit) as qty";
					}
					
					$sql.= " FROM ".MAIN_DB_PREFIX."expeditiondet_asset eda
								LEFT JOIN ".MAIN_DB_PREFIX."expeditiondet as ed ON (ed.rowid = eda.fk_expeditiondet)
								LEFT JOIN ".MAIN_DB_PREFIX."commandedet as cd ON (cd.rowid = ed.fk_origin_line)
								LEFT JOIN ".MAIN_DB_PREFIX."product as p ON (p.rowid = cd.fk_product)
							WHERE eda.fk_expeditiondet = ".$originid."
							AND cd.fk_product = ".$object->fk_product;
							
					if(!FACTURE_DISPATCH_ON_EXPEDITION && $conf->dispatch->enabled){
						$sql.= " GROUP BY eda.weight_unit, cd.fk_product ";
					}
					
					$sql.= " ORDER BY eda.weight_unit ASC";
 				}
				elseif($object->origin === "shipping" && !$conf->dispatch->enabled){
					/* cas sans dispatch, on rappatrie le poids du produit */
					$table = "facturedet";
					$originid = GETPOST('originid');
					
					$sql = "SELECT cd.tarif_poids as weight, cd.poids as weight_unit, ed.qty as qty";
					
					$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd
								LEFT JOIN ".MAIN_DB_PREFIX."expeditiondet as ed ON (cd.rowid = ed.fk_origin_line)
								LEFT JOIN ".MAIN_DB_PREFIX."product as p ON (p.rowid = cd.fk_product)
							WHERE ed.fk_expedition = ".$originid."
							AND cd.fk_product = ".$object->fk_product;
							
					$resql = $this->db->query($sql);
					$res = $this->db->fetch_object($resql);
	
					$poids = $res->weight;
					$weight_units = $res->weight_unit;
					$object->qty = $res->qty;	
					
					$this->db->query("UPDATE ".MAIN_DB_PREFIX.$table." SET tarif_poids = ".$poids.", poids = ".$weight_units." WHERE rowid = ".$object->rowid);
					
					return 0;
				}
				else{
					return 0;
				}

				//echo $sql; exit;
				
				$resql = $this->db->query($sql);
				$res = $this->db->fetch_object($resql);

				$poids = $res->weight;
				$weight_units = $res->weight_unit;
				$object->qty = $res->qty;

				$this->db->query("UPDATE ".MAIN_DB_PREFIX.$table." SET tarif_poids = ".($poids / $object->qty).", poids = ".$weight_units." WHERE rowid = ".$object->rowid);

				if($object->origin == "shipping" && $conf->dispatch->enabled){
					$object->subprice = $this->calcule_prix_facture($res,$object);

					$object->update($user);
					$this->_updateTotauxLine($object,$object->qty);
					
					//Si plusieurs flacons avec des unités différentes ont été envoyé
					//on ajoute des lignes de facture suplémentaire
					while($res = $this->db->fetch_object($resql)){
						$newrowid = $object->insert(true);
						$poids = $res->weight;
						$weight_units = $res->weight_unit;
						$object->qty = $res->qty;

						$this->db->query("UPDATE ".MAIN_DB_PREFIX.$table." SET tarif_poids = ".($poids / $object->qty).", poids = ".$weight_units." WHERE rowid = ".$object->rowid);

						$object->subprice = $this->calcule_prix_facture($res,$object);
						$object->update($user);	
						$this->_updateTotauxLine($object,$object->qty);
					}
				}
			}
			//Ligne libre
			else{
				
				$this->db->query("UPDATE ".MAIN_DB_PREFIX.$tabledet." SET tarif_poids = ".$poids.", poids = ".$weight_units." WHERE rowid = ".$object->rowid);
			}
			
			dol_syslog("Trigger '".$this->name."' for actions '$action' launched by ".__FILE__.". id=".$object->rowid);
			
			if($action == 'LINEORDER_SUPPLIER_CREATE') {
				$object = $tmpObject;
			}
		}

		elseif(($action == 'LINEORDER_UPDATE' || $action == 'LINEPROPAL_UPDATE' || $action == 'LINEBILL_UPDATE'  || $action==='LINEORDER_SUPPLIER_UPDATE') 
				&& (!isset($_REQUEST['notrigger']) || $_REQUEST['notrigger'] != 1)) {
			
			$idProd = __val( $object->fk_product, $object->oldline->fk_product, 'integer');
			
			if($conf->declinaison->enabled) {
				$sql = "SELECT fk_parent FROM ".MAIN_DB_PREFIX."declinaison WHERE fk_declinaison = ".$idProd;
					
				$res = $this->db->query($sql);
				$resql = $this->db->fetch_object($res);
				$idParent = $resql->fk_parent;
				
				if(!empty($idParent)) {
					$idProd = $idParent;
				}
			}
			
			
			if(get_class($object) == 'PropaleLigne'){
				 $table = 'propal';
				 $tabledet = 'propaldet';
			}
			elseif(get_class($object) == 'OrderLine'){
				 $table = 'commande';
				 $tabledet = 'commandedet';
			}
			elseif(get_class($object) == 'FactureLigne'){
				 $table = 'facture';
				 $tabledet = 'facturedet';
			}
			elseif(get_class($object) == 'CommandeFournisseur'){
				$table = "commande_fournisseur"; 
				$tabledet = 'commande_fournisseurdet'; 
				$parentfield = 'fk_commande';
			}
			
			$idLine = __val($object->rowid, $object->id); 
			
			$sql = "SELECT tarif_poids, poids FROM ".MAIN_DB_PREFIX.$tabledet." WHERE rowid = ".$idLine;
			$resql = $this->db->query($sql);
			$res = $this->db->fetch_object($resql);
			
			$weight_units = __get('weight_units',0,'integer');
			$poids = __get('poids',1,'float');
			
			//echo floatval($res->tarif_poids * pow(10, $res->poids))." ".floatval($_POST['poids'] * pow(10, $_POST['weight_units']));exit;
			// Si on a un poids passé en $_POST alors on viens d'une facture, propale ou commande
			// ET si la quantité ou le poids a changé
			//exit($object->oldline->qty." != ".$object->qty." || ".floatval($res->tarif_poids * pow(10, $res->poids))." != ".floatval($poids * pow(10, $weight_units)));
			//echo $res->tarif_poids;
			//echo floatval($res->tarif_poids * pow(10, $res->poids)) != floatval($poids * pow(10, $weight_units));
			
			
			//Gestion du poids et de l'unité transmise
			
			if(!empty($_REQUEST['poidsAff_product'])){ //Si un poids produit a été transmis
				$poids = ($_REQUEST['poidsAff_product'] > 0) ? $_REQUEST['poidsAff_product'] : 1;
			}
			elseif(!empty($_REQUEST['poidsAff_libre'])){ //Si un poids ligne libre a été transmis
				$poids = ($_REQUEST['poidsAff_libre'] > 0) ? $_REQUEST['poidsAff_libre'] : 1;
			}
			else{ //Aucun poids transmis = poids reçois 1
				$poids = 1;
			}
			
			if(isset($_REQUEST['weight_unitsAff_product'])){ //Si on a un unité produit transmise
				$weight_units = $_REQUEST['weight_unitsAff_product'];
			}
			else{ //Sinon on est sur un tarif à l'unité donc pas de gestion de poids => 69 chiffre pris au hasard
				$weight_units = 69;
			}			
			
			
			if($object->oldline->qty != $object->qty || (floatval($res->tarif_poids * pow(10, $res->poids)) != floatval($poids * pow(10, $weight_units)) && !$conf->global->TARIF_DONT_ADD_UNIT_SELECT)){
				
				if(!empty($idProd)){
					if($conf->multidevise->enabled){
						$sql = "SELECT devise_code as code, devise_taux as coef FROM ".MAIN_DB_PREFIX.$table." WHERE rowid = ".__val($object->{"fk_".$table},$_REQUEST['id'],'integer'); //Récup devise du parent + taux de conv 
						
						$res = $db->query($sql);
						$resql = $db->fetch_object($res);
						
						$coef_conv = $resql->coef;
						$devise = $resql->code;
					}
					
					if(empty($coef_conv)){
						$coef_conv = 1;
						$devise = $conf->currency;
					}
					
					// Chargement de l'objet parent
					$object_parent = $this->_getObjectParent($object);
					$price_level = $object_parent->client->price_level;
					$fk_country = $object_parent->client->country_id;

					// On récupère les catégories dont le client fait partie
					if (!empty($object_parent->thirdparty->id))
						$TFk_categorie = TTarif::getCategClient($object_parent->thirdparty->id); 

					list($remise, $type_prix) = TTarif::getRemise($this->db,$idProd,$object->qty,$poids,$weight_units, $conf->currency,$fk_country, $TFk_categorie);
					$prix = __val($object->subprice,$object->price,'float',true);
					
					if($remise == 0 || $type_prix=='PERCENT/PRICE'){
						/*$object_parent = $this->_getObjectParent($object);
						$price_level = $object_parent->client->price_level;
						$fk_country = $object_parent->client->country_id;*/
		
						list($prix_devise, $tvatx) =TTarif::getPrix($this->db,$idProd,$object->qty*$poids,$poids,$weight_units,$prix,$coef_conv,$devise,$price_level,$fk_country, $TFk_categorie,$object_parent->thirdparty->id, $object_parent->fk_project);
						if($prix_devise !== false) @$prix = $prix_devise / $coef_conv;
					}
					
					if($tvatx === false) $tvatx = $object->tva_tx;
					
					$this->_updateLineProduct($object,$user,$idProd,$poids,$weight_units,$remise,$prix,$prix_devise,$tvatx); //--- $poids = conditionnement !
					$this->_updateTotauxLine($object,$object->qty);
					
				}
				
				$sql = "UPDATE ".MAIN_DB_PREFIX.$tabledet." SET tarif_poids = ".(float)price2num($poids).", poids = ".(int)$weight_units." WHERE rowid = ".$idLine;
				$this->db->query($sql);
				
			}
			
			dol_syslog("Trigger '".$this->name."' for actions '$action' launched by ".__FILE__.". id=".$object->rowid);

		}
		
		//MAJ des différents prix de la grille de tarif par conditionnement lors d'une modification du prix produit
		elseif(false && $action == 'PRODUCT_PRICE_MODIFY'){ //false => désactive
			
			$resql = $this->db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."tarif_conditionnement WHERE fk_product = ".$object->id);
			
			if($resql->num_rows > 0){
				$res = $this->db->fetch_object($resql);
				//MAJ des tarifs par conditionnement
				$this->db->query("UPDATE ".MAIN_DB_PREFIX."tarif_conditionnement 
								  SET tva_tx = ".$object->tva_tx.", price_base_type = '".$object->price_base_type."', prix = ".$object->price." 
								  WHERE fk_product = ".$object->id);
			}

			//MAJ du prix 2
			if(isset($_REQUEST['price_1'])){
				$level = 2;	
				$price = str_replace(',', '.', $_REQUEST['price_1']);
				$price = str_replace(' ', '', $price);
				$price = $price * (1 - 0.15);
				$price_ttc = $price * (1 + ($_REQUEST['tva_tx_1'] / 100));
				$base = $_REQUEST['multiprices_base_type_1'];
				$tva_tx = $_REQUEST['tva_tx_1'];
			}
			
			$now=dol_now();
			
			
			//seulement si produit
			if($object->type == 0){
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_price
					(price_level,date_price,fk_product,fk_user_author,price,price_ttc,price_base_type,tosell,tva_tx,recuperableonly,localtax1_tx, localtax2_tx, price_min,price_min_ttc,price_by_qty,entity) 
					VALUES
					(".$level.",'".$this->db->idate($now)."',".$object->id.",".$user->id.",".$price.",".$price_ttc.",'".$base."',".$object->status.",".$tva_tx.",".$object->tva_npr.",".$object->localtax1_tx."
					,".$object->localtax2_tx.",".$object->price_min.",".$object->price_min_ttc.",0
					,".(! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode) ? 1 : $conf->entity).")";
				
				$this->db->query($sql);
			}
		}

		return 1;
	}

	
}
