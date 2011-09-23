/*
 * Created on 1/08/2009 by Eduard
 *
 * functions for growth map
 */
var map = null;


if(Drupal.jsEnabled) {
    $(document).ready(function(){
        draw_map();
    }); 
}

var http_request;
var adata;  //carga el json de la web en el array
var anodes; //array de los nodos
var alinks; //array de los links
var aitems; // array de nodos y links ordenados por fecha
var aobjects = new Array; // array de objectes a pintar
var icon;
var icon2;
var ndisplay=0;
var total_objects=0;
var hinterval;
var swinit=0;
var swrun=0;
var ddate = new Date();
var d1 = new String();
var d2 = new String();
var vstamp = 0;
var cero= new String("0");
var stamp = 0;
var stampend = 0;
var swbusy = 0;
var nobj=0;
var supportsCanvas = false;
var img;
var img2;
var vv1;
var vv2;
var vv3;
var numnodes;
var numlinks;
var canvassupernodes;
var canvasbackbone;
var canvasnodes;
var canvaslink; 
var objsupernodes;
var objbackbone;
var objnodes;
var objlinks; 
var zoom;
var div2;
var init_widget;
var play_widget;
var stop_widget;
var clean_widget;
var fast_widget;
var normal_widget;
var slow_widget;
var navmapcontrol;
var nfast = 10;
var nnormal = 35;
var nslow = 100;
var speed = nfast;;

