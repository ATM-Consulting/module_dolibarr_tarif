<?php

require '../config.php';

$get=GETPOST('get','alpha');
$set=GETPOST('set','alpha');

switch ($get) {
	case 'unite_poids':
		__out( _getTUnitPoids() );
		break;
	case 'list_conditionnement':
		__out( _getTConditionnement() );
		break;

	default:
		break;
}

switch ($set) {
	default:
		break;
}


function _getTUnitPoids()
{
	global $db,$conf;

	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	include_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

	$id = GETPOST('fk_product','int');
	$type = GETPOST('type','alpha');

	$TRes = array(
		'unit' => ''
		,'poids' => ''
	);


	$sql = "SELECT unite_vente FROM ".MAIN_DB_PREFIX."product_extrafields WHERE fk_object = ". (int) $id;
	$resql = $db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		if ($obj) $unite = $obj->unite_vente;

		if ($unite == "size") $unite = "length";

		$sql = "SELECT ".$unite."_units, ".$unite.", fk_product_type
				FROM ".MAIN_DB_PREFIX."product
				WHERE rowid = ".$id;
		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj && $obj->fk_product_type == 0)
			{
				$weight_unit = $obj->{$unite.'_units'};
				$poids = $obj->{$unite};
				$TRes["unite"] = !is_null($weight_unit) ? $weight_unit : -3;
				$TRes["poids"] = !empty($poids) ? $poids : 1;
				if ($unite == "length") $unite = "size";
				$formproduct = new FormProduct($db);
				$TRes["unite_vente"] = $formproduct->load_measuring_units("weight_unitsAff_product", $unite) ;
			}
			elseif (!empty($conf->global->TARIF_KEEP_FIELD_CONDITIONNEMENT_FOR_SERVICES))
			{
				// Si c'est un service et que le module peinture et activé, et qu'on autorise l'affichage du champ conditionnement pour les services,
				// alors, on affiche le champ conditionnement ainsi que le lien vers le popin du métré
				$TRes['keep_field_cond'] = 1;
			}

		}
		else
		{
			dol_print_error($db);
			exit;
		}
	}
	else
	{
		dol_print_error($db);
		exit;
	}

	return $TRes;
}


function _getTConditionnement()
{
	global $db;

	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';

	$fk_fourn_price = GETPOST('fk_fourn_price','int');
	$fk_product = GETPOST('fk_product','int');

	if(!empty($fk_fourn_price))
	{
		$FournPrice = new ProductFournisseur($db);
		$FournPrice->fetch_product_fournisseur_price($fk_fourn_price);
		$id = $FournPrice->fk_product;
	}
	elseif (!empty($fk_product))
	{
		$id = $fk_product;
	}

	$ATMdb = new TPDOdb;
	$TRes = array();

	$sql = "SELECT rowid AS id, description AS intitule, contenance, prix
			FROM ".MAIN_DB_PREFIX."tarif_conditionnement
			WHERE fk_product = ".$id."
			ORDER BY date_cre DESC";

	$resql = $db->query($sql);
	if ($resql)
	{
		while($obj = $db->fetch_object($resql))
		{
			// TODO le formatage de l'attribut "intitule" devrait être un $langs->trans(key, val1, val2...)
			$TRes[] = array(
				"id" => $obj->id
				,"intitule" => $obj->intitule." - ".number_format($obj->contenance,0,"."," ")." grammes (".number_format($obj->prix,2)." euros)"
			);
		}
	}

	echo json_encode($TRes);
}
