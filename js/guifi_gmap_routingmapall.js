var map = null;


if(Drupal.jsEnabled) {
	  $(document).ready(function(){
        xz();
	    }); 
	}

var init_widget;
var bgp_widget;
var ospf_widget;
var area_widget;
var swinit;
    
var alinks = new Array;
var anodes = new Array;
var colors = new Array("","#ff0000","#00aeff")
var pics = new Array(0,1,5,2)
var icons = new Array();
var picspath = new Array();

var narea;

function xz(){
  swinit=0
  if (GBrowserIsCompatible()) {
    map=new GMap2(document.getElementById("map"));
    if (map.getSize().height >= 300) map.addControl(new GLargeMapControl());
    else map.addControl(new GSmallMapControl());
    if (map.getSize().width >= 500) {
      map.addControl(new GScaleControl()) ;
      map.addControl(new GOverviewMapControl());
  	   map.addControl(new GMapTypeControl());
    }
    map.enableScrollWheelZoom();
    
    for(var i=1;i<=3;i++){
      picspath[i] = document.getElementById("edit-jspath").value+'marker_traceroute_icon' + pics[i] + '.png';
      icons[i] = new GIcon();
      icons[i].image = document.getElementById("edit-jspath").value+'marker_traceroute_icon' + pics[i] + '.png';
      icons[i].shadow = '';
      icons[i].iconSize = new GSize(10, 10);
      icons[i].shadowSize = new GSize(5,5);
      icons[i].iconAnchor = new GPoint(5, 5);
      icons[i].dragCrossImage = '';
    }
    
	 var layer1 = new GWMSTileLayer(map, new GCopyrightCollection("guifi.net"),1,17);
    layer1.baseURL=document.getElementById("guifi-wms").value;
    layer1.layers="Nodes,Links";
    layer1.mercZoomLevel = 0;
    layer1.opacity = 1.0;

    var myMapTypeLayers=[G_SATELLITE_MAP.getTileLayers()[0],layer1];
    var myCustomMapType = new GMapType(myMapTypeLayers, 
    		G_NORMAL_MAP.getProjection(), "guifi.net", G_SATELLITE_MAP);

    map.addMapType(myCustomMapType);
    map.setCenter(new GLatLng(41.83, 2.30), 9);
    map.setMapType(myCustomMapType);
    var vh=25;
    var vw=50;
    create_init_widget(8,35,vw);
    create_bgp_widget(8,35+1*vh,vw);
    bgp_widget.set_state(1);
    create_ospf_widget(8,35+2*vh,vw);
    ospf_widget.set_state(1);
    create_area_widget(8,35+3*vh,vw);
    disable_widgets();
    document.getElementById("topmap").innerHTML="Find routing area and click the init button";
    swinit=1
  }
}

function init(p){
  map.clearOverlays();
  swinit=2;
  document.getElementById("topmap").innerHTML="Loading.";
  loaddata();
}
function loaddata(plat,plon){
    var vbounds=map.getBounds();
    var vlatlon_sw=vbounds.getSouthWest();
    var vlatlon_ne=vbounds.getNorthEast();
    var lat1=vlatlon_sw.lat();
    var lon1=vlatlon_sw.lng();
    var lat2=vlatlon_ne.lat();
    var lon2=vlatlon_ne.lng();
    var vurl='/guifi/routingmap/allsearch/0?lat1='+lat1+'&lon1='+lon1+'&lat2='+lat2+'&lon2='+lon2;
    //var vurl='/guifi/routingmap/allsearch/0?lat1=41.21378767703215&lon1=0.97503662109375&lat2=42.44170109062157&lon2=3.6199951171874996';
    document.getElementById("topmap").innerHTML="Loading...";
    loadXMLDoc(vurl);
}

