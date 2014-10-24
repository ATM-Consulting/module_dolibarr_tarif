<?php
$res=@include("../../main.inc.php");						// For root directory
if (! $res) $res=@include("../../../main.inc.php");			// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/abricot/includes/class/class.form.core.php');

$langs->load("admin");
$langs->load('tarif@tarif');

global $db;

// Security check
if (! $user->admin) accessforbidden();

$action=GETPOST('action');
$id=GETPOST('id');

/*
 * Action
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

llxHeader('',$langs->trans("tarifConfigSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("tarifConfigSetup"),$linkback,'tarif@tarif');

print '<br>';

$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="500">'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">'.$langs->trans("Value").'</td>'."\n";


$form=new Form($db);
// Add shipment as titles in invoice
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("PricesQtyOnTotalInvoiceQty").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_TARIF_TOTAL_QTY_ON_TOTAL_INVOICE_QTY">';
print $form->selectyesno("TARIF_TOTAL_QTY_ON_TOTAL_INVOICE_QTY",$conf->global->TARIF_TOTAL_QTY_ON_TOTAL_INVOICE_QTY,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

print '</table>';

// Footer
llxFooter();
// Close database handler
$db->close();
