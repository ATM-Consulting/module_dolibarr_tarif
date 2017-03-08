<?php

require '../config.php';

$sql = 'SELECT rowid, prix, quantite
		FROM '.MAIN_DB_PREFIX.'tarif_conditionnement_fournisseur
		WHERE fk_product='.GETPOST('idprod');

$resql = $db->query($sql);
$TRes = array();
while($res = $db->fetch_object($resql)) $TRes[$res->rowid] = $res->quantite.'kg : '.$res->prix.'€';

$form = new TFormCore;
if(empty($TRes)) $TRes = array(0=>'');
echo json_encode($form->combo($pLib,'fk_fourn_product_price',$TRes,GETPOST('selected')));