<?php

ini_set('memory_limit', '512M');
set_time_limit(0);

require('../config.php');
//dol_include_once("/product/class/product.class.php");
dol_include_once("/product/class/product.class.php");
dol_include_once("/custom/tarif/class/tarif.class.php");
dol_include_once("/custom/asset/class/asset.class.php");

$ATMdb = new Tdb; 
$articlesfile = fopen('../import/produits.csv', 'r');
$flaconsfile = fopen('../import/flacons.csv','r');
$lotsfile = fopen('../import/lots.csv','r');
$unitesfile = fopen('../import/unites.csv','r');
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

//Création de tableau intermédiaire
//Pour optimisation du traitement

//Unites
$unite = fgetcsv($unitesfile,0,'|',';');
while($unite = fgetcsv($unitesfile,0,'|',';')){
	$TGlobal['unite'][$unite[0]] = $unite[1];
}

//Flacons
$flacon = fgetcsv($flaconsfile,0,'|',';');
while($flacon = fgetcsv($flaconsfile,0,'|',';')){
	$TGlobal['flacon'][$flacon[1]] = $flacon[8]; // TGlobal['flacon']['ref_flacon'] = id_produit;
}

//Lots
$lot = fgetcsv($lotsfile,0,'|',';');
while($lot = fgetcsv($lotsfile,0,'|',';')){
	$TGlobal['lot'][$lot[2]] = array('ref_produit'=>$lot[1],'quantite'=>$lot[12]); // TGlobal['lot']['ref_lot'] = array(id_produit,quantite);
}

/*echo '<pre>';
print_r($TGlobal);
echo '</pre>';
exit;*/

$line = fgetcsv($articlesfile,0,'|',';');
while($line = fgetcsv($articlesfile,0,'|',';')){
	if(empty($TGlobal['product'][$line[2]]) && !empty($line[2])) { // Création du produit la première fois que l'on a la référence
		echo "<hr>$i - $line[2] - $line[3]<br>";
	
		$produit = new Product($db);
		$produit->ref = $line[2];
		$produit->libelle = $line[3];
		$produit->description = "";
		
		$produit->price_base_type = 'TTC';
		$produit->price_ttc = 0;
		$produit->tva_tx = '19.6';
		
		$produit->type= 0;
		$produit->status = 1;
		$produit->status_buy = 1;
		$produit->finished = 1;
		
		$produit->create($user);
		
		//Tarifs par conditionnement
		//Conditionnement 1
		if(!empty($line[36]) && $line[36] > 0)
		{
			$string_unite = explode(" ", $line[35]);
			
			$tarif = new TTarif;
			$tarif->unite_value = _unit($string_unite[1]);
			$tarif->unite = $string_unite[1];
			$tarif->quantite = $string_unite[0];
			$tarif->price_base_type = "HT";
			$tarif->fk_product = $produit->id;
			$tarif->fk_user_author = 1;
			$tarif->tva_tx = 19.6;
			$tarif->remise_percent = 0;
			$tarif->prix = $line[36];
			//$tarif->save($ATMdb);
			echo "$string_unite[0] "._unit($string_unite[1])." ";
		}
		
		//Conditionnement 2
		if(!empty($line[38]) && $line[38] > 0)
		{
			$string_unite = explode(" ", $line[37]);
			
			$tarif = new TTarif;
			$tarif->unite_value = _unit($string_unite[1]);
			$tarif->unite = $string_unite[1];
			$tarif->quantite = $string_unite[0];
			$tarif->price_base_type = "HT";
			$tarif->fk_product = $produit->id;
			$tarif->fk_user_author = 1;
			$tarif->tva_tx = 19.6;
			$tarif->remise_percent = 0;
			$tarif->prix = $line[38];
			$tarif->save($ATMdb);
			echo "$string_unite[0] "._unit($string_unite[1])." ";
		}
		
		//Conditionnement 3
		if(!empty($line[40]) && $line[40] > 0)
		{
			$string_unite = explode(" ", $line[39]);
			
			$tarif = new TTarif;
			$tarif->unite_value = _unit($string_unite[1]);
			$tarif->unite = $string_unite[1];
			$tarif->quantite = $string_unite[0];
			$tarif->price_base_type = "HT";
			$tarif->fk_product = $produit->id;
			$tarif->fk_user_author = 1;
			$tarif->tva_tx = 19.6;
			$tarif->remise_percent = 0;
			$tarif->prix = $line[40];
			$tarif->save($ATMdb);
			echo "$string_unite[0] "._unit($string_unite[1])."<br>";
		}
		
		//Equitements (Flacons et lots)
		foreach($TGlobal['lot'] as $ref_lot=>$Tinfos_lot){
			if($Tinfos_lot['ref_produit'] == $line[0]){
				$equipement = new TAsset;
				$equipement->fk_product = $produit->id;
				$equipement->entity = 0;
				$equipement->lot_number = $ref_lot;
				$equipement->contenance_value = $Tinfos_lot['quantite'];
				$equipement->contenancereel_value = $Tinfos_lot['quantite'];
				$equipement->contenance_units = _unit($TGlobal['unite'][$line[8]]);
				$equipement->contenancereel_units = _unit($TGlobal['unite'][$line[8]]);
				
				echo $ref_lot." ".$Tinfos_lot['quantite']." "._unit($TGlobal['unite'][$line[8]])." ";
				
				foreach($TGlobal['flacon'] as $ref_flacon=>$flacon_ref_produit){
					if($flacon_ref_produit == $line[0]){
						$equipement->serial_number = $ref_flacon;
						echo "$ref_flacon<br>";
						break;
					}
				}
				
				$equipement->save($ATMdb);
				break;
			}
		}
		
		$TGlobal['product'][$line[2]] = $produit->id;
	} else {
		continue;
	}
	
	$i++;
}

fclose($articlesfile);
