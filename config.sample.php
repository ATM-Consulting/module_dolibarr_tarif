<?php

	require('config.default.php');

/**
 * Configuration spécifique au module
 */

	define('FACTURE_DISPATCH_ON_EXPEDITION',true);
	define('DONT_ADD_UNIT_SELECT', false);
	define('DOL_DEFAULT_UNIT','weight'); //Définie l'unité de vente par défaut des lignes de propale, commande, facture : weight, size, surface, volume
	
	