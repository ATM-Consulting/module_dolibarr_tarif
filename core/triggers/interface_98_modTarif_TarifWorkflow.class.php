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
	
	function _getRemise($idProd,$qty,$conditionnement,$weight_units){
		
		//chargement des prix par conditionnement associé au produit (LISTE des tarifs pour le produit testé & TYPE_REMISE grâce à la jointure !!!)
		$sql = "SELECT p.type_remise as type_remise, tc.quantite as quantite, tc.type_price, tc.unite as unite, tc.prix as prix, tc.unite_value as unite_value, tc.tva_tx as tva_tx, tc.remise_percent as remise_percent";
		$sql.= " FROM ".MAIN_DB_PREFIX."tarif_conditionnement as tc";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as p on p.fk_object = tc.fk_product";
		$sql.= " WHERE fk_product = ".$idProd;
		$sql.= " ORDER BY quantite DESC"; //unite_value DESC, 
		
		$resql = $this->db->query($sql);
		
		if($resql->num_rows > 0) {
			$pallier = 0;
			while($res = $this->db->fetch_object($resql)) {
				
				if( strpos($res->type_price,'PERCENT')!==false ){
					
					if($res->type_remise == "qte" && $qty >= $res->quantite){
						return array($res->remise_percent, $res->type_price);
					} 
					else if($res->type_remise == "conditionnement" && $conditionnement >= $res->quantite && $res->unite_value == $weight_units) {
						return array($res->remise_percent, $res->type_price);
					}
				}
			}
		}
		
		return 0;
	}

	function _getPrix($idProd,$qty,$conditionnement,$weight_units,$subprice,$coef,$devise,$price_level=1,$fk_country=0){

		//chargement des prix par conditionnement associé au produit (LISTE des tarifs pour le produit testé & TYPE_REMISE grâce à la jointure)
		$sql = "SELECT p.type_remise as type_remise, tc.type_price, tc.quantite as quantite, tc.unite as unite, tc.prix as prix, tc.unite_value as unite_value, tc.tva_tx as tva_tx, tc.remise_percent as remise_percent, pr.weight";
		$sql.= " FROM ".MAIN_DB_PREFIX."tarif_conditionnement as tc";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as p on p.fk_object = tc.fk_product
				 LEFT JOIN ".MAIN_DB_PREFIX."product pr ON p.fk_object=pr.rowid ";
		$sql.= " WHERE fk_product = ".$idProd." AND (tc.currency_code = '".$devise."' OR tc.currency_code IS NULL)";
		
		if($fk_country>0) {
			
			$sql.=" AND tc.fk_country IN (0, $fk_country)";
			
		}
		
		$sql.= " ORDER BY quantite DESC, tc.fk_country DESC"; 
		
		$resql = $this->db->query($sql);
		
		if($resql->num_rows > 0) {
			while($res = $this->db->fetch_object($resql)) {
				
				if(strpos($res->type_price,'PRICE') !== false){
					
					if($res->type_remise == "qte" && $qty >= $res->quantite){
						//Ici on récupère le pourcentage correspondant et on arrête la boucle
						return $this->_price_with_multiprix($res->prix, $price_level);
					} 
					else if($res->type_remise == "conditionnement" && $conditionnement >= $res->quantite &&  $res->unite_value == $weight_units) {
						return $this->_price_with_multiprix($res->prix * ($conditionnement / $res->weight), $price_level); // prise en compte unité produit et poid init produit
					}
				}
			}
		}
		
		
		
		
		return $subprice * $coef;

	}
	function _price_with_multiprix($price, $price_level) {
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
	function _updateLineProduct(&$object,&$user,$idProd,$conditionnement,$weight_units,$remise, $prix ,$prix_devise){
		
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
			$this->db->query('UPDATE '.MAIN_DB_PREFIX.$tabledet.' SET devise_pu = '.$prix_devise.', devise_mt_ligne = '.(($prix_devise * $object->qty) * ( 1 - ($object->remise_percent/100))).' WHERE rowid = '.$object->rowid);
			//exit("$prix $prix_devise");
		}
		//exit;
	}
	
	function _updateTotauxLine(&$object,$qty){
		//MAJ des totaux de la ligne
		$object->total_ht = $object->subprice * $qty * (1 - $object->remise_percent / 100);
		$object->total_tva = ($object->total_ht * (1 + ($object->tva_tx/100))) - $object->total_ht;
		$object->total_ttc = $object->total_ht + $object->total_tva;
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
		dol_include_once('/commande/class/commande.class.php');
		dol_include_once('/fourn/class/fournisseur.commande.class.php');
		dol_include_once('/compta/facture/class/facture.class.php');
		dol_include_once('/comm/propal/class/propal.class.php');
		dol_include_once('/dispatch/class/dispatchdetail.class.php');
		
		global $user, $db;

		//Création d'une ligne de facture, propale ou commande
		if (($action == 'LINEORDER_INSERT' || $action == 'LINEPROPAL_INSERT' || $action == 'LINEBILL_INSERT') 
			&& (!isset($_REQUEST['notrigger']) || $_REQUEST['notrigger'] != 1)) {
			
				
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

			$poids = __get('poids', 1,'float');
			$weight_units = $_POST['weight_units'];
			
			// Si on a un poids passé en $_POST alors on viens d'une facture, propale ou commande
			if($poids > 0 && $idProd > 0){				
				
				if($conf->multidevise->enabled){
					
					if(get_class($object) == "OrderLine"){
						$table = "commande";
						//$object->update(true);
					}
					elseif(get_class($object) == 'PropaleLigne'){
						$table = "propal";
						//$object->update(true);
					}
					elseif(get_class($object) == 'FactureLigne'){
						$table = "facture";
					}
					
					$sql = "SELECT devise_code as code, devise_taux as coef FROM ".MAIN_DB_PREFIX.$table." WHERE rowid = ".$object->{"fk_".$table}; //Récup devise du parent + taux de conv 
					
					$res = $db->query($sql);
					$resql = $db->fetch_object($res);
					
					$coef_conv = $resql->coef;
					$devise = $resql->code;
				}
				else{ //devise = a celle du système
					$coef_conv = 1;
					$devise = $conf->currency;
				}
				
				list($remise, $type_prix) = $this->_getRemise($idProd,$object->qty,$poids,$weight_units);
				$prix = __val($object->subprice,$object->price,'float',true);
				
				if($remise == 0 || $type_prix == 'PERCENT/PRICE'){
					$object_parent = $this->_getObjectParent($object);
					$price_level = $object_parent->client->price_level;
					$fk_country = $object_parent->client->country_id;
		
					$prix_devise = $this->_getPrix($idProd,$object->qty*$poids,$poids,$weight_units,$prix,$coef_conv,$devise,$price_level,$fk_country);
					$prix = $prix_devise / $coef_conv;
				}
				
				$this->_updateLineProduct($object,$user,$idProd,$poids,$weight_units,$remise,$prix,$prix_devise); //--- $poids = conditionnement !
				$this->_updateTotauxLine($object,$object->qty);
					
				//MAJ du poids et de l'unité de la ligne
				if(get_class($object) == 'PropaleLigne') $table = 'propaldet';
				if(get_class($object) == 'OrderLine') $table = 'commandedet';
				if(get_class($object) == 'FactureLigne') $table = 'facturedet'; 
				$this->db->query("UPDATE ".MAIN_DB_PREFIX.$table." SET tarif_poids = ".$poids.", poids = ".$weight_units." WHERE rowid = ".$object->rowid);
				//pre($object,true); exit;
				//echo "1 "; exit;
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
					$sql = "SELECT tarif_poids as weight, 1 as qty, poids as weight_unit 
							FROM ".MAIN_DB_PREFIX."commandedet
							WHERE rowid = ".$originid;
				}
				
				elseif($object->origin == "shipping"){
					
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

				//echo $sql; exit;
				
				$resql = $this->db->query($sql);
				$res = $this->db->fetch_object($resql);

				$poids = $res->weight;
				$weight_units = $res->weight_unit;
				$object->qty = $res->qty;

				$this->db->query("UPDATE ".MAIN_DB_PREFIX.$table." SET tarif_poids = ".($poids / $object->qty).", poids = ".$weight_units." WHERE rowid = ".$object->rowid);

				if($object->origin == "shipping"){
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
				//MAJ du poids et de l'unité de la ligne
				if(get_class($object) == 'PropaleLigne') $table = 'propaldet';
				if(get_class($object) == 'OrderLine') $table = 'commandedet';
				if(get_class($object) == 'FactureLigne') $table = 'facturedet'; 
				$this->db->query("UPDATE ".MAIN_DB_PREFIX.$table." SET tarif_poids = ".$poids.", poids = ".$weight_units." WHERE rowid = ".$object->rowid);
			}
			
			dol_syslog("Trigger '".$this->name."' for actions '$action' launched by ".__FILE__.". id=".$object->rowid);
		}

		elseif(($action == 'LINEORDER_UPDATE' || $action == 'LINEPROPAL_UPDATE' || $action == 'LINEBILL_UPDATE') 
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
			
			$resql = $this->db->query("SELECT tarif_poids, poids FROM ".MAIN_DB_PREFIX.$tabledet." WHERE rowid = ".$object->rowid);
			$res = $this->db->fetch_object($resql);
			
			$weight_units = __get('weight_units',0,'integer');
			$poids = __get('poids',1,'float');
			
			//echo floatval($res->tarif_poids * pow(10, $res->poids))." ".floatval($_POST['poids'] * pow(10, $_POST['weight_units']));exit;
			// Si on a un poids passé en $_POST alors on viens d'une facture, propale ou commande
			// ET si la quantité ou le poids a changé
			//exit($object->oldline->qty." != ".$object->qty." || ".floatval($res->tarif_poids * pow(10, $res->poids))." != ".floatval($poids * pow(10, $weight_units)));
			if($object->oldline->qty != $object->qty || floatval($res->tarif_poids * pow(10, $res->poids)) != floatval($poids * pow(10, $weight_units))){
				
				if(!empty($idProd)){
					if($conf->multidevise->enabled){
						$sql = "SELECT devise_code as code, devise_taux as coef FROM ".MAIN_DB_PREFIX.$table." WHERE rowid = ".__val($object->{"fk_".$table},$_REQUEST['id'],'integer'); //Récup devise du parent + taux de conv 
						
						$res = $db->query($sql);
						$resql = $db->fetch_object($res);
						
						$coef_conv = $resql->coef;
						$devise = $resql->code;
					}
					else{ //devise = a celle du système
						$coef_conv = 1;
						$devise = $conf->currency;
					}
					
					list($remise, $type_prix) = $this->_getRemise($idProd,$object->qty,$poids,$weight_units);
					$prix = __val($object->subprice,$object->price,'float',true);
					
					if($remise == 0 || $type_prix=='PERCENT/PRICE'){
						$object_parent = $this->_getObjectParent($object);
						$price_level = $object_parent->client->price_level;
						$fk_country = $object_parent->client->country_id;
		
						$prix_devise = $this->_getPrix($idProd,$object->qty*$poids,$poids,$weight_units,$object->subprice,$coef_conv,$devise, $price_level,$fk_country);
						$prix = $prix_devise / $coef_conv;
					}
					
					//pre($object, true);exit;
					$this->_updateLineProduct($object,$user,$idProd,$poids,$weight_units,$remise,$prix,$prix_devise); //--- $poids = conditionnement !
					$this->_updateTotauxLine($object,$object->qty);
					
				}

				$this->db->query("UPDATE ".MAIN_DB_PREFIX.$tabledet." SET tarif_poids = ".$poids.", poids = ".$weight_units." WHERE rowid = ".$object->rowid);

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
					(".$level.",'".$this->db->idate($now)."',".$object->id.",".$user->id.",".$price.",".$price_ttc.",'".$base."',".$object->status.",".$tva_tx.",".$object->tva_npr.",".$object->localtax1_tx.",".$object->localtax2_tx.",".$object->price_min.",".$object->price_min_ttc.",0,".$conf->entity.")";
				
				$this->db->query($sql);
			}
		}

		return 1;
	}
}
