<?php
require("../config.php");
require(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

$id = $_POST['fk_product'];

$ATMdb = new Tdb;
$Tres = array();

$sql = "SELECT weight_units, weight, fk_product_type
		FROM ".MAIN_DB_PREFIX."product
		WHERE rowid = ".$id;

$ATMdb->Execute($sql);
$ATMdb->Get_line();

$Tres["unite"] = '';
$Tres["poids"] = '';
if($ATMdb->Get_field('fk_product_type') == 0) { // On ne renvoie un poids que s'il s'agit d'un produit
	$Tres["unite"] = ($ATMdb->Get_field('weight_units')) ? $ATMdb->Get_field('weight_units') : -3;
	$Tres["poids"] = ($ATMdb->Get_field('weight')) ? $ATMdb->Get_field('weight') : 1;
}

echo json_encode($Tres);