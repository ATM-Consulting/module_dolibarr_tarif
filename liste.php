<?php
	require('config.php');
	require('class/tarif.class.php');
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	
	if(is_file(DOL_DOCUMENT_ROOT."/lib/product.lib.php")) require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
	else require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
	
	require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
	dol_include_once('/categories/class/categorie.class.php');
	
	global $langs;
	
	$langs->Load("other");
	$langs->Load("tarif@tarif");
	
	//pre($langs);exit;
	
	llxHeader('',$langs->trans('TarifList'),'','');
	
	$ATMdb = new TPDOdb;
	
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
				<a class="butAction" href="?action=add&fk_product='.$object->id.'">'.$langs->trans('AddTarif').'</a>
			</div><br>';
	
	/***********************************
	 * Traitements actions
	 ***********************************/
	 
	$action=__get('action','list');
	 
	if(isset($_REQUEST['action']) && !empty($_REQUEST['action']) && ($action == 'add' || $action == 'edit' )){
		
		$tarif = new TTarif;
		$tarif->type_price = defined('TARIF_DEFAULT_TYPE') ? TARIF_DEFAULT_TYPE : '';
		if($action=='edit') $tarif->load($ATMdb, __get('id',0,'integer'));

		print '<table class="notopnoleftnoright" width="100%" border="0" style="margin-bottom: 2px;" summary="">';
		print '<tbody><tr>';
		print '<td class="nobordernopadding" valign="middle"><div class="titre">Nouveau conditionnement</div></td>';
		print '</tr></tbody>';
		print '</table>';
		
		print '<form action="" method="POST">';
		print '<input type="hidden" name="action" value="add_conditionnement">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		print '<input type="hidden" name="id_tarif" value="'.$tarif->getId().'">';
		print '<table class="border" width="100%">';

		// VAT
        print '<tr><td width="20%">'.$langs->trans("VATRate").'</td><td>';
        print $form->load_tva("tva_tx", ($action=='edit') ? $tarif->tva_tx : $object->tva_tx,$mysoc,'',$object->id,$object->tva_npr);
        print '</td></tr>';

		// Price base
		print '<tr><td width="20%">'.$langs->trans('PriceBase').'</td>';
		print '<td>';
		//print $form->select_PriceBaseType($object->price_base_type, "price_base_type");
		print 'HT</td>';
		print '</tr>';
		
		print '<tr><td width="20%">'.$langs->trans('PriceType').'</td><td>';
        print $form->selectarray("type_prix",$TTarif->TType_price,$tarif->type_price);
        print '</td></tr>';
        
        if($conf->multidevise->enabled){
	        //Devise
			print '<tr><td>'.$langs->trans('Devise').'</td><td colspan="3">';
			print $form->selectCurrency( ($action=='edit') ? $tarif->currency_code : $conf->currency,"currency");
			print '</td></tr>';
			
		}

        //Pays
		print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
		print $form->select_country( ($action=='edit') ? $tarif->fk_country : 0,"fk_country");
		print '</td></tr>';
		
		print '<tr><td>'.$langs->trans('CategoriesCustomer').'</td><td colspan="3">';
		print $form->select_all_categories(2, ($action=='edit') ? $tarif->fk_categorie_client : 'auto', 'fk_categorie_client');
		print '</td></tr>';
		
		$prix = ( ($action=='edit') ? $tarif->prix :$object->price);
		// Price
		print '<tr><td width="20%">';
		print $langs->trans('SellingPrice');
		print '</td><td>
		<input type="hidden" name="prix" id="prix" value="'.$prix.'">
		<input size="10" name="prix_visu" value="'.number_format($prix,2,",","").'"></td></tr>';
		
		$remise = $tarif->remise_percent;		
		// Remise
		print '<tr><td width="20%">';
		print $langs->trans('Remise');
		print '</td><td><input id="remise" size="10" name="remise" value="'.$remise.'" />%</td></tr>';
		
		?>
			<script type="text/javascript">
			
				$('input[name=remise]').change(function() {
					var n_percent = $(this).val();
					var price = $('#prix').val();
					if(n_percent>100 || n_percent<0) {
						alert('<?php echo $langs->trans('Remise'); ?>');
						return false;
					}
					if($('#type_prix').val() != 'PERCENT/PRICE') {
						$('[name=prix_visu]').val(((100 - n_percent) * price / 100).toFixed(2));
					}
				});			
				
				$('input[name=prix_visu]').change(function() {
					if($('#type_prix').val() != 'PERCENT/PRICE') {
						var n_price = parseFloat($(this).val());
						var price = parseFloat($('#prix').val());
						
						var percent = - (((n_price - price) / price) *100 );
						
						$('#remise').val(percent.toFixed(0));
						
					}
				});
				
						
				
			</script>
		<?				
		
		//Quantité
		print '<tr><td width="20%">';
		print $langs->trans('Quantity');
		print '</td><td><input size="10" name="quantite" value="'.__val($tarif->quantite,1,'double',true).'"></td></tr>';
		print '<tr><td width="20%">';
		print $langs->trans('Unit');
		print '</td><td>';
		if($type_unite=='unite') print 'U';
		else print $formproduct->select_measuring_units("weight_units", $type_unite, ($action=='edit') ? $tarif->unite_value : $object->{$type_unite.'_units'});
		print '</td></tr>';

		print '</table>';

		print '<center><br><input type="submit" class="button" value="'.$langs->trans("Save").'" name="save">&nbsp;';
		print '<input type="submit" class="button" value="Annuler" name="back"></center>';

		print '<br></form>';
	}
	elseif(isset($_REQUEST['action']) && !empty($_REQUEST['action']) && $_REQUEST['action'] == 'add_conditionnement' && isset($_REQUEST['save'])) {
		
		//pre($_REQUEST,true);
		
		//logueur, poids, etc
		if(!empty($_REQUEST['weight_units'])){
			$unite = measuring_units_string($_REQUEST['weight_units'],$type_unite);
			$unite = $langs->trans($unite);
		}
		else{ //unitaire
			$unite = 'U';
		}
		
		$Ttarif = new TTarif;
		
		if($_REQUEST['id_tarif']>0) $Ttarif->load($ATMdb, $_REQUEST['id_tarif']);
		//pre($Ttarif,true);exit;
		
		$Ttarif->tva_tx = $_POST['tva_tx'];
		$Ttarif->price_base_type = 'HT';
		$Ttarif->fk_user_author = $user->id;
		$Ttarif->type_price = $_REQUEST['type_prix'];
		$Ttarif->currency_code = $_REQUEST['currency'];
		$Ttarif->fk_country = $_REQUEST['fk_country'];		
		
		if($_REQUEST['type_prix'] == 'PERCENT/PRICE'){
			$Ttarif->prix = price2num($_POST['prix_visu'],2,1);
			$Ttarif->remise_percent = __get('remise',0,'float');
		}
		else if($_REQUEST['type_prix'] == 'PRICE'){
			
			$Ttarif->prix =price2num($_POST['prix_visu'],2,1);

		}
		else{
			$Ttarif->prix = price2num($_POST['prix_visu'],2,1);
			$Ttarif->remise_percent = __get('remise',0,'float') ;
		}
		$Ttarif->quantite = $_POST['quantite'];
		//$Ttarif->quantite =  number_format(str_replace(",", ".", $_POST['quantite']),2,".","");
		$Ttarif->unite = $unite;
		
		$Ttarif->unite_value = $_POST['weight_units'];
		$Ttarif->fk_product = $_POST['id'];
		$Ttarif->fk_categorie_client = $_REQUEST['fk_categorie_client'];
		//$ATMdb->db->debug=true;
			
		//pre($Ttarif,true);exit;
		$Ttarif->save($ATMdb);
	}
	elseif(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']))
	{
		$Ttarif = new TTarif;
		$Ttarif->load($ATMdb,$_GET['id']);
		$Ttarif->delete($ATMdb);
	}
	
	
	if($type_unite == "size") $type_unite = "length"; //Pout la longeur le nom du champ diffère....
	
	if(empty($type_unite)) $type_unite = 'weight';
	
	/**********************************
	 * Liste des tarifs
	 **********************************/
	$TConditionnement = array();
	
	if($conf->multidevise->enabled){

		$sql = "SELECT tc.rowid AS 'id', tc.type_price as type_price, pays.libelle as 'Pays', cat.label as 'Catégorie',
					   tc.price_base_type AS base, tc.quantite as quantite,
					   tc.unite AS unite, tc.remise_percent AS remise, tc.tva_tx AS tva, tc.prix AS prix ";
		
		if($type_unite == "unite") {

			$sql.=" ,tc.unite_value AS unite_value,
				tc.quantite * tc.prix";
			if($Ttarif->remise_percent){
				$sql .= "* (100-tc.remise_percent)/100";
			} 
			$sql .=	"  AS 'Total'";
		
		}
		else {
			
			$sql.=" , p.".$type_unite."_units AS base_poids, tc.unite_value AS unite_value,
				((tc.quantite * POWER(10,(tc.unite_value-p.".$type_unite."_units))) * tc.prix) - ((tc.quantite * POWER(10,(tc.unite_value-p.".$type_unite."_units))) * tc.prix)";
			if($Ttarif->remise_percent){
				$sql .=  	  "* (tc.remise_percent/100)";
			}
			$sql .=	  " AS 'Total'";

		}
					   
		$sql.= " , c.code as currency, '' AS 'Actions'
				FROM ".MAIN_DB_PREFIX."tarif_conditionnement AS tc
					LEFT JOIN ".MAIN_DB_PREFIX."product AS p ON (tc.fk_product = p.rowid)
					LEFT JOIN ".MAIN_DB_PREFIX."currency AS c ON (c.code = tc.currency_code)
					LEFT JOIN ".MAIN_DB_PREFIX."c_pays AS pays ON (pays.rowid = tc.fk_country)
					LEFT JOIN ".MAIN_DB_PREFIX."categorie AS cat ON (cat.rowid = tc.fk_categorie_client)
				WHERE fk_product = ".$product->id."
				ORDER BY unite_value, quantite ASC";
	}
	else {
		$sql = "SELECT tc.rowid AS 'id', tc.type_price as type_price,pays.libelle as 'Pays', cat.label as 'Catégorie', tc.price_base_type AS base, tc.quantite as quantite,";
		if($type_unite == "unite") {
			$sql.=			   "tc.unite AS unite, tc.remise_percent AS remise, tc.tva_tx AS tva, tc.prix AS prix, tc.unite_value AS unite_value,";
			$sql.=			  "tc.quantite * tc.prix * (100-tc.remise_percent)/100 AS 'Total',";
		} 
		else {
			$sql.=			   "tc.unite AS unite, tc.remise_percent AS remise, tc.tva_tx AS tva, tc.prix AS prix, p.".$type_unite."_units AS base_poids, tc.unite_value AS unite_value,";
			$sql.=			  "((tc.quantite * POWER(10,(tc.unite_value-p.".$type_unite."_units))) * tc.prix) - ((tc.quantite * POWER(10,(tc.unite_value-p.".$type_unite."_units))) * tc.prix)";
			if($Ttarif->remise_percent){
				$sql .=  	  "* (tc.remise_percent/100)";
			}
			$sql .=			  " AS 'Total',";
		}
		$sql.=			   "'' AS 'Actions'
				FROM ".MAIN_DB_PREFIX."tarif_conditionnement AS tc
					LEFT JOIN ".MAIN_DB_PREFIX."product AS p ON (tc.fk_product = p.rowid)
					LEFT JOIN ".MAIN_DB_PREFIX."c_pays AS pays ON (pays.rowid = tc.fk_country)
					LEFT JOIN ".MAIN_DB_PREFIX."categorie AS cat ON (cat.rowid = tc.fk_categorie_client)
					
				WHERE fk_product = ".$product->id."
				ORDER BY unite_value, quantite ASC";
	}
	//echo $sql;
	$r = new TSSRenderControler(new TTarif);
	
	$THide = array(
			'id'
			,'base_poids'
			,'unite_value'
			,'base'
		);
		
	if(!$conf->multidevise->enabled) $THide[] = 'currency';
	
	print $r->liste($ATMdb, $sql, array(
		'limit'=>array('nbLine'=>1000)
		,'title'=>array(
			'base' =>$langs->trans('PriceBase')
			,'quantite'=>$langs->trans('Quantity')
			,'currency'=>$langs->trans('Devise')
			,'type_price' =>$langs->trans('PriceType')
			,'unite'=>$langs->trans('Unit')
			,'prix'=>$langs->trans('Tarif')
			,'remise' =>$langs->trans('Remise')
			,'tva'=>$langs->trans('TVA')
			,'Total' =>$langs->trans('Total')
			,'Supprimer' =>$langs->trans('Delete')
			,'Pays' =>$langs->trans('Country')
		)
		,'type'=>array('date_debut'=>'date','date_fin'=>'date','tva' => 'number', 'prix'=>'number', 'Total' => 'number' , 'quantite' => 'number')
		,'hide'=> $THide
		,'link'=>array(
			'Actions'=>'
					<a href="?id=@id@&action=delete&fk_product='.$object->id.'" onclick="return confirm(\''.$langs->trans('ConfirmDelete').'\');">'.img_delete().'</a>
					<a href="?id=@id@&action=edit&fk_product='.$object->id.'">'.img_edit().'</a>
			'
		)
		,'eval'=>array(
			'type_price'=>'_getTypePrice("@val@")'
		)
	));


	function _getTypePrice($idPriceCondi){
		global $langs;

		$TPDOdb = new TPDOdb;

		$TTarif = new TTarif;

		return $langs->trans($TTarif->TType_price[$idPriceCondi]);
	}
	
	
	?>
	<br>
	
	