var map = null;

var marker_NE;
var marker_SW;
var marker_move ;

var border;
var groundOverlay;

var icon_NE;
var icon_SW;
var icon_move ;

    jQuery(document).ready(function($) {
        draw_map();
    });

function draw_map() 
{

    var divmap = document.getElementById("map");
    var baseURL = document.getElementById("edit-guifi-wms").value;

    opts = {
        center: new google.maps.LatLng(20.0, -10.0),
        zoom: 2,
        minZoom: 2,
        mapTypeControl: true,
        mapTypeControlOptions: {
            mapTypeIds: [ google.maps.MapTypeId.ROADMAP,
                          google.maps.MapTypeId.TERRAIN,
                          google.maps.MapTypeId.SATELLITE,
                          google.maps.MapTypeId.HYBRID ]
        },
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        scaleControl: false,
        streetViewControl: false,
        zoomControl: true,
        panControl: true,
        zoomControlOptions: {
            style: google.maps.ZoomControlStyle.LARGE
        },

    }

    // Add the map to the div
    map = new google.maps.Map(divmap, opts);

    // Add the OSM map type
    //map.mapTypes.set('osm', openStreet);
    //initCopyrights();

    // Guifi control
    var guifi = new GuifiLayer(map, baseURL);
    map.overlayMapTypes.insertAt(0, guifi.overlay);

    var guifiControl = new Control("guifi");
    guifiControl.div.index = 1;
    map.controls[google.maps.ControlPosition.TOP_RIGHT].push(guifiControl.div);

    // Setup the click event listeners: simply set the map to Chicago
    google.maps.event.addDomListener(guifiControl.ui, 'click', function() {
        if (guifiControl.enabled) {
            map.overlayMapTypes.removeAt(0);
            guifiControl.disable();
        } else {
            // Add the guifi layer
            map.overlayMapTypes.insertAt(0, guifi.overlay);
            guifiControl.enable();
        }
    });

    var icon_NE_url = document.getElementById("edit-jspath").value + 'marker_NE_icon.png';
    icon_NE = new google.maps.MarkerImage(
                    icon_NE_url,
                    new google.maps.Size(32, 32),
                    null,
                    new google.maps.Point(22, 10));

    var icon_SW_url = document.getElementById("edit-jspath").value + 'marker_SW_icon.png';
    icon_SW = new google.maps.MarkerImage(
                    icon_SW_url, 
                    new google.maps.Size(32, 32), 
                    null,
                    new google.maps.Point(6, 20));

    var icon_move_url = document.getElementById("edit-jspath").value + 'marker_move_icon.png';
    icon_move = new google.maps.MarkerImage(
                    icon_move_url,
                    new google.maps.Size(32, 32),
                    null,
                    new google.maps.Point(6, 20));

    var newNE = new google.maps.LatLng(document.getElementById("edit-maxy").value, 
			                           document.getElementById("edit-maxx").value);
    var newSW = new google.maps.LatLng(document.getElementById("edit-miny").value, 
			                           document.getElementById("edit-minx").value); 

    var newBounds = new google.maps.LatLngBounds(newSW, newNE) ;

    marker_NE = new google.maps.Marker( { position: newBounds.getNorthEast(), draggable: true, icon: icon_NE, map: map } ) ;
    google.maps.event.addListener(marker_NE, 'dragend', function() { updatePolyline() ; }) ;

    marker_SW = new google.maps.Marker( { position: newBounds.getSouthWest(), draggable: true, icon: icon_SW, map: map } ) ;
    google.maps.event.addListener(marker_SW, 'dragend', function() { updatePolyline() ; }) ;

    marker_move = new google.maps.Marker( { position: new google.maps.LatLng(((marker_SW.getPosition().lat() + marker_NE.getPosition().lat()) / 2), (marker_NE.getPosition().lng() + marker_SW.getPosition().lng()) / 2), draggable: true, icon: icon_move, map: map  }) ;
    google.maps.event.addListener(marker_move, 'dragend', function() { updatePolyline() ; }) ;
    marker_move.savePoint = marker_move.getPosition() ;			// Save for later

    updatePolyline();
}

function updatePolyline() {

    if (border) {
        border.setMap(null);
    }

    // Check for moved center...
    if ( marker_move.getPosition() != marker_move.savePoint ) {
        var x = marker_move.getPosition().lat() - marker_move.savePoint.lat() ;
        var y = marker_move.getPosition().lng() - marker_move.savePoint.lng() ;

        swx = marker_SW.getPosition().lat() + x;
        if (swx < -85) swx = -85;

        swy = marker_SW.getPosition().lng() + y;
        if (swy < -170) swy = -170;

        nex = marker_NE.getPosition().lat() + x;
        if (nex > 85) nex = 85;

        ney = marker_NE.getPosition().lng() + y;
        if (ney > 170) ney = 170;

        marker_SW.setPosition( new google.maps.LatLng( swx, swy ) ) ;
        marker_NE.setPosition( new google.maps.LatLng( nex, ney ) ) ;
    }

    // Center not moved so move center
    var x = (marker_SW.getPosition().lat() + marker_NE.getPosition().lat()) / 2 ;
    var y = (marker_NE.getPosition().lng() + marker_SW.getPosition().lng()) / 2 ;
    marker_move.setPosition( new google.maps.LatLng(x,y) ) ;
    map.setCenter(new google.maps.LatLng(x,y));

    marker_move.savePoint = marker_move.getPosition() ;			// Save for later

    var points = [
        marker_NE.getPosition(),
        new google.maps.LatLng(marker_SW.getPosition().lat(), marker_NE.getPosition().lng()),
        marker_SW.getPosition(),
        new google.maps.LatLng(marker_NE.getPosition().lat(), marker_SW.getPosition().lng()),
        marker_NE.getPosition()
    ];

    border = new google.maps.Polyline( { path: points, strokeColor: "#66000", strokeOpacity: .4, strokeWeight: 5, map: map });

 
    document.getElementById("edit-miny").value = marker_SW.getPosition().lat();
    document.getElementById("edit-minx").value = marker_SW.getPosition().lng();
    document.getElementById("edit-maxy").value = marker_NE.getPosition().lat();
    document.getElementById("edit-maxx").value = marker_NE.getPosition().lng();

    var bounds = new google.maps.LatLngBounds(marker_SW.getPosition(), marker_NE.getPosition());
    map.fitBounds(bounds);
}


