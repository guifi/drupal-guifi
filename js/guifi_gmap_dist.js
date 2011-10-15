var map = null;
var markers = Array();
var cloak_overlays = Array();
var cloak_query = false;

if(Drupal.jsEnabled) {
    $(document).ready(function(){
        draw_map();
    }); 
}
	
var lat2;
var lon2;
var marker;
var point; //end point
var node;
var pLine;
var id;
var r;

// REQUEST
function wt_request(url, error_label) {
  return $.ajax({
                        url: url,
                        async: false,
                }).responseText;
}

function wt_request_array_of_lines(url, name) {
  var s = wt_request(url, name);
  if (s == null) return null;

  // pop off last element, which will be blank (if response ends with \n)
  var a = s.split('\n');
  a.pop();
  return a;
}


// IMGOVERLAY CLASS
function ImgOverlay(bounds, url, map) {
  this.bounds  = bounds;
  this.url     = ".." + url;
  this.map     = map;
}

ImgOverlay.prototype = new google.maps.OverlayView();

ImgOverlay.prototype.onAdd = function() {
  // Create the DIV and set some basic attributes.
  var div = document.createElement('DIV');
  div.style.border = "none";
  div.style.borderWidth = "0px";
  div.style.position = "absolute";

  // Create an IMG element and attach it to the DIV.
  var img = document.createElement("img");
  img.src = this.url;
  img.style.width = "100%";
  img.style.height = "100%";

  div.appendChild(img);

  // Set the overlay's div_ property to this DIV
  this.img = img;
  this.div_ = div;

  // Our image is flat against the map, so we add our selves to the MAP_PANE pane,
  // which is at the same z-index as the map itself (i.e., below the marker shadows)
  var panes = this.getPanes();
  panes.overlayLayer.appendChild(div);
}

ImgOverlay.prototype.remove = function() {
  this.img.parentNode.removeChild(this.img);
}

ImgOverlay.prototype.copy = function() {
  return new ImgOverlay(this.bounds, this.url, this.map);
}

ImgOverlay.prototype.draw = function() {
  // Size and position the overlay. We use a southwest and northeast
  // position of the overlay to peg it to the correct position and size.
  // We need to retrieve the projection from this overlay to do this.
  var overlayProjection = this.getProjection();

  // Retrieve the southwest and northeast coordinates of this overlay
  // in latlngs and convert them to pixels coordinates.
  // We'll use these coordinates to resize the DIV.
  var sw = overlayProjection.fromLatLngToDivPixel(this.bounds.getSouthWest());
  var ne = overlayProjection.fromLatLngToDivPixel(this.bounds.getNorthEast());

  // Resize the image's DIV to fit the indicated dimensions.
  var div = this.div_;
  div.style.left = sw.x + 'px';
  div.style.top = ne.y + 'px';
  div.style.width = (ne.x - sw.x) + 'px';
  div.style.height = (sw.y - ne.y) + 'px';
}

function OneDegreeImgOverlay(lat, lon, fudge, url, map) {
  return new ImgOverlay(new google.maps.LatLngBounds(new google.maps.LatLng(lat - fudge, lon - fudge), 
    new google.maps.LatLng(lat + 1 + fudge, lon + 1 + fudge)), url, map);
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
  return new OneDegreeImgOverlay(a[0], a[1], .5/3600, name, map);
}

function show_cloak() {

    if (!cloak_query) {
        handle_query();
        cloak_query = true;
    }

    cloak_overlays.length = 0;
    var a = wt_request_array_of_lines('../list_cloakm/'+id, 'CLOAK');
    if (!a) return;

    for (var i = 0; i < a.length; i++) {
        var b = CloakOverlayByName(a[i]);
        if (b) {
            cloak_overlays.push(b);
            b.setMap(map);
        }
    }
}

