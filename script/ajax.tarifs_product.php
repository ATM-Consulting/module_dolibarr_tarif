<?php

require '../config.php';

$type = GETPOST('type');

if(empty($type) || $type === 'fournisseur') $table = 'tarif_conditionnement_fournisseur';
elseif($type === 'client') $table = 'tarif_conditionnement';

$sql = 'SELECT u.short_label, t.rowid, t.prix, t.quantite
		FROM '.MAIN_DB_PREFIX.$table.' t
		LEFT JOIN '.MAIN_DB_PREFIX.'c_units u ON (t.unite = u.rowid)
		WHERE fk_product='.GETPOST('idprod');

$resql = $db->query($sql);
$TRes = array();
while($res = $db->fetch_object($resql)) $TRes[$res->rowid] = $res->quantite.$res->short_label.' : '.$res->prix.'€';

$form = new TFormCore;
if(empty($TRes)) $TRes = array(0=>'');
echo json_encode($form->combo($pLib,'fk_tarif',$TRes,GETPOST('selected')));