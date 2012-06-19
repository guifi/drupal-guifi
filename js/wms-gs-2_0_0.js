var GWMSTileLayer = function() {
    this.WGS84_SEMI_MAJOR_AXIS = 6378137.0;
    this.WGS84_ECCENTRICITY = 0.0818191913108718138;
}

GWMSTileLayer.prototype = { 

    dd2MercMetersLng: function(longitude) {
        return this.WGS84_SEMI_MAJOR_AXIS * (longitude * Math.PI / 180.0);
    },

    dd2MercMetersLat: function(latitude) {
        var rads = latitude * Math.PI / 180.0;
        return this.WGS84_SEMI_MAJOR_AXIS * Math.log(
          Math.tan((rads+Math.PI/2)/2) *
          Math.pow(((1-this.WGS84_ECCENTRICITY*Math.sin(rads))/(1+this.WGS84_ECCENTRICITY*Math.sin(rads))), this.WGS84_ECCENTRICITY/2));
    },
}

var GuifiLayer = function(map, url, layers) {

    this.map = map;
    this.tileSize = new google.maps.Size(256, 256);

    if (typeof url === "undefined") {
        this.baseURL = url;
    } else {
        this.baseURL = "http://guifi.net/cgi-bin/mapserv?map=/home/guifi/maps.guifi.net/guifimaps/GMap.map";
    }
    if (typeof layers === "undefined") {
        this.layers="Nodes,Links";
    } else {
        this.layers=layers;
    }
     
    this.overlay = new google.maps.ImageMapType( {

        name: "guifi.net",
        alt: "guifi.net WMS Image Layer",
        maxZoom: 18,

        tileSize: this.tileSize,
        baseURL: this.baseURL,
        map: this.map,
        layers: this.layers,
        format: "image/png",
        mercZoomLevel: 0,

        getTileUrl: function(point, zoom) {

            var url = this.baseURL;
            var proj = this.map.getProjection();
            var tileSize = this.tileSize.width;
            var layer = new GWMSTileLayer();
            var zfactor=Math.pow(2,zoom);

            var upperLeftPoint = new google.maps.Point(point.x * tileSize/zfactor, (point.y+1) * tileSize/zfactor);
            var lowerRightPoint = new google.maps.Point((point.x+1) * tileSize/zfactor, point.y * tileSize/zfactor);
            var upperLeft = proj.fromPointToLatLng(upperLeftPoint, zoom);
            var lowerRight = proj.fromPointToLatLng(lowerRightPoint, zoom);
            var srs = "EPSG:4326";

            if (this.mercZoomLevel != 0 && zoom < this.mercZoomLevel) {
                var boundBox = layer.dd2MercMetersLng(upperLeft.lng()) + "," +
                               layer.dd2MercMetersLat(upperLeft.lat()) + "," +
                               layer.dd2MercMetersLng(lowerRight.lng()) + "," +
                               layer.dd2MercMetersLat(lowerRight.lat());
            } else {
                var boundBox = upperLeft.lng() + "," + upperLeft.lat() + "," + lowerRight.lng() + "," + lowerRight.lat();
            }

            url += "&REQUEST=GetMap";
            url += "&SERVICE=WMS";
            url += "&VERSION=1.1.1";
            if (this.layers) url += "&LAYERS=" + this.layers;
            url += "&FORMAT=" + this.format;
            url += "&BGCOLOR=0xFFFFFF";
            url += "&TRANSPARENT=TRUE";
            url += "&SRS=" + srs;
            url += "&BBOX=" + boundBox;
            url += "&WIDTH=" + this.tileSize.width;
            url += "&HEIGHT=" + this.tileSize.height;

            return url;
        }
    });

}

var openStreet = new google.maps.ImageMapType({
      getTileUrl: function(ll, z) {
              var X = ll.x % (1 << z);  // wrap
              return "http://alzina.act.uji.es/tiles/" + z + "/" + X + "/" + ll.y + ".png";
      },
      tileSize: new google.maps.Size(256, 256),
      isPng: true,
      maxZoom: 18,
      name: "OSM",
      alt: "OpenStreetMap"
});

