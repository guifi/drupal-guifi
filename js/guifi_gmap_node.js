var map = null;
var marcador = null;

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
            mapTypeIds: [ google.maps.MapTypeId.ROADMAP,
                          google.maps.MapTypeId.TERRAIN,
                          google.maps.MapTypeId.SATELLITE, 
                          google.maps.MapTypeId.HYBRID ]
        },
        mapTypeId: google.maps.MapTypeId.HYBRID,
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

    // Add the marker
    marcador = new google.maps.Marker( { position: node, draggable: true, map: map } );

    // Add the autocomplete
    var search = document.getElementById("mapSearch");
    var bounds = new google.maps.LatLngBounds(
                        new google.maps.LatLng(-10.76171875, 34.91003829791827),
                        new google.maps.LatLng(4.2578125, 43.91632552738952));
    var autocomplete = new google.maps.places.Autocomplete(search);
    autocomplete.bindTo('bounds', map);

    google.maps.event.addListener(autocomplete, 'place_changed', function() {
        var place = autocomplete.getPlace();
        map.setOptions({ center: place.geometry.location, zoom: 12 });
        marcador.setPosition(place.geometry.location);
    });

    $("input#mapSearch").bind("keypress", function(e) {
        if ( e.keyCode == 13 ) {
            e.preventDefault();
            var addr = $(this).val();
            var geo = new google.maps.Geocoder();
            geo.geocode( { address: addr }, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    var result = results[0].geometry.location;
                    map.setOptions({ center: result, zoom: 12 });
                    marcador.setPosition(result);
                }
            });
        }
        return true;
    });

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
