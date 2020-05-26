<?php
	// Fix TK11198 : pour ne pas renouveller le token CSRF à chaque inclusion du main.inc.php et Eviter l'erreur CSRF
	define('NOTOKENRENEWAL', 1);

	require('config.default.php');

	if(!defined('FACTURE_DISPATCH_ON_EXPEDITION'))
		define('FACTURE_DISPATCH_ON_EXPEDITION', $conf->global->TARIF_FACTURE_DISPATCH_ON_EXPEDITION); // TODO global dolibarr 0

	if(!defined('DONT_ADD_UNIT_SELECT'))
	    define('DONT_ADD_UNIT_SELECT', $conf->global->TARIF_DONT_ADD_UNIT_SELECT);	//default 1

	if(!defined('TARIF_DEFAULT_TYPE'))
		define('TARIF_DEFAULT_TYPE', $conf->global->TARIF_DEFAULT_TYPE); // PRICE

	if(!defined('DOL_DEFAULT_UNIT'))
	    define('DOL_DEFAULT_UNIT', $conf->global->TARIF_DOL_DEFAULT_UNIT ); //'unite'  //Définie l'unité de vente par défaut des lignes de propale, commande, facture : weight, size, surface, volume

	if(!defined('TARIF_DONT_USE_TVATX'))
		if(! empty($conf->global->TARIF_DONT_USE_TVATX))
			define('TARIF_DONT_USE_TVATX', $conf->global->TARIF_DONT_USE_TVATX); // Garder le taux de TVA déterminé par Dolibarr
