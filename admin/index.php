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

$action = GETPOST('action', 'alpha');

/*
 * Actions
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}
	
if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/**
 * View
 */

llxHeader('',$langs->trans("tarifConfigSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("tarifConfigSetup"),$linkback,'tarif@tarif');

showParameters();

function showParameters() {
	global $db,$conf,$langs,$bc;
	
	$form=new Form($db);
	$dolibarr_version = (float) DOL_VERSION;
	
	$var=false;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameters").'</td>'."\n";
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("PricesQtyOnTotalInvoiceQty").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('TARIF_TOTAL_QTY_ON_TOTAL_INVOICE_QTY');
	print '</td></tr>';
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("tarifTARIF_CAN_SET_PACKAGE_ON_LINE").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('TARIF_CAN_SET_PACKAGE_ON_LINE');
	print '</td></tr>';
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("tarifTARIF_USE_PRICE_OF_PRECEDENT_LEVEL_IF_ZERO").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('TARIF_USE_PRICE_OF_PRECEDENT_LEVEL_IF_ZERO');
	print '</td></tr>';
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("tarifTARIF_FACTURE_DISPATCH_ON_EXPEDITION").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('TARIF_FACTURE_DISPATCH_ON_EXPEDITION');
	print '</td></tr>';
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("tarifTARIF_DONT_ADD_UNIT_SELECT").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('TARIF_DONT_ADD_UNIT_SELECT');
	print '</td></tr>';
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("tarifTARIF_KEEP_FIELD_CONDITIONNEMENT_FOR_SERVICES").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('TARIF_KEEP_FIELD_CONDITIONNEMENT_FOR_SERVICES');
	print '</td></tr>';
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("tarifTARIF_USE_METRE").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('TARIF_USE_METRE');
	print '</td></tr>';
	
	if($dolibarr_version < 3.8) {
		/*
		 * Configurations obsolètes
		 */
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td>'.$langs->trans("tarifTARIF_ONLY_UPDATE_LINE_PRICE").'</td>';
		print '<td align="center" width="20">&nbsp;</td>';
		print '<td align="center" width="300">';
		print ajax_constantonoff('TARIF_ONLY_UPDATE_LINE_PRICE');
		print '</td></tr>';
	}
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("tarifTARIF_DOL_DEFAULT_UNIT").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print 'définition non disponible ici. cf.divers';
	print '</td></tr>';
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("TARIF_DO_NOT_GET_REMISE_ON_UPDATE_LINE").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('TARIF_DO_NOT_GET_REMISE_ON_UPDATE_LINE');
	print '</td></tr>';
	
	 
	print '</table><br />';
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

llxFooter();