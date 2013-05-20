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
    	global $db;
		
    	if (in_array('propalcard',explode(':',$parameters['context']))) 
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
		include_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
		
		if (in_array('ordersuppliercard',explode(':',$parameters['context']))) 
        {
        	$commande = new Commande($db);
        	$commande->fetch($_GET['id']);
			$commande->fetch_lines();
        	?> 
         	<script type="text/javascript">
         		<?php
         			echo (count($commande->lines) >0)? "$('#tablelines').children().first().children().first().children().last().prev().prev().prev().prev().prev().after('<td align=\"right\" width=\"50\">Poids</td>');" : '' ;
         			foreach($commande->lines as $line){
         				$resql = $db->query("SELECT tarif_poids, poids FROM ".MAIN_DB_PREFIX."commandedet WHERE rowid = ".$line->rowid);
						$res = $db->fetch_object($resql);
						switch($res->poids){
							case -6:
								$unite = "mg";
								break;
							case -3:
								$unite = "g";
								break;
							case 0:
								$unite = "kg";
								break;
						}
         				echo "$('#row-".$line->rowid."').children().last().prev().prev().prev().prev().prev().after('<td align=\"right\">".((!is_null($res->tarif_poids))?$res->tarif_poids." ".$unite:"")."</td>');";
         			}
         		?>
	         	$('#add').parent().next().next().next().next().after('<td align="right" width="110">Poids</td>');
	         	$('#qty').parent().after('<td align="right"><input id="poidsAff" type="text" value="0" name="poidsAff" size="3"><select class="flat" name="weight_unitsAff" id="weight_unitsAff"><option value="-6">mg</option><option value="-3">g</option><option selected="selected" value="0">kg</option></select></td>');
	         	$('#addproduct').append('<input id="poids" type="hidden" value="0" name="poids" size="3">');
	         	$('#addproduct').append('<input id="weight_units" type="hidden" value="0" name="weight_units" size="3">');
	         	$('#addproduct').submit(function() {
	         		$('#poids').val( $('#poidsAff').val() );
	         		$('#weight_units').val( $('#weight_unitsAff option:selected').val() );
	         		return true;
	         	});
         	</script>
         	<?php
        }
		return 0;
	}

	function formAddObjectLine ($parameters, &$object, &$action, $hookmanager) {
		
		global $db;
		include_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
		
		if (in_array('propalcard',explode(':',$parameters['context'])) || in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context']))) 
        {
        	if(in_array('propalcard',explode(':',$parameters['context']))){
        		$instance = new Propal($db);
	        	$instance->fetch($_GET['id']);
				$table = "propaldet";
        	}
			elseif(in_array('ordercard',explode(':',$parameters['context']))){
				$instance = new Commande($db);
	        	$instance->fetch($_GET['id']);
				$table = "commandedet";
			}
        	elseif(in_array('invoicecard',explode(':',$parameters['context']))){
        		$instance = new Facture($db);
	        	$instance->fetch((isset($_GET['facid']))?$_GET['facid']:$_GET['id']);
				$table = "facturedet";
        	}
			//echo count($instance->lines);
			
			/*echo '<pre>';
			print_r($object);
			echo '</pre>';*/
        	?> 
         	<script type="text/javascript">
         		<?php
         			echo (count($instance->lines) >0)? "$('#tablelines').children().first().children().first().children().last().prev().prev().prev().prev().prev().after('<td align=\"right\" width=\"50\">Poids</td>');" : '' ;
         			foreach($instance->lines as $line){
         				$resql = $db->query("SELECT tarif_poids, poids FROM ".MAIN_DB_PREFIX.$table." WHERE rowid = ".$line->rowid);
						$res = $db->fetch_object($resql);
						switch($res->poids){
							case -6:
								$unite = "mg";
								break;
							case -3:
								$unite = "g";
								break;
							case 0:
								$unite = "kg";
								break;
						}
         				echo "$('#row-".$line->rowid."').children().last().prev().prev().prev().prev().prev().after('<td align=\"right\">".((!is_null($res->tarif_poids))?$res->tarif_poids." ".$unite:"")."</td>');";
         			}
         		?>
	         	$('#add').parent().next().next().next().next().after('<td align="right" width="110">Poids</td>');
	         	$('#qty').parent().after('<td align="right"><input id="poidsAff" type="text" value="0" name="poidsAff" size="3"><select class="flat" name="weight_unitsAff" id="weight_unitsAff"><option value="-6">mg</option><option value="-3">g</option><option selected="selected" value="0">kg</option></select></td>');
	         	$('#addproduct').append('<input id="poids" type="hidden" value="0" name="poids" size="3">');
	         	$('#addproduct').append('<input id="weight_units" type="hidden" value="0" name="weight_units" size="3">');
	         	$('#addproduct').submit(function() {
	         		$('#poids').val( $('#poidsAff').val() );
	         		$('#weight_units').val( $('#weight_unitsAff option:selected').val() );
	         		return true;
	         	});
         	</script>
         	<?php
        }
		return 0;
	}
}