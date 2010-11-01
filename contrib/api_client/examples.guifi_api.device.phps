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
		$node_id = 27192;
		$type = 'radio';
		$mac = 'AB:CD:EF:AB:CD:EE';
		
		$device = array();
		$device['nick'] = "DispositiuTroncal3";
		$device['notification'] = 'name@example.com';
		$device['comment'] = "Aquest dispositiu servirà per extendre la troncal";
		$device['status'] = 'Planned';
		$device['graph_server'] = 15902;
		$device['model_id'] = 27; // Routerboard 600
		$device['firmware'] = "RouterOSv3.x";
		
		$added = $gapi->addDevice($node_id, $type, $mac, $device );
		
		if( $added ) {
			echo "Device created correctly!!<br />\n<br />\nThe identificator of the new device is: device_id = <strong>$added->device_id</strong>";
		} else {
			echo "There was an error adding the device.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
	case 'update':
		$device_id = 19534;
		
		$device = array();
		$device['nick'] = "DispositiuTroncal3";
		$device['notification'] = 'name@example.com';
		$device['comment'] = "Aquest dispositiu servirà per extendre la troncal. I ara funciona!";
		$device['status'] = 'Working';
		$device['model_id'] = 27; // Supertrasto RB600 guifi.net
		$device['firmware'] = "RouterOSv3.x";
		
		$updated = $gapi->updateDevice( $device_id, $device );
		if( $updated ) {
			echo "Device <strong>$device_id</strong> was updated correctly.<br />\n<br />\n";
		} else {
			echo "There was an error updating the device.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
	case 'delete':
		$device_id = 19534;
		
		$removed = $gapi->removeDevice( $device_id );
		if( $removed ) {
			echo "Device <strong>$device_id</strong> removed correctly.<br />\n<br />\n";
		} else {
			echo "There was an error deleting the device.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
}

?>