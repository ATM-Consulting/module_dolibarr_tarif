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
$flaconsfile = fopen('../import/flaconsVirtuels.csv','r');

/*
 * TAB FLACON
 */
$TInfo = fgetcsv($flaconsfile,0,';','"');
while($TInfo = fgetcsv($flaconsfile,0,';','"')){
	$emp = $TInfo[0];
	$numflacon = $TInfo[1];
	$refproduit = $TInfo[2];
	$lot = $TInfo[3];
	$tare = $TInfo[5];
	$poids = price2num($TInfo[6]);
	
	echo "Flacon ".$numflacon;
	
	$flacon = new TAsset();
	if($flacon->loadReference($ATMdb, $numflacon)) {
		echo ' existe deja';
	} else {
		$produit = new Product($db);
		if($produit->fetch(0, $refproduit)) {
			$flacon->fk_product = $produit->id;
			$flacon->serial_number = $numflacon;
			$flacon->emplacement = $emp;
			$flacon->contenancereel_value = $poids;
			$flacon->lot_number = $lot;
			$flacon->contenancereel_units = 100; // 'unit'
			
			$flacon->save($ATMdb, $user, 'Stock initial');
			echo ' => OK';
		} else {
			echo ' produit '.$refproduit.' KO';
		}
	}
	
	echo "<br />";
}

