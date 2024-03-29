<?php
/**
 * jtlwawi_connector/dbeS/Artikel.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.12 / 13.03.07
*/

require_once("syncinclude.php");

$return = 3;
if(auth()){
	if(intval($_POST["action"]) == 1 && intval($_POST['KeyArtikel'])){
		$return = 0;
		//hole einstellungen
		$cur_query = eS_execute_query("SELECT * FROM ".DB_PREFIX."eazysales_einstellungen");
		$einstellungen = mysql_fetch_object($cur_query);
		
		$conf_query = eS_execute_query("SELECT configuration_key, configuration_value FROM ".DB_PREFIX."configuration WHERE configuration_key LIKE 'GLOBAL_COLUMN_%'");
		while($conf = mysql_fetch_array($conf_query))
			define($conf['configuration_key'], $conf['configuration_value']);
		
		$artikel->kArtikel = realEscape($_POST["KeyArtikel"]);
		$artikel->cArtNr = realEscape($_POST["ArtikelNo"]);
		$artikel->cName = realEscape($_POST["ArtikelName"]);
		$artikel->cBeschreibung = realEscape($_POST["ArtikelBeschreibung"]);
		$artikel->fVKBrutto = realEscape($_POST["ArtikelVKBrutto"]);
		$artikel->fVKNetto = realEscape($_POST["ArtikelVKNetto"]);
		$artikel->fMwSt = realEscape($_POST["ArtikelMwSt"]);
		$artikel->cAnmerkung = realEscape($_POST["ArtikelAnmerkung"]);
		$artikel->nLagerbestand = max(realEscape($_POST["ArtikelLagerbestand"]), 0);
		$artikel->cEinheit = realEscape($_POST["ArtikelEinheit"]);
		$artikel->nMindestbestellmaenge = realEscape($_POST["ArtikelMindBestell"]);
		$artikel->cBarcode = realEscape($_POST["ArtikelBarcode"]);
		$artikel->fVKHaendlerBrutto = realEscape($_POST["ArtikelVKHaendlerBrutto"]);
		$artikel->fVKHaendlerNetto = realEscape($_POST["ArtikelVKHaendlerNetto"]);
		$artikel->cTopArtikel = realEscape($_POST["TopAngebot"]);
		$artikel->fGewicht = realEscape($_POST["Gewicht"]);
		$artikel->cNeu = realEscape($_POST["Neu"]);
		$artikel->cKurzBeschreibung = realEscape($_POST["ArtikelKurzBeschreibung"]);
		$artikel->fUVP = realEscape($_POST["ArtikelUVP"]);
		$artikel->cHersteller = realEscape($_POST["Hersteller"]);
			
		$startseite = 0;
		if($artikel->cTopArtikel=="Y")
			$startseite = 1;
		$shipping_status = '0';
		if($GLOBALS['einstellungen']->shipping_status_id > 0)
			$shipping_status = $GLOBALS['einstellungen']->shipping_status_id;
			
		//update oder insert?
		$products_id = getFremdArtikel($artikel->kArtikel);
		
		if($products_id > 0) { # Update
			eS_execute_query("DELETE FROM ".DB_PREFIX."products_attributes WHERE products_id = ".$products_id);
			eS_execute_query("DELETE FROM ".DB_PREFIX."products_to_categories WHERE products_id = ".$products_id);

			$products_tax_class_id = holeSteuerId($artikel->fMwSt);
			$brand_id = holeHerstellerId($artikel->cHersteller);
			
			eS_execute_query("UPDATE 
									".DB_PREFIX."products 
								SET
									products_fsk18 = '0',
									products_shippingtime = '".$shipping_status."',
									products_startpage = '".$startseite."',
									products_model = '".$artikel->cArtNr."',
									products_price = '".$artikel->fVKNetto."',
									products_tax_class_id = '".$products_tax_class_id."',
									products_quantity = '".$artikel->nLagerbestand."',
									products_ean = '".$artikel->cBarcode."',
									products_weight = '".$artikel->fGewicht."',
									brand_id = '".$brand_id."',
									products_status = '1',
									products_last_modified = NOW(),
									product_template = '".$einstellungen->prod_product_template."',
									options_template = '".$einstellungen->prod_options_template."'
								WHERE 
									products_id = '".$products_id."'");

			eS_execute_query("UPDATE 
									".DB_PREFIX."products_description 
								SET 
									products_name = '".$artikel->cName."',
									products_description = '".$artikel->cBeschreibung."',
									products_short_description = '".$artikel->cKurzBeschreibung."'
								WHERE
									products_id = '".$products_id."'
								AND
									language_id = '".$einstellungen->languages_id."'");
			//kundengrp preise
			insertPreise($products_id);

		} else { # Insert
			//hole Mwst classId
			$products_tax_class_id = holeSteuerId($artikel->fMwSt);
			//setze Hersteller, falls es ihn noch nicht gibt
			$brand_id = holeHerstellerId($artikel->cHersteller);
			
			eS_execute_query("INSERT INTO ".DB_PREFIX."products 
									(products_shippingtime, 
									products_startpage,
									products_model,
									products_price, 
									products_tax_class_id, 
									products_quantity, 
									products_ean, 
									products_weight, 
									brand_id, 
									product_template, 
									options_template, 
									products_status, 
									products_date_added,
									products_col_top,
									products_col_left,
									products_col_right,
									products_col_bottom) 
								VALUES 
									('".$shipping_status."',
									'".$startseite."',
									'".$artikel->cArtNr."',
									'".$artikel->fVKNetto."',
									'".$products_tax_class_id."',
									'".$artikel->nLagerbestand."',
									'".$artikel->cBarcode."',
									'".$artikel->fGewicht."',
									'".$brand_id."',
									'".$einstellungen->prod_product_template."',
									'".$einstellungen->prod_options_template."',
									'1',
									NOW(),
									'".GLOBAL_COLUMN_TOP."',
									'".GLOBAL_COLUMN_LEFT."',
									'".GLOBAL_COLUMN_RIGHT."',
									'".GLOBAL_COLUMN_BOTTOM."')");

			$products_id = mysql_insert_id();
			if($products_id > 0) {
				insertPreise($products_id);
				eS_execute_query("INSERT INTO 
									".DB_PREFIX."products_description 
									(products_id, 
									products_name, 
									products_description, 
									products_short_description, 
									language_id) 
								VALUES 
									(".$products_id.",
									'".$artikel->cName."',
									'".$artikel->cBeschreibung."',
									'".$artikel->cKurzBeschreibung."',
									'".$einstellungen->languages_id."')");

				setMappingArtikel($artikel->kArtikel,$products_id);
				
				//erstelle leere description für alle anderen Sprachen
				$sonstigeSprachen = getSonstigeSprachen($einstellungen->languages_id);
				if(is_array($sonstigeSprachen)) {
					foreach($sonstigeSprachen AS $sonstigeSprache) {
						eS_execute_query("INSERT INTO ".DB_PREFIX."products_description 
												(products_id,
												products_name,
												products_description,
												products_short_description,
												language_id)
											VALUES
												('".$products_id."',
												'".$artikel->cName."',
												'".$artikel->cBeschreibung."',
												'".$artikel->cKurzBeschreibung."',
												'".$sonstigeSprache."')");
					}
				}

			} else //Fehler aufgetreten
				$return = 1;
		}

		if ($products_id > 0){
			//setze Kundengruppenerlaubnis für alle gruppen
			//Attribute.php bearbeitet Ausnahmen der Kundengruppenerlaubnis
			setzeKundengruppenerlaubnis("", $products_id);
			
			//VPE
			$products_vpe_id=0;
			//gibt es schon so einen products_vpe?
			$cur_query = eS_execute_query("select products_vpe_id from ".DB_PREFIX."products_vpe where language_id=".$einstellungen->languages_id." and  products_vpe_name=\"".$artikel->cEinheit."\"");
			$products_vpe_id_arr = mysql_fetch_row($cur_query);
			if ($products_vpe_id_arr[0]>0)
				$products_vpe_id=$products_vpe_id_arr[0];

			else {
				$cur_query = eS_execute_query("SELECT max(products_vpe_id) from ".DB_PREFIX."products_vpe");
				$max_shipping_products_vpe_arr = mysql_fetch_row($cur_query);
				$products_vpe_id = $max_shipping_products_vpe_arr[0]+1;
				eS_execute_query("insert into ".DB_PREFIX."products_vpe (products_vpe_id, language_id, products_vpe_name) values ($products_vpe_id, $einstellungen->languages_id, \"$artikel->cEinheit\")");
			}
			eS_execute_query("update ".DB_PREFIX."products set products_vpe=".$products_vpe_id." where products_id=".$products_id);
		}
 	} else
		$return = 5;

	if (intval($_POST["action"]) == 3 && intval($_POST['KeyArtikel'])){
		$products_id = getFremdArtikel(intval($_POST['KeyArtikel']));
		if ($products_id>0)
			eS_execute_query("update ".DB_PREFIX."products set products_status=0 where products_id=".$products_id);
		$return = 0;
	}
}

mysql_close();
echo($return);
logge($return);

function insertPreise($products_id){
	$personalOfferTable = DB_PREFIX."personal_offers_by_customers_status_";
	$endKunden_arr = explode(";",$GLOBALS['einstellungen']->mappingEndkunde);
	foreach ($endKunden_arr as $customers_status_id){
		if ($customers_status_id>=0 && strlen($customers_status_id)>0){
			$table = $personalOfferTable.$customers_status_id;
			eS_execute_query("delete from $table where products_id=".$products_id);
			eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,1,".floatval($_POST["ArtikelVKNetto"]).")");
			if (intval($_POST["PAnz1"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["PAnz1"]).",".floatval($_POST["PPreis1"]).")");
			if (intval($_POST["PAnz2"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["PAnz2"]).",".floatval($_POST["PPreis2"]).")");
			if (intval($_POST["PAnz3"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["PAnz3"]).",".floatval($_POST["PPreis3"]).")");
			if (intval($_POST["PAnz4"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["PAnz4"]).",".floatval($_POST["PPreis4"]).")");
			if (intval($_POST["PAnz5"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["PAnz5"]).",".floatval($_POST["PPreis5"]).")");
		}
	}
	$haendlerKunden_arr = explode(";",$GLOBALS['einstellungen']->mappingHaendlerkunde);
	foreach ($haendlerKunden_arr as $customers_status_id)
	{
		if ($customers_status_id>=0 && strlen($customers_status_id)>0)
		{
			$table = $personalOfferTable.$customers_status_id;
			eS_execute_query("delete from $table where products_id=".$products_id);
			eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,1,".floatval($_POST["ArtikelVKHaendlerNetto"]).")");
			if (intval($_POST["HAnz1"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["HAnz1"]).",".floatval($_POST["HPreis1"]).")");
			if (intval($_POST["HAnz2"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["HAnz2"]).",".floatval($_POST["HPreis2"]).")");
			if (intval($_POST["HAnz3"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["HAnz3"]).",".floatval($_POST["HPreis3"]).")");
			if (intval($_POST["HAnz4"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["HAnz4"]).",".floatval($_POST["HPreis4"]).")");
			if (intval($_POST["HAnz5"])>0)
				eS_execute_query("insert into $table (products_id, quantity, personal_offer) values ($products_id,".intval($_POST["HAnz5"]).",".floatval($_POST["HPreis5"]).")");
		}
	}
}

function holeHerstellerId($cHersteller)
{
	if (strlen($cHersteller)>0)
	{
		//ex. dieser Hersteller?
		$cur_query = eS_execute_query("select brand_id from ".DB_PREFIX."brand where brand_name=\"".$cHersteller."\"");
		$manu = mysql_fetch_object($cur_query);
		if ($manu->brand_id>0)
			return $manu->brand_id;
		else 
		{
			//erstelle diesen Hersteller
			eS_execute_query("insert into ".DB_PREFIX."brand (brand_name, date_added) values (\"".$cHersteller."\", now())");
			$query = eS_execute_query("select LAST_INSERT_ID()");
			$manu_id_arr = mysql_fetch_row($query);
			eS_execute_query("insert into ".DB_PREFIX."brand_info (brand_id, languages_id) values (".$manu_id_arr[0].", ".$GLOBALS['einstellungen']->languages_id.")");
			return $manu_id_arr[0];
		}
	}
	return 0;
}

function holeSteuerId($MwSt)
{
	//existiert so ein Steuersatz ?
	$cur_query = eS_execute_query("select tax_class_id from ".DB_PREFIX."tax_rates where tax_zone_id=".$GLOBALS['einstellungen']->tax_zone_id." and tax_rate=".$MwSt);
	$tax = mysql_fetch_object($cur_query);
	if ($tax->tax_class_id>0)
		return $tax->tax_class_id;
	else {
		//erstelle klasse
		eS_execute_query("insert into ".DB_PREFIX."tax_class (tax_class_title, date_added) values (\"JTL-Wawi Steuerklasse ".$MwSt."%\", now())");
		$tax_class_id = mysql_insert_id();
		//füge diesen Steuersatz ein
		eS_execute_query("insert into ".DB_PREFIX."tax_rates (tax_zone_id, tax_class_id, tax_priority, tax_rate, date_added) values (".$GLOBALS['einstellungen']->tax_zone_id.",".$tax_class_id.", ".$GLOBALS['einstellungen']->tax_priority.", ".$MwSt.", now())");
		return $tax_class_id;
	}
}