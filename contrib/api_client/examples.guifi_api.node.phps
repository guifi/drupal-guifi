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
		$title = "Plaça Major, 34";
		$zone_id = 27182;
		$lat = '42.187065853813635';
		$lon = '2.0233726501464844';
		$node = array();
		$node['nick'] = 'PlacaMajor34'; // Abbreviated name of the node
		$node['body'] = "Aquest és el meu node, situat a la Plaça Major, 34, al nucli antic del poble."; // Body of the node
		$node['zone_description'] = "Situat al nucli antic del poble, al costat de la carnisseria"; // Description of the zone
		$node['notification'] = "email@example.com";
		$node['lat'] = '41.54301946112854'; // Latitude of the node
		$node['lon'] = '1.5803146362304688'; // Longitude of the node
		$node['elevation'] = 30; // 30 metres high
		$node['stable'] = 'Yes';
		$node['graph_server'] = 15902;
		$node['status'] = 'Planned';

		$added = $gapi->addNode( $title, $zone_id, $lat, $lon, $node );
		if( $added ) {
			echo "Node created correctly!!<br />\n<br />\nThe identificator of the new node is: node_id = <strong>$added->node_id</strong>";
		} else {
			echo "There was an error adding the node.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
	case 'update':
		$node_id = 27185;
		
		$node = array();
		$zone['title'] = "Plaça Major, 34, 2n pis";
		$zone['nick'] = "PlacaMajor34";
		$node['body'] = "Aquest és el meu node, situat a la Plaça Major, 34, al nucli antic del poble. Ara està actualitzat i plenament operatiu!"; // Body of the node
		$node['elevation'] = 40; // 30 metres high
		$node['status'] = 'Working';
		
		$updated = $gapi->updateNode( $node_id, $node );
		if( $updated ) {
			echo "Node <strong>$node_id</strong> was updated correctly.<br />\n<br />\n";
		} else {
			echo "There was an error updating the node.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
	case 'delete':
		$node_id = 27185;
		$removed = $gapi->removeNode( $node_id );
		if( $removed ) {
			echo "Node <strong>$node_id</strong> removed correctly.<br />\n<br />\n";
		} else {
			echo "There was an error deleting the node.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
}

?>