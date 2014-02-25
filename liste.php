<?php
	require('config.php');
	require('class/tarif.class.php');
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	
	llxHeader('','Liste des tarifs par conditionnement','','');
	
	if(is_file(DOL_DOCUMENT_ROOT."/lib/product.lib.php")) require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
	else require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
	
	require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
	
	global $langs;
	
	$langs->load("other");
	
	$ATMdb = new Tdb;
	
	$ATMdb->Execute("SELECT unite_vente FROM ".MAIN_DB_PREFIX."product_extrafields WHERE fk_object = ".$_REQUEST['fk_product']);
	$ATMdb->Get_line();
	$type_unite = $ATMdb->Get_field('unite_vente');
	
	$TTarif = new TTarif;
	$product = new Product($db);
	$result=$product->fetch($_REQUEST['fk_product']);	
		
	$head=product_prepare_head($product, $user);
	$titre=$langs->trans("CardProduct".$product->type);
	$picto=($product->type==1?'service':'product');
	dol_fiche_head($head, 'tabTarif1', $titre, 0, $picto);
	
	$object = $product;
	$form = new Form($db);
	$formproduct = new FormProduct($db);
	
	/*$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	if (empty($reshook) && ! empty($extrafields->attribute_label))
	{
	      print $object->showOptionals($extrafields);
	}*/
	
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

		// VAT
        print '<tr><td width="20%">'.$langs->trans("VATRate").'</td><td>';
        print $form->load_tva("tva_tx",$object->tva_tx,$mysoc,'',$object->id,$object->tva_npr);
        print '</td></tr>';

		// Price base
		print '<tr><td width="20%">Base du prix</td>';
		print '<td>';
		//print $form->select_PriceBaseType($object->price_base_type, "price_base_type");
		print 'HT</td>';
		print '</tr>';
		
		print '<tr><td width="20%">Type de prix</td><td>';
        print $form->selectarray("type_prix",$TTarif->TType_price);
        print '</td></tr>';
        
        if($conf->multidevise->enabled){
	        //Devise
			print '<tr><td>Devise</td><td colspan="3">';
			print $form->select_currency($conf->currency,"currency");
			print '</td></tr>';
		}
		
		// Price
		print '<tr><td width="20%">';
		print 'Prix de vente';
		print '</td><td><input type="hidden" name="prix" id="prix" value="'.$object->price.'"><input size="10" name="prix_visu" value="'.number_format($object->price,2,",","").'"></td></tr>';
				
		// Remise
		print '<tr><td width="20%">';
		print 'Pourcentage de remise';
		print '</td><td><input value="" id="remise" size="10" name="remise">%</td></tr>';
		
		?>
			<script type="text/javascript">
			
				$('input[name=remise]').change(function() {
					var n_percent = $(this).val();
					var price = $('#prix').val();
					if(n_percent>100) {
						alert('Votre pourcentage doit être inférieur ou égal à cent');
						return false;
					}
					$('[name=prix_visu]').val(((100 - n_percent) * price / 100).toFixed(2));
				});			
				
				$('input[name=prix_visu]').change(function() {
					
					var n_price = parseFloat($(this).val());
					var price = parseFloat($('#prix').val());
					
					var percent = - (((n_price - price) / price) *100 );
					
					$('#remise').val(percent.toFixed(0));
					
				});
				
			</script>
		<?				
		
		//Quantité
		print '<tr><td width="20%">';
		print 'Quantit&eacute;';
		print '</td><td><input value="" size="10" name="quantite"></td></tr>';
		
		print '<tr><td width="20%">';
		print 'Unit&eacute;';
		print '</td><td>';
		print $formproduct->select_measuring_units("weight_units", $type_unite, $object->{$type_unite.'_units'});
		print '</td></tr>';

		print '</table>';

		print '<center><br><input type="submit" class="button" value="'.$langs->trans("Save").'" name="save">&nbsp;';
		print '<input type="submit" class="button" value="Annuler" name="back"></center>';

		print '<br></form>';
	}
	elseif(isset($_REQUEST['action']) && !empty($_REQUEST['action']) && $_REQUEST['action'] == 'add_conditionnement' && isset($_REQUEST['save'])) {
		
		if($type_unite == "size") $type_unite = "length"; //Pout la longeur le nom du champ diffère....
		$unite = measuring_units_string($_REQUEST['weight_units'],$type_unite);
		$unite = $langs->trans($unite);
		
		$Ttarif = new TTarif;
		$Ttarif->tva_tx = $_POST['tva_tx'];
		$Ttarif->price_base_type = 'HT';
		$Ttarif->fk_user_author = $user->id;
		$Ttarif->type_price = $_REQUEST['type_prix'];
		$Ttarif->currency_code = $_REQUEST['currency'];
		
		if($_REQUEST['type_prix'] == 'PRICE'){
			
			$Ttarif->prix = number_format(str_replace(",", ".", $_POST['prix_visu']),2,".","");
			
		}
		else{
			$Ttarif->prix = number_format(str_replace(",", ".", $_POST['prix']),2,".","");
			(isset($_POST['remise']) && !empty($_POST['remise'])) ? $Ttarif->remise_percent = $_POST['remise'] : "" ;
		}
		$Ttarif->quantite = $_POST['quantite'];
		//$Ttarif->quantite =  number_format(str_replace(",", ".", $_POST['quantite']),2,".","");
		$Ttarif->unite = $unite;
		
		$Ttarif->unite_value = $_POST['weight_units'];
		$Ttarif->fk_product = $_POST['id'];
		$Ttarif->save($ATMdb);
	}
	elseif(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']))
	{
		$Ttarif = new TTarif;
		$Ttarif->load($ATMdb,$_GET['id']);
		$Ttarif->delete($ATMdb);
	}
	
	
	if($type_unite == "size") $type_unite = "length"; //Pout la longeur le nom du champ diffère....
	
	/**********************************
	 * Liste des tarifs
	 **********************************/
	$TConditionnement = array();
	
	$sql = "SELECT tc.rowid AS 'id', tc.tva_tx AS tva, tc.type_price as type_price, c.code as currency, tc.price_base_type AS base, tc.quantite as quantite,
				   tc.unite AS unite, tc.remise_percent AS remise, tc.prix AS prix, p.".$type_unite."_units AS base_poids, tc.unite_value AS unite_value,
				   ((tc.quantite * POWER(10,(tc.unite_value-p.".$type_unite."_units))) * tc.prix) - ((tc.quantite * POWER(10,(tc.unite_value-p.".$type_unite."_units))) * tc.prix) * (tc.remise_percent/100)  AS 'Total',
				   '' AS 'Supprimer'
			FROM ".MAIN_DB_PREFIX."tarif_conditionnement AS tc
				LEFT JOIN ".MAIN_DB_PREFIX."product AS p ON (tc.fk_product = p.rowid)
				LEFT JOIN ".MAIN_DB_PREFIX."currency AS c ON (c.code = tc.currency_code)
			WHERE fk_product = ".$product->id."
			ORDER BY unite_value, quantite ASC";
	
	$r = new TSSRenderControler(new TTarif);
	
	$THide = array(
			'id'
			,'base_poids'
			,'unite_value'
			,'tva'
			,'base'
		);
		
	if(!$conf->multidevise->enabled) $THide[] = 'currency';
	
	print $r->liste($ATMdb, $sql, array(
		'limit'=>array('nbLine'=>1000)
		,'title'=>array(
			'tva'=>'Taux TVA'
			,'base' => 'Base du Prix'
			,'quantite'=>'Quantit&eacute'
			,'currency'=>'Devise'
			,'type_price' => 'Type de prix'
			,'unite'=>'Unit&eacute;'
			,'prix'=>'Tarif'
			,'remise' => 'Remise (%)'
			,'Total' => 'Total'
			,'Supprimer' => 'Supprimer'
		)
		,'type'=>array('date_debut'=>'date','date_fin'=>'date','tva' => 'number', 'prix'=>'money', 'Total' => 'money' , 'quantite' => 'number')
		,'hide'=> $THide
		,'link'=>array(
			'Supprimer'=>'<a href="?id=@id@&action=delete&fk_product='.$object->id.'" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer ce conditionnement?\');"><img src="img/delete.png"></a>'
		)
		,'eval'=>array(
			'type_price'=>'_getTypePrice(@id@)'
		)
	));
	
	
	function _getTypePrice($idPriceCondi){
		$TPDOdb = new TPDOdb;
		
		$TTarif = new TTarif;
		$TTarif->load($TPDOdb, $idPriceCondi);
		
		return $TTarif->TType_price[$TTarif->type_price];
	}
	
	
	?>
	<br>
	
	