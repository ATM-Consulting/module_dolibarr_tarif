<?php

class ActionsTarif
{ 
     /** Overloading the doActions function : replacing the parent's function with the one below 
      *  @param      parameters  meta datas of the hook (context, etc...) 
      *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...) 
      *  @param      action             current action (if set). Generally create or edit or null 
      *  @return       void 
      */ 
     
     var $module_number = 104190;
	  function formEditProductOptions($parameters, &$object, &$action, $hookmanager) 
    {
    	global $db,$conf;
		include_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
		include_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
		include_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
		include_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
		include_once(DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php');
		
		define('INC_FROM_DOLIBARR', true);
		dol_include_once('/tarif/config.php');
		
		if(!defined('DOL_DEFAULT_UNIT')){
			define('DOL_DEFAULT_UNIT','weight');
		}
		
		
    	if (in_array('propalcard',explode(':',$parameters['context']))
    		|| in_array('ordercard',explode(':',$parameters['context']))
			|| in_array('ordersuppliercard',explode(':',$parameters['context']))
    		|| in_array('invoicecard',explode(':',$parameters['context'])))
        {
			
			if($action === "editline"){
				
				?>
				<script type="text/javascript">
					$(document).ready(function(){
						$('#tablelines form').attr('name','editline');
						
						<?php
						$formproduct = new FormProduct($db);
						foreach($object->lines as $line){
	         				$resql = $db->query("SELECT e.tarif_poids, e.poids, pe.unite_vente 
	         									 FROM ".MAIN_DB_PREFIX.$object->table_element_line." as e 
	         									 	LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as pe ON (e.fk_product = pe.fk_object)
	         									 WHERE e.rowid = ".$line->rowid);
							$res = $db->fetch_object($resql);
							if($line->rowid == $_REQUEST['lineid'] && $line->product_type == 0){
								
								if(defined('DONT_ADD_UNIT_SELECT') && DONT_ADD_UNIT_SELECT) {
									null;
								}	
								else {
									?>$('input[name=qty]').parent().after('<td align="right"><?php
									
											if($conf->global->TARIF_CAN_SET_PACKAGE_ON_LINE) {
												?><input id="poidsAff" type="text" value="<?php echo (!is_null($res->tarif_poids)) ? number_format($res->tarif_poids,2,",","") : '' ?>" name="poidsAff" size="6" /><?php	
											}

									?></td>');

									<?php
								}
							}
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

	function formBuilddocOptions ($parameters, &$object, &$action, $hookmanager) {
		
		global $db,$langs,$conf;
		include_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
		include_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
		include_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
		include_once(DOL_DOCUMENT_ROOT."/core/lib/functions.lib.php");
		include_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
		include_once(DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php');
		$langs->load("other");
		$langs->load("tarif@tarif");

		define('INC_FROM_DOLIBARR', true);
		dol_include_once('/tarif/config.php');
		
		if(!defined('DOL_DEFAULT_UNIT')){
			define('DOL_DEFAULT_UNIT','weight');
		}
		
		if (in_array('propalcard',explode(':',$parameters['context']))
			|| in_array('ordercard',explode(':',$parameters['context']))
			|| in_array('ordersuppliercard',explode(':',$parameters['context']))
			|| in_array('invoicecard',explode(':',$parameters['context']))) 
        {
        		
			if($object->line->error)
				dol_htmloutput_mesg($object->line->error,'', 'error');
			
			//var_dump($object->lines);
			
        	?>
         	<script type="text/javascript">
         		<?php
         			$formproduct = new FormProduct($db);
         			//echo (count($instance->lines) >0)? "$('#tablelines').children().first().children().first().children().last().prev().prev().prev().prev().prev().after('<td align=\"right\" width=\"50\">Poids</td>');" : '' ;
					
				if(defined('DONT_ADD_UNIT_SELECT') && DONT_ADD_UNIT_SELECT) {
					null;
				}	
				else {

         			foreach($object->lines as $line){
         				
						$idLine = empty($line->id) ? $line->rowid : $line->id;
						
         				$sql = "SELECT e.tarif_poids, e.poids, pe.unite_vente 
	         									 FROM ".MAIN_DB_PREFIX.$object->table_element_line." as e 
	         									 	LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as pe ON (e.fk_product = pe.fk_object)
	         									 WHERE e.rowid = ".$idLine;
         				$resql = $db->query($sql);
						$res = $db->fetch_object($resql);
						
						?>$('#row-<?=$idLine ?>').children().eq(3).after('<td align="right" tarif-col="conditionnement"><?php
						
							if(!is_null($res->tarif_poids)) {
								if($conf->global->TARIF_CAN_SET_PACKAGE_ON_LINE) {
									//if($res->poids != 69){ //69 = chiffre au hasard pour définir qu'on est sur un type "unité" et non "poids"
										print number_format($res->tarif_poids,2,",","");
									//}
								}
								if($line->fk_product>0 && $res->poids != 69){
									print " ".measuring_units_string($res->poids,($res->unite_vente) ? $res->unite_vente : DOL_DEFAULT_UNIT);
								}
								elseif($res->poids == 69){
									print ' U';
								}
							}
						?></td>'); <?
						//if($line->error != '') echo "alert('".$line->error."');";
					}

	         		?>
		         	$('#tablelines .liste_titre > td').each(function(){
		         		if($(this).html() == "Qté" || $(this).html() == "Qty"){
						var weight_label = "<?=defined('WEIGHT_LABEL') ? WEIGHT_LABEL :  $langs->trans('Cond'); ?>";
		         			$(this).after('<td align="right" width="140">'+weight_label+'</td>');
					}
		         	});

		         	$('#np_desc').parent().next().after('<td align="right" tarif-col="conditionnement_product"><span id="AffUnite" style="display:none;"><?php echo $langs->trans('Unit'); ?></span><?php
			         		if($conf->global->TARIF_CAN_SET_PACKAGE_ON_LINE) {
			         			?><input class="poidsAff" type="text" value="0" name="poidsAff_product" id="poidsAffProduct" size="6" /><?php
							}
		         			//print ($type_unite=='unite') ? 'U' :  $formproduct->select_measuring_units("weight_unitsAff_product", ($res->unite_vente) ? $res->unite_vente : DOL_DEFAULT_UNIT,0); 
		         			
		         			?></td>');

		         	$('#dp_desc').parent().next().next().next().after('<td align="right" tarif-col="conditionnement_libre"><?
		         		if($conf->global->TARIF_CAN_SET_PACKAGE_ON_LINE) {
		         			?><input class="poidsAff" type="text" value="0" name="poidsAff_libre" size="6"><?php	
						}
		         	
		         		//print ($type_unite=='unite') ? 'U' :  $formproduct->select_measuring_units("weight_unitsAff_libre", ($res->unite_vente) ? $res->unite_vente : DOL_DEFAULT_UNIT,0); 
		         		?></td>');  	<?php 
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
	         	$('#idprod').change( function(){
					$.ajax({
						type: "POST"
						,url: "<?=dol_buildpath('/custom/tarif/script/ajax.unite_poids.php',1); ?>"
						,dataType: "json"
						,data: {
							fk_product: $('#idprod').val()
							}
						},"json").then(function(select){
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
