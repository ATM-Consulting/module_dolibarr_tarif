<?php

class TTarif extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'tarif_conditionnement');
		parent::add_champs('description','type=chaine;');
		parent::add_champs('prix,contenance','type=float;');
		parent::add_champs('entity,fk_user_author,fk_product','type=entier;index;');
		parent::add_champs('date','type=date;');
		
		parent::_init_vars();
		parent::start();
	}
}