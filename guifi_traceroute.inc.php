<?php

/**
 * @file guifi_traceroute.inc.php
 * Created on 1/08/2008 by rroca
 * Functions for traceroute tools
 */


function guifi_live_ping($device_id) {
  if (empty($device_id))
    $output = t('Target device not specified.');
  else {
    $gs = guifi_service_load(guifi_graphs_get_server($device_id,'device'));
    $ipd = guifi_main_ip($device_id);
    $ipf = guifi_main_ip($gs->device_id);
  }

  $title = t('Live ping from %from (%ipf) to %dest (%ipd)',
    array('%ipd' => $ipd['ipv4'],
      '%dest' => guifi_get_hostname($device_id),
      '%ipf' => $ipf['ipv4'],
      '%from' => guifi_get_hostname($gs->device_id),
      ));
  drupal_set_title($title);
  print theme('page',guifi_cnml_live('liveping',$device_id,$ipd['ipv4'],$gs), FALSE);
  exit;
}

function guifi_live_traceroute($device_id) {
  if (empty($device_id))
    $output = t('Target device not specified.');
  else {
    $gs = guifi_service_load(guifi_graphs_get_server($device_id,'device'));
    $ipd = guifi_main_ip($device_id);
    $ipf = guifi_main_ip($gs->device_id);
  }

  $title = t('Live traceroute from %from (%ipf) to %dest (%ipd)',
    array('%ipd' => $ipd['ipv4'],
      '%dest' => guifi_get_hostname($device_id),
      '%ipf' => $ipf['ipv4'],
      '%from' => guifi_get_hostname($gs->device_id),
      ));
  drupal_set_title($title);
  print theme('page',guifi_cnml_live('livetraceroute',$device_id,$ipd['ipv4'],$gs), FALSE);
  exit;
}

function guifi_cnml_live($cmd,$device_id,$ipv4,$gs) {
  switch ($cmd) {
  	case 'liveping':
      $output = l('Live traceroute','guifi/menu/ip/livetraceroute/'.$device_id)."<br />";
      $cmd_str = 'Live ping';
      break;
    case 'livetraceroute':
      $output = l('Live ping','guifi/menu/ip/liveping/'.$device_id)."<br />";
      $cmd_str = 'Live traceroute';
      break;
  }
  $handle = fopen(
      guifi_cnml_call_service($gs,$cmd,
        array('ip' => $ipv4)),
      "r");

  if ($handle) {
    $pings =  stream_get_contents($handle);
    fclose($handle);
    $output .= '<pre>'.$pings.'</pre>';
  } else
    $output = t('%cmd failed.',array('%cmd' => $cmd_str));

  return theme('box',t('output'),$output);
}

