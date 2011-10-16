/*
 * Created on 1/08/2009 by Eduard
 *
 * functions for growth map
 */
var map = null;
var overlay = false;

var initControl = null;
var playControl = null;
var cleanControl = null;
var fastControl = null;
var normalControl = null;
var slowControl = null;

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

    opts = {
        center: new google.maps.LatLng(41.83, 2.30),
        zoom: 9,
        minZoom: 2,
        mapTypeControl: true,
        mapTypeControlOptions: {
            mapTypeIds: [ "osm",
                          google.maps.MapTypeId.ROADMAP,
                          google.maps.MapTypeId.SATELLITE,
                          google.maps.MapTypeId.HYBRID,
                          google.maps.MapTypeId.TERRAIN ],
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
    map.mapTypes.set('osm', openStreet);

    // Add the guifi layer
    var guifi = new GuifiLayer(map);
    //map.overlayMapTypes.insertAt(0, guifi.overlay);

    var guifiControl = new Control("guifi", true);
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

    var icon_url = document.getElementById("edit-jspath").value + 'marker_traceroute_icon1.png';
    icon = new google.maps.MarkerImage(
                 icon_url,
                 new google.maps.Size(10, 10),
                 null,
                 new google.maps.Point(5, 5));
    
    var icon2_url = document.getElementById("edit-jspath").value + 'marker_traceroute_icon9.png';
    icon2 = new google.maps.MarkerImage(
                 icon2_url,
                 new google.maps.Size(8, 8),
                 null,
                 new google.maps.Point(4, 4));
    
    img = new Image();
    img.src = document.getElementById("edit-jspath").value+'marker_traceroute_icon1.png';
    img2 = new Image();
    img2.src = document.getElementById("edit-jspath").value+'marker_traceroute_icon9.png';

    var nini=35;
    var ninc=22;

    // Init control
    initControl = new Control("Init", true, true, 55);
    map.overlayMapTypes.push(null);
    initControl.div.index = 1;
    map.controls[google.maps.ControlPosition.RIGHT_TOP].push(initControl.div);

    // Setup the click event listeners
    google.maps.event.addDomListener(initControl.ui, 'click', function() {
        if (!initControl.blocked) {
            if (initControl.enabled) {
                initControl.disable();
            } else {
                initControl.loading();
                init();
            }
        }
    }); 

    // Play control
    playControl = new Control("Play", true, false, 55);
    map.overlayMapTypes.push(null);
    playControl.div.index = 1;
    map.controls[google.maps.ControlPosition.RIGHT_TOP].push(playControl.div);

    // Setup the click event listeners
    google.maps.event.addDomListener(playControl.ui, 'click', function() {
        if (!playControl.blocked) {
            if (playControl.enabled) {
                playControl.disable();
                pause();
            } else {
                playControl.enable();
                play();
            }
        }
    }); 

    // Clean control
    cleanControl = new Control("Clean", true, false, 55);
    map.overlayMapTypes.push(null);
    cleanControl.div.index = 1;
    map.controls[google.maps.ControlPosition.RIGHT_TOP].push(cleanControl.div);

    // Setup the click event listeners
    google.maps.event.addDomListener(cleanControl.ui, 'click', function() {
        if (!cleanControl.blocked) {
            cleanControl.enable();
            clean();
            cleanControl.disable();
        }
    }); 

    // Fast control
    fastControl = new Control("Fast", true, false, 55);
    map.overlayMapTypes.push(null);
    fastControl.div.index = 1;
    map.controls[google.maps.ControlPosition.RIGHT_TOP].push(fastControl.div);

    // Setup the click event listeners
    google.maps.event.addDomListener(fastControl.ui, 'click', function() {
        if (!fastControl.blocked) {
            if (fastControl.enabled) {
                fastControl.disable();
            } else {
                fastControl.enable();
                fast();
            }
        }
    }); 

    // Nor. control
    normalControl = new Control("Norm.", true, false, 55);
    map.overlayMapTypes.push(null);
    normalControl.div.index = 1;
    map.controls[google.maps.ControlPosition.RIGHT_TOP].push(normalControl.div);

    // Setup the click event listeners
    google.maps.event.addDomListener(normalControl.ui, 'click', function() {
        if (!normalControl.blocked) {
            if (normalControl.enabled) {
                normalControl.disable();
            } else {
                normalControl.enable();
                normal();
            }
        }
    }); 

    // Slow control
    slowControl = new Control("Slow", true, false, 55);
    map.overlayMapTypes.push(null);
    slowControl.div.index = 1;
    map.controls[google.maps.ControlPosition.RIGHT_TOP].push(slowControl.div);

    // Setup the click event listeners
    google.maps.event.addDomListener(slowControl.ui, 'click', function() {
        if (!slowControl.blocked) {
            if (slowControl.enabled) {
                slowControl.disable();
            } else {
                slowControl.enable();
                slow();
            }
        }
    }); 

    playControl.block();
    cleanControl.block();
    speed = nfast;
    fastControl.block();
     
    document.getElementById("edit-formmap2").value="";
      
    if (document.getElementById('testcanvas').getContext!=undefined){
          supportsCanvas = true;

          overlay = new google.maps.OverlayView();
          overlay.draw = function() {};
          overlay.setMap(map);

          /**
          * @fileoverview Canvas tools for google maps. The first object is
          * to create arrows on google maps.
          * @author frank2008cn@gmail.com (Xiaoxi Wu)
          * modified for this application
          */
            var Canvas = function(pname,pzorder) {
                this.name_ = pname;
                this.map_ = map;
                this.zorder_=pzorder;
                this.setMap(map);
            }

            Canvas.prototype = new google.maps.OverlayView();

            Canvas.prototype.onAdd = function() {
                var div = document.createElement('div');
                div.style.position = "absolute";
                div.innerHTML = '<canvas id="'+this.name_+'" width="800px" height="600px"></canvas>' ;
                this.div_ = div;
                this.div_.style.zIndex=this.zorder_;

                var panes = this.getPanes();
                panes.overlayLayer.appendChild(div);

                //this.reset();
            };
           
            Canvas.prototype.draw = function() {
            };

            Canvas.prototype.remove = function() {
                this.div_.parentNode.removeChild(this.div_);
                this.div_ = null;
            };
           
            Canvas.prototype.reset = function() {
                  var p = overlay.getProjection().fromLatLngToDivPixel(this.map_.getCenter());
                  //var h = parseInt(this.div_.clientHeight);
                  //this.div_.style.width = "800px";
                  //this.div_.style.height = "600px";
                  this.xoffset_=p.x -400;
                  this.yoffset_=p.y -300;
                  this.div_.style.left = this.xoffset_ + "px";
                  this.div_.style.top = this.yoffset_ + "px";

                  var j =new google.maps.Point(p.x +400,p.y - 300);
                  this.latlngne_ = overlay.getProjection().fromDivPixelToLatLng(j);
                  var k = new google.maps.Point(p.x - 400,p.y + 300);
                  this.latlngsw_ = overlay.getProjection().fromDivPixelToLatLng(k);
            };

            Canvas.prototype.getne = function() {
                  return(this.latlngne_);
            };

            Canvas.prototype.getsw = function() {
                  return(this.latlngsw_);
            };

            Canvas.prototype.LatLngToCanvasPixel = function(pLatLng) {
                  var p = overlay.getProjection().fromLatLngToDivPixel(pLatLng);
                  var v=new google.maps.Point(p.x - this.xoffset_,p.y - this.yoffset_);
                  return(v);
            };
             
            Canvas.prototype.obj = function() {
                  this.canvas_ = document.getElementById(this.name_);
                  console.log(this.canvas_);
                  return this.canvas_.getContext('2d');
            }
 
            //****************************************************
            
            objsupernodes = new Canvas("canvassupernodes",204);
            objbackbone = new Canvas("canvasbackbone",203);
            objnodes = new Canvas("canvasnodes",202);
            objlinks = new Canvas("canvaslinks",201);
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
            icon.iconSize = new google.maps.Size(zoom, zoom);
            icon.iconAnchor = new google.maps.Point(zoom/2,zoom/2);
            icon2.iconSize = new google.maps.Size(zoom/2, zoom/2);
            icon2.iconAnchor = new google.maps.Point(zoom/4,zoom/4);
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
                        aobjects[nobj]=new Array("n",aitems[item],anodes[n]["type"],new google.maps.LatLng(lat1,lon1));
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
                              aobjects[nobj]=new Array("l",aitems[item],alinks[n]["type"], new google.maps.LatLng(lat1,lon1),new google.maps.LatLng(lat2,lon2));
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
                  aobjects[ndisplay][0].setMap(map);
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
                  aobjects[i][0].setMap(null);
            }
      }
      zoom=zoomnode(map.getZoom());
      init_calendar();
      init_interval();
}



