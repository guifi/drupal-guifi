<?php

/**
 * @file guifi_includes.inc.php
 * Miscellaneous and auxiliar routines for guifi.module
 */

/**
 * _guifi_validate_mac validates a MAC address
 *
*/
function _guifi_validate_mac($mac) {
//  if (($mac == '00:00:00:00:00:00') || ($mac == FALSE) || ($mac == NULL))
  if (($mac == FALSE) || ($mac == NULL))
    return FALSE;
  $mac = str_replace(":","",$mac);
  $mac = str_replace("-","",$mac);
  $mac = str_replace("/","",$mac);
  $mac = str_replace(" ","",$mac);
  $mac = str_replace(".","",$mac);
  if (strlen($mac) != 12)
    return FALSE;
  foreach (explode(':',substr(chunk_split($mac,2,':'),0,17)) as $item) {
    if (($item != '00') && (hexdec($item) == 0))
      return FALSE;
  }
  return strtoupper(substr(chunk_split($mac,2,':'),0,17));
}

function _guifi_mac_sum($mac,$sum) {
  $mac = str_replace(":","",$mac);
  $dec = hexdec($mac) + $sum;
  $smac = str_pad(dechex($dec),12,'0',STR_PAD_LEFT);
  return strtoupper(substr(chunk_split($smac,2,':'),0,17));
}

function guifi_img_icon($i) {
  global $base_url;
  return '<img src="'.$base_url.'/'.drupal_get_path('module', 'guifi').'/icons/'.$i.'" >';
}

/**
 * guifi_rrdfile
**/

function guifi_rrdfile($nick) {
   return str_replace(array (' ','.','-','?','&','%','$'),"",strtolower($nick));
}

/**
 * guifi_traffic_rrdfile
**/

function guifi_traffic_rrdfile($nick,$mrtg_index = '') {
   if (mrtg_index != '')
     return variable_get('rrddb_path','/home/comesfa/mrtg/logs/').guifi_rrdfile($nick).'_'.$mrtg_index.'.rrd';
   else
     return variable_get('rrddb_path','/home/comesfa/mrtg/logs/').guifi_rrdfile($nick).'.rrd';
}

/**
 * _guifi_tostrunits convert a number to string format in B,KB,MB...
**/
function _guifi_tostrunits($num) {
  $base = array('B','KB','MB','GB','TB','PB');
  $str = sprintf("%3d B",$num);
  foreach ($base as $key => $unit) {
    if ($num > pow(1024,$key))
      $str = sprintf("%7.2f %s",$num/pow(1024,$key),$unit);
    else
      return $str;
  }
}

function guifi_main_ethernet($device_id) {
  $Qi= db_query(
     'SELECT i.id
      FROM {guifi_interfaces} i
      WHERE i.device_id = :did
      ORDER BY radiodev_counter,etherdev_counter,id',
      array(':did' => $device_id))->fetchObject();

  if (empty($Qi->id))
    return false;
  return $Qi->id;
}

function guifi_main_interface($mode = NULL) {
  switch ($mode) {
    case 'ap': return 'wLan/Lan';
    case 'client': return 'Wan';
    case NULL: return 'Lan';
    default: return 'Lan';
  }
}

