<?php
/**
 * jtlwawi_connector/install/index.php
 * Datenbank installscript für JTL-Wawi Connector
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.04 / 26.10.06
*/

chdir('../../../../');

error_reporting( E_ALL & ~(E_STRICT|E_NOTICE));

require_once('includes/configure.php');
require(DIR_FS_CATALOG.DIR_WS_CLASSES.'class.mercari_db.php');
$db = new mercari_db();

$Con = 0;
if (isset($_POST["DBhost"]) && !empty($_POST["DBhost"]))
	$Con = pruefeConnection();

echo zeigeKopf();
if(schritt1EingabenVollstaendig())
	installiere();
else
	installSchritt1();

echo zeigeFuss();

function zeigeKopf() {
	return '<html>
			<head>
				<meta http-equiv="content-type" content="text/html;charset=utf-8">
				<meta http-equiv="language" content="deutsch, de">
				<meta name="author" content="JTL-Software & SEO:mercari">
				<title>JTL-Wawi Connector für SEO:mercari Installation</title>
				<link rel="stylesheet" type="text/css" href="../admin/jtlwawiConnectorAdmin.css">
			</head>
			<body>
			<div id="header">
				<div id="logo"><img src="../images/connector_header.jpg" alt="SEO:mercari JTL-WawiConnector Installation" /></div>
				<div id="links">
					<a href="http://www.seo-mercari.de/" target="_blank"  class="headerLink">Shop-System</a> |
					<a href="http://faq.seo-mercari.de/" target="_blank"  class="headerLink">Shop-Handbuch</a> |
					<a href="http://forum.seo-mercari.de/" target="_blank" class="headerLink">Forum</a>
				</div>
			</div>
			<div id="wrapper">
				<div id="inner_wrapper">
					<table class="outerTable">
						<tr>
							<td valign="top">';
}

function zeigeFuss() {
						return '</td>
							</tr>
							<tr>
								<td>
									<br /><div style="text-align:center"><a href="http://www.jtl-software.de/jtlwawi.php"><img src="../images/powered_by_jtlwawi.png"></a></div>
								</td>
							</tr>
						</table>
				</div></div>
				<table id="footer" width="100%">
			  <tr>
			    <td align="center" valign="top">
					<a href="http://www.seo-mercari.de" target="_blank">SEO:mercari</a> Copyright &copy; '.date('Y').' -
					ein Projekt von <a href="http://www.siekiera-media.de" target="_blank">siekiera-media</a>
				</td>
			  </tr>
			</table>
				</body>
			</html>';
}

