<?php

ini_set('memory_limit', '512M');
set_time_limit(0);

require('../config.php');
//dol_include_once("/product/class/product.class.php");
dol_include_once("/product/class/product.class.php");
dol_include_once("/custom/tarif/class/tarif.class.php");
dol_include_once("/custom/asset/class/asset.class.php");
dol_include_once("/societe/class/societe.class.php");
dol_include_once("/contact/class/contact.class.php");

$ATMdb = new Tdb;
$articlesfile = fopen('../import/produits.csv', 'r');
$flaconsfile = fopen('../import/flacons.csv','r');
$lotsfile = fopen('../import/lots.csv','r');
$unitesfile = fopen('../import/unites.csv','r');
$societesfile = fopen('../import/contacts.csv','r');
$fournisseursfile = fopen('../import/fournisseurs.csv','r');
$TGlobal = array();
$i = 0;

function _unit($unite){
	switch ($unite) {
		case 'µg':
			return -9;
			break;
		case 'mg':
			return -6;
			break;
		case 'g':
			return -3;
			break;
		case 'gr':
			return -3;
			break;
		case 'kg':
			return 0;
			break;
	}
}

function _add_condi($ATMdb,$line,$produit,$nbColUnit,$nbColPrix){
	$string_unite = explode(" ", $line[$nbColUnit]);
			
	$tarif = new TTarif;
	$tarif->unite_value 		= _unit($string_unite[1]);
	$tarif->unite 				= $string_unite[1];
	$tarif->quantite 			= $string_unite[0];
	$tarif->price_base_type 	= "HT";
	$tarif->fk_product 			= $produit->id;
	$tarif->fk_user_author 		= 1;
	$tarif->tva_tx 				= 19.6;
	$tarif->remise_percent 		= 0;
	$tarif->prix 				= $line[$nbColPrix];
	$tarif->save($ATMdb);
	echo "$string_unite[0] "._unit($string_unite[1])." ";
}

function _add_tiers($ATMdb,$user,$db,$line,$type){
	$num_ligne = ($type=="client") ? 9: 6;
	
	$ATMdb->Execute('SELECT rowid FROM '.MAIN_DB_PREFIX."c_pays WHERE libelle LIKE '".$line[$num_ligne]."' LIMIT 1");
	$ATMdb->Get_line();
	
	$societe = new Societe($db);
	if($type == "client"){
		$societe->client 			= 3; //Client/Prospect
		$societe->fournisseur 		= 0; //fournisseur
		$societe->particulier 		= 0; //Société/Association
		$societe->name 				= (!empty($line[5]))? $line[5]: "";
		$societe->status 			= 1; //En activité
		$societe->address 			= (!empty($line[6]))? $line[6]: "";
		$societe->zip 				= (!empty($line[8]))? $line[8]: "";
		$societe->town 				= (!empty($line[7]))? $line[7]: "";
		$societe->country_id 		= $ATMdb->Get_field('rowid');
		$societe->email 			= (!empty($line[14]))? $line[14]: "";
		$societe->phone 			= (!empty($line[11]))? $line[11]: "";
		$societe->fax 				= (!empty($line[12]))? $line[12]: "";
	}
	else{
		$societe->client 			= 3; //Client/Prospect
		$societe->fournisseur 		= 1; //fournisseur
		$societe->particulier 		= 0; //Société/Association
		$societe->name 				= (!empty($line[1]))? $line[1]: "";
		$societe->status 			= 1; //En activité
		$societe->address 			= (!empty($line[2]))? $line[2]: "";
		$societe->zip 				= (!empty($line[4]))? $line[4]: "";
		$societe->town 				= (!empty($line[5]))? $line[5]: "";
		$societe->country_id 		= $ATMdb->Get_field('rowid');
		$societe->email 			= (!empty($line[9]))? $line[9]: "";
		$societe->phone 			= (!empty($line[7]))? $line[7]: "";
		$societe->fax 				= (!empty($line[8]))? $line[8]: "";
	}
	$societe->create($user);
	
	if($type == "client"){
		if(!empty($line[3])){
			$contact=new Contact($db);
	        $contact->name			= $line[3];
	        $contact->firstname		= (!empty($line[2]))? $line[2]: "";
	        $contact->address		= (!empty($line[6]))? $line[6]: "";
	        $contact->zip			= (!empty($line[8]))? $line[8]: "";
	        $contact->town			= (!empty($line[7]))? $line[7]: "";
	        $contact->country_id	= $ATMdb->Get_field('rowid');
	        $contact->socid			= $societe->id;	// fk_soc
	        $contact->status		= 1;
	        $contact->email			= (!empty($line[14]))? $line[14]: "";
			$contact->phone_pro		= (!empty($line[11]))? $line[11]: "";
			$contact->fax			= (!empty($line[12]))? $line[12]: "";
	        $contact->priv			= 0;
			
			$contact->create($user);
		}
	}
	
	return $societe;
}

