<?php
	require('config.php');
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	
	llxHeader('','Liste des tarifs par conditionnement','','');
	
	if(is_file(DOL_DOCUMENT_ROOT."/lib/product.lib.php")) require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
	else require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
	
	require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
	
	$ATMdb = new Tdb;	
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
				<a class="butAction" href="?action=add&fk_product='.$object->id.'">Ajouter un conditionnement</a>
			</div><br>';
	
	/***********************************
	 * Traitements actions
	 ***********************************/
	if(isset($_REQUEST['action']) && !empty($_REQUEST['action']) && $_REQUEST['action'] == 'add'){
		
		print '<table class="notopnoleftnoright" width="100%" border="0" style="margin-bottom: 2px;" summary="">';
		print '<tbody><tr>';
		print '<td class="nobordernopadding" valign="middle"><div class="titre">Nouveau conditionnement</div></td>';
		print '</tr></tbody>';
		print '</table>';
		
		print '<form action="" method="POST">';
		print '<input type="hidden" name="action" value="add_conditionnement">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		print '<table class="border" width="100%">';

		print '<tr>';
		print '	<td>Intitul&eacute; :</td>';
		print '	<td><input type="text" name="intitule" size="40" maxlength="255" /></td>';
		print '</tr>';
		print '<tr>';
		print '	<td>Contenance :</td>';
		print '	<td><input type="text" name="contenance" size="15" maxlength="255" /> grammes</td>';
		print '</tr>';
		print '<tr>';
		print '	<td>Tarif :</td>';
		print '	<td><input type="text" name="tarif" size="15" maxlength="255" /> TTC</td>';
		print '</tr>';

		print '</table>';

		print '<center><br><input type="submit" class="button" value="'.$langs->trans("Save").'" name="save">&nbsp;';
		print '<input type="submit" class="button" value="Annuler" name="back"></center>';

		print '<br></form>';
	}
	elseif(isset($_REQUEST['action']) && !empty($_REQUEST['action']) && $_REQUEST['action'] == 'add_conditionnement' && isset($_REQUEST['save'])) {
		$Ttarif = new TTarif;
		$Ttarif->description = $_POST['intitule'];
		$Ttarif->prix = $_POST['tarif'];
		$Ttarif->fk_user_author = $user->id;
		$Ttarif->contenance = $_POST['contenance'];
		$Ttarif->fk_product = $_POST['id'];
		$Ttarif->save($ATMdb);
	}
	elseif(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']))
	{
		$Ttarif = new TTarif;
		$Ttarif->load($ATMdb,$_GET['id']);
		$Ttarif->delete($ATMdb);
	}

	
	/**********************************
	 * Liste des tarifs
	 **********************************/
	$TConditionnement = array();
	
	$sql = "SELECT rowid AS 'id', description AS description, contenance AS contenance, prix AS prix, '' AS 'Supprimer'
			FROM ".MAIN_DB_PREFIX."tarif_conditionnement
			WHERE fk_product = ".$object->id."
			ORDER BY rowid DESC";
			
	$ATMdb->Execute($sql);
	
	$r = new TSSRenderControler(new TTarif);
		
	print $r->liste($ATMdb, $sql, array(
		'limit'=>array('nbLine'=>1000)
		,'title'=>array(
			'description'=>'Conditionnement'
			,'contenance' => 'Contenance (grammes)'
			,'prix'=>'Tarif TTC'
			,'Supprimer' => 'Supprimer'
		)
		,'type'=>array('date_debut'=>'date','date_fin'=>'date')
		,'hide'=>array(
			'id'
		)
		,'link'=>array(
			'Supprimer'=>'<a href="?id=@id@&action=delete&fk_product='.$object->id.'"><img src="img/delete.png"></a>'
		)
	));
	?>
	<br>
	
	