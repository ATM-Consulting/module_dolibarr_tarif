<?php
	require('config.php');
	require('class/tarif.class.php');
	dol_include_once('/core/lib/product.lib.php');
	dol_include_once('/product/class/product.class.php');
	dol_include_once('/product/class/html.formproduct.class.php');
	dol_include_once('/core/class/extrafields.class.php');
	
	if(is_file(DOL_DOCUMENT_ROOT."/lib/product.lib.php")) dol_include_once("/lib/product.lib.php");
	else dol_include_once("/core/lib/product.lib.php");
	
	dol_include_once("/product/class/product.class.php");
	dol_include_once('/categories/class/categorie.class.php');
	
    if (! empty($conf->projet->enabled)) {
        dol_include_once( '/projet/class/project.class.php');
        dol_include_once( '/core/class/html.formprojet.class.php');
    }
    
	global $langs;
	
	$langs->Load("other");
	$langs->Load("bank");
	$langs->Load("tarif@tarif");
	
	$fk_product = GETPOST('fk_product','int');
	
	//pre($langs);exit;
	
	llxHeader('',$langs->trans('TarifFournisseurList'),'','');
	
	$ATMdb = new TPDOdb;
	
	$ATMdb->Execute("SELECT unite_vente FROM ".MAIN_DB_PREFIX."product_extrafields WHERE fk_object = ".$fk_product);
	$ATMdb->Get_line();
	$type_unite = $ATMdb->Get_field('unite_vente');
	
	$TTarifFournisseur = new TTarifFournisseur;
	$product = new Product($db);
	$result=$product->fetch($fk_product);
		
	$head=product_prepare_head($product, $user);
	$titre=$langs->trans("CardProduct".$product->type);
	$picto=($product->type==1?'service':'product');
	dol_fiche_head($head, 'tabTarif2', $titre, 0, $picto);
	
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
		
		$tarif = new TTarifFournisseur;
		$tarif->type_price = defined('TARIF_DEFAULT_TYPE') ? TARIF_DEFAULT_TYPE : '';
		if($action=='edit') $tarif->load($ATMdb, __get('id',0,'integer'));

		print '<table class="notopnoleftnoright" width="100%" border="0" style="margin-bottom: 2px;" summary="">';
		print '<tbody><tr>';
		print '<td class="nobordernopadding" valign="middle"><div class="titre">Nouveau conditionnement</div></td>';
		print '</tr></tbody>';
		print '</table>';
		
		print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
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
        print $form->selectarray("type_prix",$TTarifFournisseur->TType_price,$tarif->type_price);
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
		
         //client
        print '<tr><td>'.$langs->trans('Supplier').'</td><td colspan="3">';
        print $form->select_company($tarif->fk_soc, 'fk_soc','fournisseur=1',1);
        print '</td></tr>';
        
        //categorie
		print '<tr><td>'.$langs->trans('CategoriesCustomer').'</td><td colspan="3">';
		print $form->select_all_categories(2, ($action=='edit') ? $tarif->fk_categorie_client : 'auto', 'fk_categorie_client');
		print '</td></tr>';
    
    	//Projet
    	if (! empty($conf->projet->enabled)) 
    	{
	        $formproject = new FormProjets($db);
	        print '<tr><td>'.$langs->trans('Project').'</td><td colspan="3">';
	        print $formproject->select_projects(-1, $tarif->fk_project, 'fk_project');
	        print '</td></tr>';
		}
		
        // dates
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
		
		$prix = ( ($action=='edit') ? $tarif->prix :$object->price);
		$prixVente=$prix/(1-($conf->global->TARIF_PERCENT_AUTO_CREATE/100));
		
		// Price
		print '<tr><td width="30%">';
		print $langs->trans('BuyingPrice');
		print '</td><td>
		<input type="hidden" name="prix" id="prix" value="'.$prix.'">
		<input size="10" name="prix_visu" value="'.price($prix).'">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ('.$langs->trans('SellingPrice').'  : <span size="10" name="prix_vente_visu">'.$prixVente.'</span>)</td></tr>';
		
		$remise = $tarif->remise_percent;		
		// Remise
		print '<tr><td width="30%">';
		print $langs->trans('Remise(%)');
		print '</td><td><input id="remise" size="10" name="remise" value="'.$remise.'" />%</td></tr>';
		
		?>
			<script type="text/javascript">
			
				$('input[name=remise]').change(function() {
					var n_percent = parseInt($(this).val());
					if (isNaN(n_percent)) { 
						n_percent = 0;
						$(this).val(0);
					}
					
					var price = $('#prix').val();
					if(n_percent>100 || n_percent<0) {
						alert('<?php echo $langs->transnoentities('tarif_percent_not_between_0_100'); ?>');
						$(this).val(0);
						return false;
					}
					if($('#type_prix').val() != 'PERCENT/PRICE') {
						$('[name=prix_visu]').val(((100 - n_percent) * price / 100).toFixed(2));
					}
				});
				
				$('input[name=prix_visu]').change(function() {
					if($('#type_prix').val() != 'PERCENT/PRICE') {
						var n_price = parseFloat($(this).val().replace(',','.'));
						if (isNaN(n_price)) { 
							n_price = 0;
							$(this).val(0);
						}
						
						var price = parseFloat($('#prix').val());
						
						var priceVente = n_price/(1-(<?php echo $conf->global->TARIF_PERCENT_AUTO_CREATE ?>/100));
						$('span[name=prix_vente_visu]').html(priceVente);
						
						var percent;
						
						if (price == 0) {
							percent = 0;
						} else {
							percent = - (((n_price - price) / price) *100 );
						}
						$('#remise').val(percent.toFixed(0));
						
					}
				});

			</script>
		<?php			
		
		//Quantité
		print '<tr><td width="30%">';
		print $langs->trans('Conditionnement'); // Pour solebio, le champ quantite eest toujours un conditionnement
		print '</td><td><input size="10" name="quantite" value="'.__val($tarif->quantite,1,'double',true).'"></td></tr>';
		print '<tr><td width="30%">';
		print $langs->trans('Unit');
		print '</td><td>';
		if($type_unite=='unite') print 'U';
		else print $form->selectUnits($tarif->unite);
		print '</td></tr>';
		print '<tr><td width="30%">';
		print $langs->trans('PoidsUnite');
		print '</td><td>';
		if($type_unite=='unite') print 'U';
		else print '<input size="10" name="poids_unite" value="'.__val($tarif->poids_unite,0,'double',true).'">';
		print '</td></tr>';
		print '<tr><td width="30%">';
		print $langs->trans('MotifChangement');
		print '</td><td>';
		if($type_unite=='unite') print 'U';
		else print '<textarea name="motif_changement"></textarea>';
		print '</td></tr>';
		
		
	


		print '</table>';

		print '<center><br><input type="submit" class="button" value="'.$langs->trans("Save").'" name="save">&nbsp;';
		print '<input type="submit" class="button" value="Annuler" name="back"></center>';

		print '<br></form>';
	}
	elseif(!empty($action) && $action == 'add_conditionnement' && isset($_REQUEST['save'])) {
		
		//pre($_REQUEST,true);
		
		//logueur, poids, etc
		/*if(!is_null($_REQUEST['weight_units'])){
			$unite = measuring_units_string($_REQUEST['weight_units'],$type_unite);
			$unite = $langs->trans($unite);
		}
		else{ //unitaire
			$unite = 'U';
		}*/
		$unite = GETPOST('units');
		//var_dump($_REQUEST);exit;
		$TTarifFournisseur = new TTarifFournisseur;
		
		$log_tarif = false;
		if($id_tarif>0) {
			$TTarifFournisseur->load($ATMdb, $id_tarif);
			$log_tarif = true;
		}
		
		$TTarifFournisseur->tva_tx = GETPOST('tva_tx','int');
		$TTarifFournisseur->price_base_type = 'HT';
		$TTarifFournisseur->fk_user_author = $user->id;
		$TTarifFournisseur->type_price = GETPOST('type_prix');
		$TTarifFournisseur->currency_code = GETPOST('currency');
		$TTarifFournisseur->fk_country = GETPOST('fk_country','int');
		
        $TTarifFournisseur->fk_soc = GETPOST('fk_soc','int');
        $TTarifFournisseur->fk_project = GETPOST('fk_project','int');
        
		$prix = price2num(GETPOST('prix_visu'));
		$remise = price2num(GETPOST('remise'));
		
		if($TTarifFournisseur->type_price == 'PERCENT/PRICE'){
			$TTarifFournisseur->prix = $prix;
			$TTarifFournisseur->remise_percent = $remise;
		}
		else if($TTarifFournisseur->type_price == 'PRICE'){
			$TTarifFournisseur->prix = $prix;
		}
		else{
			$TTarifFournisseur->prix = $prix;
			$TTarifFournisseur->remise_percent = $remise;
		}
		
		$TTarifFournisseur->quantite = price2num(GETPOST('quantite'));
		//$TTarifFournisseur->quantite =  number_format(str_replace(",", ".", $_POST['quantite']),2,".","");
		$TTarifFournisseur->unite = $unite;
		
		$TTarifFournisseur->unite_value = GETPOST('weight_units');
		$TTarifFournisseur->fk_product = $fk_product;
		$TTarifFournisseur->fk_categorie_client = GETPOST('fk_categorie_client','int');
		$TTarifFournisseur->date_fin = $TTarifFournisseur->set_date('date_fin',$_REQUEST['date_fin']);
		$TTarifFournisseur->date_debut = $TTarifFournisseur->set_date('date_debut',$_REQUEST['date_debut']);

		$TTarifFournisseur->poids_unite = price2num(GETPOST('poids_unite'));

		$TTarifFournisseur->save($ATMdb, true, $log_tarif);
		
	}
	elseif(!empty($action) && $action == 'delete' && !empty($id_tarif))
	{
		$TTarifFournisseur = new TTarifFournisseur;
		$TTarifFournisseur->load($ATMdb,$id_tarif);
		$TTarifFournisseur->delete($ATMdb);
	}
	elseif(!empty($action) && $action == 'deletelog' && !empty($id_tarif))
	{
		$TtarifFournLog = new TTarifFournisseurLog;
		$TtarifFournLog->load($ATMdb,$id_tarif);
		$TtarifFournLog->delete($ATMdb);
	}
	
	
	if($type_unite == "size") $type_unite = "length"; //Pout la longeur le nom du champ diffère....
	
	if(empty($type_unite)) $type_unite = 'weight';
	
	/**********************************
	 * Liste des tarifs
	 **********************************/
	$TConditionnement = array();

	/*if($conf->multidevise->enabled){ TODO pour l'instant on laisse de côté la partie multidevise

		$sql = "SELECT tc.rowid AS 'id', tc.type_price as type_price, ".((DOL_VERSION >= 3.7) ? "pays.label" : "pays.libelle")." as 'Pays'
		              , tc.fk_soc
		              , cat.label as 'Catégorie'
		              , tc.price_base_type AS base, tc.quantite as quantite,
					   tc.unite AS unite, tc.remise_percent AS remise, tc.tva_tx AS tva
					   , IF(tc.date_debut<='1000-01-01 00:00:00', '' , DATE_FORMAT(tc.date_debut,'%d/%m/%Y')) AS date_debut
					   , IF(tc.date_fin<='1000-01-01 00:00:00', '' , DATE_FORMAT(tc.date_fin,'%d/%m/%Y')) AS date_fin
					   , tc.prix AS prix
					   ";
		
		if($type_unite == "unite") {

			$sql.=" ,tc.unite_value AS unite_value,
				tc.quantite * tc.prix";
			if($TTarifFournisseur->remise_percent){
				$sql .= "* (100-tc.remise_percent)/100";
			} 
			$sql .=	"  AS 'Total'";
		
		}
		else {
			
			$sql.=" , p.".$type_unite."_units AS base_poids, tc.unite_value AS unite_value,
				((tc.quantite * POWER(10,(tc.unite_value-p.".$type_unite."_units))) * tc.prix) - ((tc.quantite * POWER(10,(tc.unite_value-p.".$type_unite."_units))) * tc.prix)";
			if($TTarifFournisseur->remise_percent){
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
	else {*/
		$sql = "SELECT tc.rowid AS 'id', tc.type_price as type_price,".((DOL_VERSION >= 3.7) ? "pays.label" : "pays.libelle")." as 'Pays', tc.fk_soc, cat.label as 'Catégorie', tc.price_base_type AS base, tc.quantite as quantite,";
		if($type_unite == "unite") {
			$sql.=			   "unit.label AS unite, tc.poids_unite, tc.remise_percent AS remise, tc.tva_tx AS tva, tc.prix AS prix, tc.unite_value AS unite_value,";
			$sql.=			  "tc.quantite * tc.prix * (100-tc.remise_percent)/100 AS 'Total',";
		} 
		else {
			$sql.=			   "unit.label AS unite, tc.poids_unite, tc.remise_percent AS remise, tc.tva_tx AS tva, tc.prix AS prix, p.".$type_unite."_units AS base_poids, tc.unite_value AS unite_value,";
			$sql.=			  "((tc.quantite * POWER(10,(tc.unite_value-p.".$type_unite."_units))) * tc.prix) - ((tc.quantite * POWER(10,(tc.unite_value-p.".$type_unite."_units))) * tc.prix)";
			if($TTarifFournisseur->remise_percent){
				$sql .=  	  "* (tc.remise_percent/100)";
			}
			$sql .=			  " AS 'Total',";
		}
		
		$sql .= "
				 IF(tc.date_debut<='1000-01-01 00:00:00', '' , DATE_FORMAT(tc.date_debut,'%d/%m/%Y')) AS date_debut
				, IF(tc.date_fin<='1000-01-01 00:00:00', '' , DATE_FORMAT(tc.date_fin,'%d/%m/%Y')) AS date_fin
		";
	
		$sql.=			   ", '' AS 'Actions' ";
		
		$sql.=		" FROM ".MAIN_DB_PREFIX."tarif_conditionnement_fournisseur AS tc
					LEFT JOIN ".MAIN_DB_PREFIX."product AS p ON (tc.fk_product = p.rowid)
					LEFT JOIN ".MAIN_DB_PREFIX.((DOL_VERSION >= 3.7) ? "c_country" : "c_pays")." AS pays ON (pays.rowid = tc.fk_country)
					LEFT JOIN ".MAIN_DB_PREFIX."categorie AS cat ON (cat.rowid = tc.fk_categorie_client)
					LEFT JOIN ".MAIN_DB_PREFIX."c_units unit ON(unit.rowid = tc.unite)
				WHERE fk_product = ".$product->id."
				ORDER BY unite_value, quantite ASC";
	//}
	//echo $sql;
	$r = new TSSRenderControler(new TTarifFournisseur);
	
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
			, 'fk_soc'=>$langs->trans('Company')
			,'date_debut'=>$form->textwithpicto($langs->trans('StartDate'), $langs->trans('StartDateInfo'), 1, 'help', '', 0, 3)
			,'date_fin'=>$form->textwithpicto($langs->trans('EndDate'), $langs->trans('EndDateInfo'), 1, 'help', '', 0, 3)
			,'quantite'=>$langs->trans('Conditionnement') // Pour solebio, le champ quantite eest toujours un conditionnement
			,'currency'=>$langs->trans('Devise')
			,'type_price' =>$langs->trans('PriceType')
			,'unite'=>$langs->trans('Unit')
			,'prix'=>$langs->trans('TarifU')
			,'remise' =>$langs->trans('Remise(%)')
			,'tva'=>$langs->trans('TVA')
			,'Total' =>$langs->trans('Total')
			,'Supprimer' =>$langs->trans('Delete')
			,'Pays' =>$langs->trans('Country')
			,'poids_unite'=>$langs->trans('PoidsUnite')
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
			,'fk_soc'=>'_getNomURLSoc(@val@)'
		)
	));
	
	if(!empty($conf->global->TARIF_LOG_TARIF_UPDATE)) {
	
		print '<br />';
		
		$sql = strtr($sql, array('tarif_conditionnement_fournisseur'=>'tarif_conditionnement_fournisseur_log', 'AS date_fin'=>'AS date_fin, tc.motif_changement')); // Même requête mais dans la table log
		
		print $r->liste($ATMdb, $sql, array(
			'limit'=>array('nbLine'=>1000)
			,'title'=>array(
				'base' =>$langs->trans('PriceBase')
				, 'fk_soc'=>$langs->trans('Company')
				,'date_debut'=>$form->textwithpicto($langs->trans('StartDate'), $langs->trans('StartDateInfo'), 1, 'help', '', 0, 3)
				,'date_fin'=>$form->textwithpicto($langs->trans('EndDate'), $langs->trans('EndDateInfo'), 1, 'help', '', 0, 3)
				,'quantite'=>$langs->trans('Conditionnement')
				,'currency'=>$langs->trans('Devise')
				,'type_price' =>$langs->trans('PriceType')
				,'unite'=>$langs->trans('Unit')
				,'prix'=>$langs->trans('TarifU')
				,'remise' =>$langs->trans('Remise(%)')
				,'tva'=>$langs->trans('TVA')
				,'Total' =>$langs->trans('Total')
				,'Supprimer' =>$langs->trans('Delete')
				,'Pays' =>$langs->trans('Country')
				,'poids_unite'=>$langs->trans('PoidsUnite')
				,'motif_changement'=>$langs->trans('MotifChangementShort')
			)
			,'type'=>array(/*'date_debut'=>'date','date_fin'=>'date',*/'tva' => 'number', 'prix'=>'number', 'Total' => 'number' , 'quantite' => 'number')
			,'hide'=> $THide
			,'link'=>array(
				'Actions'=>'
						<a href="?id=@id@&action=deletelog&fk_product='.$object->id.'" onclick="return confirm(\''.$langs->trans('ConfirmDelete').'\');">'.img_delete().'</a>
				'
			)
			,'eval'=>array(
				'type_price'=>'_getTypePrice("@val@")'
				,'fk_soc'=>'_getNomURLSoc(@val@)'
				,'motif_changement'=>'_getMotif("@val@")'
			)
			,'liste'=>array(
				'titre'=>$langs->trans('PriceLog')
			)
		));
	
	}
	
	print '
		<style type="text/css">
			#list_llx_tarif_conditionnement td div {
				text-align:left !important;
			}
		</style>
	';


	function _getTypePrice($idPriceCondi){
		global $langs;

		$TPDOdb = new TPDOdb;

		$TTarifFournisseur = new TTarifFournisseur;

		return $langs->trans($TTarifFournisseur->TType_price[$idPriceCondi]);
	}
	
	function _getNomURLSoc($id_soc) {
		
		global $db;
		
		$s = new Societe($db);
		$s->fetch($id_soc);
		
		if($s->id > 0) {
			return $s->getNomUrl(1);
		}
		
	}
	
	function _getMotif($motif) {
		if(strlen($motif) > 20) return '<span title="...'.substr($motif, 20).'">'.substr($motif, 0, 20).'...</span>';
		return $motif;
	}
	
	llxFooter();
	