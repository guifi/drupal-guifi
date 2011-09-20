var map = null;

var marker_NE;
var marker_SW;
var marker_move ;

var border;

if(Drupal.jsEnabled) {
    $(document).ready(function(){
        draw_map();
    }); 
}

function drawp_map() 
{

    var divmap = document.getElementById("map");
    var lat = document.getElementById("edit-lat").value;
    var lon = document.getElementById("edit-lon").value;
    var baseURL=document.getElementById("edit-guifi-wms").value;

    var node  = new google.maps.LatLng(lat, lon);

    opts = {
        center: node,
        zoom: 16,
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

        mapTypeId: google.maps.MapTypeId.HYBRID
    }

    // Add the map to the div
    map = new google.maps.Map(divmap, opts);

    // Add the guifi layer
    var guifi = new GuifiMapType(map, baseURL);
    map.overlayMapTypes.insertAt(0, guifi.overlay);
    
    map.setCenter(new google.maps.LatLng(20.0, -10.0), 2);
    return; 
    initialPosition();
  }
}
 
function initialPosition()
{
 map.clearOverlays();

 var newNE = new GLatLng(document.getElementById("maxy").value, 
			 document.getElementById("maxx").value);
 var newSW = new GLatLng(document.getElementById("miny").value, 
			 document.getElementById("minx").value); 

 var newBounds = new GLatLngBounds(newSW, newNE) ;

 marker_NE = new GMarker(newBounds.getNorthEast()) ;
 marker_SW = new GMarker(newBounds.getSouthWest()) ;
 marker_move = new GMarker( new GLatLng(((marker_SW.getPoint().lat() + marker_NE.getPoint().lat()) / 2),
		 (marker_NE.getPoint().lng() + marker_SW.getPoint().lng()) / 2)) ;
 marker_move.savePoint = marker_move.getPoint() ;			// Save for later
 
 GEvent.addListener(map, "click", function(marker, point) {
   if (marker) {
     null;
   } else {  
     map.clearOverlays();    
     var marcador = new GMarker(point);
     var basePath = Drupal.settings.basePath;

     if (map.getZoom() > 15) {
       map.addOverlay(marcador);
       marcador.openInfoWindowHtml(
         'Lat : '+point.y+'<br>Lon: '+point.x+
         '<br><a href="'+basePath+'node/add/guifi-node?lon='
           +point.x+'&lat='+point.y+
           '&zone='+document.getElementById("zone_id").value+
         '" TARGET=fijo APPEND=blank>Add a new node here</a>');
     } else {
       map.setCenter(point,map.getZoom()+3);	
     }
   }
 }	);
 updatePolyline();
}

function updatePolyline()
{
 var bounds = new GLatLngBounds();
	
 if (border)
 {
  map.removeOverlay(border);
 }

 // Check for moved center...

 if ( marker_move.getPoint() != marker_move.savePoint )
 {
  var x = marker_move.getPoint().lat() - marker_move.savePoint.lat() ;
  var y = marker_move.getPoint().lng() - marker_move.savePoint.lng() ;
  marker_SW.setPoint( new GLatLng( marker_SW.getPoint().lat() + x, marker_SW.getPoint().lng() + y) ) ;
  marker_NE.setPoint( new GLatLng( marker_NE.getPoint().lat() + x, marker_NE.getPoint().lng() + y) ) ;

 } else						// Center not moved so move center
 {
  var x = (marker_SW.getPoint().lat() + marker_NE.getPoint().lat()) / 2 ;
  var y = (marker_NE.getPoint().lng() + marker_SW.getPoint().lng()) / 2 ;
  marker_move.setPoint( new GLatLng(x,y) ) ;
 // map.setCenter(new GLatLng(x,y),Math.abs(90/x));
  
  map.setCenter(new GLatLng(x,y));
 }

 marker_move.savePoint = marker_move.getPoint() ;			// Save for later

 var points = [
      marker_NE.getPoint(),
      new GLatLng(marker_SW.getPoint().lat(), marker_NE.getPoint().lng()),
      marker_SW.getPoint(),
      new GLatLng(marker_NE.getPoint().lat(), marker_SW.getPoint().lng()),
      marker_NE.getPoint()];
 border = new GPolyline(points, "#66000");
 
 map.addOverlay(border);
 bounds.extend(marker_SW.getPoint());
 bounds.extend(marker_NE.getPoint());
 map.setZoom(map.getBoundsZoomLevel(bounds)); 
 
// map.setCenter(new GLatLng(20.0, -10.0), 2)

}


