<?php

require 'config.php';
dol_include_once('/tarif/class/tarif.class.php');
dol_include_once('/tarif/lib/tarif.lib.php');

require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';


$is_project_module_enabled = !empty($conf->projet->enabled);
if ($is_project_module_enabled) {
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// TODO check si droit de modifier un produit à minima
//if(empty($user->rights->tarif->read)) accessforbidden();

$langs->load('abricot@abricot');
$langs->load('tarif@tarif');
$langs->load("other");
$langs->load("bank");
$langs->load("products");


$object = new Tarif($db);
$product = new Product($db);

$hookmanager->initHooks(array('tariflist'));

$action = GETPOST('action','list');

$fk_product = GETPOST('fk_product','int');
$id = GETPOST('id','int');

if ($id > 0)
{
	$res = $object->fetch($id);
	if ($res < 0)
	{
		dol_print_error($db);
		exit;
	}
}

if ($fk_product > 0)
{
	$res = $product->fetch($fk_product);
	if ($res < 0)
	{
		dol_print_error($db);
		exit;
	}
}

/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');


if (empty($reshook))
{
	// do action from GETPOST ...
	if ($action == 'confirm_delete')
	{
		if ($object->delete($user) < 0)
		{
			dol_print_error($db);
			exit;
		}
		header('Location: '.$_SERVER['PHP_SELF'].'?fk_product='.$product->id);
		exit;
	}
	elseif ($action == 'save')
	{
		if(GETPOST('cancel') == '')
		{
			$weight_units = GETPOST('weight_units');
			if(!empty($weight_units) && !empty($product->array_options['options_unite_vente']))
			{
				$unite = measuring_units_string($weight_units, $product->array_options['options_unite_vente']);
				$unite = $langs->trans($unite);
			}
			else
			{
				$unite = 'U';
			}

			$object->unite = $unite;
			$object->unite_value = $weight_units;

			$object->tva_tx = GETPOST('tva_tx','int');
			$object->price_base_type = 'HT';
			$object->fk_user_author = $user->id;
			$object->type_price = GETPOST('type_prix');
			$object->currency_code = GETPOST('currency');
			var_dump($object->currency_code);
			$object->fk_country = GETPOST('fk_country','int');

			$object->fk_soc = GETPOST('fk_soc','int');
			$object->fk_project = GETPOST('fk_project','int');

			$prix = price2num(GETPOST('prix_visu'));
			$remise = price2num(GETPOST('remise'));

			if($object->type_price == 'PERCENT/PRICE')
			{
				$object->prix = $prix;
				$object->remise_percent = $remise;
			}
			elseif($object->type_price == 'PRICE')
			{
				$object->prix = $prix;
			}
			else
			{
				$object->prix = $prix;
				$object->remise_percent = $remise;
			}

			$object->quantite = price2num(GETPOST('quantite'));

			$object->fk_product = $fk_product;
			$object->fk_categorie_client = GETPOST('fk_categorie_client','int');
			$object->date_debut = dol_mktime(0, 0, 0, GETPOST('date_debutmonth'), GETPOST('date_debutday'), GETPOST('date_debutyear'));
			$object->date_fin = dol_mktime(23, 59, 59, GETPOST('date_finmonth'), GETPOST('date_finday'), GETPOST('date_finyear'));

			$res = $object->save();
			if ($res < 0)
			{
				setEventMessage($object->error, 'errors');
			}

		}

		header('Location: '.$_SERVER['PHP_SELF'].'?fk_product='.$product->id);
		exit;
	}
}


$sql = 'SELECT t.rowid, t.type_price, t.fk_country, t.fk_soc, t.fk_categorie_client, t.fk_project';
$sql.= ', t.quantite, t.unite, t.remise_percent, t.tva_tx, t.prix';
$sql.= ', \'\' AS total';
$sql.= ', t.date_debut, t.date_fin';
$sql.= ', \'\' AS action';

$sql.= ' FROM '.MAIN_DB_PREFIX.$object->table_element.' t ';

$sql.= ' WHERE fk_product = '.$product->id;


/*
 * View
 */

llxHeader('',$langs->trans('TarifList'),'','');


$head=product_prepare_head($product);
$titre=$langs->trans("CardProduct".$product->type);
$picto=($product->type== Product::TYPE_SERVICE?'service':'product');
dol_fiche_head($head, 'tabTarif1', $titre, 0, $picto);

$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($product, 'ref', $linkback, ($user->socid?0:1), 'ref');


if ($action != 'add' && $action != 'edit')
{
	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?fk_product='.$product->id.'&action=add">'.$langs->trans('AddTarif').'</a>';
	print '</div>';
}

dol_fiche_end();

if ($action == 'add' || $action == 'edit')
{
	if (empty($object->id)) $title = $langs->trans("TarifNewPrice");
	else $title = $langs->trans("TarifUpdatePrice");

	print load_fiche_titre($title, '', 'title_accountancy.png');

	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="save">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';
	print '<input type="hidden" name="fk_product" value="' . $product->id . '">';

	dol_fiche_head('');

	print '<table class="border" width="100%">';
	print '<tbody>';

	// TODO print inputs
	// debut
	print '<tr><td class="titlefield">'.$langs->trans("VATRate").'</td><td>';
	print $form->load_tva("tva_tx", ($action=='edit') ? $object->tva_tx : $object->tva_tx,$mysoc,'',$object->id,$object->tva_npr);
	print '</td></tr>';

	// Price base
	print '<tr><td class="titlefield">'.$langs->trans('PriceBase').'</td>';
	print '<td>';
	//print $form->select_PriceBaseType($object->price_base_type, "price_base_type");
	print 'HT</td>';
	print '</tr>';

	print '<tr><td class="titlefield">'.$langs->trans('PriceType').'</td><td>';
	print $form->selectarray('type_prix', Tarif::$TPriceType, $object->type_price, 0, 0, 0, '', 1);
	print '</td></tr>';

	if (!empty($conf->multicurrency->enabled))
	{
		//Devise
		print '<tr><td>'.$langs->trans('Devise').'</td><td colspan="3">';
		print $form->selectCurrency( ($action=='edit') ? $object->currency_code : $conf->currency,"currency");
		print '</td></tr>';
	}

	//Pays
	print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
	print $form->select_country( ($action=='edit') ? $object->fk_country : 0,"fk_country");
	print '</td></tr>';

	//client
	print '<tr><td>'.$langs->trans('Customer').'</td><td colspan="3">';
	print $form->select_company($object->fk_soc, 'fk_soc','',1);
	print '</td></tr>';

	//categorie
	print '<tr><td>'.$langs->trans('CategoriesCustomer').'</td><td colspan="3">';
	print $form->select_all_categories(2, ($action=='edit') ? $object->fk_categorie_client : 'auto', 'fk_categorie_client');
	print '</td></tr>';

	//Projet
	if ($is_project_module_enabled)
	{
		$formproject = new FormProjets($db);
		print '<tr><td>'.$langs->trans('Project').'</td><td colspan="3">';
		print $formproject->select_projects(-1, $object->fk_project, 'fk_project');
		print '</td></tr>';
	}

	// dates
	print '<tr><td class="titlefield">';
	print $langs->trans('DateBeginTarif');
	print '</td><td>';
	$form->select_date($object->date_debut,'date_debut','','',1,"add",1,1);
	print '</td></tr>';

	print '<tr><td class="titlefield">';
	print $langs->trans('DateEndTarif');
	print '</td><td>';
	$form->select_date($object->date_fin,'date_fin','','', 1,"add",1,1);
	print '</td></tr>';

	$prix = ( ($action=='edit') ? $object->prix : $product->price);
	// Price
	print '<tr><td class="titlefield">';
	print $langs->trans('SellingPrice');
	print '</td><td>
		<input type="hidden" name="prix" id="prix" value="'.$prix.'">
		<input size="10" name="prix_visu" value="'.price($prix).'"></td></tr>';

	$remise = $object->remise_percent;
	// Remise
	print '<tr><td class="titlefield">';
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
				var n_price = parseFloat($(this).val());
				if (isNaN(n_price)) {
					n_price = 0;
					$(this).val(0);
				}

				var price = parseFloat($('#prix').val());
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
	print '<tr><td class="titlefield">';
	print $langs->trans('Quantity');
	print '</td><td><input size="10" name="quantite" value="'.__val($object->quantite,1,'double',true).'"></td></tr>';

	if(! empty($type_unite) && $type_unite != 'unite') {
		//Unité
		print '<tr><td class="titlefield">';
		print $langs->trans('Unit');
		print '</td><td>';
		print $formproduct->select_measuring_units("weight_units", $type_unite, ($action=='edit') ? $object->unite_value : $object->{$type_unite.'_units'});
		print '</td></tr>';
	}

	print '<tr><td class="titlefield">';
	print $langs->trans('MotifChangement');
	print '</td><td>';
	print '<textarea rows="'.ROWS_4.'" style="width:90%" name="motif_changement"></textarea>';
	print '</td></tr>';



	// FIN

	print '</tbody>';

	print '</table>';


	dol_fiche_end();


	print '<div style="text-align: center">';
	print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
	print '&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '"></div>';
	print '</form>';

}
else
{
	$formcore = new TFormCore($_SERVER['PHP_SELF'], 'form_list_tarif', 'GET');

	$nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;

	$r = new Listview($db, 'tarif');

	$renderParameters = array(
        'view_type' => 'list' // default = [list], [raw], [chart]
        ,'limit'=>array(
                'nbLine' => 0
            )
        ,'subQuery' => array()
        ,'link' => array(
                'action' => '
                    <a href="'.$_SERVER['PHP_SELF'].'?id=@rowid@&action=edit&fk_product='.$product->id.'">'.img_edit().'</a>
                    <a href="'.$_SERVER['PHP_SELF'].'?id=@rowid@&action=delete&fk_product='.$product->id.'">'.img_delete().'</a>
                '
            )
        ,'type' => array(
                'date_debut' => 'date' // [datetime], [hour], [money], [number], [integer]
                ,'date_fin' => 'date'
                , 'prix'=>'money'
                , 'total'=>'money'
            )
        ,'search' => array(
    //			'date_creation' => array('search_type' => 'calendars', 'allow_is_null' => true)
    //			,'tms' => array('search_type' => 'calendars', 'allow_is_null' => false)
    //			,'ref' => array('search_type' => true, 'table' => 't', 'field' => 'ref')
    //			,'label' => array('search_type' => true, 'table' => array('t', 't'), 'field' => array('label')) // input text de recherche sur plusieurs champs
    //			,'status' => array('search_type' => Tarif::$TStatus, 'to_translate' => true) // select html, la clé = le status de l'objet, 'to_translate' à true si nécessaire
            )
        ,'translate' => array()
        ,'hide' => array(
             'rowid'
            )
        ,'list' => array(
             'title' => $langs->trans('TarifList')
            ,'image' => 'title_accountancy.png'
            ,'picto_precedent' => '<'
            ,'picto_suivant' => '>'
            ,'noheader' => 0
            ,'messageNothing' => $langs->trans('NoTarif')
            ,'picto_search' => img_picto('','search.png', '', 0)
            )
        ,'title'=>array(
              'type_price' =>$langs->trans('PriceBase')
            , 'fk_soc'=>$langs->trans('Company')
            , 'fk_country'=>$langs->trans('Country')
            , 'fk_categorie_client'=>$langs->trans('Category')
            , 'fk_project'=>$langs->trans('Project') // TODO: activate this column only when the Project module is enabled; then Project should be editable too
            , 'quantite'=>$langs->trans('Quantity')
            , 'unite'=>$langs->trans('Unit')
            , 'remise_percent'=>$langs->trans('Remise(%)')
            , 'prix'=>$langs->trans('Tarif')
            , 'total'=>$langs->trans('Total')
            , 'date_debut'=>$form->textwithpicto($langs->trans('StartDate'), $langs->trans('StartDateInfo'), 1, 'help', '', 0, 3)
            , 'date_fin'=>$form->textwithpicto($langs->trans('EndDate'), $langs->trans('EndDateInfo'), 1, 'help', '', 0, 3)
            , 'action'=> ''
            )
        ,'position' => array(
                'text-align' => array(
                    'remise_percent' => 'right'
                    ,'prix'=>'right'
                    ,'total'=>'right'
                    ,'date_debut' => 'right'
                    ,'date_fin' => 'right'
                    ,'action' => 'right'
                )
            )
        ,'eval'=>array(
                'type_price' => 'Tarif::getPriceType("@val@")'
                ,'fk_country' => '_getCountryName("@val@")' // Si on a un fk_user dans notre requête
                ,'fk_soc' => '_getNomURLSoc(@val@)' // Si on a un fk_user dans notre requête
                ,'fk_categorie_client' => '_getCategoryName("@val@")' // Si on a un fk_user dans notre requête
                ,'fk_project' => '_getProjectName("@val@")' // Si on a un fk_user dans notre requête
            )

    );

	if (!$is_project_module_enabled) {
	    // if the project module is not enabled, remove this.
	    unset($renderParameters['title']['fk_project']);
    }

	echo $r->render($sql, $renderParameters);

	$parameters=array('sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	$formcore->end_form();
}


// Check if need to print formconfirm
$formconfirm = getFormConfirmTarif($form, $object, $action);
if (!empty($formconfirm)) print $formconfirm;

llxFooter('');
$db->close();


function _getCountryName($fk_country)
{
	global $db,$langs,$TCountryLabelTmp;

	if (empty($TCountryLabelTmp))
	{
		$sql = 'SELECT rowid, label, code FROM '.MAIN_DB_PREFIX.'c_country WHERE rowid > 0';
		$resql = $db->query($sql);
		if ($resql)
		{
			while ($obj = $db->fetch_object($resql))
			{
				if ($langs->transnoentitiesnoconv("Country".$obj->code) != "Country".$obj->code) $TCountryLabelTmp[$obj->rowid] = $langs->transnoentitiesnoconv("Country".$obj->code);
				else $TCountryLabelTmp[$obj->rowid] = $obj->label;
			}
		}
	}

	if (isset($TCountryLabelTmp[$fk_country])) return $TCountryLabelTmp[$fk_country];
}

function _getNomURLSoc($fk_soc)
{
	global $db;

	$s = new Societe($db);
	if ($s->fetch($fk_soc) > 0) return $s->getNomUrl(1);
}

function _getMotif($motif)
{
	if(strlen($motif) > 20) return '<span title="...'.substr($motif, 20).'">'.substr($motif, 0, 20).'...</span>';
	return $motif;
}

function _getCategoryName($fk_categorie_client)
{
	global $db,$TCategoryLabelTmp;

	if (empty($TCategoryLabelTmp))
	{
		$sql = 'SELECT rowid, label FROM '.MAIN_DB_PREFIX.'categorie WHERE type = 2';
		$resql = $db->query($sql);
		if ($resql)
		{
			while ($obj = $db->fetch_object($resql))
			{
				$TCategoryLabelTmp[$obj->rowid] = $obj->label;
			}
		}
	}

	if (isset($TCategoryLabelTmp[$fk_categorie_client])) return $TCategoryLabelTmp[$fk_categorie_client];
}

function _getProjectName($fk_project)
{
	global $db, $TProjectNameTmp;

	if (!isset($TProjectNameTmp[$fk_project]))
	{
		$project = new Project($db);
		if ($project->fetch($fk_project) > 0) $TProjectNameTmp[$fk_project] = $project->title;
	}

	if (isset($TProjectNameTmp[$fk_project])) return $TProjectNameTmp[$fk_project];
}
