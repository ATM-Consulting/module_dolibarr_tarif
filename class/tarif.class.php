<?php

class TTarif extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'tarif_conditionnement');
		parent::add_champs('unite,price_base_type','type=chaine;');
		parent::add_champs('quantite','type=entier;');
		parent::add_champs('prix,tva_tx','type=float;');
		parent::add_champs('fk_user_author,fk_product','type=entier;index;');
		
		parent::_init_vars();
		parent::start();
	}
}