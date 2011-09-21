var map = null;

var marker_NE;
var marker_SW;
var marker_move ;

var border;
var groundOverlay;

var icon_NE;
var icon_SW;
var icon_move ;

if(Drupal.jsEnabled) {
    $(document).ready(function(){
        draw_map();
    }); 
}

function draw_map() 
{

    var divmap = document.getElementById("map");
    var baseURL = document.getElementById("edit-guifi-wms").value;

    opts = {
        center: new google.maps.LatLng(20.0, -10.0),
        zoom: 2,
        mapTypeControl: true,
        mapTypeControlOptions: {
            mapTypeIds: [ google.maps.MapTypeId.ROADMAP,
                          google.maps.MapTypeId.SATELLITE, google.maps.MapTypeId.HYBRID ],
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

    // Add the guifi layer
    var guifi = new GuifiMapType(map);
    map.overlayMapTypes.insertAt(0, guifi.overlay);

    var icon_NE_url = document.getElementById("edit-jspath").value + 'marker_NE_icon.png';
    icon_NE = new google.maps.MarkerImage( {
                    url: icon_NE_url,
                    size: new google.maps.Size(32, 32),
                    origin: new google.maps.Point(0, 0),
                    anchor: new google.maps.Point(22, 10) });

    //icon_NE.shadowSize = new GSize(22, 20);
    //icon_NE.dragCrossImage = '';
    //icon_NE.shadow = '';

    var icon_SW_url = document.getElementById("edit-jspath").value + 'marker_SW_icon.png';
    icon_SW = new google.maps.MarkerImage( {
                    url: icon_SW_url,
                    size: new google.maps.Size(32, 32),
                    origin: new google.maps.Point(0, 0),
                    anchor: new google.maps.Point(22, 10) });

    //icon_SW.shadow = '';
    //icon_SW.shadowSize = new GSize(22, 20);
    //icon_SW.dragCrossImage = '';

    var icon_move_url = document.getElementById("edit-jspath").value + 'marker_move_icon.png';
    icon_move = new google.maps.MarkerImage( {
                    url: icon_move_url,
                    size: new google.maps.Size(32, 32),
                    origin: new google.maps.Point(0, 0),
                    anchor: new google.maps.Point(6, 20) });

    //icon_move.shadow = '';
    //icon_move.shadowSize = new GSize(6, 20);
    //icon_move.dragCrossImage = '';


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

    var bounds = new google.maps.LatLngBounds();
	
    if (border) {
        border.setMap(null);
    }

    // Check for moved center...
    if ( marker_move.getPosition() != marker_move.savePoint ) {
        var x = marker_move.getPosition().lat() - marker_move.savePoint.lat() ;
        var y = marker_move.getPosition().lng() - marker_move.savePoint.lng() ;
        marker_SW.setPosition( new google.maps.LatLng( marker_SW.getPosition().lat() + x, marker_SW.getPosition().lng() + y) ) ;
        marker_NE.setPosition( new google.maps.LatLng( marker_NE.getPosition().lat() + x, marker_NE.getPosition().lng() + y) ) ;
    } else {
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

    border = new google.maps.Polyline( { path: points, strokeColor: "#66000", strokeOpacity: .4, strokeWeight: 5, map: map });

 
    document.getElementById("edit-miny").value = marker_SW.getPosition().lat();
    document.getElementById("edit-minx").value = marker_SW.getPosition().lng();
    document.getElementById("edit-maxy").value = marker_NE.getPosition().lat();
    document.getElementById("edit-maxx").value = marker_NE.getPosition().lng();

    bounds.extend(marker_SW.getPosition());
    bounds.extend(marker_NE.getPosition());
    map.fitBounds(bounds);

}


