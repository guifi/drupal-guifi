<?php

function guifi_kamikaze_files($dev,$zone) {
//SOME VARIABLES

  $lan = guifi_unsolclic_if($dev->id,'wLan/Lan');
  $wan = guifi_unsolclic_if($dev->id,'Wan');
  $dns = guifi_get_dns($zone,2);
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
  $wds_links = array();
  $wds_str = '';
  foreach ($dev->radios as $radio_id => $radio) 
    foreach ($radio[interfaces] as $interface_id => $interface) 
      if ($interface['interface_type'] == 'wds/p2p')
        foreach ($interface[ipv4] as $ipv4_id => $ipv4) 
          foreach ($ipv4[links] as $key => $link) {
            if ($link['link_type'] == 'wds')
              $wds_links[] = $link ;
            $iplocal[] = $ipv4 ;
            $iflocal[] = $interface ;
  }
  if (count($wds_links) == 0)
    return;

// SECTION FILES
// FILE NETWORK
  $lan_network = _ipcalc($lan->ipv4,$lan->netmask);
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
        option \'ipaddr\'   \''.$lan->ipv4.'\'
        option \'netmask\'  \''.$lan->netmask.'\'
        option \'gateway\'  \'0.0.0.0\'
        option \'dns\'      \''.$dns.'\'

config interface wan
        option \'ifname\'   \''.$wan_iface.'\'
        option \'proto\'    \'none\'
        
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/network'));
  _out_file($file_network,'/etc/config/network');

// FILE WIRELESS

  $file_wireless='
config \'wifi-device\' \''.$wireless_iface.'\'
        option \'type\' \''.$wireless_model.'\'
        option \'channel\' \''.$dev->radios[0][channel].'\'
        option \'disabled\' \'0\'
        option \''.$txant.'\' \''.$dev->radios[0][antenna_mode].'\'
        option \''.$rxant.'\' \''.$dev->radios[0][antenna_mode].'\'

config wifi-iface
        option \'device\' \''.$wireless_iface.'\'
        option \'network\' \'lan\'
        option \'mode\' \'ap\'
        option \'ssid\' \'guifi.net-'.guifi_to_7bits($dev->radios[0][ssid]).'\'
        option \'encryption \'none\'
';

  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/wireless'));
  _out_file($file_wireless,'/etc/config/wireless');

// WDS Links
  $ifcount = 1;
    foreach ($wds_links as $key => $wds) {
    $hostname = guifi_get_hostname($wds['device_id']);
      if (preg_match("/(Working|Testing|Building)/",$wds['flag'])) {
        $status = 'active';
        if ($wds['routing'] == 'BGP') 
          $bgpd='1'; 
        if ($wds['routing'] == 'OSPF') 
          $ospfd='1';
        
        $ifcount++;    
        $wds_won='option \'bssid\' \''.$wds['interface']['mac'].'\'';
        $wds_non='option \'proto\' \'static\'
        option \'ifname\' \'wds0.'.($key+1).'\'
        option \'ipaddr\' \''.$iplocal[$key][ipv4].'\'
        option \'netmask\' \''.$iplocal[$key][netmask].'\'';
      }
      else {
        $status = 'disabled';
        $wds_won='option \'bssid\' \'00:00:00:00:00:00\'';
        $wds_non='option \'proto\' \'none\'';
      }
      $wds_network ='config \'interface\' \'wds_'.$hostname.'\'
        '.$wds_non.'
        ';
      $wds_wireless ='config \'wifi-iface\'
        option \'device\' \'wl0\'
        option \'network\' \'wds_'.$hostname.'\'
        option \'mode\' \'wds\'
        option \'encryption\' \'none\'
        '.$wds_won.'
        ';
        _outln_comment();
        _outln_comment('WDS '.$hostname.'');
        _outln_comment('Routing: '.$wds['routing'].'');
        _outln_comment('Status: '.$status.'');
         print '<pre>echo "'.$wds_network.'
" >> /etc/config/network
          </pre>';
         print '<pre>echo "'.$wds_wireless.'
" >> /etc/config/wireless
          </pre>';
    }

    if (count($wds_links) >= 5)
      return;

// QUAGGA CONFIG FILES
  $file_zebra='';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/quagga/zebra.conf')); 
  _out_file($file_zebra,'/etc/quagga/zebra.conf');

// FILE OSPFD
  if ($ospfd == '1') {
    _outln_comment();
    _outln_comment();
    _outln_comment(t('File /etc/quagga/ospfd.conf')); 
    print '<pre>mv /etc/quagga/ospfd.conf /etc/quagga/ospfd.conf.bak
echo "
!
interface br-lan
!
router ospf
 ospf router-id '.$lan->ipv4.'
 redistribute bgp
 network '.$lan_network[netid].'/'.$lan_network[maskbits].' area 0<br />';
    foreach ($wds_links as $key => $wds) {
      if ($wds['routing'] == 'OSPF') {
        $wds_network = _ipcalc($iplocal[$key][ipv4],$iplocal[$key][netmask]);
        print ' network '.$wds_network[netid].'/'.$wds_network[maskbits].' area 0<br />';
      }
    }
    print 'default-information originate
!
" > /etc/quagga/ospfd.conf</pre>';
  }

// FILE BGPD
  if ($bgpd == '1') {
    _outln_comment();
    _outln_comment();
    _outln_comment(t('File /etc/quagga/bgpd.conf'));
     print '<pre>mv /etc/quagga/bgpd.conf /etc/quagga/bgpd.conf.bak
echo "
!
interface br-lan
!
router bgp '.$dev->id.'
bgp router-id '.$lan->ipv4.'
 network '.$lan_network[netid].'/'.$lan_network[maskbits].'<br />';
    foreach ($wds_links as $key => $wds) {
      if ($wds['routing'] == 'BGP') {
        $wds_network = _ipcalc($iplocal[$key][ipv4],$iplocal[$key][netmask]);
          print ' network '.$wds_network[netid].'/'.$wds_network[maskbits].'<br />';
      }
    }
    print 'redistribute ospf
';
  foreach ($dev->radios as $radio_id => $radio) 
    foreach ($radio[interfaces] as $interface_id => $interface)
      foreach ($interface[ipv4] as $ipv4_id => $ipv4)
        foreach ($ipv4[links] as $link_id => $link)
          if ($link['routing'] == 'BGP')
            print 'neighbor '.$link['interface']['ipv4']['ipv4'].' remote-as '.$link['device_id'].'
';

    print '" > /etc/quagga/bgpd.conf</pre>';
  }
  
//FILE FIREWALL
  $firewall='
config defaults
        option \'syn_flood\' \'1\'
        option \'input\' \'ACCEPT\'
        option \'output\' \'ACCEPT\'
        option \'forward\' \'ACCEPT\'
        
config zone
        option \'name\' \'lan\'
        option \'input\' \'ACCEPT\'
        option \'output\' \'ACCEPT\'
        option \'forward\' \'ACCEPT\'

';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/firewall'));
  _out_file($firewall,'/etc/config/firewall');

