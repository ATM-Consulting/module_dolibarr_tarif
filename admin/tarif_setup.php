<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/tarif.php
 * 	\ingroup	tarif
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/tarif.lib.php';
dol_include_once('abricot/includes/lib/admin.lib.php');

// Translations
$langs->load("tarif@tarif");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
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

/*
 * View
 */
$page_name = "TarifSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback);

$notab = -1;
// Configuration header
$head = tarifAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104190Name"),
    $notab,
    "tarif@tarif"
);

// Setup page goes here
$form=new Form($db);
$var=false;
print '<table class="noborder" width="100%">';


if(!function_exists('setup_print_title')){
    print '<div class="error" >'.$langs->trans('AbricotNeedUpdate').' : <a href="http://wiki.atm-consulting.fr/index.php/Accueil#Abricot" target="_blank"><i class="fa fa-info"></i> Wiki</a></div>';
    exit;
}

setup_print_title("Parameters");

// Example with a yes / no select
setup_print_on_off('TARIF_TOTAL_QTY_ON_TOTAL_INVOICE_QTY');
setup_print_on_off('TARIF_CAN_SET_PACKAGE_ON_LINE');
setup_print_on_off('TARIF_USE_PRICE_OF_PRECEDENT_LEVEL_IF_ZERO');

// TODO à check, mais je crois que cette conf n'est pas utilisé
setup_print_on_off('TARIF_FACTURE_DISPATCH_ON_EXPEDITION');

setup_print_on_off('TARIF_DONT_ADD_UNIT_SELECT');
setup_print_on_off('TARIF_KEEP_FIELD_CONDITIONNEMENT_FOR_SERVICES');
setup_print_on_off('TARIF_USE_METRE');

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("TARIF_DOL_DEFAULT_UNIT").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print 'définition non disponible ici. cf.divers';
print '</td></tr>';

setup_print_on_off('TARIF_DO_NOT_GET_REMISE_ON_UPDATE_LINE');
// TODO: uncomment the following line once logging is implemented.
//setup_print_on_off('TARIF_LOG_TARIF_UPDATE');

$var=!$var;

// TODO: uncomment the following section once supplier rates are implemented.
/*print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("set_TARIF_PERCENT_AUTO_CREATE").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300" style="white-space:nowrap;">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_TARIF_PERCENT_AUTO_CREATE">';
print '<input type="text" name="TARIF_PERCENT_AUTO_CREATE" value="'.$conf->global->TARIF_PERCENT_AUTO_CREATE.'" size="10"/>';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';*/


// Example with imput
//setup_print_input_form_part('CONSTNAME', 'ParamLabel');

// Example with color
//setup_print_input_form_part('CONSTNAME', 'ParamLabel', 'ParamDesc', array('type'=>'color'),'input','ParamHelp');

// Example with placeholder
//setup_print_input_form_part('CONSTNAME','ParamLabel','ParamDesc',array('placeholder'=>'http://'),'input','ParamHelp');

// Example with textarea
//setup_print_input_form_part('CONSTNAME','ParamLabel','ParamDesc',array(),'textarea');


print '</table>';

dol_fiche_end($notab);

llxFooter();

$db->close();
