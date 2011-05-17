<?php

// Generador dels unsolclic
function guifi_unsolclic($dev, $format = 'html') {
  global $rc_startup;
  global $ospf_zone;
  global $otype;

  $otype = $format;

  $dev = (object)$dev;
  
  if ($dev->variable['firmware'] == 'n/a') {
	_outln_comment(t("ERROR: I do need a firmware selected at the radio web interface: ").'<a href=/guifi/device/'.$dev->id.'/edit>http://guifi.net/guifi/device/'.$dev->id.'/edit');
        return;
  } else {
	_outln_comment(t("Generated for:"));
	_outln_comment($dev->variable['firmware']);
  }

  foreach (glob(drupal_get_path('module', 'guifi') .'/firmware/*.inc.php', GLOB_BRACE) as $firm_inc_php){
    include_once("$firm_inc_php");
  }
 if ($dev->radios[0]['mode'] == 'client') {
    $links = 0;
     foreach ($dev->radios[0]['interfaces'] as $interface_id => $interface) 
     foreach ($interface['ipv4'] as $ipv4_id => $ipv4) 
     if (isset($ipv4['links'])) foreach ($ipv4['links'] as $key => $link) {
       if ($link['link_type'] == 'ap/client') {
        $links++;
        break; 
      }
    }

    if ($links == 0) {
	_outln_comment(t("ERROR: Radio is in client mode but has no AP selected, please add a link to the AP at: ").'<a href='.base_path().'guifi/device/'.$dev->id.'/edit>http://guifi.net/guifi/device/'.$dev->id.'/edit');
        return;
    }
  }

  switch ($dev->variable['firmware']) {
    case 'RouterOSv2.9':
    case 'RouterOSv3.x':
    case 'RouterOSv4.0+':
    case 'RouterOSv4.7+':
      unsolclic_routeros($dev);
      exit;
      break;
    case 'DD-guifi':
    case 'DD-WRTv23':
    case 'Alchemy':
    case 'Talisman':
      unsolclic_wrt($dev);
      exit;
      break;
    case 'AirOsv221':
    case 'AirOsv30':
    case 'AirOsv3.6+':
      unsolclic_airos($dev);
      exit;
      break;
//    case 'AirOsv52':
//    unsolclic_airos52($dev);
//      exit;
//      break;
    case 'GuifiStationOS1.0':
      unsolclic_guifistationos($dev);
      exit;
      break;
  }

  $unsolclic='unsolclic_'.$dev->variable['firmware'];
   
  if(function_exists(${unsolclic})){

     ${unsolclic}($dev);
    exit;
  }
  else
    unsolclic_todo($dev);

}

function _out_file($txt,$file) {
  global $otype;

  if ($otype == 'html')
    print '<pre>echo "'.$txt.'" > '.$file.'</pre>';
  else
    print 'echo "'.$txt.'" > '.$file;
}

function _outln($string = '') {
  global $otype;

  print $string;
  if ($otype == 'html') print "\n<br />"; else print "\n";
}

function _outln_comment($string = '') {
  global $otype;

  print "# ".$string;
  if ($otype == 'html') print "\n<br />"; else print "\n";
}

function _outln_nvram($parameter, $value) {
  global $otype;

  print "nvram set ".$parameter.'="';
 
  if (strlen($value) <= 80) {
    print $value;
  } else { 
    $pos = 0;
    if ($otype == 'html') print "\n<br />"; else print "\n";
    do {
      print substr($value, $pos * 80, 80).'\\';
      $pos ++;
      if ($otype == 'html') print "\n<br />"; else print "\n";
    } while (strlen(substr($value,($pos-1) * 80)) > 80);
  }
  print('"');
  if ($otype == 'html') print "\n<br />"; else print "\n";
}

function _out_nvram($parameter,$value = NULL) {
  global $otype;
  print "nvram set ".$parameter.'="';
  if (!empty($value))
    print $value;
  if ($otype == 'html') print "\n<br />"; else print "\n";
}

function _out($value = '', $end = '') {
  global $otype;
  print "    ".$value.$end;
  if ($otype == 'html') print "\n<br />"; else print "\n";
}

function guifi_unsolclic_if($id, $itype) {
  return db_fetch_object(db_query("SELECT i.id, a.ipv4, a.netmask FROM {guifi_interfaces} i LEFT JOIN {guifi_ipv4} a ON i.id=a.interface_id AND a.id=0 WHERE device_id = %d AND interface_type = '%s' LIMIT 1",$id,$itype));
}

function guifi_get_dns($zone,$max = 3) {

  $dns = array();
  if (!empty($zone->dns_servers))
    $dns = explode(",",$zone->dns_servers);
  while (count($dns) < $max) {
    $zone = db_fetch_object(db_query("SELECT dns_servers, master FROM {guifi_zone} WHERE id=%d",$zone->master));
    if (!empty($zone->dns_servers))
      $dns = array_merge($dns,explode(",",$zone->dns_servers));
    if ($zone->master == 0) {
      break;
    }
  } 
  while (count($dns) > $max)
    array_pop($dns);

  return implode(" ",$dns);
}

function guifi_get_ospf_zone($zone) {

  $ospf = array();
  if (!empty($zone->ospf_zone))
    return $zone->ospf_zone;
  do {
    $zone = db_fetch_object(db_query("SELECT dns_servers, master FROM {guifi_zone} WHERE id=%d",$zone->master));
    if (!empty($zone->ospf_zone))
      return $zone->ospf_zone;
  } while ($zone->master > 0); 

  return '0';
}

function guifi_get_ntp($zone,$max = 3) {
  $ntp = array();
  if (!empty($zone->ntp_servers))
    $ntp = explode(",",$zone->ntp_servers);
  while (count($ntp) < $max) {
    $zone = db_fetch_object(db_query("SELECT ntp_servers, master FROM {guifi_zone} WHERE id=%d",$zone->master));
    if (!empty($zone->ntp_servers))
      $ntp = array_merge($ntp,explode(",",$zone->ntp_servers));
    if ($zone->master == 0) {
      break;
    }
  } 
  while (count($ntp) > $max)
    array_pop($ntp);

  return implode(" ",$ntp);
}
?>