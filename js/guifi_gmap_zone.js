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
            mapTypeIds: [ "osm", "mapquestosm", "mapquestopenaerial",
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
    map.mapTypes.set('osm', openStreet);
    map.mapTypes.set('mapquestosm', mapquestosm);
    map.mapTypes.set('mapquestopenaerial', mapquestopenaerial);
    initCopyrights();
    //map.mapTypes.set('osm', openStreet);

    // directory of Drupal installation
    var basepath = (typeof Drupal.settings.basePath === "undefined") ? '' : Drupal.settings.basePath;
    
    // Add the right panel
    var panelcontrol = new PanelControl({
        forms:{
        fuente: {name: 'Mapas', tooltip: 'Proveedor de los mapas de la capa inferior (terreno, etc)',
                type: 'radio', list: {
                    google: { name: 'Google', tooltip: 'Google Maps', default: true },
                    bing: { name: 'Bing', tooltip: 'Bing Maps', disabled: true },
                    osm: { name: 'OSM', tooltip: 'OpenStreetMap', disabled: true },
                    mapquest: { name: 'MapQuest', tooltip: 'Map Quest', disabled: true }}},
        capas: {name: 'Capas', tooltip: 'Capas de datos extra',
                type: 'checkbox', list: {
                    //supernodos: { name: 'Supernodos', tooltip: 'Nodos con más de 1 enlace inalámbrico', default: true},
                    nodos: { name: 'Nodos', tooltip: /*'Nodos clientes (1 enlace inalámbrico)'*/'Nodos y supernodos', default: true,
                                        extrahtml: '<img id="img_overlay_nodos" alt="(loading)" title="cargando" src="'
                                        + basepath + 'sites/all/modules/guifi/js/loading.gif" style="vertical-align: middle; margin-left: 10px;" />' },
                    //superenlaces: { name: 'Superenlaces', tooltip: 'Enlaces entre supernodos (troncales)', default: true},
                    enlaces: { name: 'Enlaces', tooltip: /*'Enlaces cliente (de nodo a supernodo)'*/'Enlaces cliente y enlaces troncales', default: true,
                                        extrahtml: '<img id="img_overlay_enlaces" alt="(loading)" title="cargando" src="'
                                        + basepath + 'sites/all/modules/guifi/js/loading.gif" style="vertical-align: middle; margin-left: 10px;" />'}}}
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

    // sets the 'loading' icon visible for the layer
    var loadingTiles = function (p) {
        var n = document.getElementById('img_overlay_nodos');
        var e = document.getElementById('img_overlay_enlaces');
        if (p.panel.capas.nodos && p.panel.capas.enlaces) {
            if (n) { n.style.display='inline'; }
            if (e) { e.style.display='inline'; }
        } else {
            if (p.panel.capas.nodos) {
                if (n) { n.style.display='inline'; }
            } else if (p.panel.capas.enlaces) {
                if (e) { e.style.display='inline'; }
            }
        }   
    }
    // loads the selected layers
    var toggleOverlays = function () {
        var n = document.getElementById('img_overlay_nodos');
        var e = document.getElementById('img_overlay_enlaces');
        if (this.panel.capas.nodos && this.panel.capas.enlaces) {
            map.overlayMapTypes.setAt(0, guifinodeslinks.overlay);
            n.style.display='inline';
            e.style.display='inline';
        } else {
            if (this.panel.capas.nodos) {
                map.overlayMapTypes.setAt(0, guifinodes.overlay);
                n.style.display='inline';
            } else if (this.panel.capas.enlaces) {
                map.overlayMapTypes.setAt(0, guifilinks.overlay);
                e.style.display='inline';
            } else {
                map.overlayMapTypes.pop();
                n.style.display='none';
                e.style.display='none';
            }
        }
    }
    google.maps.event.addDomListener(panelcontrol.inputs.nodos, 'click', toggleOverlays);
    google.maps.event.addDomListener(panelcontrol.inputs.enlaces, 'click', toggleOverlays);
    // every time the zoom changes, new tiles have to be loaded
    google.maps.event.addListener(map, 'zoom_changed', function () {
        new loadingTiles(panelcontrol);
    });
    // There's no way to know for sure if new tiles have to be loaded.
    // We could add an event hook for 'bounds_changed', but that won't work
    // if we just move the map a little, but not enough to go outside the tile.
    // 'bounds_changed' would probably work for base layers, but not for overlays.
    // We really need to migrate to OpenLayers...

    // hide the 'loading' icon when the tiles finish loading
    google.maps.event.addListener(guifinodes.overlay, 'tilesloaded', function () {
        var n = document.getElementById('img_overlay_nodos');
        n.style.display='none';
    });
    google.maps.event.addListener(guifilinks.overlay, 'tilesloaded', function () {
        var n = document.getElementById('img_overlay_enlaces');
        n.style.display='none';
    });
    google.maps.event.addListener(guifinodeslinks.overlay, 'tilesloaded', function () {
        var n = document.getElementById('img_overlay_nodos');
        var e = document.getElementById('img_overlay_enlaces');
        n.style.display='none';
        e.style.display='none';
    });

    
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


