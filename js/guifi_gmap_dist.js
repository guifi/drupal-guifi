var map = null;
var oGMark = null; //GMarker

if(Drupal.jsEnabled) {
    $(document).ready(function(){
        draw_map();
    }); 
}
	
var icon_start;
var oNode;
var newNode; //initial point
var lat2;
var lon2;
var marker;
var point; //end point
var pLine;

var id;
var r;


// REQUEST

function parse_response(request) {
  var o = {ok: 0,
           response: null,
           http_status_code: null,
           http_status_message: null,
           app_status_code: null,
           app_status_message: null};

  o.http_status_code    = request.status;
  o.http_status_message = request.statusText;
  if (request.status != 200)
    return o;

  var s;
  var a;
  try { s = request.getResponseHeader('X-App-Status'); } catch(e) {}
  if (s && (a = s.match(/^(\d\d\d)\s+(.+)/))) {
    o.app_status_code    = a[1];
    o.app_status_message = a[2];
    if (a[0] != 200)
      return o;
  }

  o.ok             = 1;
  o.response       = request.responseText;

  return o;
}

function _wt_request(url, error_label) {
  var request  = GXmlHttp.create();
//alert(url);
  request.open("GET", url, 0);
  request.send(null);
  var o = parse_response(request);
  if (!o.ok && error_label)
    alert(error_label + ': ' +
                (  o.status_message?     o.status_message
                 : o.app_status_message? o.app_status_message
                 : 'request error'));
  return o;
}

function wt_request(url, error_label) {
  var o = _wt_request(url, error_label);
  return o.response;
}

function wt_request_array_of_lines(url, name) {
  var s = wt_request(url, name);
  if (s == null)
    return null;
        // pop off last element, which will be blank (if response ends with \n)
  var a = s.split('\n');
  a.pop();
  return a;
}


// IMGOVERLAY CLASS

function ImgOverlay(bounds, url) {
  this.bounds  = bounds;
  this.url     = ".."+url;
}

function derive(newclass, base) {
  function xxx() {}
  xxx.prototype      = base.prototype;
  newclass.prototype = new xxx();
}

derive(ImgOverlay, google.maps.OverlayView);

ImgOverlay.prototype.initialize = function(map) {
  this.map            = map;
  this.img            = document.createElement("img");
  this.img.src        = this.url;
  this.style          = this.img.style;
  this.style.position = "absolute";

  // Our image is flat against the map, so we add our selves to the MAP_PANE pane,
  // which is at the same z-index as the map itself (i.e., below the marker shadows)
  map.getPane(G_MAP_MAP_PANE).appendChild(this.img);
}

ImgOverlay.prototype.remove = function() {
  this.img.parentNode.removeChild(this.img);
}

ImgOverlay.prototype.copy = function() {
  return new ImgOverlay(this.bounds, this.url);
}

ImgOverlay.prototype.redraw = function(change_in_coordinate_system) {
  if (!change_in_coordinate_system)
    return;

  var sw   = this.map.fromLatLngToDivPixel(this.bounds.getSouthWest());
  var ne   = this.map.fromLatLngToDivPixel(this.bounds.getNorthEast());
  var s    = this.style;
  s.width  = (ne.x - sw.x) + "px";
  s.height = (sw.y - ne.y) + "px";
  s.left   = sw.x + "px";
  s.top    = ne.y + "px";
}

function OneDegreeImgOverlay(lat, lon, fudge, url) {
  return new ImgOverlay(new GLatLngBounds(new GLatLng(lat - fudge, lon - fudge), 
    new GLatLng(lat + 1 + fudge, lon + 1 + fudge)), url);
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
function WTGControl(map, x, width, text0, text1, titlef, onclick, oncreate) {
  //var c = new GControl(0, 0);
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
                  null,  // for disable, tried  function(d) { d.style.color = '#606060'; },
                  function(i) { return i? text1 : text0; },
                  titlef,
                  onclick
            );

    if (oncreate)
      oncreate.call(w);
    return w.div;
  };
  map.addControl(c, new GControlPosition(G_ANCHOR_TOP_RIGHT, new GSize(x, 30)));
}


// VISIBILITY CLOAK

function show_alert() {
  alert("Generating visibility cloak. Please wait 1 minute!");
}

var srtm_re = /([NS])(\d\d)([EW])(\d\d\d)\./;

function srtm_latlon(s) {
  var a = s.match(srtm_re);
  if (!a || a.length != 5)
    return null;
  return [ (a[1] == 'N'? 1 : -1) * a[2], (a[3] == 'E'? 1 : -1) * a[4] ];
}

