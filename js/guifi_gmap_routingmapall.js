var map = null;
var swinit = 0;
var overlays = Array();
var initControl = null;
var BGPControl = null;
var OSPFControl = null;
var AreaControl = null;
    
var alinks = new Array;
var anodes = new Array;
var colors = new Array("","#ff0000","#00aeff")
var pics = new Array(0,1,5,2)
var icons = new Array();
var picspath = new Array();
var narea;

    jQuery(document).ready(function($) {
        draw_map();
    });

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
    
    for(var i=1;i<=3;i++){
      var url = document.getElementById("edit-jspath").value+'marker_traceroute_icon' + pics[i] + '.png';
      picspath[i] = url;

      icons[i] = new google.maps.MarkerImage(
                        url,
                        new google.maps.Size(10, 10),
                        null,
                        new google.maps.Point(5, 5));
    }
   
    // Init control
    initControl = new Control("init", true, true, 55);
    map.overlayMapTypes.push(null);
    initControl.div.index = 1;
    map.controls[google.maps.ControlPosition.RIGHT_TOP].push(initControl.div);

    // Setup the click event listeners
    google.maps.event.addDomListener(initControl.ui, 'click', function() {
        if (initControl.enabled) {
            initControl.disable();
        } else {
            initControl.loading();
            init();
        }
    }); 

    // BGP control
    BGPControl = new Control("BGP", true, false, 55);
    map.overlayMapTypes.push(null);
    BGPControl.div.index = 1;
    map.controls[google.maps.ControlPosition.RIGHT_TOP].push(BGPControl.div);

    // Setup the click event listeners
    google.maps.event.addDomListener(BGPControl.ui, 'click', function() {
        if (!BGPControl.blocked) {
            if (BGPControl.enabled) {
                fbgp(0);
                BGPControl.disable();
            } else {
                fbgp(1);
                BGPControl.enable();
            }
        }
    }); 

    // OSPF control
    OSPFControl = new Control("OSPF", true, false, 55);
    map.overlayMapTypes.push(null);
    OSPFControl.div.index = 1;
    map.controls[google.maps.ControlPosition.RIGHT_TOP].push(OSPFControl.div);

    // Setup the click event listeners
    google.maps.event.addDomListener(OSPFControl.ui, 'click', function() {
        if (!OSPFControl.blocked) {
            if (OSPFControl.enabled) {
                fospf(0);
                OSPFControl.disable();
            } else {
                fospf(1);
                OSPFControl.enable();
            }
        }
    }); 

    // Area control
    AreaControl = new Control("Area", true, false, 55);
    map.overlayMapTypes.push(null);
    AreaControl.div.index = 1;
    map.controls[google.maps.ControlPosition.RIGHT_TOP].push(AreaControl.div);

    // Setup the click event listeners
    google.maps.event.addDomListener(AreaControl.ui, 'click', function() {
        if (!AreaControl.blocked) {
            farea();
        }
    }); 

    disable_widgets();
    jQuery("#topmap").text("Find routing area and click the init button");
    swinit = 1;

}

function init(){
    for (var i = 0; i < overlays.length; i++) {
        overlays[i].setMap(null);
    }
    overlays.length = 0;

    jQuery("#topmap").text("Loading...");
    swinit = 2;

    var vbounds=map.getBounds();
    var vlatlon_sw=vbounds.getSouthWest();
    var vlatlon_ne=vbounds.getNorthEast();
    var lat1=vlatlon_sw.lat();
    var lon1=vlatlon_sw.lng();
    var lat2=vlatlon_ne.lat();
    var lon2=vlatlon_ne.lng();
    var vurl='/guifi/routingmap/allsearch/0?lat1='+lat1+'&lon1='+lon1+'&lat2='+lat2+'&lon2='+lon2;
    //var vurl='/guifi/routingmap/allsearch/0?lat1=41.21378767703215&lon1=0.97503662109375&lat2=42.44170109062157&lon2=3.6199951171874996';

    var r = jQuery.ajax({
                     url: vurl,
                     success: function(data) {
                         build_routing(data);
                     }
                   });
}

