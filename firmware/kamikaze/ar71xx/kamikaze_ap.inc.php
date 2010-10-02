<?php

function guifi_kamikaze_files($dev,$zone) {
//SOME VARIABLES

  $dns = guifi_get_dns($zone,2);
  $lan = guifi_unsolclic_if($dev->id,'wLan/Lan');
  $lan_network = _ipcalc($lan->ipv4,$lan->netmask);

  switch ($dev->variable['model_id']) {
    case "38":
    // RouterStation
      $wireless_model='atheros';
      $lan_iface='eth0';
      $lan2_iface='eth1';

      $packages='ar71xx/packages';
      break;
    default:
      _outln_comment('model id not supported');
      exit;
  }

// SECTION FILES

  _outln_comment();
  _outln_comment();
  _outln_comment(t('Wireless Settings'));
  print '<pre>';

  function wds_add($dev,$radio, $radio_id) {
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
                  print 'uci set wireless.@wifi-iface['.$radio_id.'].bssid'.$ifcount.'='.$wds['interface']['mac'].'<br />';

                  $ifcount++;
                }
                else {
                  $status = 'disabled';
                  print '# uci set wireless.@wifi-iface['.$radio_id.'].bssidX='.$wds['interface']['mac'].' # '.t($wds['flag']).'<br />';
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
uci delete network.wds_'.$hostname.'
uci set network.wds_'.$hostname.'=interface
uci set network.wds_'.$hostname.'.ifname=ath'.$radio[radiodev_counter].'.wds'.$ifcount.'
uci set network.wds_'.$hostname.'.proto=static
';
        $ifcount++;
              }
              else {
                $status = 'disabled';
                print '
##### '.t($wds['flag']).' ####
## wds_'.$hostname.'
# uci set network.wds_'.$hostname.'.ifname=ath'.$radio[radiodev_counter].'.wdsX
# uci set network.wds_'.$hostname.'.proto=none
';
              }
            }
          if ($status == 'active') {
            print 'uci set network.wds_'.$hostname.'.ipaddr='.$ipv4[ipv4].'
uci set network.wds_'.$hostname.'.netmask='.$ipv4['netmask'].'
';
              }
              else {
            print '# uci set network.wds_'.$hostname.'.ipaddr='.$ipv4[ipv4].'
# uci set network.wds_'.$hostname.'.netmask='.$ipv4['netmask'].'
';
              }
          }
  }

  if (isset($dev->radios)) foreach ($dev->radios as $radio_id => $radio) {
    $mode = 'apwds';
    $ssid = $radio[ssid];
    $channel = atheros_channel($radio);
   if ($channel <= 14)
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
      $network = 'wlanLan';
    } else {
      $wireless_iface = 'wifi'.$radio_id;
      $network = 'wlan'.($radio_id+1);
    }

  $wireless_model = 'atheros';
    $txant=$radio[antenna_mode];
    $rxant=$radio[antenna_mode];

  print '
## Radio: '.$radio[ssid].'
uci delete wireless.'.$wireless_iface.'
uci set wireless.'.$wireless_iface.'=wifi-device
uci set wireless.'.$wireless_iface.'.type='.$wireless_model.'
uci set wireless.'.$wireless_iface.'.channel='.$channel.'
uci set wireless.'.$wireless_iface.'.disabled=0
uci set wireless.'.$wireless_iface.'.hwmode='.$band.'
uci set wireless.'.$wireless_iface.'.diversity=0
uci set wireless.'.$wireless_iface.'.txantenna='.$txant.'
uci set wireless.'.$wireless_iface.'.rxantenna='.$rxant.'
uci set wireless.'.$wireless_iface.'.txpower=16
uci set wireless.'.$wireless_iface.'.regdomain=31
uci set wireless.'.$wireless_iface.'.country=724
uci set wireless.'.$wireless_iface.'.distance=2000
uci delete wireless.@wifi-iface['.$radio_id.']
uci add wireless wifi-iface
uci set wireless.@wifi-iface['.$radio_id.']=wifi-iface
uci set wireless.@wifi-iface['.$radio_id.'].device='.$wireless_iface.'
uci set wireless.@wifi-iface['.$radio_id.'].network='.$network.'
uci set wireless.@wifi-iface['.$radio_id.'].mode='.$mode.'
uci set wireless.@wifi-iface['.$radio_id.'].ssid=guifi.net-'.$radio[ssid].'
uci set wireless.@wifi-iface['.$radio_id.'].encryption=none
';
  wds_add($dev,$radio, $radio_id);
}
  print 'sleep 1</pre>';




  _outln_comment();
  _outln_comment();
  _outln_comment(t('Network Settings'));
   print '<pre>';

  function cable_network($dev) {
    foreach ($dev->interfaces as $interface_id => $interface) {
      switch ($interface[interface_type]) {
        case 'vlan1': $iname = 'eth0:1'; break;
        case 'vlan2': $iname = 'eth1'; break;
        case 'vlan3': $iname = 'eth1:1'; break;
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
uci delete network.'.$network.'
uci set network.'.$network.'=interface
uci set network.'.$network.'.ifname='.$iname.'
uci set network.'.$network.'.proto=static
uci set network.'.$network.'.ipaddr='.$ipv4[ipv4].'
uci set network.'.$network.'.netmask='.$ipv4[netmask].'
';
              }
            }
            else {
              $status = 'disabled';

              print '
##### '.t($link['flag']).' ####
## cable_'.$network.'
# uci set network.'.$network.'=interface
# uci set network.'.$network.'.ifname='.$iname.'
# uci set network.'.$network.'.proto=static
# uci set network.'.$network.'.ipaddr='.$ipv4[ipv4].'
# uci set network.'.$network.'.netmask='.$ipv4[netmask].'
';

            }
          }
    }
}
  print '