function guifi_traceroute($path, $to, &$routes, $maxhops = 10,$cost = 0, $alinks = array()) {
  $btime = microtime(TRUE);

  $hop = count($path);
  $kpath = array_keys($path);
  end($path);
  $parent = key($path);

  // if links array not loaded, fill the array
  if (!count($alinks)) {
    $lbegin = microtime(TRUE);
    $qry = db_query('SELECT * FROM {guifi_links} WHERE flag != "Dropped"');
    while ($link = db_fetch_array($qry)) {

      // alinks[devices] will contain all the links for every device
      $alinks['devices'][$link['device_id']][] = $link['id'];

      // alinks[links][link_id] will contain all the information of every link:
      //  [0] => an array of every device and related data
      //     [0] => type
      //     [1] => status
      //     [device_id] => (should have 2, one per peer)
      //                [0] => links with device_id
      //                [1] => node id
      //                [2] => interface_id
      //                [3] => ipv4_id

      if (isset($alinks['links'][$link['id']])) {
        // link data is alredy filled just adding a peer
        // the other peer is the other key

        end($alinks['links'][$link['id']]);
        $peer = key($alinks['links'][$link['id']]);

        // fill counterpart at previous peer
        $alinks['links'][$link['id']][$peer][0] = $link['device_id'];

        // fill the data of this peer
        $alinks['links'][$link['id']][$link['device_id']] = array(
          $peer,
          $link['nid'],
          $link['interface_id'],
          $link['ipv4_id']
        );
      } else
        // first peer of the link, the other peer is still unknown
        $alinks['links'][$link['id']] = array(
          0 => array($link['link_type'],$link['flag']),
          $link['device_id'] => array(
            0,  // peer still unknown
            $link['nid'],
            $link['interface_id'],
            $link['ipv4_id']
          ),
        );
    }
//    print count($alinks['links'])." links loaded in ".
//      number_format(microtime(TRUE)-$lbegin,4).
//      " seconds\n<br />";
  }


//  print "Hop# $hop path ".implode(',',$kpath)." parent $parent looking for ".implode(',',$to)."\n<br />";

//  $qry = db_query(
//    'SELECT l1.*, l2.device_id ddevice_id, l2.ipv4_id dipv4_id, l2.interface_id dinterface_id, l2.nid dnid
//     FROM {guifi_links} l1, {guifi_links} l2
//     WHERE l1.device_id=%d AND l1.id=l2.id AND l2.device_id != %d AND l1.flag != "Dropped" AND l1.flag="Working"',
//     $parent, $parent);

  $c = 0;
  $links = array();
//  while ($linked = db_fetch_object($qry)) {
//    // if loopback, ignore this link
//    if (in_array($linked->ddevice_id,$kpath))
//      continue;
//
//    $links[] = $linked;
//  }

  foreach ($alinks['devices'][$parent] as $lid) {
    $link = $alinks['links'][$lid];
    if (!(count($link) == 3))
      // link ins incomplete, ignore
      continue;

    $dest = $link[$parent][0];

    // if loopback, ignore this link
    if (in_array($link[$parent][0],$kpath))
      continue;

    $c++;

//    print "Linked $parent to $dest \n<br />";

    $npath = $path;
    $npath[$parent]['from'] = array(
      $lid,              // 0 link id
      $link[0][0],       // 1 type
      $link[0][1],       // 2 flag
      $link[$parent][2], // 3 interface_id
      $link[$parent][3], // 4 ipv4_id
      $link[$parent][1]  // 5 node id

    );
    $npath[$dest]['to'] = array(
      $link[$dest][1],   // 0 dest nid
      $link[$dest][2],   // 1 dest interface_id
      $link[$dest][3]    // 2 dest ipv4 id
    );

    $ncost = $cost;
    // calculating the current route cost
    switch ($link[0][0]) {
      case 'cable': $ncost += 1; break;
      case 'wds': $ncost += 5; break;
      default: $ncost += 10; break;
    }
    if ($link[0][1] != 'Working')
      $ncost += 100;

    // if linked device in target destinations, add to routes
    if (in_array($dest,$to)) {
      $routes[] = array($ncost,$npath);
    }

    // if #hops < #maxhops and cost < 200, next hop
    if ((count($npath) < $maxhops) and ($ncost < 200)) {
      $c += guifi_traceroute($npath,$to,$routes,$maxhops,$ncost,$alinks);
    }
  }
  return $c;
}

function guifi_traceroute_search($params = NULL) {

  if (count($params)) {
    $to = explode(',',$params);
    $from = array_shift($to);
  }

  $output = drupal_get_form('guifi_traceroute_search_form',$from,$to);

  if (!count($to))
    return $output;

  if (is_numeric($to[0])) {
    $dto=guifi_get_devicename($to[0],'nick');
  } else {
    $dto=$to[0];
    $qry = db_query('SELECT device_id FROM {guifi_services} WHERE service_type="%s"',
      $to[0]);
    $nto = array();
    while ($service = db_fetch_object($qry))
      $nto[] = $service->device_id;
    $to = $nto;
  }

  $routes = array();
  $btime = microtime(TRUE);
  $explored = guifi_traceroute(array($from => array()),$to,$routes);

  $tracetit = t('%results routes found. %explored routes analyzed in %secs seconds',
    array('%results' => count($routes),
          '%explored' => number_format($explored),
          '%secs' => number_format(microtime(TRUE)-$btime,4)));

  $tracetit .= '<br /><small>'.
   t('Note that this is a software traceroute generated by the information currently available at the database, it might be distinct than the real routes at the network, however this information could be helpful in cleaning the data and network planning.').
   '</small><hr>';

  sort($routes);

  $linkslist = array();
  $nodeslist = array();
  $nroute = 0;
  $trace = '';
  $collapsed = FALSE;

  foreach ($routes as $route) {
    end($route[1]);
    $target = key($route[1]);

    $trace .= theme('fieldset',array(
      '#title' => t('Route from !oname to !dname, !hops hops, cost !cost',
        array(
          '!cost' => $route[0],
          '!oname' => guifi_get_devicename($from,'nick'),
          '!hops' => count($route[1]) - 1,
          '!dname' => guifi_get_devicename($target,'nick'))),
      '#value' => theme_guifi_traceroute($route[1]),
      '#collapsible' => TRUE,
      '#collapsed' => $collapsed
      ));
    $collapsed = TRUE;
    guifi_traceroute_dataexport($route[1],++$nroute,$linkslist,$nodeslist);
  }

  if (guifi_gmap_key()) {
    drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_gmap_traceroute.js','module');
    $datalinks = guifi_export_arraytojs($linkslist);
    $datanodes = guifi_export_arraytojs($nodeslist);
    $lat1=99;$lon1=190;$lat2=-99;$lon2=-190;
    foreach ($nodeslist as $value){
      if ($value[lat]<$lat1){$lat1=$value[lat];}
      if ($value[lon]<$lon1){$lon1=$value[lon];}
      if ($value[lat]>$lat2){$lat2=$value[lat];}
      if ($value[lon]>$lon2){$lon2=$value[lon];}
    }
    if ($lat1==99){$lat1='NA';$lon1='NA';$lat2='NA';$lon2='NA';}
    $tracetit .=  '<form>' .
        '<input type=hidden value='.$lat1.' id=lat />'.
        '<input type=hidden value='.$lon1.' id=lon />' .
        '<input type=hidden value='.$lat2.' id=lat2 />'.
        '<input type=hidden value='.$lon2.' id=lon2 />' .
        '<input type=hidden value="'.$datalinks.'" id=datalinks />' .
        '<input type=hidden value="'.$datanodes.'" id=datanodes />' .
        '<input type=hidden value='.base_path().drupal_get_path('module','guifi').'/js/'.' id=edit-jspath />' .
        '<input type=hidden value='.variable_get('guifi_wms_service','').' id=guifi-wms />' .
        '</form>';

    $tracetit .= drupal_get_form('guifi_traceroute_map_form');
    $tracetit .= '<div id="map" style="width: 100%; height: 600px; margin:5px;"></div>';
  }

  $output .= theme('box',t('Software traceroute result from %from to %to',
    array('%from' => guifi_get_devicename($from,'nick'),'%to' => $dto)),$tracetit.$trace);

  guifi_log(GUIFILOG_TRACE,'Routes',$routes);

  return $output;
}
 
