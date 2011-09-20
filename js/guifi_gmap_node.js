var map = null;

if(Drupal.jsEnabled) {
    $(document).ready(function(){
        draw_map();
    }); 
}

function draw_map() 
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
            mapTypeIds: [google.maps.MapTypeId.ROADMAP, google.maps.MapTypeId.SATELLITE, google.maps.MapTypeId.HYBRID, "guifi.net"],
        },
        mapTypeId: google.maps.MapTypeId.HYBRID,
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

    // Add the marker
    var marcador = new google.maps.Marker( { position: node, draggable: true } );
    marcador.setMap(map);

    // Add the guifi layer
    google.maps.event.addListener(map, 'idle', function() {
        // Draw the WMS layer 
        var guifi = new GuifiMapType(map, baseURL);
        map.overlayMapTypes.insertAt(0, guifi.overlay);
    });

    //map.mapTypes.set(guifiMapType.MAPTYPE_ID, guifiMapType.overlay);
    //map.setMapTypeId(guifiMapType.MAPTYPE_ID);

    if (node.toString() == '(0, 0)') {
        map.setCenter(new google.maps.LatLng(41.974175, 2.238118));
        map.setZoom(2);
    }

    google.maps.event.addListener(map, "click", function(event) {
        
   	    marcador.setPosition(event.latLng);
   	    document.getElementById("edit-latdeg").value = event.latLng.lat();
   	    document.getElementById("edit-londeg").value = event.latLng.lng();
   	    document.getElementById("edit-latmin").value = "";
   	    document.getElementById("edit-lonmin").value = "";
   	    document.getElementById("edit-latseg").value = "";
   	    document.getElementById("edit-lonseg").value = "";
   	    map.setCenter(event.latLng);

   	    if (map.getZoom() <= 15 ) {
            map.setZoom(map.getZoom()+3);	
   	    }

	});

    google.maps.event.addListener(marcador, 'dragend', function(event) {
   	    document.getElementById("edit-latdeg").value = event.latLng.lat();
   	    document.getElementById("edit-londeg").value = event.latLng.lng();
   	    document.getElementById("edit-latmin").value = "";
   	    document.getElementById("edit-lonmin").value = "";
   	    document.getElementById("edit-latseg").value = "";
   	    document.getElementById("edit-lonseg").value = "";

   	    if (map.getZoom() <= 15 ) {
   	        map.setCenter(event.latLng);
            map.setZoom(map.getZoom()+3);	
   	    }

    });
}
