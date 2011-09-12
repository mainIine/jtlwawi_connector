<?php
/**
 * jtlwawi_connector/adminTemplates.php
 * AdminLogin f�r JTL-Wawi Connector
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.01 / 09.11.06
*/

function zeigeKopf() {
	echo('
<html>
	<head>
		<meta http-equiv="content-type" content="text/html;charset=utf-8">
		<meta http-equiv="language" content="deutsch, de">
		<meta name="author" content="JTL-Software, www.jtl-software.de">
		<title>JTL-Wawi Connector für SEO:mercari Installation</title>
		<link rel="stylesheet" type="text/css" href="../admin/jtlwawiConnectorAdmin.css">
	</head>
	<body>
	<div id="header">
		<div id="logo"><img src="../images/connector_admin.jpg" alt="SEO:mercari JTL-WawiConnector Installation" /></div>
		<div id="links">
			<a href="http://www.seo-mercari.de/" target="_blank"  class="headerLink">Shop-System</a> |
			<a href="http://faq.seo-mercari.de/" target="_blank"  class="headerLink">Shop-Handbuch</a> |
			<a href="http://forum.seo-mercari.de/" target="_blank" class="headerLink">Forum</a> |
			<a href="http://www.shop-erweiterungen.de" target="_blank"  class="headerLink">Erweiterungen</a>
		</div>
	</div>
	<div id="wrapper">
		<div id="inner_wrapper">
			<table class="outerTable">
				<tr>
					<td valign="top">');
}

function zeigeFuss() {
	echo('
					</td>
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
</html>');
}

function zeigeLogin() {
	echo('
					
							<h2>Admin-Login</h2>
							<table cellspacing="0" cellpadding="0" width="96%" class="config">
								<tr>
									<td class="content" align="center"><br>
									Bitte loggen Sie sich als Admin ein. Es gelten die Zugangsdaten für den bestehenden Administrationsbereich des Shops.<br /><br />
									<form name="login" method="post" action="index.php">
									<input type="hidden" name="adminlogin" value="1" />
									<table cellspacing="0" cellpadding="10" width="300" style="border-width:1px;border-color:#222222;border-style:solid;">
										<tr>
											<td><b>e-Mailadresse</b></td><td><input type="text" name="benutzer" size="40" class="login"></td>
										</tr>
										<tr>

											<td><b>Passwort</b></td><td><input type="password" name="passwort" size="40" class="login"></td>
										</tr>
									</table><br><br>
										<input type="submit" class="button" value="JTL-Wawi Connector Login" />
										<br><br><br>
									</form>
								</td></tr>
							</table>
	');
}

function zeigeLoginBereich(){
	echo('
						
							<h2>Willkommen im Konfigurationsbereich vom JTL-Wawi Connector</h2>
							<table cellspacing="0" cellpadding="0" width="96%">
								<tr><td class="content" align="center"><br>
										Sie haben sich erfolgreich eingeloggt.<br>
										Bitte benutzen Sie das Menü links zur Navigation.<br><br>
								</td></tr>
							</table><br>
	');
}

function zeigeLinks($loggedIn){
	if ($loggedIn==1){
		echo('
							
								<table cellspacing="0" cellpadding="0" width="100%" valign="top">
									<tr><td class="unterlink"><a class="button" href="konfiguration.php">Konfiguration</a><br /><br /></td></tr>
									<tr><td class="unterlink"><a class="button" href="bildexport.php">Bildexport</a><br></td></tr>
		');

		switch(date(w))
		{
			case 0:$tag="Sonntag";break;
			case 1:$tag="Montag";break;
			case 2:$tag="Dienstag";break;
			case 3:$tag="Mittwoch";break;
			case 4:$tag="Donnerstag";break;
			case 5:$tag="Freitag";break;
			case 6:$tag="Samstag";break;

		}

		echo('
									<tr><td><br><br><br></td></tr>
									<tr><td class="user"><span class="small">&nbsp;'.$tag.', '.date("d.m.y H:i").'</span></td></tr>
								</table>
							</td><td>
		');
		
	} else {
		echo('
							
								<table cellspacing="0" cellpadding="0" width="100%">
									<tr><td class="oberlink_gewaehlt"><a class="class" href="">Login</a><br></td></tr>
								</table>
							</td><td>
		');
	}
}
?>