function guifi_main_ip($device_id) {
  $qryipv4 = db_query('SELECT mainipv4 from {guifi_devices} where id = :did', array(':did' => $device_id))->fetchObject();
  if ($qryipv4->mainipv4) {
   $devmainipv4 = explode(',',$qryipv4->mainipv4);
   $ipv4 = db_query('SELECT ipv4, netmask from {guifi_ipv4} where id = :id AND interface_id = :iface_id', array(':id' => $devmainipv4['1'], ':iface_id' => $devmainipv4['0']))->fetchObject();
   $item = _ipcalc($ipv4->ipv4,$ipv4->netmask);
   $ipv4arr = array('ipv4' => $ipv4->ipv4,'maskbits' => $item['maskbits'], 'netmask' => $ipv4->netmask);
   return $ipv4arr;
  }
  else {
    $qips = db_query('SELECT a.ipv4,a.netmask,a.id, i.interface_type
        FROM {guifi_interfaces} i LEFT JOIN {guifi_ipv4} a ON a.interface_id=i.id
        WHERE i.device_id = :did ORDER BY a.id', array(':did' => $device_id));
    $ip_array=array();
    while ($ip = $qips->fetchObject()) {
      if ($ip->ipv4 != NULL) {
        $item = _ipcalc($ip->ipv4,$ip->netmask);
        $ipv4arr = array('ipv4' => $ip->ipv4,'maskbits' => $item['maskbits'], 'netmask' => $ip->netmask);
        switch ($ip->interface_type) {
        case 'wLan/Lan':
          $ip_array[0+$ip->id]=$ipv4arr; break;
        case 'Lan':
          $ip_array[100]=$ipv4arr; break;
        case 'Wan':
          $ip_array[200]=$ipv4arr; break;
        case 'wLan':
          if (!isset($ip_array[3])) $ip_array[300]=$ipv4arr; break;
        case 'wds/p2p':
          if (!isset($ip_array[4])) $ip_array[400]=$ipv4arr; break;
        }
      }
    }
    ksort($ip_array);
    reset($ip_array);
    return current($ip_array);
  }
}

function guifi_types($type, $start = 24, $end = 0, $relations = NULL) {
  if ($type == 'netmask') {
    for ($n = $start; $n > $end; $n--) {
      $item = _ipcalc_by_netbits('0.0.0.0',$n);
      $masks[$item['netmask']] = $item['netmask'].' - '.$item['hosts'].' '.t('hosts');
    }
    if ($end == 0)
      $masks['0.0.0.0'] = t('0 - all hosts');
    return $masks;
  }

  $values = array();

  if ($type == 'firmware') {
    $query = db_query("select
          usc.id,
          usc.mid,
          usc.fid,
          f.nom as name,
          f.descripcio as description
        from
          guifi_firmware f
          inner join
        guifi_configuracioUnSolclic usc ON usc.fid = f.id  and usc.mid = :relations
        order by name asc", array(':relations' => $relations));
        while ($type = $query->fetchAssoc()) {
          $values[] = $type;
        }
        return $values;
  }

  if ($relations == NULL)
    $query = db_query("SELECT text, description FROM {guifi_types} WHERE type = :type ORDER BY id",array(':type' => $type));
  else
    $query = db_query("SELECT text, description FROM {guifi_types} WHERE type = :type AND RELATIONS LIKE :relations ORDER BY id",array(':type' => $type, ':relations' => '%'.$relations.'%'));
  //foreach ($query->fetchObject() as $type) {
    while ($type = $query->fetchObject()) {
    $values[$type->text] = t($type->description);
  }
  return $values;
}

function guifi_validate_types($type, $text, $relations = NULL) {
  if ($relations == NULL) {
    $query = db_query("SELECT COUNT(*) AS count FROM {guifi_types} WHERE type='%s' AND text = '%s' ORDER BY id", $type, $text);
  }
  else {
    $query = db_query("SELECT COUNT(*) AS count FROM {guifi_types} WHERE type='%s' AND text = '%s' AND relations LIKE '%s' ORDER BY id", $type, $text, "%$relations%" );
  }
  $count = $query->fetchObject();

  return $count->count > 0;
}


function guifi_get_mac($id,$itype) {
  $dev = db_query("SELECT mac from {guifi_devices} WHERE id = :id",array(':id' => $id))->fetchObject();
  $mac = db_query("SELECT relations FROM {guifi_types} WHERE type='interface' AND text = :itype", array(':itype' => $itype))->fetchObject();

//  print "Assign MAC: ".$id."-".$itype." Device: ".$dev->id." op ".$mac->relations."\n<br />";

  if (!empty($dev->mac))
    return _guifi_mac_sum($dev->mac,$mac->relations);
  else
    return NULL;
}

function guifi_get_model_specs($mid) {
  $m = db_query('select * from {guifi_model_specs} where mid = :mid',array(':mid' => $mid))->fetchObject();

  $m->ethernames = explode('|',$m->interfaces);
  for ($i=0; $i < $m->etherdev_max; $i++)
    if (empty($m->ethernames[$i]))
      $m->ethernames[$i]=$i+1;

  $m->ethermax =count($m->ethernames);

  if ((empty($m->optodev_max) or !(strpos($m->model_class,'fiber'))))
    return $m;

  $m->optonames = explode('|',$m->opto_interfaces);

  // if opto interfaces present, check if overlaps the ethernet interfaces
  if (count($m->optonames)) {
    $m->ethernames = array_diff($m->ethernames, $m->optonames);
    $m->ethernames = array_merge($m->ethernames, $m->optonames);
    if ($m->ethermax < count($m->ethernames))
      // There are NO ports overlapped between cooper & optics, so total is the aggregation of both
      $m->ethermax = count($m->ethernames);
  }
  return $m;
}

/*
 * guifi_type_relation()
 * Validates if a relationship is valid or not
 *
 * @type type code
 * @subject type code of the subject to check
 * @related type code of the relationship to be checked
 *
 * @return TRUE if relation is valid,m FALSE if is invalid
*/
function guifi_type_relation($type,$subject,$related) {
  $relations = db_query("SELECT text, relations FROM {guifi_types} WHERE type = :type AND text = :text",array(':type' => $type,':text' => $subject))->fetchObject();
  $pattern = str_replace("/","\/",$relations->relations);

//  print "preg_match: ".$pattern." ".$subject." related ".$related." relations ".$re"\n<br />";
  return preg_match("/(".$pattern.")/",$related);
}

function guifi_form_column($form) {
  return "  <td>\r  ".$form."\r  </td>\r";
}

function guifi_form_column_group($title,$form,$help) {
  return form_group($title,"\n<table>\r  <tr>\r  ".$form."  </tr>\r</table>\n",$help);
}

function guifi_url($url,$text = NULL) {
  if ($text == NULL)
   $text = $url;
  if (!preg_match("/^http:\/\//",$url))
    $url = 'http://'.$url;
  return '<a href="'.$url.'">'.$text.'</a>';
}

function guifi_server_descr($did) {
  $server = db_query('SELECT CONCAT(d.id,\'-\',z.nick,\', \',l.nick,\' \',d.nick) descr ' .
      'FROM {guifi_devices} d, {guifi_location} l, {guifi_zone} z ' .
      'WHERE d.id = :id ' .
      ' AND d.type IN (\'server\',\'cam\',\'cloudy\') '.
      ' AND d.nid=l.id' .
      ' AND l.zone_id=z.id',
      array(':id' => $did))->fetchObject();

  return $server->descr;
}

/***
 * funtion _set_value
 * called by guifi_devices_select to populate list of values
**/
function _set_value($device,$node,&$var,$id,$rid,$search) {
  $prefix = '';

  if (isset($device->radiodev_counter))
    $ql = db_query('SELECT l1.id FROM {guifi_links} l1 LEFT JOIN {guifi_interfaces} i1 ON l1.interface_id=i1.id LEFT JOIN {guifi_links} l2 ON l1.id=l2.id LEFT JOIN {guifi_interfaces} i2 ON l2.interface_id=i2.id WHERE l1.device_id = :did AND i1.radiodev_counter= :rc AND l2.device_id = :2did AND i2.radiodev_counter= :2rc', array(':did' => $device->id, ':rc' => $device->radiodev_counter, ':2did' => $id, ':2rc' => $rid));
  else
    $ql = db_query('SELECT l1.id FROM {guifi_links} l1 LEFT JOIN {guifi_links} l2 ON l1.id=l2.id WHERE l1.device_id = :did AND l2.device_id = :2did', array(':did' => $device->id, ':2did' => $id));

  if ($ql->fetchField() > 0)
    // link already exists
    return;

  if ((!user_access('administer guifi zones') and ($device->clients_accepted=='No')))
    // backhaul and not zone administrator, can't link to backhaul nodes
    return;

  if ($device->clients_accepted == 'No')
    $backhaul = '**'.t('backbone').'**';

  $zone = db_query('SELECT title FROM {guifi_zone} WHERE id = :zid', array(':zid' => $device->zone_id))->fetchObject();
  if ($device->distance) {
    $value= $zone->title.', '.$device->ssid.$backhaul.
      '<a href="http://www.heywhatsthat.com/bin/profile.cgi?axes=1&curvature=0&metric=1' .
      '&pt0='.$node->lat.','.$node->lon.',ff0000' .
      '&pt1='.$device->lat.','.$device->lon.',00c000" ' .
      'title="Click to check for line of sight" '.
      'target="_blank">' .
      ' ('.$device->distance.' '.t('kms').') '.
    '</a>';
    if ($device->fund_required) {
      $value .= t('Contribution for coverage').': '.number_format($device->fund_amount,2,',','.').
                ' '.$device->fund_currency;
    }
  } else
    $value= $zone->title.', '.$device->ssid;

  if (($search != NULL) and
     (!stristr($value,$search)))
    return;


  if (isset($device->radiodev_counter))
    $var[$device->nid.','.$device->id.','.$device->radiodev_counter] = $value;
  else
    $var[$device->nid.','.$device->id] = $value;

} // eof function _set_value


/***
 * function guifi_devices_select
 * pupulates an array to be used as a select list for selecting WDS/p2p, cable or ap/client connections
***/
function guifi_devices_select($filters, $action = '') {
  guifi_log(GUIFILOG_TRACE,'function guifi_devices_select()',$filters);

  $var = array();
  $found = FALSE;

  if ($filters['type'] == 'cable') {
    if ($filters['mode'] != 'cable-router') {
      $query = '
        SELECT
          l.lat, l.lon, r.nick ssid, r.id, r.nid, z.id zone_id
        FROM {guifi_devices} r,{guifi_location} l, {guifi_zone} z
        WHERE
          l.id = :id
          AND r.nid=l.id
          AND l.zone_id=z.id';
    } else {
      $query = '
        SELECT
          l.lat, l.lon, r.nick ssid, r.id r.nid, z.id zone_id, r.type,
          r.fund_required, r.fund_amount, r.fund_currency
        FROM {guifi_devices} r,{guifi_location} l, {guifi_zone} z
        WHERE r.type IN (\'radio\',\'nat\')
          AND l.id = :id AND r.nid=l.id
          AND l.zone_id=z.id';
    }
  } else {
    $query = '
      SELECT
        l.lat, l.lon, r.id, r.clients_accepted, r.nid, z.id zone_id,
        r.radiodev_counter, r.ssid, r.mode, r.antenna_mode,
        r.fund_required, r.fund_amount, r.fund_currency
      FROM {guifi_radios} r,{guifi_location} l, {guifi_zone} z
      WHERE l.id <> :id
        AND r.nid=l.id
        AND l.zone_id=z.id';
  }

  $devdist = array();
  $devarr = array();
  $k = 0;
  $devsq = db_query($query, array(':id' => $filters['from_node']));

  while ($device = $devsq->fetchObject()) {
    $k++;
    $l = FALSE;
    if ($filters['type']!='cable') {
      $oGC = new GeoCalc();
      $node = db_query('
        SELECT lat, lon
        FROM {guifi_location}
        WHERE id = :from',
        array(':from' => $filters['from_node']))->fetchObject();
      $distance = round( $oGC->EllipsoidDistance($device->lat, $device->lon, $node->lat, $node->lon), 3);
      if (($distance > $filters['dmax']) or ($distance < $filters['dmin'])) {
        continue;
      }
      if ($filters['azimuth']) {
        foreach (explode('-', $filters['azimuth']) as $minmax) {
          list($min, $max) = explode(',', $minmax);
          $Az = round($oGC->GCAzimuth($device->lat, $device->lon, $node->lat, $node->lon));
          if (($Az <= $max) and ($Az >= $min)) {
            $l = TRUE;
          }
        }
      } else {
        $l = TRUE;
      }
    }
    if ($l) {
      $devdist[$k] = $distance;
      $devarr[$k] = $device;
      $devarr[$k]->distance = $distance;
    }
  }

  asort($devdist);

//  ob_start();
//  print "Query: $query \n<br />";
//  print_r($devdist);
//  $txt = ob_get_contents();
//  ob_end_clean();


  if (!empty($devdist)) foreach ($devdist as $id => $foo) {
    $device = $devarr[$id];

    switch ($filters['type']) {
      case 'ap/client':
          if (($filters['mode'] == 'ap') and ($device->mode == 'client')) {
            $cr = guifi_count_radio_links($device->id);
            if ($cr['ap'] < 1) {
              _set_value($device, $node, $var, $filters['from_device'], $filters['from_radio'], $filters['search']);
            }
          } else
          if (($filters['mode'] == 'client') and ($device->mode == 'ap')) {
            _set_value($device, $node, $var, $filters['from_device'], $filters['from_radio'], $filters['search']);
          }
        break;
      case 'wds':
        if ($device->mode == 'ap')
          _set_value($device, $node, $var, $filters['from_device'], $filters['from_radio'], $filters['search']);
        break;
      case 'cable':
          _set_value($device, $node, $var, $filters['from_device'], $filters['from_radio'], $filters['search']);
        break;
      } // eof switch link_type
  } // eof while query device,node,zone

  $form = array(
    '#type' => 'fieldset',
 //   '#title' => t('filters'),
 //   '#weight' => 0,
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
 //   '#weight' => $fweight++,
    '#prefix' => '<div id="list-devices">',
    '#suffix' => '</div>',
  );

  if (count($var) == 0) {
    $form['d'] = array(
      '#type' => 'item',
      '#parents'=> array('dummy'),
      '#title' => t('No devices available'),
      '#value'=> t('There are no devices to link within the given criteria, you can use the filters to get more results.'),
//      '#description' => t('...or press "Ignore & Back to the Main Form" to dismiss.'),
//      '#description' => $txt.'<br />'.$action,
//      '#prefix' => '<div id="list-devices">',
//      '#suffix' => '</div>',
    );
    $form['dbuttons'] = guifi_device_buttons(TRUE, $action, 0);
    return $form;
  }

  $form['d'] = array(
    '#type' => 'radios',
    '#parents'=> array('linked'),
    '#title' => t('select the device which do you like to link with'),
    '#options' => $var,
//    '#description' => $txt.'<br />'.$action,
    '#attributes' => array('class' => 'required'),

//    '#description' => t('If you save at this point, link will be created and information saved.'),
//    '#prefix' => '<div id="list-devices">',
//    '#suffix' => '</div>',
  );

  $form['dbuttons'] = guifi_device_buttons(TRUE,$action,1);

  return $form;

}

function guifi_get_all_interfaces($id,$type = 'radio', $db = TRUE) {
  if (($db) and ($type == 'radio'))
    $model = db_query('SELECT m.interfaces FROM {guifi_radios} r LEFT JOIN {guifi_model_specs} m ON m.mid=r.model_id WHERE r.id = :id', array(':id' => $id))->fetchAssoc();
  else
    $model[interfaces] = 'Lan';
  return explode('|',$model[interfaces]);
}


function guifi_get_possible_interfaces($edit = array()) {
  if ($edit['type'] == 'radio')
    $model = db_query('
      SELECT m.interfaces
      FROM {guifi_model_specs} m
      WHERE mid = :mid',
    array(':mid' => $edit['variable']['model_id']))->fetchAssoc();
  else
    $model['interfaces'] = 'Lan';
  $possible = explode('|',$model['interfaces']);
  $possible[] = 'other';

  return $possible;
}

/** guifi_get_device_interfaces(): Populates a select list with the available cable interfaces 
 *
 * prameters:
 *   id: device_id
 *   iid: current interface
 * @return list of device free cable interfaces in an array
 */
function guifi_get_device_interfaces($id,$iid = NULL) {

  $used = array(''=>t('Not defined'));

  if (empty($id))
    return $used;

  if (empty($iid))
    $iid = 0;

  guifi_log(GUIFILOG_TRACE,'guifi_get_device_interfaces(id - iid)',$id.' - '.$iid);
  guifi_log(GUIFILOG_TRACE,'guifi_get_device_interfaces(iid)',$id);

  $did = explode('-',$id);

  if (!is_numeric($did[0]))
    return $used;
    
  $sql_i = '
    SELECT id, interface_type, connto_iid
    FROM {guifi_interfaces}
    WHERE device_id = ' .$did[0];

  $sql_i .= 
    ' AND ((radiodev_counter is NULL or radiodev_counter = 0) or (upper(interface_type) IN ("WLAN/LAN"))) 
      AND (interface_class is NULL or interface_class = "ethernet" )';
      
  guifi_log(GUIFILOG_TRACE,'guifi_get_devicename(sql)',$sql_i);

  $qi = db_query($sql_i);

  while ($i = $qi->fetchObject()) {
    if ( ((empty($i->connto_did)) and (empty($i->connto_iid)) ) or ($i->id == $iid)) {
      $trimmed_itype = trim($i->interface_type);
      if (!empty($trimmed_itype))
        $used[$i->id] = $i->interface_type;
    }
  }

  return $used;
}

/** guifi_get_device_allinterfaces(): Populates a select list with all the available interfaces 
 *
 * prameters:
 *   id: device_id
 * @return list of device interfaces in an array
 */
function guifi_get_device_allinterfaces($id) {
  
  $allinterfaces = array();
  
  if (empty($id))
    return $allinterfaces;

  if (empty($iid))
    $iid = 0;

  $did = explode('-',$id);

  if (!is_numeric($did[0]))
    return $allinterfaces;
    
  $sql_i = '
    SELECT id, interface_type
    FROM {guifi_interfaces}
    WHERE device_id = ' .$did[0];
      
  $qi = db_query($sql_i);

  while ($i = $qi->fetchObject()) 
    $allinterfaces[$i->id] = $i->interface_type;

  return $allinterfaces;
}


function guifi_get_firmware($id) {
  $sql= db_query('
      SELECT *
      FROM {guifi_firmware}
      WHERE id = :id
       OR nom = :name', array(':id' => $id,':name' => $id));
  $firmware = $sql->fetchObject();
  guifi_log(GUIFILOG_TRACE,'function guifi_get_firmware(firmware)',$firmware);
  if (!empty($firmware->managed)) {
    $m = explode('|',$firmware->managed);
    foreach($m as $v)
      $managed[$v] = $v;
    $firmware->managed = $managed;
  } else
    $firmware->managed = array();
  guifi_log(GUIFILOG_TARCE,'function guifi_get_firmware(managed)',$firmware->managed);

  return $firmware;
}

/* guifi_get_free_interfaces(): Populates a select list with the available & free cable interfaces */
function guifi_get_free_interfaces($id,$edit = array()) {

  $possible = guifi_get_possible_interfaces($edit);

  $qi = db_query('
    SELECT interface_type
    FROM {guifi_interfaces}
    WHERE device_id = :id',
    array(':id' => $id));
  $used = array();
  while ($i = $qi->fetchObject()) {
    $used[] = $i->interface_type;
  }
  if ($edit != NULL)
  if (count($edit['interfaces']) > 0)
    foreach ($edit['interfaces'] as $k => $value) {
      if ($value['deleted']) continue;
      $used[] = $value['interface_type'];
    }

   $free = array_diff($possible, $used);
   if (count($free)==0)
     $free[] = 'other';

  return array_combine($free, $free);
}

/* guifi_devices_select_filter($form,$filters): Construct a list of devices to link with */
function guifi_devices_select_filter($form_state,$action='',&$fweight = -100) {

  $form = array();
  $ahah = array(
          'path' => 'guifi/js/select-device/'.$action,
          'wrapper' => 'list-devices',
          'method' => 'replace',
          'event' => 'change',
          'effect' => 'fade',
         );

  $form['f'] = array(
    '#type' => 'fieldset',
    '#title' => t('Filters'),
    '#weight' => 0,
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#weight' => $fweight++,
  );
  $form['f']['dmin'] = array(
    '#type' => 'textfield',
    '#parents' => array('filters','dmin'),
    '#title' => t('Distance from'),
    '#size' => 5,
    '#maxlength' => 5,
    '#attributes' => array('class' => 'digits min(0)'),
    '#default_value' => $form_state['values']['filters']['dmin'],
    '#description' => t("List starts at this distance"),
    '#prefix' => '<table><tr><td>',
    '#suffix' => '</td>',
    '#ahah' => $ahah,
    '#weight' => $fweight++
  );
  $form['f']['dmax'] = array(
    '#type' => 'textfield',
    '#parents' => array('filters','dmax'),
    '#title' => t('until'),
    '#size' => 5,
    '#maxlength' => 5,
    '#default_value' => $form_state['values']['filters']['dmax'],
    '#attributes' => array('class' => 'digits min(0)'),
    '#description' => t("...and finishes at this distance"),
    '#prefix' => '<td>',
    '#suffix' => '</td>',
    '#ahah' => $ahah,
    '#weight' => $fweight++
  );
  if (isset($form_state['values']['filters']['max']))
  $form['f']['max'] = array(
    '#type' => 'textfield',
    '#parents' => array('filters','max'),
    '#title' => t('Stop list at'),
    '#size' => 5,
    '#maxlength' => 5,
    '#default_value' => $form_state['values']['filters']['max'],
    '#description' => t("Max. # of rows to list"),
    '#prefix' => '<td>',
    '#suffix' => '</td>',
    '#ahah' => $ahah,
    '#weight' => $fweight++
  );
  $form['f']['search'] = array(
    '#type' => 'textfield',
    '#parents' => array('filters','search'),
    '#title' => t('Search string'),
    '#size' => 25,
    '#maxlength' => 25,
    '#default_value' => $form_state['values']['filters']['search'],
    '#description' => t("Zone, node or device contains this string"),
    '#prefix' => '<td>',
    '#suffix' => '</td>',
    '#ahah' => $ahah,
    '#weight' => $fweight++
  );
    if (isset($form_state['values']['filters']['sn']))
  $form['f']['sn'] = array(
    '#type' => 'checkbox',
    '#parents' => array('filters','sn'),
    '#title' => t('Only Supernodes'),
    '#size' => 1,
    '#maxlength' => 1,
    '#default_value' => $form_state['values']['filters']['sn'],
    '#description' => t("Search only for supernodes?"),
    '#prefix' => '<td>',
    '#suffix' => '</td>',
    '#ahah' => $ahah,
    '#weight' => $fweight++
  );
  if (isset($form_state['values']['filters']['status'])) {
    $choices =array_merge(array('All' => t('All')),guifi_types('status'));
    $form['f']['status'] = array(
      '#type' => 'select',
      '#parents' => array('filters','status'),
      '#title' => t("Status"),
      '#required' => TRUE,
      '#default_value' => $form_state['values']['filters']['status'],
      '#options' => $choices,
      '#description' => t("Status of the node"),
      '#prefix' => '<td>',
      '#suffix' => '</td>',
      '#ahah' => $ahah,
      '#weight' => $fweight++,
     );
  }
  $form['f']['azimuth'] = array(
    '#type' => 'select',
    '#parents' => array('filters','azimuth'),
    '#title' => t('Azimuth'),
    '#default_value' => $form_state['values']['filters']['azimuth'],
    '#options' => array(
       '0,360'        => t('All'), //N
       '292,360-0,67' => t('North'), //N
       '22,158'       => t('East'),  //E
       '112,248'      => t('South'), //S
       '202,338'      => t('West'),  //W
        ),
    '#description' => t('List nodes at the selected orientation.'),
//    '#multiple' => TRUE,
//    '#size' => 3,
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
    '#ahah' => $ahah,
    '#weight' => $fweight++,
  );
/*  $form['f']['action'] = array(
    '#type' => 'submit',
    '#parents' => array('action'),
    '#value' => t('Apply filter'),
    '#ahah' => array(
          'path' => 'guifi/js/select-device',
          'wrapper' => 'list-devices',
          'method' => 'replace',
          'effect' => 'fade',
         ),
    '#weight' => $fweight++,
  );*/

  if (isset($form_state['values']['filters']['type']))
  $form['f']['type'] = array(
     '#type'  => 'hidden',
     '#parents' => array('filters','type'),
     '#value' => $form_state['values']['filters']['type']);
  if (isset($form_state['values']['filters']['mode']))
  $form['f']['mode'] = array(
     '#type'  => 'hidden',
     '#parents' => array('filters','mode'),
     '#value' => $form_state['values']['filters']['mode']);
  if (isset($form_state['values']['filters']['from_node']))
  $form['f']['from_node'] = array(
     '#type'  => 'hidden',
     '#parents' => array('filters','from_node'),
     '#value' => $form_state['values']['filters']['from_node']);
  if (isset($filters['from_device']))
  $form['f']['from_device'] = array(
     '#type'  => 'hidden',
     '#parents' => array('filters','from_device'),
     '#value' => $form_state['values']['filters']['from_device']);
  if (isset($form_state['values']['filters']['from_radio']))
  $form['f']['from_radio'] = array(
     '#type'  => 'hidden',
     '#parents' => array('filters','from_radio'),
     '#value' => $form_state['values']['filters']['from_radio']);
  if (isset($form_state['values']['filters']['skip']))
  $form['f']['skip'] = array(
     '#type'  => 'hidden',
     '#parents' => array('filters','skip'),
     '#value' => $form_state['values']['filters']['skip']);
  return $form;
}

function _guifi_set_namelocation($location) {
  $prefix = '';
  foreach (array_reverse(guifi_zone_get_parents($location->zone_id)) as $parent) {
    if ($parent > 0) {
      $result = db_query(
        'SELECT z.id, z.title, z.master ' .
        'FROM {guifi_zone} z ' .
        'WHERE z.id = :parent',
        array(':parent' => $parent))->fetchAssoc();
      if ($result['master']) {
        $prefix .= $result['title'].', ';
      }
    }

  }
  return $prefix.$location->nick;
} // eof function

function guifi_services_select($stype) {
  $var = array();
  $found = FALSE;

  $query = db_query(
    'SELECT s.id, n.title nick, z.id zone_id ' .
    'FROM {node} n,{guifi_services} s, {guifi_zone} z ' .
    'WHERE s.id=n.nid ' .
    '  AND s.service_type = :stype ' .
    '  AND s.zone_id=z.id ' .
    'ORDER BY z.id, s.id, s.nick',
    array(':stype' => $stype));

  while ($service = $query->fetchObject()) {
    $var[$service->id] = _guifi_set_namelocation($service,$new_pointer,$found);
  } // eof while query service,zone

  asort($var);
  return $var;
}

function guifi_validate_nick($nick) {
  if  ($nick != htmlentities($nick, ENT_QUOTES)) {
    form_set_error('nick', t('No special characters allowed for nick name, use just 7 bits chars.'));
  }

  if (count(explode(' ',$nick)) > 1) {
    form_set_error('nick', t('Nick name have to be a single word.'));
  }
   if (isset($nick)) {
    if (trim($nick) == '') {
      form_set_error('nick', t('You have to specify a nick.'));
    }
  }
}

function guifi_validate_ip($ip,&$form_state) {
  if ($form_state['clicked_button']['#value'] == t('Reset'))
    return;

  $longIp = ip2long($ip['#value']);

  if (($longIp==FALSE) or (count(explode('.',$ip['#value']))!=4))
    form_error($ip,
      t('Error in ipv4 address (%addr), use "10.138.0.1" format.',
        array('%addr' => $ip['#value'])),'error');
  else
    $ip['#value'] = long2ip($longIp);

  return $ip;
}

function guifi_device_loaduser($id) {
  $device = db_query("SELECT d.user_created FROM {guifi_devices} d WHERE d.id = :id", array(':id' => $id))->fetchObject();
  return ($device->user_created);
}

function guifi_get_nodeuser($id) {
  $node = db_query("SELECT d.user_created FROM {guifi_location} d WHERE d.id = :id", array(':id' => $id))->fetchObject();
  return ($node->user_created);
}

function guifi_get_hostname($id) {
  $device = db_query("SELECT d.nick FROM {guifi_devices} d WHERE d.id = :id", array(':id' => $id))->fetchObject();
  return guifi_to_7bits($device->nick);
}

function guifi_get_ap_ssid($id,$radiodev_counter) {
  $radio = db_query("SELECT r.ssid, d.id FROM {guifi_radios} r LEFT JOIN {guifi_devices} d ON r.id=d.id WHERE r.id = :id AND r.radiodev_counter = :rc", array(':id' => $id, ':rc' => $radiodev_counter))->fetchObject();
  return guifi_clean_ssid($radio->ssid);
}

function guifi_get_ap_protocol($id,$radiodev_counter) {
  $radio = db_query("SELECT r.protocol, d.id FROM {guifi_radios} r LEFT JOIN {guifi_devices} d ON r.id=d.id WHERE r.id = :id AND r.radiodev_counter = :rc", array(':id' => $id, ':rc' => $radiodev_counter))->fetchObject();
  return $radio->protocol;
}

function guifi_get_ap_channel($id,$radiodev_counter) {
  $radio = db_query("SELECT r.channel, d.id FROM {guifi_radios} r LEFT JOIN {guifi_devices} d ON r.id=d.id WHERE r.id = :id AND r.radiodev_counter = :rc", array(':id' => $id, ':rc' => $radiodev_counter))->fetchObject();
  return $radio->channel;
}

/* returns an array with id=>ipv4/mask from a device array */
function guifi_get_currentDeviceIpv4s($device) {
  $ips = array();
  
  if (empty($device['ipv4']))
    return $ips;
    
  foreach ($device['ipv4'] as $k=>$ip) {
    if (empty($ip[netmask]))
      continue;
    $ipc = _ipcalc($ip ['ipv4'],$ip['netmask']);
    $interfaces = guifi_get_currentInterfaces($device);
    if (array_key_exists($ip['interface_id'],$interfaces))
      $ki = $ip['interface_id'];
    else {
      $ki = $device['id'].','.$ip['id'];
    }
    $ips[$ip['interface_id'].','.$ip['id']] = $ip['ipv4'].'/'.$ipc['maskbits'].' '.
      $interfaces[$ki];
  }
  return $ips;
}

/* returns an array with id=>mac from a device array */
function guifi_get_currentDeviceMacs($device) {
  $macs = array();
  
  foreach(array('radios','interfaces','vlans','aggregations') as $iClass)
    foreach ($device[$iClass] as $id => $interface) {
      if (!(empty($interface[mac])))
        if ($iClass != 'radios') {  
          if (isset($interface['id']))
            $macs[$interface['id']] = $interface[mac];
        } else 
          $macs[$device[id].','.$id] = $interface[mac]; 
    }
 
  return $macs;  
}

function guifi_get_currentInterfaces($device, $iradios = FALSE) {
  guifi_log(GUIFILOG_TRACE,'function guifi_get_currentInterfaces(device)',$device);
  $interfaces = array();

  foreach ($device[radios] as $k => $radio) {
    if ($iradios == FALSE) {
      $interfaces[$device[id].','.$k] = 'wlan'.($k+1).' - '.$radio[ssid];
    }
    else  {
      foreach ($radio['interfaces'] as $x => $wiface) {
        $interfaces[$wiface['id']] = $wiface['interface_type'];
      }
    }
  }
  foreach (array('ports','interfaces','vlans','aggregations','tunnels') as $iClass){
    guifi_log(GUIFILOG_TRACE,
      sprintf('function guifi_get_currentInterfaces(%s)',$iClass),
      count($device[$iClass]));
//    if (!empty($device[$iClass]))
    foreach ($device[$iClass] as $k => $interface) {
      guifi_log(GUIFILOG_TRACE,"function guifi_get_currentInterfaces($k)",$interface);
      
      if (empty($interface[interface_type]))
        if (empty($interface[iname]))
          continue;
        else
         $interfaces[$k] = $interface[iname];                    
      else
        $interfaces[$k] = $interface[interface_type];
    }
  }

  return $interfaces;
}

function guifi_get_devicename($id, $format = 'nick') {

  if (!is_numeric($id)) {
    $v = explode('-',$id);
    if (!is_numeric($v[0]))
      $id = $v[0];
  }

  switch ($format) {
  case 'large':
    $device = db_query(
      'SELECT
        CONCAT(d.id,\'-\',d.nick,\', \',l.nick,\', \',z.title) str
      FROM {guifi_location} l, {guifi_zone} z, {guifi_devices} d
      WHERE d.id = :id AND l.id=d.nid AND l.zone_id=z.id', array(':id' => $id))->fetchObject();
    break;
  case 'nick':
  default:
    $device = db_query(
      'SELECT d.nick str
      FROM {guifi_location} l, {guifi_zone} z, {guifi_devices} d
      WHERE d.id = :id AND l.id=d.nid AND l.zone_id=z.id', array(':id' => $id))->fetchObject();
  }
  return $device->str;
}

function guifi_get_nodename($id) {
  $node = db_query("SELECT nick FROM {guifi_location} WHERE id = :id", array(':id' => $id))->fetchObject();
  return($node->nick);
}

function guifi_get_location($id) {
  $node = db_query("SELECT d.lat,d.lon FROM {guifi_location} d WHERE d.id = :id", array(':id' => $id))->fetchObject();
  return array('lat' => $node->lat, 'lon' => $node->lon);
}

function guifi_get_zone_of_node($id) {
  $node = db_query("SELECT d.zone_id FROM {guifi_location} d WHERE d.id = :id", array(':id' => $id))->fetchObject();
  return $node->zone_id;
}

function guifi_get_zone_of_service($id) {
  $node = db_query("SELECT s.zone_id FROM {guifi_services} s WHERE s.id = :id", array(':id' => $id))->fetchObject();
  return $node->zone_id;
}

function guifi_get_zone_nick($id) {
  $node = db_query("SELECT nick, title FROM {guifi_zone} WHERE id = :id", array(':id' => $id))->fetchObject();
  if (!empty($node->nick))
    return $node->nick;
  else
    return $node->title;
}

function guifi_get_zone_name($id) {
  $node = db_query("SELECT title FROM {guifi_zone} WHERE id = :id",array(':id' => $id))->fetchObject();
  return empty($node->title) ? t('None') : $node->title;
}

function guifi_get_interface_descr($iid) {
  $interface = db_query("SELECT device_id, interface_type, radiodev_counter  FROM {guifi_interfaces} WHERE id = :iid", array(':iid' => $iid))->fetchObject();
  if ($interface->radiodev_counter != NULL) {
    $ssid = guifi_get_ap_ssid($interface->device_id,$interface->radiodev_counter);
    return $ssid.' '.$interface->interface_type;
  } else
    return $interface->interface_type;
}

function guifi_trim_vowels($str) {
  return str_replace(array('a','e','i','o','u','A','E','I','O','U'),'',$str);
}

function guifi_abbreviate($str,$len = 5) {
  $str = guifi_to_7bits($str);
  if  (strlen($str) > $len) {
    $words = str_word_count(ucwords($str),1,'1234567890');

    if (count($words) > 1) {
      $s = "";
      foreach ($words as $k => $word) {
        if ($k == 1) {
          $s .= substr($word,0,3);
        } else {
          $s .= substr($word,0,1);
        }
      }
      return $s;
    } else {
      return guifi_trim_vowels($str);
    }
  }
  return str_replace(" ","",$str);
}

/** IP functions to get IP's within a subnet or allocate new subnets **/

/**
 * guifi_ipcalc_get_ips
 *  gets a the allocated ips
 * @return ordered array
**/

function guifi_ipcalc_get_maskbits($mask) {
  if ($mask == '255.255.255.255')
    return 32;
  return strlen(preg_replace("/0/", "", decbin(ip2long($mask))));
}

function guifi_ipcalc_get_ips(
  $start = '0.0.0.0',   // start address to look for
  $mask = '0.0.0.0',    // range, 0.0.0.0 means all
  $edit = NULL,         // array which can contain ipv4 values to be added,
                        //   must be labeled "ipv4"
  $ntype = NULL,        // ipv4 type
                        //   1: public
                        //   2: backbone
                        //   3: protocol specific (ad-hoc/mesh)
  $zid = NULL)          // zone id, to be used in the future
                        //   to improve performance
{

  // for 64 bit systems
  $start_dec = is_numeric($start) ? $start : ip2long($start);
  $item = _ipcalc($start,$mask);
  $end_dec = ip2long($item['broadcast']);

  // to support 32-bits systems
  if ($start  == '0.0.0.0')
    $start_dec = -2147483648;
  if ($mask == '0.0.0.0')
    $end_dec = 2147483647;

  $ips = array();
  $sql_where = array();

  $sql = "SELECT ipv4, netmask FROM {guifi_ipv4}";

  if (!is_null($ntype))
    $sql_where[] = 'ipv4_type = '.$ntype;

  if (!is_null($zid))
    $sql_where[] = 'zone_id = '.$zid;

  if (!empty($sql_where))
    $sql .= ' WHERE '.implode(' AND ',$sql_where);

  $query = db_query($sql);
  while ($ip = $query->fetchAssoc()) {
    if ( ($ip['ipv4'] != 'dhcp') and (!empty($ip['ipv4'])) )  {
      $ip_dec = ip2long($ip['ipv4']);
//      print "ip: $ip[ipv4] $ip_dec - ";
      $min = FALSE; $max = FALSE;
      if (!isset($ips[$ip_dec]))
        if (($start == '0.0.0.0') and ($mask == '0.0.0.0'))
          $ips[$ip_dec] = guifi_ipcalc_get_maskbits($ip['netmask']);
        else if (($ip_dec <= $end_dec) and ($ip_dec >= $start_dec))
          $ips[$ip_dec] = guifi_ipcalc_get_maskbits($ip['netmask']);

        // save memory by storing just the maskbits
        // by now, 1MB array contains 7,750 ips
        $ips[$ip_dec] = guifi_ipcalc_get_maskbits($ip['netmask']);
    }
  }

  // going to get current device ips, if given
  if ($edit != NULL)
    guifi_ipcalc_get_ips_recurse($edit,$ips) ;
  ksort($ips);
  return $ips;
}

function guifi_ipcalc_get_ips_recurse($var,&$ips) {
  foreach ($var as $k => $value) {
    if ($k == 'ipv4') {
      $ip_dec = ip2long((string)$value);
      if ( ($ip_dec) and (!isset($ips[$ip_dec])) ) {
        $ips[$ip_dec] = guifi_ipcalc_get_maskbits($var['netmask']);
      }
    }
    if (is_array($value))
      guifi_ipcalc_get_ips_recurse($value,$ips);
  }
}

function guifi_ipcalc_get_subnet_by_nid(
  $nid,                                 // node id
  $mask_allocate = '255.255.255.224',   // mask size to look for & allocate
  $network_type = 'public',             // public or backbone
  $ips_allocated = NULL,                // sorted array containing current used ips
  $allocate = 'No',                     // if 'Yes' and network_type is public,
                                        //   allocate the obtained range at the
                                        //   guifi_networks table
  $verbose=FALSE)                       // create time&trace output
{

  if (empty($nid)) {
    drupal_set_message(t('Error: trying to search for a network for unknown node or zone'),'error');
    return;
  }
  if (empty($mask_allocate)) {
    drupal_set_message(t('Error: trying to search for a network of unknown size'),'error');
    return;
  }
  if (empty($network_type)) {
    drupal_set_message(t('Error: trying to search for a network for unknown type'),'error');
    return;
  }

  // print "Going to allocate network ".$mask_allocate."-".$network_type;

  global $user;

  $tbegin = microtime(TRUE);

  $zone = node_load($nid);

  if ($zone->type == 'guifi_location')
    $zone = node_load($zone->zone_id);

  $rzone = $zone;

  $depth = 0;
  $root_zone = $zone->id;

  $lbegin = microtime(TRUE);

  $search_mask = $mask_allocate;

  do {  // while next is not the master, check within the already allocated ranges
    $result = db_query(
        'SELECT n.id, n.base, n.mask ' .
        'FROM {guifi_networks} n ' .
        'WHERE n.zone = :zid ' .
        '  AND network_type = :ntype ' .
        'ORDER BY n.id',
        array(':zid' => $zone->id, ':ntype' => $network_type));

    if ($verbose)
      drupal_set_message(t(
        'Searching if %mask is available at %zone, elapsed: %secs',
         array('%mask' => $mask_allocate,
           '%zone' => $zone->title,
           '%secs' => round(microtime(TRUE)-$lbegin,4))));

    // if there are already networks defined, increase network mask, up to /20 level
    // here, getting the total # of nets defined

    $tnets = 0;

    while ($net = $result->fetchObject()) {
      $tnets++;
      $item = _ipcalc($net->base,$net->mask);

      // if looking for mesh ip (255.255.255.255) base address & broadcast
      // should be considered as used
      if ($search_mask == '255.255.255.255') {
        $ips_allocated[ip2long($net->base)] = 32;
        $ips_allocated[ip2long($item['netend'])] = 32;
      }

      if ($ip = guifi_ipcalc_find_subnet($net->base, $net->mask, $mask_allocate, $ips_allocated)) {
        if ($verbose)
          drupal_set_message(
            t('Found %ip/%rmask available at %netbase/%amask, got from %zone, elapsed: %secs',
              array('%amask' => $net->mask,
                '%netbase' => $net->base,
                '%ip' => $ip,
                '%rmask' => guifi_ipcalc_get_maskbits($mask_allocate),
                '%zone' => $zone->title,
                '%secs' => round(microtime(TRUE)-$lbegin,4))));

        // reserve the available range fount into database?
        if ( ($depth) and
             ( ($allocate == 'Yes') and ($network_type == 'public') and ($mask_allocate != '255.255.255.255') )
           ) {
          $msg = strip_tags(t('A new network (%base / %mask) has been allocated for zone %name, got from %name2 by %user.',
                          array('%base' => $ip,
                                '%mask' => $mask_allocate,
                                '%name' => $rzone->title,
                                '%name2' => $zone->title,
                                '%user' =>  $user->name
                               )));

          $to_mail = explode(',',$rzone->notification);
          $to_mail = explode(',',$zone->notification);

          $nnet = array(
            'new' => TRUE,
            'base' => $ip,
            'mask' => $mask_allocate,
            'zone' => $root_zone,
            'newtwork_type' => $network_type
          );
          $nnet = _guifi_db_sql(
            'guifi_networks',
            NULL,
            $nnet,
            $log,
            $to_mail);
          guifi_notify(
            $to_mail,
            $msg,
            $log);
          drupal_set_message($msg);
          if ($search_mask == '255.255.255.255')
            $ip = long2ip(ip2long($ip)+1);
        }
        return $ip;
      }
    } // while there is a network defined at the zone

    // Network was not allocated
    if ($verbose)
      drupal_set_message(t('Unable to find space at %zone, will look at parents, elapsed: %secs',
        array('%zone' => $zone->title,
          '%secs' => round(microtime(TRUE)-$lbegin,4))));

    // Need for an unused range,
    // already allocated networks from others than parents should be considered
    // as allocated ips (skipped)

    // This have to be done once, so do if is the zone being asked for
    if ($root_zone == $zone->id ) {
      $parents = guifi_zone_get_parents($root_zone);
      $query = db_query(
                                   'SELECT base ipv4, mask ' .
                                   'FROM {guifi_networks} ' .
                                   'WHERE zone NOT IN ('.
                                   implode(',',guifi_zone_get_parents($root_zone)).
                                   ')');
      while ($nip = $query->fetchAssoc()) {
        $ips_allocated[ip2long($nip['ipv4']) + 1] =
        guifi_ipcalc_get_maskbits($nip['mask']);
      }
      // once merged, sort
      ksort($ips_allocated);

      // calculating the needed mask
      if ($network_type == 'public') {
        $depth++;

        if (($tnets > 0) and ($tnets < 5))
          // between 1 and 4, 24 - nets defined
          $maskbits = 24 - $tnets;
        else if ($tnets >= 5)
          // greater than 4, /20 - 255.255.240.0
          $maskbits = 20;
        else
          // first net, /24 - 255.255.255.0
          $maskbits = 24;

        $mitem = _ipcalc_by_netbits($net->base,$maskbits);
        $mbits_allocate = guifi_ipcalc_get_maskbits($mask_allocate);

        if (($mbits_allocate > $maskbits) or
        ($mask_allocate == '255.255.255.255'))
          $mask_allocate = $mitem['netmask'];
      }
    }

    // Take a look at the parent network zones
    $master = $zone->master;
    if ( $zone->master > 0)
      $zone = node_load($zone->master);

  } while ( $master  > 0);

  return FALSE;
}

function guifi_ipcalc_find_subnet(
  $base_ip,              // base address to start from
  $mask_range,           // range to look (up to/total size)
  $mask_allocated,       // size of free space to look for within the total range
  $ips_allocated = NULL) // sorted array with the currently used ips
{

  // if current allocated addresses are not given,take it from the database
  if (empty($ips_allocated)) {
    $ips_allocated = guifi_ipcalc_get_ips($base_ip,$mask_range);
  }

  // print "Looking sizeof $mask_allocated at $base_ip / $mask_range into ".count($ips_allocated)." keys\n<br />";

  // start looking at the given base ip to look at up to the size of mask_range
  // in chunks of "increments"
  $net_dec = is_numeric($base_ip) ? $base_ip : ip2long($base_ip);
  $item = _ipcalc($base_ip,$mask_range);
  $end_dec = ip2long($item['broadcast']);
  if ($mask_allocated == '255.255.255.255')
    $increment = 1;
  else {
    $item = _ipcalc($base_ip,$mask_allocated);
    $increment = $item['hosts'] + 2;
  }

  if ($end_dec < ($net_dec + $increment))
    // space to look for is greater than the range to look at, no need to search
    return FALSE;

  while (($net_dec) <= ($end_dec)) {

    // check that we are starting from a network base address, if not,
    // advance to the end of the current subnetwork to find at the next,
    // forcing $net_dec to be a valid base address
    $item = _ipcalc(long2ip($net_dec),$mask_allocated);
    if (ip2long($item['netid']) != $net_dec) {
      $net_dec = ip2long($item['broadcast']) + 1;
    }

    $last  = $net_dec + $increment;
    $key = $net_dec;

    // is there any ip allocated in the range between net_dec and increment?
    // print "Going to find between ".long2ip($net_dec)." and ".long2ip($last)." \n<br />";
    while ($key < $last)
      if (isset($ips_allocated[$key])) {
        break;
      } else
        $key++;

    // if no ips found (reached end of range), return succesfully
    if ($key == $last) {
//      print "IPs allocated: ".$ips_allocated[$net_dec+1]."\n";
      if (($ips_allocated[$net_dec+1] == 32) or
        (!isset($ips_allocated[$net_dec+1])))
      return long2ip($net_dec);
    }

    // space was already used
    // now advance the pointer up to the end of the network of the current
    // address found
    $item = _ipcalc_by_netbits(long2ip($key),$ips_allocated[$key]);
    $net_dec = ip2long($item['broadcast']) + 1;
  } // end while

  // no space available
  return FALSE;
}

function guifi_ipcalc_find_ip($base_ip = '0.0.0.0',
  $mask_range = '0.0.0.0', $ips_allocated = NULL, $verbose = true) {

  if ($ips_allocated == NULL) {
    $ips_allocated = guifi_ipcalc_get_ips($base_ip,$mask_range);
  }

  $ip_dec = ip2long($base_ip) + 1;
  $item = _ipcalc($base_ip,$mask_range);
  $end_dec = ip2long($item['broadcast']);

  $key = $ip_dec;

  while ((isset($ips_allocated[$key])) and ($key < $end_dec))
    $key++;

  if ($key < $end_dec)
    return long2ip($key);

  $ipc = _ipcalc($base_ip,$mask_range);
  if ($verbose) 
    drupal_set_message(t('Network %net/%mask is full',
      array('%net' => $base_ip, '%mask' => $ipc[maskbits])),
        'warning');
    
  return FALSE;
}

// EOF ipcalc funtions

function guifi_get_interface($ipv4) {
  $if = db_query("SELECT i.*,a.ipv4, a.netmask FROM {guifi_interfaces} i LEFT JOIN {guifi_ipv4} a ON i.id=a.interface_id WHERE ipv4 = :ipv4", array(':ipv4' => $ipv4))->fetchObject();
  if (!empty($if))
    return $if;
  else
    return 0;
}

function guifi_get_existent_interface($device_id, $interface_type) {
  if (preg_match('(wds|vlan|vwan|vwlan)',$interface_type))
    return 0;

  $if = db_query("SELECT * FROM {guifi_interfaces} WHERE device_id = :did AND interface_type = :itype", array(':did' => $device_id, ':itype' => $interface_type))->fetchObject();
  if (!empty($if))
    return $if;
  else
    return 0;
}

function guifi_ip_type($itype1, $itype2) {

  $guifi_ipconf = array(
     'wLan/Lan' => array('preg' => '/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan|Lan|wLan\/Lan/','ntype' => 'public'),
     'Lan'=>     array('preg' => '/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan|Lan|wLan\/Lan/','ntype' => 'public'),
     'wLan'=>    array('preg' => '/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan|Lan|wLan\/Lan/','ntype' => 'public'),
     'Wan'=>     array('preg' => '/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'vlan'=>    array('preg' => '/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'vlan'=>    array('preg' => '/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'vwlan'=>   array('preg' => '/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'vwan'=>    array('preg' => '/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'vlan1'=>   array('preg' => '/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'vlan2'=>   array('preg' => '/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'vlan3'=>   array('preg' => '/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'vlan4'=>   array('preg' => '/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'wds/p2p'=> array('preg' => '/wds\/p2p/',                                                 'ntype' => 'backbone'),
     'tunnel'=>  array('preg' => '/tunnel/',                                                   'ntype' => 'backbone')
     ); // eof variable_get

  if ((empty($itype1)) or (empty($itype2)))
   return FALSE;

  // if found, return network type for this interface configuration
  if (preg_match($guifi_ipconf[$itype1]['preg'],$itype2))
    return $guifi_ipconf[$itype1]['ntype'];
  if (preg_match($guifi_ipconf[$itype2]['preg'],$itype1))
    return $guifi_ipconf[$itype2]['ntype'];

  // not supported configuration, don't know how to assign ip address
  return FALSE;

}


function guifi_rename_graphs($old, $new) {

  $ext = array ('_6.rrd','_ping.rrd');

  $fold = variable_get('rrddb_path','/home/comesfa/mrtg/logs/').guifi_rrdfile($old);
  $fnew = variable_get('rrddb_path','/home/comesfa/mrtg/logs/').guifi_rrdfile($new);

  foreach ($ext as $fext) {
//    print "Going to rename ".$fold.$fext." to ".$fnew.$fext."\n<br />";
    if (file_exists($fold.$fext)) {
      rename($fold.$fext,$fnew.$fext);
//      print $fold.$fext." renamed to ".$fnew.$fext."\n<br />";
    }
  }
}

define('GUIFILOG_NONE',0);
define('GUIFILOG_BASIC',1);
define('GUIFILOG_TRACE',2);
define('GUIFILOG_FULL',3);
define('GUIFILOG_FILE',-1);

function guifi_log($level, $var, $var2 = NULL) {
  global $user;

  if ($level > variable_get('guifi_loglevel',GUIFILOG_NONE))
    return;

  $output = $var;
  if ($var2 != NULL)
  if (gettype($var2) != 'string') {
    $output .= ': '.var_export($var2, TRUE);
  } else {
    $output .= ": ".$var2;
  }

  switch ($level) {
  case 1: $m = t('BASIC'); break;
  case 2: $m = t('TRACE'); break;
  case 3: $m = t('FULL'); break;
  }

  if ($level == GUIFILOG_FILE) {
  	$fp = fopen('/tmp/guifi.log',"a");
  	fwrite($fp,date(DATE_RFC2822)."\n".$output."\n");
  	fclose($fp);
  } else
    drupal_set_message('guifiLOG: '.$m.' '.$output);
}

function guifi_to_7bits($str) {
 $str = iconv('UTF-8', 'ASCII//IGNORE', $str);
 $ignore_chars = '"\!"@#$%&/()=?\'*+[]{};:_-.,<>';
 $str_new = str_replace(str_split($ignore_chars),'',$str);
// $from =  "���������������������;
// $to   =  "aeiuoaeiouaeiouaeiouaeiouaeioucCnNaeiouaeiou";
// $str_new = str_replace(str_split($from),str_split($to),$str_new);
 guifi_log(GUIFILOG_FULL,'Converted to 7 bits: '.$str_new);
 return $str_new;
}

/**
 * This function builds an URL to the CNML service target :?
 *
 * @todo Assert url != null
 */
function guifi_cnml_call_service($target,$service,$params=array(),$extra=NULL) {
//  guifi_log(GUIFILOG_BASIC,'call CNML service target',$target);

  // processing graphserver. Needs refactor
  if (is_array($target)) {
//    print_r($target);
//    print "\n<br />Key: ".key($target)."\n";
    if  (in_array(key($target),array('zone','node','device')))
      $gs = node_load(guifi_graphs_get_server($target[key($target)],key($target)));
  }

  if (is_object($target))
    $gs = $target;

  if (is_string($target))
    $url = $target;
  else
    $url = $gs->var['url'];

    if ($url == '')
      return;
  $basename = basename($url);

  // Temporary, for backward compatibility, to take out later
  if ($basename=='graphs.php') {
    $version = '1.0';

    if ($params['type']=='device')
      $params['type']='radio';
    if ($service=='availability')
      $params['type']='availability';
    else if (isset($params['device'])) {
      $params['radio'] = $params['device'];
      unset($params['device']);
    }
  } else
    $version = '2.0';

//  guifi_log(GUIFILOG_BASIC,'call CNML service version',$version);

  if ($version == '1.0')
    return $url.'?'.guifi_cnml_args($params,$extra);
  else
    return $url.'/index.php?call='.$service.'&'.guifi_cnml_args($params,$extra);
}

/**
 * Builds url parameters for CNML request
 *
 * @param $args
 *
 * @param $extra
 *
 * @todo Change name to a more appropriate one like build_args
 */
function guifi_cnml_args($args,$extra=NULL) {
  $params = array();

  // Temporary, for backward compatibility, to take out later
  foreach ($args as $param => $value) {
    if ($param=='device' and empty($value))
      continue;

    $params[] = $param.'='.$value;
  }
  $str = implode('&',$params);
  if (!empty($extra))
    $str.= '&'.$extra;
  return $str;
}

/**
 * Returns image url to CNML
 */
function guifi_cnml_availability($args,$gs = NULL) {

  if (is_null($gs))
    $gs = node_load(guifi_graphs_get_server($args['device'],'device'));

  $url = guifi_cnml_call_service($gs,'availability',$args);
  if ($url != '') {
  $img_url =
    '<img src="'.guifi_cnml_call_service($gs,'availability',$args).'">';

  if ($gs->var['version'] >= 2.0)
    return l($img_url,'guifi/menu/ip/liveping/'.$args['device'],
            array(
              'html' => TRUE,
              'attributes' => array(
                'title' => t('live ping/traceroute to %device',
                  array('%device' => guifi_get_hostname($args['device']))),
                'target' => '_blank')));
  else
    // old v1.0 format for backward compatibility
    return $img_url;
  }
  else
    return t('unknown');
}

function guifi_clean_ssid($str) {
 $str = str_replace(array('Á','á','À','À','Ä','ä'),'a',$str);
 $str = str_replace(array('É','é','È','è','ë','Ë'),'e',$str);
 $str = str_replace(array('Í','í­','ì','Ì','ï','Ï'),'i',$str);
 $str = str_replace(array('Ó','ó','Ò','ò','ö','Ö'),'o',$str);
 $str = str_replace(array('Ú','ú','Ù','ù','ü','Ü'),'u',$str);
 $str = str_replace(array('Ñ','ñ'),'n',$str);
 $str = str_replace(array('Ç','ç'),'c',$str);

 return $str;
}

function guifi_clear_cache($num = NULL) {
  cache_clear_all('guifi','cache_block', TRUE);
  cache_clear_all('%/cnml/%','cache_page', TRUE);
  if ($num)
    cache_clear_all('%/'.$num.'/','cache_page', TRUE);
}

function guifi_cnml_tree($zid) {
  $result = db_query('
    SELECT z.id, z.master parent_id, z.title, z.nick, z.time_zone, z.ntp_servers,
      z.dns_servers, z.graph_server, z.homepage, z.minx, z.miny, z.maxx,
      z.maxy,z.timestamp_created, z.timestamp_changed,
      r.body_value
    FROM {guifi_zone} z, {node} n, {field_revision_body} r
    WHERE z.id = n.nid AND n.vid = r.entity_id
    ORDER BY z.title');
  while ($zone = $result->fetchObject()) {
    $zones[$zone->id] = $zone;
  }
  $result = db_query('
    SELECT l.*, r.body_value
    FROM {guifi_location} l, {node} n, {field_revision_body} r
    WHERE l.id=n.nid AND n.vid=r.entity_id
    ORDER BY l.nick');
  while ($node = $result->fetchObject()) {
    $zones[$node->zone_id]->nodes[] = $node;
  }

  $childs = array();
  $children = array();
  foreach ($zones as $zoneid => $zone) {
    if (!$children[$zone->parent_id]) {
      $children[$zone->parent_id][$zoneid] = $zone;
    }
    $children[$zone->parent_id][$zoneid] = $zone;
    if ($zoneid == $zid)
      $childs[$zid] = $zone;
  }

  $childs[$zid]->childs = guifi_zone_tree_recurse($zid,$children);

  return $childs;
}

function guifi_form_hidden_var($var,$keys = array(),$parents = array()) {

  $keys = array_merge($keys,array('new','deleted'));

  foreach ($keys as $kvalue) {
    if (isset($var[$kvalue])) {
      $form[$kvalue] = array(
        '#type' => 'hidden','#value' => $var[$kvalue]);
      if (!(empty($parents)))
        $form[$kvalue]['#parents'] = array_merge($parents,array($kvalue));
    }
  }

  return $form;
}

function guifi_form_hidden(&$form,$var,&$form_weight = -2000) {

//  guifi_log(GUIFILOG_TRACE,'function guifi_form_hidden()');

  foreach ($var as $key => $value)
    if (is_array($value))  {
      $form[$key] = array('#tree' => 1);
      guifi_form_hidden($form[$key],$value,$form_weight);
    } else {
      if (!preg_match('/^_action/',$key))
        $form[$key]=array(
          '#type' => 'hidden',
          '#value' => $value,
          '#weight' => $form_weight++);
    }
  return;
}

function guifi_count_radio_links($radio) {

  $ret[ap]=0;
  $ret[wds]=0;
//  print_r($radio);

  if (is_numeric($radio)) {
    $qc = db_query('
      SELECT l1.link_type type,count(*) c
      FROM {guifi_links} l1
        LEFT JOIN {guifi_links} l2 ON l1.id = l2.id
      WHERE l1.device_id = :radio
        AND l2.device_id != :radio2
      GROUP BY l1.link_type',
      array(':radio' => $radio, ':radios2' => $radio));
    while ($c = $qc->fetchObject()) {
      switch ($c->type) {
      case 'ap/client': $ret[ap]++; break;
      case 'wds/p2p': $ret[wds]++; break;
      }
    }
  } else {
    if (isset($radio[interfaces])) foreach ($radio[interfaces] as $ki => $interface)
    if (isset($interface[ipv4])) foreach ($interface[ipv4] as $ka => $ipv4)
    if (isset($ipv4[links])) foreach ($ipv4[links] as $kl => $link)
    if (!$link[deleted]) {
      if ($link[link_type] = 'wds/p2p')
        $ret[wds]++;
      if ($link[link_type] = 'ap/client')
        $ret[ap]++;
    }
  }

  return $ret;
}

function guifi_next_interface($edit = NULL) {
   $next = 0;
   $int = db_query('
    SELECT max(id)+1 id
    FROM {guifi_interfaces}')->fetchObject();
   $next=$int->id;

   if (isset($edit))
     $next = _interface_recurse($edit,$next);

   if (is_null($next))
     return 0;
   return $next;
}

function _interface_recurse($var,$next = 0) {

  foreach ($var as $k => $value) {
    if ($k == 'interfaces') foreach ($value as $k1 => $value1) {
      if ($k1 >= $next)
        $next = $k1 + 1;
      if (is_array($value1))
        $next = _interface_recurse($value1,$next);
    }

    if ($k == 'interface') foreach ($value as $k1 => $value1) {
      if (is_numeric(isset($value1[id])))
      if ($$value1[id] >= $next)
        $next = $value1[id] + 1;
      if (is_array($value1))
        $next = _interface_recurse($value1,$next);
    }

    if (is_array($value))
      $next = _interface_recurse($value,$next);
  }

  return $next;
}

function guifi_array_combine($arr1, $arr2) {
  reset($arr2);
  reset($arr1);
  unset($result);
  $result = array();
  if ((count($arr1) == count($arr2)) and count($arr1)) {
    foreach ($arr1 as $key => $kvalue) {
     $result[$kvalue] = current($arr2);
     next($arr2);
    }
  }
  return $result;
}

function guifi_refresh($parameter) {
  echo variable_get('guifi_refresh_'.$parameter,time());
  exit;
}

/***
 * Process notifications emails
 * Returns
 *    (return) message in HTML format
 *    $to_mail with valid emails and not unique
 *    $message in text format
***/

/** Notification engine
  * Messages are being stored at guifi_notify table, and once guifi_notify_period expires
  * are being aggregated per user and a mail will be sent.
  **/

/** guifi_notify(): Post a message to the notification queue
*/
function guifi_notify(&$to_mail, $subject, &$message,$verbose = TRUE, $notify = TRUE) {
  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_notify()');
  if (variable_get('guifi_notify_period',86400) == -1)
    return;

  if (!is_array($to_mail))
    $to_mail = explode(',',$to_mail);
  $to_mail[] = $user->mail;
  $to_mail[] = variable_get('guifi_contact','netadmin@guifi.net');
  $to_mail = array_unique($to_mail);
  foreach ($to_mail as $k => $mail)
    if (!valid_email_address(trim($mail)))
      unset($to_mail[$k]);
  $message = str_replace('<em>',' *',$message);
  $message = str_replace('</em>','* ',$message);
  $message = str_replace(array('<br />','<br />'),"\n",$message);
  $msubject = str_replace('<em>',' *',$subject);
  $msubject = str_replace('</em>','* ',$msubject);

  drupal_set_message($subject);

  if ($notify) {
    if ($to_mail != NULL) {
      $next_id = db_query('SELECT max(id)+1 id FROM {guifi_notify}')->fetchAssoc();
      if (is_null($next_id['id']))
        $next_id['id'] = 1;
      db_query("
        INSERT INTO {guifi_notify}
          (id,timestamp,who_id,who_name,to_array,subject,body)
        VALUES
           (:next_id,:time,:uid,:name,:mail,:subject,:msg)",
        array(':next_id' => $next_id['id'],
        ':time' => time(),
        ':uid' => $user->uid,
        ':name' => $user->name,
        ':mail' => serialize($to_mail),
        ':subject' => $msubject,
        ':msg' => $message));
      drupal_set_message(
        t('A notification will be sent to: %to',
          array('%to' => implode(',',$to_mail))));
    }

  }
  watchdog('guifi',$msubject, NULL,WATCHDOG_NOTICE);
  return $msubject;
}

/** guifi_notification_validate(): validates that the given emails are correct
  * arguments:
  * @to: string with a list of emails sepparated by comma
  * @returns: foretted str if all valid, FALSE otherwise
**/
function guifi_notification_validate($to) {
  $to = strtolower(trim(trim(str_replace(';',',',$to)),','));
  $emails = explode(',',$to);
  $trimmed = array();
  foreach ($emails as $email) {
    $temail = trim($email);
    if (!valid_email_address($temail)) {
      drupal_set_message(
        t('@email is not valid',array('@email' => $temail)),'error');
      return FALSE;
    }
    $trimmed[] = $temail;
  }
  return implode(', ',$trimmed);
}

function guifi_quantity_validate($quantity,&$form_state) {
  if ($form_state['clicked_button']['#value'] == t('Reset'))
    return;

  $quantity['#value'];
  if ((empty($quantity['#value'])) or (!is_numeric($quantity['#value']))) {
    form_error($quantity,
      t('Units (%quantity) must be a number greater than 0.',
        array('%quantity' => $quantity['#value'])),'error');
  }
  return $quantity;
}

/** function guifi_mac_validate($mac,&$form_state)
 * 
 * */
function guifi_mac_validate($mac,&$form_state) {
  if ($form_state['clicked_button']['#value'] == t('Reset'))
    return;
    
  $m2 = $mac;
  unset($m2['#post']);
  guifi_log(GUIFILOG_TRACE,'guifi_mac_validate',$m2['#parents']);  
  guifi_log(GUIFILOG_TRACE,'guifi_mac_validate',$form_state);  
      
  // if empty, 
  if (empty($mac['#value'])) {
    $pmac = null;

    // ...and have parents, take parent mac
    if (in_array($mac['#parents'][0],array('vlans','aggregations'))) {
      $macs = guifi_get_currentDeviceMacs($mac['#post']);
      $related = $mac['#post'][$mac['#parents'][0]][$mac['#parents'][1]]['related_interfaces'];
      guifi_log(GUIFILOG_TRACE,'guifi_mac_validate MACS',$macs);  
      guifi_log(GUIFILOG_TRACE,'guifi_mac_validate RELATED',$related);  
      
      if (is_array($related))
        $pmac = $macs[$related[0]];
      else
        $pmac = $macs[$related];
    }

    // ... or ineterface mac, calculate from base device mac
    if ($mac['#parents'][0]=='interfaces') {
      $iId = $mac['#post']['interfaces'][$mac['#parents'][1]]; 
      guifi_log(GUIFILOG_TRACE,'guifi_mac_validate interface MAC',$iId);  
      $pmac = _guifi_mac_sum($mac['#post']['mac'],$iId['etherdev_counter']);
    }

    // if mac set, set value in form
    if (!is_null($pmac)) {
      $mac['#value'] = $pmac;  

      form_set_value(array('#parents'=>$mac['#parents']),$pmac,$form_state);
      $form_state['rebuild'] = TRUE;
      guifi_log(GUIFILOG_TRACE,'guifi_mac_validate (null NEW MAC)',$pmac);             
    }

    //  if still empty, nothing to validate
    return;
  }

  $pmac = _guifi_validate_mac($mac['#value']);
  
  if ($pmac == FALSE) {
    form_error($mac,
      t('Error in MAC address (%mac), use 99:99:99:99:99:99 format.',
        array('%mac' => $mac['#value'])),'error');
  } else {
    if ($pmac != $mac['#value']) {
      form_set_value(array('#parents'=>$mac['#parents']),$pmac,$form_state);
      $form_state['rebuild'] = TRUE;
    }
  }
  return $mac;
}

function guifi_servername_validate($serverstr,&$form_state) {
  if ($form_state['clicked_button']['#value'] == t('Reset'))
    return;

  if ($serverstr['#value'] == t('Not assigned')){
    $form_state['values']['device_id']='';
    $form_state['values']['zone_id']='';
    return;
  }

  $sid = explode('-',$serverstr['#value']);
  $qry = db_query(
    'SELECT d.id,l.zone_id ' .
    'FROM {guifi_devices} d, {guifi_location} l ' .
    'WHERE d.id = :id ' .
    ' AND d.nid=l.id '.
    ' AND d.type IN (\'cam\',\'server\', \'cloudy\') ',
    array(':id' => $sid[0]));
  while ($server = $qry->fetchAssoc()) {
    $form_state['values']['device_id']=$server['id'];
    $form_state['values']['zone_id']=$server['zone_id'];
    return $serverstr;
  }
  form_error($serverstr,
    t('Server name %name not valid.',array('%name' => $serverstr['#value'])),'error');

  return $serverstr;
}

function guifi_nodename_validate($nodestr,&$form_state) {
  if ($form_state['clicked_button']['#value'] == t('Reset'))
    return;

  $nid = explode('-',$nodestr['#value']);
  $qry = db_query('SELECT id FROM {guifi_location} WHERE id = :nid', array(':nid' => $nid[0]));
  while ($node = $qry->fetchAssoc()) {
    $form_state['values']['nid']=$node['id'];
    return $nodestr;
  }
  form_error($nodestr,
    t('Node name %name not valid.',array('%name' => $nodestr['#value'])),'error');

  return $nodestr;
}

function guifi_devicename_validate($devicestr,&$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_devicename_validate()',$form_state['values']['form_id']);


  if (($form_state['clicked_button']['#value'] == t('Reset')) or
    empty($devicestr['#value']))
    return;

  $dev = explode('-',$devicestr['#value']);
  $qry = db_query(
    'SELECT id FROM {guifi_devices} WHERE id = :dev', array(':dev' => $dev[0]));
  while ($device = $qry->fetchAssoc()) {
    if ($form_state['values']['form_id'] != 'guifi_device_form')
      $form_state['values']['nid']=$device['id'];
    return $devicestr;
  }
  form_error($devicestr,
    t('Device name %name not valid.',array('%name' => $devicestr['#value'])),'error');

  return $devicestr;
}

function guifi_notify_mail($key, &$message, $params) {
  $data['user'] = $params['account'];
  $options['language'] = $message['language'];
  user_mail_tokens($variables, $data, $options);
  switch($key) {
    case 'notify':
      $message['subject'] = $params['mail']['subject'];
      $message['body'] = $params['mail']['body'];
      break;
  }
}

/** converteix les coordenades de graus,minuts i segons a graus amb decimals
 *  guifi_coord_dmstod($deg:int,$min:int,$seg:min):float or NULL..
*/
function guifi_coord_dmstod($deg,$min,$seg){
  $res=NULL;
  if ($deg != NULL){$res = $deg;}
  if ($min != NULL){$res = $res + ($min / 60);}
  if ($seg != NULL){$res = $res + ($seg / 3600);}
  return $res;
}
/** converteix les coordenades de graus amb decimals a graus,minuts i segons
 *  guifi_coord_dmstod($deg:float):array($deg:int,$min:int,$seg:int) or NULL
*/
function guifi_coord_dtodms($coord){
  if($coord!=NULL){
    $deg = floor($coord);
    $min = (($coord - floor($coord)) * 60);
    $seg = round(($min - floor($min)) * 60,4);
    $min = floor($min);
    $res=array($deg,$min,$seg);
  } else {
    $res=NULL;
  }
  return $res;
}

function guifi_gmap_key() {
  drupal_add_js(drupal_get_path('module', 'guifi').'/js/wms-gs-2_0_0.js','file');
  $element = array(
  '#type' => 'markup', // The #tag is the html tag - <link />
  '#markup' => '<script src="http://maps.googleapis.com/maps/api/js?sensor=false&amp;libraries=places" type="text/javascript"></script>',
  );
  drupal_add_html_head($element, 'guifi_gmap_key');
  return TRUE;
}

function guifi_validate_js($form_name) {
  drupal_add_js(drupal_get_path('module', 'guifi').'/js/jquery.validate.pack.js','file');
  drupal_add_js (
    '$(document).ready(function(){$("'.$form_name.'").validate()}); ',
    'inline');
}

function theme_strong($txt) {
  return '<strong>'.theme_placeholder($txt).'</strong>';
}

?>
