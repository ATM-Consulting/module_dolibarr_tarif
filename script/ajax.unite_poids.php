<?php
require("../config.php");
require(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

$id = $_POST['fk_product'];

$ATMdb = new Tdb;
$Tres = array();

$sql = "SELECT weight_units
		FROM ".MAIN_DB_PREFIX."product
		WHERE rowid = ".$id;

$ATMdb->Execute($sql);

$ATMdb->Get_line();
$Tres["unite"] = $ATMdb->Get_field('weight_units');

echo json_encode($Tres);