function build_routing(pdata){
  //alert(pdata);
  adata=eval(pdata);
  document.getElementById("topmap").innerHTML="Building...";

  avars = adata[0];
  anodes = adata[1];
  alinks = adata[2];
  
  narea = 0;
  
  var v=0;
  for (node in anodes){
    v=0;
    if(anodes[node]["bgp"]>0) v=1; 
    if(anodes[node]["ospf"]>0) v=v+2; 
    var markerOptions = {icon:icons[v],clickable:false};
    var vpoint = new GLatLng(anodes[node]["lat"],anodes[node]["lon"]);
    anodes[node]["overlay"]= new GMarker(vpoint,markerOptions);
    map.addOverlay(anodes[node]["overlay"]);
  }
  var polyOptions = {clickable:false};
  for(link in alinks){
    if(alinks[link]["n1"]>0 && alinks[link]["n2"]>0 && alinks[link]["type"]=="wds") {
        var vpoint1 = new GLatLng(anodes[alinks[link]["n1"]]["lat"],anodes[alinks[link]["n1"]]["lon"]);
        var vpoint2 = new GLatLng(anodes[alinks[link]["n2"]]["lat"],anodes[alinks[link]["n2"]]["lon"]);
        if(alinks[link]["routing"]=="BGP") v=1; else v=2;            
        alinks[link]["overlay"] = new GPolyline([vpoint1,vpoint2],colors[v], 2,1,polyOptions);
        map.addOverlay(alinks[link]["overlay"]);
    }
  }
  document.getElementById("topmap").innerHTML="<img src='"+picspath[1]+"'>&nbsp;BGP&nbsp;&nbsp;&nbsp;<img src='"+picspath[3]+"'>&nbsp;BGP/OSPF&nbsp;&nbsp;&nbsp;<img src='"+picspath[2]+"'>&nbsp;OSPF";
  enable_widgets();
}

function enable_widgets(){
  bgp_widget.enable();
  ospf_widget.enable();
  area_widget.enable();
}
function disable_widgets(){
  bgp_widget.disable();
  ospf_widget.disable();
  area_widget.disable();
}