function _add_equipement($ATMdb,$TGlobal,$line,$produit){
	foreach($TGlobal['lot'] as $ref_lot=>$Tinfos_lot){
		if($Tinfos_lot['ref_produit'] == $line[0]){
			$equipement = new TAsset;
			$equipement->fk_product 			= $produit->id;
			$equipement->entity 				= 0;
			$equipement->lot_number 			= $ref_lot;
			$equipement->contenance_value 		= $Tinfos_lot['quantite'];
			$equipement->contenancereel_value 	= $Tinfos_lot['quantite'];
			$equipement->contenance_units 		= _unit($TGlobal['unite'][$line[8]]);
			$equipement->contenancereel_units 	= _unit($TGlobal['unite'][$line[8]]);
			
			echo $ref_lot." ".$Tinfos_lot['quantite']." "._unit($TGlobal['unite'][$line[8]])." ";
			
			foreach($TGlobal['flacon'] as $ref_flacon=>$flacon_ref_produit){
				if($flacon_ref_produit == $line[0]){
					$equipement->serial_number = $ref_flacon;
					echo "$ref_flacon<br>";
					break;
				}
			}
			/*echo '<pre>';
			print_r($produit);
			echo '</pre>'; exit;*/
			$equipement->save($ATMdb);
			break;
		}
	}
}

//Création de tableau intermédiaire
//Pour optimisation du traitement

/*
 * TAB UNITE
 */
$unite = fgetcsv($unitesfile,0,'|',';');
while($unite = fgetcsv($unitesfile,0,'|',';')){
	$TGlobal['unite'][$unite[0]] = $unite[1];
}

/*
 * TAB FALCON
 */
$flacon = fgetcsv($flaconsfile,0,'|',';');
while($flacon = fgetcsv($flaconsfile,0,'|',';')){
	$TGlobal['flacon'][$flacon[1]] = $flacon[8]; // TGlobal['flacon']['ref_flacon'] = id_produit;
}

/*
 * TAB LOTS
 */
$lot = fgetcsv($lotsfile,0,'|',';');
while($lot = fgetcsv($lotsfile,0,'|',';')){
	$TGlobal['lot'][$lot[2]] = array('ref_produit'=>$lot[1],'quantite'=>$lot[12]); // TGlobal['lot']['ref_lot'] = array(id_produit,quantite);
}

/*
 * PRODUITS
 */
$line = fgetcsv($articlesfile,0,'|',';');
while($line = fgetcsv($articlesfile,0,'|',';')){
	if(empty($TGlobal['product'][$line[2]]) && !empty($line[2])) { // Création du produit la première fois que l'on a la référence
		echo "<hr>$i - $line[2] - $line[3]<br>";
	
		$produit = new Product($db);
		$produit->ref 				= $line[2];
		$produit->libelle 			= $line[3];
		$produit->description 		= "";
		$produit->price_base_type 	= 'TTC';
		$produit->price_ttc 		= 0;
		$produit->tva_tx 			= '19.6';
		$produit->type				= 0;
		$produit->status 			= 1;
		$produit->status_buy 		= 1;
		$produit->finished 			= 1;
		
		$produit->create($user);
		
		//Tarifs par conditionnement
		//Conditionnement 1
		if(!empty($line[36]) && $line[36] > 0)
			_add_condi($ATMdb,$line,$produit,35,36);
		
		//Conditionnement 2
		if(!empty($line[38]) && $line[38] > 0)
			_add_condi($ATMdb,$line,$produit,37,38);
		
		//Conditionnement 3
		if(!empty($line[40]) && $line[40] > 0)
			_add_condi($ATMdb,$line,$produit,39,40);
		
		/*
		 * Equitements (Flacons et lots)
		 */
		_add_equipement($ATMdb,$TGlobal,$line,$produit);
		
		$TGlobal['product'][$line[2]] = $produit->id;
	} else {
		continue;
	}
	
	$i++;
}
fclose($articlesfile);

/*
 * CLIENTS
 */
$line = fgetcsv($societesfile,0,'|',';');
while($line = fgetcsv($societesfile,0,'|',';')){
	if(empty($TGlobal['societe'][$line[5]]) && !empty($line[5])){
		$type = "client";
		$societe = _add_tiers($ATMdb,$user, $db, $line,$type);
		$TGlobal['societe'][$line[5]] = $societe->id;
	}
	else {
		continue;
	}
}
fclose($societesfile);

 /*
 * FOURNISSEURS
 */
$line = fgetcsv($fournisseursfile,0,'|',';');
while($line = fgetcsv($fournisseursfile,0,'|',';')){
	if(empty($TGlobal['fournisseur'][$line[0]]) && !empty($line[0])){
		$type = "fournisseur";
		$societe = _add_tiers($ATMdb,$user, $db, $line,$type);
		$TGlobal['fournisseur'][$line[0]] = $societe->id;
	}
	else {
		continue;
	}
}
fclose($fournisseursfile);