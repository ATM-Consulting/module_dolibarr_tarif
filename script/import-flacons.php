<?php

ini_set('memory_limit', '1024M');
set_time_limit(0);

require('../config.php');
//dol_include_once("/product/class/product.class.php");
dol_include_once("/product/class/product.class.php");
dol_include_once("/custom/tarif/class/tarif.class.php");
dol_include_once("/custom/asset/class/asset.class.php");
dol_include_once("/societe/class/societe.class.php");
dol_include_once("/contact/class/contact.class.php");
dol_include_once("/categories/class/categorie.class.php");

$ATMdb = new Tdb;
$flaconsfile = fopen('../import/flaconsOK.csv','r');

/*
 * TAB FLACON
 */
$flacon = fgetcsv($flaconsfile,0,'|','"');
while($TInfo = fgetcsv($flaconsfile,0,'|','"')){
	$emp = $TInfo[0];
	$numflacon = $TInfo[1];
	$refproduit = $TInfo[2];
	$lot = $TInfo[3];
	$tare = $TInfo[5];
	$poids = $TInfo[6];
	
	echo "Flacon ".$numflacon;
	
	$flacon = new TAsset();
	if($flacon->loadReference($ATMdb, $numflacon)) {
		$produit = new Product($db);
		$produit->fetch(0, $refproduit);
		if($produit->id == $flacon->fk_product) {
			$flacon->emplacement = $emp;
			$flacon->contenancereel_value = $poids;
			$flacon->lot_number = $lot;
			if(!empty($flacon->TStock[0])) {
				$flacon->TStock[0]->qty = $poids;
			}
			//$flacon->save($ATMdb);
			echo " OK";
		} else {
			echo " mauvais produit";
		}
		
	} else {
		echo " non trouvé";
	}
	
	echo "<br />";
	
	
	break;
}

