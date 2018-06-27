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
        this.baseURL = "http://guifimaps.guifi.net/cgi-bin/mapserv?map=/var/www/guifimaps/GMap.map";
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
              return "http://alzina.act.uji.es/" + z + "/" + X + "/" + ll.y + ".png";
      },
      tileSize: new google.maps.Size(256, 256),
      isPng: true,
      maxZoom: 18,
      name: "OSM",
      alt: "OpenStreetMap (servidor propio)"
});

var mapquestosm = new google.maps.ImageMapType({
      getTileUrl: function(ll, z) {
              if (typeof this.counter === "undefined") { this.counter = 0; }
              var X = ll.x % (1 << z);  // wrap
              this.counter++;
              if (this.counter > 4) { this.counter = 1; }
              return "http://otile" + this.counter + ".mqcdn.com/tiles/1.0.0/osm/" + z + "/" + X + "/" + ll.y + ".jpg";
      },
      tileSize: new google.maps.Size(256, 256),
      isPng: false,
      maxZoom: 18,
      name: "MapQuest",
      alt: "MapQuest-OSM"
});

var mapquestopenaerial = new google.maps.ImageMapType({
      getTileUrl: function(ll, z) {
              if (typeof this.counter === "undefined") { this.counter = 0; }
              var X = ll.x % (1 << z);  // wrap
              this.counter++;
              if (this.counter > 4) { this.counter = 1; }
              return "http://oatile" + this.counter + ".mqcdn.com/tiles/1.0.0/sat/" + z + "/" + X + "/" + ll.y + ".jpg";
      },
      tileSize: new google.maps.Size(256, 256),
      isPng: false,
      maxZoom: 11,
      name: "MQ Open Aerial",
      alt: "MapQuest Open Aerial"
});

var opencyclemap = new google.maps.ImageMapType({
      getTileUrl: function(ll, z) {
              var X, chars;
              
              chars = "abcdefghijklmnopqrstuvwxyz"; // only abc needed in this case
              if (typeof this.counter === "undefined") { this.counter = 0; }
              X = ll.x % (1 << z);  // wrap
              this.counter++;
              if (this.counter > 3) { this.counter = 1; }
              
              return "http://" + chars.charAt(this.counter - 1) + ".tile.opencyclemap.org/cycle/" + z + "/" + X + "/" + ll.y + ".png";
      },
      tileSize: new google.maps.Size(256, 256),
      isPng: true,
      maxZoom: 16,
      name: "OpenCycleMap",
      alt: "Open Cycle Map"
});

var opencyclemaptransport = new google.maps.ImageMapType({
      getTileUrl: function(ll, z) {
              var X, chars;

              chars = "abcdefghijklmnopqrstuvwxyz"; // only abc needed in this case
              if (typeof this.counter === "undefined") { this.counter = 0; }
              X = ll.x % (1 << z);  // wrap
              this.counter++;
              if (this.counter > 3) { this.counter = 1; }

              return "http://" + chars.charAt(this.counter - 1) + ".tile2.opencyclemap.org/transport/" + z + "/" + X + "/" + ll.y + ".png";
      },
      tileSize: new google.maps.Size(256, 256),
      isPng: true,
      maxZoom: 18,
      name: "OpenCycleMap Transport",
      alt: "Open Cycle Map Transport (experimental)"
});

