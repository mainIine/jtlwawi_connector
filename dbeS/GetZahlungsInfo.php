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
 * @version v1.0 / 15.06.06
*/
require_once("syncinclude.php");
if (file_exists(DOCROOT_PATH."inc/changedataout.inc.php"))
	require_once(DOCROOT_PATH."inc/changedataout.inc.php");

$return=3;
if (auth())
{
	$return=5;
	if (intval($_POST['KeyBestellung']))
	{
		$return = 0;
		//hole order
		$cur_query = eS_execute_query("select orders_id, payment_method from ".DB_PREFIX."orders where orders_id=".intval($_POST['KeyBestellung']));
		$ZahlungsInfo = mysql_fetch_object($cur_query);
		$ZahlungsInfo->send=0;
		//ist es Banktransfer?
		if ($ZahlungsInfo->payment_method=="banktransfer")
		{
			//hole bankdaten
			$cur_query = eS_execute_query("select * from ".DB_PREFIX."banktransfer where orders_id=".intval($_POST['KeyBestellung']));
			$Bank = mysql_fetch_object($cur_query);
			if ($Bank->orders_id>0)
			{
				$ZahlungsInfo->send=1;
				$ZahlungsInfo->cBankName=$Bank->banktransfer_bankname;
				$ZahlungsInfo->cBLZ=$Bank->banktransfer_blz;
				$ZahlungsInfo->cKontoNr=$Bank->banktransfer_number;
				$ZahlungsInfo->cInhaber=$Bank->banktransfer_owner;
			}
		}
		if ($ZahlungsInfo->payment_method=="cc")
		{		
			//Kreditkarte
			//hole chainkey
			$ZahlungsInfo->send=1;
			$cur_query = eS_execute_query("select configuration_value from ".DB_PREFIX."configuration where configuration_key=\"CC_KEYCHAIN\"");
			$chain = mysql_fetch_object($cur_query);
			$ZahlungsInfo->cKartenNr = changedataout($ZahlungsInfo->cc_number,$chain->configuration_value);
			$ZahlungsInfo->cGueltigkeit = $ZahlungsInfo->cc_expires;
			$ZahlungsInfo->cCVV = $ZahlungsInfo->cc_cvv;
			$ZahlungsInfo->cKartenTyp = $ZahlungsInfo->cc_type;
			$ZahlungsInfo->cInhaber = $ZahlungsInfo->cc_owner;
		}
		
		
		if ($ZahlungsInfo->send==1)
		{
			echo(CSVkonform(iconv("UTF-8","ISO-8859-1",$ZahlungsInfo->orders_id)).';');
			echo(CSVkonform(iconv("UTF-8","ISO-8859-1",$ZahlungsInfo->orders_id)).';');
			echo(CSVkonform(iconv("UTF-8","ISO-8859-1",$ZahlungsInfo->cBankName)).';');
			echo(CSVkonform(iconv("UTF-8","ISO-8859-1",$ZahlungsInfo->cBLZ)).';');
			echo(CSVkonform(iconv("UTF-8","ISO-8859-1",$ZahlungsInfo->cKontoNr)).';');
			echo(CSVkonform(iconv("UTF-8","ISO-8859-1",$ZahlungsInfo->cKartenNr)).';');
			echo(CSVkonform(iconv("UTF-8","ISO-8859-1",$ZahlungsInfo->cGueltigkeit)).';');
			echo(CSVkonform(iconv("UTF-8","ISO-8859-1",$ZahlungsInfo->cCVV)).';');
			echo(CSVkonform(iconv("UTF-8","ISO-8859-1",$ZahlungsInfo->cKartenTyp)).';');
			echo(CSVkonform(iconv("UTF-8","ISO-8859-1",$ZahlungsInfo->cInhaber)).';');
			echo("\n");
		}
	}
}

mysql_close();
echo($return);
logge($return);
?>