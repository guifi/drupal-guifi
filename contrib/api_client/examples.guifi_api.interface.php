<?php
// The source code packaged with this file is Free Software, Copyright (C) 2010 by
// Eduard Duran <eduard.duran at iglu.cat>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
// 		http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "LICENSE.txt".

require ( 'guifi_api.php' );

/**
 * Configuration of authentication against guifi.net
 */
$username = "";
$password = "";

/**
 * Code from here!
 */
$gapi = new guifiAPI( $username, $password );

$action = $_GET['action'];

switch( $action ) {
	case 'add':
		$device_id = 19534;
		$radiodev_counter = 1;
		
		$added = $gapi->addInterface( $device_id, $radiodev_counter );
		if( $added ) {
			echo "Interface created correctly!!<br />\n<br />\n";
			echo "The identificator of the new interface is: interface_id = <strong>$added->interface_id</strong>";
			if( $added->ipv4 ) {
				echo "<br /><br />\n\n";
				echo "New IPv4 configuration:<br />";
				echo '<ul>';
				foreach( $added->ipv4 as $ipv4 ) {
					echo '<li>';
					echo "ipv4_type = $ipv4->ipv4_type<br />";
					echo "ipv4 = $ipv4->ipv4 <br />";
					echo "netmask = $ipv4->netmask<br />";
					echo '</li>';
				}
				echo '</ul>';
			}
		} else {
			echo "There was an error adding the interface.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
	case 'delete':
		$interface_id = 26531;
		
		$removed = $gapi->removeInterface( $interface_id );
		if( $removed ) {
			echo "Interface <strong>$interface_id</strong> removed correctly.<br />\n<br />\n";
		} else {
			echo "There was an error deleting the interface.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
}

?>