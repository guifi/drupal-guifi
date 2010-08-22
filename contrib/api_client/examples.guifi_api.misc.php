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
	case 'model':
		$model = array();
		$model['fid'] = 8;
		$model['supported'] = 'Yes';
		
		$models = $gapi->getModels( $model );
		if( $models ) {
			echo '<ul>';
			foreach( $models as $model ) {
				echo '<li>';
				echo '<ul>';
				echo "<li>Model ID: $model->mid</li>";
				echo "<li>Manufacturer ID: $model->fid</li>";
				echo "<li>Model: $model->model</li>";
				echo "<li>Type: $model->type</li>";
				echo "<li>Supported: $model->supported</li>";
				echo '</ul>';
				echo '</li>';
			}
			echo '</ul>';
		} else {
			echo "There was an error getting the models.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
	case 'manufacturer':
		$manufacturers = $gapi->getManufacturers();
		if( $manufacturers ) {
			echo '<ul>';
			foreach( $manufacturers as $manufacturer ) {
				echo '<li>';
				echo '<ul>';
				echo "<li>Manufacturer ID: $manufacturer->fid</li>";
				echo "<li>Name: $manufacturer->name</li>";
				echo "<li>URL: $manufacturer->url</li>";
				echo '</ul>';
				echo '</li>';
			}
			echo '</ul>';
		} else {
			echo "There was an error getting the manufacturers.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
	case 'firmware':
		$firmware = array();
		$firmware['model_id'] = 27; // RB600
		

		$firmwares = $gapi->getFirmwares( $firmware );
		if( $firmwares ) {
			echo '<ul>';
			foreach( $firmwares as $firmware ) {
				echo '<li>';
				echo '<ul>';
				echo "<li>Title: $firmware->title</li>";
				echo "<li>Description: $firmware->description</li>";
				echo '</ul>';
				echo '</li>';
			}
			echo '</ul>';
		} else {
			echo "There was an error getting the firmwares.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
	case 'protocol':
		$protocols = $gapi->getProtocols();
		if( $protocols ) {
			echo '<ul>';
			foreach( $protocols as $protocol ) {
				echo '<li>';
				echo '<ul>';
				echo "<li>Title: $protocol->title</li>";
				echo "<li>Description: $protocol->description</li>";
				echo '</ul>';
				echo '</li>';
			}
			echo '</ul>';
		} else {
			echo "There was an error getting the protocols.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
	case 'channel':
		$protocol = "802.11b";
		$channels = $gapi->getChannels($protocol);
		if( $channels ) {
			echo '<ul>';
			foreach( $channels as $channel ) {
				echo '<li>';
				echo '<ul>';
				echo "<li>Title: $channel->title</li>";
				echo "<li>Description: $channel->description</li>";
				echo '</ul>';
				echo '</li>';
			}
			echo '</ul>';
		} else {
			echo "There was an error getting the channels.<br />\n";
			echo $gapi->getErrorsStr();
		}
		break;
}

?>