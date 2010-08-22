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
		xz();
	    }); 
	}

function xz() 
{
  if (GBrowserIsCompatible()) {
    map=new GMap2(document.getElementById("map"));
    map.addControl(new GLargeMapControl());
    map.addControl(new GScaleControl()) ;
//    map.addControl(new GMapTypeControl());
    map.enableScrollWheelZoom();
    map.addControl(new GOverviewMapControl());
    
	var layer1 = new GWMSTileLayer(map, new GCopyrightCollection("guifi.net"),1,17);
    layer1.baseURL=document.getElementById("edit-guifi-wms").value;
    layer1.layers="Nodes,Links";
    layer1.mercZoomLevel = 0;
    layer1.opacity = 1.0;

    var myMapTypeLayers=[G_SATELLITE_MAP.getTileLayers()[0],layer1];
    var myCustomMapType = new GMapType(myMapTypeLayers, 
    		G_NORMAL_MAP.getProjection(), "guifi.net", G_SATELLITE_MAP);

    map.addMapType(myCustomMapType);
	map.addControl(new GMapTypeControl());	
	
    icon_NE = new GIcon(); 
    icon_NE.image = document.getElementById("edit-jspath").value+
      'marker_NE_icon.png';
    icon_NE.shadow = '';
    icon_NE.iconSize = new GSize(32, 32);
    icon_NE.shadowSize = new GSize(22, 20);
    icon_NE.iconAnchor = new GPoint(22, 10);
    icon_NE.dragCrossImage = '';

    icon_SW = new GIcon(); 
    icon_SW.image = document.getElementById("edit-jspath").value+
      'marker_SW_icon.png';
    icon_SW.shadow = '';
    icon_SW.iconSize = new GSize(32, 32);
    icon_SW.shadowSize = new GSize(22, 20);
    icon_SW.iconAnchor = new GPoint(6, 20);
    icon_SW.dragCrossImage = '';

    icon_move = new GIcon();
    icon_move.image = document.getElementById("edit-jspath").value+
      'marker_move_icon.png';
    icon_move.shadow = '';
    icon_move.iconSize = new GSize(32, 32);
    icon_move.shadowSize = new GSize(6, 20);
    icon_move.iconAnchor = new GPoint(6, 20);
    icon_move.dragCrossImage = '';


    map.setCenter(new GLatLng(20.0, -10.0), 2);
    //map.setMapType(myCustomMapType);
    
    initialPosition();
  }
}
 
function initialPosition()
{
 map.clearOverlays();

 var newNE = new GLatLng(document.getElementById("edit-maxy").value, 
			 document.getElementById("edit-maxx").value);
 var newSW = new GLatLng(document.getElementById("edit-miny").value, 
			 document.getElementById("edit-minx").value); 

 var newBounds = new GLatLngBounds(newSW, newNE) ;

 marker_NE = new GMarker(newBounds.getNorthEast(), {draggable: true, icon: icon_NE}) ;
 GEvent.addListener(marker_NE, 'dragend', function() { updatePolyline() ; }) ;

 marker_SW = new GMarker(newBounds.getSouthWest(), {draggable: true, icon: icon_SW}) ;
 GEvent.addListener(marker_SW, 'dragend', function() { updatePolyline() ; }) ;

 marker_move = new GMarker( new GLatLng(((marker_SW.getPoint().lat() + marker_NE.getPoint().lat()) / 2),
		 (marker_NE.getPoint().lng() + marker_SW.getPoint().lng()) / 2), {draggable: true, icon: icon_move}) ;
 GEvent.addListener(marker_move, 'dragend', function() { updatePolyline() ; }) ;
 marker_move.savePoint = marker_move.getPoint() ;			// Save for later
 
 map.addOverlay(marker_NE);
 map.addOverlay(marker_SW);
 map.addOverlay(marker_move);

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
 
 document.getElementById("edit-miny").value = marker_SW.getPoint().lat();
 document.getElementById("edit-minx").value = marker_SW.getPoint().lng();
 document.getElementById("edit-maxy").value = marker_NE.getPoint().lat();
 document.getElementById("edit-maxx").value = marker_NE.getPoint().lng();

 map.addOverlay(border);
 bounds.extend(marker_SW.getPoint());
 bounds.extend(marker_NE.getPoint());
 map.setZoom(map.getBoundsZoomLevel(bounds)); 
 
// map.setCenter(new GLatLng(20.0, -10.0), 2)

}