function guifi_traceroute_map_form($form_state) { //Eduard
  $vtext = t('Route Level: Importance of the route depending on the cost and proximity to the main route').'<br />';
  $vtext .= t('main').':<img src="'.base_path().drupal_get_path('module','guifi').'/js/marker_traceroute_icon1.png"/>';  
  for($i=2;$i<=10;$i++){
    $vtext .='&nbsp;&nbsp;&nbsp;&nbsp;'.$i.':<img src="'.base_path().drupal_get_path('module','guifi').'/js/marker_traceroute_icon'.$i.'.png"/>';
  }
  $form['#action'] = '';
  $form['formmap3'] = array(
    '#type' => 'button',
    '#name' => 'btnrouteright',
    '#value' => '>>',
    '#attributes' => array('onclick'=> 'return printroute(1)'),
    '#prefix' => '<div style="float:right;text-align:right;width:200px;"><div style="float:right;margin-left:8px;margin-top:0px">',
    '#suffix' => '</div>'
  );
  $form['formmap2'] = array(
    '#type' => 'textfield',
    '#name' => 'texroute',
    '#default_value' => 0,
    '#size' => 4,
    '#attributes' => array('style' => 'text-align:right;'),
    //'#prefix' => '<div style="float:right;margin-left:8px;margin-top:-14px">',
    '#prefix' => '<div style="float:right;margin-left:0px;margin-top:13px">',
    '#suffix' => '</div>'
  );
  $form['formmap1'] = array(
    '#type' => 'button',
    '#name' => 'btnrouteleft',
    '#value' => '<<',
    '#attributes' => array('onclick' => 'return printroute(-1)'),    
    '#prefix' => '<div style="float:right;">',
    '#suffix' => '</div></div><div>'.$vtext.'</div>'
  );

  return $form;
}