function installSchritt1() {
	global $db;
	
	$configuration = $db->db_query("SELECT configuration_key as cfgKey, configuration_value as cfgValue from ".DB_PREFIX."configuration WHERE configuration_key != 'DB_CACHE'");
	while(!$configuration->EOF) {
		define($configuration->fields['cfgKey'], $configuration->fields['cfgValue']);
		$configuration->MoveNext();
	}

	$category_listing_template_arr = getTemplateArray(CURRENT_TEMPLATE, "categorie_listing");
	$productinfo_template_arr = getTemplateArray(CURRENT_TEMPLATE, "product_info");
	$productoptions_template_arr = getTemplateArray(CURRENT_TEMPLATE, "product_options");
	
	$order_array=array(array('id' => 'p.products_price','text'=>'Artikelpreis'),
				array('id' => 'pd.products_name','text'=>'Artikelname'),
				array('id' => 'p.products_ordered','text'=>'Bestellte Artikel'),
				array('id' => 'p.products_sort','text'=>'Reihung'),
				array('id' => 'p.products_weight','text'=>'Gewicht'),
				array('id' => 'p.products_quantity','text'=>'Lagerbestand'));
				
	$order_array2 = array(array('id' => 'ASC','text'=>'Aufsteigend'),
				array('id' => 'DESC','text'=>'Absteigend'));
								
	if (!$einstellungen->shopURL)
		$einstellungen->shopURL = HTTP_SERVER;
	if (!$einstellungen->tax_priority)
		$einstellungen->tax_priority = 1;
	if (!$einstellungen->versandMwst)
		$einstellungen->versandMwst = 19;
	if (!$einstellungen->tax_zone_id)
		$einstellungen->tax_zone_id = 5;
	if (!$einstellungen->languages_id) {
		if(defined(DEFAULT_LANGUAGE)) {
			$langID = $db->db_query("SELECT languages_id FROM ".DB_PREFIX."languages WHERE code = '".DEFAULT_LANGUAGE."'");
			$einstellungen->languages_id = $langID->fields['languages_id'];
		} elseif (DEFAULT_LANGUAGE!='') {
			$langID = $db->db_query("SELECT languages_id FROM ".DB_PREFIX."languages WHERE code = '".DEFAULT_LANGUAGE."'");
			$einstellungen->languages_id = $langID->fields['languages_id'];
		} else {
			//erstbeste Lang
			$langID = $db->db_query("SELECT languages_id FROM ".DB_PREFIX."languages");		
			$einstellungen->languages_id = $langID->fields['anguages_id'];
		}
	}
	
	if (!$einstellungen->mappingEndkunde) {
		$def_userstatus = $db->db_query("SELECT configuration_value FROM ".DB_PREFIX."configuration WHERE configuration_key = 'DEFAULT_CUSTOMERS_STATUS_ID'");
		$einstellungen->mappingEndkunde = $def_userstatus->fields['configuration_value'];
		
		$def_userstatus_guest = $db->db_query("SELECT configuration_value FROM ".DB_PREFIX."configuration WHERE configuration_key = 'DEFAULT_CUSTOMERS_STATUS_ID_GUEST'");	
		$einstellungen->mappingEndkunde.=";".$def_userstatus_guest->fields['configuration_value'];
	}
	$mappingEndkunde_arr = explode (";",$einstellungen->mappingEndkunde);
	$mappingHaendlerkunde_arr = explode (";",$einstellungen->mappingHaendlerkunde);
	//ende konfig
	
	$hinweis="";
	if ($_POST["installiereSchritt1"]==1)
		$hinweis="Bitte alle Felder vollständig ausfüllen!";
	srand();
	$syncuser = generatePW(8);
	sleep(1);
	$syncpass = generatePW(8); ?>
	<h1>JTL-Wawi Connector Installation</h1><br />
			Dieses Modul erlaubt es, Ihren SEO:mercari Shop mit der kostenlosen Warenwirtschaft <a target="_blank" href="http://www.jtl-software.de/jtlwawi.php">JTL-Wawi</a> zu betreiben. Dieses Modul ist kostenfrei, kann frei weitergegeben werden, Urheber ist JTL-Software. Bei technischen Problemen wenden Sie sich bitte an unser <a target="_blank" href="http://forum.seo-mercari.de">Community Forum</a>.<br /><br />
			Den Funktionsumfang dieses Modul finden Sie unter <a target="_blank" href="http://www.jtl-software.de/jtlwawi_connector.php">http://www.jtl-software.de/jtlwawi_connector.php</a>.<br /><br />
			Die Installation und Inbetriebnahme von JTL-Wawi Connector geschieht auf eigenes Risiko. Haftungsansprüche für evtl. entstandene Schäden werden nicht übernommen! <b>Sichern Sie sich daher vorher sowohl Ihre Shopdatenbank als auch die JTL-Wawi Datenbank.</b><br /><br />
			Für den reibungslosen Im-/ und Export von Daten zwischen JTL-Wawi und Ihrem SEO:mercari-Shop, müssen einige Einstellungen als Standard gesetzt sein.<br /><br />
			<form action="index.php" method="post" name="konfig">
			<input type="hidden" name="install" value="1" />
			<table cellpadding="5" width="100%" class="config">
				<tr>
					<td colspan="2"><h2>Einstellungen</h2></td>
				</tr>
				<tr>
					<td width="45%"><b>Shop URL</b></td><td><input type="text" name="shopurl" size="50" class="konfig" value="<?php echo $einstellungen->shopURL ?>"></td>
				</tr>
				<tr>
					<td><b>Standardwährung</b></td>
					<td>
						<select name="waehrung">
						<?php								
							$currency = $db->db_query("SELECT currencies_id, title FROM ".DB_PREFIX."currencies");
							while (!$currency->EOF) {
								echo '	<option value="'.$currency->fields['currencies_id'].'"'.($currency->fields['currencies_id'] == $einstellungen->currencies_id ? ' selected=""' : '').'>'.		$currency->fields['title'].
										'</option>';
								$currency->MoveNext();
							} 
						?>
						</select>
					</td>
				</tr>
				<tr>
					<td><b>Standardsprache</b></td>
					<td>
						<select name="sprache">
						<?php 
							$lang = $db->db_query("SELECT languages_id, name FROM ".DB_PREFIX."languages");
							while (!$lang->EOF)	{
								echo '	<option value="'.$lang->fields['languages_id'].'"'.($lang->fields['languages_id'] == $einstellungen->languages_id ? ' selected=""' :'').'>
											'.$lang->fields['name'].'
										</option>';
								$lang->MoveNext();
							}
						?>
						</select>														
					</td>
				</tr>
				<tr>
					<td><b>Standardliefertermin</b></td>
					<td>
						<select name="liefertermin">
						<?php
							$liefer = $db->db_query("SELECT shipping_status_id, shipping_status_name FROM ".DB_PREFIX."shipping_status WHERE language_id = '".$einstellungen->languages_id."'");
							while (!$liefer->EOF){
								echo '	<option value="'.$liefer->fields['shipping_status_id'].'"'.($liefer->fields['shipping_status_id']==$einstellungen->shipping_status_id ? ' selected=""' : '').'>
											'.$liefer->fields['shipping_status_name'].'
										</option>';
								$liefer->MoveNext();
							} 
						?>
						</select>														
					</td>
				</tr>
				<tr>
					<td><b>Standard Steuerzone</b></td>
					<td>
						<select name="steuerzone">
						<?php
							$zone = $db->db_query("SELECT geo_zone_id, geo_zone_name FROM ".DB_PREFIX."geo_zones");
							while (!$zone->EOF){
								echo'	<option value="'.$zone->fields['geo_zone_id'].'"'.($zone->fields['geo_zone_id'] == $einstellungen->tax_zone_id ? ' selected=""':'').'>
											'.$zone->fields['geo_zone_name'].'
										</option>';
								$zone->MoveNext();
							} 
						?>
						</select>														
					</td>
				</tr>
				<tr>
					<td><b>Standard Steuerklasse*</b></td>
					<td>
						<select name="steuerklasse">
						<?php
							$klasse = $db->db_query("SELECT tax_class_id,tax_class_title FROM ".DB_PREFIX."tax_class");
							while (!$klasse->EOF){
								echo '	<option value="'.$klasse->fields['tax_class_id'].'"'.($klasse->fields['tax_class_id'] == $einstellungen->tax_class_id ? ' selected=""':'').'>
											'.$klasse->fields['tax_class_title'].'
										</option>';
								$klasse->MoveNext();
							} 
						?>
						</select>														
					</td>
				</tr>
				<tr>
					<td><b>Standard Steuersatzpriorität</b></td>
					<td>
						<input type="text" name="prioritaet" size="50" class="konfig" style="width:30px;" value="<?php echo $einstellungen->tax_priority ?>" />
					</td>
				</tr>
				<tr>
					<td>
						<b>Steuersatz für Versandkosten</b>
					</td>
					<td>
						<input type="text" name="versandMwst" size="50" class="konfig" style="width:30px;" value="<?php echo $einstellungen->versandMwst ?>" />%
					</td>
				</tr>
				<tr>
					<td colspan="2" class="bb">&nbsp;</td>
				</tr>
				<tr>
					<td colspan="2"><h2>Bestellstatusänderungen</h2></td>
				</tr>
				<tr>
					<td>
						<b>Sobald Bestellung erfolgreich in JTL-Wawi übernommen wird, Status setzen auf:</b>
					</td>
					<td>
						<select name="StatusAbgeholt">
							<option value="0">Status nicht ändern</option>
							<?php
								$status = $db->db_query("SELECT 
															orders_status_id, 
															orders_status_name 
														FROM 
															".DB_PREFIX."orders_status 
														WHERE 
															language_id = '".$einstellungen->languages_id."' 
														ORDER BY 
															orders_status_id ASC");
								while (!$status->EOF) {
									print_r($status->fields);
									echo '	<option value="'.$status->fields['orders_status_id'].'"'.($status->fields['orders_status_id'] == $einstellungen->StatusAbgeholt ? ' selected=""':'').'>'
												.$status->fields['orders_status_name'].'
											</option>';
									$status->MoveNext();
								}
							?>
						</select>														
					</td>
				</tr>
				<tr>
					<td><b>Sobald Bestellung in JTL-Wawi versandt wird, Status setzen auf</b></td>
					<td>
						<select name="StatusVersendet">
							<option value="0">Status nicht ändern</option>
							<?php
								$status = $db->db_query("SELECT 
																orders_status_id, 
																orders_status_name 
															FROM 
																".DB_PREFIX."orders_status 
															WHERE 
																language_id='".$einstellungen->languages_id."' 
															ORDER BY 
																orders_status_id ASC");
								while (!$status->EOF){
									echo '	<option value="'.$status->fields['orders_status_id'].'"'.($status->fields['orders_status_id'] == $einstellungen->StatusVersendet ? 'selected=""':'').'>'
												.$status->fields['orders_status_name'].'
											</option>';
									$status->MoveNext();
								}
							?>
						</select>														
					</td>
				</tr>
			</table><br />
			JTL-Wawi kennt z.Zt. nur die Kundengruppen Endkunde und Händlerkunde. Hier können Sie Kundengruppen auf Ihren Shop zuweisen, welche die Händlerpreise zugewiesen bekommen sollen. Alle anderen Kundengruppen erhalten die Endkundenpreise aus JTL-Wawi.<br />
			<table cellpadding="5" width="100%" class="config">
				<tr>
					<td valign="top"><b>JTL-Wawi Händlerkunde</b></td>
					<td>
					<?php
						$grp = $db->db_query("SELECT 
													customers_status_id, 
													customers_status_name 
												FROM 
													".DB_PREFIX."customers_status 
												WHERE 
													language_id='".$einstellungen->languages_id."' 
												ORDER BY 
													customers_status_id ASC");
						while (!$grp->EOF){
							echo '<input type="checkbox" name="haendlerkunde[]" value="'.$grp->fields['customers_status_id'].'"'.(in_array($grp->fields['customers_status_id'],$mappingHaendlerkunde_arr) ? ' checked="checked"':'').'> '.$grp->fields['customers_status_name'].'<br />';
							$grp->MoveNext();
						}
					?>
					</td>
				</tr>
				<tr>
					<td colspan="2" class="bb">&nbsp;</td>
				</td>
				<tr>
					<td colspan="2"><h2>Vorlagen für Kategorien und Artikel</h2></td>
				</td>
				<tr>
					<td valign="top"><b>Produktlisten</b></td>
					<td>
						<b>product_listings.html</b><br />
						Sie können im Adminbereich unter "Design => Globale Einstellungen => Produktlisten Optionen" weitere Einstellungen vornehmen.
					</td>
				</td>
				<tr>
					<td valign="top">
						<b>Kategorieübersicht</b>
					</td>
					<td>
						<select name="cat_template">
						<?php
							if (is_array($category_listing_template_arr)) {	
								foreach ($category_listing_template_arr AS $template) {
									echo '	<option value="'.$template['id'].'"'.($template['id'] == $einstellungen->cat_category_template ? ' selected=""': '').'>
												'.$template['text'].'
											</option>';
									$row++;
								}
							}
						?>
						</select>	
					</td>
				</tr>
				<tr>
					<td valign="top"><b>Artikelsortierung</b></td>
					<td>
						<select name="cat_sorting">
						<?php
							if (is_array($order_array)) {	
								foreach ($order_array AS $sortierung) {
									echo '	<option value="'.$sortierung['id'].'"'.($sortierung['id'] == $einstellungen->cat_sorting ? ' selected=""':'').'>'
												.$sortierung['text'].'
											</option>';
								}
							}
						?>
						</select> 
						<select name="cat_sorting2">
						<?php
							if (is_array($order_array2)) {	
								foreach ($order_array2 AS $sortierung) {
									echo '	<option value="'.$sortierung['id'].'"'.($sortierung['id'] == $einstellungen->cat_sorting2 ? ' selected=""':'').'>'.
												$sortierung['text'].'
											</option>';
								}
							}
						?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>
				<tr>
					<td valign="top"><b>Artikeldetails</b></td>
					<td>
						<select name="product_template">
						<?php
							if (is_array($productinfo_template_arr)){	
								foreach ($productinfo_template_arr AS $template){
									echo '	<option value="'.$template['id'].'"'.($template['id'] == $einstellungen->prod_product_template ? ' selected=""':'').'>'
												.$template['text'].'
											</option>';
								}
							}
						?>
						</select>	
					</td>
				</tr>
				<tr>
					<td valign="top"><b>Artikeloptionen</b></td>
					<td>
						<select name="option_template">
						<?php
							if (is_array($productoptions_template_arr)){	
								foreach ($productoptions_template_arr AS $template){
									echo '	<option value="'.$template['id'].'"'.($template['id'] == $einstellungen->prod_options_template ? ' selected=""':'').'>'
												.$template['text'].'
											</option>';
								}
							}
						?>
						</select>	
					</td>
				</tr>
				<tr>
				<tr>
					<td valign="top"><b>Bestellungen <=> JTL-Wawi</b></td>
					<td>
						Folgende Bestellungen und dazugehörige Kundendaten werden hiermit als "bereits zu JTL-Wawi versandt" markiert und bei einem Webshopabgleich nicht nach JTL-Wawi geholt. Möchten Sie auch alle Bestellungen und zugehörige Kundendaten in JTL-Wawi importieren, so kreuzen Sie <u>nichts</u> an:<br /><br />
						<?php
							$status = $db->db_query("SELECT orders_status_id, orders_status_name FROM ".DB_PREFIX."orders_status WHERE language_id = '".$einstellungen->languages_id."' ORDER BY orders_status_id");
							while (!$status->EOF) {
								echo '<input type="checkbox" name="bestellungen_bestellt[]" value="'.$status->fields['orders_status_id'].'" /> '.$status->fields['orders_status_name'];
								$status->MoveNext();
							}
						?>
					</td>
				</tr>
				</tr>
			</table><br /><br />
			<table cellspacing="0" cellpadding="0" width="100%" class="config">
			<tr>
				<td class="unter_content_header">&nbsp;<b>Synchronsations - Benutzerdaten</b></td>
			</tr>
			<tr>
				<td class="content" align="center">													
					Für die Synchronisation zwischen JTL-Wawi und diesem wird ein Synchronisationsbenutzer benütigt. Bitte <b>notieren Sie sich</b> unbedingt <b>diese Angaben</b> und setzen sie einen starken kryptischen Benutzernamen und Passwort - oder übernehmen Sie die zufällig generierten Vorgaben. Diese Angaben werden einmalig in den JTL-Wawi Einstellungen eingetragen.
					<br /><br /><br />
					<table cellspacing="0" cellpadding="5" width="70%" align="center" style="border-width:1px;border-color:#555;border-style:solid;">
						<tr>
							<td><b>Sync-Benutzername</b></td><td><input type="text" name="syncuser" size="20" class="login" value="<?php echo $syncuser ?>"></td>
						</tr>
						<tr>
							<td><b>Sync-Passwort</b></td><td><input type="text" name="syncpass" size="20" class="login" value="<?php echo $syncpass ?>"></td>
						</tr>
					</table>
					<br /><br />
					<?php echo $hinweis ?>
					<div style="text-align:center"><input type="submit" value="Installation starten" class="button" /></div>
					</form>
				</td>
			</tr>
		</table>
	</td>
	
<?php }

function schritt1EingabenVollstaendig() {
	if (strlen($_POST["syncuser"])>0 && strlen($_POST["syncpass"])>0)
		return 1;
	return 0;
}

function installiere() {
	global $db;
	require(DIR_FS_INC.'inc.sql_query.php');
	$hinweis = sql_query(DIR_FS_ADMIN.'includes/modules/jtlwawi_connector/install/jtlwawi_connector_DB.sql', $_POST['sprache']);
	//inserte syncuser
	if (!$db->db_query("INSERT INTO ".DB_PREFIX."eazysales_sync VALUES ('".$_POST['syncuser']."','".$_POST['syncpass']."')")) $hinweis.="<br />".mysql_error()." Nr: ".mysql_errno();
	
	//Bestellungen gesendet markieren
	$qry_teil="";
	if (is_array($_POST['bestellungen_bestellt'])){
		foreach ($_POST['bestellungen_bestellt'] AS $i => $status){
			if ($i!=0)
				$qry_teil.=" OR orders_status=".$status;
			else
				$qry_teil.=" orders_status=".$status;
		}
	}
	if (strlen($qry_teil) > 1) {
		$orderkey = $db->db_query("SELECT orders_id FROM ".DB_PREFIX."orders where $qry_teil ORDER BY orders_id");
		while (!$orderkey->EOF) {
			if ($orderkey->fields['orders_id'] > 0) {
				$einzel = $db->db_query("SELECT orders_id FROM ".DB_PREFIX."eazysales_sentorders WHERE orders_id = '".$orderkey->fields['orders_id']."'");
				if (!$einzel->_numOfRows)
					$db->db_query("INSERT INTO ".DB_PREFIX."eazysales_sentorders (orders_id) VALUES ('".$orderkey->fields['orders_id']."')");
			}
			$orderkey->MoveNext();
		}
	}
		
	//inserte einstellungen
	$mappingEndkunde="";
	$mappingHaendlerkunde="";
	if(is_array($_POST['haendlerkunde']))
		$mappingHaendlerkunde = implode(";",$_POST['haendlerkunde']);
	
	$shopurl = $_POST['shopurl']; if (!$shopurl) $shopurl="";
	$waehrung = $_POST['waehrung']; if (!$waehrung) $waehrung=0;
	$sprache = $_POST['sprache']; if (!$sprache) $sprache=0;
	$liefertermin = $_POST['liefertermin']; if (!$liefertermin) $liefertermin=0;
	$steuerzone = $_POST['steuerzone']; if (!$steuerzone) $steuerzone=0;
	$steuerklasse = $_POST['steuerklasse']; if (!$steuerklasse) $steuerklasse=0;
	$prioritaet = $_POST['prioritaet']; if (!$prioritaet) $prioritaet=0;
	$versandMwst = floatval($_POST['versandMwst']); if (!$versandMwst) $versandMwst=0;
	$cat_listing = $_POST['cat_listing']; if (!$cat_listing) $cat_listing="";
	$cat_template = $_POST['cat_template']; if (!$cat_template) $cat_template="";
	$cat_sorting = $_POST['cat_sorting']; if (!$cat_sorting) $cat_sorting="";
	$cat_sorting2 = $_POST['cat_sorting2']; if (!$cat_sorting2) $cat_sorting2="";
	$product_template = $_POST['product_template']; if (!$product_template) $product_template="";
	$option_template = $_POST['option_template']; if (!$option_template) $option_template="";
	$statusAbgeholt = $_POST['StatusAbgeholt']; if (!$statusAbgeholt) $statusAbgeholt=0;
	$statusVersandt = $_POST['StatusVersendet']; if (!$statusVersandt) $statusVersandt=0;
	

	$db->db_query("DELETE FROM ".DB_PREFIX."eazysales_einstellungen");
	$db->db_query("INSERT INTO ".DB_PREFIX."eazysales_einstellungen 
						(StatusAbgeholt, 
						StatusVersendet, 
						currencies_id, 
						languages_id, 
						mappingEndkunde, 
						mappingHaendlerkunde, 
						shopURL, 
						tax_class_id, 
						tax_zone_id, 
						tax_priority, 
						shipping_status_id, 
						versandMwst,
						cat_listing_template,
						cat_category_template,
						cat_sorting,
						cat_sorting2,
						prod_product_template,
						prod_options_template) 
					VALUES (
						'".$statusAbgeholt."', 
						'".$statusVersandt."', 
						'".$waehrung."',
						'".$sprache."',
						'".$mappingEndkunde."',
						'".$mappingHaendlerkunde."',
						'".$shopurl."',
						'".$steuerklasse."',
						'".$steuerzone."',
						'".$prioritaet."',
						'".$liefertermin."', 
						".floatval($versandMwst).",
						'".$cat_listing."',
						'".$cat_template."',
						'".$cat_sorting."',
						'".$cat_sorting2."',
						'".$product_template."',
					'".$option_template."')");

	if(!empty($hinweis) > 0) {
		echo '
							<td valign="top" align="center"><br />
								<table cellspacing="0" cellpadding="0" width="96%">
									<tr><td class="content_header" align="center"><h3>JTL-Wawi Connector Datenbankeinrichtung fehlgeschlagen</h3></td></tr>
									<tr><td class="content" align="center"><br />
											<table cellspacing="0" cellpadding="0" width="580">
												<tr>
													<td class="unter_content_header">&nbsp;<b>Bei der Datenbankeinrichtung sind folgende Fehler aufgetreten</b></td>
												</tr>
												<tr>
													<td class="content">
	'.$hinweis.'<br /><br /><br />Lösungen sollten Sie hier finden: <a href="http://forum.seo-mercari.de">SEO:mercari - Community Forum</a>
													</td>
												</tr>
											</table>
									</td></tr>
								</table><br />
							</td>
		';
		
	} else {
		//hole webserver
		echo '<td>
				<table cellspacing="0" cellpadding="0" width="100%" class="config">
					<tr>
						<td class="content">
							<h2>Der JTL-Wawi Connector für SEO:mercari wurde erfolgreich installiert!</h2><br />
							Die Datenbank für JTL-Wawi Connector wurde aufgesetzt. Die Installation ist serverseitig soweit abgeschlossen. Sie müssen nun JTL-Wawi im Menü Einstellungen -> Shop-Einstellungen konfigurieren.<br /><br />
							Folgende Einstellungen müssen Sie in JTL-Wawi eintragen:<br /><br />
							<table width="95%" class="config">
								<tr>
									<td><b>API-KEY:</b></td>
									<td><b>JTL-Wawi Connector</b></td>
								</tr>
								<tr>
									<td><b>Web-Server:</b></td>
									<td><b>'.HTTP_SERVER.DIR_WS_ADMIN.'includes/modules/jtlwawi_connector</b></td>
								</tr>
								<tr>
									<td><b>Web-Benutzer:</b></td>
									<td><b>'.$_POST['syncuser'].'</b></td></tr>
								<tr>
									<td><b>Passwort:</b></td>
									<td><b>'.$_POST['syncpass'].'</b></td>
								</tr>
							</table><br /><br />
							Setzen Sie einen Haken bei "Bilder per HTTP versenden".	Bei den FTP-Einstellungen müssen Sie nichts eintragen.<br /><br />
							<b>Wir wünschen Ihnen viel Erfolg mit Ihrem SEO:mercari Shop!</b>
						</td>
					</tr>
				</table><br />
			</td>';
	}
}

function generatePW($length=8) {
	$dummy = array_merge(range('0','9'), range('a','z'), range('A','Z'));
	mt_srand((double)microtime()*1000000);
	for ($i = 1; $i <= (count($dummy)*2); $i++) {
		$swap= mt_rand(0,count($dummy)-1);
		$tmp= $dummy[$swap];
		$dummy[$swap]= $dummy[0];
		$dummy[0]= $tmp;
	}
	return substr(implode('',$dummy),0,$length);
}

function getTemplateArray($cur_template, $module) {
	$files = array();
	if ($dir = opendir(DIR_FS_CATALOG.'templates/'.CURRENT_TEMPLATE.'/module/'.$module.'/')) {
		$files[] = array('id' => '', 'text' => ' -- default -- ');
		while(($file = readdir($dir)) !==false)
			if(is_file(DIR_FS_CATALOG.'templates/'.CURRENT_TEMPLATE.'/module/'.$module.'/'.$file) && ($file !="index.html"))
				$files[] = array('id' => $file,'text' => $file);
		closedir($dir);
	}	
	return $files;
}
?>