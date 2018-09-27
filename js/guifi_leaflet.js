var data =
{
  baseTiles: [
      {
        name: 'OpenStreetMap',
        tiles: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        options: {
          maxZoom: 20,
          attribution: 'Guifi FO <a href="http://openstreetmap.org">&copy; OpenStreetMap</a>, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>'
        }
      },
      {
        name: 'Google Maps 1',
        tiles: 'https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
        options: {
          maxZoom: 20,
          subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
          attribution: '<a href="http://maps.google.es">&copy; Google Maps</a> contributors'
        }
      },
      {
        name: 'Google Maps 2',
        tiles: '  https://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}',
        options: {
          maxZoom: 20,
          subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
          attribution: '<a href="http://maps.google.es">&copy; Google Maps</a> contributors'
        }
      },
      {
        name: 'Google Maps 3',
        tiles: 'https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
        options: {
          maxZoom: 20,
          subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
          attribution: '<a href="http://maps.google.es">&copy; Google Maps</a> contributors'
        }
      },
      {
        name: 'Google Maps 4',
        tiles: 'https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
        options: {
          maxZoom: 20,
          subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
          attribution: '<a href="http://maps.google.es">&copy; Google Maps</a> contributors'
        }
      }
    ],
  overlayTiles: [
    {
      name: 'Guifi.net nodes',
      tiles: 'https://www.guifi.net/cgi-bin/mapserv?map=/var/www/guifimaps/GMap.map',
      options: {
        format: 'image/png',
        transparent: true,
        version: '1.1.1',
        uppercase: true,
        layers: 'Nodes'
      }
    },
    {
      name: 'Guifi.net links',
      tiles: 'https://guifimaps.guifi.net/cgi-bin/mapserv?map=/var/www/guifimaps/GMap.map',
      options: {
        format: 'image/png',
        transparent: true,
        version: '1.1.1',
        uppercase: true,
        layers: 'Links'
      }
    },
    {
      name: 'Punts fibra òptica guifi.net',
      tiles: 'https://guifimaps.guifi.net/cgi-bin/mapserv?map=/var/www/guifimaps/GMap.map',
      options: {
        format: 'image/png',
        transparent: true,
        version: '1.1.1',
        uppercase: true,
        layers: 'Sites'
      }
    },
    {
      name: 'Trams fibra òptica guifi.net',
      tiles: 'https://guifimaps.guifi.net/cgi-bin/mapserv?map=/var/www/guifimaps/GMap.map',
      options: {
        format: 'image/png',
        transparent: true,
        version: '1.1.1',
        uppercase: true,
        layers: 'Paths'
      }
    }
  ],
  selectedOverlayTiles: [0,1

  ],
  selectedBaseTile: 0
}

var map = null;

if(Drupal.jsEnabled) {
    $(document).ready(function(){
        drawMap();
    });
}

function loadTiles () {
  let baseMaps = {}
  for (let x in data.baseTiles) {
    let tile = data.baseTiles[x];
    let tileLayer = L.tileLayer(tile.tiles, tile.options);
    baseMaps[tile.name] = tileLayer;
    if (data.selectedBaseTile === parseInt(x)) {
      tileLayer.addTo(map);
    }
  }

  let overlayMaps = {}
  for (let x in data.overlayTiles) {
    let tile = data.overlayTiles[x];
    tile.options['crs'] = L.CRS.EPSG4326;
    let tileLayer = L.tileLayer.wms(tile.tiles, tile.options);
    overlayMaps[tile.name] = tileLayer;
    let index = data.selectedOverlayTiles.find(function (selected) {
      return selected === parseInt(x);
    })
    if (index >= 0) tileLayer.addTo(map);
  }

  let controlLayers = L.control.layers(baseMaps, overlayMaps, {position: 'bottomright'});
  controlLayers.addTo(map);
}

function drawMap() {
  let maxy = document.getElementById("maxy").value;
  let maxx = document.getElementById("maxx").value;
  let miny = document.getElementById("miny").value;
  let minx = document.getElementById("minx").value;

  // define rectangle geographical bounds
  let bounds = [[maxy, maxx], [miny, minx]];

  map = L.map('map').fitBounds(bounds);
  drawBoxZone(bounds);
  loadTiles();
}

function drawBoxZone (bounds) {
  // add rectangle passing bounds and some basic styles
  L.rectangle(bounds, {color: '#000000', weight: 5, opacity: 0.4, fillOpacity: 0.0, }).addTo(map);
}
