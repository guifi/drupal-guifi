<?php

function _dec_addr($ip) {
  list($IP1, $IP2, $IP3, $IP4) = split("\.",$ip,4);

  return hexdec(sprintf("%02X%02X%02X%02X" ,$IP1, $IP2, $IP3, $IP4));
}

function _dec_to_ip($ip) {
  $hex_str = chunk_split(sprintf("%08X",$ip),2,':');
//    return sprintf("%08X",$ip);
  list($hex1, $hex2, $hex3, $hex4) =  split('\:',$hex_str,4);
  return base_convert($hex1,16,10) ."."
    .base_convert($hex2,16,10) ."."
    .base_convert($hex3,16,10) ."."
    .base_convert($hex4,16,10);
}

// got non 64-bit systems, let's avoid long2ip & ip2long
function _ip2long($str) {
  return ip2long($str);
}

function _long2ip32($f) {
  return long2ip32($f);
}

function _network_calcBase($ip="10.138.0.0", $netmask="255.255.255.224") {
  return long2ip(ip2long($ip) & ip2long($netmask));
}

function _ipcalc($ip,$mask) {
  # Modified by Ramon Roca (http://guifi.net)
  # based on code created by Daniel Lafraia
  # www.lafraia.com
  # Please do not remove this header

  $return = array();

  $return['netmask']   = $mask;
  if (($ip == '0.0.0.0') && (($mask == '0.0.0.0') || ($mask == 0))) {
    $return['netid'] = '0.0.0.0';
    $return['netmask'] = '0.0.0.0';
    $return['broadcast'] = '255.255.255.255';
    $return['netstart'] = '0.0.0.1';
    $return['netend'] = '255.255.255.254';
    $return['maskbits'] = '0';
    $return['wildcard'] = '255.255.255.255';
    $return['hosts'] = ip2long($return['netend']) - ip2long($return['netstart']) + 1;

    return $return;
  }

  $octets = array("ip" => preg_split('/\./',$ip),
    "mask" => preg_split('/\./',$mask));

  foreach ($octets as $obj => $split) {
    foreach ($split as $val) {
      if ($val >= 0 && $val <= 255) {
        $octets[$obj]['binary'] .= str_repeat("0",8-strlen(decbin($val))).decbin($val);
      } else {
        guifi_log(GUIFILOG_NONE,'_ipcalc '.$ip. ' mask '.$mask.' invalid('.$obj.')', NULL);
        print "Error: Item '$obj' invàlid";
        return -1;
      }
    }
  }

  if ($mask=='255.255.255.255')
	  $return['maskbits'] = 32;
  else {
	  if (! preg_match("/^(1+)0+$/",$octets['mask']['binary'],$matches)) {
		  guifi_log(GUIFILOG_NONE,'_ipcalc '.$ip. ' mask '.$mask.' invalid ('.$obj.')',$matches);
		  //    print "Error: Item 'mask' ".$mask." invàlid ";
		  return -1;
	  } else {
		  $return["maskbits"] = strlen($matches[1]);
	  }
  }

  $return['netid'] =
    bintoIP(substr($octets['ip']['binary'],0,$return["maskbits"]).str_repeat("0",32-$return["maskbits"]));
  $return['broadcast'] =
    bintoIP(substr($octets['ip']['binary'],0,$return["maskbits"]).str_repeat("1",32-$return["maskbits"]));
  $positivemask = ((32 - $return["maskbits"] - 1) >= 0)? (32 - $return["maskbits"] - 1) : 0;
  $return['netstart'] =
    bintoIP(substr($octets['ip']['binary'],0,$return["maskbits"]).str_repeat("0",$positivemask)."1");
  $return['netend']  = bintoIP(substr($octets['ip']['binary'],0,$return["maskbits"]).str_repeat("1",$positivemask)."0");
  $return['wildcard']  = preg_replace(array("/1/","/0/"),array("a","b"),$octets['mask']['binary']);
  $return['wildcard']  = bintoIP(preg_replace(array("/a/","/b/"),array("0","1"),$return['wildcard']));
  $return['hosts'] = ip2long($return['netend']) - ip2long($return['netstart']) + 1;
  return $return;
}

function _ipcalc_by_netbits($ip,$netbits) {
  return _ipcalc($ip, bintoIP(str_repeat("1",$netbits).str_repeat("0",32-$netbits)) );
}

function _ipcalc_by_netmask($ip,$hosts) {
  return _ipcalc($ip, _netmask_by_hosts($hosts) );
}

function bintoIP ($bin) {
  return bindec(substr($bin,0,8)).".".
    bindec(substr($bin,8,8)).".".
    bindec(substr($bin,16,8)).".".
    bindec(substr($bin,24,8));
}

function _netmask_by_hosts($hosts) {
  $netbits =  (32 - strlen(base_convert($hosts + 1,10,2)));
  return bintoIP(str_repeat("1",$netbits).str_repeat("0",32-$netbits));
}

function guifi_networks_list($info) {

  $query_nt = db_query("SELECT DISTINCT network_type FROM guifi_networks ORDER BY network_type");

  while ($item_nt = db_fetch_object($query_nt)) {
    $header = array(t('network'));
    $query = db_query("SELECT INET_ATON(base) AS nip,INET_ATON(mask) AS nmask,base,mask,network_type FROM guifi_networks WHERE network_type='%s' ORDER BY network_type,nip,nmask", $item_nt->network_type);
    $rows = array();

    while ($item = db_fetch_object($query)) {
      $row = array($item->base.'/'.round(32-log(0xffffffff-$item->nmask,2)));
      $rows = array_merge($rows, array($row));
    }

    $table = theme('table', $header, $rows);
    $box .= theme('box', t($item_nt->network_type.' list'), $table);
  }

  return $row ? $box :"";
}


?>