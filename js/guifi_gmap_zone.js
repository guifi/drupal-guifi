var map = null;

var marker_NE;
var marker_SW;
var marker_move ;

    jQuery(document).ready(function($) {
        draw_map();
    });

function draw_map() {

    var divmap = document.getElementById("map");
    var baseURL = document.getElementById("guifi-wms").value

    opts = {
        center: new google.maps.LatLng(41.974175, 2.238118),
        zoom: 2,
        mapTypeControl: true,
        mapTypeControlOptions: {
            mapTypeIds: [ 
                          google.maps.MapTypeId.ROADMAP,
                          google.maps.MapTypeId.TERRAIN,
                          google.maps.MapTypeId.SATELLITE,
                          google.maps.MapTypeId.HYBRID ]
        },
        scaleControl: false,
        streetViewControl: false,
        zoomControl: true,
        panControl: true,
        zoomControlOptions: {
            style: google.maps.ZoomControlStyle.LARGE
        },

        mapTypeId: google.maps.MapTypeId.HYBRID
    }

    // Add the map to the div
    map = new google.maps.Map(divmap, opts);




    
    // Add the OSM map type
    map.mapTypes.set('osm', openStreet);
    map.mapTypes.set('opencyclemap', opencyclemap);
    map.mapTypes.set('opencyclemaptransport', opencyclemaptransport);
    map.mapTypes.set('opencyclemaplandscape', opencyclemaplandscape);
    map.mapTypes.set('mapquestosm', mapquestosm);
    map.mapTypes.set('mapquestopenaerial', mapquestopenaerial);
    initCopyrights();
    

    // directory of Drupal installation
    var basepath = (typeof Drupal.settings.basePath === "undefined") ? '' : Drupal.settings.basePath;
    
    // Add the right panel
    var panelcontrol = new PanelControl({
        forms:{
        fuente: {name: 'Proveedores', tooltip: 'Proveedor de los mapas de la capa inferior (terreno, etc)',
                type: 'radio', list: {
                    google: { name: 'Google', tooltip: 'Google Maps', default: true },
                    osm: { name: 'OSM', tooltip: 'OpenStreetMap' },
                    mapquest: { name: 'MapQuest', tooltip: 'Map Quest' }
                }},
        capas: {name: 'Capas', tooltip: 'Capas de datos extra',
                type: 'checkbox', list: {
                    //supernodos: { name: 'Supernodos', tooltip: 'Nodos con más de 1 enlace inalámbrico', default: true},
                    nodos: { name: 'Nodos', tooltip: /*'Nodos clientes (1 enlace inalámbrico)'*/'Nodos y supernodos', default: true,
                                        extrahtml: '<img id="img_overlay_nodos" alt="(loading)" title="cargando" src="'
                                        + basepath + 'sites/all/modules/guifi/js/loading.gif" style="vertical-align: middle; margin-left: 10px;" />' },
                    //superenlaces: { name: 'Superenlaces', tooltip: 'Enlaces entre supernodos (troncales)', default: true},
                    enlaces: { name: 'Enlaces', tooltip: /*'Enlaces cliente (de nodo a supernodo)'*/'Enlaces cliente y enlaces troncales', default: true,
                                        extrahtml: '<img id="img_overlay_enlaces" alt="(loading)" title="cargando" src="'
                                        + basepath + 'sites/all/modules/guifi/js/loading.gif" style="vertical-align: middle; margin-left: 10px;" />'},
                    nodosd: { name: 'Nodos dinámicos', tooltip: 'Nodos interactivos (con información al hacer click)', default: false, extrahtml: '<img id="img_nodos_dinamicos" alt="(loading)" title="cargando" src="'
                                        + basepath + 'sites/all/modules/guifi/js/loading.gif" style="vertical-align: middle; margin-left: 10px; display: none;" />' }
                }}
        },
        extrahtml:'<div id="filtros" style="margin-top:-15px; margin-left: 10px; font-size: 10px; display:none;"><table style="border:0;border-collapse:collapse"><tbody style="border:0">\
        <tr><td style="padding:0"><input id="fil_w" type="checkbox" checked="checked" style="margin-top:0;margin-bottom:0;vertical-align:bottom;padding:0;"/><label for="fil_w">Operativos</label>\
        </td><td style="padding:0"><input id="fil_t" type="checkbox" checked="checked" style="margin-top:0;margin-bottom:0;vertical-align:bottom;padding:0;"/><label for="fil_t">En pruebas</label></tr>\
        <tr><td style="padding:0"><input id="fil_b" type="checkbox" checked="checked" style="margin-top:0;margin-bottom:0;vertical-align:bottom;padding:0;"/><label for="fil_b">En construcción</label>\
        </td><td style="padding:0"><input id="fil_r" type="checkbox" style="margin-top:0;margin-bottom:0;vertical-align:bottom;padding:0;"/><label for="fil_r">Reservados</label></tr>\
        <tr><td style="padding:0"><input id="fil_p" type="checkbox" checked="checked" style="margin-top:0;margin-bottom:0;vertical-align:bottom;padding:0;"/><label for="fil_p">Proyectados</label>\
        </td><td style="padding:0"><input id="fil_d" type="checkbox" style="margin-top:0;margin-bottom:0;vertical-align:bottom;padding:0;"/><label for="fil_d">Borrados</label></tr>\
        <tr><td style="padding:0"><input id="fil_c" type="checkbox" checked="checked" style="margin-top:0;margin-bottom:0;vertical-align:bottom;padding:0;"/><label for="fil_c">Clientes</label>\
        </td><td style="padding:0"><input id="fil_s" type="checkbox" checked="checked" style="margin-top:0;margin-bottom:0;vertical-align:bottom;padding:0;"/><label for="fil_s">SuperNodos</label></td></tr></tbody></table></div>'
    });
    map.controls[google.maps.ControlPosition.RIGHT_TOP].push(panelcontrol.div);

    // Guifi layers
    var guifinodes = new GuifiLayer(map, baseURL,"Nodes");
    var guifilinks = new GuifiLayer(map, baseURL,"Links");
    var guifinodeslinks = new GuifiLayer(map, baseURL,"Nodes,Links");
    //map.overlayMapTypes.insertAt(0, guifinodes.overlay);
    //map.overlayMapTypes.insertAt(0, guifilinks.overlay);
    map.overlayMapTypes.insertAt(0, guifinodeslinks.overlay);

    // sets the 'loading' icon visible for the layer
    var loadingTiles = function (p) {
        var n = document.getElementById('img_overlay_nodos');
        var e = document.getElementById('img_overlay_enlaces');
        if (p.panel.capas.nodos && p.panel.capas.enlaces) {
            if (n) { n.style.display='inline'; }
            if (e) { e.style.display='inline'; }
        } else {
            if (p.panel.capas.nodos) {
                if (n) { n.style.display='inline'; }
            } else if (p.panel.capas.enlaces) {
                if (e) { e.style.display='inline'; }
            }
        }   
    }
    // loads the selected layers
    var toggleOverlays = function () {
        var n = document.getElementById('img_overlay_nodos');
        var e = document.getElementById('img_overlay_enlaces');
        if (this.panel.capas.nodos && this.panel.capas.enlaces) {
            map.overlayMapTypes.setAt(0, guifinodeslinks.overlay);
            n.style.display='inline';
            e.style.display='inline';
        } else {
            if (this.panel.capas.nodos) {
                map.overlayMapTypes.setAt(0, guifinodes.overlay);
                n.style.display='inline';
            } else if (this.panel.capas.enlaces) {
                map.overlayMapTypes.setAt(0, guifilinks.overlay);
                e.style.display='inline';
            } else {
                map.overlayMapTypes.pop();
                n.style.display='none';
                e.style.display='none';
            }
        }
    }
    google.maps.event.addDomListener(panelcontrol.inputs.nodos, 'click', toggleOverlays);
    google.maps.event.addDomListener(panelcontrol.inputs.enlaces, 'click', toggleOverlays);
    // every time the zoom changes, new tiles have to be loaded
    google.maps.event.addListener(map, 'zoom_changed', function () {
        new loadingTiles(panelcontrol);
    });
    // There's no way to know for sure if new tiles have to be loaded.
    // We could add an event hook for 'bounds_changed', but that won't work
    // if we just move the map a little, but not enough to go outside the tile.
    // 'bounds_changed' would probably work for base layers, but not for overlays.
    // We really need to migrate to OpenLayers...

    // hide the 'loading' icon when the tiles finish loading
    google.maps.event.addListener(guifinodes.overlay, 'tilesloaded', function () {
        var n = document.getElementById('img_overlay_nodos');
        n.style.display='none';
    });
    google.maps.event.addListener(guifilinks.overlay, 'tilesloaded', function () {
        var n = document.getElementById('img_overlay_enlaces');
        n.style.display='none';
    });
    google.maps.event.addListener(guifinodeslinks.overlay, 'tilesloaded', function () {
        var n = document.getElementById('img_overlay_nodos');
        var e = document.getElementById('img_overlay_enlaces');
        n.style.display='none';
        e.style.display='none';
    });

    /* ---------------------------------------------------------------  */
    //
    // Build our custom menus for each provider
    //

    // OSM menu
    layerswitcher_osm = new LayerSwitcher({
        "menu": {
            "name": "Mapas",
            "title": "Pulse aquí para seleccionar mapas alternativos",
            "type": "menulist",
            "list": [
                { "name": "Mapa", "title": "Muestra el callejero", "type": "menu", "selected": "true", 
                "list": [
                    { "name": "Capa base", "title": "Mapa que se verá al fondo del todo", "type": "radiolist", "list": [
                        { "name": "OpenStreetMap", "title": "Mapa casero elaborado con datos de OpenStreetMap", "type": "radio", "selected": "true" },
                        { "name": "OpenCycleMap", "title": "Mapa ciclista (incluye curvas de nivel)", "type": "radio" },
                        { "name": "OCM Transport", "title": "OpenCycleMap con líneas de transporte destacadas", "type": "radio" },
                        { "name": "OCM Landscape", "title": "OpenCycleMap optimizado para naturaleza (relieve, curvas de nivel, etc)", "type": "radio" }
                    ] }
                ]},
                { "name": "Satélite", "title": "Muestra las imágenes de satélite", "type": "menu",
                "list": [
                    { "name": "Capa base", "title": "Imagen que se verá al fondo del todo", "type": "radiolist", "list": [
                        { "name": "OpenAerialMap", "title": "Open Aerial Map (fotos sacadas de la NASA, de licencia libre, pero poca resolución)", "type": "radio", "selected": "true" },
                        { "name": "PNOA (máx.res.)", "title": "Fotografías libres de la superficie española, máxima resolución", "type": "radio", "enabled": "false" },
                        { "name": "PNOA (más recientes)", "title": "Fotografías libres de la superficie española, las más recientes disponibles", "type": "radio", "enabled": "false" }
                    ] },
                    { "name": "Etiquetas", "title": "Nombres de pueblos, etc", "type": "checklist", "list": [
                        { "name": "Nombres de ciudades", "title": "Etiquetas con los nombres de las ciudades principales", "type": "check", "selected": "true", "enabled": "false" },
                        { "name": "Carreteras", "title": "Principales carreteras", "type": "check", "selected": "true", "enabled": "false" }
                    ] }                    
                ]}
            ]}
    });
    layerswitcher_osm.view.toggle(false);
    map.controls[google.maps.ControlPosition.TOP_RIGHT].push(layerswitcher_osm.div);

    // MapQuest menu
    layerswitcher_mapquest = new LayerSwitcher({
        "menu": {
            "name": "Mapas",
            "title": "Pulse aquí para seleccionar mapas alternativos",
            "type": "menulist",
            "list": [
                { "name": "Mapa", "title": "Muestra el callejero", "type": "menu", "selected": "true" },
                { "name": "Satélite", "title": "Muestra las imágenes de satélite", "type": "menu" }
            ]}
    });
    layerswitcher_mapquest.view.toggle(false);
    map.controls[google.maps.ControlPosition.TOP_RIGHT].push(layerswitcher_mapquest.div);

    // Tie the events on the right panel to actions on the layerswitcher control
    google.maps.event.addDomListener(panelcontrol.inputs.google, 'click', function () {
        layerswitcher_osm.view.toggle(false);
        layerswitcher_mapquest.view.toggle(false);
        map.setOptions({mapTypeControl: true});   // enable google layer control
        map.setMapTypeId(opts.mapTypeId);         // switch to the default layer
        google.maps.event.trigger(map, 'resize'); // trigger redraw/reflow
    });
    google.maps.event.addDomListener(panelcontrol.inputs.osm, 'click', function () {
        map.setOptions({mapTypeControl: false});
        layerswitcher_mapquest.view.toggle(false);
        layerswitcher_osm.view.toggle(true);
        layerswitcher_osm.model.notifySelections(true);
        google.maps.event.trigger(map, 'resize');
    });
    google.maps.event.addDomListener(panelcontrol.inputs.mapquest, 'click', function () {
        map.setOptions({mapTypeControl: false});
        layerswitcher_osm.view.toggle(false);
        layerswitcher_mapquest.view.toggle(true);
        layerswitcher_mapquest.model.notifySelections(true);
        google.maps.event.trigger(map, 'resize');
    });

    // OSM layer switcher: if a subelement of "Mapa" is selected, switch to that layer
    layerswitcher_osm.model.list[0].list[0].onChildSelected.attach( function (sender, args) {
        if (args.state) {
            switch (args.who.name) {
            case "OpenStreetMap":
                map.setMapTypeId("osm");
                break;
            case "OpenCycleMap":
                map.setMapTypeId("opencyclemap");
                break;
            case "OCM Transport":
                map.setMapTypeId("opencyclemaptransport");
                break;
            case "OCM Landscape":
                map.setMapTypeId("opencyclemaplandscape");
                break;
            }
        }
    });
    // OSM layer switcher: if a subelement of "Satélite" is selected, switch to that layer
    layerswitcher_osm.model.list[1].list[0].onChildSelected.attach( function (sender, args) {
        if (args.state) {
            switch (args.who.name) {
            case "OpenAerialMap":
                map.setMapTypeId("mapquestopenaerial");
                break;
            }
        }
    });
    layerswitcher_osm.model.list[0].onSelected.attach( function (sender, args) {
        sender.notifyChildrenSelections(true);
    });
    layerswitcher_osm.model.list[1].onSelected.attach( function (sender, args) {
        sender.notifyChildrenSelections(true);
    });
    
    layerswitcher_mapquest.model.list[0].onSelected.attach( function (sender, args) {
        if (args.state) {
            map.setMapTypeId("mapquestosm");
        }
    });
    layerswitcher_mapquest.model.list[1].onSelected.attach( function (sender, args) {
        if (args.state) {
            map.setMapTypeId("mapquestopenaerial");
        }
    });

    /****************************************************************************/
    // "Clickable nodes" feature

    var clickablenodes = false; // feature initially enabled?
    var markers = new Object(); // associative array with all the markers, visible or not
    var nodes   = new Array();  // current requested nodes, ordered by importance
    markers.length = 0;         // number of current visible markers
    var maxnodes = 1000;            // maximum visiable nodes at the same time

    // holds the data about what needs to be shown/hidden
    var filters = function () {
        var self;
        self = this;
        this.lastshown = -1;
        
        // reads the checkboxes
        this.update = function () {
            var e;
            // Planned, Reserved, Testing, Building, Working, Dropped, Clients, Supernodes
            e = document.getElementById("fil_p");
            self.p = e ? e.checked : true;
            e = document.getElementById("fil_r");
            self.r = e ? e.checked : true;
            e = document.getElementById("fil_t");
            self.t = e ? e.checked : true;
            e = document.getElementById("fil_b");
            self.b = e ? e.checked : true;
            e = document.getElementById("fil_w");
            self.w = e ? e.checked : true;
            e = document.getElementById("fil_d");
            self.d = e ? e.checked : true;
            e = document.getElementById("fil_c");
            self.c = e ? e.checked : true;
            e = document.getElementById("fil_s");
            self.s = e ? e.checked : true;
        };
        this.apply = function (node) {
            var show;
            show = true;

            if (node.hasOwnProperty("id") && node.hasOwnProperty("status") && node.hasOwnProperty("links") && markers.hasOwnProperty(node["id"])) {
                switch (node.status) {
                    case "W":
                        if (!filter.w) { show = false; }
                        break;
                    case "P":
                        if (!filter.p) { show = false; }
                        break;
                    case "T":
                        if (!filter.t) { show = false; }
                        break;
                    case "R":
                        if (!filter.r) { show = false; }
                        break;
                    case "B":
                        if (!filter.b) { show = false; }
                        break;
                    case "D":
                        if (!filter.d) { show = false; }
                        break;
                }
                if (node.links > 1) {
                    if (!filter.s) { show = false; }
                } else {
                    if (!filter.c) { show = false; }
                }
                if (markers[node.id].getMap() === map) {
                    if (!show) {
                        markers[node.id].setMap(null);
                        markers.length -= 1;
                    }
                } else {
                    if (show) {
                        markers[node.id].setMap(map);
                        markers.length += 1;
                    }
                }
                return show;
            }
            return null;
        };
        this.applyAll = function () {
            var i, len, shown, n;

            n = document.getElementById('img_nodos_dinamicos');
            n.style.display = 'inline';

            self.update();
            len = nodes.length;
            for (i = 0; i < len; i += 1) {
                shown = self.apply(nodes[i]);
                if (markers.length < maxnodes) {
                    if (shown && self.lastshown < i) {
                        self.lastshown = i;
                    }
                } else if (shown) {
                    if (i < self.lastshown) {
                        markers[nodes[self.lastshown].id].setMap(null);
                        markers.length -= 1;
                        self.searchLastShown(self.lastshown);
                    } else {
                        markers[nodes[i].id].setMap(null);
                        markers.length -= 1;
                    }
                }
            }
            n.style.display = 'none';
        };
        this.searchLastShown = function (from) {
            var i, len, lastpos;
            
            len = nodes.length;
            if (typeof from === "undefined") {
                lastpos = len - 1;
            } else {
                lastpos = from;
            }
            for (i = lastpos; i >= 0; i -= 1) {
                if (nodes[i].hasOwnProperty("id") && markers.hasOwnProperty(nodes[i].id) && markers[nodes[i].id].getMap() === map) {
                    self.lastshown = i;
                    return;
                }
            }
            self.lastshown = -1;
        };
    };
    var filter = new filters();

    // enables/disables the entire "clickable nodes" feature
    var toggleClickableNodes = function () {
        var id, filters;        
        
        filters = document.getElementById('filtros');
        if (clickablenodes) {
            for (id in markers) {
                if (markers.hasOwnProperty(id) && (id !== "length")) {
                    if (markers[id].getMap() === map) {
                        markers.length -= 1;
                        markers[id].setMap(null);
                    }
                    delete markers[id];
                    totalmarkercount -= 1;
                }
            }
            filters.style.display = "none";
        } else {
            filters.style.display = "block";

            // disable static node layer
            panelcontrol.panel.capas.nodos = false;
            panelcontrol.inputs.nodos.checked = false;
            toggleOverlays.call(panelcontrol.inputs.nodos);
        }
        clickablenodes = !clickablenodes;
        google.maps.event.trigger(map, 'bounds_changed');
    };
    google.maps.event.addDomListener(panelcontrol.inputs.nodosd, 'click', toggleClickableNodes);

    // we've added checkboxes to panelcontrol through extrahtml, so
    // we have to wait for them to become available
    var waitforfilterinputs;
    waitforfilterinputs = setInterval(function () {
        var e, i, j, tbody, tr, td, input;
        e = document.getElementById("filtros");
        if (e) {
            tbody = e.children[0].children[0];
            for (i = 0; i < tbody.children.length; i += 1) {
                tr = tbody.children[i];
                for (j = 0; j < tr.children.length; j += 1) {
                    td = tr.children[j];
                    input = td.children[0];
                    google.maps.event.addDomListener(input, 'click', filter.applyAll);
                }
            }
            filter.update();
            clearInterval(waitforfilterinputs);
        }
    },1000);

    // creates the object for AJAX calls
    var createXmlHttp = function () {
        var xmlHttp;
        if (typeof XMLHttpRequest == "undefined") {
            try { xmlHttp = new ActiveXObject("Msxml2.XMLHTTP.6.0"); }
            catch (a) {
                try { xmlHttp = new ActiveXObject("Msxml2.XMLHTTP.3.0"); }
                catch (b) {
                    try { xmlHttp = new ActiveXObject("Microsoft.XMLHTTP"); }
                    catch (c) { xmlHttp = null; }
                }
            }
        } else {
            xmlHttp = new XMLHttpRequest();
        }
        return xmlHttp;
    };
    var request = createXmlHttp();

    // store all the ImageMarkers here for later usage (improves performance)
    var iconset = function () {
        var self, size, x, y, i, j, step, url, imagesize, codes, code;
        self = this;

        this.geticon = function (links, status, stable) {
            var size, i;
            if (links > 1) {
                size = Math.floor((2 + (Math.pow(links, 0.4) / 2)) * 4);
                if (size > 32) { size = 32; }
                if (size < 10) { size = 10; }
                i = Math.floor((size - 10) / 2);
                return self.supernodes[i][status];
            } else {
                if (stable == "Y") {
                    return self.clients[0][status];
                } else {
                    return self.clients[1][status];
                }
            }
        };

        size = 10;
        step = 2;
        url = basepath + 'sites/all/modules/guifi/js/markers.png';
        imagesize = 256;
        codes = ["W", "T", "B", "P", "R", "D", "U", "I"];// ..., Unknown, Invisible

        this.supernodes = new Array();
        this.clients = new Array();

        y = 0;
        for (j = 0; j < 12; j += 1) {
            this.supernodes[j] = new Object();
            for (i = 0; i < 8; i += 1) {
                x = i * size;
                code = codes[i];
                this.supernodes[j][code] = new google.maps.MarkerImage(url,
                                                   new google.maps.Size(size,size),
                                                   new google.maps.Point(x,y),
                                                   new google.maps.Point(size/2,size/2),
                                                   new google.maps.Size(256,256)
                );
            }
            y += size;
            size += step;
        }

        // icons for the clients (various styles)
        // mini:   size = 1, y = 0
        // small:  size = 2, y = 2
        // normal: size = 5, y = 6
        // big:    size = 5, y = 16
        y = 16;
        size = 5;
        for (j = 0; j < 2; j += 1) {
            x = 216;
            this.clients[j] = new Object();
            for (i = 0; i < 8; i += 1) {
                code = codes[i];
                this.clients[j][code] = new google.maps.MarkerImage(url,
                                                   new google.maps.Size(size,size),
                                                   new google.maps.Point(x,y),
                                                   new google.maps.Point(size/2,size/2),
                                                   new google.maps.Size(256,256)
                );
                x += size;
            }
            y += size;
        }
    };
    var icons = new iconset();

    var totalmarkercount = 0;

    // draws the markers (nodes) on the map
    var draw_nodes = function () {
        var result, i, id, len, links, infowindow, /*desaturate,
        f, b, borderSize, size,
        star,*/ nimg, shown;

        if (!clickablenodes) { return; }
        
        /*desaturate = function (red, green, blue, alpha) {
            var gray;
            // different grayscale methods

            // gimp desaturate: lightness (claridad)
            //gray = (Math.max(red, green, blue) + Math.min(red, green, blue)) / 2;

            // gimp desaturate: luminosity (luminosidad)
            gray = red * 0.21 + green * 0.72 + blue * 0.07;

            // gimp desaturate: average (media)
            //gray = (red + green + blue) / 3;

            gray = Math.floor(gray); // must be an integer
            return { red: gray, green: gray, blue: gray, alpha: alpha };
        };
         // five point star in SVG path notation
        star = 'M 1.25992,1.9741 0.01732,1.306 -1.21224,2 -0.99024,0.555 -2,-0.4561 -0.62004,-0.6809 -0.01452,-1.9998 0.61604,-0.6938 2,-0.4982 1.00972,0.5339 z';*/

        // remove markers not in viewable area
        for (id in markers) {
            if (markers.hasOwnProperty(id) && (id !== "length")) {
                if (!map.getBounds().contains(markers[id].getPosition())) {
                    if (markers[id].getMap() === map) {
                        markers.length -= 1;
                        markers[id].setMap(null);
                    }
                    delete markers[id];
                    totalmarkercount -= 1;
                }
            }
        }
        if ((request.readyState == 4) && (request.status == 200)) {
            result = eval("(" + request.responseText + ")"); // parse JSON
            if (!result.hasOwnProperty('message')) {
                nodes = result;
                filter.update();
                filter.lastshown = -1;
                len = result.length;
                for (i = 0; i < len; i += 1) {
                    if (!markers.hasOwnProperty(result[i].id)) {
                        //markers.length += 1;
                        totalmarkercount += 1;
                        /*f = { red: 0, green: 0, blue: 0, alpha: 1 };
                        b = { red: 0, green: 0, blue: 0, alpha: 1 };*/
                        links = parseInt(result[i].links, 10); // number of air links
                        /*size = 2 + (Math.pow(links,0.4) / 2);  // size of the marker
                        if (links < 2) { size = 1; }
                        borderSize = 0;
                        if (links > 1) { borderSize = 1; }      // supernodes have border
                        if (links > 20) { borderSize = 2; }     // big supernodes
                        if (links > 100) { borderSize = 3; }    // huge supernodes
                        switch (result[i].status) {
                        case 'W':
                            f.red = 51; f.green = 255; f.blue = 0;
                            break;
                        case 'T':
                            f.red = 255; f.green = 153; f.blue = 0;
                            break;
                        case 'B':
                            f.red = 255; f.green = 255; f.blue = 153;
                            break;
                        case 'P':
                            f.red = 102; f.green = 255; f.blue = 255;
                            break;
                        case 'R':
                            f.red = 255; f.green = 168; f.blue = 243;
                            break;
                        case 'D':
                            f.red = 10; f.green = 10; f.blue = 10;
                            break;
                        default:
                            f.red = 255; f.green = 255; f.blue = 255;
                        }
                        if (result[i].stable === "N") { f = desaturate(f.red, f.green, f.blue, 0.5); b.alpha = 0.5; }*/
                        markers[result[i].id] = new google.maps.Marker({
                            position: new google.maps.LatLng(result[i].lat, result[i].lon),
                            map: null,
                            clickable: true,
                            flat: true,
                            visible: true,
                            optimized: true,
                            icon: icons.geticon(links, result[i].status, result[i].stable),
                            /*icon: {
                                path:(links > 1) ? star : google.maps.SymbolPath.CIRCLE,
                                fillColor: "rgb(" + f.red + "," + f.green + "," + f.blue + ")",
                                fillOpacity: f.alpha,
                                strokeColor: "rgb(" + b.red + "," + b.green + "," + b.blue + ")",
                                strokeOpacity: b.alpha,
                                strokeWeight: borderSize,
                                scale: size
                            },*/
                            zIndex: parseInt(result[i].links, 10),
                            title: result[i].nick
                        });
                        // Info Window when the user clicks on the node
                        google.maps.event.addListener(markers[result[i].id], 'click', (function(nodes,i) {
                            return function() {
                                var status, stable, supernode, links;
                                links = parseInt(result[i].links,10);
                                supernode = '<strong>Supernode</strong>: ';
                                status = '<strong>Status</strong>: ';
                                stable = '<strong>Stable</strong>: ';
                                if (links > 1) { supernode += 'yes'; } else { supernode += 'no'; }
                                supernode += ' (' + links + ' airlinks)';
                                switch (result[i].status) {
                                    case 'W':
                                        status += '<span style="color: #080;">Working</span>';
                                        break;
                                    case 'T':
                                        status += '<span style="color: #f80;">Testing</span>';
                                        break;
                                    case 'B':
                                        status += '<span style="color: #880;">Building</span>';
                                        break;
                                    case 'P':
                                        status += '<span style="color: #33f;">Planned</span>';
                                        break;
                                    case 'R':
                                        status += '<span style="color: #B374AA;">Reserved</span>';
                                        break;
                                    case 'D':
                                        status += '<span style="color: #666;">Dropped</span>';
                                        break;
                                    default:
                                        status += '?';
                                }
                                switch (result[i].stable) {
                                    case 'Y':
                                        stable += 'yes';
                                        break;
                                    case 'N':
                                        stable += '<span style="color: #f00;">no</span>';
                                        break;
                                    default:
                                        stable += '?';
                                }
                                if (typeof infowindow === "undefined") {
                                    infowindow = new google.maps.InfoWindow();
                                }
                                infowindow.setContent('<div><h3><a target="_blank" href="' + basepath + 'node/' + result[i].id + '">' + result[i].nick + '</a></h3><hr /><p>' + supernode + '<br />' + status + '<br />' + stable + '</p></div>');
                                infowindow.open(map, markers[result[i].id]);
                            }
                        })(nodes, i));
                        shown = filter.apply(result[i]);
                        if (shown) {
                            if (markers.length <= maxnodes) {
                                filter.lastshown = i;
                            } else {
                                markers[result[i].id].setMap(null);
                                markers.length -= 1;
                            }
                        }
                    }
                }
            } else {
                alert(result.message);
            }
        nimg = document.getElementById('img_nodos_dinamicos');
        nimg.style.display = 'none';
        }
    };
    
    var dragging, pendingupdate;
    dragging = false;
    pendingupdate = false;

    google.maps.event.addListener(map, 'dragstart', function () {
        dragging = true;
    });

    google.maps.event.addListener(map, 'dragend', function () {
        dragging = false;
        if (pendingupdate) {
            google.maps.event.trigger(map, 'bounds_changed');
        }
    });
    
    google.maps.event.addListener(map, 'bounds_changed', function () {
        var coord, n;
        if (dragging) { pendingupdate = true; }
        
        if (clickablenodes && !dragging) {
            pendingupdate = false;
            n = document.getElementById('img_nodos_dinamicos');
            n.style.display = 'inline';
            coord = map.getBounds().toUrlValue().replace(/,/g,'/');
            request.onreadystatechange = draw_nodes;
            request.open("GET", basepath + "guifi/spatialsearch/nodes/air/all/" + coord, true);
            //39.9469/-0.1015/40.0227/-0.003
            request.send(null);
        }
    });
    /****************************************************************************/
    
    /*var guifiControl = new Control("guifi");
    guifiControl.div.index = 1;
    map.controls[google.maps.ControlPosition.TOP_RIGHT].push(guifiControl.div);

    // Setup the click event listeners
    google.maps.event.addDomListener(guifiControl.ui, 'click', function() {
        if (guifiControl.enabled) {
            map.overlayMapTypes.removeAt(0);
            guifiControl.disable();
        } else {
            // Add the guifi layer
            map.overlayMapTypes.insertAt(0, guifi.overlay);
            guifiControl.enable();
        }
    });*/

    var marcador = new google.maps.Marker();

    var newNE = new google.maps.LatLng(document.getElementById("maxy").value, document.getElementById("maxx").value);
    var newSW = new google.maps.LatLng(document.getElementById("miny").value, document.getElementById("minx").value); 

    var newBounds = new google.maps.LatLngBounds(newSW, newNE) ;

    marker_NE = new google.maps.Marker({ position: newBounds.getNorthEast() }) ;
    marker_SW = new google.maps.Marker({ position: newBounds.getSouthWest() }) ;
    marker_move = new google.maps.Marker( 
                      new google.maps.LatLng(((marker_SW.getPosition().lat() + marker_NE.getPosition().lat()) / 2),
		                                      (marker_NE.getPosition().lng() + marker_SW.getPosition().lng()) / 2)) ;
    marker_move.savePoint = marker_move.getPosition() ;			// Save for later

    var infoWindow = new google.maps.InfoWindow({});

    google.maps.event.addListener(map, "dblclick", function(event) {

        var point = event.latLng;

        if (map.getZoom() <= 15 ) {
            map.setCenter(point);
            map.setZoom(map.getZoom()+2)
        } else {

            marcador.setPosition(point);
            var basePath = Drupal.settings.basePath;

            infoWindow.setContent(
                    'Lat : ' + point.lat() + '<br />Lon: ' + point.lng() +
                    '<br /><a href="' + basePath + 'node/add/guifi-node?lon='
                    + point.lng() + '&lat=' + point.lat() +
                    '&zone='+document.getElementById("zone_id").value+
                    '" TARGET=fijo APPEND=blank>Add a new node here</a>');
            infoWindow.open(map, marcador);
        }
    });

    var bounds = new google.maps.LatLngBounds();

    // Check for moved center...
    if ( marker_move.getPosition() != marker_move.savePoint ) {

        var x = marker_move.getPosition().lat() - marker_move.savePoint.lat() ;
        var y = marker_move.getPosition().lng() - marker_move.savePoint.lng() ;
        marker_SW.setPosition( new google.maps.LatLng( marker_SW.getPosition().lat() + x, marker_SW.getPosition().lng() + y) ) ;
        marker_NE.setPosition( new google.maps.LatLng( marker_NE.getPosition().lat() + x, marker_NE.getPosition().lng() + y) ) ;

    } else	{
        // Center not moved so move center
        var x = (marker_SW.getPosition().lat() + marker_NE.getPosition().lat()) / 2 ;
        var y = (marker_NE.getPosition().lng() + marker_SW.getPosition().lng()) / 2 ;
        marker_move.setPosition( new google.maps.LatLng(x,y) ) ;
        map.setCenter(new google.maps.LatLng(x,y));
    }

    marker_move.savePoint = marker_move.getPosition() ;			// Save for later

    var points = [
        marker_NE.getPosition(),
        new google.maps.LatLng(marker_SW.getPosition().lat(), marker_NE.getPosition().lng()),
        marker_SW.getPosition(),
        new google.maps.LatLng(marker_NE.getPosition().lat(), marker_SW.getPosition().lng()),
        marker_NE.getPosition()
    ];

    var border = new google.maps.Polyline( { path: points, strokeColor: "#66000", strokeOpacity: .4, strokeWeight: 5, map: map });
 
    bounds.extend(marker_SW.getPosition());
    bounds.extend(marker_NE.getPosition());
    map.fitBounds(bounds);
 
}


