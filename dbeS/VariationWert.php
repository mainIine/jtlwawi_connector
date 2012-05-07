<?php
/**
 * jtlwawi_connector/dbeS/VariationsWert.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.01 / 17.09.06
*/

require_once('syncinclude.php');

$return = 3;
if(auth()){
	if(intval($_POST["action"]) == 1 && intval($_POST['KeyEigenschaftWert'])){
		$return = 0;
		
		$EigenschaftWert->kEigenschaftWert = intval($_POST["KeyEigenschaftWert"]);
		$EigenschaftWert->kEigenschaft = intval($_POST["KeyEigenschaft"]);
		$EigenschaftWert->fAufpreis = floatval($_POST["Aufpreis"]);
		$EigenschaftWert->cName = realEscape($_POST["Name"]);
		$EigenschaftWert->nSort = intval($_POST["Sort"]);
		$EigenschaftWert->nLager = intval($_POST["Lager"]);
		$EigenschaftWert->cArtikelNr = realEscape($_POST["ArtikelNr"]);
		$EigenschaftWert->fGewichtDiff = floatval($_POST["GewichtDiff"]);

		//hole einstellungen
		$cur_query = eS_execute_query("SELECT 
											languages_id, 
											tax_class_id, 
											tax_zone_id 
										FROM 
											".DB_PREFIX."eazysales_einstellungen");
		$einstellungen = mysql_fetch_object($cur_query);
		
		$products_options_id = getFremdEigenschaft($EigenschaftWert->kEigenschaft);

		if($products_options_id > 0) {
			//schaue, ob dieser EigenschaftsWert bereits global existiert für diese Eigenschaft!!
			$cur_query = eS_execute_query("SELECT 
												pov.products_options_values_id 
											FROM 
												".DB_PREFIX."products_options_values AS pov,
												".DB_PREFIX."products_options_values_to_products_options AS povtpo
											WHERE 
												povtpo.products_options_id = '".$products_options_id."'
											AND 
												povtpo.products_options_values_id = pov.products_options_values_id 
											AND 
												pov.language_id = '".$einstellungen->languages_id."'
											AND 
												pov.products_options_values_name = '".$EigenschaftWert->cName."' ");
			
			$options_values = mysql_fetch_object($cur_query);
			
			if(!$options_values->products_options_values_id) {
				//erstelle diesen Wert global
				//hole max PK
				$query = mysql_fetch_array(mysql_query("SELECT MAX(products_options_values_id) AS next_id FROM ".DB_PREFIX."products_options_values"));
				$options_values->products_options_values_id = ($query['next_id']+1);
				
				eS_execute_query("INSERT INTO ".DB_PREFIX."products_options_values 
										(products_options_values_id,
										language_id,
										products_options_values_name) 
									VALUES 
										('".$options_values->products_options_values_id."',
										'".$einstellungen->languages_id."',
										'".$EigenschaftWert->cName."'
									)");			
				
				//erstelle leere description für alle anderen Sprachen
				$sonstigeSprachen = getSonstigeSprachen($einstellungen->languages_id);
				if(is_array($sonstigeSprachen)){
					foreach ($sonstigeSprachen AS $sonstigeSprache){
						eS_execute_query("INSERT INTO ".DB_PREFIX."products_options_values 
												(products_options_values_id,
												language_id,
												products_options_values_name) 
											VALUES 
												('".$options_values->products_options_values_id."',
												'".$sonstigeSprache."',
												'".$EigenschaftWert->cName."'
											)");
					}
				}
				
				//erstelle verknüpfung zwischen wert und eig
				eS_execute_query("INSERT INTO ".DB_PREFIX."products_options_values_to_products_options 
										(products_options_id,
										products_options_values_id) 
									VALUES
										('".$products_options_id."',
										'".$options_values->products_options_values_id."'
									)");
			}

			$kArtikel = getEigenschaftsArtikel($EigenschaftWert->kEigenschaft);
			if ($kArtikel > 0) {
				$products_id = getFremdArtikel($kArtikel);
				if ($products_id > 0) {
					$cur_query = eS_execute_query("SELECT products_tax_class_id FROM ".DB_PREFIX."products WHERE products_id = '".$products_id."'");
					$cur_tax = mysql_fetch_object($cur_query);
					$Aufpreis = ($EigenschaftWert->fAufpreis/(100+get_tax($cur_tax->products_tax_class_id)))*100;

					$Aufpreis_prefix = "+";
					if ($Aufpreis < 0){
						$Aufpreis_prefix = "-";
						$Aufpreis*=-1;
					}

					$Gewicht_prefix = "+";
					if ($EigenschaftWert->fGewichtDiff < 0) {
						$Gewicht_prefix = "-";
						$EigenschaftWert->fGewichtDiff*=-1;
					}

					eS_execute_query("INSERT INTO 
											".DB_PREFIX."products_attributes (
											products_id,
											options_id,
											options_values_id,
											options_values_price,
											price_prefix,
											attributes_model,
											attributes_stock,
											options_values_weight,
											weight_prefix,
											sortorder) 
										VALUES
											('".$products_id."',
											'".$products_options_id."',
											'".$options_values->products_options_values_id."',
											'".$Aufpreis."',
											'".$Aufpreis_prefix."',
											'".$EigenschaftWert->cArtikelNr."',
											'".$EigenschaftWert->nLager."',
											'".$EigenschaftWert->fGewichtDiff."',
											'".$Gewicht_prefix."',
											'".$EigenschaftWert->nSort."'
										)");

					$last_attribute_id = mysql_insert_id();					
					setMappingEigenschaftsWert($EigenschaftWert->kEigenschaftWert, $last_attribute_id, $kArtikel);
				}
			}
		}
 	} else
		$return=5;
}

mysql_close();
echo($return);
logge($return);