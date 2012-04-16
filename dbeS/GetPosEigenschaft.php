<?php
/**
 * jtlwawi_connector/dbeS/setArtikel.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.01 / 06.06.07
*/

require_once("syncinclude.php");

$return=3;
if (auth())
{
	$return=5;
	if (intval($_POST['KeyBestellPos']))
	{		
		$return = 0;
		//hole einstellungen
		$cur_query = eS_execute_query("select languages_id from ".DB_PREFIX."eazysales_einstellungen");
		$einstellungen = mysql_fetch_object($cur_query);

		//hole orders_products_id
		$orders_products_id = getFremdBestellPos(intval($_POST['KeyBestellPos']));
			
		//hole alle Eigenschaften, die ausgewählt wurden zu dieser bestellung
		$cur_query = eS_execute_query("SELECT 
											opa.*, 
											op.products_tax, 
											op.products_id 
										FROM 
											".DB_PREFIX."orders_products_attributes opa, 
											".DB_PREFIX."orders_products op
										WHERE 
											opa.orders_products_id = ".$orders_products_id." 
										AND 
											op.orders_products_id = opa.orders_products_id 
										ORDER BY 
											opa.orders_products_id");
											
		while ($WarenkorbPosEigenschaft = mysql_fetch_object($cur_query))
		{
			$preisprefix=1;
			if ($WarenkorbPosEigenschaft->price_prefix=="-")
				$preisprefix=-1;

			//hole attribut
			$attribut_query = eS_execute_query("SELECT 	
													products_attributes.products_attributes_id 
												from 
													".DB_PREFIX."products_attributes pa, 
													".DB_PREFIX."products_options po, 
													".DB_PREFIX."products_options_values pov 
												WHERE 
													pa.products_id = ".$WarenkorbPosEigenschaft->products_id." 
												AND 
													pa.options_id = po.products_options_id 
												AND 
													pa.options_values_id = pov.products_options_values_id 
												AND 
													pa.options_values_price = ".$WarenkorbPosEigenschaft->options_values_price." 
												AND 
													po.products_options_name = \"".mysql_real_escape_string($WarenkorbPosEigenschaft->products_options)."\" 
												AND 
													po.language_id=".$einstellungen->languages_id." 
												AND 
													pov.products_options_values_name = \"".mysql_real_escape_string($WarenkorbPosEigenschaft->products_options_values)."\" 
												AND 
													pov.language_id = ".$einstellungen->languages_id);
			
			$attribut_arr = mysql_fetch_row($attribut_query);
			
			echo(CSVkonform($WarenkorbPosEigenschaft->orders_products_attributes_id).';');
			echo(CSVkonform(intval($_POST['KeyBestellPos'])).';');
			echo(';');
			echo(CSVkonform(getEsEigenschaftsWert($attribut_arr[0],getEsArtikel($WarenkorbPosEigenschaft->products_id))).';');
			echo(CSVkonform(($WarenkorbPosEigenschaft->options_values_price+$WarenkorbPosEigenschaft->options_values_price*$WarenkorbPosEigenschaft->products_tax/100)*$preisprefix).';');
			echo("\n");
		}
	}
}

mysql_close();
echo($return);
logge($return);
?>