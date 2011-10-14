var map = null;

if(Drupal.jsEnabled) {
    $(document).ready(function(){
        draw_map();
    });
}

var init_widget;
var swinit = 0;
var infowindow = new google.maps.InfoWindow();
    
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
        center: new google.maps.LatLng(41.974175, 2.238118),
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

    // Guifi control
    var guifi = new GuifiLayer(map, baseURL);
    map.overlayMapTypes.push(null);
    map.overlayMapTypes.setAt(0, guifi.overlay);

    var guifiControl = new Control("guifi");
    guifiControl.div.index = 1;
    map.controls[google.maps.ControlPosition.TOP_RIGHT].push(guifiControl.div);

    // Setup the click event listeners
    google.maps.event.addDomListener(guifiControl.ui, 'click', function() {
        if (map.overlayMapTypes.getAt(0)) {
            map.overlayMapTypes.setAt(0, null);
            guifiControl.disableButton();
        } else {
            // Add the guifi layer
            map.overlayMapTypes.setAt(0, guifi.overlay);
            guifiControl.enableButton();
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
    
    var pointUpLeft = new google.maps.LatLng(document.getElementById("lat").value, 
			                                 document.getElementById("lon").value);
    var pointDownRight = new google.maps.LatLng(document.getElementById("lat2").value, 
			                                    document.getElementById("lon2").value);
    
    var bounds = new google.maps.LatLngBounds(pointUpLeft, pointDownRight);
    map.setCenter(bounds.getCenter());
    map.fitBounds(bounds);

    var v = document.getElementById("datalinks").value;
    eval("oLinks = " + v);
    var v = document.getElementById("datanodes").value;
    eval("oNodes = " + v);

    //numbered routes and assigns levels to nodes and links, depending on the cost and distance from the main route
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
      var point = new google.maps.LatLng(oNode.lat,oNode.lon);
      oGNodes[nNode] = new google.maps.Marker({ position: point, icon: icons[oSubRouteLevel[oNode.subroute]], map: map });
      createEventNode(oGNodes[nNode],nNode);
    }
    
    var n=0;

    for(nLink in oLinks){
      n++;
      var oLink = oLinks[nLink];
      if(oLink.subroute>0){
            var point1 = new google.maps.LatLng(oNodes[oLink.fromnode].lat,oNodes[oLink.fromnode].lon);
            var point2 = new google.maps.LatLng(oNodes[oLink.tonode].lat,oNodes[oLink.tonode].lon);
            oGLinks[nLink] = new google.maps.Polyline({ path: [point1,point2], 
                                                        strokeColor: colors[oSubRouteLevel[oLink.subroute]], 
                                                        strokeWeight: 5, 
                                                        strokeOpacity: 0.6, 
                                                        clickable: true });
            if(oLink.paint==0){
                  oGLinks[nLink].setMap(null);
            }
            oGLinks[nLink].setMap(map);
            createEventLink(oGLinks[nLink],nLink);
      }
    }
}

function createEventNode(pNode, pNumber) {
  pNode.value = pNumber;
  google.maps.event.addListener(pNode, "mouseover", function() {
      var point = new google.maps.LatLng(oNodes[pNumber].lat,oNodes[pNumber].lon);
      var v="Node: "+oNodes[pNumber]["nodelink"]+"-"+oNodes[pNumber]["nodename"];
      v += "<br>level: "+oSubRouteLevel[oNodes[pNumber].subroute];
      infowindow.setContent(v);
      infowindow.setPosition(point);
      infowindow.open(map);
  });
  google.maps.event.addListener(pNode, "mouseout", function() {
      infowindow.close();
  });
}

function createEventLink(pLink, pNumber) {
  pLink.value = pNumber;
  google.maps.event.addListener(pLink, "click", function(point) {
      var v="from device: "+oLinks[pNumber]["fromdevicename"]+"-"+oLinks[pNumber]["fromipv4"];
      v+="<br>to device: "+oLinks[pNumber]["todevicename"]+"-"+oLinks[pNumber]["toipv4"];
      v+="<br>distance: "+oLinks[pNumber]["distance"]+" Km."+"&nbsp;&nbsp;&nbsp;&nbsp;level: "+oSubRouteLevel[oLinks[pNumber]["subroute"]];
      v+="<br>routes: "+oLinksId[oLinks[pNumber]["idlink"]]["routes"];
      infowindow.setContent(v);
      infowindow.setPosition(point.latLng);
      infowindow.open(map);
  });
}

function printroute(p){
    infowindow.close();
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
      oGNodes[nNode].setMap(null);
    }

    for(nLink in oLinks){
      if(oLinks[nLink]["subroute"]>0){
            oGLinks[nLink].setMap(null);
      }
    }

    if (vroute>0){
      //show route vroute    
      for(nLink in oLinks){
        var oLink = oLinks[nLink];
        if(oLink.route==vroute && oLink.subroute>0){
            oGLinks[nLink].setMap(map);
            if (oGNodes[oLink.fromnode].getMap() == null) oGNodes[oLink.fromnode].setMap(map);
            if (oGNodes[oLink.tonode].getMap() == null) oGNodes[oLink.tonode].setMap(map);
        }    
      }
    } else {
      //show all routes
      for(nNode in oNodes){
        oGNodes[nNode].setMap(map);
      }

      for(nLink in oLinks){
        var oLink = oLinks[nLink];
        if(oLink.subroute>0 && oLink.paint==1) oGLinks[nLink].setMap(map);
      }
    }

    return(false);
}
