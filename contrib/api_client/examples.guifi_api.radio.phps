<?php 
// The source code packaged with this file is Free Software, Copyright (C) 2005 by
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
		$mode = "ap";
		$device_id = 19534;
		$mac = 'AA:BB:CC:DD:EE:FA';
		
		$radio = array();
		$radio['antenna_angle'] = 120; // 60º
		$radio['antenna_gain'] = "21";
		$radio['antenna_azimuth'] = 30;
		$radio['protocol'] = '802.11b';
		$radio['channel'] = 13;
		$radio['clients_accepted'] = 'Yes';

		$added = $gapi->addRadio( $mode, $device_id, $mac, $radio );
		if( $added ) {
			echo "Radio created correctly!!<br />\n<br />\n";
			echo "The identificator of the new radio is: radiodev_counter = <strong>$added->radiodev_counter</strong>";
			if( $added->interfaces ) {
				echo "<br />\n<br />\n";
				echo "New interfaces added!:<br />";
				echo '<ul>';
				foreach( $added->interfaces as $interface ) {
					echo '<li>';
					echo "Type: $interface->interface_type<br />";
					if( $interface->ipv4 ) {
						echo "New IPv4 configuration:<br />";
						foreach( $interface->ipv4 as $ipv4 ) {
							echo "ipv4_type = $ipv4->ipv4_type<br />";
							echo "ipv4 = $ipv4->ipv4 <br />";
							echo "netmask = $ipv4->netmask<br />";
						}
					}
					echo '</li>';
				}
				echo '</ul>';
			}
		} else {
			echo "There was an error adding the radio.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
	case 'update':
		$device_id = 19534;
		$radiodev_counter = 0;
		
		$radio = array();
		$radio['antenna_angle'] = 90;
		$radio['antenna_gain'] = 14;
		
		$updated = $gapi->updateRadio( $device_id, $radiodev_counter, $radio );
		if( $updated ) {
			echo "Radio <strong>$radiodev_counter</strong> at device <strong>$device_id</strong> was updated correctly.<br />\n<br />\n";
		} else {
			echo "There was an error updating the device.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
	case 'delete':
		$device_id = 19534;
		$radiodev_counter = 0;
		
		$removed = $gapi->removeRadio( $device_id, $radiodev_counter );
		if( $removed ) {
			echo "Radio <strong>$radiodev_counter</strong> at device <strong>$device_id</strong> removed correctly.<br />\n<br />\n";
		} else {
			echo "There was an error deleting the radio.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
}

?>