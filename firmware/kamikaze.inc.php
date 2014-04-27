<?php

function unsolclic_kamikaze($dev) {
  $version = "1.0";
  $loc = node_load(array('nid' => $dev->nid));
  $zone = node_load(array('nid' => $loc->zone_id));
  $kamikaze_dir = drupal_get_path('module', 'guifi') .'/firmware/kamikaze/';

  if ($dev->radios[0]['mode'] == 'ap') {
    switch ($dev->variable['model_id']) {
      case "1": case "15": case "16": case "17": case "18":	
      // WRT54Gv1-4, WHR-HP-G54, WHR-G54S (BUFFALO), WRT54GL, WRT54GSv1-2, WRT54GSv4
        include_once(''.$kamikaze_dir.'broadcom/kamikaze_ap.inc.php');
        break;
      case "25": case "26": case "32": case "33": case "34": case "35": case "36": case "37":
      // NanoStationX, LiteStationX, NanoStation LocoX, Bullet
        include_once(''.$kamikaze_dir.'atheros/kamikaze_ap.inc.php');
        break;
      case "39":
      // Avila GW2348-4
        include_once(''.$kamikaze_dir.'ixp4xx/kamikaze_ap.inc.php');
        break;
      case "38":
      // RouterStation
        include_once(''.$kamikaze_dir.'ar71xx/kamikaze_ap.inc.php');
        break;
      default:
        _outln_comment('model id not supported');
        exit;
    }
  }
  if ($dev->radios[0]['mode'] == 'client') {
    switch ($dev->variable['model_id']) {
      case "1": case "15": case "16": case "17": case "18":	
      // WRT54Gv1-4, WHR-HP-G54, WHR-G54S (BUFFALO), WRT54GL, WRT54GSv1-2, WRT54GSv4
        include_once(''.$kamikaze_dir.'broadcom/kamikaze_client.inc.php');
        break;
      case "25": case "26": case "32": case "33": case "34": case "35": case "36": case "37":
      // NanoStationX, LiteStationX, NanoStation LocoX, Bullet
        include_once(''.$kamikaze_dir.'atheros/kamikaze_client.inc.php');
        break;
      default:
        _outln_comment('model id not supported');
        exit;
    }
  }

function openwrt_out_file($txt,$file) {
  global $otype;

  if ($otype == 'html') {
    print '<pre>cat > '.$file.' << EOF '.$txt;
    print 'EOF</pre>';
  }
  else {
    print 'echo "'.$txt.'" > '.$file;
}
}

function guifi_kamikaze_common_files($dev,$zone) {
  list($ntp1,$ntp2) = explode(' ',guifi_get_ntp($zone,2));
    $ntp[] .= $ntp1;
    $ntp[] .= $ntp2;

//FILE NTP
  _outln_comment();
  _outln_comment();
  _outln_comment(t('NTPClient Settings'));
  print '<pre>';
  print 'COUNTER=0
while [  $COUNTER -lt 4 ]; do
 uci delete ntpclient.@ntpserver[0] > /dev/null 2>&1
  let COUNTER=COUNTER+1 
done
';
  print 'uci add ntpclient ntpserver
uci set ntpclient.@ntpserver[0]=ntpserver
uci set ntpclient.@ntpserver[0].hostname='.$ntp1.'
uci set ntpclient.@ntpserver[0].port=123
uci add ntpclient ntpserver
uci set ntpclient.@ntpserver[1]=ntpserver
uci set ntpclient.@ntpserver[1].hostname='.$ntp2.'
uci set ntpclient.@ntpserver[1].port=123
uci add ntpclient ntpserver
uci set ntpclient.@ntpserver[2]=ntpserver
uci set ntpclient.@ntpserver[2].hostname=1.openwrt.pool.ntp.org
uci set ntpclient.@ntpserver[2].port=123
uci set ntpclient.@ntpclient[0]=ntpclient
uci set ntpclient.@ntpclient[0].interval=60
uci set ntpclient.@ntpdrift[0]=ntpdrift
uci set ntpclient.@ntpdrift[0].freq=0
';
  print 'sleep 1</pre>';
  
//FILE SYSTEM
  _outln_comment();
  _outln_comment();
  _outln_comment(t('System Settings'));
  print '<pre>';
  print 'uci set system.@system[0]=system
uci set system.@system[0].hostname='.$dev->nick.'
uci set system.@system[0].zonename=Europe/Andorra
uci set system.@system[0].timezone=CET-1CEST,M3.5.0,M10.5.0/3
uci delete system.@button[0]
uci delete system.@button[1]
uci add system button
uci set system.@button[0]=button
uci set system.@button[0].button=reset
uci set system.@button[0].action=released
uci set system.@button[0].handler="logger reboot"
uci set system.@button[0].min=0
uci set system.@button[0].max=4
uci add system button
uci set system.@button[1]=button
uci set system.@button[1].button=reset
uci set system.@button[1].action=released
uci set system.@button[1].handler="logger factory default"
uci set system.@button[1].min=5
uci set system.@button[1].max=30
';
  print 'sleep 1</pre>';

  _outln_comment();
  _outln_comment();
  _outln_comment(t('SNMP Settings'));
  $loc = node_load(array('nid' => $dev->nid));
  print '<pre>';
  print 'uci set snmpd.@system[0]=system
uci set snmpd.@system[0].sysLocation='.$loc->nick.'
uci set snmpd.@system[0].sysContact='.$loc->notification.'
uci set snmpd.@system[0].sysName=guifi.net
uci set snmpd.@system[0].sysDescr="Xarxa Oberta, Lliure i Neutral"
';
  print 'sleep 1</pre>';

  _outln_comment();
  _outln_comment();
  _outln_comment(t('LLDP Settings'));
  $loc = node_load(array('nid' => $dev->nid));
  print '<pre>';
  print 'uci set lldpd.config=lldpd
uci set lldpd.config.enable_cdp=1
uci set lldpd.config.enable_fdp=1
uci set lldpd.config.enable_sonmp=1
uci set lldpd.config.enable_edp=1
uci set lldpd.config.lldp_class=4
uci set lldpd.config.lldp_location=2:ES:6:'.$loc->nick.':3:guifi.net:19:'.$dev->id.'
';
  print 'sleep 1</pre>';

//FILE PASSWD
  $file_pass='
root:WLL3bqv6fH7qM:0:0:root:/root:/bin/ash
nobody:*:65534:65534:nobody:/var:/bin/false
daemon:*:65534:65534:daemon:/var:/bin/false
quagga:x:51:51:quagga:/tmp/.quagga:/bin/false
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/passwd'));
  openwrt_out_file($file_pass,'/etc/passwd');
 print '<pre>sleep 1</pre>
';

}
  switch ($dev->variable['model_id']) {
    case "1":	// WRT54Gv1-4
    case "15":	// WHR-HP-G54, WHR-G54S (BUFFALO)
    case "16":	// WRT54GL
      $firmware_tftp = 'broadcom/openwrt-wrt54g-squashfs.bin';
      $firmware = 'broadcom/openwrt-brcm-2.4-squashfs.trx';
      break;
    case "17":	// WRT54GSv1-2
      $firmware_tftp = 'broadcom/openwrt-wrt54gs-squashfs.bin';
      $firmware = 'broadcom/openwrt-brcm-2.4-squashfs.trx';
      break;
    case "18":	// WRT54GSv4
      $firmware_tftp = 'broadcom/openwrt-wrt54gs_v4-squashfs.bin';
      $firmware = 'broadcom/openwrt-brcm-2.4-squashfs.trx';
      break;
    case "25": // NanoStation2
    case "34": // NanoStationLoco2
    case "36": // Bullet2
      $firmware_tftp = 'atheros/openwrt-ns2-squashfs.bin';
      $firmware = 'atheros/openwrt-ns2-squashfs.bin';
      break;
    case "26": // NanoStation5
    case "35": // NanoStationLoco5
    case "37": // Bullet5
      $firmware_tftp = 'atheros/openwrt-ns5-squashfs.bin';
      $firmware = 'atheros/openwrt-ns5-squashfs.bin';
      break;
    case "38":	// RouterStation
      $firmware_tftp = 'ar7xx/openwrt-ar71xx-ubnt-rs-squashfs.bin';
      $firmware = 'ar7xx/openwrt-ar71xx-ubnt-rs-squashfs.bin';
      break;
  }
    $model = db_fetch_object(db_query("
      SELECT *
      FROM {guifi_model_specs}
      WHERE mid=%d", $dev->variable['model_id']));


  _outln_comment(''.$model->model.'');
  _outln_comment(' radio:     '.$dev->id.'-'.$dev->nick);
  _outln_comment();
  _outln_comment('unsolclic version: '.$version);
  _outln_comment();
  _outln_comment(t('TFTP method:'));
    if ($dev->radios[0]['mode'] == 'ap') {
  _outln_comment(t('<a href="'.base_path().'files/openwrt/ap/'.$firmware_tftp.'"> Click here to download firmware OpenWRT Kamikaze file: '.$firmware_tftp.'.</a>'));
   }
   else {
  _outln_comment(t('<a href="'.base_path().'files/openwrt/client/'.$firmware_tftp.'"> Click here to download firmware OpenWRT Kamikaze file: '.$firmware_tftp.'.</a>'));
   }
  _outln_comment();
  _outln_comment(t('Web Browser method:'));
    if ($dev->radios[0]['mode'] == 'ap') {
  _outln_comment(t('<a href="'.base_path().'files/openwrt/ap/'.$firmware.'"> Click here to download firmware OpenWRT Kamikaze file: '.$firmware.'.</a>'));
   }
   else {
  _outln_comment(t('<a href="'.base_path().'files/openwrt/client/'.$firmware.'"> Click here to download firmware OpenWRT Kamikaze file: '.$firmware.'.</a>'));
   }

  _outln_comment(t('Put the mouse cursor over the link. Right click the link and select "Save Link/Target As..." to save to your Desktop.'));
  _outln_comment();
  _out();

  // print files
  guifi_kamikaze_files($dev, $zone);
  guifi_kamikaze_common_files($dev, $zone);

  _outln_comment();
  _outln_comment(t('end of script and reboot'));
  print '<pre>';
  print 'uci commit
reboot';
  print '</pre>';
}
 function atheros_channel($radio) {

   if ($radio[channel] < 5000) {
      $band = '11b';
      $channel = $radio[channel];
    }
    else {
      $band = '11a';
      switch ($radio[channel]) {
        case "5180":
          $channel= '36';
        break;
        case "5200":
          $channel= '40';
        break;
        case "5220":
          $channel= '44';
        break;
        case "5240":
          $channel= '48';
        break;
        case "5260":
          $channel= '52';
        break;
        case "5280":
          $channel= '56';
        break;
        case "5300":
          $channel= '60';
        break;
        case "5320":
          $channel= '64';
        break;
        case "5500":
          $channel= '100';
        break;
        case "5520":
          $channel= '104';
        break;
        case "5540":
          $channel= '108';
        break;
        case "5560":
          $channel= '112';
        break;
        case "5580":
          $channel= '116';
        break;
        case "5600":
          $channel= '120';
        break;
        case "5620":
          $channel= '124';
        break;
        case "5640":
          $channel= '128';
        break;
        case "5660":
          $channel= '132';
        break;
        case "5680":
          $channel= '138';
        break;
        case "5700":
          $channel= '140';
        break;
        default:
          $channel= '36';
      }
    }
return $channel; 
}
?>