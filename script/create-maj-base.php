<?php
/*
 * Script créant et vérifiant que les champs requis s'ajoutent bien
 * 
 */
	if(!defined('INC_FROM_DOLIBARR')) {
		define('INC_FROM_CRON_SCRIPT', true);
		
		require('../config.php');
	
	}

	dol_include_once('/tarif/class/tarif.class.php');
	
	$PDOdb=new TPDOdb;
	//$PDOdb->debug=true;

	$o=new TTarif;
	$o->init_db_by_vars($PDOdb);

	$o=new TTarifFournisseur;
	$o->init_db_by_vars($PDOdb);
	
	$o=new TTarifCommandedet;
	$o->init_db_by_vars($PDOdb);
	
	$o=new TTarifPropaldet;
	$o->init_db_by_vars($PDOdb);
	
	$o=new TTarifFacturedet;
	$o->init_db_by_vars($PDOdb);
	
	$o=new TTarifCommandeFourndet;
	$o->init_db_by_vars($PDOdb);
	