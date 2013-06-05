<?php

class TTarif extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'tarif_conditionnement');
		parent::add_champs('unite','type=chaine;');
		parent::add_champs('unite_value','type=entier;');
		parent::add_champs('price_base_type','type=chaine;');
		parent::add_champs('quantite','type=entier;');
		parent::add_champs('prix,tva_tx','type=float;');
		parent::add_champs('fk_user_author,fk_product','type=entier;index;');
		
		parent::_init_vars();
		parent::start();
	}
}

class TTarifCommandedet extends TObjetStdDolibarr {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'commandedet');
		parent::add_champs('poids','type=entier;');
		parent::add_champs('tarif_poids','type=float;');
		
		parent::_init_vars();
		parent::start();
	}
}

class TTarifPropaldet extends TObjetStdDolibarr {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'propaldet');
		parent::add_champs('poids','type=entier;');
		parent::add_champs('tarif_poids','type=float;');
		
		parent::_init_vars();
		parent::start();
	}
}

class TTarifFacturedet extends TObjetStdDolibarr {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'facturedet');
		parent::add_champs('poids','type=entier;');
		parent::add_champs('tarif_poids','type=float;');
		
		parent::_init_vars();
		parent::start();
	}
}