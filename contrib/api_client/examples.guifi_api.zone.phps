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
		// All possible parameters to add a zone
		$title = "Sant Jaume de Frontanyà"; // Nom de la zona
		$master = 4176; // Zona pare
		$min_lat = '42.183122730364815'; // Latitud de l'escaire SO de la zona
		$min_lon = '2.0180511474609375'; // Longitud de l'escaire SO de la zona
		$max_lat = '42.19139028706819'; // Latitud de l'escaire NE de la zona
		$max_lon = '2.0276641845703125'; // Longitud de l'escaire NE de la zona
		

		$zone = array();
		$zone['nick'] = "StJaumeFrontanyà";
		$zone['zone_mode'] = 'infrastructure';
		$zone['body'] = "Sant Jaume de Frontanyà és un municipi de la comarca del Berguedà.<br /><br />El seu terme és muntanyenc i escassament poblat, amb boscos de pins i pastures per a la ramaderia que juntament amb l'agricultura de cereals i el turisme és la base de la seva economia. Regat per afluents de la reira de Merlès.";
		$zone['graph_server'] = 14428;
		$zone['proxy_server'] = 15903;
		$zone['dns_servers'] = '10.145.2.34,10.145.2.66';
		$zone['ntp_servers'] = '10.145.8.226';
		$zone['ospf_zone'] = 5;
		$zone['homepage'] = 'http://www.santjaumedefrontanya.net';
		$zone['notification'] = "name@example.com";
		
		$added = $gapi->addZone( $title, $master, $min_lat, $min_lon, $max_lat, $max_lon, $zone );
		if( $added ) {
			echo "Zone created correctly!!<br />\n<br />\nThe identificator of the new zone is: zone_id = <strong>$added->zone_id</strong>";
		} else {
			echo "There was an error adding the zone.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
	case 'update':
		$zone_id = 27181;
		
		$zone = array();
		$zone['title'] = "Sant Jaume de Frontanyà - Autèntic";
		$zone['nick'] = "StJaumeFrontanyàAutentic";
		$zone['zone_mode'] = 'ad-hoc';
		$zone['body'] = "Sant Jaume de Frontanyà és un municipi de la comarca del Berguedà.<br /><br />El seu terme és muntanyenc i escassament poblat, amb boscos de pins i pastures per a la ramaderia que juntament amb l'agricultura de cereals i el turisme és la base de la seva economia. Regat per afluents de la reira de Merlès. Ara a més a més és ad-hoc";
		$zone['graph_server'] = ""; // Get from parents
		$zone['proxy_server'] = ''; // Get from parents
		$zone['dns_servers'] = ''; // Get from parents
		$zone['ntp_servers'] = ''; // Get from parents
		$zone['ospf_zone'] = 0;
		$zone['homepage'] = 'http://www.santjaumedefrontanya.net/novaweb';
		
		$updated = $gapi->updateZone( $zone_id, $zone );
		if( $updated ) {
			echo "Zone <strong>$zone_id</strong> was updated correctly.<br />\n<br />\n";
		} else {
			echo "There was an error updating the zone.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
	case 'delete':
		$zone_id = 27181;
		$removed = $gapi->removeZone( $zone_id );
		if( $removed ) {
			echo "Zone <strong>$zone_id</strong> removed correctly.<br />\n<br />\n";
		} else {
			echo "There was an error deleting the zone.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
	case 'nearest':
		$lat = "41.5787648";
		$lon = "1.6171926";
		$nearest = $gapi->nearestZone( $lat, $lon );
		if( $nearest ) {
			if( $nearest->nearest ) {
				$zone = $nearest->nearest;
				echo "Found nearest zone:<br />\n";
				echo "&nbsp;&nbsp;Title: <strong>$zone->title</strong><br />\n";
				echo "&nbsp;&nbsp;Zone ID: <strong>$zone->zone_id</strong><br />\n";
			}
			if( $nearest->candidates ) {
				$candidates = $nearest->candidates;
				echo "<br />\n";
				echo "Found candidate zones:\n";
				echo '<ul>' . "\n";
				foreach( $candidates as $zone ) {
					echo "<li>\n";
					echo "&nbsp;&nbsp;Title: <strong>$zone->title</strong><br />\n";
					echo "&nbsp;&nbsp;Zone ID: <strong>$zone->zone_id</strong>\n";
					echo "</li>\n";
				}
				echo "</ul>\n";
			}
		} else {
			echo "There was an error getting the nearest zone.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
}

?>