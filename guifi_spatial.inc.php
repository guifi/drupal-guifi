<?php
//
// Functions for spatial searches
//      (by David Rubio)
//
// Intended use:
//   Javascript programs that need to query lists of nodes/links within an area
//   in real time, such as mapping applications (Google Maps, OpenLayers, etc).
//
// Usage example:
// http://guifi.net/guifi/spatialsearch/nodes/all/all/39.9469/-0.1015/40.0227/-0.003
// (retrieves a list of all nodes near Castellón de la Plana, Spain)
//
// TODO: I've tried to make these functions as fast as possible, but some structural
//       changes in the database are needed to make them really efficient:
//         1) Storing latitude/longitude pairs as a single spatial-type column making
//            use of Spatial Extensions (MySQL) or PostGIS (PostgreSQL), with a spatial
//            index on the column.
//            The cost of a node (point) search is O(n^2) with no index, O(n) with
//            normal index (current implementation), O(log n) with spatial index.
//            (n = node count)
//         2) Storing the number of links coming from/to a node in extra column/s.
//            Clients could want to render the node differently depending on the
//            number of links (i.e. supernodes are nodes with >1 air links).
// NOTE: The current implementation doesn't take into account cable links for
//       deciding if a node is a supernode.


function guifi_spatialsearch($what="nodes", $type="air", $status="all", $latitude1="-90.0", $longitude1="-180.0", $latitude2="90.0", $longitude2="180.0") {
    $valid_statuses = array('all',   // all statuses
                            'plan',  // reserved, planned, building
                            'test',  // testing, working
                            'nodrop',// all but dropped
                            'Reserved', 'Planned', 'Building', 'Testing', 'Working', 'Dropped');
    $valid_types = array('all',  // all link types
                         'air',  // only aerial links (no cable)
                         'cable','client' /* ap/client */,'wds');

    $lat1 = (float)$latitude1;
    $lat2 = (float)$latitude2;
    $lon1 = (float)$longitude1;
    $lon2 = (float)$longitude2;

    if (($lat1 < -90.0) || ($lat1 > 90.0) ||
        ($lon1 < -180.0) || ($lon1 > 180.0) ||
        ($lat2 < -90.0) || ($lat2 > 90.0) ||
        ($lon2 < -180.0) || ($lon2 > 180.0)) {
        $json = '{ "message": "Error: latitude/longitude values out of range", "result": { } }';
        echo $json;
        return NULL;
    }

    if (($lat1 > $lat2) || ($lon1 > $lon2)) {
        $json = '{ "message": "Error: latitude/longitude values in reverse order (should be minlat,minlon,maxlat,maxlon)", "result": { } }';
        echo $json;
        return NULL;
    }

    if (!in_array($status, $valid_statuses)) {
        $json = '{ "message": "Error: Invalid status", "result": { } }';
        echo $json;
        return NULL;
    }

    if (!in_array($type, $valid_types)) {
        $json = '{ "message": "Error: Invalid type: must be \'all\', \'air\', \'cable\', \'client\' or \'wds\'.", "result": { } }';
        echo $json;
        return NULL;
    }

    switch ($what) {
        case 'supernodes':
        case 'clientnodes':
        case 'nodes':
            $json = guifi_spatialsearch_nodes($what, $type, $status, $lat1, $lon1, $lat2, $lon2);
            break;
        case 'links':
            $json = guifi_spatialsearch_links($type, $status, $lat1, $lon1, $lat2, $lon2);
            break;
        default:
            $json = '{ "message": "Error: first parameter must be \'nodes\' or \'links\'.", "result": { } }';
    }

    echo $json;
    return NULL;
}

//
// Outputs a list of nods withing an area, in JSON format
//
// The output contains:
//
// node id, node nick, node status, node stable status,
// node latitude, node longitude, number of aerial links
function guifi_spatialsearch_nodes($what, $type, $status, $lat1, $lon1, $lat2, $lon2) {
    // I've found no significant performance penalty doing this order by
    $orderby = ' ORDER BY links DESC';
    switch ($what) {
        case 'supernodes':
            $having = ' HAVING links > 1';
            break;
        case 'clientnodes':
            $having = ' HAVING links < 2';
            break;
        case 'nodes':
        default:
            $having = '';
    }

    switch ($type) {
        case 'air':
            $joinfilter = ' AND l.link_type!=\'cable\'';
            break;
        case 'cable':
            $joinfilter = ' AND l.link_type=\'cable\'';
            break;
        case 'client':
            $joinfilter = ' AND l.link_type=\'ap/client\'';
            break;
        case 'wds':
            $joinfilter = ' AND l.link_type=\'wds\'';
            break;
        case 'all':
        default:
            $joinfilter = '';
    }

    switch ($status) {
        case 'all':
            $status_where = '';
            break;
        case 'plan':
            $status_where = " AND (status_flag='Reserved' OR status_flag='Planned' OR status_flag='Building')";
            break;
        case 'test':
            $status_where = " AND (status_flag='Testing' OR status_flag='Working')";
            break;
        case 'nodrop':
            $status_where = " AND (status_flag!='Dropped')";
            break;
        default:
            $status_where = " AND (status_flag='$status')";
    }

    $sql = "SELECT n.*, COUNT(l.nid) as links FROM (SELECT id, nick, SUBSTR(status_flag FROM 1 FOR 1) as status, SUBSTR(stable FROM 1 FOR 1) as stable, lat, lon FROM guifi_location WHERE lat BETWEEN $lat1 AND $lat2 AND lon BETWEEN $lon1 AND $lon2${status_where}) n LEFT OUTER JOIN guifi_links l ON n.id=l.nid${joinfilter} GROUP BY n.id${having}${orderby}";

    //echo $sql."\n<br />";
    $result = db_query($sql);
    $rows = array();
    while ($record = db_fetch_object($result)) {
        $rows[] = $record;
    }
    return json_encode($rows);
}

function guifi_spatialsearch_links($type, $status, $lat1, $lon1, $lat2, $lon2) {
    return '{ "message": "not yet implemented", "result": { } }';
}


?>