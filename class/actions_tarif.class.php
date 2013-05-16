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
		
    	if (in_array('propalcard',explode(':',$parameters['context'])) || in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context']))) 
        {
        	if(!defined('INC_FROM_DOLIBARR'))define('INC_FROM_DOLIBARR',true);
        	dol_include_once('/tarif/config.php');
		 
			if($action == 'addline'){
           		if(isset($_POST['poids']) && !empty($_POST['poids']) && $_POST['poids'] != 0){ //si poids renseigné alors conditionnement
					$sql = "SELECT quantite, unite, prix
							FROM ".MAIN_DB_PREFIX."tarif_conditionnement
							WHERE fk_product = ".$_POST['idprod']."
							ORDER BY quantite DESC";
							
					$resql = $db->query($sql);
					while($res = $db->fetch_object($resql)){
						//echo "ok ";
						if($res->quantite <= ($_POST['qty'] * $_POST['poids'])){
							$prod = new Product($db);
							$prod->fetch($_POST['idprod']);
							
							$commande = new Commande($db);
							$commande->addline(
										$_POST['id'],
										$prod->description,
										$res->prix,
										$_POST['qty'],
										$_POST['tva_tx'],
										0,
										0,
										$_POST['idprod'],
										$_POST['remise_percent'],
										0,
										0,
										"HT",
										0,
										'',
										'',
										$_POST['type'],
										-1,
										0,
										$_POST['fk_parent_line'],
										null,
										0,
										($_POST['product_label']?$_POST['product_label']:''));
							break;
						}
					}
           		}
           	}
           	
			$this->resprints='';
        }
 
        /*$this->results=array('myreturn'=>$myvalue);
        $this->resprints='';
 */
        return 1;
    }
    
	function formCreateProductSupplierOptions($parameters, &$object, &$action, $hookmanager) {
				
		global $db;
		
		if (in_array('ordersuppliercard',explode(':',$parameters['context']))) 
        {
        	/*echo '<pre>';
        	print_r($object->lines);
			echo '</pre>';*/
			if($action == "addline"){
           		//echo $object->weight;
           		/*$quantite = $object->weight * $object->
           		$ATMdb = new Tdb;
			   	$sql = "SELECT "
           		print_r($object->weight);*/
           }
        	/*?> 
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
         	<?php*/
        }
		return 0;
	}

	function formAddObjectLine ($parameters, &$object, &$action, $hookmanager) {
		
		global $db;
		
		if (in_array('propalcard',explode(':',$parameters['context'])) || in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context']))) 
        {
        	$commande = new Commande($db);
        	$commande->fetch($_GET['id']);
			$commande->fetch_lines();
        	?> 
         	<script type="text/javascript">
         		<?php echo (count($commande->lines) >0)? "$('.liste_titre').first().children().last().prev().prev().prev().prev().prev().after('<td align=\"right\" width=\"50\">Poids</td>');" : '' ;?>
         		$('#row-2').children().last().prev().prev().prev().prev().prev().after('<td> </td>');
	         	$('#add').parent().next().next().next().next().after('<td align="right" width="50">Poids</td>');
	         	$('#qty').parent().after('<td align="right"><input id="poidsAff" type="text" value="0" name="poidsAff" size="3"></td>');
	         	$('#addproduct').append('<input id="poids" type="hidden" value="0" name="poids" size="3">');
	         	$('#addproduct').submit(function() {
	         		$('#poids').val( $('#poidsAff').val() );
	         		
	         		return true;
	         	});
         	</script>
         	<?php
        }
		return 0;
	}
}