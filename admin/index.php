<?php
/**
 * jtlwawi_connector/index.php
 * AdminLogin f�r JTL-Wawi Connector
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.0 / 14.06.06
*/

require_once("admininclude.php");
require_once("adminTemplates.php");

//adminlogin
if (intval(isset($_POST["adminlogin"]) && $_POST["adminlogin"])==1) {
	$user = $db->db_query("SELECT customers_password FROM ".DB_PREFIX."customers WHERE customers_email_address = '".$_POST["benutzer"]."'");
	if($user->_numOfRows) {
		
		require(DIR_FS_INC.'inc.validate_password.php');
		if(validate_password($_POST['passwort'], $user->fields['customers_password']))
			$_SESSION["loggedIn"] = 1;
	}	
}

zeigeKopf();
if(isset($_SESSION["loggedIn"]))
	zeigeLinks($_SESSION["loggedIn"]);
if(!isset($_SESSION["loggedIn"]) && $_SESSION["loggedIn"]!=1)
	zeigeLogin();
else
	zeigeLoginBereich();
zeigeFuss();
?>