uci set network.loopback=interface
uci set network.loopback.ifname=lo
uci set network.loopback.proto=static
uci set network.loopback.ipaddr=127.0.0.1
uci set network.loopback.netmask=255.0.0.0

uci delete network.lan
uci delete network.wan
';
  if (isset($dev->radios)) foreach ($dev->radios as $radio_id => $radio) {
    if (isset($radio[interfaces])) foreach ($radio[interfaces] as $interface_id => $interface) {
      if ($interface[interface_type] != 'wds/p2p') {
        if (isset($interface[ipv4])) foreach ($interface[ipv4] as $ipv4_id => $ipv4) {
          if ($interface[interface_type] == 'wLan/Lan') {
            $iface = '"ath0 eth0"';
            $network = 'wlanLan';
          } else {
            $iface = 'wifi'.($radio_id);
            $network = 'wlan'.($radio_id+1);
          }
          $item = _ipcalc($ipv4[ipv4],$ipv4[netmask]);

// FILE NETWORK
            print '
uci delete network.'.$network.'
uci set network.'.$network.'=interface
';
          if ($interface[interface_type] == 'wLan/Lan')
            print 'uci set network.'.$network.'.type=bridge
';
            print 'uci set network.'.$network.'.ifname='.$iface.'
uci set network.'.$network.'.proto=static
uci set network.'.$network.'.ipaddr='.$ipv4[ipv4].'
uci set network.'.$network.'.netmask='.$ipv4[netmask].'
uci set network.'.$network.'.dns="'.$dns.'"
';
         } 
       }
    }
  wds_network($dev, $radio);
  }
  cable_network($dev);
  print 'sleep 1</pre>';









//FILE FIREWALL
  _outln_comment();
  _outln_comment();
  _outln_comment(t('Firewall Settings'));
  print '<pre>';
  print 'uci set firewall.@defaults[0]=defaults
uci set firewall.@defaults[0].syn_flood=1
uci set firewall.@defaults[0].input=ACCEPT
uci set firewall.@defaults[0].output=ACCEPT
uci set firewall.@defaults[0].forward=ACCEPT
';
  print 'COUNTER=0
while [  $COUNTER -lt 64 ]; do
  uci delete firewall.@zone[0] > /dev/null 2>&1
  let COUNTER=COUNTER+1 
done
uci delete firewall.@forwarding[0]
';
  $icount = '0';
  foreach ($dev->radios as $radio_id => $radio) 
    foreach ($radio[interfaces] as $interface_id => $interface)
      if (($interface[interface_type] == 'wLan') || ($interface[interface_type] == 'wLan/Lan'))
        foreach ($interface[ipv4] as $ipv4_id => $ipv4) {
          if ($interface[interface_type] == 'wLan/Lan') {
            $network = 'wlanLan';
          } else {
            $network = 'wlan'.($radio_id+1);
          }
          print 'uci add firewall zone
uci set firewall.@zone['.$icount.']=zone
uci set firewall.@zone['.$icount.'].name='.$network.'
uci set firewall.@zone['.$icount.'].input=ACCEPT
uci set firewall.@zone['.$icount.'].output=ACCEPT
uci set firewall.@zone['.$icount.'].forward=ACCEPT
';
          $icount++;
        }

  foreach ($dev->radios as $radio_id => $radio)
    foreach ($radio[interfaces] as $interface_id => $interface)
      if ($interface[interface_type] == 'wds/p2p')
        foreach ($interface[ipv4] as $ipv4_id => $ipv4)
          foreach ($ipv4[links] as $link_id => $link)
            if ($link['link_type'] == 'wds') {
              $wds_links = array();
              $wds_links[] = $link;
              foreach ($wds_links as $key => $wds) {
                $hostname = guifi_get_hostname($wds['device_id']);
                if (preg_match("/(Working|Testing|Building)/",$wds['flag'])) {
                  print 'uci add firewall zone
uci set firewall.@zone['.$icount.']=zone
uci set firewall.@zone['.$icount.'].name=wds_'.$hostname.'
uci set firewall.@zone['.$icount.'].input=ACCEPT
uci set firewall.@zone['.$icount.'].output=ACCEPT
uci set firewall.@zone['.$icount.'].forward=ACCEPT
';
                  $icount++;
                }
              }
            }

    foreach ($dev->interfaces as $interface_id => $interface)
      foreach ($interface[ipv4] as $ipv4_id => $ipv4)
        foreach ($ipv4[links] as $link_id => $link) {
          $hostname = guifi_get_hostname($link['device_id']);
            if (preg_match("/(Working|Testing|Building)/",$link['flag'])) {
              print 'uci add firewall zone
uci set firewall.@zone['.$icount.']=zone
uci set firewall.@zone['.$icount.'].name='.$hostname.'
uci set firewall.@zone['.$icount.'].input=ACCEPT
uci set firewall.@zone['.$icount.'].output=ACCEPT
uci set firewall.@zone['.$icount.'].forward=ACCEPT
';
               $icount++;
             }
        }
  print 'sleep 1</pre>';




  _outln_comment();
  _outln_comment();
  _outln_comment(t('DHCP Static Leases'));
  print '<pre>';
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
  $counter = '0';
  print 'COUNTER=0