//FILE OPKG
  $opkg_conf='
src/gz guifi http://ausa.guifi.net/drupal/files/openwrt/ap/'.$packages.'
dest root /
dest ram /tmp
lists_dir ext /var/opkg-lists
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/opkg.conf'));
  _out_file($opkg_conf,'/etc/opkg.conf');


  $dhcp_statics = array();
  $max = explode(".",$dev->ipv4);

  function merge_static($link, &$dhcp_statics,&$max,&$curr) {
    if (empty($link['interface'][mac]))
      $link['interface'][mac] = 'FF:FF:FF:FF:FF:FF'; 
    $dhcp_statics[] = array($link['interface'][ipv4][ipv4],$link['interface'][mac],guifi_get_hostname($link['interface'][device_id]));
    $curr = explode(".",$link['interface'][ipv4][ipv4]);
    if ($curr[3] > $max[3]) {
      $max[3] = $curr[3];
    }
  }

  $main_ip = guifi_main_ip($dev->id);
  $item = _ipcalc_by_netbits($main_ip[ipv4],$main_ip[maskbits]);
  $max = explode(".",$main_ip[ipv4]);
 
  // cable links
  if (!empty($dev->interfaces)) foreach ($dev->interfaces as $interface) 
  if (!empty($interface[ipv4])) foreach ($interface[ipv4] as $ipv4) 
  if (!empty($ipv4[links]))     foreach ($ipv4[links] as $link) 
  {
    if ($link['interface'][ipv4][ipv4] != '') {
      $item2 = _ipcalc($link['interface'][ipv4][ipv4], $link['interface'][ipv4][netmask]); 
      if ($item[netid] == $item2[netid])
        merge_static($link,$dhcp_statics,$max,$cur);
    }
  }

  // ap/client links
  if (!empty($dev->radios))       foreach ($dev->radios as $radio) 
  if (!empty($radio[interfaces])) foreach ($radio[interfaces] as $interface) 
  if (!empty($interface[ipv4]))   foreach ($interface[ipv4] as $ipv4) 
  if (!empty($ipv4[links]))       foreach ($ipv4[links] as $link) 
  {
    if (($link['link_type'] == 'ap/client') and (!empty($link['interface'][ipv4][ipv4]))) 
    merge_static($link,$dhcp_statics,$max,$cur);
  }
  $statics = count($dhcp_statics) - 1;
  $totalstatics = count($dhcp_statics);
  $first = explode(".",$item[netid]);
  $last = explode(".",$item[broadcast]);
  $limit =  ((($last[3] - 1) - ($first[3] + 3)) - ($totalstatics));

  if ($statics == -1) {
    _outln_comment();
    _outln_comment(t('File /etc/config/luci_ethers'));
    _outln_nvram('dhcp_start',($max[3] + 2));

  }
  _outln_comment();
  _outln_comment(t('File /etc/config/luci_ethers'));
  print 'echo "';
    foreach ($dhcp_statics as $static) {
  print '<pre>
## Device: '.$static[2].'
config \'static_lease\'
        option \'macaddr\' \''.$static[1].'\'
        option \'ipaddr\' \''.$static[0].'\'
</pre>';
}
    print '" > /etc/config/luci_ethers<br />';

// FILE DHCP  
 $file_dhcp ='
config \'dnsmasq\'
        option \'domainneeded\' \'1\'
        option \'boguspriv\' \'1\'
        option \'filterwin2k\' \'0\'
        option \'localise_queries\' \'1\'
        option \'local\' \'/lan/\'
        option \'domain\' \'lan\'
        option \'expandhosts\' \'1\'
        option \'nonegcache\' \'0\'
        option \'authoritative\' \'1\'
        option \'readethers\' \'1\'
        option \'leasefile\' \'/tmp/dhcp.leases\'
        option \'resolvfile\' \'/tmp/resolv.conf.auto\'

config \'dhcp\' \'lan\'
        option \'interface\' \'lan\'
        option \'leasetime\' \'12h\'
        option \'start\' \''.($max[3] + 2).'\'
        option \'limit\' \''.$limit.'\'
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/dhcp'));
  _out_file($file_dhcp,'/etc/config/dhcp');

 
}

?>