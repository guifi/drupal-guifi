<?php
/**
 * @file guifi_nodexchange.inc.php
 */

// All commented on 14/8/2008, to check if it's really being used or not

//function guifi_nodexchange($zoneid,$action = 'help') {
//
//  if ($action == "help") {
//     $zone = db_fetch_object(db_query('SELECT title, nick FROM {guifi_zone} WHERE id = %d',$zoneid));
//     drupal_set_breadcrumb(guifi_zone_ariadna($zoneid));
//     $output = '<div id="guifi">';
//     $output .= '<h2>'.t('Zone %zname%',array('%zname%' => $zone->title)).'</h2>';
//     $output .= '<p>'.t('You must specify which data do you want to export, the following options are available:').'</p>';
//     $output .= '<ol><li>'. l(t('Zones'), "guifi/nodexchange/".$zoneid."/zones", array('title' => t('export zone and zone childs in nodexchange format')) ).'</li>';
//     $output .= '<li>'. l(t('Zones and nodes'), "guifi/nodexchange/".$zoneid."/nodes", array('title' => t('export zones and nodes in nodexchange format (short)')) ).'</li>';
//     $output .= '<li>'. l(t('Detailed'), "guifi/nodexchange/".$zoneid."/detail", array('title' => t('export zones, nodes  and devices in nodexchange format (long)')) ).'</li></ol>';
//     $output .= '<p>'.t('The <a href="/node/3521">nodeXchange</a> is a XML format to interchange network information between services or servers.').'</p>';
//     $output .= '<p>'.t('<b>IMPORTANT LEGAL NOTE:</b> This network information is under the <a href="http://guifi.net/ComunsSensefils/">Comuns Sensefils</a> license, and therefore, available for any other network under the same licensing. If is not your case, you should ask for permission before using it.</a>').'</p>';
//     $output .= "</div>";
//     print theme('page',$output,t('export %zname% in GML format',array('%zname%' => $z->title)));
//     return;
//  }
//
//  function xmltag($ident = 0, $tag, $content, $nl = "\r\n") {
//    if (!empty($content))
//      return str_repeat(' ',2*$ident)."<".$tag.">".htmlspecialchars($content,ENT_QUOTES)."</".$tag.">".$nl;
//  }
//
//  function xmlsummary($ident = 0, $summary = NULL, $nl = "\r\n") {
//    $output = $nl.str_repeat(' ',$ident*2).'<!-- ';
//    if (!empty($summary)) {
//      foreach ($summary as $key => $num) 
//        $output .= ' '.$key.'="'.$num.'"';
//    }
//    return $output.' -->'.$nl;
//  }
//
//  function xmlopentag($ident = 0, $tag, $attributes = NULL, $nl = "\r\n") {
//    $output = $nl.str_repeat(' ',$ident*2).'<'.$tag;
//    if (!empty($attributes)) {
//      foreach ($attributes as $key => $attr) 
//        $output .= ' '.$key.'="'.htmlspecialchars($attr,ENT_QUOTES).'"';
//    }
//    return $output.'>'.$nl;
//  }
//
//  function xmlclosetag($ident = 0, $tag, $nl = "\r\n") {
//    return str_repeat(' ',2*$ident).'</'.$tag.'>'.$nl;
//  }
// 
//  function xmldate($timestamp) {
//    if (empty($timestamp) or ($timestamp == 0))
//      return;
//    return date('Ymd hi',$timestamp);
//  }
//
//  function xmlgraph($ident,$img_src,$graph_url,$graph_title,$nl = "\r\n") {
//    $output = xmlopentag($ident,'graph',array('title' => $graph_title,
//                                              'url' => $graph_url,
//                                              'img_src' => $img_src
//                                             ),$nl);
//    return $output.xmlclosetag($ident,'graph');
//  }
//
//  function services($did, $ident, $nl) {
//
//    $services->count = 0;
//    $services->xml = "";
//    $qservices = db_query("SELECT * FROM {guifi_services} s WHERE s.device_id=%d",$did);
//    while ($s = db_fetch_object($qservices)) {
//      $services->count++;
//      $services->xml .= xmlopentag($ident,'service',array('id' => $did,'title' => $device->title),$nl); 
//      $ident++;
//      $services->xml .= xmltag($ident,'service_type',$s->service_type); 
//      $services->xml .= xmltag($ident,'service_status',$s->status_flag); 
//      $ident--;
//      $services->xml .= xmlclosetag($ident,'service',$nl); 
//      
//    }
//   
//    return $services;
//  }
//
//  function links($iid,$iipv4_id,$ident,$nl) {
//
//    $links->count = 0;
//    $links->xml = "";
//    $qlinks = db_query("SELECT l2.* FROM {guifi_links} l1 LEFT JOIN {guifi_links} l2 ON l1.id=l2.id WHERE l1.device_id<>l2.device_id AND l1.interface_id=%d AND l1.ipv4_id=%d",$iid,$iipv4_id);
//     while ($l = db_fetch_object($qlinks)) {
//      $links->count++;
//      $links->xml .= xmlopentag($ident,'link',array('id' => $l->id,
//						    'linked_device_id' => $l->device_id,
//                                                    'linked_node_id' => $l->nid,
//						    'linked_interface_id' => $l->interface_id,
//                                                    'link_type' => $l->link_type,
//                                                    'link_status' => $l->flag)); 
//      $links->xml .= xmlclosetag($ident,'link',$nl); 
//      
//    }
//   
//    return $links->xml;
//  }
//
//  function interfaces($did,$ident,$nl,$rcounter=NULL) {
//
//    $interfaces->count = 0;
//    $interfaces->xml = "";
//    if ($rcounter == NULL)
//      $qinterfaces = db_query("SELECT i.id,a.id ipv4_id,a.ipv4,a.netmask FROM {guifi_interfaces} i, {guifi_ipv4} a WHERE i.id=a.interface_id AND i.device_id=%d AND i.radiodev_counter is NULL",$did);
//    else
//      $qinterfaces = db_query("SELECT i.id,a.id ipv4_id,a.ipv4,a.netmask FROM {guifi_interfaces} i, {guifi_ipv4} a WHERE i.id=a.interface_id AND i.device_id=%d AND i.radiodev_counter = %d",$did,$rcounter);
//    while ($i = db_fetch_object($qinterfaces)) {
////      print "Interfaces ".$did."-".$rcounter."\n<br />";
////      print_r($i);
////      print "\n<br />";
//      $interfaces->count++;
//      $interfaces->xml .= xmlopentag($ident,'interface',array('id' => $i->id,'ipv4' => $i->ipv4,'mask' => $i->netmask),$nl); 
//      $links=links($i->id,$i->ipv4_id,$ident+1,$nl);
//      $interfaces->xml .= $links;
//      $interfaces->xml .= xmlclosetag($ident,'interface',$nl); 
//      
//    }
//   
//    return $interfaces;
//  }
//
//  function devices($nid,$ident,$nl) {
//    $devices->aps = 0;
//    $devices->count = 0;
//    $qdevices = db_query("SELECT d.* FROM {guifi_devices} d WHERE d.nid=%d",$nid);
//    while ($d = db_fetch_object($qdevices)) {
//         $interfaces = interfaces($d->id,$ident+1,$nl);
//#         $links = links($d->id,$ident+1,$nl);
//         $services = services($d->id,$ident+1,$nl);
//
//         $devices->count++;
//         if ($d->type == 'ADSL') {
//          $variable = unserialize($d->extra);
//          $devices->xml .= xmlopentag($ident,'device',array('id' => $d->id,'title' => $d->nick,
//                                                           'device_type' => $d->type,
//                                                           'device_url' => '/guifi/device/'.$d->id,
//                                                           'rrd_ping' => guifi_rrdfile($d->nick).'_ping',
//                                                           'rrd_traffic' => guifi_rrdfile($d->nick).'_'.$variable['mrtg_index'],
//                                                           'created' => xmldate($d->timestamp_created),
//                                                           'updated' => xmldate($d->timestamp_changed)),
//                                                           $nl); 
//         } else
//          $devices->xml .= xmlopentag($ident,'device',array('id' => $d->id,'title' => $d->nick,
//                                                           'device_type' => $d->type,
//                                                           'device_url' => '/guifi/device/'.$d->id,
//                                                           'rrd_ping' => guifi_rrdfile($d->nick).'_ping',
//                                                           'created' => xmldate($d->timestamp_created),
//                                                           'updated' => xmldate($d->timestamp_changed)),
//                                                           $nl); 
//         $ident++;
//         $devices->xml .= xmlsummary($ident,array('interfaces' => $interfaces->count,'links' => $links->count,'services' => $services->count),$nl); 
//         $devices->xml .= xmltag($ident,'device_description',$d->comment);
//         $devices->xml .= xmltag($ident,'device_status',$r->flag);
//
//         // if device is a radio, then get the radio data and traffic graph
//         if ($d->type == 'radio') {
//           $qr = db_query("SELECT r.*,m.radiodev_max FROM {guifi_radios} r, {guifi_model_specs} m WHERE r.model_id=m.mid AND r.id=%d",$d->id);
//           while ($r = db_fetch_object($qr)) {
//             $ident++;
//             if ($r->radiodev_max == 1)
//               $rrdtraf = guifi_rrdfile($d->nick).'_6';
//             else
//               $rrdtraf = guifi_rrdfile($r->ssid);
//             $interfaces_radio = interfaces($d->id,$ident+1,$nl,$r->radiodev_counter);
//             $devices->xml .= xmlopentag($ident,'radio',array('id' => $r->radiodev_counter,
//                                                           'ssid' => $r->ssid,
//                                                           'mode' => $r->mode,
//                                                           'protocol' => $r->protocol,
//                                                           'channel' => $r->channel,
//                                                           'antenna_angle' => $r->antenna_angle,
//                                                           'antenna_gain' => $r->antenna_gain,
//                                                           'antenna_azimuth' => $r->antenna_azimuth,
//                                                           'rrd_traffic' => $rrdtraf),
//                                                           $nl); 
//             $devices->id = $d->id; 
//             if ($r->mode == 'ap')  
//               $devices->aps++;
//
//             // graphs
//             $devices->xml .= xmlgraph($ident,'/guifi/graph?type=radio&radio='.$d->id.'&start=-86400&end=-300',
//                  '/guifi/graph_detail?type=radio&radio='.$d->id,
//                  'wLan In&Out',$nl);
//             $devices->xml .= $interfaces_radio->xml;
//             $ident--;
//             $devices->xml .= xmlclosetag($ident,"radio",$nl); 
//           }
//         }
//         // availability graph
//         $devices->xml .= xmlgraph($ident,'/guifi/graph?type=pings&radio='.$d->id.'&start=-86400&end=-300',
//                  '/guifi/graph_detail?type=pings&radio='.$d->id,
//                  'Latency &#038; Availability',$nl);
//
//         $devices->xml .= $interfaces->xml;
//         $devices->xml .= $links->xml;
//         $devices->xml .= $services->xml;
//
//         $ident--;
//         $devices->xml .= xmlclosetag($ident,"device",$nl); 
//    }
//
//    return $devices;
//  }
//
//  function nodes($zid,$tree,$ident,$action,$nl) {
//    $nodes->count = 0;
//    $nodes->minx = 180;
//    $nodes->maxx = -180;
//    $nodes->miny = 90;
//    $nodes->maxy = -90;
//
//    if (count($tree->nodes)>0)
//    foreach ($tree->nodes as $nid => $n) {
//
//       if ($action != 'zones')
//       $devices = devices($n->id,$ident+1,$nl);
//
//       $nodes->count++;
//       if ($n->lon > $nodes->maxx) $nodes->maxx = $n->lon;
//       if ($n->lon < $nodes->minx) $nodes->minx = $n->lon;
//       if ($n->lat > $nodes->maxy) $nodes->maxy = $n->lat;
//       if ($n->lat < $nodes->miny) $nodes->miny = $n->lat;
//       switch ($devices->aps) {
//         case 0:  $ntype = 'Client'; break; 
//         case 1:  $ntype = 'AP'; break; 
//         default: $ntype = 'Backbone'; break; 
//       }
//       $nodes->xml .= xmlopentag($ident,'node',array('id' => $n->id,
//                                                     'title' => $n->nick,
//                                                     'type' => $ntype,
//                                                     'lat' => $n->lat,
//                                                     'lon' => $n->lon,
//                                                     'status' => $n->status_flag,
//                                                     'url' => '/node/'.$n->id,
//                                                     'created' => xmldate($n->timestamp_created),
//                                                     'updated' => xmldate($n->timestamp_changed),
//                                                    ),$nl); 
//       $ident++;
//       $nodes->xml .= xmlsummary($ident,array('devices' => $devices->count),$nl); 
//       $nodes->xml .= htmlspecialchars($n->body,ENT_QUOTES);
//
//       // going to list node devices
//       if ($action=='detail')
//         $nodes->xml .= $devices->xml;
//
//       // traffic graphs
//       if ($devices->aps > 0) {
//
//         if ($devices->aps > 1) {
//           // Supernode, consolidate graphs
//           $nodes->xml .= xmlgraph($ident,'/guifi/graph?type=supernode&node='.$n->id.'&direction=in&start=-86400&end=-300',
//                                   '/guifi/graph_detail?type=supernode&node='.$n->id.'&direction=in',
//                                   'Traffic In',$nl);
//           $nodes->xml .= xmlgraph($ident,'/guifi/graph?type=supernode&node='.$n->id.'&direction=out&start=-86400&end=-300',
//                                   '/guifi/graph_detail?type=supernode&node='.$n->id.'&direction=out',
//                                   'Traffic Out',$nl);
//         } else {
//           // Only 1 AP, just wLan in/out & Availability
//           // traffic graph
//           $nodes->xml .= xmlgraph($ident,'/guifi/graph?type=radio&radio='.$device->id.'&start=-86400&end=-300',
//                                   '/guifi/graph_detail?type=radio&radio='.$device->id,
//                                   'wLan In&Out',$nl);
//           $nodes->xml .= xmlgraph($ident,'/guifi/graph?type=pings&radio='.$device->id.'&start=-86400&end=-300',
//                                   '/guifi/graph_detail?type=pings&radio='.$device->id,
//                                   'Latency &#038; Availability',$nl);
//         }
//       }
//
//       $ident--;
//       $nodes->xml .=  xmlclosetag($ident,"node",$nl); 
//    }
//
//    return $nodes;
//  }
//
//  function zone_recurse($zoneid,$tree,$ident, $action, $nl, $total_nodes = 0) {
//     $nodes = nodes($zoneid,$tree,$ident+1,$action,$nl);
//
//     // going to list zone childs
//     $childs->count = 0;
//     $zones->nodes = $nodes->count;
//     $zones->maxx = $nodes->maxx;
//     $zones->minx = $nodes->minx;
//     $zones->maxy = $nodes->maxy;
//     $zones->miny = $nodes->miny;
//     if (count($tree->childs > 0))
//     foreach ($tree->childs as $child) {    
//       $childs->count++;
//       $c = zone_recurse($child->id,$child,$ident+1,$action,$nl,$total_nodes); 
//       $childs->xml .= $c->xml;
//       $zones->nodes = $zones->nodes + $c->nodes;
//       if ($c->maxx > $zones->maxx) $zones->maxx = $c->maxx;
//       if ($c->minx < $zones->minx) $zones->minx = $c->minx;
//       if ($c->maxy > $zones->maxy) $zones->maxy = $c->maxy;
//       if ($c->miny < $zones->miny) $zones->miny = $c->miny;
//     }
//     $zones->count = $child->count + 1;
//
//     $query = db_query("SELECT * FROM {guifi_zone} z WHERE z.id=%d",$zoneid);
//     $zone = db_fetch_object($query);
//     if (($zone->minx != NULL) && ($zone->minx != 0)) $zones->minx = $zone->minx;
//     if (($zone->maxx != NULL) && ($zone->maxx != 0)) $zones->maxx = $zone->maxx;
//     if (($zone->miny != NULL) && ($zone->miny != 0)) $zones->miny = $zone->miny;
//     if (($zone->maxy != NULL) && ($zone->maxy != 0)) $zones->maxy = $zone->maxy;
//
//     $zones->xml = xmlopentag($ident,'zone',array('id' => $zoneid,
//                                                  'title' => $zone->title,
//                                                  'box' => $zones->minx.','.$zones->miny.','.$zones->maxx.','.$zones->maxy,
//                                                  'parent_id' => $zone->master,
//                                                  'url' => $zone->url,
//						  'map_url' => '/node/'.$zoneid.'/view/map',
//                                                  'child_zones' => $childs->count,
//                                                  'zone_nodes' => $nodes->count,
//                                                  'total_nodes' => $zones->nodes),
//                                                  $nl);
//     $ident++;
//     $zones->xml .= htmlspecialchars($zone->body,ENT_QUOTES);
//
//     $zones->xml .= $childs->xml;
//     if ($action != 'zones')
//       $zones->xml .= $nodes->xml;
//
//     $zones->zone_nodes = $nodes->count;
//     $zones->nodes = $total_nodes + $zones->nodes;
//
//     $ident--;
//     $zones->xml .= xmlclosetag($ident,'zone');
//
//     return $zones;
//  }
//
//  global $base_url;
//  
////  if (isset($_GET['ascii']))
////    $nl = "\n";
////  else
////    $nl = "<BR>\n";
//  $nl = "\r\n";
//
////  $query = db_query("SELECT z.title FROM {guifi_zone} z WHERE z.id=%d",$zoneid);
////  $zone = db_fetch_object($query);
//
//  // TODO: load nodes and zones in memory for faster execution
//
//  $tree = guifi_nodexchange_tree($zoneid);
//
////  $nodesquery = db_query('SELECT * FROM {guifi_location}');
////  while ($node = db_fetch_object($nodesquery)) {
////    $zones_tree[$node->zone_id]->nodes[] = $node;
////  }
//
////  print_r($tree);
//
//
////  return;
//
///*  print '<?XML nodeXchange.dtd - guifi version="1.0" zone_id="'.$zoneid.'" title="'.$zone->title.'" ?>'.$nl;
//*/
//  drupal_set_header('Content-Type: application/xml; charset=utf-8');
//  print '<?xml version="1.0" encoding="UTF-8" ?>'.$nl;
//  $zones = zone_recurse($zoneid,$tree[$zoneid],1,$action,$nl);
//  $output = '<network id="'.$zoneid.'" title="'.htmlspecialchars($tree[$zoneid]->title,ENT_QUOTES).'" >'.$nl;
//  $output .= xmlsummary($ident,array('nodes' => $zones->nodes),$nl);
//  $output .= xmltag(1,'network_url',$base_url.'/node/'.$zoneid);
//  $output .= xmltag(1,'network_graphs_base_url',$base_url);
//  $output .= $nl.$zones->xml;
//  $output .= '</network>';
//  print $output;
//  return;
//  
//}

?>
