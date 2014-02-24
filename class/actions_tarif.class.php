<?php
class ActionsTarif
{ 
     /** Overloading the doActions function : replacing the parent's function with the one below 
      *  @param      parameters  meta datas of the hook (context, etc...) 
      *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...) 
      *  @param      action             current action (if set). Generally create or edit or null 
      *  @return       void 
      */ 
    function formEditProductOptions($parameters, &$object, &$action, $hookmanager) 
    {
    	/*ini_set('dysplay_errors','On');
			error_reporting(E_ALL); */
    	global $db;
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
		
		
    	if (in_array('propalcard',explode(':',$parameters['context'])) || in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context'])))
        {
			if($action == "editline"){
				
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
									?>
									$('input[name=qty]').parent().after('<td align="right"><input id="poidsAff" type="text" value="<?php if(!is_null($res->tarif_poids)) echo number_format($res->tarif_poids,2,",",""); ?>" name="poidsAff" size="6" /><?php $formproduct->select_measuring_units("weight_unitsAff", ($res->unite_vente) ? $res->unite_vente : DOL_DEFAULT_UNIT, $res->poids); ?></td>');
					
									<?php
								}
								
								?>
								$('form[name=editline]').append('<input id="poids" type="hidden" value="1" name="poids" size="3" />');
					         	$('form[name=editline]').append('<input id="weight_units" type="hidden" value="0" name="weight_units" size="3" />');
					
					         	$('form[name=editline]').submit(function() {
					         	
						         	if($('#poidsAff').length>0) {
						         		$('#poids').val( $('#poidsAff').val() );
						         		$('#weight_units').val( $('select[name=weight_unitsAff]').val() );
						         	} 
						         	return true;
						         	
					         	});
								<?php
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
		
		global $db,$langs;
		include_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
		include_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
		include_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
		include_once(DOL_DOCUMENT_ROOT."/core/lib/functions.lib.php");
		include_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
		include_once(DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php');
		$langs->load("other");

		define('INC_FROM_DOLIBARR', true);
		dol_include_once('/tarif/config.php');
		
		if(!defined('DOL_DEFAULT_UNIT')){
			define('DOL_DEFAULT_UNIT','weight');
		}
		
		if (in_array('propalcard',explode(':',$parameters['context'])) || in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context']))) 
        {
        				
			if($object->line->error)
				dol_htmloutput_mesg($object->line->error,'', 'error');
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
         				$resql = $db->query("SELECT e.tarif_poids, e.poids, pe.unite_vente 
	         									 FROM ".MAIN_DB_PREFIX.$object->table_element_line." as e 
	         									 	LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as pe ON (e.fk_product = pe.fk_object)
	         									 WHERE e.rowid = ".$line->rowid);
						$res = $db->fetch_object($resql);
						
						echo "$('#row-".$line->rowid."').children().eq(3).after('<td align=\"right\">".((!is_null($res->tarif_poids))? number_format($res->tarif_poids,2,",","")." ".measuring_units_string($res->poids,($res->unite_vente) ? $res->unite_vente : DOL_DEFAULT_UNIT) : "")."</td>');";
						//if($line->error != '') echo "alert('".$line->error."');";
         			}

	         		?>
		         	$('#tablelines .liste_titre > td').each(function(){
		         		if($(this).html() == "Qté"){
						var weight_label = "<?=defined('WEIGHT_LABEL') ? WEIGHT_LABEL :  'Poids' ?>";
		         			$(this).after('<td align="right" width="140">'+weight_label+'</td>');
					}
		         	});

		         	$('#np_desc').parent().next().after('<td align="right"><span id="AffUnite" style="display:none;">unité</span><input class="poidsAff" type="text" value="0" name="poidsAff_product" id="poidsAffProduct" size="6" /><?php $formproduct->select_measuring_units("weight_unitsAff_product", ($res->unite_vente) ? $res->unite_vente : DOL_DEFAULT_UNIT,-6); ?></td>');
		         	$('#dp_desc').parent().next().next().next().after('<td align="right"><input class="poidsAff" type="text" value="0" name="poidsAff_libre" size="6"><?php $formproduct->select_measuring_units("weight_unitsAff_libre", ($res->unite_vente) ? $res->unite_vente : DOL_DEFAULT_UNIT,-6); ?></td>');

		         	<?php
				}
					
	         	
	         	?>
	         	$('#addpredefinedproduct').append('<input class="poids_product" type="hidden" value="1" name="poids" size="3">');
	         	$('#addpredefinedproduct').append('<input class="weight_units_product" type="hidden" value="0" name="weight_units" size="3">');
	         	$('#addproduct').append('<input class="poids_libre" type="hidden" value="1" name="poids" size="3">');
	         	$('#addproduct').append('<input class="weight_units_libre" type="hidden" value="0" name="weight_units" size="3">');
	         
	         	$('#addpredefinedproduct,#addproduct').submit(function() {
	         		if($('[name=poidsAff_libre]').length>0) {
		         		$('.poids_libre').val( $('[name=poidsAff_libre]').val() );
		         		$('.weight_units_libre').val( $('select[name=weight_unitsAff_libre] option:selected').val() );
		         		$('.poids_product').val( $('#poidsAffProduct').val() );
		         		$('.weight_units_product').val( $('select[name=weight_unitsAff_product] option:selected').val() );
	         			
	         		}
	         		
	         		return true;
	         	});
	         	
	         	//Sélection automatique de l'unité de mesure associé au produit sélectionné
	         	$('#idprod').change( function(){
					$.ajax({
						type: "POST"
						,url: "<?=DOL_URL_ROOT; ?>/custom/tarif/script/ajax.unite_poids.php"
						,dataType: "json"
						,data: {fk_product: $('#idprod').val()}
						},"json").then(function(select){
							if(select.unite != ""){
								if(select.unite_vente != ""){
									$('select[name=weight_unitsAff_product]').remove();
									$('#poidsAffProduct').after(select.unite_vente);
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