function CloakOverlayByName(name) {
  var a = srtm_latlon(name);
  if (!a)
    return null;
  return new OneDegreeImgOverlay(a[0], a[1], .5/3600, name);
}

var cloak_overlays = [];
var cloak_query = false;

function show_cloak() {
  if (!cloak_query) {
    handle_query();
    cloak_query = true;
    setTimeout(show_alert, 0);
  }
  cloak_overlays = [];
  var a = wt_request_array_of_lines('../list_cloakm/'+id, 'CLOAK');
  if (!a)
    return;
  for (var i = 0; i < a.length; i++) {
    var b = CloakOverlayByName(a[i]);
    if (b) {
    //alert(a[i]);
    
      cloak_overlays.push(b);
      map.addOverlay(b);
    }
  }
}

function remove_cloak() {
  for (var i = 0; i < cloak_overlays.length; i++)
    map.removeOverlay(cloak_overlays[i]);
  cloak_overlays = [];
}

function handle_query() {
  var name = "tst";
  var lat = document.getElementById("lat").value;
  var lon = document.getElementById("lon").value;

  if (lat < -54 || lat >= 71 || (lat > 60 && (lon < -173 || lon >= -139))) {
    alert('Invalid latitude.\nWe currently cover latitude 60N to 54S and most of Alaska.');
    return;
  }

  var elev = document.getElementById("elevation").value;
  id = wt_request('../query/'+lat+'/'+lon+'/'+elev,'QUERY');

  return;
}

var cloak_widget;
function create_visibilitycloak_widget(x, width) {
  WTGControl(map, x, width, 'Visibility&nbsp;cloak', '<b>Visibility&nbsp;cloak</b>', null,
    function(i) { if (i) show_cloak(); else remove_cloak(); },
    function() { cloak_widget = this; }
  );
}


// CONTOUR

var contour_overlay;
var contour_widget;
var contour_listener;
                                 // 0     1     2     3     4     5     6     7    8    9   10   11   12   13  14  15  16  17
var contour_interval_m_array  = [1000, 1000,  750,  750,  750,  250,  250,  250, 200, 100,  50,  50,  25,  25, 25, 10,  3];

function contour_interval(z) {
  if (z >= contour_interval_m_array.length)
    return contour_interval_m_array[contour_interval_m_array.length - 1];
  return contour_interval_m_array[z];
}

function TileLayer(min_zoom, max_zoom, copyright_prefix, copyright, url_function) {
  var cc = new GCopyrightCollection(copyright_prefix);
  cc.addCopyright(new GCopyright(2, new GLatLngBounds(new GLatLng(-54,-180), new GLatLng(60,180)), min_zoom, copyright));
  var t = new GTileLayer(cc, min_zoom, max_zoom);
  t.getTileUrl = url_function;
  return t;
}

function show_contour_overlay() {
  if (contour_overlay)
    map.removeOverlay(contour_overlay);
  contour_overlay = new GTileLayerOverlay(TileLayer(0, 17, 'Contours (C) 2007', 'Michael Kosowsky',
    function(point, zoom) {
      return 'http://contour.heywhatsthat.com/bin/contour_tiles.cgi?x='+ 
        point.x+'&y='+point.y+'&zoom='+zoom+'&interval='+contour_interval(zoom)+
	'&color=0000FF30&src=guifi.net';
     }));
  map.addOverlay(contour_overlay);
}

function create_contour_widget(x, width) {
  WTGControl(map, x, width, 'Contours', '<b>Contours</b>',
    function() {
      return this.state?
                 'contour interval ' + contour_interval(map.getZoom()) + ' m'
               : 'Click to see contours';
    },
    function(i) {
      if (i) {
        show_contour_overlay();
      } else {
        if (contour_overlay)
          map.removeOverlay(contour_overlay);
        contour_overlay = null;
      }
    },
    function() {
      contour_widget = this;
    });

  contour_listener = GEvent.addListener(map, "zoomend", contour_zoom_callback);
}

function contour_zoom_callback(oldz, newz) {
  if (contour_widget)
    contour_widget.set_title();
}

// GUIFI
var layer1;
var myCustomMapTypeNormal;
var myCustomMapTypeSatellite;
var myCustomMapTypePhysical;

var guifi_widget;
function create_guifi_widget(x, width) {
  WTGControl(map, x, width, '<b>Guifi</b>', 'Guifi', null,
    function(i) { if (i) remove_guifi(); else show_guifi(); },
    function() { guifi_widget = this; }
);
}
//