var copyrightNode;

function initCopyrights() {
    
    // Create div for showing copyrights.
    copyrightNode = document.createElement('div');
    copyrightNode.id = 'copyright-control';
    copyrightNode.style.fontSize = '11px';
    copyrightNode.style.fontFamily = 'Arial, sans-serif';
    copyrightNode.style.margin = '0 2px 2px 0';
    copyrightNode.style.whiteSpace = 'nowrap';
    copyrightNode.index = 0;
    map.controls[google.maps.ControlPosition.BOTTOM_RIGHT].push(copyrightNode);
    
    google.maps.event.addListener(map, 'idle', updateCopyrights);
    google.maps.event.addListener(map, 'maptypeid_changed', updateCopyrights);
}

function updateCopyrights() {
    var notice = '&copy; OpenStreetMap contributors, CC-BY-SA';
    if (map.getMapTypeId() == "osm") {
    	copyrightNode.innerHTML = notice;
    } else {
    	copyrightNode.innerHTML = "";
    }
}


function Control(name, disabled, loading_icon, size) {
    this.div = document.createElement('DIV');
    this.enabled = !disabled;
    this.blocked = false;
    this.name = name;
    this.loading_icon = loading_icon;

    // Set CSS styles for the DIV containing the control
    // Setting padding to 5 px will offset the control
    // from the edge of the map
    this.div.style.padding = '5px';
    this.div.style.paddingLeft = '0px';
    this.div.style.paddingRight = '5px';

    // Set CSS for the control border
    this.ui = document.createElement('DIV');
    this.ui.style.backgroundColor = '#708dce';
    this.ui.style.borderStyle = 'solid';
    this.ui.style.borderColor= '#3071ad';
    this.ui.style.borderWidth = '1px';
    this.ui.style.cursor = 'pointer';
    this.ui.style.textAlign = 'center';
    this.ui.style.boxShadow = '2px 2px 3px #222';
    this.ui.style.borderRadius = '1px';

    if (size) {
        this.ui.style.width = size + "px";
    }

    this.ui.title = name;
    this.div.appendChild(this.ui);

    // Set CSS for the control interior
    this.text = document.createElement('DIV');
    this.text.style.fontFamily = 'Arial,sans-serif';
    this.text.style.fontSize = '12px';
    this.text.style.color = 'white';
    this.text.style.paddingLeft = '10px';
    this.text.style.paddingRight = '10px';
    this.text.style.paddingTop = '2px';
    this.text.style.paddingBottom = '2px';

    if (loading_icon) {
        this.imgid = this.name.replace(/ /g, "") + "-loading";
        this.text.innerHTML = '<img id="' + this.imgid + '" style="display: none; vertical-align: middle;" src="/sites/all/modules/guifi/icons/loading.gif" /> ' + name;
    } else {
        this.text.innerHTML = name;
    }

    this.ui.appendChild(this.text);

    if (disabled) {
        this.ui.style.backgroundColor = 'white';
        this.ui.style.borderColor= '#a9bbdf';
        this.text.style.color = 'black';
    }
}

Control.prototype = {

    enable: function(text) {
            if (this.loading_icon) {
                $("img#" + this.name.replace(/ /g, "") + "-loading").hide();
            }

            if (text) {
                this.text.innerHTML = text;
            }

            this.enabled = true;
            this.ui.style.backgroundColor = '#708dce';
            this.ui.style.borderColor= '#708dce';
            this.text.style.color = 'white';
    },

    loading: function() {
            $("#" + this.imgid).show();
    },

    disable: function(text) {
            if (this.loading_icon) {
                $("img#" + this.name.replace(/ /g, "") + "-loading").hide();
            }

            if (text) {
                this.text.innerHTML = text;
            }

            this.enabled = false;
            this.ui.style.backgroundColor = 'white';
            this.ui.style.borderColor= '#a9bbdf';
            this.text.style.color = 'black';
    },

    block: function() {
            if (this.loading_icon) {
                $("img#" + this.name.replace(/ /g, "") + "-loading").hide();
            }
            this.blocked = true;
            this.ui.style.backgroundColor = 'white';
            this.ui.style.borderColor= '#a9bbdf';
            this.text.style.color = 'grey';
    },

    unblock: function() {
            if (this.loading_icon) {
                $("img#" + this.name.replace(/ /g, "") + "-loading").hide();
            }
            this.blocked = false;
            this.ui.style.backgroundColor = 'white';
            this.ui.style.borderColor= '#a9bbdf';
            this.text.style.color = 'black';
    },
}