// IP search
function guifi_traceroute_search_form($form_state, $from = NULL, $to = array()) {

  $ftitle = t('From:');
  if ($from) {
    $fname = guifi_get_devicename($from,'large');
    $ftitle .= ' '.$fname;
  }

  $search_help = t('To find the device, you can write some letters to find the available devices in the database.');
  $form['from'] = array(
    '#type' => 'fieldset',
    '#title' => $ftitle,
    '#collapsible' => TRUE,
    '#collapsed' => $from
  );
  $form['from']['from_description'] = array(
    '#type' => 'textfield',
    '#title' => t('Device'),
    '#required' => TRUE,
    '#default_value' => $fname,
    '#size' => 60,
    '#maxlength' => 128,
    '#autocomplete_path'=> 'guifi/js/select-node-device',
    '#element_validate' => array('guifi_devicename_validate'),
    '#description' => t('Search for a device to trace the route from.').'<br />'.
        $search_help,
  );

  $dtitle = t('To:');
  if (count($to)) {
    if (is_numeric($to[0])) {
      $dname = guifi_get_devicename($to[0],'large');
      $dservice = '';
      $dtitle .= ' '.$dname;
    } else {
      $dservice = $to[0];
      $dname = '';
      $dtitle .= ' '.t('Explore service !service',array('!service' => $to[0]));
    }
  }

  $form['to'] = array(
    '#type' => 'fieldset',
    '#title' => $dtitle,
    '#collapsible' => TRUE,
    '#collapsed' => count($to),
    '#description' => t('Choose between tracing a route to a known device, or discover services by selecting a service type. Yo use only one option at a time, and then press the <em>"Get traceroute"</em> button to get the list of possible known routes ordered by best route first.')
  );
  $form['to']['to_description'] = array(
    '#type' => 'textfield',
    '#title' => t('Device'),
    '#default_value' => $dname,
    '#size' => 60,
    '#maxlength' => 128,
    '#element_validate' => array('guifi_devicename_validate'),
    '#autocomplete_path'=> 'guifi/js/select-node-device',
    '#description' => t('Target device to trace the route to.').'<br />'.
        $search_help,
    '#prefix' => '<table><tr><td>',
    '#suffix' => '</td>',
  );
  $types[] = t('<select one from this list>');
  $form['to']['discover_service'] = array(
    '#type' => 'select',
    '#title' => t("Service"),
    '#default_value' => $dservice,
    '#options' => array_merge($types,guifi_types('service')),
    '#description' => t('Type of service to be discovered'),
    '#prefix' => '<td>',
    '#suffix' => '</td>',
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Get traceroute'),
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
  );

  return $form;
}

function guifi_traceroute_search_form_validate($form, $form_state) {
  if ((!$form_state['values']['discover_service']) and
       ($form_state['values']['to_description'] == '')
     )
    form_set_error('discover_service', t('You must select either a service to discover or a device destination'));
  if (($form_state['values']['discover_service']) and
       ($form_state['values']['to_description'] != '')
     )
    form_set_error('to_description', t('You must select a service to discover or a device destination, not both'));
}

function guifi_traceroute_search_form_submit($form, &$form_state) {
   $from = explode('-',$form_state['values']['from_description']);
   ($form_state['values']['to_description'] != '') ?
     $to = explode('-',$form_state['values']['to_description'])
     : $to = array($form_state['values']['discover_service']);

   drupal_goto('guifi/menu/ip/traceroute/'.$from[0].','.$to[0]);
   return;
}

function theme_guifi_traceroute($route) {
  $oGC = new GeoCalc();
  $tDist = 0;

  foreach ($route as $did => $hop) {
    $cols = array();
    $cols[] = l(guifi_get_devicename($did,'nick'),'guifi/device/'.$did);
    if (isset($hop['to'])) {
      $cols[] = l(guifi_get_nodename($hop['to'][0]),'node/'.$hop['to'][0]);
      $ip = db_fetch_object(db_query(
        'SELECT ipv4, netmask FROM {guifi_ipv4} WHERE id=%d AND interface_id=%d',
        $hop['to'][2],$hop['to'][1]));
      $cols[] = $ip->ipv4.'/'.guifi_ipcalc_get_maskbits($ip->netmask);
    } else {
      $cols[] = array('data' => NULL,'colspan' => 2);
    }


    if (isset($hop['from'])) {
      $ip = db_fetch_object(db_query(
        'SELECT ipv4, netmask FROM {guifi_ipv4} WHERE id=%d AND interface_id=%d',
        $hop['from'][4],$hop['from'][3]));
      $cols[] = $ip->ipv4.'/'.guifi_ipcalc_get_maskbits($ip->netmask);
      $cols[] = $hop['from'][1];     // type
                                     // status
      $cols[] = array('data' => t($hop['from'][2]),'class' => $hop['from'][2]);
    } else {
      $cols[] = array('data' => NULL,'colspan' => 3);
    }

    // if not same location, give the distance
    if ($hop['from'][1] != 'cable') {
      $qry = db_query(
        'SELECT n.id nid, lat, lon
         FROM {guifi_location} n, {guifi_links} l
         WHERE l.id=%d
           AND l.nid=n.id',
         $hop['from'][0]);
      $loc1 = db_fetch_object($qry);
      $loc2 = db_fetch_object($qry);
      $gDist = round($oGC->EllipsoidDistance($loc1->lat, $loc1->lon, $loc2->lat, $loc2->lon),3);

      if ($gDist) {
        $cols[] = array('data' => $gDist,'align' => right);
        $tDist += $gDist;
      }
    }


    $rows[] = $cols;
  }
  $rows[] = array(array('data'=>
    t('Total distance %tDist kms., %hops hops',
      array('%tDist' => $tDist,'%hops' => count($route) - 1)),
    'colspan' => 0)
  );

  $header = array(
    t('Device'),
    t('Node'),
    t('In address'),
    t('Out address'),
    t('Type'),
    t('Status'),
    t('Kms')
  );
  return theme('table',$header,$rows);

}

