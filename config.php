<?php
	require('default.config.php');

	define('FACTURE_DISPATCH_ON_EXPEDITION', $conf->global->TARIF_FACTURE_DISPATCH_ON_EXPEDITION); // TODO global dolibarr 0
    define('DONT_ADD_UNIT_SELECT', $conf->global->TARIF_DONT_ADD_UNIT_SELECT);	//default 1
	define('TARIF_DEFAULT_TYPE', $conf->global->TARIF_DEFAULT_TYPE); // PRICE
    define('DOL_DEFAULT_UNIT', $conf->global->TARIF_DOL_DEFAULT_UNIT ); //'unite'  //Définie l'unité de vente par défaut des lignes de propale, commande, facture : weight, size, surface, volume
	define('TARIF_DONT_USE_TVATX', $conf->global->TARIF_DONT_USE_TVATX); // Garder le taux de TVA déterminé par Dolibarr