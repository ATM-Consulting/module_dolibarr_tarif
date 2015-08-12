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
	$langs->Load("bank");
	$langs->Load("tarif@tarif");
	
	$fk_product = GETPOST('fk_product','int');
	
	//pre($langs);exit;
	
	llxHeader('',$langs->trans('TarifList'),'','');
	
	$ATMdb = new TPDOdb;
	
	$ATMdb->Execute("SELECT unite_vente FROM ".MAIN_DB_PREFIX."product_extrafields WHERE fk_object = ".$fk_product);
	$ATMdb->Get_line();
	$type_unite = $ATMdb->Get_field('unite_vente');
	
	$TTarif = new TTarif;
	$product = new Product($db);
	$result=$product->fetch($fk_product);
		
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
	$id_tarif=__get('id',0,'integer');
	 
	if(!empty($action) && ($action == 'add' || $action == 'edit' )){
		
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
		print '<input type="hidden" name="fk_product" value="'.$object->id.'">';
		print '<input type="hidden" name="id" value="'.$tarif->getId().'">';
		print '<table class="border" width="100%">';

		// VAT
        print '<tr><td width="30%">'.$langs->trans("VATRate").'</td><td>';
        print $form->load_tva("tva_tx", ($action=='edit') ? $tarif->tva_tx : $object->tva_tx,$mysoc,'',$object->id,$object->tva_npr);
        print '</td></tr>';

		// Price base
		print '<tr><td width="30%">'.$langs->trans('PriceBase').'</td>';
		print '<td>';
		//print $form->select_PriceBaseType($object->price_base_type, "price_base_type");
		print 'HT</td>';
		print '</tr>';
		
		print '<tr><td width="30%">'.$langs->trans('PriceType').'</td><td>';
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
		print '<tr><td width="30%">';
		print $langs->trans('SellingPrice');
		print '</td><td>
		<input type="hidden" name="prix" id="prix" value="'.$prix.'">
		<input size="10" name="prix_visu" value="'.price($prix).'"></td></tr>';
		
		$remise = $tarif->remise_percent;		
		// Remise
		print '<tr><td width="30%">';
		print $langs->trans('Remise(%)');
		print '</td><td><input id="remise" size="10" name="remise" value="'.$remise.'" />%</td></tr>';
		
		?>
			<script type="text/javascript">
			
				$('input[name=remise]').change(function() {
					var n_percent = $(this).val();
					var price = $('#prix').val();
					if(n_percent>100 || n_percent<0) {
						alert('<?php echo $langs->trans('Remise(%)'); ?>');
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
		<?php			
		
		//Quantité
		print '<tr><td width="30%">';
		print $langs->trans('Quantity');
		print '</td><td><input size="10" name="quantite" value="'.__val($tarif->quantite,1,'double',true).'"></td></tr>';
		print '<tr><td width="30%">';
		print $langs->trans('Unit');
		print '</td><td>';
		if($type_unite=='unite') print 'U';
		else print $formproduct->select_measuring_units("weight_units", $type_unite, ($action=='edit') ? $tarif->unite_value : $object->{$type_unite.'_units'});
		print '</td></tr>';
		
		
		
		print '<tr><td width="30%">';
		print $langs->trans('DateBeginTarif');
		print '</td><td>';
		
		// Par défaut les tarifs n'ont pas de date de fin
		$show_empty = 0;
		if($action === 'add' || ($action === 'edit' && $tarif->date_debut === 0)) {
			$show_empty = 1;
			$tarif->date_debut = '';
		}
		
		$form->select_date($tarif->date_debut,'date_debut','','',$show_empty,"add",1,1);
		print '</td></tr>';
		
		
		
		
		print '<tr><td width="30%">';
		print $langs->trans('DateEndTarif');
		print '</td><td>';
		
		// Par défaut les tarifs n'ont pas de date de fin
		$show_empty = 0;
		if($action === 'add' || ($action === 'edit' && $tarif->date_fin === 0)) {
			$show_empty = 1;
			$tarif->date_fin = '';
		}
		
		$form->select_date($tarif->date_fin,'date_fin','','',$show_empty,"add",1,1);
		print '</td></tr>';



		print '</table>';

		print '<center><br><input type="submit" class="button" value="'.$langs->trans("Save").'" name="save">&nbsp;';
		print '<input type="submit" class="button" value="Annuler" name="back"></center>';

		print '<br></form>';
	}
	elseif(!empty($action) && $action == 'add_conditionnement' && isset($_REQUEST['save'])) {
		
		//pre($_REQUEST,true);
		
		//logueur, poids, etc
		if(!is_null($_REQUEST['weight_units'])){
			$unite = measuring_units_string($_REQUEST['weight_units'],$type_unite);
			$unite = $langs->trans($unite);
		}
		else{ //unitaire
			$unite = 'U';
		}
		
		$Ttarif = new TTarif;
		
		if($id_tarif>0) $Ttarif->load($ATMdb, $id_tarif);
		//pre($Ttarif,true);exit;
		
		$Ttarif->tva_tx = GETPOST('tva_tx','int');
		$Ttarif->price_base_type = 'HT';
		$Ttarif->fk_user_author = $user->id;
		$Ttarif->type_price = GETPOST('type_prix');
		$Ttarif->currency_code = GETPOST('currency');
		$Ttarif->fk_country = GETPOST('fk_country','int');
		
		$prix = price2num(GETPOST('prix_visu'));
		$remise = price2num(GETPOST('remise'));
		
		if($Ttarif->type_price == 'PERCENT/PRICE'){
			$Ttarif->prix = $prix;
			$Ttarif->remise_percent = $remise;
		}
		else if($Ttarif->type_price == 'PRICE'){
			$Ttarif->prix = $prix;
		}
		else{
			$Ttarif->prix = $prix;
			$Ttarif->remise_percent = $remise;
		}
		
		$Ttarif->quantite = price2num(GETPOST('quantite'));
		//$Ttarif->quantite =  number_format(str_replace(",", ".", $_POST['quantite']),2,".","");
		$Ttarif->unite = $unite;
		
		$Ttarif->unite_value = GETPOST('weight_units');
		$Ttarif->fk_product = $fk_product;
		$Ttarif->fk_categorie_client = GETPOST('fk_categorie_client','int');
		$Ttarif->date_fin = $Ttarif->set_date('date_fin',$_REQUEST['date_fin']);
		$Ttarif->date_debut = $Ttarif->set_date('date_debut',$_REQUEST['date_debut']);
		//$ATMdb->db->debug=true;
			
		//pre($Ttarif,true);exit;
		$Ttarif->save($ATMdb);
	}
	elseif(!empty($action) && $action == 'delete' && !empty($id_tarif))
	{
		$Ttarif = new TTarif;
		$Ttarif->load($ATMdb,$id_tarif);
		$Ttarif->delete($ATMdb);
	}
	
	
	if($type_unite == "size") $type_unite = "length"; //Pout la longeur le nom du champ diffère....
	
	if(empty($type_unite)) $type_unite = 'weight';
	
	/**********************************
	 * Liste des tarifs
	 **********************************/
	$TConditionnement = array();

	if($conf->multidevise->enabled){

		$sql = "SELECT tc.rowid AS 'id', tc.type_price as type_price, ".((DOL_VERSION >= 3.7) ? "pays.label" : "pays.libelle")." as 'Pays', cat.label as 'Catégorie',
					   tc.price_base_type AS base, tc.quantite as quantite,
					   tc.unite AS unite, tc.remise_percent AS remise, tc.tva_tx AS tva, CASE tc.date_debut WHEN '0000-00-00 00:00:00' THEN '' ELSE CONCAT(DAY(tc.date_debut), '/', MONTH(tc.date_debut), '/', YEAR(tc.date_debut)) END AS date_debut, CASE tc.date_fin WHEN '0000-00-00 00:00:00' THEN '' ELSE CONCAT(DAY(tc.date_fin), '/', MONTH(tc.date_fin), '/', YEAR(tc.date_fin)) END AS date_fin, tc.prix AS prix ";
		
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
					LEFT JOIN ".MAIN_DB_PREFIX.((DOL_VERSION >= 3.7) ? "c_country" : "c_pays")." AS pays ON (pays.rowid = tc.fk_country)
					LEFT JOIN ".MAIN_DB_PREFIX."categorie AS cat ON (cat.rowid = tc.fk_categorie_client)
				WHERE fk_product = ".$product->id."
				ORDER BY unite_value, quantite ASC";
	}
	else {
		$sql = "SELECT tc.rowid AS 'id', tc.type_price as type_price,".((DOL_VERSION >= 3.7) ? "pays.label" : "pays.libelle")." as 'Pays', cat.label as 'Catégorie', tc.price_base_type AS base, tc.quantite as quantite,";
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
		$sql.= 		' CASE tc.date_debut WHEN "0000-00-00 00:00:00" THEN "" ELSE CONCAT(DAY(tc.date_debut), "/", MONTH(tc.date_debut), "/", YEAR(tc.date_debut)) END AS date_debut, ';
		$sql.= 		' CASE tc.date_fin WHEN "0000-00-00 00:00:00" THEN "" ELSE CONCAT(DAY(tc.date_fin), "/", MONTH(tc.date_fin), "/", YEAR(tc.date_fin)) END AS date_fin, ';
		$sql.=			   "'' AS 'Actions' ";
		
		$sql.=		" FROM ".MAIN_DB_PREFIX."tarif_conditionnement AS tc
					LEFT JOIN ".MAIN_DB_PREFIX."product AS p ON (tc.fk_product = p.rowid)
					LEFT JOIN ".MAIN_DB_PREFIX.((DOL_VERSION >= 3.7) ? "c_country" : "c_pays")." AS pays ON (pays.rowid = tc.fk_country)
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
			,'date_debut'=>$langs->trans('StartDate')
			,'date_fin'=>$langs->trans('EndDate')
			,'quantite'=>$langs->trans('Quantity')
			,'currency'=>$langs->trans('Devise')
			,'type_price' =>$langs->trans('PriceType')
			,'unite'=>$langs->trans('Unit')
			,'prix'=>$langs->trans('Tarif')
			,'remise' =>$langs->trans('Remise(%)')
			,'tva'=>$langs->trans('TVA')
			,'Total' =>$langs->trans('Total')
			,'Supprimer' =>$langs->trans('Delete')
			,'Pays' =>$langs->trans('Country')
		)
		,'type'=>array(/*'date_debut'=>'date','date_fin'=>'date',*/'tva' => 'number', 'prix'=>'number', 'Total' => 'number' , 'quantite' => 'number')
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
	
	