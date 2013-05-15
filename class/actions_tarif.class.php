<?php

class ActionsTarif
{ 
     /** Overloading the doActions function : replacing the parent's function with the one below 
      *  @param      parameters  meta datas of the hook (context, etc...) 
      *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...) 
      *  @param      action             current action (if set). Generally create or edit or null 
      *  @return       void 
      */ 
    function doActions($parameters, &$object, &$action, $hookmanager) 
    {
    	if (in_array('ordersuppliercard',explode(':',$parameters['context']))) 
        { 
          
		  
		   $this->resprints='';
        }
 
        /*$this->results=array('myreturn'=>$myvalue);
        $this->resprints='';
 */
        return 0;
    }
    
	function formCreateProductSupplierOptions($parameters, &$object, &$action, $hookmanager) {
				
		global $db;
		
		if (in_array('ordersuppliercard',explode(':',$parameters['context']))) 
        {
        	?> 
         	<script type="text/javascript">
	         	$('#idprodfournprice').after('<span id="span_condi"> ou </span><select id="conditionnement" name="conditionnement" class="flat"></select>');
	         	$('#conditionnement, #span_condi').hide();
	         	$('#idprodfournprice').change( function(){
	         		$.ajax({
	         			type: "POST"
	         			,url: "<?=DOL_URL_ROOT; ?>/custom/tarif/script/ajax.liste_conditionnement.php"
	         			,dataType: "json"
	         			,data: {fk_fourn_price: $('#idprodfournprice option:selected').val()}
	         		},"json").then(function(select){
	         			if(select.length > 0){
	         				$('#conditionnement').empty().show();
	         				$('#span_condi').show();
	         				$.each(select, function(i,option){
	         					$('#conditionnement').prepend('<option value="'+option.id+'">'+option.intitule+'</option>');
	         				})
	         				$('#conditionnement').prepend('<option value="0" selected="selected">S&eacute;lectionnez un conditionnement</option>');
	         			}
	         			else{
	         				$('#conditionnement, #span_condi').hide();
	         			}
	         		});
	         	});
         	</script>
         	<?php
        }
		return 0;
	}

	function formAddObjectLine ($parameters, &$object, &$action, $hookmanager) {
		
		global $db;
		
		if (in_array('propalcard',explode(':',$parameters['context'])) || in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context']))) 
        {
        	?> 
         	<script type="text/javascript">
	         	$('#idprod').after('<span id="span_condi"> ou </span><select id="conditionnement" name="conditionnement" class="flat"></select>');
	         	$('#conditionnement, #span_condi').hide();
	         	$('#idprod').change( function(){
	         		$.ajax({
	         			type: "POST"
	         			,url: "<?=DOL_URL_ROOT; ?>/custom/tarif/script/ajax.liste_conditionnement.php"
	         			,dataType: "json"
	         			,data: {fk_product: $('#idprod option:selected').val()}
	         		},"json").then(function(select){
	         			if(select.length > 0){
	         				$('#conditionnement').empty().show();
	         				$('#span_condi').show();
	         				$.each(select, function(i,option){
	         					$('#conditionnement').prepend('<option value="'+option.id+'">'+option.intitule+'</option>');
	         				})
	         				$('#conditionnement').prepend('<option value="0" selected="selected">S&eacute;lectionnez un conditionnement</option>');
	         			}
	         			else{
	         				$('#conditionnement, #span_condi').hide();
	         			}
	         		});
	         	});
         	</script>
         	<?php
        }
		return 0;
	}
}