function draw_map() {

    var divmap = document.getElementById("map");
    var baseURL = document.getElementById("edit-guifi-wms").value;

    opts = {
        center: new google.maps.LatLng(41.83, 2.30),
        zoom: 9,
        minZoom: 2,
        mapTypeControl: true,
        mapTypeControlOptions: {
            mapTypeIds: [ google.maps.MapTypeId.ROADMAP,
                          google.maps.MapTypeId.SATELLITE,
                          google.maps.MapTypeId.HYBRID,
                          google.maps.MapTypeId.TERRAIN ],
        },
        mapTypeId: google.maps.MapTypeId.ROADMAP,
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

    // Add the guifi layer
    var guifi = new GuifiLayer(map);
    map.overlayMapTypes.insertAt(0, guifi.overlay);


    var icon_url = document.getElementById("edit-jspath").value + 'marker_traceroute_icon1.png';
    icon = new google.maps.MarkerImage(
                 icon_NE_url,
                 new google.maps.Size(10, 10),
                 null,
                 new google.maps.Point(5, 5));
    
    var icon2_url = document.getElementById("edit-jspath").value + 'marker_traceroute_icon9.png';
    icon2 = new google.maps.MarkerImage(
                 icon_NE_url,
                 new google.maps.Size(8, 8),
                 null,
                 new google.maps.Point(4, 4));
    
    img = new Image();
    img.src = document.getElementById("edit-jspath").value+'marker_traceroute_icon1.png';
    img2 = new Image();
    img2.src = document.getElementById("edit-jspath").value+'marker_traceroute_icon9.png';


    var nini=35;
    var ninc=22
    create_init_widget(8,nini+(ninc*0),34);
    create_play_widget(8,nini+(ninc*1),34);
    create_clean_widget(8,nini+(ninc*2),34);
    create_fast_widget(8,nini+(ninc*3),34);
    create_normal_widget(8,nini+(ninc*4),34);
    create_slow_widget(8,nini+(ninc*5),34);
    init_widget.enable();
    play_widget.disable();
    clean_widget.disable();
    speed = nfast;
    fast_widget.disable();
    normal_widget.enable();
    slow_widget.enable();
      
    document.getElementById("edit-formmap2").value="";
      
    if (document.getElementById('testcanvas').getContext!=undefined){
          supportsCanvas = true;
            
          /**
          * @fileoverview Canvas tools for google maps. The first object is
          * to create arrows on google maps.
          * @author frank2008cn@gmail.com (Xiaoxi Wu)
          * modified for this application
          */
            var Canvas = function(pname,pzorder) {
              this.name_ = pname;
              this.zorder_=pzorder;
            };
           
            Canvas.prototype = new GOverlay();
           
            Canvas.prototype.initialize = function(map) {
                this.map_ = map;
                var div = document.createElement('div');
                div.style.position = "absolute";
                div.innerHTML = '<canvas id="'+this.name_+'" width="800px" height="600px"></canvas>' ;

                map.getPane(G_MAP_MARKER_PANE).appendChild(div);
                this.map_ = map;
                this.div_ = div;
                this.div_.style.zIndex=this.zorder_;
                this.canvas_ = document.getElementById(this.name_);
                this.reset();
              };
           
            Canvas.prototype.remove = function() {
                 this.canvas_.parentNode.removeChild(this.canvas_);
            };
           
            Canvas.prototype.copy = function() {
                  return new Canvas();
            };
           
            Canvas.prototype.redraw = function(change_in_coordinate_system) {
 
            };

            Canvas.prototype.reset = function() {
                  var p = this.map_.fromLatLngToDivPixel(this.map_.getCenter());
                  //var h = parseInt(this.div_.clientHeight);
                  //this.div_.style.width = "800px";
                  //this.div_.style.height = "600px";
                  this.xoffset_=p.x -400;
                  this.yoffset_=p.y -300;
                  this.div_.style.left = this.xoffset_ + "px";
                  this.div_.style.top = this.yoffset_ + "px";

                  var j =new GPoint(p.x +400,p.y - 300);
                  this.latlngne_=this.map_.fromDivPixelToLatLng(j);
                  var k = new GPoint(p.x - 400,p.y + 300);
                  this.latlngsw_=this.map_.fromDivPixelToLatLng(k);
            };

            Canvas.prototype.getne = function() {
                  return(this.latlngne_);
            };
            Canvas.prototype.getsw = function() {
                  return(this.latlngsw_);
            };
            Canvas.prototype.LatLngToCanvasPixel = function(pLatLng) {
                  var p=this.map_.fromLatLngToDivPixel(pLatLng);
                  var v=new GPoint(p.x - this.xoffset_,p.y - this.yoffset_);
                  return(v);
            };
              
            Canvas.prototype.obj = function() {
                  return this.canvas_.getContext('2d');
            };
            //****************************************************
            
            objsupernodes = new Canvas("canvassupernodes",204);
            map.addOverlay(objsupernodes);
            canvassupernodes=objsupernodes.obj();

            objbackbone = new Canvas("canvasbackbone",203);
            map.addOverlay(objbackbone);
            canvasbackbone=objbackbone.obj();

            objnodes = new Canvas("canvasnodes",202);
            map.addOverlay(objnodes);
            canvasnodes=objnodes.obj();

            objlinks = new Canvas("canvaslinks",201);
            map.addOverlay(objlinks);
            canvaslinks=objlinks.obj();

            document.getElementById("footmap").innerHTML="Mode: canvas";
      } else {
            supportsCanvas = false;
            document.getElementById("footmap").innerHTML="Mode: SVG/VLM";
      }
      if(document.getElementById("maprun").value==1){
            init();
      };
}

function build_history(pdata){
      var t=""
      var lat1=0;
      var lon1=0;
      var lat2=0;
      var lon2=0;
      var n=0;
      var item;
      var totitems=0;
      document.getElementById("edit-formmap2").value="Building...";
      
      adata=eval(pdata);
      aitems = adata[0];
      anodes = adata[1];
      alinks = adata[2];

      zoom=zoomnode(map.getZoom());      
      if (!supportsCanvas){
            icon.iconSize = new GSize(zoom, zoom);
            icon.iconAnchor = new GPoint(zoom/2,zoom/2);
            icon2.iconSize = new GSize(zoom/2, zoom/2);
            icon2.iconAnchor = new GPoint(zoom/4,zoom/4);
      }

      var nobj=0;
      for (item in aitems){
            t=item.substr(0,1);
            switch(t){
            case "n":
                  n=item.substr(2);
                  lat1=anodes[n]["lat"];
                  lon1=anodes[n]["lon"];
                  if (supportsCanvas){
                        aobjects[nobj]=new Array("n",aitems[item],anodes[n]["type"],new GLatLng(lat1,lon1));
                  }else{
                        aobjects[nobj] = new Array(create_node(anodes[n]["type"],lat1,lon1),aitems[item],anodes[n]["type"]);
                  }
                  nobj++;
                  break;
            case "l":
                  n=item.substr(2);
                  lat1=anodes[alinks[n]["nid1"]]["lat"];
                  lon1=anodes[alinks[n]["nid1"]]["lon"];
                  lat2=anodes[alinks[n]["nid2"]]["lat"];
                  lon2=anodes[alinks[n]["nid2"]]["lon"];
                  if(lat1!=undefined && lat2!=undefined){
                        if (supportsCanvas){
                              aobjects[nobj]=new Array("l",aitems[item],alinks[n]["type"],new GLatLng(lat1,lon1),new GLatLng(lat2,lon2));
                        }else{
                              aobjects[nobj] = new Array(create_link(alinks[n]["type"],lat1,lon1,lat2,lon2),aitems[item]);
                        }
                        nobj++;
                  }else{
                        alert(lat1+"  "+lon1+"  "+lat2+"   "+lon2+"   "+alinks[n]["nid1"]+"    "+alinks[n]["nid2"]);
                  }
                  break;
            }
      }
      document.getElementById("edit-formmap2").value="GO";
      aitems=null;
      anodes=null;
      alinks=null;
      adata=null;
      swinit=1;
      total_objects=aobjects.length-1;
      init_calendar();
      init_interval();
}

function init_calendar(){
      var n=0;
      var d = ddate.setTime(aobjects[0][1]*1000);
      while(true){
            if (n<total_objects){
                  ddate.setTime(aobjects[n][1]*1000);
                  if (ddate.getFullYear()>=2004){
                        d=ddate;
                        if(aobjects[n][2]==2){
                              d=ddate;
                              break;
                        }
                  }
                  n++;
            }else{
                  break;
            }
      }
      stamp=d.getTime()-86400000;
      stampend=aobjects[total_objects][1]*1000+86400000;
      ndisplay=0;
      numnodes=0;
      numlinks=0;
}

      

function init_interval(){
      swbusy=0;
      swrun=1;
      hinterval = setInterval(calendar,speed);
}
function stop_interval(){
      swrun=0;
      clearInterval(hinterval);
}

function calendar(){
      stamp=stamp+86400000;
      if (stamp<=stampend){
            ddate.setTime(stamp);
            d1=String(ddate.getDate());
            d2=String(ddate.getMonth() + 1);
            if (supportsCanvas){
                  vv3=cero.substr(0,2-d1.length)+d1 +"/"+cero.substr(0,2-d2.length)+d2+ "/"+ ddate.getFullYear()+
                  "   nodes: "+numnodes;
            }else{
                  vv3=cero.substr(0,2-d1.length)+d1 +"/"+cero.substr(0,2-d2.length)+d2+ "/"+ ddate.getFullYear()
            }
            document.getElementById("edit-formmap2").value=vv3;
      }else if(swbusy==0){
            stop_interval();
            if (supportsCanvas){
                  vv3="Total nodes: "+numnodes;
            }else{
                  vv3="The End";
            }
            document.getElementById("edit-formmap2").value=vv3;
            play_widget.set_state(0);
      }
      if(swbusy==0){
            display_nodes();
      }
}

function zoomnode(pzoom){
      var v=(pzoom-9)+4;
      if (v<4){
            v=4;
      }else if(v>10){
            v=10;
      }
      return v;
}

      
function display_nodes(){
      swbusy=1;
      vstamp=Math.floor(stamp/1000);
      while(ndisplay <= total_objects && aobjects[ndisplay][1] <= vstamp){
            if (supportsCanvas){
                  if (aobjects[ndisplay][0]=="n"){
                        numnodes++;
                        vv1=objsupernodes.LatLngToCanvasPixel(aobjects[ndisplay][3]);
                        if(aobjects[ndisplay][2]==2){
                              canvassupernodes.drawImage(img,vv1.x-(zoom/2),vv1.y-(zoom/2),zoom,zoom);
                        }else{
                              canvasnodes.drawImage(img2,vv1.x-((zoom-2)/2),vv1.y-((zoom-2)/2),zoom-2,zoom-2);
                        }
                  }else if (aobjects[ndisplay][0]=="l"){
                        numlinks++;
                        vv1=objbackbone.LatLngToCanvasPixel(aobjects[ndisplay][3]);
                        vv2=objbackbone.LatLngToCanvasPixel(aobjects[ndisplay][4]);
                        if(aobjects[ndisplay][2]==2){
                              canvasbackbone.lineWidth = 2;
                              canvasbackbone.strokeStyle  = "#00FF00";
                              canvasbackbone.beginPath();
                              canvasbackbone.moveTo(vv1.x,vv1.y);
                              canvasbackbone.lineTo(vv2.x,vv2.y);
                              canvasbackbone.stroke();
                        }else{
                              canvaslinks.lineWidth = 1;
                              canvaslinks.strokeStyle  = "#03FFF6";  //FBF801 
                              canvaslinks.beginPath();
                              canvaslinks.moveTo(vv1.x,vv1.y);
                              canvaslinks.lineTo(vv2.x,vv2.y);
                              canvaslinks.stroke();
                        }
                  }
            }else{
                  aobjects[ndisplay][0].show();
            }
            ndisplay++;
      }
      swbusy=0;
}

function replay(){
      if (supportsCanvas){
            canvassupernodes.clearRect(0,0,800,600);
            canvasbackbone.clearRect(0,0,800,600);
            canvasnodes.clearRect(0,0,800,600);
            canvaslinks.clearRect(0,0,800,600);
      }else{
            for(var i=0;i<=total_objects;i++){
                  aobjects[i][0].hide();
            }
      }
      zoom=zoomnode(map.getZoom());
      init_calendar();
      init_interval();
}



function create_node(ptype,plat,plon){
      if (ptype==2) var markerOptions = {icon:icon,clickable:false,zIndexProcess:node_zindex2};
      else var markerOptions = {icon:icon2,clickable:false,zIndexProcess:node_zindex1};
      var vpoint = new GLatLng(plat,plon);
      var vnode= new GMarker(vpoint,markerOptions);
      map.addOverlay(vnode);
      vnode.hide();
      return vnode;
}
function create_link(ptype,plat1,plon1,plat2,plon2){
      if (ptype==2) var polyOptions = {clickable:false,zIndexProcess:link_zindex2};
      else var polyOptions = {clickable:false,zIndexProcess:link_zindex1};
      var vpoint1 = new GLatLng(plat1,plon1);
      var vpoint2 = new GLatLng(plat2,plon2);
      if (ptype==2) var vlink = new GPolyline([vpoint1,vpoint2],"#00ff00", 2,1,polyOptions);
      else var vlink = new GPolyline([vpoint1,vpoint2],"#03FFF6", 1,1,polyOptions);
      vlink.hide();
      map.addOverlay(vlink);
      return vlink;
}
function node_zindex2(){
      return 204;
}
function node_zindex1(){
      return 204;
}
function link_zindex2(){
      return 100;
}
function link_zindex1(){
      return 100;
}
function loaddata(){
            document.getElementById("edit-formmap2").value="Loading.";
            if(supportsCanvas){
                  objsupernodes.reset();
                  objbackbone.reset()
                  objnodes.reset()
                  objlinks.reset()
                  var vlatlon_sw=objsupernodes.getsw();
                  var vlatlon_ne=objsupernodes.getne();
            }else{
                  var vbounds=map.getBounds();
                  var vlatlon_sw=vbounds.getSouthWest();
                  var vlatlon_ne=vbounds.getNorthEast();
            }
            var lat1=vlatlon_sw.lat();
            var lon1=vlatlon_sw.lng();
            var lat2=vlatlon_ne.lat();
            var lon2=vlatlon_ne.lng();
            document.getElementById("edit-formmap2").value="Loading...";
            var vurl='/guifi/cnml/0/growthmap?lat1='+lat1+'&lon1='+lon1+'&lat2='+lat2+'&lon2='+lon2
            loadXMLDoc(vurl);
}

function play(){
      stop_interval();
      if (swinit==2){
            swinit=1;
            init_interval();
      }else if(swinit==1){
            replay();
      }
}
function pause(){
      if(swinit==1){
            stop_interval();
            swinit=2;
      }
}
function init(){
      stop_interval();
      document.getElementById("edit-formmap2").value="";
      aobjects.length=0;
      init_widget.disable();
      play_widget.enable();
      clean_widget.enable();
      play_widget.set_state(1);
      if(supportsCanvas){
            canvassupernodes.clearRect(0,0,800,600);
            canvasbackbone.clearRect(0,0,800,600);
            canvasnodes.clearRect(0,0,800,600);
            canvaslinks.clearRect(0,0,800,600);
            map.disableDoubleClickZoom();
            map.removeControl(navmapcontrol);
            map.disableScrollWheelZoom();
      }else{
            map.clearOverlays();
      }
      swinit=0;
      loaddata();
}

function clean(){
      stop_interval();
      document.getElementById("edit-formmap2").value="";
      init_widget.enable();
      play_widget.disable();
      clean_widget.disable();
      play_widget.set_state(1);
      if (supportsCanvas){
            canvassupernodes.clearRect(0,0,800,600);
            canvasbackbone.clearRect(0,0,800,600);
            canvasnodes.clearRect(0,0,800,600);
            canvaslinks.clearRect(0,0,800,600);
            map.enableDoubleClickZoom();
            if (map.getSize().height >= 300) map.addControl(navmapcontrol = new GLargeMapControl());
            else map.addControl(navmapcontrol = new GSmallMapControl());
            map.enableScrollWheelZoom();
      }else{
            map.clearOverlays();
      }
}

function fast(){
      speed = nfast;
      fast_widget.disable();
      normal_widget.enable();
      slow_widget.enable();
      if(swrun==1){
            stop_interval();
            init_interval();
      }
}
function normal(){
      speed = nnormal;
      fast_widget.enable();
      normal_widget.disable();
      slow_widget.enable();
      if(swrun==1){
            stop_interval();
            init_interval();
      }
}
function slow(){
      speed = nslow;
      fast_widget.enable();
      normal_widget.enable();
      slow_widget.disable();
      if(swrun==1){
            stop_interval();
            init_interval();
      }
}

// CONTROLS

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
    function(i) { if (i) init(); else init();},
    function() { init_widget = this; });
}
function create_play_widget(x, y, width) {
  WTGControl(map, x, y, width, '<b>Play</b>', '<b>Pause</b>', null,
    function(i) { if (i) play(); else pause(); },
    function() { play_widget = this; });
}
function create_clean_widget(x, y, width) {
  WTGControl(map, x, y, width, '<b>Clean</b>', '<b>Clean</b>', null,
    function(i) { if (i) clean(); else clean();},
    function() { clean_widget = this; });
}
function create_fast_widget(x, y, width) {
  WTGControl(map, x, y, width, '<b>Fast</b>', '<b>Fast</b>', null,
    function(i) { if (i) fast(); else fast();},
    function() { fast_widget = this; });
}
function create_normal_widget(x, y, width) {
  WTGControl(map, x, y, width, '<b>Nor.</b>', '<b>Nor.</b>', null,
    function(i) { if (i) normal(); else normal();},
    function() { normal_widget = this; });
}
function create_slow_widget(x, y, width) {
  WTGControl(map, x, y, width, '<b>Slow</b>', '<b>Slow</b>', null,
    function(i) { if (i) slow(); else slow();},
    function() { slow_widget = this; });
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
                  build_history(http_request.responseText);
            }else{
                  alert("Problem retrieving data:" + http_request.statusText);
            }
      }
}

