<?php
/**
 * @file guifi_gml.inc.php
 */

function guifi_gml($zid,$action = "help",$type = 'gml') {

  if ($action == "help") {
     $zone = db_query('SELECT title, nick FROM {guifi_zone} WHERE id = :id', array(':id' => $zid))->fetchObject();
     drupal_set_breadcrumb(guifi_zone_ariadna($zid));
     $output = '<div id="guifi">';
     $output .= '<h2>'.t('Zone %zname%',array('%zname%' => $zone->title)).'</h2>';
     $output .= '<p>'.t('You must specify which data do you want to export, the following options are available:').'</p>';
     $output .= '<ol><li>'. l(t('Nodes'), "guifi/gml/".$zid."/nodes", array('title' => t('export zone nodes in gml format')) ).'</li>';
     $output .= '<li>'. l(t('Links'), "guifi/gml/".$zid."/links", array('title' => t('export zone links in gml format')) ).'</li></ol>';
     $output .= '<p>'.t('The <a href="http://opengis.net/gml/">GML</a> is a Markup Language XML for Geography described at the <a href="http://www.opengeospatial.org/">Open Geospatial Consortium</a>').'</p>';
     $output .= '<p>'.t('<b>IMPORTANT LEGAL NOTE:</b> This network information is under the <a href="http://guifi.net/ComunsSensefils/">Comuns Sensefils</a> license, and therefore, available for any other network under the same licensing. If is not your case, you should ask for permission before using it.</a>').'</p>';
     $output .= "</div>";
     $output .= t('export %zname% in GML format',array('%zname%' => $zone->title));
     return $output;
  }

  switch ($action) {
  case 'links':
    guifi_gml_links($zid,$type);
    break;
  case 'nodes':
    guifi_gml_nodes($zid,$type);
    break;
  }
} //EOF function guifi_gml

function guifi_gml_nodes($zid,$type) {
  $minx = 180; $miny = 90; $maxx= -180; $maxy = -90;

  $zchilds = guifi_zone_childs($zid);

  $res = db_query(
    "SELECT id,nick,lat,lon,zone_id,status_flag " .
    "FROM {guifi_location}");

  while ($row = $res->fetchObject()) {
    if (($row->zone_id != $zid) and (!in_array($row->zone_id,$zchilds)))
      continue;
    $rsql = db_query(
       "SELECT mode " .
       "FROM {guifi_radios} " .
       "WHERE nid = :nid",
       array(':nid' => $row->id));
    $rcount = 0;
    $node_type = 'N_A';
    while ($r = $rsql->fetchObject()) {
      $rcount++;
      if ($rcount == 1)
        $node_type = $r->mode;
      else {
        $node_type = 'Supernode';
        break;
      }
    }

    if ($type == 'gml') {
    $output .= '
  <gml:featureMember>
    <dnodes fid="'.$row->id.'">
      <ogr:geometryProperty><gml:Point><gml:coordinates>'.$row->lon.','.$row->lat.'</gml:coordinates></gml:Point></ogr:geometryProperty>
      <NODE_ID>'.$row->id.'</NODE_ID>
      <NODE_NAME>'.$row->nick.'</NODE_NAME>
      <NODE_TYPE>'.$node_type.'</NODE_TYPE>
      <STATUS>'.$row->status_flag.'</STATUS>
    </dnodes>
  </gml:featureMember>';
    } else {
      $output .= $row->id.','.$row->lon.','.$row->lat.','.$row->nick.','.$node_type.','.$row->status_flag."\n";
    }
    if ($row->lon > $maxx) $maxx = $row->lon;
    if ($row->lat > $maxy) $maxy = $row->lat;
    if ($row->lon < $minx) $minx = $row->lon;
    if ($row->lat < $miny) $miny = $row->lat;
  } // while nodes

  drupal_add_http_header('Content-Type: application/xml; charset=utf-8');
  if ($type == 'gml') print '<?xml version="1.0" encoding="utf-8" ?>
<ogr:FeatureCollection
     xmlns:xsi="http://www.w3c.org/2001/XMLSchema-instance"
     xsi:schemaLocation=". dnodes.xsd"
     xmlns:ogr="http://ogr.maptools.org/"
     xmlns:gml="http://www.opengis.net/gml">
  <gml:boundedBy>
    <gml:Box>
      <gml:coord><gml:X>'.$minx.'</gml:X><gml:Y>'.$miny.'</gml:Y></gml:coord>
      <gml:coord><gml:X>'.$maxx.'</gml:X><gml:Y>'.$maxy.'</gml:Y></gml:coord>
    </gml:Box>
  </gml:boundedBy>';
  print $output;
  if ($type == 'gml') print '
</ogr:FeatureCollection>';
} // EOF function guifi_gml_nodes()

