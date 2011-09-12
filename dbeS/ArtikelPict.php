<?php
/**
 * jtlwawi_connector/dbeS/ArtikelPict.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.03 / 20.08.06
*/

require_once("syncinclude.php");
$picpath = "../produktbilder/";
$return=3;
if (auth())
{
	$return=0;
	if (intval($_POST["action"]) == 3 && intval($_POST['KeyArtikel'])>0)
	{
		$return =0;
		$products_id = getFremdArtikel(intval($_POST['KeyArtikel']));
		if ($products_id>0)
		{
			if (intval($_POST["Nr"]) == 1)
			{
				eS_execute_query("update ".DB_PREFIX."products set products_image='' where products_id=".$products_id);
			}
			if (intval($_POST["Nr"]) > 1)
			{
				eS_execute_query("delete from ".DB_PREFIX."products_images where products_id=$products_id and image_nr=".(intval($_POST['Nr'])-1));
			}
		}
	}
	
}

mysql_close();
echo($return);
logge($return);
?>