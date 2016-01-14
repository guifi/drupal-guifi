var map = null;
var initControl = null;
var overlays = Array();

    jQuery(document).ready(function($) {
        draw_map();
    });

var init_widget;
var swinit = 0;
    
var oLinks = new Object;
var oNodes = new Object;
var oGNodes = new Array;
var oGLinks = new Array;
var oSubRouteLevel = new Array; //keeps the level of each subroute
var oLinksId = new Object;        //array control links properties
var colors = new Array("","#ff0000","#fb00ff","#6f00ff","#001eff","#00aeff","#00ffc3","#00ff37","#59ff00","#e5ff00","#ff8c00")
var icons = new Array();
var nRoute = 0;
var nRouteActual = 0;

function draw_map(){

    var divmap = document.getElementById("map");
    var baseURL = document.getElementById("guifi-wms").value;

    var opts = {
        center: new google.maps.LatLng(41.83, 2.30),
        zoom: 9,
        minZoom: 2,
        mapTypeControl: true,
        mapTypeControlOptions: {
            mapTypeIds: [ google.maps.MapTypeId.ROADMAP,
                          google.maps.MapTypeId.TERRAIN,
                          google.maps.MapTypeId.SATELLITE,
                          google.maps.MapTypeId.HYBRID ]
        },
        mapTypeId: google.maps.MapTypeId.SATELLITE,
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
    map.overlayMapTypes.push(null);
    map.overlayMapTypes.setAt(0, guifi.overlay);

    var guifiControl = new Control("guifi");
    guifiControl.div.index = 1;
    map.controls[google.maps.ControlPosition.TOP_RIGHT].push(guifiControl.div);

    // Setup the click event listeners
    google.maps.event.addDomListener(guifiControl.ui, 'click', function() {
        if (guifiControl.enabled) {
            map.overlayMapTypes.setAt(0, null);
            guifiControl.disable();
        } else {
            // Add the guifi layer
            map.overlayMapTypes.setAt(0, guifi.overlay);
            guifiControl.enable();
        }
    });

    for(var i=1;i<=10;i++){
        var url = document.getElementById("edit-jspath").value+'marker_traceroute_icon' + i + '.png';
        icons[i] = new google.maps.MarkerImage(
                        url,
                        new google.maps.Size(10, 10),
                        null,
                        new google.maps.Point(5, 5));
    }
  
    // Init control
    initControl = new Control("init", true);
    map.overlayMapTypes.push(null);
    initControl.div.index = 1;
    map.controls[google.maps.ControlPosition.RIGHT_TOP].push(initControl.div);

    // Setup the click event listeners
    google.maps.event.addDomListener(initControl.ui, 'click', function() {
        if (initControl.enabled) {
            initControl.disable();
        } else {
            init(0);
            initControl.enable();
        }
    });
 
    jQuery("#topmap").text("Find a supernode ospf area and click the init button");
    swinit = 1;
}

function init(p){
    for (var i = 0; i < overlays.length; i++) {
        overlays[i].setMap(null);
    }
    overlays.length = 0;

    google.maps.event.clearListeners(map,"click");
    google.maps.event.addListener(map,"click", function(event) {     
        if (event.latLng) { 
            init_search(event.latLng);
        }
    });
    swinit=1;
    jQuery("#topmap").text("Click on the initial node");
}

function init_search(platlng){
    if(swinit==1){
        jQuery("#topmap").text("Searching");
        google.maps.event.clearListeners(map,"click");
        swinit=2;
        loaddata(platlng.lat(),platlng.lng());
    }
}

function loaddata(plat,plon){
    var vinc=0.001
    var lat1=plat-vinc;
    var lon1=plon-vinc;
    var lat2=plat+vinc;
    var lon2=plon+vinc;
    //alert(lat1 + ' ' + lon1 + ' ' + lat2 + ' ' + lon2);
    var vurl='/guifi/routingmap/search/0?lat1='+lat1+'&lon1='+lon1+'&lat2='+lat2+'&lon2='+lon2
    //var vurl='http://localhost/guifi/routingmap/search/0?lat1=41.86486002927498&lon1=2.289477752685547&lat2=41.866860029274974&lon2=2.2914777526855468';
    //alert(vurl);
    loadXMLDoc(vurl);
}

function build_routing(pdata){
  //alert(pdata);
  adata=eval(pdata);
  vnodeinit = adata[0];
  if(vnodeinit==0){
    alert("You have not selected any node. Has to try again");
    init(1);
    initControl.disable();
    return;
  }else{
    jQuery("#topmap").text("You've selected the supernode " + vnodeinit + ". Drawing....");
  }

  anodes = adata[1];
  alinks = adata[2];
  anets = adata[3];
  aznets = adata[4];
  agnets = adata[5];
  
  for (node in anodes){
    var vpoint = new google.maps.LatLng(anodes[node]["lat"],anodes[node]["lon"]);
    var vnode= new google.maps.Marker({ position: vpoint, icon: icons[1], clickable: false});
    overlays.push(vnode);
    vnode.setMap(map);
  }

  for(link in alinks){
    var vpoint1 = new google.maps.LatLng(anodes[alinks[link]["nid1"]]["lat"],anodes[alinks[link]["nid1"]]["lon"]);
    var vpoint2 = new google.maps.LatLng(anodes[alinks[link]["nid2"]]["lat"],anodes[alinks[link]["nid2"]]["lon"]);
    var vlink = new google.maps.Polyline({ path: [vpoint1,vpoint2], 
                                           strokeColor: "#ff0000", 
                                           strokeWeight: 2, 
                                           strokeOpacity: 1,
                                           clickable: false });
    overlays.push(vlink);
    vlink.setMap(map);
  }

  var output = "ultimate aggregation<br />";
  for(gnet in agnets){
    output += agnets[gnet]["netid"] + "/" + agnets[gnet]["maskbits"]+"&nbsp;&nbsp;" + agnets[gnet]["zone"]+"<br />";
  }

  output += "<br />aggregate subnets<br />";
  for(net in anets){
    output += anets[net]["netid"] + "/" + anets[net]["maskbits"]+"<br />";
  }

  output += "<br />zone networks<br />";
  for(znet in aznets){
    output += aznets[znet]["netid"] + "/" + aznets[znet]["maskbits"]+"&nbsp;&nbsp;broadcast:"+aznets[znet]["broadcast"]+"&nbsp;&nbsp;zone:"+aznets[znet]["zid"]+"&nbsp;"+aznets[znet]["znick"]+"<br />";
  }

  jQuery("#topmap").text("Completed");
  jQuery("#bottommap").text(output);

  // Disable the init button
  initControl.disable();
}

//CONTROLS
function exec_or_value(f, o) {
  if (typeof f != 'function')
    return f;
                // arguments.slice(2) doesn't work
  var a = [];
  for (var i = 2; i < arguments.length; i++)
    a.push(arguments[i]);
  return f.apply(o, a);
}

//httprequest
// REQUEST
function loadXMLDoc(url) {
    var r = jQuery.ajax({
                     url: url,
                     async: false,
                   }).responseText;
    return build_routing(r);j
}
