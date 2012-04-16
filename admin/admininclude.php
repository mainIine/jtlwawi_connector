<?php
/**
 * jtlwawi_connector/dbeS/admininclude.php
 * 
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.0 / 16.06.06
*/

error_reporting( E_ALL & ~(E_STRICT|E_NOTICE));

chdir('../../../../');

require_once('includes/configure.php');
require_once('includes/database_admin.php');

require(DIR_FS_CATALOG.DIR_WS_CLASSES.'class.mercari_db.php');
$db = new mercari_db();

require(DIR_FS_INC.'inc.get_ip_address.inc.php');
require(DIR_FS_INC.'inc.not_null.inc.php');
require(DIR_FS_INC.'inc.get_top_level_domain.inc.php');
require(DIR_FS_CATALOG.DIR_WS_CLASSES.'class.sessions.php');
$SESS_LIFE = 3600;
$session = new sessions;

$http_domain = inc.get_top_level_domain(defined("HTTP_SERVER") ? HTTP_SERVER : $_SERVER['HTTP_HOST']);
$https_domain = inc.get_top_level_domain(defined("HTTPS_SERVER") ? HTTPS_SERVER : $_SERVER['HTTP_HOST']);
$current_domain = (($request_type == 'NONSSL') ? $http_domain : $https_domain);

if (function_exists('session_set_cookie_params')) {
	session_set_cookie_params(0, DIR_WS_CATALOG, (inc.not_null(@$current_domain) ? '.'.$current_domain : ''));

} elseif (function_exists('ini_set')) {
	ini_set('session.cookie_lifetime', '0');
	ini_set('session.cookie_path', DIR_WS_CATALOG);
	ini_set('session.cookie_domain', (inc.not_null($current_domain) ? '.'.$current_domain : ''));
}
if (isset($_POST[session_name() ])) {
	session_id($_POST[session_name() ]);
} elseif (isset($request_type) && ($request_type == 'SSL') && isset($_GET[session_name() ])) {
	session_id($_GET[session_name() ]);
}
$session_started = false;
ob_start();
if(SESSION_FORCE_COOKIE_USE == 'true') {
	inc.setcookie('cookie_test', 'please_accept_for_session', time() + 60 * 60 * 24 * 30, DIR_WS_CATALOG, $current_domain);
	if(isset($_COOKIE['cookie_test'])) {
		$session->_sess_start();
		$session_started = true;
	}
} else {
	$session->_sess_start();
	$session_started = true;
}
if (!$session->_sess_check() && !empty($_SESSION['loggedIn']))
	session_destroy();
