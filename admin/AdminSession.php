<?php
/**
 * jtlwawi_connector/adminSession.php
 * AdminSession Verwaltung
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.0 / 16.06.06
*/

class AdminSession {
	// session-lifetime
	var $lifeTime;
	var $db;
	
	function __construct() {
		global $db;
		
		$this->db = $db;
	}
	
	function open($savePath, $sessName) {
	   // get session-lifetime
	   $this->lifeTime = get_cfg_var("session.gc_maxlifetime");
	   // return success
	   if(!$GLOBALS["DB"]->DB_Connection)
	      return false;
	   return true;
	}
	function close() {
	   // mach nichts
	   return true;
	}
	function read($sessID) {
	   // fetch session-data
	   $res = $this->db->db_query("SELECT 
	   									cSessionData 
	   								FROM 
	   									".DB_PREFIX."eazysales_adminsession
	                       			WHERE 
	                       				cSessionId = '".$sessID."'
	                       			AND 
	                       				nSessionExpires > ".time());
	   
	   if($res->_numOfRows)
	       return $row->fields['cSessionData'];
	   return "";
	}
	function write($sessID,$sessData) {
	   // new session-expire-time
	   $newExp = time() + $this->lifeTime;
	   // is a session with this id in the database?
	   $res = $this->db->db_query("SELECT * FROM ".DB_PREFIX."eazysales_adminsession
	                       WHERE cSessionId = '$sessID'");
	   // if yes,
	   if($res->_numOfRows) {
	       // ...update session-data
	      $update = $this->db->db_query("UPDATE 
	       							".DB_PREFIX."eazysales_adminsession
			                     SET 
			                     	nSessionExpires = '".$newExp."',
			                     	cSessionData = '".$sessData."'
			                     WHERE 
			                     	cSessionId = '".$sessID."'");
	       
	       if($update->Affected_Rows())
	           return true;
	   }
	   // if no session-data was found,
	   else {
	       // create a new row
	       $update = $this->db->db_query("INSERT INTO 
	       									".DB_PREFIX."eazysales_adminsession 
		       								(cSessionId,
								             nSessionExpires,
								             cSessionData)
							             VALUES('".$sessID."',
							                     '".$newExp."',
							                     '".$sessData."')");
	       // if row was created, return true
		   if($update->Affected_Rows())
	           return true;
	   }
	   // an unknown error occured
	   return false;
	}
	function destroy($sessID) {
	   // delete session-data
	   $update = $this->db->db_query("DELETE FROM ".DB_PREFIX."eazysales_adminsession WHERE cSessionId = '$sessID'");
	   // if session was deleted, return true,
	   if($update->Affected_Rows())
	       return true;
	   // ...else return false
	   return false;
	}
	function gc($sessMaxLifeTime) {
	   // delete old sessions
	   $update = $this->db->db_query("DELETE FROM ".DB_PREFIX."eazysales_adminsession WHERE nSessionExpires < ".time());
	   // return affected rows
	   return $update->Affected_Rows();
	}

	function AdminSession() {
		session_name("eSConnectorAdm");
		session_start();
	}
}