function PanelControl(opts) {
    var self = this;
    var defaultopts = { startminimized : true, extrahtml : '' };
    function MergeRecursive(obj1, obj2) {
        "use strict";
        var p;
        for (p in obj2) {
            if (obj2.hasOwnProperty(p)) {
                try {
                    if (obj2[p].constructor === Object) {
                        obj1[p] = new MergeRecursive(obj1[p], obj2[p]);
                    } else {
                        obj1[p] = obj2[p];
                    }
                } catch (e) {
                    obj1[p] = obj2[p];
                }
            }
        }
        return obj1;
    }
    this.opts = new MergeRecursive(defaultopts, opts);
    this.minimized = this.opts.startminimized;
    this.div = document.createElement('div');
    this.div.style.padding = '5px';
    this.div.style.paddingRight = '0px';

    // general panel styling
    this.panel = document.createElement('div');
    this.panel.style.padding = '10px';
    this.panel.style.paddingRight = '15px';
    this.panel.style.backgroundColor = 'white';
    this.panel.style.opacity = 0.95;
    this.panel.style.filter = 'alpha(opacity = ' + 95 + ')';
    this.panel.style.borderStyle = 'solid';
    this.panel.style.borderWidth = '1px';
    this.panel.style.borderColor = '#ddd';
    this.panel.style.borderTopLeftRadius = '10px';
    this.panel.style.borderBottomLeftRadius = '10px';
    this.panel.style.boxShadow = '0 2px 4px rgba(0, 0, 0, 0.4)';

    // maximize/minimize button
    this.mbutton = document.createElement('div');
    this.mbutton.style.zIndex = '1';
    this.mbutton.style.cursor = 'pointer';
    this.mbutton.style.backgroundColor = 'white';
    this.mbutton.style.borderStyle = 'solid';
    this.mbutton.style.borderRadius = '3px';
    this.mbutton.style.borderColor = '#888';
    this.mbutton.style.borderWidth = '1px';
    this.mbutton.style.boxShadow = '0px 2px 4px rgba(0,0,0,0.4)';
    this.mbutton.style.position = 'absolute';
    this.mbutton.style.right = '0px';
    this.mbutton.style.top = '10px';
    this.mbutton.style.textAlign = 'center';
    this.mbutton.style.width = '15px';
    this.mbutton.style.height = '15px';
    this.mbutton.mbuttontext = document.createElement('span'); //TODO: replace with image
    this.mbutton.mbuttontext.style.lineHeight = '15px';
    this.mbutton.mbuttontext.style.fontSize = '15px';
    this.mbutton.mbuttontext.style.fontWeight = 'bold';
    this.mbutton.mbuttontext.style.color = '#888';
    this.mbutton.mbuttontext.style.display = 'block';
    this.mbutton.mbuttontext.style.verticalAlign = 'middle';
    this.mbutton.mbuttontext.style.position = 'relative';
    this.mbutton.appendChild(this.mbutton.mbuttontext);    
    var minstatechanged = function () {
        self.panel.style.display = self.minimized ? 'none' : 'block';
        self.mbutton.mbuttontext.innerHTML = self.minimized ? '+' : '-';
        self.mbutton.title = self.minimized ? 'Maximiza el panel' : 'Minimiza el panel';
        self.mbutton.style.borderBottomLeftRadius = self.minimized ? '7px' : '3px';
        self.mbutton.style.borderTopLeftRadius = self.minimized ? '7px' : '3px';
        self.mbutton.style.borderBottomRightRadius = self.minimized ? '0px' : '3px';
        self.mbutton.style.borderTopRightRadius = self.minimized ? '0px' : '3px';
        self.mbutton.style.borderRightWidth = self.minimized ? '0px' : '1px';
    }
    this.toggleminimized = function () {
        self.minimized = !self.minimized;
        minstatechanged();
    };
    this.mbutton.onclick = this.toggleminimized;
    this.mbutton.onkeydown = this.toggleminimized; // for touch devices
    minstatechanged();
    this.div.appendChild(this.mbutton);

    this.inputs = [];

    // parse opts
    var forms = this.opts.forms;
    for (var form in forms) {
        if (forms.hasOwnProperty(form)) {
            var f = forms[form];
            if (f.hasOwnProperty('name')) {
                var h = document.createElement('h1');
                h.style.fontSize = '120%';
                h.style.fontWeight = 'bold';
                h.innerHTML = f.name;
                if (f.hasOwnProperty('tooltip')) h.title = f.tooltip;
                this.panel.appendChild(h);
            }
            if (f.hasOwnProperty('list')) {
                var formul = document.createElement('form');
                formul.method = 'get';
                formul.action = '#';
                formul.id = form;
                formul.style.padding = '0px';
                formul.style.margin = '0px';
                var p = document.createElement('p');
                var l = f.list;
                for (var elem in l) {
                    if (l.hasOwnProperty(elem)) {
                        var i = document.createElement('input');
                        var input = l[elem];
                        if (input.hasOwnProperty('default') && input['default']) {
                            i.checked = 'checked';
                        }
                        if (input.hasOwnProperty('disabled') && input['disabled']) {
                            i.disabled = 'disabled';
                        }
                        i.style.verticalAlign = 'bottom';
                        i.style.margin = '1px 1px 1px 5px';
                        i.value = elem;
                        i.id = 'guifimap_' + elem;
                        if (f.hasOwnProperty('type')) {
                            i.type = f.type;
                            if (f.type == 'radio') {
                                i.name = form;
                                if (i.checked) {
                                    this.panel[form] = elem;
                                }
                            } else {
                                i.name = elem;
                                if (typeof this.panel[form] === "undefined") { this.panel[form] = []; }
                                if (f.type == 'checkbox') {
                                    this.panel[form][elem] = (i.checked) ? true : false;
                                } else {
                                    i.value = input.hasOwnProperty('defaultvalue') ? input['defaultvalue'] : '';
                                    this.panel[form][elem] = i.value;
                                }
                            }
                            // the first action is to store the value somewhere
                            google.maps.event.addDomListener(i, 'click', function () {
                                switch(this.type) {
                                    case 'checkbox':
                                        self.panel[this.form.id][this.name] = this.checked ? true : false;
                                        break;
                                    case 'radio':
                                        self.panel[this.name] = this.value;
                                        break;
                                    default:
                                        self.panel[this.form.id][this.name] = this.value;
                                } 
                            });
                            // add extra event handlers
                            if (input.hasOwnProperty('events')) {
                                for (var e in input.events) {
                                    if (input.events.hasOwnProperty(e)) {
                                        google.maps.event.addDomListener(i, e, input.events[e]);
                                    }
                                }
                            }
                            i.panel = this.panel;
                            this.inputs[elem] = i;
                            p.appendChild(i);
                        }
                        var la = document.createElement('label');
                        la.htmlFor = i.id;
                        la.style.verticalAlign = 'bottom';
                        la.style.marginLeft = '5px';
                        if (input.hasOwnProperty('tooltip')) {
                            la.title = input.tooltip;
                        }
                        if (input.hasOwnProperty('name')) {
                            la.innerHTML = input.name;
                        }
                        p.appendChild(la);
                        p.appendChild(document.createElement('br'));
                    }
                }
                formul.appendChild(p);
                this.panel.appendChild(formul);
            }
        }
    }
    var extrahtml = document.createElement('div');
    extrahtml.innerHTML = this.opts.extrahtml;
    this.panel.appendChild(extrahtml);
    
    this.div.appendChild(this.panel);
}
