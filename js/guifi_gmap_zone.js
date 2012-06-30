var map = null;

var marker_NE;
var marker_SW;
var marker_move ;

if(Drupal.jsEnabled) {
    $(document).ready(function(){
        draw_map();
    }); 
}

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
                    mapquest: { name: 'MapQuest', tooltip: 'Map Quest' },
                    demo: { name: 'Demo de menús', tooltip: 'Demostración de menús' }
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
                                        + basepath + 'sites/all/modules/guifi/js/loading.gif" style="vertical-align: middle; margin-left: 10px;" />'}}}
        },
        extrahtml:'<p style="font-size: 10px;text-align:center;color:#888;">(en construcción)</p>'
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

    // demo menu
    var layerswitcher_demo = new LayerSwitcher({
        "menu": {
            "name": "Mapas",
            "title": "Pulse aquí para seleccionar mapas alternativos",
            "type": "menulist",
            "list": [
                { "name": "Mapa", "title": "Muestra el callejero", "type": "menu",
                "list": [
                    { "name": "Ejemplo1", "title": "Texto explicativo (opcional)", "type": "radiolist", "list": [
                        { "name": "opción 1", "title": "o1", "type": "radio", "selected": "true" },
                        { "name": "opción 2", "title": "o2", "type": "radio" },
                        { "type": "extrahtml", "extrahtml": "<span style='font-size: 10px;'>(demostración)</span>" }
                    ] },
                    { "name": "Ejemplo2", "type": "radiolist", "list": [
                        { "name": "opción 1", "title": "o1", "type": "radio", "selected": "true" },
                        { "name": "opción 2", "title": "o2", "type": "radio" }
                    ] },
                    { "name": "Ejemplo3", "title": "Texto explicativo (opcional)", "type": "checklist", "list": [
                        { "name": "check1", "type": "check", "selected": "true" },
                        { "name": "check2", "type": "check", "selected": "false", "list": [
                            { "name": "sub1", "type": "check", "selected": "true" },
                            { "name": "sub2", "type": "check", "selected": "true" }
                        ] },
                        { "name": "check3", "type": "check", "selected": "false" },
                        { "name": "check4", "type": "check" }
                    ] },
                    { "name": "Ejemplo4", "type": "checklist", "list": [
                        { "name": "check1", "type": "check", "selected": "true" },
                        { "name": "check2", "type": "check", "selected": "true" }                        
                    ] }
                ]},
                { "name": "Satélite", "title": "Muestra las imágenes de satélite", "type": "menu", "selected": "true",
                "list": [
                    { "name": "Ejemplo1", "title": "Texto explicativo (opcional)", "type": "radiolist", "list": [
                        { "name": "opción 1", "title": "o1", "type": "radio", "selected": "true" },
                        { "name": "opción 2", "title": "o2", "type": "radio" },
                        { "type": "extrahtml", "extrahtml": "<span style='font-size: 10px;'>(demostración)</span>" }
                    ] },
                    { "name": "Ejemplo2", "type": "radiolist", "list": [
                        { "name": "opción 1", "title": "o1", "type": "radio", "selected": "true" },
                        { "name": "opción 2", "title": "o2", "type": "radio" }
                    ] },
                    { "name": "Ejemplo3", "title": "Texto explicativo (opcional)", "type": "checklist", "list": [
                        { "name": "check1", "type": "check", "selected": "true" },
                        { "name": "check2", "type": "check", "selected": "false", "list": [
                            { "name": "sub1", "type": "check", "selected": "true" },
                            { "name": "sub2", "type": "check", "selected": "true" }
                        ] },
                        { "name": "check3", "type": "check", "selected": "false" },
                        { "name": "check4", "type": "check" }
                    ] },
                    { "name": "Ejemplo4", "type": "checklist", "list": [
                        { "name": "check1", "type": "check", "selected": "true" },
                        { "name": "check2", "type": "check", "selected": "true" }
                    ] }
                ]},
                { "name": "Ejemplo (púlsame)", "title": "Muestra de un posible menú", "type": "menu", "list": [
                    { "name": "Mapas base", "title": "Mapas que salen al fondo del todo", "type": "radiolist", "list" : [
                        { "name": "Fotografía aérea", "title": "Fotos desde el aire (avión, satélite)", "type": "radio", "selected": "true" },
                        { "name": "Mapa básico", "title": "Mapa dibujado", "type": "radio" }
                    ]},
                    { "name": "Capas superpuestas", "title": "Capas con datos geográficas superpuestas a la capa base", "type": "checklist", "list" : [
                        { "name": "Etiquetas", "title": "Nombres de pueblos, etc", "type": "check", "selected": "true" },
                        { "name": "Puntos de interés", "title": "Elementos puntuales (antenas de móviles, postes de alta tensión, etc)", "type": "check", "list": [
                            { "name": "Antenas de telefonía", "title": "Antenas de móviles", "type": "check", "selected": "true" },
                            { "name": "Postes eléctricos", "title": "Postes de tendido eléctrico", "type": "check", "selected": "true", "list": [
                                { "name": "alta tensión", "title": "Postes de alta tensión", "type": "check", "selected": "true" },
                                { "name": "baja tensión", "title": "Postes de baja tensión", "type": "check" }
                            ] },
                            { "name": "Torres", "title": "Torres o edificios especialmente altos", "type": "check", "selected": "true" }
                        ] }
                    ]},
                    { "name": "Otros", "title": "Otras capas", "type": "checklist", "list": [
                        { "name": "varios", "title": "La mayoría de elementos son opcionales y configurables", "type": "check", "selected": "true" },
                        { "type": "extrahtml", "extrahtml": "<span style='font-size: 10px;'>(se puede añadir HTML extra como un elemento más)</span>" }
                    ]}
                ]},
                { "name": "Etc", "title": "Se pueden tener tantos menús como se quieran", "type": "menu", "list": [
                    { "name": "etc", "title": "Opción de ejemplo", "type": "check" }
                ]}
            ]}
    });
    layerswitcher_demo.view.toggle(false);
    map.controls[google.maps.ControlPosition.TOP_RIGHT].push(layerswitcher_demo.div);


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
                        { "name": "OCM transport", "title": "OpenCycleMap con líneas de transporte destacadas", "type": "radio" }
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
                { "name": "Mapa", "title": "Muestra el callejero", "type": "menu" },
                { "name": "Satélite", "title": "Muestra las imágenes de satélite", "type": "menu", "selected": "true" }
            ]}
    });
    layerswitcher_mapquest.view.toggle(false);
    map.controls[google.maps.ControlPosition.TOP_RIGHT].push(layerswitcher_mapquest.div);

    // Tie the events on the right panel to actions on the layerswitcher control
    google.maps.event.addDomListener(panelcontrol.inputs.google, 'click', function () {
        layerswitcher_demo.view.toggle(false);    // disable the controls that are not google
        layerswitcher_osm.view.toggle(false);
        layerswitcher_mapquest.view.toggle(false);
        map.setOptions({mapTypeControl: true});   // enable google layer control
        map.setMapTypeId(opts.mapTypeId);         // switch to the default layer
        google.maps.event.trigger(map, 'resize'); // trigger redraw/reflow
    });
    google.maps.event.addDomListener(panelcontrol.inputs.demo, 'click', function () {
        map.setOptions({mapTypeControl: false});
        layerswitcher_osm.view.toggle(false);
        layerswitcher_mapquest.view.toggle(false);
        layerswitcher_demo.view.toggle(true);
        layerswitcher_demo.model.notifySelections(true);
        google.maps.event.trigger(map, 'resize');
    });
    google.maps.event.addDomListener(panelcontrol.inputs.osm, 'click', function () {
        map.setOptions({mapTypeControl: false});
        layerswitcher_demo.view.toggle(false);
        layerswitcher_mapquest.view.toggle(false);
        layerswitcher_osm.view.toggle(true);
        layerswitcher_osm.model.notifySelections(true);
        google.maps.event.trigger(map, 'resize');
    });
    google.maps.event.addDomListener(panelcontrol.inputs.mapquest, 'click', function () {
        map.setOptions({mapTypeControl: false});
        layerswitcher_demo.view.toggle(false);
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
            case "OCM transport":
                map.setMapTypeId("opencyclemaptransport");
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

    var newNE = new google.maps.LatLng(document.getElementById("maxy").value, 
			                           document.getElementById("maxx").value);
    var newSW = new google.maps.LatLng(document.getElementById("miny").value, 
			                           document.getElementById("minx").value); 

    var newBounds = new google.maps.LatLngBounds(newSW, newNE) ;

    marker_NE = new google.maps.Marker({ position: newBounds.getNorthEast() }) ;
    marker_SW = new google.maps.Marker({ position: newBounds.getSouthWest() }) ;
    marker_move = new google.maps.Marker( 
                      new google.maps.LatLng(((marker_SW.getPosition().lat() + marker_NE.getPosition().lat()) / 2),
		                                      (marker_NE.getPosition().lng() + marker_SW.getPosition().lng()) / 2)) ;
    marker_move.savePoint = marker_move.getPosition() ;			// Save for later

    var infoWindow = new google.maps.InfoWindow({});

    google.maps.event.addListener(map, "click", function(event) {

        var point = event.latLng;

        if (map.getZoom() <= 15 ) {
            map.setZoom(map.getZoom()+3);
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


