<?php
/**
 * jtlwawi_connector/dbeS/KategoriePict.php
 * Synchronisationsscript
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.01 / 03.07.06
*/

require_once("syncinclude.php");
logExtra(Dump($_POST));
$return=3;
if (auth()){
	$return=0;
	if (intval($_POST["action"]) == 3 && intval($_POST['KeyKategorie'])>0){
		$return = 0;
		//hol categories_id
		$categories_id = getFremdKategorie(intval($_POST['KeyKategorie']));
		eS_execute_query("update ".DB_PREFIX."categories set categories_image='' where categories_id=".$categories_id);
		
	}
}

mysql_close();
echo($return);
logge($return);
?>