<?php
/**
 * jtlwawi_connector/dbeS/Variation.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.01 / 16.09.06
*/

require_once("syncinclude.php");

$return = 3;
if(auth()){
	if(intval($_POST["action"]) == 1 && intval($_POST['KeyEigenschaft'])){		
		$Eigenschaft->kEigenschaft = intval($_POST["KeyEigenschaft"]);
		$Eigenschaft->kArtikel = intval($_POST["KeyArtikel"]);
		$Eigenschaft->cName = realEscape($_POST["Name"]);
		$Eigenschaft->nSort = intval($_POST["Sort"]);

		$products_id = getFremdArtikel($Eigenschaft->kArtikel);
		if($products_id > 0) {
			$cur_query = eS_execute_query("select languages_id from ".DB_PREFIX."eazysales_einstellungen");
			$einstellungen = mysql_fetch_object($cur_query);

			$cur_query = eS_execute_query("SELECT 
												products_options_id 
											FROM 
												".DB_PREFIX."products_options 
											WHERE 
												language_id = '".$einstellungen->languages_id."'
											AND 
												products_options_name = '".$Eigenschaft->cName."'");
			$options_id = mysql_fetch_object($cur_query);
			if(!$options_id->products_options_id){
				$query = mysql_fetch_array(mysql_query("SELECT MAX(products_options_id) AS next_id FROM ".DB_PREFIX."products_options"));
				$options_id->products_options_id = ($query['next_id']+1);
				
				eS_execute_query("INSERT INTO ".DB_PREFIX."products_options 
										(products_options_id,
										language_id,
										products_options_name) 
									VALUES 
										('".$options_id->products_options_id."',
										'".$einstellungen->languages_id."',
										'".$Eigenschaft->cName."'
									)");

				$sonstigeSprachen = getSonstigeSprachen($einstellungen->languages_id);
				if(is_array($sonstigeSprachen)){
					foreach ($sonstigeSprachen AS $sonstigeSprache) {
						eS_execute_query("INSERT INTO ".DB_PREFIX."products_options 
												(products_options_id,
												language_id,
												products_options_name) 
											VALUES 
												('".$options_id->products_options_id."',
												'".$sonstigeSprache."',
												'".$Eigenschaft->cName."'
											)");
					}
				}
			}
			setMappingEigenschaft($Eigenschaft->kEigenschaft, $options_id->products_options_id, $Eigenschaft->kArtikel);
			$return = 0;
		}
 	}
	else
		$return=5;
}

mysql_close();
echo($return);
logge($return);