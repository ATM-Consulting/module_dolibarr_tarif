<?php

	define('ROOT','/var/www/dolibarr/htdocs/');
	define('COREROOT','/var/www/ATM/atm-core/');
	define('COREHTTP','http://127.0.0.1/ATM/atm-core/');
	define('HTTP','http://localhost/dolibarr/');

	if(!defined('INC_FROM_DOLIBARR') && defined('INC_FROM_CRON_SCRIPT')) {
		include(ROOT."master.inc.php");
	}
	elseif(!defined('INC_FROM_DOLIBARR')) {
		include(ROOT."main.inc.php");
	} else {
		global $dolibarr_main_db_host, $dolibarr_main_db_name, $dolibarr_main_db_user, $dolibarr_main_db_pass;
	}
	
	if(!empty($dolibarr_main_db_host)) {
		define('DB_HOST',$dolibarr_main_db_host);
		define('DB_NAME',$dolibarr_main_db_name);
		define('DB_USER',$dolibarr_main_db_user);
		define('DB_PASS',$dolibarr_main_db_pass);
		define('DB_DRIVER','mysqli');
	}
	
	define('DOL_PACKAGE', true);
	define('USE_TBS', true);
	
	define('FACTURE_DISPATCH_ON_EXPEDITION',true);
	define('DONT_ADD_UNIT_SELECT', false);
	define('WEIGHT_LABEL','Poids');
	define('DOL_DEFAULT_UNIT','weight'); //Définie l'unité de vente par défaut des lignes de propale, commande, facture : weight, size, surface, volume
	
	require(COREROOT.'inc.core.php');