function create_node(ptype,plat,plon){
      var vpoint = new google.maps.LatLng(plat,plon);

      if (ptype == 2) {
        var vnode= new google.maps.Marker( { position: vpoint, icon:icon, clickable:false, zIndexProcess: node_zindex2 });
      } else {
        var vnode= new google.maps.Marker( { position: vpoint, icon:icon2, clickable:false, zIndexProcess: node_zindex1 });
      }

      return vnode;
}

function create_link(ptype,plat1,plon1,plat2,plon2){
      var vpoint1 = new google.maps.LatLng(plat1,plon1);
      var vpoint2 = new google.maps.LatLng(plat2,plon2);
      if (ptype==2) var vlink = new google.maps.Polyline({ path: [vpoint1,vpoint2], 
                                                           strokeColor: "#00ff00",
                                                           strokeWeight: 2,
                                                           strokeOpacity: 1,
                                                           clickable:false,
                                                           zIndexProcess:link_zindex2 });
      else var vlink = new google.maps.Polyline( { path: [vpoint1,vpoint2],
                                                   strokeColor: "#03FFF6",
                                                   strokeWeight: 1,
                                                   strokeOpacity: 1,
                                                   clickable: false,
                                                   zIndexProcess: link_zindex1 } );
      vlink.setMap(map);
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
    var vurl='/guifi/cnml/0/growthmap?lat1='+lat1+'&lon1='+lon1+'&lat2='+lat2+'&lon2='+lon2;

    $.ajax({
             url: vurl,
             success: function(data) {
                 build_history(data);
             }
           });
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
      initControl.block();
      playControl.unblock();
      cleanControl.unblock();
      if(supportsCanvas){
            canvassupernodes = $("#canvassupernodes")[0].getContext('2d');
            canvasbackbone = $("#canvasbackbone")[0].getContext('2d');
            canvasnodes = $("#canvasnodes")[0].getContext('2d');
            canvaslinks = $("#canvaslinks")[0].getContext('2d');
            canvassupernodes.clearRect(0,0,800,600);
            canvasbackbone.clearRect(0,0,800,600);
            canvasnodes.clearRect(0,0,800,600);
            canvaslinks.clearRect(0,0,800,600);
            map.disableDoubleClickZoom = true;
            map.mapTypeControl = false;
            map.scrollwheel = false;
      }
      swinit=0;
      loaddata();
}

function clean(){
      stop_interval();
      document.getElementById("edit-formmap2").value="";
      initControl.unblock();
      playControl.block();
      cleanControl.block();
      if (supportsCanvas){
            canvassupernodes.clearRect(0,0,800,600);
            canvasbackbone.clearRect(0,0,800,600);
            canvasnodes.clearRect(0,0,800,600);
            canvaslinks.clearRect(0,0,800,600);
      }
}

function fast(){
      speed = nfast;
      fastControl.block();
      normalControl.unblock();
      slowControl.unblock();
      if(swrun==1){
            stop_interval();
            init_interval();
      }
}
function normal(){
      speed = nnormal;
      fastControl.unblock();
      normalControl.block();
      slowControl.unblock();
      if(swrun==1){
            stop_interval();
            init_interval();
      }
}
function slow(){
      speed = nslow;
      fastControl.unblock();
      normalControl.unblock();
      slowControl.block();
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

