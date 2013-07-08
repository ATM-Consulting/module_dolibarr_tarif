<?php

ini_set('memory_limit', '512M');
set_time_limit(0);

require('../config.php');
//dol_include_once("/product/class/product.class.php");
dol_include_once("/product/class/product.class.php");

$articlesfile = fopen('../import/produits.csv', 'r');
$TGlobal = array();
$i = 0;

while($line = fgetcsv($articlesfile,0,';','|')){
	if(empty($TGlobal['product'][$line[3]]) && !empty($line[3])) { // Création du produit la première fois que l'on a la référence
		$produit = new Product($db);
		$produit->ref = $line[3];
		$produit->libelle = $line[4];
		$produit->description = "";
		
		$produit->price_base_type = 'TTC';
		$produit->price_ttc = 0;
		$produit->tva_tx = '19.6';
		
		$produit->type= 0;
		$produit->status = 1;
		$produit->status_buy = 1;
		$produit->finished = 1;
		
		$produit->create($user);
		
		$TGlobal['product'][$line[3]] = $produit->id;
	} else {
		continue;
	}
	
	$i++;
	echo "<hr>$i - $line[3] - $line[4]<hr>";
}

fclose($articlesfile);
