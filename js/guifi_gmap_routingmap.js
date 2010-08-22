var map = null;


if(Drupal.jsEnabled) {
	  $(document).ready(function(){
        xz();
	    }); 
	}

var init_widget;
var swinit;
    
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
    
    for(var i=1;i<=10;i++){
      icons[i] = new GIcon();
      icons[i].image = document.getElementById("edit-jspath").value+'marker_traceroute_icon' + i + '.png';
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
    create_init_widget(8,35,34);
    document.getElementById("topmap").innerHTML="Find a supernode ospf area and click the init button";
    swinit=1
  }
}

function init(p){
  map.clearOverlays();
  GEvent.clearListeners(map,"click");
  GEvent.addListener(map,"click", function(overlay, latlng) {     
    if (latlng) { 
      init_search(latlng);
    }
  });
  swinit=1;
  document.getElementById("topmap").innerHTML="Click on the initial node";
}
function init_search(platlng){
  if(swinit==1){
    document.getElementById("topmap").innerHTML="Searching";
    GEvent.clearListeners(map,"click");
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
    return;
  }else{
    document.getElementById("topmap").innerHTML="You've selected the supernode " + vnodeinit + ". Drawing....";
  }

  anodes = adata[1];
  alinks = adata[2];
  anets = adata[3];
  aznets = adata[4];
  agnets = adata[5];
  
  for (node in anodes){
    var markerOptions = {icon:icons[1],clickable:false};
    var vpoint = new GLatLng(anodes[node]["lat"],anodes[node]["lon"]);
    var vnode= new GMarker(vpoint,markerOptions);
    map.addOverlay(vnode);
  }
  var polyOptions = {clickable:false};
  for(link in alinks){
    var vpoint1 = new GLatLng(anodes[alinks[link]["nid1"]]["lat"],anodes[alinks[link]["nid1"]]["lon"]);
    var vpoint2 = new GLatLng(anodes[alinks[link]["nid2"]]["lat"],anodes[alinks[link]["nid2"]]["lon"]);
    var vlink = new GPolyline([vpoint1,vpoint2],"#ff0000", 2,1,polyOptions);
    map.addOverlay(vlink);
  }
  var output = "ultimate aggregation<br>";
  for(gnet in agnets){
    output += agnets[gnet]["netid"] + "/" + agnets[gnet]["maskbits"]+"&nbsp;&nbsp;" + agnets[gnet]["zone"]+"<br>";
  }
  output += "<br>aggregate subnets<br>";
  for(net in anets){
    output += anets[net]["netid"] + "/" + anets[net]["maskbits"]+"<br>";
  }
  output += "<br>zone networks<br>";
  for(znet in aznets){
    output += aznets[znet]["netid"] + "/" + aznets[znet]["maskbits"]+"&nbsp;&nbsp;broadcast:"+aznets[znet]["broadcast"]+"&nbsp;&nbsp;zone:"+aznets[znet]["zid"]+"&nbsp;"+aznets[znet]["znick"]+"<br>";
  }
  document.getElementById("topmap").innerHTML="Completed";
  document.getElementById("bottommap").innerHTML=output;
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
  WTGControl(map, x, y, width, '<b>Init</b>', '<b>Init</b>', null,
    function(i) { if (i) init(0); else init(0);},
    function() { init_widget = this; });
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





    /*/numbered routes and assigns levels to nodes and links, depending on the cost and distance from the main route
    //mark links repeated
    var nLevel = 1;
    nRoute = 0;
    var sw = 0;
    var nSubRoute = 0;
    for(nLink in oLinks){
      var oLink = oLinks[nLink];
      if(oLinksId[oLink.idlink]==undefined){
            if(oLink.route!=nRoute || sw==0){
                  sw = 1;
                  nLevel = 1;
                  nSubRoute++;
                  nRoute=oLink.route;
                  if(oNodes[oLink.fromnode].subroute == undefined){
                        oNodes[oLink.fromnode].subroute = nSubRoute;
                        oNodes[oLink.fromnode].levelend = nLevel;
                  }else{
                        nLevel=oNodes[oLink.fromnode].levelend+1;
                        oNodes[oLink.fromnode].levelend=nLevel;
                  }
            }
            if(oNodes[oLink.tonode].subroute == undefined){
                  oNodes[oLink.tonode].subroute = nSubRoute;
                  oNodes[oLink.tonode].levelend = nLevel;
                  oLink.subroute = nSubRoute;
            }else if(oLink.fromnode != oLink.tonode){
                  if(nLevel<=oNodes[oLink.tonode].levelend){
                        nLevel = oNodes[oLink.tonode].levelend+1;
                  }
                  oNodes[oLink.tonode].levelend = nLevel;
                  sw = 0;
                  oLink.subroute = nSubRoute;
            }else{
                  oLink.subroute=0;
            }
            if (oLink.subroute==0){
                  oLink.paint=0
            }else{
                  oLink.paint=1
            }
            oSubRouteLevel[nSubRoute] = nLevel;
            oLinksId[oLink.idlink]= new Array;
            oLinksId[oLink.idlink]["nlink"] = nLink;
            oLinksId[oLink.idlink]["routes"]=""+oLink.route;
      }else{
            oLink.subroute=oLinks[oLinksId[oLink.idlink]["nlink"]].subroute;
            oLink.paint=0;
            oLinksId[oLink.idlink]["routes"]+=","+oLink.route;
      }
    }
    
    //Draw nodes and links
    document.getElementById("edit-formmap2").value=0;
    nRouteActual=0;
    for(nNode in oNodes){
      var oNode=oNodes[nNode];
      var point = new GLatLng(oNode.lat,oNode.lon);
      oGNodes[nNode] = new GMarker(point,icons[oSubRouteLevel[oNode.subroute]]);
      map.addOverlay(oGNodes[nNode]);
      createEventNode(oGNodes[nNode],nNode);
    }
    var polyOptions = {clickable:true};
    var n=0;
    for(nLink in oLinks){
      n++;
      var oLink = oLinks[nLink];
      if(oLink.subroute>0){
            var point1 = new GLatLng(oNodes[oLink.fromnode].lat,oNodes[oLink.fromnode].lon);
            var point2 = new GLatLng(oNodes[oLink.tonode].lat,oNodes[oLink.tonode].lon);
            oGLinks[nLink] = new GPolyline([point1,point2],colors[oSubRouteLevel[oLink.subroute]], 5,0.6,polyOptions);
            if(oLink.paint==0){
                  oGLinks[nLink].hide();
            }
            map.addOverlay(oGLinks[nLink]);
            createEventLink(oGLinks[nLink],nLink);
      }
    }
    
  }
}
    */
/*
function createEventNode(pNode, pNumber) {
  pNode.value = pNumber;
  GEvent.addListener(pNode, "mouseover", function() {
      var point = new GLatLng(oNodes[pNumber].lat,oNodes[pNumber].lon);
      var v="Node: "+oNodes[pNumber]["nodelink"]+"-"+oNodes[pNumber]["nodename"];
      v+="<br>level: "+oSubRouteLevel[oNodes[pNumber].subroute];
      map.openInfoWindowHtml(point,v);
      //pNode.openInfoWindowHtml(v);
  });
  GEvent.addListener(pNode, "mouseout", function() {
      map.closeInfoWindow();
  });
}
function createEventLink(pLink, pNumber) {
  pLink.value = pNumber;
  GEvent.addListener(pLink, "click", function(point) {
      var v="from device: "+oLinks[pNumber]["fromdevicename"]+"-"+oLinks[pNumber]["fromipv4"];
      v+="<br>to device: "+oLinks[pNumber]["todevicename"]+"-"+oLinks[pNumber]["toipv4"];
      v+="<br>distance: "+oLinks[pNumber]["distance"]+" Km."+"&nbsp;&nbsp;&nbsp;&nbsp;level: "+oSubRouteLevel[oLinks[pNumber]["subroute"]];
      v+="<br>routes: "+oLinksId[oLinks[pNumber]["idlink"]]["routes"];
      map.openInfoWindowHtml(point,v);
  });
}



function printroute(p){
    map.closeInfoWindow();
    var vroute=document.getElementById("edit-formmap2").value;
    if (p==-1){
      if (vroute>0) vroute--;
    }else if(p==1){  
      if (vroute<nRoute) vroute++;
    }else vroute=0;
    document.getElementById("edit-formmap2").value=vroute;
    nRouteActual=vroute;
    //hide nodes and links    
    for(nNode in oNodes) {
      oGNodes[nNode].hide();
    }
    for(nLink in oLinks){
      if(oLinks[nLink]["subroute"]>0){
            oGLinks[nLink].hide()
      }
    }
    if (vroute>0){
      //show route vroute    
      for(nLink in oLinks){
        var oLink = oLinks[nLink];
        if(oLink.route==vroute && oLink.subroute>0){
            oGLinks[nLink].show();
            if (oGNodes[oLink.fromnode].isHidden()) oGNodes[oLink.fromnode].show()
            if (oGNodes[oLink.tonode].isHidden()) oGNodes[oLink.tonode].show()
        }    
      }
    }else{
      //show all routes

      for(nNode in oNodes){
        oGNodes[nNode].show();
      }
      for(nLink in oLinks){
        var oLink = oLinks[nLink];
        if(oLink.subroute>0 && oLink.paint==1) oGLinks[nLink].show();
      }
    }
    return(false);
}
*/

