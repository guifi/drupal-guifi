var map = null;

if(Drupal.jsEnabled) {
  $(document).ready(function(){
    draw_map();
  }); 
}

function draw_map() 
{

    var divmap = document.getElementById("map");
    var lat = document.getElementById("lat").value;
    var lon = document.getElementById("lon").value;
    var baseURL=document.getElementById("guifi-wms").value;

    var node  = new google.maps.LatLng(lat, lon);
   
    opts = {
        center: node,
        zoom: 16,
        mapTypeControl: false,
        scaleControl: false,
        streetViewControl: false,
        zoomControl: true,
        panControl: false,
        zoomControlOptions: {
            style: google.maps.ZoomControlStyle.LARGE
        },

        mapTypeId: google.maps.MapTypeId.HYBRID
    }

    // Add the map to the div
    map = new google.maps.Map(divmap, opts);

    // Add the node position as a marker on the map
    var marcador = new google.maps.Marker( { position: node, map: map } );

    google.maps.event.addListener(map, 'idle', function() {
        // Draw the WMS layer 
        var guifi = new GuifiMapType(map, baseURL);
        map.overlayMapTypes.insertAt(0, guifi.overlay); // set the overlay, 0 index
    });

}

