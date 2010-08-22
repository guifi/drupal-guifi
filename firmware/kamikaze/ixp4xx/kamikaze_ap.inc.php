<?php

function guifi_kamikaze_files($dev,$zone) {
//SOME VARIABLES

  $dns = guifi_get_dns($zone,2);
  $lan = guifi_unsolclic_if($dev->id,'wLan/Lan');
  $lan_network = _ipcalc($lan->ipv4,$lan->netmask);

  switch ($dev->variable['model_id']) {
    case "39":	
    // Avila GW2348-4
      $wireless_model='atheros';
      $lan_iface='eth0';    
      $lan2_iface='eth1';     

      $packages='ixp4xx/packages';
      break;
    default:
      _outln_comment('model id not supported');
      exit;
  }

// SECTION FILES

  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/wireless'));
  
  function wds_add($dev,$radio) {
    $wds_links = array();
    foreach ($radio[interfaces] as $interface_id => $interface) {
      if ($interface[interface_type] == 'wds/p2p') {
        foreach ($interface[ipv4] as $ipv4_id => $ipv4)
          foreach ($ipv4[links] as $link_id => $link) 
            if ($link['link_type'] == 'wds')
              $wds_links[] = $link;
              $ifcount = 0;
              foreach ($wds_links as $key => $wds) {
                if (preg_match("/(Working|Testing|Building)/",$wds['flag'])) {
                  $status = 'active';
                  print '        option \'wds_add'.$ifcount.'\' \''.$wds['interface']['mac'].'\'<br />';
                  $ifcount++;
                }
                else {
                  $status = 'disabled';
                  print '# option \'wds_addX\' \''.$wds['interface']['mac'].'\'# '.t($wds['flag']).'<br />';
                }
             }
       }
    }
  }

  function wds_network($dev,$radio) {
    $ifcount = '0';
    foreach ($radio[interfaces] as $interface_id => $interface)
      foreach ($interface[ipv4] as $ipv4_id => $ipv4)
        foreach ($ipv4[links] as $link_id => $link)
          if ($link['link_type'] == 'wds') {
            $wds_links = array();  
            $wds_links[] = $link;
            foreach ($wds_links as $key => $wds) {
              $hostname = guifi_get_hostname($wds['device_id']);
              if (preg_match("/(Working|Testing|Building)/",$wds['flag'])) {
                $status = 'active';
                print '
## wds_'.$hostname.'
config \'interface\' \'wds_'.$hostname.'\'
        option \'ifname\' \'ath'.$radio[radiodev_counter].'.wds'.$ifcount.'\'
        option \'proto\' \'static\'
';
        $ifcount++;
              }
              else {
                $status = 'disabled';
                print '
##### '.t($wds['flag']).' ####
## wds_'.$hostname.'
# config \'interface\' \'ath'.$radio[radiodev_counter].'.wdsX\'
#       option \'proto\' \'none\'
';
              } 
            }
          if ($status == 'active') {
            print '        option \'ipaddr\' \''.$ipv4[ipv4].'\'
        option \'netmask\' \''.$ipv4['netmask'].'\'
';
              }
              else {
            print '#       option \'ipaddr\' \''.$ipv4[ipv4].'\'
#       option \'netmask\' \''.$ipv4['netmask'].'\'
';
              }
          }
  }

  function cable_network($dev) {
    foreach ($dev->interfaces as $interface_id => $interface) {
      switch ($interface[interface_type]) {
        case 'vlan1': $iname = 'eth0:1'; break;
        case 'vlan2': $iname = 'eth1'; break;
        case 'vlan3': $iname = 'eth2'; break;
        default:
          $iname = $interface[interface_type];
        break;
      }

      foreach ($interface[ipv4] as $ipv4_id => $ipv4)
        foreach ($ipv4[links] as $link_id => $link) {
          $network = guifi_get_hostname($link[device_id]);
          if (preg_match("/(Working|Testing|Building)/",$link['flag'])) {
            $status = 'active';
            if ($interface[interface_type] != 'wLan/Lan') {
              print '
## cable_'.$network.'
config \'interface\' \''.$network.'\'
        option \'ifname\'   \''.$iname.'\'
';
              print '        option \'proto\'    \'static\'
        option \'ipaddr\'   \''.$ipv4[ipv4].'\'
        option \'netmask\'  \''.$ipv4[netmask].'\'

';
              }
            }
            else {
              $status = 'disabled';
              print '
##### '.t($link['flag']).' ####
## cable_'.$network.'
# config \'interface\' \''.$network.'\'
#        option \'ifname\'   \''.$iname.'\'
';
              print '#        option \'proto\'    \'static\'
#        option \'ipaddr\'   \''.$ipv4[ipv4].'\'
#        option \'netmask\'  \''.$ipv4[netmask].'\'

';
            }
          }
    }
  }

  print '<pre>
echo "
';

  if (isset($dev->radios)) foreach ($dev->radios as $radio_id => $radio) {
    $mode = 'ap';
    $ssid = $radio[ssid];
    $channel = atheros_channel($radio);
   if ($channel < 14)
      $band = '11b';
    else
      $band = '11a';
    if (empty($radio[antenna_mode])) {
      $radio[antenna_mode]= '1';
    }
    else {
      if ($radio[antenna_mode] != 'Main')
        $radio[antenna_mode]= '2';
      else
        $radio[antenna_mode]= '1';
    }
    
    if ($radio_id == '0') {
      $wireless_iface = 'wifi0';
      $network = 'lan';
    } else {
      $wireless_iface = 'wifi'.$radio_id;
      $network = 'wlan'.($radio_id+1);
    }

  $wireless_model = 'atheros';
    $txant='txantenna';
    $rxant='rxantenna';
     
  print '
## Radio: '.$radio[ssid].'
config \'wifi-device\' \''.$wireless_iface.'\'
        option \'type\' \''.$wireless_model.'\'
        option \'channel\' \''.$channel.'\'
        option \'disabled\' \'0\'
        option \'hwmode\' \''.$band.'\'
        option \'diversity\' \'0\'
        option \''.$txant.'\' \''.$radio[antenna_mode].'\'
        option \''.$rxant.'\' \''.$radio[antenna_mode].'\'
        option \'txpower\' \'16\'

config wifi-iface
        option \'device\' \''.$wireless_iface.'\'
        option \'network\' \''.$network.'\'
        option \'agmode\' \''.$mode.'\'
        option \'ssid\' \'guifi.net-'.$radio[ssid].'\'
        option \'encryption\' \'none\'
';
  wds_add($dev,$radio);
}
  print '
" > /etc/config/wireless </pre>
';

  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/network'));
  print '<pre>
echo "
config interface loopback
        option \'ifname\'  \'lo\'
        option \'proto\'    \'static\'
        option \'ipaddr\'   \'127.0.0.1\'
        option \'netmask\'  \'255.0.0.0\'

';
  if (isset($dev->radios)) foreach ($dev->radios as $radio_id => $radio) {
    if (isset($radio[interfaces])) foreach ($radio[interfaces] as $interface_id => $interface) {
      if ($interface[interface_type] != 'wds/p2p') {
        if (isset($interface[ipv4])) foreach ($interface[ipv4] as $ipv4_id => $ipv4) {
          if ($interface[interface_type] == 'wLan/Lan') {
            $iface = 'ath0 eth0';
            $network = 'lan';
          } else {
            $iface = 'wifi'.($radio_id);
            $network = 'wlan'.($radio_id+1);
          }
          $item = _ipcalc($ipv4[ipv4],$ipv4[netmask]);

// FILE NETWORK
  print '
config interface '.$network.'
        option \'ifname\'   \''.$iface.'\'
';
          if ($interface[interface_type] == 'wLan/Lan') 
print '        option \'type\'     \'bridge\'
';
print '        option \'proto\'    \'static\'
        option \'ipaddr\'   \''.$ipv4[ipv4].'\'
        option \'netmask\'  \''.$ipv4[netmask].'\'
        option \'dns\'      \''.$dns.'\'

';
         } 
       }
}     

  wds_network($dev, $radio);

}
  cable_network($dev);
print '
" > /etc/config/network </pre>
';


  $wds_links = array();
  foreach ($dev->radios as $radio_id => $radio)
    foreach ($radio[interfaces] as $interface_id => $interface)
      if ($interface[interface_type] == 'wds/p2p')
        foreach ($interface[ipv4] as $ipv4_id => $ipv4)
          foreach ($ipv4[links] as $link_id => $link) 
            if ($link['link_type'] == 'wds')
              $wds_links[] = $link;
              foreach ($wds_links as $key => $wds) {
                if ($wds['routing'] == 'BGP') 
                  $wds_bgpd='1'; 
                if ($wds['routing'] == 'OSPF') 
                  $wds_ospfd='1';
              }

  $cable_links = array();
    foreach ($dev->interfaces as $interface_id => $interface)
      foreach ($interface[ipv4] as $ipv4_id => $ipv4)
        foreach ($ipv4[links] as $link_id => $link)
          if ($link['link_type'] == 'cable')
            $cable_links[] = $link;
            foreach ($cable_links as $key => $cable) {
              if ($cable['routing'] == 'BGP') 
                $cable_bgpd='1'; 
              if ($cable['routing'] == 'OSPF') 
                $cable_ospfd='1';
            }

// QUAGGA CONFIG FILES
  $file_zebra='';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/quagga/zebra.conf')); 
  _out_file($file_zebra,'/etc/quagga/zebra.conf');

// FILE OSPFD
  if (($wds_ospfd == '1') || ($cable_ospfd == '1')){
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
';
    foreach ($dev->radios as $radio_id => $radio)
      foreach ($radio[interfaces] as $interface_id => $interface)
        if (($interface[interface_type] == 'wLan') || ($interface[interface_type] == 'wLan/Lan'))  {
          foreach ($interface[ipv4] as $ipv4_id => $ipv4)
            $network = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
              if (preg_match("/(Working|Testing|Building)/",$link['flag']))
                print ' network '.$network[netid].'/'.$network[maskbits].' area 0<br />';

        }
    foreach ($wds_links as $key => $wds) {
      $iplocal[] = $wds['interface']['ipv4'];
      if ($wds['routing'] == 'OSPF') {
        $wds_network = _ipcalc($iplocal[$key][ipv4],$iplocal[$key][netmask]);
          if (preg_match("/(Working|Testing|Building)/",$wds['flag']))
            print ' network '.$wds_network[netid].'/'.$wds_network[maskbits].' area 0<br />';
      }
    }
    foreach ($dev->interfaces as $interface_id => $interface)
      foreach ($interface[ipv4] as $ipv4_id => $ipv4)
        foreach ($ipv4[links] as $link_id => $link) {
          $item = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
            if ($link['routing'] == 'OSPF') {
              if (preg_match("/(Working|Testing|Building)/",$link['flag']))
                print ' network '.$item[netid].'/'.$item[maskbits].' area 0<br />';
            }
        }
    print 'default-information originate
!
" > /etc/quagga/ospfd.conf</pre>';
  }

// FILE BGPD
  if (($wds_bgpd == '1') || ($cable_bgpd == '1')){
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
';
    foreach ($dev->radios as $radio_id => $radio)
      foreach ($radio[interfaces] as $interface_id => $interface)
      if (($interface[interface_type] == 'wLan') || ($interface[interface_type] == 'wLan/Lan'))  {
        foreach ($interface[ipv4] as $ipv4_id => $ipv4)
        $network = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
        print ' network '.$network[netid].'/'.$network[maskbits].'<br />';

      }
    print 'redistribute ospf
';
    foreach ($wds_links as $key => $wds) {
      $iplocal[] = $wds['interface']['ipv4'];
      if ($wds['routing'] == 'BGP') {
        $wds_network = _ipcalc($iplocal[$key][ipv4],$iplocal[$key][netmask]);
          if (preg_match("/(Working|Testing|Building)/",$wds['flag']))
            print ' network '.$wds_network[netid].'/'.$wds_network[maskbits].'<br />';
      }
    }
    foreach ($dev->interfaces as $interface_id => $interface)
      foreach ($interface[ipv4] as $ipv4_id => $ipv4)
        foreach ($ipv4[links] as $link_id => $link) {
          $item = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
            if ($link['routing'] == 'BGP') {
              if (preg_match("/(Working|Testing|Building)/",$link['flag']))
                print ' network '.$item[netid].'/'.$item[maskbits].'<br />';
            }
        }

  foreach ($dev->radios as $radio_id => $radio) 
    foreach ($radio[interfaces] as $interface_id => $interface)
      foreach ($interface[ipv4] as $ipv4_id => $ipv4)
        foreach ($ipv4[links] as $link_id => $link)
          if ($link['routing'] == 'BGP') {
            if (preg_match("/(Working|Testing|Building)/",$link['flag']))
              print ' neighbor '.$link['interface']['ipv4']['ipv4'].' remote-as '.$link['device_id'].'
';
         }
    foreach ($dev->interfaces as $interface_id => $interface)
      foreach ($interface[ipv4] as $ipv4_id => $ipv4)
        foreach ($ipv4[links] as $link_id => $link)
            if ($link['routing'] == 'BGP') {
              if (preg_match("/(Working|Testing|Building)/",$link['flag']))
                print ' neighbor '.$link['interface']['ipv4']['ipv4'].' remote-as '.$link['device_id'].'
';
            }
    print '" > /etc/quagga/bgpd.conf</pre>';
  }
  
//FILE FIREWALL
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/firewall'));
  print '<pre>
echo "
config defaults
        option \'syn_flood\' \'1\'
        option \'input\' \'ACCEPT\'
        option \'output\' \'ACCEPT\'
        option \'forward\' \'ACCEPT\'
';
    foreach ($dev->radios as $radio_id => $radio)
      foreach ($radio[interfaces] as $interface_id => $interface)
      if (($interface[interface_type] == 'wLan') || ($interface[interface_type] == 'wLan/Lan'))  {
        foreach ($interface[ipv4] as $ipv4_id => $ipv4) {
          if ($interface[interface_type] == 'wLan/Lan') {
            $network = 'lan';
          } else {
            $network = 'wlan'.($radio_id+1);
          }
          print'        
config zone
        option \'name\' \''.$network.'\'
        option \'input\' \'ACCEPT\'
        option \'output\' \'ACCEPT\'
        option \'forward\' \'ACCEPT\'

';
      }
    }
      if ($interface[interface_type] == 'wds/p2p')
        foreach ($interface[ipv4] as $ipv4_id => $ipv4)
          foreach ($ipv4[links] as $link_id => $link) 
            if ($link['link_type'] == 'wds')
              $ifcount = 0;
              foreach ($wds_links as $key => $wds) {
                $hostname = guifi_get_hostname($wds['device_id']);
                if (preg_match("/(Working|Testing|Building)/",$wds['flag'])) 
                  print'        
config zone
        option \'name\' \'wds_'.$hostname.'\'
        option \'input\' \'ACCEPT\'
        option \'output\' \'ACCEPT\'
        option \'forward\' \'ACCEPT\'

';
                
      }
    foreach ($dev->interfaces as $interface_id => $interface)
      foreach ($interface[ipv4] as $ipv4_id => $ipv4)
        foreach ($ipv4[links] as $link_id => $link) {
                $hostname = guifi_get_hostname($link['device_id']);
                if (preg_match("/(Working|Testing|Building)/",$link['flag'])) {
                  print'        
config zone
        option \'name\' \''.$hostname.'\'
        option \'input\' \'ACCEPT\'
        option \'output\' \'ACCEPT\'
        option \'forward\' \'ACCEPT\'

';}}
      print '" > /etc/config/firewall</pre>';

//FILE OPKG
  $opkg_conf='
src/gz snapshots http://downloads.openwrt.org/snapshots/'.$packages.'
dest root /
dest ram /tmp
lists_dir ext /var/opkg-lists
option overlay_root /jffs
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
    if ($curr[3] > $max[3])
      $max[3] = $curr[3];
  }

  $main_ip = guifi_main_ip($dev->id);
  $item = _ipcalc_by_netbits($main_ip[ipv4],$main_ip[maskbits]);
  $max = explode(".",$main_ip[ipv4]);
 
  // cable links
  foreach ($dev->interfaces as $interface) 
    foreach ($interface[ipv4] as $ipv4) 
      foreach ($ipv4[links] as $link) {
        if ($link['interface'][ipv4][ipv4] != '') {
          $item2 = _ipcalc($link['interface'][ipv4][ipv4], $link['interface'][ipv4][netmask]); 
            if ($item[netid] == $item2[netid])
              merge_static($link,$dhcp_statics,$max,$cur);
        }
      }

  // ap/client links
  foreach ($dev->radios as $radio) 
    foreach ($radio[interfaces] as $interface) 
     foreach ($interface[ipv4] as $ipv4) 
       foreach ($ipv4[links] as $link)
         if (($link['link_type'] == 'ap/client') and (!empty($link['interface'][ipv4][ipv4]))) 
           merge_static($link,$dhcp_statics,$max,$cur);

  $statics = count($dhcp_statics) - 1;
  $totalstatics = count($dhcp_statics);
  $first = explode(".",$item[netid]);
  $last = explode(".",$item[broadcast]);
  $limit =  ((($last[3] - 1) - ($first[3] + 3)) - ($totalstatics));

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
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/dhcp'));
 print '<pre>
echo "
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
';
    foreach ($dev->radios as $radio_id => $radio)
      foreach ($radio[interfaces] as $interface_id => $interface)
      if (($interface[interface_type] == 'wLan') || ($interface[interface_type] == 'wLan/Lan'))  {
        foreach ($interface[ipv4] as $ipv4_id => $ipv4) {
          if ($interface[interface_type] == 'wLan/Lan') {
            $iface = 'lan';
            $network = 'lan';
          } else {
            $network = 'wlan'.($radio_id+1);
          }

  $max = explode(".",$ipv4[ipv4]);
        foreach ($ipv4[links] as $link)
         if (($link['link_type'] == 'ap/client') and (!empty($link['interface'][ipv4][ipv4]))) {
   $totalstaticss = count($ipv4[links]);


   }
        $first = explode(".",$item[netid]);
  $last = explode(".",$item[broadcast]);
  $limit =  ((($last[3] - 1) - ($first[3] + 3)) - ($totalstaticss));
  $totalstaticss ='0';
  
print '
 config \'dhcp\' \''.$network.'\'
        option \'interface\' \''.$network.'\'
        option \'leasetime\' \'12h\'
        option \'start\' \''.($max[3] + 2).'\'
        option \'limit\' \''.$limit.'\'
';
}
}

  print '" > /etc/config/dhcp<br /></pre>';
  }
  
?>