function guifi_traceroute_dataexport($route,$nRoute,&$linkslist,&$nodeslist) {
  $oGC = new GeoCalc();
  $tDist = 0;
  $nLink = 0;
  $nReg = 0;
  $output = '';
  foreach ($route as $did => $hop) {

    if (isset($hop['to'])) {
      $linkslist[$nReg]['todevicename'] = guifi_get_devicename($did,'nick');
      $linkslist[$nReg]['todevicelink'] = 'guifi/device/'.$did;
      $linkslist[$nReg]['tonode'] = $hop['to'][0];
      $ip = db_fetch_object(db_query(
        'SELECT ipv4, netmask FROM {guifi_ipv4} WHERE id=%d AND interface_id=%d',
        $hop['to'][2],$hop['to'][1]));
      $linkslist[$nReg]['toipv4'] = $ip->ipv4.'/'.guifi_ipcalc_get_maskbits($ip->netmask);
      if (!isset($nodeslist[$hop['to'][0]])) {
        $nodeslist[$hop["to"][0]]=guifi_get_location($hop["to"][0]);
        $nodeslist[$hop["to"][0]][nodename]=guifi_get_nodename($hop['to'][0]);
        $nodeslist[$hop["to"][0]][nodelink]=$hop['to'][0];
      }
    }


    if (isset($hop['from'])) {
      $nLink++;
      $nReg = $nRoute*100+$nLink;
      $linkslist[$nReg]['route'] = $nRoute;
      $linkslist[$nReg]['nlink'] = $nLink;
      $linkslist[$nReg]['idlink'] = $hop['from'][0];
      $linkslist[$nReg]['fromdevicename'] = guifi_get_devicename($did,'nick');
      $linkslist[$nReg]['fromdevicelink'] = 'guifi/device/'.$did;
      $linkslist[$nReg]['fromnode'] = $hop['from'][5];
      $ip = db_fetch_object(db_query(
        'SELECT ipv4, netmask FROM {guifi_ipv4} WHERE id=%d AND interface_id=%d',
        $hop['from'][4],$hop['from'][3]));
      $linkslist[$nReg]['fromipv4'] = $ip->ipv4.'/'.guifi_ipcalc_get_maskbits($ip->netmask);
      $linkslist[$nReg]['type'] = $hop['from'][1];
      $linkslist[$nReg]['status'] = $hop['from'][2];
      if (!isset($nodeslist[$hop['from'][5]])) {
        $nodeslist[$hop["from"][5]]=guifi_get_location($hop["from"][5]);
        $nodeslist[$hop["from"][5]][nodename]=guifi_get_nodename($hop['from'][5]);
        $nodeslist[$hop["from"][5]][nodelink]=$hop['from'][5];
      }
    }

    // if not same location, give the distance
    if ($hop['from'][1] != 'cable') {
      $qry = db_query(
        'SELECT n.id nid, lat, lon
         FROM {guifi_location} n, {guifi_links} l
         WHERE l.id=%d
           AND l.nid=n.id',
         $hop['from'][0]);
      $loc1 = db_fetch_object($qry);
      $loc2 = db_fetch_object($qry);
      $gDist = round($oGC->EllipsoidDistance($loc1->lat, $loc1->lon, $loc2->lat, $loc2->lon),3);

      if ($gDist) {
        $linkslist[$nReg]['distance'] = $gDist;
      }
    }
  }
  return $output;
}

/*
 * guifi_export_arraytojs converts an array to string to import in js
 */
function guifi_export_arraytojs($array){
 $output = '';
  foreach ($array as $key => $value){
    if ($output != ''){
      $output .= ',';
    }
    if (is_array($value)){
      $output .= $key.":";
      $output .= guifi_export_arraytojs($value);
    }else{
      if (is_numeric($value)){
        $output .= $key.":".$value;
      }else{
        $output .= $key.":'".$value."'";
      }
    }
  }
  $output ='{'.$output.'}';
  return $output;
}
?>