var opencyclemaplandscape = new google.maps.ImageMapType({
      getTileUrl: function(ll, z) {
              var X, chars;

              chars = "abcdefghijklmnopqrstuvwxyz"; // only abc needed in this case
              if (typeof this.counter === "undefined") { this.counter = 0; }
              X = ll.x % (1 << z);  // wrap
              this.counter++;
              if (this.counter > 3) { this.counter = 1; }

              return "http://" + chars.charAt(this.counter - 1) + ".tile3.opencyclemap.org/landscape/" + z + "/" + X + "/" + ll.y + ".png";
      },
      tileSize: new google.maps.Size(256, 256),
      isPng: true,
      maxZoom: 18,
      name: "OpenCycleMap Landscape",
      alt: "Open Cycle Map Landscape (experimental)"
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
    copyrightNode.style.opacity = 0.50;
    copyrightNode.style.filter = 'alpha(opacity = ' + 50 + ')';
    copyrightNode.style.backgroundColor = 'white';
    copyrightNode.style.padding = '2px 10px';
    copyrightNode.index = 0;
    map.controls[google.maps.ControlPosition.BOTTOM_RIGHT].push(copyrightNode);
    
    google.maps.event.addListener(map, 'idle', updateCopyrights);
    google.maps.event.addListener(map, 'maptypeid_changed', updateCopyrights);
}

function updateCopyrights() {
    var mq = 'Tiles Courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="http://developer.mapquest.com/content/osm/mq_logo.png">';
    var osm = '&copy; OpenStreetMap contributors, CC-BY-SA';
    var ocm = 'Tiles Courtesy of <a href="http://www.opencyclemap.org" target="_blank">OpenCycleMap</a>';
    switch (map.getMapTypeId()) {
        case "osm":
            copyrightNode.innerHTML = osm;
            break;
        case "mapquestosm":
            copyrightNode.innerHTML = mq + '<br />' + osm;
            break;
        case "mapquestopenaerial":
            copyrightNode.innerHTML = mq + '<br />' +
            'Portions Courtesy NASA/JPL-Caltech and U.S. Depart. of Agriculture, Farm Service Agency';
            break;
        case "opencyclemap":
            copyrightNode.innerHTML = ocm + '<br />' + osm;
            break;
        case "opencyclemaptransport":
            copyrightNode.innerHTML = ocm + '<br />' + osm;
            break;
        case "opencyclemaplandscape":
            copyrightNode.innerHTML = ocm + '<br />' + osm;
            break;
        default:
            copyrightNode.innerHTML = '';
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
    this.mbutton.mbuttontext.style.fontWeight = 'bold';
    this.mbutton.mbuttontext.style.color = '#888';
    this.mbutton.mbuttontext.style.display = 'block';
    this.mbutton.mbuttontext.style.verticalAlign = 'middle';
    this.mbutton.mbuttontext.style.position = 'relative';
    this.mbutton.appendChild(this.mbutton.mbuttontext);    
    var minstatechanged = function () {
        self.panel.style.display = self.minimized ? 'none' : 'block';
        self.mbutton.mbuttontext.innerHTML = self.minimized ? '+' : '-';
        self.mbutton.mbuttontext.style.fontSize = self.minimized ? '14px' : '15px';
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
                            i.disabled = true;
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
                        if (i.disabled) { la.style.color = '#aaa'; }
                        if (input.hasOwnProperty('tooltip')) {
                            la.title = input.tooltip;
                        }
                        if (input.hasOwnProperty('name')) {
                            la.innerHTML = input.name;
                        }
                        p.appendChild(la);
                        if (input.hasOwnProperty('extrahtml')) {
                            var extra = document.createElement('span');
                            extra.innerHTML = input.extrahtml;
                            p.appendChild(extra);
                        }
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


/* ---------------------------------------------------------------------- */
// Simple event class
var Event = function (sender) {
    "use strict";
    this.sender = sender;
    this.listeners = [];
};
Event.prototype = {
    attach: function (listener) {
        "use strict";
        this.listeners.push(listener);
    },
    notify: function (args) {
        "use strict";
        var i, len;
        len = this.listeners.length;
        for (i = 0; i < len; i += 1) {
            this.listeners[i](this.sender, args);
        }
    }
};
/* ---------------------------------------------------------------------- */
var LayerSwitcher = function (opts) {
    "use strict";
    var MenuData, DefaultView, CondensedView, loadData;

    // model for the menu
    MenuData = function (p) {
        var self;

        self = this;
        this.parent = null;
        this.list = [];
        // events/signals emitted from here
        this.onSelected = new Event(this);
        this.onChildSelected = new Event(this);
        this.onNewChild = new Event(this);
        this.onParented = new Event(this);
        this.onChildRemoved = new Event(this);

        if (typeof p !== "undefined") {
            if (p.hasOwnProperty('name')) { this.name = p.name; }
            if (p.hasOwnProperty('title')) { this.title = p.title; }
            if (p.hasOwnProperty('enabled')) {
                if (p.enabled === "false") {
                    this.enabled = false;
                } else if (p.enabled === "true") {
                    this.enabled = true;
                }
            }
            if (p.hasOwnProperty('type')) {
                this.type = p.type;
                switch (this.type) {
                case "radio":
                    this.selectedChild = null;
                case "menu":
                case "check":
                    if (p.hasOwnProperty('selected')) {
                        if (p.selected === "true") {
                            this.selected = true;
                        } else if (p.selected === "false") {
                            this.selected = false;
                        }
                    } else { this.selected = false; }
                    break;
                case "menulist":
                case "radiolist":
                case "checklist":
                    this.selectedChild = null;
                    break;
                case "extrahtml":
                    if (p.hasOwnProperty('extrahtml')) {
                        this.extrahtml = p.extrahtml;
                    }
                    break;
                }
            }
        }

    };
    MenuData.prototype = {
        attachChild: function (child) {
            // check if child is compatible with parent
            if ((!this.hasOwnProperty('type')) || (!child.hasOwnProperty('type'))) { return false; }
            if (child.type !== "extrahtml") {
                if ((this.type === "menulist") && (child.type !== "menu")) { return false; }
                if ((this.type === "radiolist") && (child.type !== "radio")) { return false; }
                if ((this.type === "checklist") && (child.type !== "check")) { return false; }
            }

            this.list.push(child);
            child.parent = this;
            child.onSelected.attach(function (sender, args) {
                sender.parent.onChildSelected.notify({ "who": sender, "state": args.state });
            });
            if ((child.hasOwnProperty('selected')) && (this.hasOwnProperty('selectedChild')) && (child.selected)) {
                if (this.selectedChild !== null) {
                    this.selectedChild.selected = false;
                }
                this.selectedChild = child;
            }
            this.onNewChild.notify({ "parent" : this, "child" : child });
            child.onParented.notify({ "parent" : this, "child" : child });
            return true;
        },
        removeChildByName: function (name) {
            var i, len, child;
            child = null;
            len = this.list.length;
            for (i = 0; i < len; i += 1) {
                if ((this.list[i].hasOwnProperty('name')) && (this.list[i].name === name)) {
                    child = this.list.splice(i, 1);
                    this.onChildRemoved.notify({ "parent": this, "child": child });
                    break;
                }
            }
            return child;
        },
        select: function (state) {
            var currentItem;

            if (typeof state === "undefined") { state = true; }
            if (this.hasOwnProperty('type') && ((this.hasOwnProperty('enabled') && (this.enabled === true)) || (!this.hasOwnProperty('enabled')))) {
                // TODO: comprobar que la selección es válida
                // informar al padre ???
                // actualizar parent.selectedChild ??
                switch (this.type) {
                case "check":
                    this.selected = state;
                    this.selectAllChildren(state);
                    this.onSelected.notify({ "who": this, "state": state });
                    break;
                case "menu":
                case "radio":
                    if ((state === false) && (this.selected === true)) {
                        // don't allow direct unselection
                        // to unselect a radio button, select a sibling
                        break;
                    } else if ((state === true) && (this.selected === false)) {
                        currentItem = this.parent.selectedChild;
                        if (currentItem !== null) {
                            // unselect previous selected item (sibling)
                            currentItem.selected = false;
                        }
                        this.parent.selectedChild = this;
                        // now that it's marked as unselected, and parent is updated
                        // fire the event on the sibling that has been unselected
                        if (currentItem !== null) {
                            currentItem.onSelected.notify({ "who": currentItem, "state": false });
                        }
                        this.selected = state;
                        this.onSelected.notify({ "who": this, "state": state });
                    }
                    break;
                }
            }
            return this.hasOwnProperty('selected') ? this.selected : false;
        },
        unselect: function () {
            this.select(false);
        },
        selectAllChildren: function (state) {
            var i, len;
            len = this.list.length;
            for (i = 0; i < len; i += 1) {
                this.list[i].select(state);
            }
        },
        // fires the onSelected event for it and all children unconditionally or only for selected items
        notifySelections: function (restrict_to_selected) {
            var i, len, onlyselected;

            if (typeof restrict_to_selected === "undefined") {
                onlyselected = false;
            } else {
                onlyselected = restrict_to_selected;
            }

            if ((this.hasOwnProperty('selected') && this.selected) || !onlyselected || (!this.hasOwnProperty('selected'))) {
                this.onSelected.notify({ "who": this, "state": this.selected });
                len = this.list.length;
                for (i = 0; i < len; i += 1) {
                    this.list[i].notifySelections(onlyselected);
                }
            }
        },
        // fires the onSelected event for all children unconditionally or only for selected items
        notifyChildrenSelections: function (restrict_to_selected) {
            var i, len, onlyselected;

            if (typeof restrict_to_selected === "undefined") {
                onlyselected = false;
            } else {
                onlyselected = restrict_to_selected;
            }

            if ((this.hasOwnProperty('selected') && this.selected) || !onlyselected || (!this.hasOwnProperty('selected'))) {
                len = this.list.length;
                for (i = 0; i < len; i += 1) {
                    this.list[i].notifySelections(onlyselected);
                }
            }
        },
        toString: function () {
            var name;
            name = this.hasOwnProperty('name') ? this.name : "";
            return '[MenuData "' + name + '"]';
        }
    };

    // ************************
    // VIEWS:
    // ************************
    // default view: a dropdown for each category
    DefaultView = function (initial_elem) {
        this.div = document.createElement('div');
        this.build(this.div, initial_elem);
    };
    DefaultView.prototype = {
        toggle: function (state) {
            var enable, div;
            //div = this.div;
            div = this.div.parentNode;
            
            if (typeof state === "undefined") {
                enable = (div.style.display === "none") ? true : false;
            } else {
                enable = state;
            }
            if (enable) {
                div.style.display = "block";
            } else {
                div.style.display = "none";
            }
        },
        // parent = HTML Element, elem = model element
        build: function (parent, elem) {
            var i, len,
                e, e2, e3, e4, // elements
                view,
                textWidth, makeUnselectable, highlight, descends;

            // returns true if child descends from parent
            descends = function (parent, child) {
                var e;
                if (typeof parent === "undefined") { return false; }
                if (typeof child === "undefined") { return false; }
                e = child;
                while ((typeof e !== "undefined") && (e !== null) && (e.hasOwnProperty('parentNode')) && (e !== parent)) {
                    e = e.parentNode;
                }
                return (e === parent);
            };
            // computes the (maximum) width of a string in pixels
            textWidth = function (text, fontsize, fontfamily, fontweight) {
                var div, width;
                div = document.createElement('div');
                div.style.position = "absolute";
                div.style.visibility = "hidden";
                div.style.width = "auto";
                div.style.height = "auto";
                div.style.whitespace = "nowrap";
                div.style.fontSize = (typeof fontsize !== "undefined") ? fontsize : "13px";
                div.style.fontFamily = (typeof fontfamily !== "undefined") ? fontfamily : "Arial,sans-serif";
                div.style.fontWeight = (typeof fontweight !== "undefined") ? fontweight : "bold"; // we choose bold for giving the maximum width to characters
                div.innerHTML = text;
                document.body.appendChild(div);
                width = div.clientWidth + 1;
                document.body.removeChild(div);
                return width + "px";
            };
            // makes an element text unselectable
            makeUnselectable = function (element) {
                element.unselectable = true;
                element.style.MozUserSelect = "none";
                element.style.WebkitUserSelect = "none";
                element.style.MozUserSelect = "none";
                element.style.msUserSelect = "none";
                element.style.userSelect = "none";
            };
            highlight = function (element, state) {
                var active;
                active = (typeof state === "undefined") ? true : state;

                if (active === true) {
                    if (!element.hasOwnProperty('oldbg')) {
                        element.oldbg = element.style.backgroundColor;
                        element.style.backgroundColor = "#eee";
                    }
                    element.style.backgroundImage = "-moz-linear-gradient(to bottom, #ffffff 0%,#e6e6e6 100%)";
                    element.style.backgroundImage = "-webkit-linear-gradient(top, #ffffff,#e6e6e6)";
                    element.style.backgroundImage = "-o-linear-gradient(top, #ffffff 0%,#e6e6e6 100%)";
                    element.style.backgroundImage = "-ms-linear-gradient(top, #ffffff 0%,#e6e6e6 100%)";
                    element.style.backgroundImage = "linear-gradient(to bottom, #ffffff 0%,#e6e6e6 100%)";
                    element.style.filter = "progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#e6e6e6',GradientType=0 )";
                } else if (active === false) {
                    if (element.hasOwnProperty('oldbg')) {
                        element.style.backgroundColor = element.oldbg;
                    }
                    element.style.backgroundImage = "none";
                }
            };

            view = this;
            e = null;
            if (elem.hasOwnProperty('type')) {
                switch (elem.type) {
                case "menulist":
                    e = document.createElement('div');
                    //e.style.margin = "5px"; // moved to main div
                    e.style.cursor = "pointer";
                    parent.appendChild(e);
                    break;
                case "menu":
                    // wrapper for the dropdown menu & menu button
                    e3 = document.createElement('div');
                    e3.style.cssFloat = "left";
                    e3.style.direction = "rtl";

                    // menu button
                    e2 = document.createElement('div');
                    if (elem.hasOwnProperty('name')) { e2.innerHTML = elem.name; }
                    if (elem.hasOwnProperty('title')) { e2.title = elem.title; }
                    if (elem.hasOwnProperty('selected')) {
                        e2.style.fontWeight = elem.selected ? "bold" : "normal";
                        e2.style.color = elem.selected ? "#000" : "#333333";
                    }
                    if (parent.hasChildNodes()) {
                        e2.style.borderStyle = "solid solid solid none";
                        e2.style.borderWidth = "1px 1px 1px 0px";
                        e2.style.borderColor = "rgb(113, 123, 135)";
                    } else {
                        e2.style.border = "1px solid rgb(113, 123, 135)";
                    }
                    // Make the text inside buttons unselectable (not in W3C standards)
                    makeUnselectable(e2);
                    // various stuff
                    e2.style.direction = "ltr";
                    e2.style.padding = "1px 6px";
                    e2.style.backgroundColor = "#fff";
                    e2.style.textAlign = "center";
                    e2.style.fontFamily = "Arial,sans-serif";
                    e2.style.fontSize = "13px";
                    e2.style.boxShadow = "0px 2px 4px rgba(0, 0, 0, 0.4)";
                    e2.style.position = "relative";
                    e2.style.overflow = "hidden";
                    e2.style.zIndex = "20";
                    // menu buttons render with fixed width (=string width with bold font)
                    e2.style.minWidth = textWidth(e2.innerHTML,
                                                  e2.style.fontSize,
                                                  e2.style.fontFamily,
                                                  "bold");


                    // little wrapper for the dropdown menulist
                    e4 = document.createElement('div');
                    e4.style.position = "relative";
                    
                    // dropdown menu
                    e = document.createElement('div');
                    e.style.border = "1px solid #000";
                    e.style.backgroundColor = "#fff";
                    e.style.position = "absolute";
                    //e.style.top = "18px";
                    e.style.zIndex = "10";
                    e.style.padding = "5px 10px";
                    e.style.boxShadow = "0px 2px 4px rgba(0, 0, 0, 0.4)";
                    e.style.borderRadius = "3px";
                    e.style.cursor = "default";
                    e.style.direction = "ltr";
                    makeUnselectable(e);
                    e.style.display = "none"; // initially invisible

                    // EVENTS
                    // e3 = wrapper, e2 = button, e = dropdown menu
                    e2.onmouseover = function (event) {
                        this.mo = true;
                        highlight(this);
                        this.style.color = "#000";
                        if (this.style.fontWeight === "bold") {
                            if (elem.list.length > 0) {
                                e.style.display = "block";
                                if ((e.offsetParent.offsetParent.offsetParent.offsetWidth - (e.offsetParent.offsetParent.offsetLeft + e.offsetWidth + e.offsetParent.offsetLeft)) < 0) {
                                    e.style.left = (e.offsetParent.offsetParent.offsetParent.offsetWidth - (e.offsetParent.offsetParent.offsetLeft + e.offsetWidth + e.offsetParent.offsetLeft)) + "px";
                                }
                            }
                        }
                    };
                    e2.onmouseout = function (event) {
                        var other, self, to;
                        self = this;
                        other = e;

                        if (!event) { event = window.event; }
                        if (event.toElement) {
                            to = event.toElement;
                        } else if (event.relatedTarget) {
                            to = event.relatedTarget;
                        } else {
                            to = null;
                        }
                        if ((to !== null) && (to !== this) && (!descends(this, to))) {
                            this.mo = false;
                            setTimeout(function () {
                                if (self.mo == false && other.mo == false) {
                                    other.style.display = "none";
                                }
                            }, 1000);
                        }

                        highlight(this, false);
                        this.style.color = (this.style.fontWeight === "bold") ? "#000" : "#333333";
                    };
                    e2.onclick = function () {
                        var i, len;

                        elem.select(true);         // select the button we're clicking
                        if (elem.list.length > 0) {
                            e.style.display = "block"; // display the dropdown menu
                            if ((e.offsetParent.offsetParent.offsetParent.offsetWidth - (e.offsetParent.offsetParent.offsetLeft + e.offsetWidth + e.offsetParent.offsetLeft)) < 0) {
                                e.style.left = (e.offsetParent.offsetParent.offsetParent.offsetWidth - (e.offsetParent.offsetParent.offsetLeft + e.offsetWidth + e.offsetParent.offsetLeft)) + "px";
                            }
                        }

                        // hide the other dropdown menus immediately
                        if (this.parentNode.parentNode) {
                            len = this.parentNode.parentNode.childNodes.length;
                            for (i = 0; i < len; i += 1) {
                                if (this.parentNode.parentNode.childNodes[i] !== this.parentNode) {
                                    this.parentNode.parentNode.childNodes[i].lastChild.lastChild.style.display = "none";
                                }
                            }
                        }
                    };
                    e2.oncontextmenu = function () { return false; };
                    elem.onSelected.attach(function (sender, args) {
                        e2.style.fontWeight = args.state ? "bold" : "normal";
                    });
                    e.oncontextmenu = function () { return false; };
                    e.onmouseover = function (event) {
                        var targ;
                        this.mo = true;
                        this.style.display = "block";
                    };
                    e.onmouseout = function (event) {
                        var other, self, to;
                        other = e2;
                        self = this;

                        if (!event) { event = window.event; }
                        if (event.toElement) {
                            to = event.toElement;
                        } else if (event.relatedTarget) {
                            to = event.relatedTarget;
                        } else {
                            to = null;
                        }
                        if ((to !== null) && (to !== this) && (!descends(this, to))) {
                            this.mo = false;
                            setTimeout(function () {
                                if (self.mo == false && other.mo == false) {
                                    self.style.display = "none";
                                }
                            }, 1000);
                        }
                    };

                    // tieing all together
                    e4.appendChild(e);
                    e3.appendChild(e2);
                    e3.appendChild(e4);
                    parent.appendChild(e3);

                    // the last dropdown menu goes from right to left
                    // so make previous sibling left to right
                    e4 = e3;
                    do {
                        e4 = e4.previousSibling;
                    } while (e4 && e4.nodeType !== 1);
                    if (e4) { e4.style.direction = "ltr"; } else { e3.style.direction = "ltr"; }
                    break;
                case "checklist":
                case "radiolist":
                    e = document.createElement('div');
                    e.style.padding = "6px 0px";
                    if (parent.hasChildNodes()) { e.style.borderTop = "3px double #eee"; }
                    if (elem.hasOwnProperty('name')) {
                        e2 = document.createElement('h3');
                        e2.style.fontSize = "13px";
                        e2.style.color = "#888";
                        e2.style.padding = "0 0 4px 0";
                        e2.innerHTML = elem.name;
                        if (elem.hasOwnProperty('title')) { e2.title = elem.title; }
                        e.appendChild(e2);
                    }
                    parent.appendChild(e);
                    break;
                case "check":
                case "radio":
                    e = document.createElement('div');
                    e.style.color = "#000";
                    //e.style.fontFamily = "Arial, sans-serif";
                    //e.style.fontSize = "11px";
                    //e.style.backgroundColor = "#fff";
                    e.style.padding = "0px 8px 0px 5px";
                    e.style.direction = "lrt";
                    e.style.textAlign = "left";
                    e.style.whiteSpace = "nowrap";
                    e.style.verticalAlign = "middle";
                    if (elem.hasOwnProperty('title')) { e.title = elem.title; }
                    e.onmouseover = function (event) {
                        var targ;

                        if (!event) { event = window.event; }
                        if (event.target) {
                            targ = event.target;
                        } else if (event.srcElement) {
                            targ = event.target;
                        }
                        if (targ.nodeType == 3) { targ = targ.parentNode; } //safari bug
                        if ((targ === e) || (targ.parentNode === e)) {
                            highlight(e);
                        }
                    };
                    e.onmouseout = function (event) { highlight(e, false); return false; };

                    e2 = document.createElement('input');
                    e2.type = (elem.type === "radio") ? "radio" : "checkbox";
                    if (elem.selected) { e2.checked = true; }
                    if (elem.hasOwnProperty('enabled') && (elem.enabled === false)) { e2.disabled = true; }
                    e2.style.verticalAlign = "bottom";
                    e2.style.margin = "1px 1px 1px 1px"; // the key for perfect alignment is to get rid of browser default margins
                    e2.onclick = function () {
                        elem.select(e2.checked);
                    };
                    elem.onSelected.attach(function (sender, args) {
                        e2.checked = args.state;
                    });
                    e.appendChild(e2);
                    if (elem.hasOwnProperty('name')) {
                        e3 = document.createElement('label');
                        e3.innerHTML = elem.name;
                        if (elem.hasOwnProperty('enabled') && (elem.enabled === false)) {
                            e3.style.color = "#ddd";
                        } else {
                            e3.style.color = elem.selected ? "#000" : "#555";
                        }
                        e3.style.verticalAlign = "middle";
                        e3.style.cursor = "pointer";
                        e3.style.marginLeft = "2px";
                        e3.style.lineHeight = "15px";
                        e3.style.fontSize = "11px";
                        e3.onclick = function () {
                            elem.select(!e2.checked);
                        };
                        elem.onSelected.attach(function (sender, args) {
                            if (sender.hasOwnProperty('enabled') && (sender.enabled === false)) {
                                e3.style.color = "#ddd";
                            } else {
                                e3.style.color = elem.selected ? "#000" : "#555";
                            }
                        });
                        e.appendChild(e3);
                    }
                    parent.appendChild(e);
                    break;
                case "extrahtml":
                    e = document.createElement('div');
                    e.innerHTML = elem.extrahtml;
                    parent.appendChild(e);
                    break;
                default:
                    e = document.createElement('div');
                    e.style.display = "none";
                    if (elem.hasOwnProperty('name')) { e.innerHTML = elem.name; }
                    if (elem.hasOwnProperty('title')) { e.title = elem.title; }
                    parent.appendChild(e);
                    break;
                }
                //parent.appendChild(e);
                if (elem.hasOwnProperty('list')) {
                    len = elem.list.length;
                    for (i = 0; i < len; i += 1) {
                        this.build(e, elem.list[i]);
                    }
                }
                elem.onNewChild.attach(function (sender, args) {
                    view.build(e, args.child);
                });
            }
            return e;
        }
    };

    // condensed (mobile) view: only a button initially
    CondensedView = function () {
        this.div = document.createElement('div');
    };

    // Initialization phase
    this.div = document.createElement('div');
    this.div.className = "LayerSwitcherControl";
    this.div.style.zIndex = "10000";
    this.div.style.margin = "5px";// same as google controls (it needs to be put here)

    // recursive function to build and tie everything
    loadData = function (list, parent) {
        var i, len, data, obj;

        len = list.hasOwnProperty('length') ? list.length : 0;
        for (i = 0; i < len; i += 1) {
            data = list[i];
            obj = new MenuData(data);
            parent.attachChild(obj);
            if (data.hasOwnProperty('list')) {
                loadData(data.list, obj);
            }
        }
    };

    // constructing the menu, etc
    if ((typeof opts !== "undefined") && (opts.hasOwnProperty('menu'))) {
        this.model = new MenuData(opts.menu); // create the root element
        this.view = new DefaultView(this.model); // TODO: select view depending on screen size
        this.div.appendChild(this.view.div);

        // fill it with children, if it has them
        if (opts.menu.hasOwnProperty('list')) {
            loadData(opts.menu.list, this.model);
        }
    }
    this.active = true;
};
/*LayerSwitcher.prototype = {
    toggle: function (state) {
        var enable;
        if (typeof state === "undefined") {
            enable = (this.view.div.style.display === "none") ? true : false;
        } else {
            enable = state;
        }
        if (enable && !this.active) {
            this.view.toggle(enable);
            this.active = true;
        } else if (!enable && this.active) {
            this.view.toggle(enable);
            this.active = false;
        }
    }
}*/
