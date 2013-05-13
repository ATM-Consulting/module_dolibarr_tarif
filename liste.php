<?php
	require('config.php');
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	
	llxHeader('','Liste des tarifs par conditionnement','','');
	
	if(is_file(DOL_DOCUMENT_ROOT."/lib/product.lib.php")) require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
	else require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
	
	require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
		
	$product = new Product($db);
	$result=$product->fetch($_REQUEST['fk_product']);	
		
	$head=product_prepare_head($product, $user);
	$titre=$langs->trans("CardProduct".$product->type);
	$picto=($product->type==1?'service':'product');
	dol_fiche_head($head, 'tabTarif1', $titre, 0, $picto);
	
	$object = $product;
	$form = new Form($db);
	
	print '<table class="border" width="100%">';
	
	// Ref
	print '<tr>';
	print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="2">';
	print $form->showrefnav($object,'fk_product','',1,'fk_product');
	print '</td>';
	print '</tr>';
	
	// Label
	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->libelle.'</td>';
	print '</tr>';
	// TVA
    print '<tr><td>'.$langs->trans("VATRate").'</td><td>'.vatrate($object->tva_tx.($object->tva_npr?'*':''),true).'</td></tr>';

    // Price
	print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>';
	if ($object->price_base_type == 'TTC')
	{
		print price($object->price_ttc).' '.$langs->trans($object->price_base_type);
	}
	else
	{
		print price($object->price).' '.$langs->trans($object->price_base_type);
	}
	print '</td></tr>';
	
	// Price minimum
	print '<tr><td>'.$langs->trans("MinPrice").'</td><td>';
	if ($object->price_base_type == 'TTC')
	{
		print price($object->price_min_ttc).' '.$langs->trans($object->price_base_type);
	}
	else
	{
		print price($object->price_min).' '.$langs->trans($object->price_base_type);
	}
	print '</td></tr>';
	
	// Status (to sell)
	print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td>';
	print $object->getLibStatut(2,0);
	print '</td></tr>';
	
	print "</table>\n";
	
	print "</div>\n";
	
	print '<div class="tabsAction">
				<a class="butAction" href="'.DOL_DOCUMENT_ROOT.'/tarif/liste.php?action=add&fk_product='.$object->id.'">Ajouter un conditionnement</a>
			</div>';
	
	/***********************************
	 * Traitements actions
	 ***********************************/
	if(isset($_REQUEST['action']) && !empty($_REQUEST['action']) && $_REQUEST['action'] == 'add'){
		print '<form action="'.DOL_DOCUMENT_ROOT.'/product/liste.php?action=add&fk_product='.$object->id.'" method="POST">';
		print '<input type="hidden" name="action" value="add_conditionnement">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		print '<table class="border" width="100%">';

        // Conditionnement
        print '<tr><td>Intitul&eacute; :</td><td>';
        print $form->text("tva_tx",$object->tva_tx,$mysoc,'',$object->id,$object->tva_npr);
        print '</td></tr>';

		// Contenance
		print '<tr><td width="15%">';
		print $langs->trans('PriceBase');
		print '</td>';
		print '<td>';
		print $form->select_PriceBaseType($object->price_base_type, "price_base_type");
		print '</td>';
		print '</tr>';

		// Prix
		print '<tr><td width="20%">';
		$text=$langs->trans('SellingPrice');
		print $form->textwithtooltip($text,$langs->trans("PrecisionUnitIsLimitedToXDecimals",$conf->global->MAIN_MAX_DECIMALS_UNIT),1,1);
		print '</td><td>';
		if ($object->price_base_type == 'TTC')
		{
			print '<input name="price" size="10" value="'.price($object->price_ttc).'">';
		}
		else
		{
			print '<input name="price" size="10" value="'.price($object->price).'">';
		}
		print '</td></tr>';

		print '</table>';

		print '<center><br><input type="submit" class="button" value="'.$langs->trans("Save").'">&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></center>';

		print '<br></form>';
	}
	
	/**********************************
	 * Liste des tarifs
	 **********************************/
	
	$ATMdb = new Tdb;
	$TConditionnement = array();
	
	$sql = "SELECT rowid AS id, description AS description, contenance AS contenance, prix AS prix
			FROM ".MAIN_DB_PREFIX."tarif_conditionnement
			WHERE fk_product = ".$object->rowid."
			ORDER BY date DESC";
			
	$ATMdb->Execute($sql);
	
	while($ATMdb->Get_line()){
		$Tligne["id"] = $ATMdb->Get_field('id');
		$Tligne["description"] = $ATMdb->Get_field('description');
		$Tligne["contenance"] = $ATMdb->Get_field('contenance');
		$Tligne["prix"] = $ATMdb->Get_field('prix');
		
		$TConditionnement[] = $Tligne;
	}
	
	$r = new TListviewTBS('liste_tarif_conditionnement', ROOT.'custom/tarif/tpl/html.list.tbs.php');
		
	print $r->renderArray($ATMdb, $TSurvey, array(
		'limit'=>array('nbLine'=>1000)
		,'title'=>array(
			'description'=>'Conditionnement'
			,'contenance' => 'Contenance'
			,'prix'=>'Tarif'
		)
		,'type'=>array('date_debut'=>'date','date_fin'=>'date')
		,'hide'=>array(
			'id'
		)
	));
	?>
	<br>
	
	