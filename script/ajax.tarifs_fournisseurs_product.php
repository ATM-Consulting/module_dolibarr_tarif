<?php

require '../config.php';

$sql = 'SELECT rowid, prix, quantite
		FROM '.MAIN_DB_PREFIX.'tarif_conditionnement_fournisseur
		WHERE fk_product='.GETPOST('idprod');

$resql = $db->query($sql);
$TRes = array();
while($res = $db->fetch_object($resql)) $TRes[$res->rowid] = 'prix '.$res->prix.', qté '.$res->quantite;

$form = new TFormCore;
/*print '<select name="fk_fourn_product_price" id="coucou">

<option value="1">la</option>
</select>';*/
echo json_encode($form->combo($pLib,'fk_fourn_product_price',$TRes,$pDefault));
//echo json_encode($TRes);