while [  $COUNTER -lt 64 ]; do
  uci delete luci_ethers.@static_lease[0] > /dev/null 2>&1
  let COUNTER=COUNTER+1 
done
';

  foreach ($dhcp_statics as $static) {
    print '
## Device: '.$static[2].'
uci add luci_ethers static_lease
uci set luci_ethers.@static_lease['.$counter.']=static_lease
uci set luci_ethers.@static_lease['.$counter.'].macaddr='.$static[1].'
uci set luci_ethers.@static_lease['.$counter.'].ipaddr='.$static[0].'
';
  $counter++;
}
  print 'sleep 1</pre>';




// FILE DHCP
  _outln_comment();
  _outln_comment();
  _outln_comment(t('DHCP Settings'));
 print '<pre>';
 print 'uci set dhcp.@dnsmasq[0]=dnsmasq                                                                                                               
uci set dhcp.@dnsmasq[0].domainneeded=1
uci set dhcp.@dnsmasq[0].boguspriv=1
uci set dhcp.@dnsmasq[0].filterwin2k=0
uci set dhcp.@dnsmasq[0].localise_queries=1
uci set dhcp.@dnsmasq[0].local=/lan/
uci set dhcp.@dnsmasq[0].domain=lan
uci set dhcp.@dnsmasq[0].expandhosts=1
uci set dhcp.@dnsmasq[0].nonegcache=0
uci set dhcp.@dnsmasq[0].authoritative=1
uci set dhcp.@dnsmasq[0].readethers=1
uci set dhcp.@dnsmasq[0].leasefile=/tmp/dhcp.leases
uci set dhcp.@dnsmasq[0].resolvfile=/tmp/resolv.conf.auto
';
    foreach ($dev->radios as $radio_id => $radio)
      foreach ($radio[interfaces] as $interface_id => $interface)
      if (($interface[interface_type] == 'wLan') || ($interface[interface_type] == 'wLan/Lan'))  {
        foreach ($interface[ipv4] as $ipv4_id => $ipv4) {
          if ($interface[interface_type] == 'wLan/Lan') {
            $iface = 'wlanLan';
            $network = 'wlanLan';
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
uci delete dhcp.lan
uci delete dhcp.wan
uci set dhcp.'.$network.'=dhcp
uci set dhcp.'.$network.'.interface='.$network.'
uci set dhcp.'.$network.'.leasetime=12h
uci set dhcp.'.$network.'.start='.($max[3] + 2).'
uci set dhcp.'.$network.'.limit='.$limit.'
uci set dhcp.'.$network.'.netmask='.$ipv4[netmask].'
';
}
}

  print 'sleep 1</pre>';

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
  print '<pre>';
  print 'true > /etc/quagga/zebra.conf';
  print '</pre>';

// FILE OSPFD
  if (($wds_ospfd == '1') || ($cable_ospfd == '1')){
    _outln_comment();
    _outln_comment();
    _outln_comment(t('File /etc/quagga/ospfd.conf')); 
    print '<pre>';
    print 'cat > /etc/quagga/ospfd.conf << EOF
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
EOF

';
print 'sleep 1</pre>';
  }

// FILE BGPD
  if (($wds_bgpd == '1') || ($cable_bgpd == '1')){
    _outln_comment();
    _outln_comment();
    _outln_comment(t('File /etc/quagga/bgpd.conf'));
     print '<pre>';
     print 'cat > /etc/quagga/bgpd.conf << EOF
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
    print 'EOF

';
    print 'sleep 1</pre>';
  }
  
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
  openwrt_out_file($opkg_conf,'/etc/opkg.conf');

  }
  
?>