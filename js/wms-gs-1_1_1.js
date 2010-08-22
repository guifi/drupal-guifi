/*
 * Call generic wms service for GoogleMaps v2
 * John Deck, UC Berkeley
 * Inspiration & Code from:
 *	Mike Williams http://www.econym.demon.co.uk/googlemaps2/ V2 Reference & custommap code
 *	Brian Flood http://www.spatialdatalogic.com/cs/blogs/brian_flood/archive/2005/07/11/39.aspx V1 WMS code
 *	Kyle Mulka http://blog.kylemulka.com/?p=287  V1 WMS code modifications
 *      http://search.cpan.org/src/RRWO/GPS-Lowrance-0.31/lib/Geo/Coordinates/MercatorMeters.pm
 *
 * Modified by Chris Holmes, TOPP to work by default with GeoServer.
 * Modified by Eduin Yesid Carrillo Vega to work with any map name. 
 * Modified by Ivan Dubrov for more clean code
 *
 * Note this only works with gmaps v2.36 and above.  http://johndeck.blogspot.com 
 * has scripts
 * that do the same for older gmaps versions - just change from 54004 to 41001.
 *
 * About:
 * This script provides an implementation of GTileLayer that works with WMS
 * services that provide epsg 41001 (Mercator).  This provides a reasonable
 * accuracy on overlays at most zoom levels.  It switches between Mercator
 * and Lat/Long at the myMercZoomLevel variable, defaulting to MERC_ZOOM_DEFAULT
 * of 5.  It also performs the calculation from a GPoint to the appropriate
 * BBOX to pass the WMS.  The overlays could be more accurate, and if you 
 * figure out a way to make them so please contribute information back to
 * http://docs.codehaus.org/display/GEOSDOC/Google+Maps.  There is much
 * information at: 
 * http://cfis.savagexi.com/articles/2006/05/03/google-maps-deconstructed
 * 
 * Use:
 * This script is used by creating a new GTileLayer, setting the required
 * and any desired optional variables, and setting the functions here to 
 * override the appropriate GTileLayer ones.   
 * 
 * At the very least you will need:
 * var myTileLayer = new GWMSTileLayer(map, new GCopyrightCollection(""), 1, 17);
 *     myTileLayer.baseURL='http://yourserver.org/wms?'
 *     myTileLayer.layers='myLayerName';
 *
 * After that you can override the format (format), the level at
 * which the zoom switches (mercZoomLevel), and the style (styles)
 * - be sure to put one style for each layer (both are separated by
 * commas). You can also override the Opacity:
 *     myTileLayer.opacity = 0.69
 *
 * Then you can overlay on google maps with something like:
 * var map = new GMap2(document.getElementById("map"));
 * var tileLayer = new GWMSTileLayer(map, new GCopyrightCollection(""), 1, 17);
 * map.addOverlay(new GTileLayerOverlay(tileLayer));
 */

function GWMSTileLayer(map, copyrights,  minResolution,  maxResolution) {
	GTileLayer.call(this, copyrights, minResolution, maxResolution);

	this.map = map;
		
	// Use PNG by default
	this.format = "image/png";
	
	// Google Maps Zoom level at which we switch from Mercator to Lat/Long.
	this.mercZoomLevel = 15;
	
	this.opacity = 1.0;
}

GWMSTileLayer.prototype = new GTileLayer(new GCopyrightCollection(), 0, 0);

// Helper functions
GWMSTileLayer.prototype.MAGIC_NUMBER = 6356752.3142;
GWMSTileLayer.prototype.WGS84_SEMI_MAJOR_AXIS = 6378137.0;
GWMSTileLayer.prototype.WGS84_ECCENTRICITY = 0.0818191913108718138;

GWMSTileLayer.prototype.dd2MercMetersLng = function(longitude) { 
	return this.WGS84_SEMI_MAJOR_AXIS * (longitude * Math.PI / 180.0);
};

GWMSTileLayer.prototype.dd2MercMetersLat = function(latitude) {
	var rads = latitude * Math.PI / 180.0;
	return this.WGS84_SEMI_MAJOR_AXIS * Math.log(
		Math.tan((rads+Math.PI/2)/2) * 
		Math.pow(((1-this.WGS84_ECCENTRICITY*Math.sin(rads))/(1+this.WGS84_ECCENTRICITY*Math.sin(rads))), this.WGS84_ECCENTRICITY/2));
};

GWMSTileLayer.prototype.isPng = function() {
	return this.format == "image/png";
};

GWMSTileLayer.prototype.getOpacity = function() {
	return this.opacity;
};

GWMSTileLayer.prototype.getTileUrl = function(point, zoom) {
	var mapType = this.map.getCurrentMapType();
	var proj = mapType.getProjection();
	var tileSize = mapType.getTileSize();

	var upperLeftPix = new GPoint(point.x * tileSize, (point.y+1) * tileSize);
	var lowerRightPix = new GPoint((point.x+1) * tileSize, point.y * tileSize);
	var upperLeft = proj.fromPixelToLatLng(upperLeftPix, zoom);
	var lowerRight = proj.fromPixelToLatLng(lowerRightPix, zoom);
	
	if (this.mercZoomLevel != 0 && zoom < this.mercZoomLevel) {
		var boundBox = this.dd2MercMetersLng(upperLeft.lng()) + "," + 
			       this.dd2MercMetersLat(upperLeft.lat()) + "," +
			       this.dd2MercMetersLng(lowerRight.lng()) + "," + 
			       this.dd2MercMetersLat(lowerRight.lat());
		// Change for GeoServer - 41001 is mercator and installed by default.
//		var srs = "EPSG:3395";
	} else {
    	var boundBox = upperLeft.lng() + "," +
    	               upperLeft.lat() + "," +
    	               lowerRight.lng() + "," + 
    	               lowerRight.lat();
    	var srs = "EPSG:4326";
	}
	var url = this.baseURL;
	url += "&REQUEST=GetMap";
	url += "&SERVICE=WMS";
	url += "&VERSION=1.1.1";
	if (this.layers)
	  url += "&LAYERS=" + this.layers;
	if (this.styles)
	  url += "&STYLES=" + this.styles; 
	if (this.sld)
	  url += "&SLD=" + this.sld; 
	url += "&FORMAT=" + this.format;
	url += "&BGCOLOR=0xFFFFFF";
	url += "&TRANSPARENT=TRUE";
	url += "&SRS=" + srs;
	url += "&BBOX=" + boundBox;
	url += "&WIDTH=" + tileSize;
	url += "&HEIGHT=" + tileSize;
	// For debugging purposes
	// document.getElementById("location3").innerHTML = url;
	return url;
};
