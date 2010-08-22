<?php


function guifi_kamikaze_files($dev,$zone) {
//SOME VARIABLES
  foreach ($dev->radios[0]['interfaces'] as $interface_id => $interface) 
    foreach ($interface['ipv4'] as $ipv4_id => $ipv4) 
      if (isset($ipv4['links'])) foreach ($ipv4['links'] as $key => $link) {
        if ($link['link_type'] == 'ap/client') {
          $gateway = $link['interface']['ipv4']['ipv4'];
        }
      }
  $wan = guifi_unsolclic_if($dev->id,'Wan');
  $dns = guifi_get_dns($zone,2);
  list($ntp1,$ntp2) = explode(' ',guifi_get_ntp($zone,2));
    $ntp[] .= $ntp1;
    $ntp[] .= $ntp2;
  $apssid = 'guifi.net-'.guifi_get_ap_ssid($link['interface']['device_id'],$link['interface']['radiodev_counter']);
  $wireless_model = 0;
  $wireless_iface = 0;

  switch ($dev->variable['model_id']) {
    case "1": case "15": case "16": case "17": case "18":	
    // WRT54Gv1-4, WHR-HP-G54, WHR-G54S (BUFFALO), WRT54GL, WRT54GSv1-2, WRT54GSv4
      $wireless_model='broadcom';
      $wireless_iface='wl0';
      $vlans='config switch eth0
        option vlan0    \"1 2 3 4 5*\"
        option vlan1    \"0 5\"
      ';
      $mode=NULL;
      $lan_iface='eth0.0';    
      $wan_iface='eth0.1';     
      $txant='txant';
      $rxant='rxant';
      $packages='broadcom/packages';
      break;

    default:
      _outln_comment('model id not supported');
      exit;
  }

  if (empty($dev->radios[0][antenna_mode]))
    $dev->radios[0][antenna_mode]= 'Main';
      if ($dev->radios[0][antenna_mode] != 'Main') 
        $dev->radios[0][antenna_mode]= '1';
      else
        $dev->radios[0][antenna_mode]= '0';

// SECTION FILES

// FILE NETWORK
  $file_network='
'.$vlans.'
config interface loopback
        option \'ifname\'  \'lo\'
        option \'proto\'    \'static\'
        option \'ipaddr\'   \'127.0.0.1\'
        option \'netmask\'  \'255.0.0.0\'

config interface lan
        option \'ifname\'   \''.$lan_iface.'\'
        option \'type\'     \'bridge\'
        option \'proto\'    \'static\'
        option \'ipaddr\'   \'192.168.1.1\'
        option \'netmask\'  \'255.255.255.0\'
        option \'dns\'      \''.$dns.'\'

config interface wan
        option \'ifname\'   \''.$wan_iface.'\'
        option \'proto\'    \'static\'
        option \'ipaddr\'   \''.$wan->ipv4.'\'
        option \'netmask\'  \''.$wan->netmask.'\'
        option \'gateway\'  \''.$gateway.'\'
        option \'dns\'      \''.$dns.'\'
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/network'));
  _out_file($file_network,'/etc/config/network');

// FILE WIRELESS
  $file_wireless='
config \'wifi-device\' \''.$wireless_iface.'\'
        option \'type\' \''.$wireless_model.'\'
        option \'disabled\' \'0\'
        option \''.$txant.'\' \''.$dev->radios[0][antenna_mode].'\'
        option \''.$rxant.'\' \''.$dev->radios[0][antenna_mode].'\'
        '.$mode.'

config wifi-iface
        option \'device\' \''.$wireless_iface.'\'
        option \'network\' \'wan\'
        option \'mode\' \'sta\'
        option \'ssid\' \''.$apssid.'\'
        option \'encryption \'none\'
';

  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/wireless'));
  _out_file($file_wireless,'/etc/config/wireless');

//FILE FIREWALL
  $firewall='
config defaults
        option \'syn_flood\' \'1\'
        option \'input\' \'ACCEPT\'
        option \'output\' \'ACCEPT\'
        option \'forward\' \'REJECT\'

config zone
        option \'name\' \'lan\'
        option \'input\' \'ACCEPT\'
        option \'output\' \'ACCEPT\'
        option \'forward\' \'REJECT\'

config zone
        option \'name\' \'wan\'
        option \'output\' \'ACCEPT\'
        option \'input\' \'ACCEPT\'
        option \'forward\' \'REJECT\'
        option \'masq\' \'1\'

config forwarding
        option \'src\' \'lan\'
        option \'dest\' \'wan\'

config rule
        option \'dst\'              \'wan\'
        option \'src_dport\'        \'22\'
        option \'target\'           \'ACCEPT\'
        option \'protocol\'         \'tcp\'

config rule
        option \'dst\'              \'wan\'
        option \'src_dport\'        \'80\'
        option \'target\'           \'ACCEPT\'
        option \'protocol\'         \'tcp\'

config rule
        option \'dst\'              \'wan\'
        option \'src_dport\'        \'161\'
        option \'target\'           \'ACCEPT\'
        option \'protocol\'         \'udp\'
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/firewall'));
  _out_file($firewall,'/etc/config/firewall');

//FILE OPKG
  $opkg_conf='
src/gz guifi http://ausa.guifi.net/drupal/files/openwrt/client/'.$packages.'
dest root /
dest ram /tmp
lists_dir ext /var/opkg-lists
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/opkg.conf'));
  _out_file($opkg_conf,'/etc/opkg.conf');
}


?>