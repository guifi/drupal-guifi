var map = null;

var marker_Node;

if(Drupal.jsEnabled) {
	  $(document).ready(function(){
		xz();
	    }); 
	}

function xz() 
{
  if (GBrowserIsCompatible()) {
    map=new GMap2(document.getElementById("map"));
    if (map.getSize().height >= 300)
      map.addControl(new GLargeMapControl());
    else
      map.addControl(new GSmallMapControl());
    if (map.getSize().width >= 500) {
      map.addControl(new GScaleControl()) ;
      map.addControl(new GOverviewMapControl());
  	  map.addControl(new GMapTypeControl());
    }
    map.enableScrollWheelZoom();
    
	var layer1 = new GWMSTileLayer(map, new GCopyrightCollection("guifi.net"),1,17);
    layer1.baseURL=document.getElementById("edit-guifi-wms").value;
    layer1.layers="Nodes,Links";
    layer1.mercZoomLevel = 0;
    layer1.opacity = 1.0;

    var myMapTypeLayers=[G_SATELLITE_MAP.getTileLayers()[0],layer1];
    var myCustomMapType = new GMapType(myMapTypeLayers, 
    		G_NORMAL_MAP.getProjection(), "guifi.net", G_SATELLITE_MAP);

    map.addMapType(myCustomMapType);	
	
    var newNode = new GLatLng(document.getElementById("edit-lat").value, 
			 document.getElementById("edit-lon").value);

    if (newNode != '(0, 0)') {
      map.setCenter(newNode, 16);
    } else {
    map.setCenter(new GLatLng(41.974175, 2.238118), 2);
    }
    GEvent.addListener(map, "click", function(marker, point) {
	     map.clearOverlays();    
   	     var marcador = new GMarker(point);

   	     if (map.getZoom() > 15) {
   	       map.addOverlay(marcador);
   	       document.getElementById("edit-latdeg").value = point.lat();
   	       document.getElementById("edit-londeg").value = point.lng();
   	       document.getElementById("edit-latmin").value = "";
   	       document.getElementById("edit-lonmin").value = "";
   	       document.getElementById("edit-latseg").value = "";
   	       document.getElementById("edit-lonseg").value = "";
   	       
   	       map.setCenter(point);
   	     } else {
   	       map.setCenter(point,map.getZoom()+3);	
   	     }
	});

    var marcador = new GMarker(newNode);
    map.addOverlay(marcador);
    map.setMapType(myCustomMapType);
  }
}
