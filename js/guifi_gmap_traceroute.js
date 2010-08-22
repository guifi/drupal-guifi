var map = null;


if(Drupal.jsEnabled) {
	  $(document).ready(function(){
        xz();
	    }); 
	}

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

function xz() 
{
  if (GBrowserIsCompatible()) {
    map=new GMap2(document.getElementById("map"));
    if (map.getSize().height >= 300)
      map.addControl(new GLargeMapControl());
    else
      map.addControl(new GSmallMapControl());
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
    
    var pointUpLeft = new GLatLng(document.getElementById("lat").value, 
			 document.getElementById("lon").value);
    var pointDownRight = new GLatLng(document.getElementById("lat2").value, 
			 document.getElementById("lon2").value);
    
    var bounds = new GLatLngBounds(pointUpLeft,pointDownRight);
    map.setCenter(bounds.getCenter(), map.getBoundsZoomLevel(bounds));

    var v = document.getElementById("datalinks").value;
    eval("oLinks = "+v);
    var v = document.getElementById("datanodes").value;
    eval("oNodes = "+v);

    map.setMapType(myCustomMapType);

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