function build_routing(pdata) {
  //alert(pdata);
  adata=eval(pdata);

  avars = adata[0];
  anodes = adata[1];
  alinks = adata[2];
  
  narea = 0;
  
  var v=0;
  for (node in anodes){
    v=0;
    if(anodes[node]["bgp"]>0) v=1; 
    if(anodes[node]["ospf"]>0) v=v+2; 
    var vpoint = new google.maps.LatLng(anodes[node]["lat"], anodes[node]["lon"]);
    anodes[node]["overlay"]= new google.maps.Marker({ position: vpoint, icon:icons[v], clickable:false });
    overlays.push(anodes[node]["overlay"]);
    anodes[node]["overlay"].setMap(map);
  }

  for(link in alinks){
    if(alinks[link]["n1"]>0 && alinks[link]["n2"]>0 && alinks[link]["type"]=="wds") {
        var vpoint1 = new google.maps.LatLng(anodes[alinks[link]["n1"]]["lat"], anodes[alinks[link]["n1"]]["lon"]);
        var vpoint2 = new google.maps.LatLng(anodes[alinks[link]["n2"]]["lat"], anodes[alinks[link]["n2"]]["lon"]);
        if (alinks[link]["routing"]=="BGP") v=1; else v=2;            
        alinks[link]["overlay"] = new google.maps.Polyline({ path: [vpoint1,vpoint2], 
                                                             strokeColor: colors[v],
                                                             strokeWeight: 2,
                                                             strokeOpacity: 1,
                                                             clickable: false });
        overlays.push(alinks[link]["overlay"]);
        alinks[link]["overlay"].setMap(map);
    }
  }

  jQuery("#topmap").html("<img src='"+picspath[1]+"'>&nbsp;BGP&nbsp;&nbsp;&nbsp;<img src='"+picspath[3]+"'>&nbsp;BGP/OSPF&nbsp;&nbsp;&nbsp;<img src='"+picspath[2]+"'>&nbsp;OSPF");
  initControl.disable();
  enable_widgets();
}

function enable_widgets(){
  BGPControl.unblock();
  BGPControl.enable();
  OSPFControl.unblock();
  OSPFControl.enable();
  AreaControl.unblock();
}

function disable_widgets(){
  BGPControl.disable();
  BGPControl.block();
  OSPFControl.disable();
  OSPFControl.block();
  AreaControl.disable();
  AreaControl.block();
}

function fbgp(p){
    var v=0;
    for (node in anodes){
        v=0;
        if(anodes[node]["bgp"]>0) v=1; 
        if(anodes[node]["ospf"]>0) v=v+2;
        if(p==0){
            if(v==1 || BGPControl.enabled==false){
                anodes[node]["overlay"].setMap(null);
            }
        } else {
            if(v==1 || v==3) {
                anodes[node]["overlay"].setMap(map);
            }
        }
    }

    for (link in alinks){
        if (alinks[link]["n1"]>0 && alinks[link]["n2"]>0 && alinks[link]["type"]=="wds") {
            if (alinks[link]["routing"]=="BGP"){
                if (p==0) {
                    alinks[link]["overlay"].setMap(null);
                } else {
                    alinks[link]["overlay"].setMap(map);
                }
            }
        }
    }    
}

function fospf(p){
  var v=0;
  for (node in anodes){
    v=0;
    if(anodes[node]["bgp"]>0) v=1; 
    if(anodes[node]["ospf"]>0) v=v+2;
     if(p==0){
        if(v==2 || OSPFControl.enabled == false ){
            anodes[node]["overlay"].setMap(null);
        }
    }else{
        if(v==2 || v==3){
            anodes[node]["overlay"].setMap(map);
        }
    }
  }
    for(link in alinks){
      if(alinks[link]["n1"]>0 && alinks[link]["n2"]>0 && alinks[link]["type"]=="wds") {
          if(alinks[link]["routing"]=="OSPF"){
            if(p==0){
                alinks[link]["overlay"].setMap(null);
            }else{
                alinks[link]["overlay"].setMap(map);
            }
          }
      }
    }    
}

function farea(){
    if(narea>=avars[1]){
        narea=0;
        alert("Total "+avars[1]+" area's");
    }
    for(link in alinks){
      if(alinks[link]["n1"]>0 && alinks[link]["n2"]>0 && alinks[link]["type"]=="wds") {
          if(alinks[link]["routing"]=="OSPF"){
            if(alinks[link]["area"]==narea){
                alinks[link]["overlay"].setOptions({strokeColor: "#0000ff"});
            }else{
                alinks[link]["overlay"].setOptions({strokeColor: colors[2]});
            }
          }
      }
    }
    narea++;
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
