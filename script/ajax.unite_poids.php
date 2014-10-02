<?php
require("../config.php");
require(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
include_once(DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php');
include_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");

global $db;

$id = $_REQUEST['fk_product'];
$type = $_REQUEST['type'];

$ATMdb = new TPDOdb;
$Tres = array();
$formproduct = new FormProduct($db);

if($type == 'idprodfournprice') {
	$sql = "SELECT fk_product FROM ".MAIN_DB_PREFIX."product_fournisseur_price pfp ";
	$sql.= "WHERE pfp.rowid = ".$id;
	
	$ATMdb->Execute($sql);
	$ATMdb->Get_line();
	$id = $ATMdb->Get_field('fk_product');
}

$sql = "SELECT unite_vente FROM ".MAIN_DB_PREFIX."product_extrafields WHERE fk_object = ".$id;

$ATMdb->Execute($sql);
$ATMdb->Get_line();
//echo $sql;
$unite = $ATMdb->Get_field('unite_vente');

if($unite == "size") $unite = "length";

$sql = "SELECT ".$unite."_units, ".$unite.", fk_product_type
		FROM ".MAIN_DB_PREFIX."product
		WHERE rowid = ".$id;

$ATMdb->Execute($sql);
$ATMdb->Get_line();

$Tres["unite"] = '';
$Tres["poids"] = '';
if($ATMdb->Get_field('fk_product_type') == 0) { // On ne renvoie un poids que s'il s'agit d'un produit
	$weight_unit = $ATMdb->Get_field($unite.'_units');
	$poids = $ATMdb->Get_field($unite);
	$Tres["unite"] = !is_null($weight_unit) ? $weight_unit : -3;
	$Tres["poids"] = !empty($poids) ? $poids : 1;
	if($unite == "length") $unite = "size";
	$Tres["unite_vente"] = $formproduct->load_measuring_units("weight_unitsAff_product", $unite) ;
}

echo json_encode($Tres);
