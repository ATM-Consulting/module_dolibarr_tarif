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
 * \file    class/actions_tarif.class.php
 * \ingroup tarif
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsTarif
 */
class ActionsTarif
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct($db)
	{
		global $langs;

		$this->db = $db;
		$langs->load('tarif@tarif');
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function doActions($parameters, &$object, &$action, $hookmanager)
	{
		return 0;
	}


	function formObjectOptions($parameters, &$object, &$action, $hookmanager) {
		global $langs;

		$TContext = explode(':',$parameters['context']);
		$intersect = array_intersect($TContext, array('propalcard', 'ordercard', 'ordersuppliercard', 'invoicecard'));

		if (!empty($intersect))
		{
			?>
			<script type="text/javascript">
				var dialog = '<div id="dialog-metre" title="<?php print $langs->trans('tarifSaveMetre'); ?>"><p><input type="text" name="metre_desc" /></p></div>';
				$(document).ready(function() {
					$('body').append(dialog);
					$('#dialog-metre').dialog({
						autoOpen:false
						,buttons: {
							"Ok": function() {
								$(this).dialog("close");
							}
							,"Annuler": function() {
								$(this).dialog("close");
							}
						}
						,close: function( event, ui ) {
							var metre = $('input[name=metre_desc]').val();
							$('input[name=metre]').val(metre );
							$('input[name=poidsAff_product]').val( eval(metre) );
						}
					});
				});

				function showMetre() {
					$('textarea[name=metre_desc]').val( $('input[name=metre]').val() );
					$('#dialog-metre').dialog('open');
				}

			</script>
			<?php
		}

		return 0;
	}

	function formEditProductOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $db,$conf;

		$TContext = explode(':',$parameters['context']);
		$intersect = array_intersect($TContext, array('propalcard', 'ordercard', 'ordersuppliercard', 'invoicecard'));

		if (!empty($intersect))
		{
			dol_include_once('/commande/class/commande.class.php');
			dol_include_once("/compta/facture/class/facture.class.php");
			dol_include_once("/comm/propal/class/propal.class.php");
			dol_include_once("/core/lib/product.lib.php");
			dol_include_once('/product/class/html.formproduct.class.php');

			if (!define('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', true);
			dol_include_once('/tarif/config.php');

			if(empty($conf->global->TARIF_DOL_DEFAULT_UNIT)) $conf->global->TARIF_DOL_DEFAULT_UNIT = 'weight';

			if ($action === 'editline' || $action === "edit_line")
			{
				$currentLine = &$parameters['line'];
				?>
				<script type="text/javascript">
					/* script tarif */
					$(document).ready(function(){
						<?php
						$formproduct = new FormProduct($db);

                        // TODO: re-implement this feature when needed
						if(true || !empty($conf->global->DONT_ADD_UNIT_SELECT))
						{
							null;
						}
						else
						{
							$sql = "SELECT e.tarif_poids, e.poids, pe.unite_vente,e.metre 
									FROM ".MAIN_DB_PREFIX.$object->table_element_line." as e 
									LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as pe ON (e.fk_product = pe.fk_object)
									WHERE e.rowid = ".$currentLine->id;

							$resql = $db->query($sql);
							$res = $db->fetch_object($resql);

							?>$('input[name=qty]').parent().after('<td align="right"><?php

								if(!empty($conf->global->TARIF_CAN_SET_PACKAGE_ON_LINE))
								{
									?><input id="poidsAff" type="text" value="<?php echo (!is_null($res->tarif_poids)) ? number_format($res->tarif_poids,2,",","") : '' ?>" name="poidsAff_product" size="6" /><?php
								}
								print ($res->poids==69) ? 'U' : $formproduct->select_measuring_units("weight_unitsAff_product", ($res->unite_vente) ? $res->unite_vente : $conf->global->TARIF_DOL_DEFAULT_UNIT, $res->poids);

								if(!empty($conf->global->TARIF_USE_METRE))
								{
									print '<a href="javascript:showMetre()">M</a><input type="hidden" name="metre" value="'.$res->metre.'" />';
								}

								?></td>');

							<?php
						}

						?>

					});
				</script>
				<?php
			}

			$this->resprints='';
		}

		return 0;
	}

	function formBuilddocOptions ($parameters, &$object, &$action, $hookmanager)
	{
		global $db,$langs,$conf;

		$TContext = explode(':',$parameters['context']);
		$intersect = array_intersect($TContext, array('propalcard', 'ordercard', 'ordersuppliercard', 'invoicecard'));

		if (!empty($intersect))
		{
			include_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
			include_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
			include_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
			include_once(DOL_DOCUMENT_ROOT."/core/lib/functions.lib.php");
			include_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
			include_once(DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php');

			$langs->load("other");

			if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', true);
			dol_include_once('/tarif/config.php');


			if(empty($conf->global->TARIF_DOL_DEFAULT_UNIT)) $conf->global->TARIF_DOL_DEFAULT_UNIT = 'weight';


			// TODO à delete
			if($object->line->error)
				dol_htmloutput_mesg($object->line->error,'', 'error');

			//var_dump($object->lines);

			?>
			<script type="text/javascript">
				<?php
				$formproduct = new FormProduct($db);
				//echo (count($instance->lines) >0)? "$('#tablelines').children().first().children().first().children().last().prev().prev().prev().prev().prev().after('<td align=\"right\" width=\"50\">Poids</td>');" : '' ;

                // TODO: re-implement this feature when needed
				if(true || !empty($conf->global->DONT_ADD_UNIT_SELECT))
				{
					null;
				}
				else
				{
					// TODO à factoriser...
					foreach($object->lines as $line)
					{
						$idLine = empty($line->id) ? $line->rowid : $line->id;

						$sql = "SELECT e.tarif_poids, e.poids, pe.unite_vente 
								FROM ".MAIN_DB_PREFIX.$object->table_element_line." as e 
								LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as pe ON (e.fk_product = pe.fk_object)
								WHERE e.rowid = ".$idLine;

						$resql = $db->query($sql);
						if (!$resql) {
                            echo '</script>';
						    dol_print_error($db);
						    exit;
                        }
						$res = $db->fetch_object($resql);

						if((float) DOL_VERSION > 3.8)
						{
							?>$('#row-<?php echo $idLine; ?> .linecolqty').after('<td align="right" tarif-col="conditionnement"><?php
						} else {
							?>$('#row-<?php echo $idLine; ?> ').children().eq(3).after('<td align="right" tarif-col="conditionnement"><?php
						}

						if (!is_null($res->tarif_poids))
						{
							if (!empty($conf->global->TARIF_CAN_SET_PACKAGE_ON_LINE))
							{
								//if($res->poids != 69){ //69 = chiffre au hasard pour définir qu'on est sur un type "unité" et non "poids"
								print number_format($res->tarif_poids,2,",","");
								//}
							}
							if ($line->fk_product>0 && $res->poids != 69)
							{
								print " ".measuring_units_string($res->poids,($res->unite_vente) ? $res->unite_vente : $conf->global->TARIF_DOL_DEFAULT_UNIT);
							}
							elseif($res->poids == 69){
								print ' U';
							}
						}
						?></td>'); <?php
							//if($line->error != '') echo "alert('".$line->error."');";
					}

					?>
					$('#tablelines .liste_titre > td').each(function(){
						if($(this).html() == "Qté" || $(this).html() == "Qty"){
							var weight_label = "<?=defined('WEIGHT_LABEL') ? WEIGHT_LABEL :  $langs->trans('Cond'); ?>";
							$(this).after('<td align="right" width="140">'+weight_label+'</td>');
						}
					});

					$('#dp_desc').parent().next().next().next().after('<td align="right" tarif-col="conditionnement_product" type_unite="<?php echo $type_unite; ?>"><?php
						if($conf->global->TARIF_CAN_SET_PACKAGE_ON_LINE) {
						?><input class="poidsAff" type="text" value="0" name="poidsAff_product" id="poidsAffProduct" size="6" /><?php
						}
						print ($type_unite=='unite') ? 'U' :  $formproduct->select_measuring_units("weight_unitsAff_product", ($res->unite_vente) ? $res->unite_vente : $conf->global->TARIF_DOL_DEFAULT_UNIT,0);

						if($conf->global->TARIF_USE_METRE) {
							print '<a href="javascript:showMetre(0)">M</a><input type="hidden" name="metre" value="" />';
						}

						?></td>');

					<?php
				}

				?>
				/*	$('#addpredefinedproduct').append('<input class="poids_product" type="hidden" value="1" name="poids" size="3">');
					$('#addpredefinedproduct').append('<input class="weight_units_product" type="hidden" value="0" name="weight_units" size="3">');
					*/
				$('form#addproduct').append('<input class="poids_libre" type="hidden" value="1" name="poids" size="3">');
				$('form#addproduct').append('<input class="weight_units_libre" type="hidden" value="0" name="weight_units" size="3">');

				$('form#addproduct').submit(function() {
					if($('[name=poidsAff_libre]').length>0) {
						$('[name=poids]').val( $('[name=poidsAff_product]').val() );
						if($('[name=weight_unitsAff_libre]').length>0) $('[name=weight_units]').val( $('select[name=weight_unitsAff_libre]').val() );
					}
					else {
						$('[name=poids]').val( $('[name=poidsAff_libre]').val() );
						if($('[name=weight_unitsAff_product]').length>0) $('[name=weight_units]').val( $('select[name=weight_unitsAff_product]').val() );

					}

					return true;
				});

				//Sélection automatique de l'unité de mesure associé au produit sélectionné
				$('#idprod, #idprodfournprice').change( function(){
					$.ajax({
						type: "POST"
						,url: "<?php echo dol_buildpath('/custom/tarif/script/ajax.unite_poids.php',1); ?>"
						,dataType: "json"
						,data: {
							fk_product: $(this).val(),
							type: $(this).attr('id')
						}
					},"json").then(function(select){
						$('td[tarif-col=conditionnement_product]').attr('type_unite', select.unite);
						if(select.unite != ""){
							if(select.unite_vente != ""){
								$('select[name=weight_unitsAff_product]').remove();
								$('td[tarif-col=conditionnement_product]').append(select.unite_vente);
							}
							$('select[name=weight_unitsAff_product]').val(select.unite);
							$('select[name=weight_unitsAff_product]').prev().show();
							$('#poidsAffProduct').val(select.poids);
							$('input[name=poids]').val(select.poids);
							$('select[name=weight_unitsAff_product]').show();
							$('#AffUnite').hide();
						}
						else if(select.keep_field_cond == 1) {
							$('select[name=weight_unitsAff_product]').hide();
						}
						else{
							$('select[name=weight_unitsAff_product]').prev().hide();
							$('select[name=weight_unitsAff_product]').hide();
							//$('#AffUnite').show();
						}
					});
				});

			</script>
			<?php
		}

		return 0;
	}
}