function draw_map() 
{

    var divmap = document.getElementById("map");
    var baseURL = document.getElementById("guifi-wms").value;

    opts = {
        center: new google.maps.LatLng(20.0, -10.0),
        zoom: 13,
        minZoom: 2,
        mapTypeControl: true,
        mapTypeControlOptions: {
            mapTypeIds: [ google.maps.MapTypeId.ROADMAP,
                          google.maps.MapTypeId.SATELLITE, google.maps.MapTypeId.HYBRID ],
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

    // Add the guifi layer
    var guifi = new GuifiMapType(map);
    map.overlayMapTypes.insertAt(0, guifi.overlay);

    var icon_start_url = document.getElementById("edit-jspath").value + 'marker_start.png';
    icon_start = new google.maps.MarkerImage(
                    icon_start_url,
                    new google.maps.Size(32, 32),
                    null,
                    new google.maps.Point(6, 20));

    newNode = new google.maps.LatLng(document.getElementById("lat").value, 
			 document.getElementById("lon").value);
    
    map.setCenter(newNode);
    
    google.maps.event.addListener(map, "click", function(event) {
        initialPosition(event.latLng);
    });

    oNode = new google.maps.Marker({ position: newNode, icon: icon_start, map: map });

    //create_guifi_widget(164, 34);
    //create_visibilitycloak_widget(73, 86);
    //create_contour_widget(7, 59);

    if (document.getElementById("lon2").value != "NA") {
        lon2 = document.getElementById("lon2").value;      
        if (document.getElementById("lat2").value != "NA") {
            lat2 = document.getElementById("lat2").value;
        }
        point = new google.maps.LatLng(lat2,lon2);
        initialPosition(point);
        var bounds = new google.maps.LatLngBounds();
        bounds = pLine.getBounds();
        map.setCenter(bounds.getCenter(),map.getBoundsZoomLevel(bounds)); 
	}
	
}

function initialPosition(ppoint) {
  point=ppoint;  //save point in global var
  oGMark = null;  //init global var
  var dNode = new google.maps.Marker( { position: point, map: map });
  var y = Math.abs(document.getElementById("lat").value - point.y);
  var x = Math.abs(document.getElementById("lon").value - point.x);
  var distance = Math.sqrt(y*y + x*x);
  var curvature = distance > 0.1 ? 1 : 0; // 0.1 a ojimetro son 10Km xD

  document.getElementById("profile").src =
    "http://www.heywhatsthat.com/bin/profile.cgi?"+
    "axes=1&curvature="+curvature+"&metric=1&groundrelative=1&"+
    "src=guifi.net&"+
    "pt0="+document.getElementById("lat").value+","+document.getElementById("lon").value+
    ",ff0000,"+document.getElementById("elevation").value+
    "&pt1="+point.y+","+point.x+
    ",00c000,9";   
  
  for (var i = 0; i < cloak_overlays.length; i++) {
    cloak_overlays[i].setMap(map);
  }

  if (contour_overlay)
    contour_overlay.setMap(map);

  pLine = new google.maps.Polyline({ path: [newNode,point], strokeColor: "#ff0000", strokeWeight: 5, strokeOpacity: .4 });
  dNode.setMap(map);
  pLine.setMap(map);   
  oNode.setMap(map);
  document.getElementById('tdistance').innerHTML=Math.round(GCDistance_js(newNode.y,newNode.x,point.y,point.x)*1000)/1000;
  document.getElementById('tazimut').innerHTML=Math.round(GCAzimuth_js(newNode.y,newNode.x,point.y,point.x)*100)/100;
}

function profileclick(event){
    var oProfile=document.getElementById("profile");
    var pointClic=coord_relativ(event,oProfile);
    //var nLat=parseFloat(document.getElementById("lat").value);
    //var nLon=parseFloat(document.getElementById("lon").value);
    var nLat=newNode.y;
    var nLon=newNode.x;
    var nLat2=point.y;
    var nLon2=point.x;
    var nDistance = GCDistance_js(nLat,nLon,nLat2,nLon2);
    var nNewDistance=(pointClic.x-29)*nDistance/(oProfile.width-29);
    var nAzimut=GCAzimuth_js(nLat,nLon,nLat2,nLon2)
    //alert('Distancia:'+nNewDistance+'  Azimut:'+nAzimut);
    var pointNew=getDestPoint(nLat,nLon,nNewDistance,nAzimut);
    //alert(' inici:'+nLat+'  '+nLon+'    newpoint:'+pointNew.lat+'  '+pointNew.lon+'   final:'+nLat2+'  '+nLon2);
    var pointGMark=new GLatLng(pointNew.lat,pointNew.lon);
    if(oGMark!=null){
        map.removeOverlay(oGMark);
    };
    oGMark=new GMarker(pointGMark);
    map.addOverlay(oGMark);
}

/*
 * Calcula la coordenada relativa de un clic respecte a les coordenades del contenidor
 */
function coord_relativ(event,oProfile){
    if (window.ActiveXObject) {  //for ie
        pos_x = event.offsetX;
        pos_y = event.offsetY;
    } else { //for Firefox
        var top = 0, left = 0;
        var elm = oProfile;
        while (elm) {
            left += elm.offsetLeft;
            top += elm.offsetTop;
            elm = elm.offsetParent;
        }
        pos_x = event.pageX - left;
        pos_y = event.pageY - top;
    }
    return {x:pos_x,y:pos_y}
}

/*
 * Movable Type Scripts
 * calculate destination point given start point, initial bearing (deg) and distance (km)
 * see http://williams.best.vwh.net/avform.htm#LL
 * original modified
 */
function getDestPoint(lat,lon,d,brng) {
  var DE2RA = 0.01745329252;
  var RA2DE = 57.2957795129;
  var R = 6371; // earth's mean radius in km
  var lat1 = lat * DE2RA;
  var lon1 = lon * DE2RA;
  brng = brng * DE2RA;
  var lat2 = Math.asin( Math.sin(lat1)*Math.cos(d/R) + 
                        Math.cos(lat1)*Math.sin(d/R)*Math.cos(brng) );
  var lon2 = lon1 + Math.atan2(Math.sin(brng)*Math.sin(d/R)*Math.cos(lat1), 
                               Math.cos(d/R)-Math.sin(lat1)*Math.sin(lat2));
  lon2 = (lon2+Math.PI)%(2*Math.PI) - Math.PI;  // normalise to -180...+180
  if (isNaN(lat2) || isNaN(lon2)) return null;
  lat2 *= RA2DE;
  lon2 *= RA2DE;
  return {lat:lat2,lon:lon2}
}

/*
 * GeoCalc
 * funcio de php pasada a javascript
 */
function GCDistance_js(pLat1, pLon1, pLat2, pLon2) {  
    var DE2RA = 0.01745329252;
    var AVG_ERAD = 6371.0;
    var nLat1 = pLat1 * DE2RA;
    var nLon1 = pLon1 * DE2RA;
    var nLat2 = pLat2 * DE2RA;
    var nLon2 = pLon2 * DE2RA;
    var d = Math.sin(nLat1)*Math.sin(nLat2) + Math.cos(nLat1)*Math.cos(nLat2)*Math.cos(nLon1 - nLon2);
    return (AVG_ERAD * Math.acos(d));
}

/*
 * GeoCalc
 * funcio de php pasada a javascript
 */
function GCAzimuth_js(plat1, plon1, plat2, plon2) {  //GeoCalc
    var DE2RA = 0.01745329252;
    var RA2DE = 57.2957795129;
    var result = 0.0;
    var ilat1 = Math.floor(0.50 + plat1 * 360000.0);
    var ilat2 = Math.floor(0.50 + plat2 * 360000.0);
    var ilon1 = Math.floor(0.50 + plon1 * 360000.0);
    var ilon2 = Math.floor(0.50 + plon2 * 360000.0);

    var lat1 = plat1 * DE2RA;
    var lon1 = plon1 * DE2RA;
    var lat2 = plat2 * DE2RA;
    var lon2 = plon2 * DE2RA;

    if ((ilat1 == ilat2) && (ilon1 == ilon2)) {
      return result;
    }
    else if (ilat1 == ilat2) {
      if (ilon1 > ilon2)
        result = 90.0;
      else
        result = 270.0;
    }
    else if (ilon1 == ilon2) {
      if (ilat1 > ilat2)
        result = 180.0;
    }
    else {
      var c = Math.acos(Math.sin(lat2)*Math.sin(lat1) + Math.cos(lat2)*Math.cos(lat1)*Math.cos((lon2-lon1)));
      var A = Math.asin(Math.cos(lat2)*Math.sin((lon2-lon1))/Math.sin(c));
      result = (A * RA2DE);


      if ((ilat2 > ilat1) && (ilon2 > ilon1)) {
        result = result;
      }
      else if ((ilat2 < ilat1) && (ilon2 < ilon1)) {
        result = 180.0 - result;
      }
      else if ((ilat2 < ilat1) && (ilon2 > ilon1)) {
        result = 180.0 - result;
      }
      else if ((ilat2 > ilat1) && (ilon2 < ilon1)) {
        result += 360.0;
      }
    }

    return result;
}


