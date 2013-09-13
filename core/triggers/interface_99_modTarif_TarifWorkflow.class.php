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
		
		
		//Création ou modification d'une ligne de facture, propale ou commande
		if (($action == 'LINEORDER_INSERT' || $action == 'LINEORDER_UPDATE' ||
			$action == 'LINEPROPAL_INSERT' || $action == 'LINEPROPAL_UPDATE' ||
			$action == 'LINEBILL_INSERT' || $action == 'LINEBILL_UPDATE') 
			&& (!isset($_REQUEST['notrigger']) || $_REQUEST['notrigger'] != 1)) {
			
			//init des variables utiles
			$prix = 0;
			$tva_tx = 0;
			$remise = !empty($_REQUEST['remise_percent']) ? $_REQUEST['remise_percent'] : 0;
			$poids = 0;
			$weight_units = 0;
			
			// Si on a un poids passé en $_POST alors on viens d'une facture, propale ou commande
			
			if(GETPOST('poids', 'int')){
				
				$poids = (!empty($_POST['poids'])) ? floatval($_POST['poids']) : 0;
				$weight_units = $_POST['weight_units'];
				$idprod = 0;
				if(!empty($_POST['idprod'])) $idProd = $_POST['idprod'];
				if(!empty($_POST['productid'])) $idProd = $_POST['productid'];
				
				//Si la ligne à insérer est liée à un produit
				if(!empty($idProd)){
					
					//chargement des prix par conditionnement associé au produit
					$sql = "SELECT quantite, unite, prix, unite_value, tva_tx, remise_percent
							FROM ".MAIN_DB_PREFIX."tarif_conditionnement
							WHERE fk_product = ".$idProd."
							ORDER BY unite_value DESC, quantite DESC";
					
					$resql = $this->db->query($sql);
					
					// Quantité totale de produit ajoutée dans la ligne
					$qte_totale = $_POST['qty'] * $poids * pow(10, $weight_units);
					
					//Si il existe au moin un prix par conditionnement
					if($resql->num_rows > 0) {
						
						$found = false;
						while($res = $this->db->fetch_object($resql)){
							$qte_totale_grille = $res->quantite * pow(10, $res->unite_value);
							if($qte_totale_grille <= $qte_totale) {
								//Récupération de la remise
								if(!empty($res->remise_percent) && empty($remise))
									$remise = $res->remise_percent;
								
								$prix = $res->prix;
								$tva_tx = $res->tva_tx;
								
								$found = true;
								break;
							}
						}
						
						//Quantité en dehors de la grille alors retourner erreur
						if(!$found) {
							$this->db->rollback();
							$this->db->rollback();
							$object->error = "Quantité trop faible";
							return -1;
						}
					}
					
					//MAJ de la ligne
					//$object->tva_tx = $tva_tx;
					$object->remise_percent = $remise;
					//$object->qty = $_POST['qty'];
					$object->subprice = $prix;
					
					if(get_class($object) == 'FactureLigne') $object->update($user, true);
					else $object->update(true);
					
					//echo $poids / pow(10, $weight_units); exit;
					$product = new Product($this->db);
					$product->fetch($idProd);
					
					//echo $product->weight_units; exit;
					if($product->weight_units < $weight_units)
						$poids = $poids * pow(10, ($weight_units - $product->weight_units ));
										
					//MAJ des totaux de la ligne
					$object->total_ht = $object->subprice * $_POST['qty'] * $poids * (1 - $object->remise_percent / 100);
					$object->total_tva = ($object->total_ht * (1 + ($tva_tx/100))) - $object->total_ht;
					$object->total_ttc = $object->total_ht + $object->total_tva;
					$object->update_total();
					
				}
				else{
					//MAJ des totaux d'une ligne libre
					$object->total_ht = $object->subprice * $_POST['qty'] * $poids * (1 - $object->remise_percent / 100);
					$object->total_tva = ($object->total_ht * (1 + ($tva_tx/100))) - $object->total_ht;
					$object->total_ttc = $object->total_ht + $object->total_tva;
					$object->update_total();
				}
				//On remet le poids originale transmis en POST dans le cas ou
				$poids = (!empty($_POST['poids'])) ? floatval($_POST['poids']) : 0;
				
				//MAJ du poids et de l'unité de la ligne
				if(get_class($object) == 'PropaleLigne') $table = 'propaldet';
				if(get_class($object) == 'OrderLine') $table = 'commandedet';
				if(get_class($object) == 'FactureLigne') $table = 'facturedet'; 
				$this->db->query("UPDATE ".MAIN_DB_PREFIX.$table." SET tarif_poids = ".$poids.", poids = ".$weight_units." WHERE rowid = ".$object->rowid);

			} 
			
			// Sinon, Si l'object origine est renseigné et est soit une propale soit une commande
			// => filtre sur propale ou commande car confli éventuel avec le trigger sur expédition
			elseif(   ((!empty($object->origin) && !empty($object->origin_id)) 
					|| (!empty($_POST['origin']) && !empty($_POST['originid'])))
					&& ($_POST['origin'] == "propal" || $object->origin == "commande")){
				
				//Cas propal on charge la ligne correspondante car non passé dans le post
				if($_POST['origin'] == "propal"){
					$table = "propaldet";
					$propal = new Propal($this->db);
					$propal->fetch($_POST['originid']);
					
					foreach($propal->lines as $line){
						if($line->rang == $object->rang)
							$originid = $line->rowid;
					}
	        	}
				//Cas commande la ligne d'origine est déjà chargé dans l'objet
				elseif($object->origin == "commande"){
					$table = "commandedet";
					$originid = $object->origin_id;
				}
				
				$prix = $object->subprice;
				$tva_tx = $object->tva_tx;
				$remise = $object->remise_percent;
				$idProd = $object->fk_product;
				
				$resql = $this->db->query("SELECT poids, tarif_poids FROM ".MAIN_DB_PREFIX.$table." WHERE rowid = ".$originid);
				$res = $this->db->fetch_object($resql);
				
				$poids = $res->tarif_poids;
				$weight_units = $res->poids;
				
				$qte_totale = $object->qty * $poids * pow(10, $weight_units);
						
			}
			
			dol_syslog("Trigger '".$this->name."' for actions '$action' launched by ".__FILE__.". id=".$object->rowid);
		}

		//MAJ des différents prix de la grille de tarif par conditionnement lors d'une modification du prix produit
		elseif($action == 'PRODUCT_PRICE_MODIFY'){
			
			$resql = $this->db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."tarif_conditionnement WHERE fk_product = ".$object->id);
			
			if($resql->num_rows > 0){
				$res = $this->db->fetch_object($resql);
				$this->db->query("UPDATE ".MAIN_DB_PREFIX."tarif_conditionnement 
									  SET tva_tx = ".$object->tva_tx.", price_base_type = '".$object->price_base_type."', prix = ".$object->price." 
									  WHERE fk_product = ".$object->id);
			}
		}

		return 1;
	}
}
