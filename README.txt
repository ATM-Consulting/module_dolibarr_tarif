	∕∕ AJOUTER LE ROWID A L'OBJET EXPEDITION => expedition.class.php
	
	Ligne 884 => dans la requête du select pour le fetch_lines 
	
		$sql.= ", ed.rowid, ed.qty as qty_shipped, ed.fk_origin_line, ed.fk_entrepot";
	
	Ligne 913 => dans la boucle pour charger les objets lignes d'expédition en fonction des réusltats de la requête
	
		$line->rowid 			= $obj->rowid;
	
	Ligne 1360 => création d'une propriété rowid dans l'objet ExpeditionLigne 
	
		var $rowid;
		

	//AJOUTER LES MICRO-GRAMMES COMME UNITE DANS DOLIBARR 
	
	htdocs -> langs -> fr_FR -> other -> ajouter WeightUnitμg=μg
	htdocs -> core -> lib -> product.lib.php -> measuring_units_string -> ajouter $measuring_units[-9] = $langs->trans(""WeightUnitμg"");
	htdocs -> product -> class -> html.formproduct.class.php -> ajouter -9 => 1 dans le tableau if ($measuring_style == 'weight')"