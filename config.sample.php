<?php

	require('config.default.php');

/**
 * Configuration spécifique au module
 */

	define('FACTURE_DISPATCH_ON_EXPEDITION',isset($conf->global->TARIF_FACTURE_DISPATCH_ON_EXPEDITION) ? $conf->global->TARIF_FACTURE_DISPATCH_ON_EXPEDITION : 1); // TODO global dolibarr
	define('DONT_ADD_UNIT_SELECT', isset($conf->global->TARIF_DONT_ADD_UNIT_SELECT) ? $conf->global->TARIF_DONT_ADD_UNIT_SELECT : 0);
	define('DOL_DEFAULT_UNIT',isset($conf->global->TARIF_DOL_DEFAULT_UNIT) ? $conf->global->TARIF_DOL_DEFAULT_UNIT : 'weight'); //Définie l'unité de vente par défaut des lignes de propale, commande, facture : weight, size, surface, volume
	