function fbgp(p){
  var v=0;
  for (node in anodes){
    v=0;
    if(anodes[node]["bgp"]>0) v=1; 
    if(anodes[node]["ospf"]>0) v=v+2;
    if(p==0){
        if(v==1 || ospf_widget.state==0){
            anodes[node]["overlay"].hide()
        }
    }else{
        if(v==1 || v==3){
            anodes[node]["overlay"].show();
        }
    }
  }
    for(link in alinks){
      if(alinks[link]["n1"]>0 && alinks[link]["n2"]>0 && alinks[link]["type"]=="wds") {
          if(alinks[link]["routing"]=="BGP"){
            if(p==0){
                alinks[link]["overlay"].hide();
            }else{
                alinks[link]["overlay"].show();
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
        if(v==2 || bgp_widget.state==0){
            anodes[node]["overlay"].hide()
        }
    }else{
        if(v==2 || v==3){
            anodes[node]["overlay"].show();
        }
    }
  }
    for(link in alinks){
      if(alinks[link]["n1"]>0 && alinks[link]["n2"]>0 && alinks[link]["type"]=="wds") {
          if(alinks[link]["routing"]=="OSPF"){
            if(p==0){
                alinks[link]["overlay"].hide();
            }else{
                alinks[link]["overlay"].show();
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
                alinks[link]["overlay"].setStrokeStyle({color:"#0000ff"});
            }else{
                alinks[link]["overlay"].setStrokeStyle({color:colors[2]});
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

function create_init_widget(x, y, width) {
  WTGControl(map, x, y, width, 'Init', 'Init', null,
    function(i) { if (i) init(0); else init(0);},
    function() { init_widget = this; });
}

function create_bgp_widget(x, y, width) {
  WTGControl(map, x, y, width, 'BGP', '<b>BGP</b>', null,
    function(i) { if (i) fbgp(1); else fbgp(0);},
    function() { bgp_widget = this; });
}

function create_ospf_widget(x, y, width) {
  WTGControl(map, x, y, width, 'OSPF', '<b>OSPF</b>', null,
    function(i) { if (i) fospf(1); else fospf(0);},
    function() { ospf_widget = this; });
}

function create_area_widget(x, y, width) {
  WTGControl(map, x, y, width, 'Area', 'Area', null,
    function(i) { if (i) farea(); else farea();},
    function() { area_widget = this; });
}

function WTControl(parent, n_states, enablef, disablef, innerHTMLf, titlef, clickf) {
  this.div        = document.createElement('div');
  parent.appendChild(this.div);
  this.style      = this.div.style;
  this.n_states   = n_states;
  this.enablef    = enablef;
  this.disablef   = disablef;
  this.innerHTMLf = innerHTMLf;
  this.clickf     = clickf;
  this.titlef     = titlef;
  this.state      = 0;
  this.enabled    = 0;
  this.enable();
  this.update();
}

WTControl.prototype.enable = function()  {
  this.enablef(this.div);
  var t = this;
  this.div.onclick = function() { t.onclick(); };
  this.enabled = 1;
  this.set_title();
}

WTControl.prototype.disable = function() {
  this.enabled = 0;
  this.disablef(this.div);
  this.div.onclick = null;
  this.set_title();
}

WTControl.prototype.show = function() {
  this.style.display = '';
}

WTControl.prototype.hide = function() {
  this.style.display = 'none';
}

WTControl.prototype.is_visible = function() {
  return this.style.display != 'none';
}

WTControl.prototype.update = function() {
  this.div.innerHTML = exec_or_value(this.innerHTMLf, this, this.state);
  this.set_title();
}

WTControl.prototype.callback = function() {
  if (this.clickf)
    this.clickf(this.state);
}

WTControl.prototype.onclick = function() {
  this.state++;
  if (this.state >= this.n_states)
    this.state = 0;
  this.update();
  this.callback();
}

WTControl.prototype.clear_title = function() {
  this.div.title = null;
}

WTControl.prototype.set_title = function() {
  if (this.titlef)
    this.div.title = exec_or_value(this.titlef, this);
}

WTControl.prototype.set_state = function(state) {
  this.state = state;
  this.update();
}

WTControl.prototype.trigger = function(state) {
  this.state = state;
  this.update();
  this.callback();
}

WTControl.prototype.reset = function() {
  this.trigger(0);
}

        // for these guys, the action happens when the map calls initialize
function WTGControl(map, x, y, width, text0, text1, titlef, onclick, oncreate) {
  var c = new GControl(0, 0);
  c.initialize = function(map) {
    var w = new WTControl(map.getContainer(), 2,
                  function(d) { s = d.style;
                                s.border          = '1px solid black'
                                s.padding         = '0px 3px';
                                s.backgroundColor = 'white';
                                s.color           = 'black';
                                s.fontSize        = '12px';
                                s.fontFamily      = 'Arial,sans-serif';
                                s.cursor          = 'pointer';
                                s.width           = width + 'px';
                                s.textAlign       = 'center';
                  },
                  function(d) { d.style.color = '#aaaaaa'; }, 
                  function(i) { return i? text1 : text0; },
                  titlef,
                  onclick
            );

    if (oncreate)
      oncreate.call(w);
    return w.div;
  };
  map.addControl(c, new GControlPosition(G_ANCHOR_TOP_RIGHT, new GSize(x, y)));
}

//httprequest
function loadXMLDoc(url){
      http_request=null;
      if (window.XMLHttpRequest){ // code for Firefox, Opera, IE7, etc.
            http_request=new XMLHttpRequest();
      }else if (window.ActiveXObject){ // code for IE5 and IE6
            http_request=new ActiveXObject("Microsoft.XMLHTTP");
      }
      if (http_request!=null){
            http_request.onreadystatechange=state_Change;
            http_request.open("GET",url,true);
            http_request.send(null);
      }else{
            alert("Your browser does not support XMLHTTP.");
      }
}

function state_Change(){
      if (http_request.readyState==4){// 4 = "loaded"
            if (http_request.status==200){// 200 = OK
                  build_routing(http_request.responseText);
            }else{
                  alert("Problem retrieving data:" + http_request.statusText);
            }
      }
}



