<?php
/*
 * Script créant et vérifiant que les champs requis s'ajoutent bien
 */

if(!defined('INC_FROM_DOLIBARR')) {
	define('INC_FROM_CRON_SCRIPT', true);

	require('../config.php');
} else {
	global $db;
}


dol_include_once('/tarif/class/tarif.class.php');

$o=new Tarif($db);
$o->init_db_by_vars();


$o=new TarifLog($db);
$o->init_db_by_vars();

