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

$gapi = new guifiAPI( $username, $password );

if( !empty( $_GET['node_id'] ) ) {
	$node_id = $_GET['node_id'];
}

if( empty( $node_id ) ) {
	header( "Location: examples.guifi_api.simple_user.php?m=1" );
}

if( $_POST ) {
	$username = $_POST['username'];
	$password = $_POST['password'];
	$to_radio = explode( ':', $_POST['radio_to'] );
	$to_device_id = $to_radio[0];
	$to_radiodev_counter = $to_radio[1];
	
	$gapi = new guifiAPI( $username, $password );
	
	/**
	 * CREATE THE DEVICE
	 */
	$type = 'radio';
	$mac = $_POST['mac'];

	$device = array();
	$device['model_id'] = 25; // NanoStation2
	$device['firmware'] = "AirOsv30";
	
	$added = $gapi->addDevice($node_id, $type, $mac, $device);
	
	if( $added ) {
		$device_id = $added->device_id;

		$added = $gapi->addRadio( 'client', $device_id );
		if( $added ) {
			$from_radiodev_counter = $added->radiodev_counter;
			
			$added = $gapi->addLink( $device_id, $from_radiodev_counter, $to_device_id, $to_radiodev_counter );
			if( $added ) {
				header( "Location: examples.guifi_api.simple_user.step3.php?node_id=$node_id&device_id=$device_id" );
				die;
			} else {
				$message = "No s'ha pogut afegir l'enllaç, error de l'API: ";
				$message .= $gapi->getErrorsStr();
			}
		} else {
			$message = "No s'ha pogut afegir la ràdio, error de l'API: ";
			$message .= $gapi->getErrorsStr();
		}
		
	} else {
		$message = "No s'ha pogut afegir el dispositiu, error de l'API: ";
		$message .= $gapi->getErrorsStr();
	}
}

$nearest = $gapi->nearestRadio( $node_id );
if( $nearest ) {
	if( $nearest->radios ) {
		$radios = $nearest->radios;
		
		$radio_select = '<select name="radio_to">'. "\n";
		
		foreach( $radios as $radio ) {
			$radio_select .= '<option value="'.$radio->device_id . ':' . $radio->radiodev_counter . '">';
			$radio_select .= "$radio->ssid ($radio->distance km)";
			$radio_select .= "</option>\n";
		}
		
		$radio_select .= "</select>\n";
		
	} else {
		$radio_select = "No s'han pogut trobar ràdios aprop, segurament no tinguis cap node guifi.net aprop on et puguis connectar :(";
	}
} else {
	$radio_select = "No s'han pogut trobar ràdios, error de l'API: ";
	$radio_select .= $gapi->getErrorsStr();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Client PHP per l'API de guifi.net</title>
<script src="http://jqueryjs.googlecode.com/files/jquery-1.3.2.min.js"></script>
<link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body>

<div id="container">
<div id="content">
<h1>Molt bé!</h1>

<?php 
if( !empty($message)) {
	echo '<div class="alert">';
	echo '<h6>Error</h6>';
	echo $message;
	echo '</div>';
}
?>

<p>Has aconseguit crear el teu node. El podràs veure a la pàgina
següent:</p>
<p><a href="http://guifi.net/node/<?php
echo $node_id?>">http://guifi.net/node/<?php
echo $node_id?></a></p>

<p>Ara només et queda un petit pas per acabar de configurar la teva
antena: escollir allà on et connectes!</p>

<form action="examples.guifi_api.simple_user.step2.php?node_id=<?php echo $node_id ?>" method="post">

<fieldset><legend>Autenticació</legend>
<p>Primer de tot, hem de saber quin nom d'usuari i contrasenya tens
registrat a guifi.net</p>

<dl>
	<dt><label for="ipt-username">Nom d'usuari:</label></dt>
	<dd><input type="text" name="username" id="ipt-username" /></dd>
</dl>

<dl>
	<dt><label for="ipt-password">Contrasenya:</label></dt>
	<dd><input type="password" name="password" id="ipt-password" /></dd>
</dl>
</fieldset>

<fieldset><legend>La teva antena</legend>
<p>Necessitem saber només una cosa de la teva antena: la seva adreça MAC.</p>

<dl>
	<dt><label for="ipt-mac">Adreça MAC:</label></dt>
	<dd><input type="text" name="mac" id="ipt-mac" /></dd>
</dl>

</fieldset>

<fieldset><legend>El node on et connectes</legend>
<p>Hem escollit per tu un seguit de nodes on és possible que et puguis
connectar, fins a 50.</p>

<p>Si no trobes el teu en aquesta llista, és difícil que et puguis
connectar a cap altre node.</p>

<p>Selecciona el nom del node on et connectes:</p>

<dl>
	<dt><label for="ipt-node">Node on et connectes:</label></dt>
	<dd><?php
	echo $radio_select?></dd>
</dl>

</fieldset>

<h5>Aquí s'acaba el segon pas!</h5>

<p>Ara només has d'enviar les dades, i es crearà un enllaç cap el node
que has seleccionat.</p>

<p>A continuació, només et caldrà <strong>configurar la teva antena</strong>,
i ja estaràs connectat a guifi.net!</p>

<p class="submit"><input type="submit" name="create_link"
	value="Crea l'enllaç" /></p>

</form>

</div>
</div>
</body>
</html>