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
		
			/*ini_set('dysplay_errors','On');
			error_reporting(E_ALL);*/
       
        /*
		 *  COMMANDES
		 */
        if ($action == 'LINEORDER_INSERT')
        {        	
			if(isset($_POST['poids']) && !empty($_POST['poids']) && $_POST['poids'] != 0){ //si poids renseigné alors recherche prix par conditionnement
			
			/*echo '<pre>';
			print_r($_POST);
			echo '</pre>';
			exit;*/
			
				// Ajout d'un produit/service existant
				if(isset($_POST['idprod'])){
					
					$sql = "SELECT quantite, unite, prix, unite_value, tva_tx, remise_percent
							FROM ".MAIN_DB_PREFIX."tarif_conditionnement
							WHERE fk_product = ".$_POST['idprod']."
							ORDER BY unite_value DESC, quantite DESC";
					
					$trouve = false;				
					$resql = $this->db->query($sql);
					while($res = $this->db->fetch_object($resql)){
						//ex : si 10mg <= 5 * 5mg
						if($res->quantite * pow(10,$res->unite_value) <= $_POST['qty'] * ($_POST['poids'] * pow(10,$_POST['weight_units']))){
							$trouve = true;
							$commande = new Commande($this->db);
							$commande->fetch($object->fk_commande);
							
							//Récupération de la remise
							if(isset($_POST['remise_percent']) && !empty($_POST['remise_percent']) && $_POST['remise_percent'] != 0) 
								$remise = $_POST['remise_percent'];
							elseif($res->remise_percent != 0 && !is_null($res->remise_percent))
								$remise = $res->remise_percent;
							else
								$remise = NULL;
										
							$commande->updateline($object->rowid, $object->desc, $res->prix, $_POST['qty'], $remise, $res->tva_tx, 0, 0, 'HT', 0, '', '', 0, 0, 0, null, 0, ($_POST['product_label']?$_POST['product_label']:''), 0);
							
							//MAJ des totaux de la ligne de commande
							$object->total_ht = $_POST['qty'] * $res->prix * ($_POST['poids'] * pow(10,abs($_POST['weight_units']-$res->unite_value)));
							$object->total_tva = ($object->total_ht * (1 + ($res->tva_tx/100))) - $object->total_ht;
							$object->total_ttc = $object->total_ht + $object->total_tva;
							$object->update_total();
							
							$this->db->query("UPDATE ".MAIN_DB_PREFIX."commandedet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$object->rowid);
							
							break;
						}
					}
					
					//La quantité est inférieur à la première tranche de prix par conditionnement
					//On concerve donc le prix de vente directement lié au produit
					if(!$trouve){
						$this->db->query("UPDATE ".MAIN_DB_PREFIX."commandedet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$object->rowid);
					}
				}
				
				// Ajout d'une ligne libre
				elseif(!isset($_POST['productid'])){
					
					$commande = new Commande($this->db);
					$commande->fetch($object->fk_commande);
								
					$commande->updateline($object->rowid, $_POST['dp_desc'], $_POST['price_ht'], $_POST['qty'], $_POST['remise_percent'], $_POST['tva_tx'], 0, 0, 'HT', 0, '', '', 0, 0, 0, null, 0, ($_POST['product_label']?$_POST['product_label']:''), 0);
					$this->db->query("UPDATE ".MAIN_DB_PREFIX."commandedet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$object->rowid);
				}
			}
			
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->rowid);

        }
        elseif ($action == 'LINEORDER_UPDATE')
        {
        	if(isset($_POST['poids']) && !empty($_POST['poids']) && $_POST['poids'] != 0){ //si poids renseigné alors conditionnement
        		
        		// MAJ d'un produit/service existant
        		if(isset($_POST['productid']) && $_POST['productid'] != 0){
        			
					$sql = "SELECT quantite, unite, prix, unite_value, tva_tx
							FROM ".MAIN_DB_PREFIX."tarif_conditionnement
							WHERE fk_product = ".$_POST['productid']."
							ORDER BY unite_value DESC, quantite DESC";
					
					$trouve = false;
					$resql = $this->db->query($sql);
					while($res = $this->db->fetch_object($resql)){
											
						if($res->quantite * pow(10,$res->unite_value) <= $_POST['qty'] * ($_POST['poids'] * pow(10,$_POST['weight_units']))){
							$trouve = true;
							$commande = new Commande($this->db);
							$commande->fetch($object->oldline->fk_commande);
							
							//Récupération de la remise
							if(isset($_POST['remise_percent']) && !empty($_POST['remise_percent']) && $_POST['remise_percent'] != $object->remise_percent) 
								$remise = $_POST['remise_percent'];
							elseif($res->remise_percent != 0 && !is_null($res->remise_percent))
								$remise = $res->remise_percent;
							else
								$remise = $object->remise_percent;
							
							$commande->deleteline($object->rowid);
							$id_line = $commande->addline($object->oldline->fk_commande, $object->desc, $res->prix, $_POST['qty'], $res->tva_tx, 0, 0, $_POST['productid'], $remise, '', '', 0, 0, '', 'HT', 0, 0, $object->rang, 0, '', 0, 0, null, 0, ($_POST['product_label']?$_POST['product_label']:''));
							
							//MAJ des totaux de la ligne de commande
							$object->fetch($id_line);
							$object->total_ht = $_POST['qty'] * $res->prix * ($_POST['poids'] * pow(10,abs($_POST['weight_units']-$res->unite_value)));
							$object->total_tva = ($object->total_ht * (1 + ($res->tva_tx/100))) - $object->total_ht;
							$object->total_ttc = $object->total_ht + $object->total_tva;
							$object->update_total();
							
							$this->db->query("UPDATE ".MAIN_DB_PREFIX."commandedet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$id_line);
							break;
						}
					}
					
					//La quantité est inférieur à la première tranche de prix par conditionnement
					//On concerve donc le prix de vente directement lié au produit
					if(!$trouve){
						$this->db->query("UPDATE ".MAIN_DB_PREFIX."commandedet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$object->rowid);
					}
				}
				
				// MAJ d'une ligne libre
				elseif(!isset($_POST['idprod']) && isset($_POST['productid']) && $_POST['productid'] == 0){
					
					$commande = new Commande($this->db);
					$commande->fetch($object->oldline->fk_commande);
					
					$commande->deleteline($object->rowid);
					
					$id_line = $commande->addline($object->oldline->fk_commande, $_POST['product_desc'], $_POST['price_ht'], $_POST['qty'], $_POST['tva_tx'], 0, 0, 0, $_POST['remise_percent'], '', '', 0, 0, '', 'HT', 0, 0, $object->rang, 0, '', 0, 0, null, 0, ($_POST['product_label']?$_POST['product_label']:''));
							
					$this->db->query("UPDATE ".MAIN_DB_PREFIX."commandedet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$id_line);
				}
			}
        	
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->rowid);
		
        }
      
         /*
		 *  PROPOSITIONS COMMERCIALES
		 */
        elseif ($action == 'LINEPROPAL_INSERT')
        {
			if(isset($_POST['poids']) && !empty($_POST['poids']) && $_POST['poids'] != 0){ //si poids renseigné alors conditionnement
				
				// Ajout d'un produit/service existant
				if(isset($_POST['idprod'])){
					$sql = "SELECT quantite, unite, prix, unite_value, tva_tx
							FROM ".MAIN_DB_PREFIX."tarif_conditionnement
							WHERE fk_product = ".$_POST['idprod']."
							ORDER BY unite_value DESC, quantite DESC";
					
					$trouve = false;				
					$resql = $this->db->query($sql);
					while($res = $this->db->fetch_object($resql)){
						if($res->quantite * pow(10,$res->unite_value) <= $_POST['qty'] * ($_POST['poids'] * pow(10,$_POST['weight_units']))){
							$trouve = true;
							$propal = new Propal($this->db);
							$propal->fetch($object->fk_propal);
							
							//Récupération de la remise
							if(isset($_POST['remise_percent']) && !empty($_POST['remise_percent']) && $_POST['remise_percent'] != 0) 
								$remise = $_POST['remise_percent'];
							elseif($res->remise_percent != 0 && !is_null($res->remise_percent))
								$remise = $res->remise_percent;
							else
								$remise = NULL;
							
							$propal->updateline($object->rowid, $res->prix, $_POST['qty'], $remise, $res->tva_tx, 0, 0, $object->desc, 'HT', 0, 0, 0, 0, 0, 0, ($_POST['product_label']?$_POST['product_label']:''), 0, '', '');
							
							//MAJ des totaux de la ligne de propal
							$object->total_ht = $_POST['qty'] * $res->prix * ($_POST['poids'] * pow(10,abs($_POST['weight_units']-$res->unite_value)));
							$object->total_tva = ($object->total_ht * (1 + ($res->tva_tx/100))) - $object->total_ht;
							$object->total_ttc = $object->total_ht + $object->total_tva;
							$object->update_total();
							
							$this->db->query("UPDATE ".MAIN_DB_PREFIX."propaldet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$object->rowid);
							break;
						}
					}
					
					if(!$trouve){
						$this->db->query("UPDATE ".MAIN_DB_PREFIX."propaldet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$object->rowid);
					}
				}
				// Ajout d'une ligne libre
				elseif(!isset($_POST['productid'])){
					$propal = new Propal($this->db);
					$propal->fetch($object->fk_propal);
					
					$propal->updateline($object->rowid, $_POST['price_ht'], $_POST['qty'], $_POST['remise_percent'], $_POST['tva_tx'], 0, 0, $_POST['dp_desc'], 'HT', 0, 0, 0, 0, 0, 0, ($_POST['product_label']?$_POST['product_label']:''), 0, '', '');
					
					$this->db->query("UPDATE ".MAIN_DB_PREFIX."propaldet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$object->rowid);
				}
			}
			
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->rowid);
			
        }
        elseif ($action == 'LINEPROPAL_UPDATE')
        {
        	if(isset($_POST['poids']) && !empty($_POST['poids']) && $_POST['poids'] != 0){ //si poids renseigné alors conditionnement
        		
        		// MAJ d'un produit/service existant
				if(isset($_POST['productid']) && $_POST['productid'] != 0){
					$sql = "SELECT quantite, unite, prix, unite_value, tva_tx
							FROM ".MAIN_DB_PREFIX."tarif_conditionnement
							WHERE fk_product = ".$_POST['productid']."
							ORDER BY unite_value DESC, quantite DESC";
					
					$trouve = false;
					$resql = $this->db->query($sql);
					while($res = $this->db->fetch_object($resql)){					
						if($res->quantite * pow(10,$res->unite_value) <= $_POST['qty'] * ($_POST['poids'] * pow(10,$_POST['weight_units']))){
							$trouve = true;
							$propal = new Propal($this->db);
							$propal->fetch($object->oldline->fk_propal);
							
							//Récupération de la remise
							if(isset($_POST['remise_percent']) && !empty($_POST['remise_percent']) && $_POST['remise_percent'] != $object->remise_percent) 
								$remise = $_POST['remise_percent'];
							elseif($res->remise_percent != 0 && !is_null($res->remise_percent))
								$remise = $res->remise_percent;
							else
								$remise = $object->remise_percent;
							
							$propal->deleteline($object->rowid);
							$propal->addline($object->oldline->fk_propal, $object->desc, $res->prix, $_POST['qty'], $res->tva_tx, 0, 0, $_POST['productid'], $remise, 'HT',0, 0, 0, $object->rang, 0, 0, 0, 0, ($_POST['product_label']?$_POST['product_label']:''));
							
							$resql2 = $this->db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."propaldet WHERE fk_propal = ".$object->oldline->fk_propal." ORDER BY rowid DESC LIMIT 1");
							$res2 = $this->db->fetch_object($resql2);
							$id_line = $res2->rowid;
							
							//MAJ des totaux de la ligne de propal
							$object->fetch($id_line);
							$object->total_ht = $_POST['qty'] * $res->prix * ($_POST['poids'] * pow(10,abs($_POST['weight_units']-$res->unite_value)));
							$object->total_tva = ($object->total_ht * (1 + ($res->tva_tx/100))) - $object->total_ht;
							$object->total_ttc = $object->total_ht + $object->total_tva;
							$object->update_total();
							
							$this->db->query("UPDATE ".MAIN_DB_PREFIX."propaldet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$id_line);
							break;
						}
					}

					if(!$trouve){
						$resql2 = $this->db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."propaldet WHERE fk_propal = ".$object->oldline->fk_propal." ORDER BY rowid DESC LIMIT 1");
						$res2 = $this->db->fetch_object($resql2);
						$id_line = $res2->rowid;
						
						$this->db->query("UPDATE ".MAIN_DB_PREFIX."propaldet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$id_line);
					}
					
				}

				// MAJ d'une ligne libre
				elseif(!isset($_POST['idprod']) && isset($_POST['productid']) && $_POST['productid'] == 0){
					$propal = new Propal($this->db);
					$propal->fetch($object->oldline->fk_propal);
					
					$propal->deleteline($object->rowid);
					$propal->addline($object->oldline->fk_propal, $_POST['product_desc'], $_POST['price_ht'], $_POST['qty'], $_POST['tva_tx'], 0, 0, 0, $_POST['remise_percent'], 'HT',0, 0, 0, $object->rang, 0, 0, 0, 0, ($_POST['product_label']?$_POST['product_label']:''));
					
					$resql2 = $this->db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."propaldet WHERE fk_propal = ".$object->oldline->fk_propal." ORDER BY rowid DESC LIMIT 1");
					$res2 = $this->db->fetch_object($resql2);
					$id_line = $res2->rowid;
					
					$this->db->query("UPDATE ".MAIN_DB_PREFIX."propaldet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$id_line);
				}
			}
			
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->rowid);
			
        }
		
		/*
		 *  FACTURES
		 */
        elseif ($action == 'LINEBILL_INSERT')
        {
        	if(isset($_POST['poids']) && !empty($_POST['poids']) && $_POST['poids'] != 0){ //si poids renseigné alors conditionnement
        	
        		// Ajout d'un produit/service existant
				if(isset($_POST['idprod'])){
					$sql = "SELECT quantite, unite, prix, unite_value, tva_tx
							FROM ".MAIN_DB_PREFIX."tarif_conditionnement
							WHERE fk_product = ".$_POST['idprod']."
							ORDER BY unite_value DESC, quantite DESC";
					
					$trouve = false;
					$resql = $this->db->query($sql);
					while($res = $this->db->fetch_object($resql)){					
						if($res->quantite * pow(10,$res->unite_value) <= $_POST['qty'] * ($_POST['poids'] * pow(10,$_POST['weight_units']))){
							$trouve = true;
							$facture = new Facture($this->db);
							$facture->fetch($object->fk_facture);
							
							//Récupération de la remise
							if(isset($_POST['remise_percent']) && !empty($_POST['remise_percent']) && $_POST['remise_percent'] != 0) 
								$remise = $_POST['remise_percent'];
							elseif($res->remise_percent != 0 && !is_null($res->remise_percent))
								$remise = $res->remise_percent;
							else
								$remise = NULL;
							
							$facture->updateline($object->rowid, $object->desc, $res->prix, $_POST['qty'], $remise, NULL, NULL, $res->tva_tx, 0, 0, 'HT', 0, 0, 0, 0, null, 0, ($_POST['product_label']?$_POST['product_label']:''), 0);
							
							//MAJ des totaux de la ligne de facture
							$object->total_ht = $_POST['qty'] * $res->prix * ($_POST['poids'] * pow(10,abs($_POST['weight_units']-$res->unite_value)));
							$object->total_tva = ($object->total_ht * (1 + ($res->tva_tx/100))) - $object->total_ht;
							$object->total_ttc = $object->total_ht + $object->total_tva;
							$object->update_total();
							
							$this->db->query("UPDATE ".MAIN_DB_PREFIX."facturedet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$object->rowid);
							break;
						}
					}
					
					if(!$trouve){
						$this->db->query("UPDATE ".MAIN_DB_PREFIX."facturedet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$object->rowid);
					}
				}
				// Ajout d'une ligne libre
				elseif(!isset($_POST['productid'])){
					$facture = new Facture($this->db);
					$facture->fetch($object->fk_propal);
					
					$facture->updateline($object->rowid, $_POST['dp_desc'], $_POST['price_ht'], $_POST['qty'], $_POST['remise_percent'], NULL, NULL, $_POST['tva_tx'], 0, 0, 'HT', 0, 0, 0, 0, null, 0, ($_POST['product_label']?$_POST['product_label']:''), 0);
					
					$this->db->query("UPDATE ".MAIN_DB_PREFIX."facturedet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$object->rowid);
				}
			}	
        	
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->rowid);
			
        }
		elseif ($action == 'LINEBILL_UPDATE')
        {
			
          	if(isset($_POST['poids']) && !empty($_POST['poids']) && $_POST['poids'] != 0){ //si poids renseigné alors conditionnement
        	
        		// Ajout d'un produit/service existant
				if(isset($_POST['productid']) && $_POST['productid'] != 0){
					$sql = "SELECT quantite, unite, prix, unite_value, tva_tx
							FROM ".MAIN_DB_PREFIX."tarif_conditionnement
							WHERE fk_product = ".$_POST['productid']."
							ORDER BY unite_value DESC, quantite DESC";
					
					$trouve = false;
					$resql = $this->db->query($sql);
					while($res = $this->db->fetch_object($resql)){					
						if($res->quantite * pow(10,$res->unite_value) <= $_POST['qty'] * ($_POST['poids'] * pow(10,$_POST['weight_units']))){
							$trouve = true;
							$facture = new Facture($this->db);
							$facture->fetch($object->oldline->fk_facture);
							
							//Récupération de la remise
							if(isset($_POST['remise_percent']) && !empty($_POST['remise_percent']) && $_POST['remise_percent'] != $object->remise_percent) 
								$remise = $_POST['remise_percent'];
							elseif($res->remise_percent != 0 && !is_null($res->remise_percent))
								$remise = $res->remise_percent;
							else
								$remise = $object->remise_percent;
							
							$facture->deleteline($object->rowid);
							$id_line = $facture->addline($object->oldline->fk_facture, $object->desc, $res->prix, $_POST['qty'], $res->tva_tx, 0, 0, $_POST['productid'], $remise, '', '', 0, 0, '', 'HT', 0, 0, $object->rang, 0, '', 0, 0, null, 0, ($_POST['product_label']?$_POST['product_label']:''));
							
							//MAJ des totaux de la ligne de propal
							$object->fetch($id_line);
							$object->total_ht = $_POST['qty'] * $res->prix * ($_POST['poids'] * pow(10,abs($_POST['weight_units']-$res->unite_value)));
							$object->total_tva = ($object->total_ht * (1 + ($res->tva_tx/100))) - $object->total_ht;
							$object->total_ttc = $object->total_ht + $object->total_tva;
							$object->update_total();
							
							$this->db->query("UPDATE ".MAIN_DB_PREFIX."facturedet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$id_line);
							break;
						}
					}
					
					if(!$trouve){
						$this->db->query("UPDATE ".MAIN_DB_PREFIX."facturedet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$object->rowid);
					}
				}
				elseif(!isset($_POST['idprod']) && isset($_POST['productid']) && $_POST['productid'] == 0){
					$facture = new Facture($this->db);
					$facture->fetch($object->oldline->fk_facture);
					
					$facture->deleteline($object->rowid);
					$id_line = $facture->addline($object->oldline->fk_facture, $_POST['product_desc'], $_POST['price_ht'], $_POST['qty'], $_POST['tva_tx'], 0, 0, 0, $_POST['remise_percent'], '', '', 0, 0, '', 'HT', 0, 0, $object->rang, 0, '', 0, 0, null, 0, ($_POST['product_label']?$_POST['product_label']:''));
					
					$this->db->query("UPDATE ".MAIN_DB_PREFIX."facturedet SET tarif_poids = ".$_POST['poids'].", poids = ".$_POST['weight_units']." WHERE rowid = ".$id_line);
				}
			}
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->rowid);
			
        }

		return 0;
    }
}
