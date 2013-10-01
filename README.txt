	∕∕ AJOUTER LE ROWID A L'OBJET EXPEDITION => expedition.class.php
	
	Ligne 884 => dans la requête du select pour le fetch_lines 
	
		$sql.= ", ed.rowid, ed.qty as qty_shipped, ed.fk_origin_line, ed.fk_entrepot";
	
	Ligne 913 => dans la boucle pour charger les objets lignes d'expédition en fonction des réusltats de la requête
	
		$line->rowid 			= $obj->rowid;
	
	Ligne 1360 => création d'une propriété rowid dans l'objet ExpeditionLigne 
	
		var $rowid;