function remove_cloak() {
    for (var i = 0; i < cloak_overlays.length; i++) {
        cloak_overlays[i].setMap(null);
    }
    cloak_overlays.length = 0;
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

// CONTOUR
var contourLayer = new google.maps.ImageMapType({
  //contour_overlay = new GTileLayerOverlay(TileLayer(0, 17, 'Contours (C) 2007', 'Michael Kosowsky',
     tileSize: new google.maps.Size(256, 256),
     getTileUrl: function(point, zoom) {
        return 'http://contour.heywhatsthat.com/bin/contour_tiles.cgi?x=' + 
            point.x+'&y='+point.y+'&zoom='+zoom+'&interval='+contour_interval(zoom) +
	        '&color=0000FF30&src=guifi.net';
        },
});

// VISIBILITY CLOAK
var cloakLayer = new google.maps.ImageMapType({
  //cloak_overlay = new GTileLayerOverlay(TileLayer(0, 17, 'Contours (C) 2007', 'Michael Kosowsky',
     tileSize: new google.maps.Size(256, 256),
     getTileUrl: function(point, zoom) {
        return 'http://contour.heywhatsthat.com/bin/contour_tiles.cgi?x=' + 
            point.x+'&y='+point.y+'&zoom='+zoom+'&interval='+contour_interval(zoom) +
	        '&color=0000FF30&src=guifi.net';
        },
});

function contour_interval(z) {
  //                                  0     1     2     3     4     5     6     7    8    9   10   11   12   13  14  15  16  17
  var contour_interval_m_array  = [1000, 1000,  750,  750,  750,  250,  250,  250, 200, 100,  50,  50,  25,  25, 25, 10,  3];

  if (z >= contour_interval_m_array.length)
    return contour_interval_m_array[contour_interval_m_array.length - 1];
  return contour_interval_m_array[z];
}

function TileLayer(min_zoom, max_zoom, copyright_prefix, copyright, url_function) {
  var cc = new GCopyrightCollection(copyright_prefix);
  cc.addCopyright(new GCopyright(2, new google.maps.LatLngBounds(new google.maps.LatLng(-54,-180), new google.maps.LatLng(60,180)), min_zoom, copyright));
  var t = new GTileLayer(cc, min_zoom, max_zoom);
  t.getTileUrl = url_function;
  return t;
}

function draw_map() {

    var divmap = document.getElementById("map");
    var baseURL = document.getElementById("guifi-wms").value;
    node = new google.maps.LatLng(document.getElementById("lat").value, 
			       document.getElementById("lon").value);
    
    var opts = {
        center: node,
        zoom: 13,
        minZoom: 2,
        mapTypeControl: true,
        mapTypeControlOptions: {
            mapTypeIds: [ google.maps.MapTypeId.ROADMAP,
                          google.maps.MapTypeId.SATELLITE, google.maps.MapTypeId.HYBRID, google.maps.MapTypeId.TERRAIN ],
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

    var icon_start_url = document.getElementById("edit-jspath").value + 'marker_start.png';
    var icon_start = new google.maps.MarkerImage(
                         icon_start_url,
                         new google.maps.Size(32, 32),
                         null,
                         new google.maps.Point(16, 16));

    google.maps.event.addListener(map, "click", function(event) {
        initialPosition(event.latLng);
    });

    var node_marker = new google.maps.Marker({ position: node, icon: icon_start, map: map });

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

    // Contour control
    var contourControl = new Control("contour layer", true);
    map.overlayMapTypes.push(null);
    contourControl.div.index = 1;
    map.controls[google.maps.ControlPosition.TOP_RIGHT].push(contourControl.div);

    // Setup the click event listeners
    google.maps.event.addDomListener(contourControl.ui, 'click', function() {
        if (contourControl.enabled) {
            map.overlayMapTypes.setAt(1, null);
            contourControl.disableButton();
        } else {
            // Add the guifi layer
            map.overlayMapTypes.setAt(1, contourLayer);
            contourControl.enable();
        }
    });

    // Visibility cloak control
    var cloakControl = new Control("visibility cloak layer", true);
    map.overlayMapTypes.push(null);
    cloakControl.div.index = 1;
    map.controls[google.maps.ControlPosition.TOP_RIGHT].push(cloakControl.div);

    // Setup the click event listeners: simply set the map to Chicago
    google.maps.event.addDomListener(cloakControl.ui, 'click', function() {
        if (cloakControl.enabled) {
            remove_cloak(); 
            cloakControl.disable();
        } else {
            // Add the guifi layer
            show_cloak();
            cloakControl.enable();
        }
    });

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

function initialPosition(point) {

    for (i in markers) {
        markers[i].setMap(null);
    }
    markers.length = 0;

    var dNode = new google.maps.Marker( { position: point, map: map });
    markers.push(dNode);
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

    pLine = new google.maps.Polyline({ path: [node,point], strokeColor: "#ff0000", strokeWeight: 5, strokeOpacity: .4, map:map });
    markers.push(pLine);

    document.getElementById('tdistance').innerHTML=Math.round(GCDistance_js(node.y,node.x,point.y,point.x)*1000)/1000;
    document.getElementById('tazimut').innerHTML=Math.round(GCAzimuth_js(node.y,node.x,point.y,point.x)*100)/100;
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


