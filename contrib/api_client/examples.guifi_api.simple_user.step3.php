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
	die();
}

if( !empty( $_GET['device_id'] ) ) {
	$device_id = $_GET['device_id'];
}

if( empty( $device_id ) ) {
	header( "Location: examples.guifi_api.simple_user.step2.php?node_id=$node_id&m=1" );
	die();
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
<h1>Felicitats!</h1>

<p>Has aconseguit afegir completament el teu node a guifi.net. Pots
veure el teu node complet a la pàgina següent:</p>
<p><a href="http://test.guifi.net/node/<?php
echo $node_id?>">http://test.guifi.net/node/<?php
echo $node_id?></a></p>

<p>Ara només et queda configurar la teva antena. Per això, et pots baixa la configuració UnSolClic, i enviar-la a la teva antena.</p>
<p>Et pots baixar aquesta configuració des de l'adreça següent:</p>
<p><a href="http://test.guifi.net/guifi/device/<?php echo $device_id ?>/view/unsolclic">http://test.guifi.net/guifi/device/<?php echo $device_id ?>/view/unsolclic</a></p>

</div>
</div>
</body>
</html>