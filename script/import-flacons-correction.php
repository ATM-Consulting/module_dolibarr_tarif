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
$TInfo = fgetcsv($flaconsfile,0,'|','"');
$TNumFlacon = array();
while($TInfo = fgetcsv($flaconsfile,0,'|','"')){
	
	$numflacon = $TInfo[1];
	$TNumFlacon[] = $numflacon;
}

$flaconsfile = fopen('../import/flaconsOK2.csv','r');

/*
 * TAB FLACON
 */
$TInfo = fgetcsv($flaconsfile,0,'|','"');
$TNumFlacon = array();
while($TInfo = fgetcsv($flaconsfile,0,'|','"')){
	
	$numflacon = $TInfo[1];
	$TNumFlacon[] = $numflacon;
}

/*
 * RECUP FLACON EXISTANTS
 */
$sql = "SELECT rowid FROM llx_asset";
$TAsset = TRequeteCore::get_id_from_what_you_want($ATMdb, 'llx_asset');
$cpt = 0;
foreach($TAsset as $i => $idasset) {
	$flacon = new TAsset();
	$flacon->load($ATMdb, $idasset);
	
	if(in_array($flacon->serial_number, $TNumFlacon) === false && $flacon->date_cre == $flacon->date_maj) {
		echo $i.' flacon '.$flacon->serial_number.' - '.$flacon->fk_product.' : delete<br />';
		$flacon->delete($ATMdb);
		$cpt++;
	}
}
echo $cpt;