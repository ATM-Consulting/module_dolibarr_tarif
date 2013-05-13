<?php
/*
 * Script créant et vérifiant que les champs requis s'ajoutent bien
 * 
 */
 	define('INC_FROM_CRON_SCRIPT', true);
	
	require('../config.php');
	require('../class/tarif.class.php');

	$ATMdb=new TPDOdb;
	$ATMdb->debug=true;

	$o=new TTarif;
	$o->init_db_by_vars($ATMdb);
	
	