<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2014 ATM Consulting <contact@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       dev/skeletons/skeleton_page.php
 *		\ingroup    mymodule othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *		\version    $Id: skeleton_page.php,v 1.19 2011/07/31 22:21:57 eldy Exp $
 *		\author		Put author name here
 *		\remarks	Put here some comments
 */
// Change this following line to use the correct relative path (../, ../../, etc)
include '../config.php';
// Change this following line to use the correct relative path from htdocs (do not remove DOL_DOCUMENT_ROOT)

$langs->load("admin");
$langs->load('tarif@tarif');

dol_include_once('/core/lib/admin.lib.php');
dol_include_once('/product/class/html.formproduct.class.php');

// Security check
if (! $user->admin) accessforbidden();


$action=__get('action','');

if($action=='save') {
	
	foreach($_REQUEST['TOptions'] as $name=>$param) {
		
		if(empty($param) && $param == "CAISSE_CMD_PRINT" ){
			$param = " ";
		}
		
		dolibarr_set_const($db, $name, $param, 'chaine', 0, '', $conf->entity);
		
	}
	
	setEventMessage("Configuration enregistrée");
}

llxHeader('',$langs->trans("tarifConfigSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("tarifConfigSetup"),$linkback,'tarif@tarif');


$form=new TFormCore;

showParameters($form);

function showParameters(&$form) {
	global $db,$conf,$langs;
	
	$html=new Form($db);
	$formproduct = new FormProduct($db);
	
	?><form action="<?php echo $_SERVER['PHP_SELF'] ?>" name="load-<?php echo $typeDoc ?>" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="action" value="save" />
	<table width="100%" class="noborder" style="background-color: #fff;">
		<tr class="liste_titre">
			<td colspan="2"><?php echo $langs->trans('Parameters') ?></td>
		</tr>
		<tr>
			<td><?php echo $langs->trans('PricesQtyOnTotalInvoiceQty') ?></td><td><?php echo $form->combo('', 'TOptions[TARIF_TOTAL_QTY_ON_TOTAL_INVOICE_QTY]',array(0=>'Non',1=>'Oui'), $conf->global->TARIF_TOTAL_QTY_ON_TOTAL_INVOICE_QTY)  ?></td>				
		</tr>
		<tr>
			<td><?php echo $langs->trans('tarifTARIF_CAN_SET_PACKAGE_ON_LINE') ?></td><td><?php echo $form->combo('', 'TOptions[TARIF_CAN_SET_PACKAGE_ON_LINE]',array(0=>'Non',1=>'Oui'), $conf->global->TARIF_CAN_SET_PACKAGE_ON_LINE)  ?></td>				
		</tr>
		<tr>
			<td><?php echo $langs->trans('tarifTARIF_USE_PRICE_OF_PRECEDENT_LEVEL_IF_ZERO') ?></td><td><?php echo $form->combo('', 'TOptions[TARIF_USE_PRICE_OF_PRECEDENT_LEVEL_IF_ZERO]',array(0=>'Non',1=>'Oui'), $conf->global->TARIF_USE_PRICE_OF_PRECEDENT_LEVEL_IF_ZERO)  ?></td>				
		</tr>
		<tr>
			<td><?php echo $langs->trans('tarifTARIF_FACTURE_DISPATCH_ON_EXPEDITION') ?></td><td><?php echo $form->combo('', 'TOptions[TARIF_FACTURE_DISPATCH_ON_EXPEDITION]',array(0=>'Non',1=>'Oui'), $conf->global->TARIF_FACTURE_DISPATCH_ON_EXPEDITION)  ?></td>				
		</tr>
		<tr>
			<td><?php echo $langs->trans('tarifTARIF_DONT_ADD_UNIT_SELECT') ?></td><td><?php echo $form->combo('', 'TOptions[TARIF_DONT_ADD_UNIT_SELECT]',array(0=>'Non',1=>'Oui'), $conf->global->TARIF_DONT_ADD_UNIT_SELECT)  ?></td>				
		</tr>
		<tr>
			<td><?php echo $langs->trans('tarifTARIF_KEEP_FIELD_CONDITIONNEMENT_FOR_SERVICES') ?></td><td><?php echo $form->combo('', 'TOptions[TARIF_KEEP_FIELD_CONDITIONNEMENT_FOR_SERVICES]',array(0=>'Non',1=>'Oui'), $conf->global->TARIF_KEEP_FIELD_CONDITIONNEMENT_FOR_SERVICES)  ?></td>				
		</tr>
		<tr>
			<td><?php echo $langs->trans('tarifTARIF_USE_METRE') ?></td><td><?php echo $form->combo('', 'TOptions[TARIF_USE_METRE]',array(0=>'Non',1=>'Oui'), $conf->global->TARIF_USE_METRE)  ?></td>				
		</tr>
		<tr>
			<td><?php echo $langs->trans('tarifTARIF_ONLY_UPDATE_LINE_PRICE') ?></td><td><?php echo $form->combo('', 'TOptions[TARIF_ONLY_UPDATE_LINE_PRICE]',array(0=>'Non',1=>'Oui'), $conf->global->TARIF_ONLY_UPDATE_LINE_PRICE)  ?></td>				
		</tr>
		<tr>
			<td><?php echo $langs->trans('tarifTARIF_DOL_DEFAULT_UNIT') ?></td><td><?php echo 'définition non disponible ici. cf.divers'  ?></td>				
		</tr>
		
	</table>
	<p align="right">
		
		<input type="submit" name="bt_save" value="<?php echo $langs->trans('Save') ?>" /> 
		
	</p>
	
	</form>
	
	
	<br /><br />
	<?php
}
?>

<table width="100%" class="noborder">
	<tr class="liste_titre">
		<td>A propos</td>
		<td align="center">&nbsp;</td>
		</tr>
		<tr class="impair">
			<td valign="top">Module développé par </td>
			<td align="center">
				<a href="http://www.atm-consulting.fr/" target="_blank">ATM Consulting</a>
			</td>
		</td>
	</tr>
</table>
<?php
