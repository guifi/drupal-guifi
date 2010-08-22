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
		$from_device_id = 19534;
		$from_radiodev_counter = 2;
		$to_device_id = 8595;
		$to_radiodev_counter = 0;
		
		$link = array();
		$link['status'] = 'Building';
		$link['ipv4'] = '10.145.5.15';
		
		$added = $gapi->addLink( $from_device_id, $from_radiodev_counter, $to_device_id, $to_radiodev_counter, $link );
		if( $added ) {
			echo "Link created correctly!!<br />\n<br />\n";
			echo "The identificator of the new link is: link_id = <strong>$added->link_id</strong>";
			if( $added->ipv4 ) {
				$ipv4 = $added->ipv4;
				echo "<br />\n<br />\n";
				echo "New IPv4 settings!:<br />";
				echo '<ul>';
				echo '<li>';
				echo "ipv4_type = $ipv4->ipv4_type<br />";
				echo "ipv4 = $ipv4->ipv4 <br />";
				echo "netmask = $ipv4->netmask<br />";
				echo '</li>';
				echo '</li>';
			}
			echo '</ul>';
		} else {
			echo "There was an error adding the link.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
	case 'update':
		$link_id = 21951;
		
		$link = array();
		$link['status'] = 'Working';
		$link['ipv4'] = '10.145.5.14';
		
		$updated = $gapi->updateLink( $link_id, $link );
		if( $updated ) {
			echo "Link <strong>$link_id</strong> was updated correctly.<br />\n<br />\n";
		} else {
			echo "There was an error updating the link.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
	case 'delete':
		$link_id = 21951;
		
		$removed = $gapi->removeLink( $link_id );
		if( $removed ) {
			echo "Link <strong>$link_id</strong> removed correctly.<br />\n<br />\n";
		} else {
			echo "There was an error deleting the link.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
}

?>