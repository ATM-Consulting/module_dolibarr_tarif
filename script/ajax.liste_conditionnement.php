<?php
require("../config.php");
require(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.product.class.php");

if(isset($_POST['fk_fourn_price'])){
	$FournPrice = new ProductFournisseur($db);
	$FournPrice->fetch_product_fournisseur_price($id);
	$id = $FournPrice->fk_product;
}
elseif(isset($_POST['fk_product'])){
	$id = $_POST['fk_product'];
}

$ATMdb = new TPDOdb;
$Tres = array();

$sql = "SELECT rowid AS id, description AS intitule, contenance, prix
		FROM ".MAIN_DB_PREFIX."tarif_conditionnement
		WHERE fk_product = ".$id."
		ORDER BY date_cre DESC";

$ATMdb->Execute($sql);

while($ATMdb->Get_line()){
	$Tres[] = array(
		"id" => $ATMdb->Get_field('id')
		,"intitule" => $ATMdb->Get_field('intitule')." - ".number_format($ATMdb->Get_field('contenance'),0,"."," ")." grammes (".number_format($ATMdb->Get_field('prix'),2)." euros)"
	);
}

echo json_encode($Tres);