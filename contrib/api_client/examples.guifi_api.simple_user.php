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

if( !empty( $_GET['a'] ) ) {
	if( $_GET['a'] == 'getZone' && !empty( $_GET['lat'] ) && !empty( $_GET['lon'] ) ) {
		$lat = $_GET['lat'];
		$lon = $_GET['lon'];
		
		$zones = $gapi->nearestZone( $lat, $lon );
		if( $zones ) {
			if( $zones->nearest ) {
				$nearest = $zones->nearest;
				$candidates = $zones->candidates;
				
				echo '<select id="ipt-zone" name="zone_id">' . "\n";
				
				foreach( $candidates as $candidate ) {
					echo '<option value="' . $candidate->zone_id . '"' . ( $candidate->zone_id == $nearest->zone_id ? ' selected="selected"' : '' ) . '>' . htmlspecialchars( $candidate->title ) . '</option>';
				}
				
				echo '</select>';
			} else {
				echo "No s'ha pogut trobar cap zona. És possible que no existeixi.<br />" . "\n";
				echo "Si saps quina zona és, introdueix manualment l'identificador de la zona:<br />\n";
				echo '<input type="text" name="zone" id="ipt-zone" />' . "\n";
			}
		} else {
			echo "No s'ha pogut trobar cap zona. Introdueix manualment l'identificador de la zona:";
			echo '<input type="text" name="zone" id="ipt-zone" />' . "\n";
		}
		die();
	}
}

if( !empty( $_GET['m'] ) ) {
	$messages[1] = "Hi ha hagut un error al crear el teu node. Revisa bé les dades introduïdes!";
	
	if( isset($messages[$_GET['m']])) {
		$message = $messages[$_GET['m']];
	}
}

if( !empty( $_POST ) ) {
	$username = $_POST['username'];
	$password = $_POST['password'];
	$title = $_POST['title'];
	$zone_id = $_POST['zone_id'];
	$lat = $_POST['lat'];
	$lon = $_POST['lon'];
	
	$gapi = new guifiAPI( $username, $password );
	
	$node = $gapi->addNode( $title, $zone_id, $lat, $lon );
	if( !empty( $node->node_id ) ) {
		header( "Location: examples.guifi_api.simple_user.step2.php?node_id=$node->node_id");
		die;
	} else {
		$message = "No s'ha pogut crear el teu node. Revisa bé les dades introduïdes!";
	}
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

<h1>Benvingut usuari!</h1>

<?php 
if( !empty($message)) {
	echo '<div class="alert">';
	echo '<h6>Error</h6>';
	echo $message;
	echo '</div>';
}
?>

<p>A través d'aquest formulari podràs crear el teu node molt fàcilment!</p>

<p>Senzillament, vés omplint els senzills camps que se't demanen a
continuació!</p>

<form action="examples.guifi_api.simple_user.php" method="post">

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

<fieldset><legend>El teu node</legend>
<p>Ara explica'ns un parell de coses sobre el teu node.</p>

<dl>
	<dt><label>A quin punt del mapa està situat?</label></dt>
	<dd>
	<div id="map" style="width: 70%; height: 400px; margin: 0 auto"></div>
	<input type="hidden" id="ipt-lat" name="lat" /> <input type="hidden"
		id="ipt-lon" name="lon" /></dd>
</dl>

<dl>
	<dt><label>A quina zona de guifi.net et trobes?</label></dt>
	<dd id="zone"><em>Primer clica sobre el mapa per saber on està situat
	el node.</em></dd>
</dl>

<dl>
	<dt><label for="ipt-title">Quin nom vols donar-li al teu node?</label><br />
	(<small>Per exemple, si vius al carrer Sant Miquel 34, un bon nom seria
	<em><span id="zone-nickname">NomPoblacio</span>StMiquel34</em></small>)</dt>
	<dd><input type="text" name="title" id="ipt-title" /></dd>
</dl>
</fieldset>

<h5>Aquí s'acaba el primer pas!</h5>

<p>Ara només has d'enviar les dades, i el teu node ja s'haurà creat.</p>

<p>A continuació, només et caldrà saber <strong>a quin node et connectes</strong>,
i ja podràs configurar la teva antena!</p>

<p class="submit">
	<input type="submit" name="create_node" value="Crea el node" />
</p>

</form>

</div>
</div>

<script
	src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAr3qx5ND13lr9SgNvfMaXFRSoMVdXjUpvF1gB4BZ5UHlg8i0YjxT29fejT_uEaVdsMzklRndOuKIb6g"
	type="text/javascript"></script>
<script type="text/javascript">
//<![CDATA[
var supernodes = null;
var map = null;
var baseIcon = null;
var zones = null;
var gmarker = null;
var clickEvent;

function load() {
	if (GBrowserIsCompatible()) {
		initMap();
	}
}

function initMap() {
	map = new GMap2(document.getElementById("map"));
	map.addControl(new GSmallMapControl());
	map.addControl(new GMapTypeControl());
	map.addMapType(G_PHYSICAL_MAP);
	map.enableScrollWheelZoom();

	map.setCenter(new GLatLng(41.583930, 1.619050), 7);

	clickEvent = GEvent.addListener(map, "click", function(marker, point) {
		if (gmarker) {
			map.removeOverlay(gmarker);
		}
	    gmarker = new GMarker(point);

	    if (map.getZoom() > 12) {
			map.addOverlay(gmarker);
			$("#ipt-lat").val(point.lat());
			$("#ipt-lon").val(point.lng());
			
			map.setCenter(point);
			loadZone();
		} else {
			map.setCenter(point, map.getZoom() + 3);	
		}
	});
}

function loadZone() {
	$('#zone').html('<img src="loader.gif" alt="Carregant..." />');
	var params = {'a':'getZone', 'lat':$("#ipt-lat").val(), 'lon': $("#ipt-lon").val() };
	$.get('examples.guifi_api.simple_user.php', params, function(data) {
			$('#zone').html(data).show();
		} );
}

$(document).ready( function() {
	load();
});
$(window).unload( function () {
	GUnload();
});

</script>
</body>
</html>