function guifi_gml_links($zid,$type) {
  $oGC = new GeoCalc();
  $minx = 180; $miny = 90; $maxx= -180; $maxy = -90;

  $res = db_query(
    "SELECT id,link_type,flag " .
    "FROM {guifi_links} " .
    "WHERE link_type != 'cable' " .
    "GROUP BY 1,2 " .
    "HAVING count(*) = 2");

  $zchilds = guifi_zone_childs($zid);
  $zchilds[$zid] = 'Top';

  while ($row = $res->fetchObject()) {
    
    $resnode = db_query(
      "SELECT n.id, n.zone_id, n.nick,n.lat, n.lon, n.status_flag " .
      "FROM {guifi_links} l, {guifi_location} n " .
      "WHERE l.id = :lid AND l.nid=n.id",
      array(':lid' => $row->id));
    $nl = array();
    while ($n = $resnode->fetchObject()) {
      $nl[] = $n;
    }
    if (count($nl) == 2)
      if ((in_array($nl[0]->zone_id,$zchilds)) || (in_array($nl[1]->zone_id,$zchilds))) {
        $distance = round($oGC->EllipsoidDistance($nl[0]->lat,$nl[0]->lon, $nl[1]->lat, $nl[1]->lon),3);
        $status = $row->flag;
        
        if ($type == 'gml') $output .= '
          <gml:featureMember>
          <dlinks fid="'.$row->id.'">
          <NODE1_ID>'.$nl[0]->id.'</NODE1_ID>
          <NODE1_NAME>'.$nl[0]->nick.'</NODE1_NAME>
          <NODE2_ID>'.$nl[1]->id.'</NODE2_ID>
          <NODE2_NAME>'.$nl[1]->nick.'</NODE2_NAME>
          <KMS>'.$distance.'</KMS>
          <LINK_TYPE>'.$row->link_type.'</LINK_TYPE>
          <STATUS>'.$status.'</STATUS>
          <ogr:geometryProperty><gml:LineString><gml:coordinates>'.$nl[0]->lon.','.$nl[0]->lat.' '.$nl[1]->lon.','.$nl[1]->lat.'</gml:coordinates></gml:LineString></ogr:geometryProperty>
          </dlinks>
          </gml:featureMember>';
        else
          $output .= $row->id.','.$nl[0]->id.','.$nl[0]->nick.','.$nl[1]->id.','.$nl[1]->nick.','.$distance.','.$row->link_type.','.$status.','.$nl[0]->lon.','.$nl[0]->lat.','.$nl[1]->lon.','.$nl[1]->lat."\n";
        
        if ($nl[0]->lon > $maxx) $maxx = $nl[0]->lon;
        if ($nl[0]->lat > $maxy) $maxy = $nl[0]->lat;
        if ($nl[0]->lon < $minx) $minx = $nl[0]->lon;
        if ($nl[0]->lat < $miny) $miny = $nl[0]->lat;
        if ($nl[1]->lon > $maxx) $maxx = $nl[1]->lon;
        if ($nl[1]->lat > $maxy) $maxy = $nl[1]->lat;
        if ($nl[1]->lon < $minx) $minx = $nl[1]->lon;
        if ($nl[1]->lat < $miny) $miny = $nl[1]->lat;
      }   
  }
  drupal_add_http_header('Content-Type: application/xml; charset=utf-8');
  if ($type == 'gml') print '<?xml version="1.0" encoding="utf-8" ?>
<ogr:FeatureCollection
     xmlns:xsi="http://www.w3c.org/2001/XMLSchema-instance"
     xsi:schemaLocation=". dlinks.xsd"
     xmlns:ogr="http://ogr.maptools.org/"
     xmlns:gml="http://www.opengis.net/gml">
  <gml:boundedBy>
    <gml:Box>
<gml:coord><gml:X>'.$minx.'</gml:X><gml:Y>'.$miny.'</gml:Y></gml:coord>
<gml:coord><gml:X>'.$maxx.'</gml:X><gml:Y>'.$maxy.'</gml:Y></gml:coord>
   </gml:Box>
</gml:boundedBy>';
  print $output;
  if ($type == 'gml') print '</ogr:FeatureCollection>';

} // eof function guifi_gml_links()

?>
