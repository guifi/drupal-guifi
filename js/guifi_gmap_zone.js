var map = null;

var marker_NE;
var marker_SW;
var marker_move ;

if(Drupal.jsEnabled) {
    $(document).ready(function(){
        draw_map();
    }); 
}

function draw_map() {

    var divmap = document.getElementById("map");
    var baseURL = document.getElementById("guifi-wms").value

    opts = {
        center: new google.maps.LatLng(41.974175, 2.238118),
        zoom: 2,
        mapTypeControl: true,
        mapTypeControlOptions: {
            mapTypeIds: [ /*"osm",*/
                          google.maps.MapTypeId.ROADMAP,
                          google.maps.MapTypeId.TERRAIN,
                          google.maps.MapTypeId.SATELLITE,
                          google.maps.MapTypeId.HYBRID ]
        },
        scaleControl: false,
        streetViewControl: false,
        zoomControl: true,
        panControl: true,
        zoomControlOptions: {
            style: google.maps.ZoomControlStyle.LARGE
        },

        mapTypeId: google.maps.MapTypeId.HYBRID
    }

    // Add the map to the div
    map = new google.maps.Map(divmap, opts);




    
    // Add the OSM map type
    //map.mapTypes.set('osm', openStreet);
    //initCopyrights();
    
    // Add the right panel
    var panelcontrol = new PanelControl({
        forms:{
        fuente: {name: 'Mapas', tooltip: 'Proveedor de los mapas de la capa inferior (terreno, etc)',
                type: 'radio', list: {
                    google: { name: 'Google', tooltip: 'Google Maps', default: true },
                    osm: { name: 'OSM', tooltip: 'OpenStreetMap' },
                    mapquest: { name: 'MapQuest', tooltip: 'Map Quest' }}},
        capas: {name: 'Capas', tooltip: 'Capas de datos extra',
                type: 'checkbox', list: {
                    //supernodos: { name: 'Supernodos', tooltip: 'Nodos con más de 1 enlace inalámbrico', default: true},
                    nodos: { name: 'Nodos', tooltip: /*'Nodos clientes (1 enlace inalámbrico)'*/'Nodos y supernodos', default: true },
                    //superenlaces: { name: 'Superenlaces', tooltip: 'Enlaces entre supernodos (troncales)', default: true},
                    enlaces: { name: 'Enlaces', tooltip: /*'Enlaces cliente (de nodo a supernodo)'*/'Enlaces cliente y enlaces troncales', default: true}}}
        },
        extrahtml:'<p style="font-size: 10px;text-align:center;color:#888;">(en construcción)</p>'
    });
    map.controls[google.maps.ControlPosition.RIGHT_TOP].push(panelcontrol.div);

    // Guifi layers
    var guifinodes = new GuifiLayer(map, baseURL,"Nodes");
    var guifilinks = new GuifiLayer(map, baseURL,"Links");
    var guifinodeslinks = new GuifiLayer(map, baseURL,"Nodes,Links");
    //map.overlayMapTypes.insertAt(0, guifinodes.overlay);
    //map.overlayMapTypes.insertAt(0, guifilinks.overlay);
    map.overlayMapTypes.insertAt(0, guifinodeslinks.overlay);
    
    var toggleOverlays = function () {
        if (this.panel.capas.nodos && this.panel.capas.enlaces) {
            map.overlayMapTypes.setAt(0, guifinodeslinks.overlay);
        } else {
            if (this.panel.capas.nodos) {
                map.overlayMapTypes.setAt(0, guifinodes.overlay);
            } else if (this.panel.capas.enlaces) {
                map.overlayMapTypes.setAt(0, guifilinks.overlay);
            } else {
                map.overlayMapTypes.pop();
            }
        }
        if (map.overlayMapTypes.getLength()>1) {
            alert('debug: capas>1\n(esto no debería pasar)');
        }
    }
    google.maps.event.addDomListener(panelcontrol.inputs.nodos, 'click', toggleOverlays);
    google.maps.event.addDomListener(panelcontrol.inputs.enlaces, 'click', toggleOverlays);

    /*var guifiControl = new Control("guifi");
    guifiControl.div.index = 1;
    map.controls[google.maps.ControlPosition.TOP_RIGHT].push(guifiControl.div);

    // Setup the click event listeners
    google.maps.event.addDomListener(guifiControl.ui, 'click', function() {
        if (guifiControl.enabled) {
            map.overlayMapTypes.removeAt(0);
            guifiControl.disable();
        } else {
            // Add the guifi layer
            map.overlayMapTypes.insertAt(0, guifi.overlay);
            guifiControl.enable();
        }
    });*/

    var marcador = new google.maps.Marker();

    var newNE = new google.maps.LatLng(document.getElementById("maxy").value, 
			                           document.getElementById("maxx").value);
    var newSW = new google.maps.LatLng(document.getElementById("miny").value, 
			                           document.getElementById("minx").value); 

    var newBounds = new google.maps.LatLngBounds(newSW, newNE) ;

    marker_NE = new google.maps.Marker({ position: newBounds.getNorthEast() }) ;
    marker_SW = new google.maps.Marker({ position: newBounds.getSouthWest() }) ;
    marker_move = new google.maps.Marker( 
                      new google.maps.LatLng(((marker_SW.getPosition().lat() + marker_NE.getPosition().lat()) / 2),
		                                      (marker_NE.getPosition().lng() + marker_SW.getPosition().lng()) / 2)) ;
    marker_move.savePoint = marker_move.getPosition() ;			// Save for later

    var infoWindow = new google.maps.InfoWindow({});

    google.maps.event.addListener(map, "click", function(event) {

        var point = event.latLng;

        if (map.getZoom() <= 15 ) {
            map.setZoom(map.getZoom()+3);
        } else {

            marcador.setPosition(point);
            var basePath = Drupal.settings.basePath;

            infoWindow.setContent(
                    'Lat : ' + point.lat() + '<br>Lon: ' + point.lng() +
                    '<br><a href="' + basePath + 'node/add/guifi-node?lon='
                    + point.lng() + '&lat=' + point.lat() +
                    '&zone='+document.getElementById("zone_id").value+
                    '" TARGET=fijo APPEND=blank>Add a new node here</a>');
            infoWindow.open(map, marcador);    
        }
    });
    
    var bounds = new google.maps.LatLngBounds();

    // Check for moved center...
    if ( marker_move.getPosition() != marker_move.savePoint ) {

        var x = marker_move.getPosition().lat() - marker_move.savePoint.lat() ;
        var y = marker_move.getPosition().lng() - marker_move.savePoint.lng() ;
        marker_SW.setPosition( new google.maps.LatLng( marker_SW.getPosition().lat() + x, marker_SW.getPosition().lng() + y) ) ;
        marker_NE.setPosition( new google.maps.LatLng( marker_NE.getPosition().lat() + x, marker_NE.getPosition().lng() + y) ) ;

    } else	{
        // Center not moved so move center
        var x = (marker_SW.getPosition().lat() + marker_NE.getPosition().lat()) / 2 ;
        var y = (marker_NE.getPosition().lng() + marker_SW.getPosition().lng()) / 2 ;
        marker_move.setPosition( new google.maps.LatLng(x,y) ) ;
        map.setCenter(new google.maps.LatLng(x,y));
    }

    marker_move.savePoint = marker_move.getPosition() ;			// Save for later

    var points = [
        marker_NE.getPosition(),
        new google.maps.LatLng(marker_SW.getPosition().lat(), marker_NE.getPosition().lng()),
        marker_SW.getPosition(),
        new google.maps.LatLng(marker_NE.getPosition().lat(), marker_SW.getPosition().lng()),
        marker_NE.getPosition()
    ];

    var border = new google.maps.Polyline( { path: points, strokeColor: "#66000", strokeOpacity: .4, strokeWeight: 5, map: map });
 
    bounds.extend(marker_SW.getPosition());
    bounds.extend(marker_NE.getPosition());
    map.fitBounds(bounds);
 
}


