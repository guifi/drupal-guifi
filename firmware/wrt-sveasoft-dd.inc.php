<?php

function unsolclic_wrt($dev) {
  $version = "v3.7";
  $loc = node_load(array('nid' => $dev->nid));
  $zone = node_load(array('nid' => $loc->zone_id));


function guifi_unsolclic_startup($dev, $version, $rc_startup) {
  global $ospf_zone;

  _outln_comment();
  _out_nvram('rc_startup','#!/bin/ash');
  _outln_comment();
  _outln_comment(' unsolclic: '.$version);
  _outln_comment(' radio:     '.$dev->id.'-'.$dev->nick);
  _outln_comment();
  if ($dev->variable[firmware] == 'DD-WRTv23' AND $dev->radios[0][mode] == 'ap') {
  _out('/bin/sleep 5');
// Write the config for bird, for compatibility with Alchemy and area support
  _out('/bin/kill -9 \`/bin/ps |/bin/grep bird|/usr/bin/cut -c1-5|/usr/bin/head -n 1\`;');
  _out('/bin/rm /tmp/bird/bird.conf');
  _out('/bin/echo -e \'router id '.$dev->ipv4,';');
  _out('protocol kernel { learn; persist; scan time 10; import all; export all; }');
  _out('protocol device { scan time 10; }');
  _out('protocol direct { interface \\"*\\";} ');
  _out('protocol ospf WRT54G_ospf {');
  _out('area '.$ospf_zone.' { tick 8;');
  _out('interface \"*\" { cost 1; hello 10; priority 1; retransmit 7; authentication none; };');
//  _out('interface \"br0\" { cost 1; hello 10; priority 1; retransmit 7; authentication none; };');
//  _out('interface \"vlan*\" { cost 1; authentication simple; password \"guifi\"; };');
  _out('};');
  _out('}');
  _out('\' >/tmp/bird/bird.conf');
  }

  _outln_comment();
  print $rc_startup;
  if ($dev->variable['firmware'] == 'DD-WRTv23') {
  _out('/bin/sleep 3');
  _out('bird -c /tmp/bird/bird.conf');
  _out('/usr/sbin/wl shortslot_override 0');
  _out('ifconfig eth1 -promisc -allmulti');
  _out('ifconfig br0 -promisc -allmulti');
  _out('ifconfig eth0 promisc','"');
   } else {
  if ($dev->variable[firmware] == 'DD-guifi') {
  _out('/bin/sleep 10');
  _out('/usr/sbin/wl shortslot_override 0');
  _out('ifconfig eth1 -promisc -allmulti');
  _out('ifconfig br0 -promisc -allmulti');
  _out('ifconfig eth0 promisc','"');
  } else {
  _out('/bin/sleep 10');
  _out('/usr/sbin/wl shortslot_override 0','"');
   }
}
}

function guifi_get_alchemy_ifs($dev) {
  $ifs = array (
           'wLan/Lan' => 'br0',
           'wLan' => 'br0',
           'vlan' => 'br0:1',
           'vlwan' => 'br0',
           'vlwan' => 'br0',
           'wds/p2p' => 'wds0.',
           'Wan' => 'vlan1',
           'vlan2' => 'vlan2',
           'vlan3' => 'vlan3',
           'vlan4' => 'vlan4'
               );
  $ret = array();
  if (!empty($dev->radios))       foreach ($dev->radios as $radio_id => $radio) 
  if (!empty($radio[interfaces])) foreach ($radio[interfaces] as $interface_id => $interface) 
  if (!empty($interface[ipv4]))   foreach ($interface[ipv4] as $ipv4_id => $ipv4) 
  if (!empty($ipv4[links]))       foreach ($ipv4[links] as $key => $link) {
    if ($link['link_type'] == 'wds')
     $wds_links[] = $link ;
    else {
     if (!isset($ret[$ifs[$interface['interface_type']]]))
       $ret[$ifs[$interface['interface_type']]] = true;
    }
  }
  if (count($wds_links))
  foreach ($wds_links as $key => $wds) 
    $ret['wds0.'.($key+2)] = true;

  if (!empty($dev->interfaces)) foreach ($dev->interfaces as $interface_id => $interface)
     if (!isset($ret[$ifs[$interface['interface_type']]]))
       $ret[$ifs[$interface['interface_type']]] = true;

  return $ret;
}

function guifi_unsolclic_gateway($dev) {
  _outln_comment();
  _outln_comment(t('Gateway routing'));
  _outln_nvram('wk_mode','gateway');
  _outln_nvram('dr_setting','0');
  _outln_nvram('route_default','1');
  _outln_nvram('dr_lan_rx','0');
  _outln_nvram('dr_lan_tx','0');
  _outln_nvram('dr_wan_rx','0');
  _outln_nvram('dr_wan_tx','0');
  _outln_nvram('dr_wan_tx','0');
  _outln_comment(t('Firewall enabled'));
  _outln_nvram('filter','on');
  _outln_nvram('rc_firewall','/usr/sbin/iptables -I INPUT -p udp --dport 161 -j ACCEPT; /usr/sbin/iptables -I INPUT -p tcp --dport 22 -j ACCEPT');
  return;
}

function guifi_unsolclic_ospf($dev,$zone) {
  global $ospf_zone;

  _outln_comment();
  _outln_comment(t('Firewall disabled'));
  _outln_nvram('filter','off');
  _outln_comment(t(' '.$dev->variable['firmware'].' OSPF routing'));
  _outln_nvram('dr_setting','3');
  _outln_nvram('dr_lan_rx','1 2');
  _outln_nvram('dr_lan_tx','1 2');
  _outln_nvram('dr_wan_rx','1 2');
  _outln_nvram('dr_wan_tx','1 2');
  _outln_nvram('wk_mode','ospf');
  if (($dev->variable['firmware'] == 'DD-WRTv23') or ($dev->variable['firmware'] == 'DD-guifi')) {
    _outln_nvram('routing_lan','on');
    _outln_nvram('routing_wan','on');
    _outln_nvram('routing_ospf','on');
  }
  if ($dev->variable['firmware'] == 'Alchemy') {
    _outln_nvram('route_default','1');
    _outln_nvram('expert_mode','1');
  }
  _out_nvram('ospfd_conf');
  _out('!');
  _out('password guifi');
  _out('enable password guifi');
  _out('!');
  
// TODO: List of routing interfaces, by now, all
      foreach (guifi_get_alchemy_ifs($dev) as $if => $exists) {
        _out('interface '.$if);
      }

      $wlan_lan = guifi_unsolclic_if($dev->id,'wLan/Lan');
      _out('!');
      _out('router ospf');
      _out(' ospf router-id '.$wlan_lan->ipv4);

      foreach ($dev->radios as $radio_id => $radio)
        foreach ($radio[interfaces] as $interface_id => $interface)
          if (($interface[interface_type] == 'wLan') || ($interface[interface_type] == 'wLan/Lan'))  {
            foreach ($interface[ipv4] as $ipv4_id => $ipv4)
              $network = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
                _out('  network '.$network[netid].'/'.$network[maskbits].' area 0');

          }
      
      $wds_links = array();
      foreach ($dev->radios as $radio_id => $radio)
        foreach ($radio[interfaces] as $interface_id => $interface)
          if ($interface[interface_type] == 'wds/p2p')
            if ($interface[ipv4]) {
              foreach ($interface[ipv4] as $ipv4_id => $ipv4)
                foreach ($ipv4[links] as $link_id => $link)
                  if ($link['link_type'] == 'wds')
                    $wds_links[] = $link;
                  foreach ($wds_links as $key => $wds) {
                    $iplocal[] = $wds['interface']['ipv4'];
                      if ($wds['routing'] == 'OSPF') {
                        $wds_network = _ipcalc($iplocal[$key][ipv4],$iplocal[$key][netmask]);
                          if (preg_match("/(Working|Testing|Building)/",$wds['flag']))
                            _out('  network '.$wds_network[netid].'/'.$wds_network[maskbits].' area 0');
                      }
                  }
              }
       foreach ($dev->interfaces as $interface_id => $interface)
         foreach ($interface[ipv4] as $ipv4_id => $ipv4) {
           $item = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
             if (($ipv4['ipv4_type'] == '1') && ($interface[interface_type] != 'wLan/Lan')) {
               _out('  network '.$item[netid].'/'.$item[maskbits].' area 0');
             }
             if ($ipv4[links] ) {
               foreach ($ipv4[links] as $link_id => $link) {
                 $item = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
                 if ($ipv4['ipv4_type'] == '2') {
                   if ($link['routing'] == 'OSPF') {
                     if (preg_match("/(Working|Testing|Building)/",$link['flag']))
                       _out('  network '.$item[netid].'/'.$item[maskbits].' area 0');
                   }
                 }
               }
           }
         }
      
      _out(' default-information originate');
      _out('!');
      _out('line vty');
      _out('!','"');

    return;
}

function guifi_unsolclic_dhcp($dev) {
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
  
  if ($statics == -1) {
    _outln_comment();
    _outln_comment('DHCP');
    _outln_nvram('dhcp_start',($max[3] + 5));
    return;
  }
  _outln_comment();
  _outln_comment('DHCP');

  if ($dev->variable['firmware'] == 'Alchemy') {
    _out_nvram('dhcpd_statics');
    for ($i = 0; $i < $statics; $i++) {
      _out(implode(" ",$dhcp_statics[$i]));
    } 
    _out(implode(" ",$dhcp_statics[$statics]),'"');
  }

  if (($dev->variable['firmware'] == 'DD-WRTv23') or ($dev->variable['firmware'] == 'DD-guifi')){
        $staticText = "";
        foreach ($dhcp_statics as $static) {
        $staticText .= $static[1]."=".$static[2]."=".$static[0]." ";
        }
    _out('nvram set static_leases="'.$staticText,' "');
    _outln_nvram('static_leasenum',$totalstatics);
    }

  if ($dev->variable['firmware'] == 'Talisman') {
    _out_nvram('dhcp_statics');
    foreach ($dhcp_statics as $static) {
      _out($static[1]."-".$static[0]."-".$static[2]." ");
    }
    _out(null,'"');
  }
  _outln_nvram('dhcp_start',($max[3] + 5));
  return;
}     
  
function guifi_unsolclic_network_vars($dev,$zone) {

   _outln_comment($dev->nick);
   _outln_comment(t('Global network parameters'));
   _outln_nvram('router_name',$dev->nick);
   _outln_nvram('wan_hostname',$dev->nick);

   $wlan_lan = guifi_unsolclic_if($dev->id,'wLan/Lan');
   if ($wlan_lan->ipv4 != '') {
     _outln_nvram('lan_ipaddr',$wlan_lan->ipv4);
     _outln_nvram('lan_gateway','0.0.0.0');
     _outln_nvram('lan_netmask',$wlan_lan->netmask);
   }

   $lan = guifi_unsolclic_if($dev->id,'Lan');
   if ($lan->ipv4 != '') {
     _outln_nvram('lan_ipaddr',$lan->ipv4);
     $item = _ipcalc($lan->ipv4, $lan->netmask);
     _outln_nvram('lan_gateway',$item['netstart']);  
     _outln_nvram('lan_netmask',$lan->netmask);
   }

   $wan = guifi_unsolclic_if($dev->id,'Wan');
   if ($wan) {
     if (empty($wan->ipv4)) 
       _outln_nvram('wan_proto','dhcp');
     else {
       _outln_nvram('wan_proto','static');
       _outln_nvram('wan_ipaddr',$wan->ipv4);
       _outln_nvram('wan_netmask',$wan->netmask);
       if (($dev->variable['firmware'] == 'DD-WRTv23') or ($dev->variable['firmware'] == 'DD-guifi')){
	  _outln_nvram('fullswitch','1');
          _outln_nvram('wan_dns',guifi_get_dns($zone,3)); 
       }
     }
   } else {
     _outln_nvram('wan_proto','disabled');
   }
 
   _outln_nvram('lan_domain','guifi.net');
   _outln_nvram('wan_domain','guifi.net');
   _outln_nvram('http_passwd','guifi');
   _outln_nvram('time_zone',$zone->time_zone);
   _outln_nvram('sv_localdns',guifi_get_dns($zone,1));
   if ($dev->variable['firmware'] == 'Alchemy') 
     _outln_nvram('wan_dns',guifi_get_dns($zone,3));
   if ($dev->variable['firmware'] == 'Talisman') {
     foreach (explode(' ',guifi_get_dns($zone,3)) as $key => $dns)
       _outln_nvram('wan_dns'.$key,$dns);
   }
   _outln_nvram('wl_net_mode','b-only');
   _outln_nvram('wl0_net_mode','b-only');
   _outln_nvram('wl_afterburner','on');
   _outln_nvram('wl_frameburst','on');
   // Setting outpur power (mW)
   _outln_nvram('txpwr','28');
    if (empty($dev->radios[0][antenna_mode]))
         $dev->radios[0][antenna_mode]= 'Main';
        if ($dev->radios[0][antenna_mode] != 'Main') 
          $dev->radios[0][antenna_mode]= '1';
        else
          $dev->radios[0][antenna_mode]= '0';
   _outln_nvram('txant',$dev->radios[0][antenna_mode]);
   _outln_nvram('wl0_antdiv','0');
   _outln_nvram('wl_antdiv','0');
   _outln_nvram('block_wan','0');
   
   if ($dev->variable['firmware'] == 'Talisman') {
     _outln_nvram('ident_pass','0');
     _outln_nvram('multicast_pass','0');
     _outln_nvram('wl_closed','0');
     _outln_nvram('block_loopback','0');
   }
   
   _outln_comment();
   _outln_comment(t('Management'));
   _outln_nvram('telnetd_enable','1');
   _outln_nvram('sshd_enable','1');
   _outln_nvram('sshd_passwd_auth','1');
   _outln_nvram('remote_management','1');
   _outln_nvram('remote_mgt_https','1');
   _outln_nvram('snmpd_enable','1');
   _outln_nvram('snmpd_sysname','guifi.net');
   _outln_nvram('snmpd_syscontact','guifi_at_guifi.net');
   _outln_nvram('boot_wait','on');
   _outln_comment(t('This is just a fake key. You must install a trusted key if you like to have you router managed externally'));
   _outln_nvram('sshd_authorized_keys','ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAIEAwWNX4942fQExw4Hph2M/sxOAWVE9PB1I4JnNyhoWuF9vid0XcU34kwWqBBlI+LjDErCQyaR4ysFgDX61V4kUuCKwBOMp+UGxhL648VTv5Qji/YwvIzt7nguUOZ5AGPISqsC0717hc0Aja1mvHkQqg9aXKznmszmyKZGhcm2+SU8= root@bandoler.guifi.net');
   // For DD-WRTv23
   _outln_nvram('http_enable','1');
   _outln_nvram('https_enable','1');


   _outln_comment();
   _outln_comment('NTP Network time protocol');
   $ntp = guifi_get_ntp($zone,1);
   if (empty($ntp)) {
     _outln_nvram('ntp_enable','0');
   } else {
     _outln_nvram('ntp_enable','1');
     _outln_nvram('ntp_server',$ntp);
   }
 
   _outln_comment();
   switch ($dev->radios[0][mode]) {
   case "ap": case "AP":
     _outln_comment(t('AP mode'));
     _outln_nvram('wl_mode','ap');
     _outln_nvram('wl0_mode','ap');
     _outln_nvram('wl_channel',$dev->radios[0][channel]);
     _outln_nvram('wl_ssid','guifi.net-'.guifi_to_7bits($dev->radios[0][ssid]));
     _outln_nvram('wl_macmode','disable');
     _outln_nvram('wl0_macmode','disable');
     _outln_nvram('wl_macmode1','disable');
     _outln_nvram('wl0_macmode1','disable');
     guifi_unsolclic_ospf($dev,$zone);
     guifi_unsolclic_dhcp($dev);
     guifi_unsolclic_wds_vars($dev);
     break;
   case 'client':
     _outln_comment(t('Client mode'));
     $ap_macs = array();
     foreach ($dev->radios[0]['interfaces'] as $interface_id => $interface) 
     foreach ($interface[ipv4] as $ipv4_id => $ipv4) 
     if (isset($ipv4[links])) foreach ($ipv4[links] as $key => $link) {
       if ($link['link_type'] == 'ap/client') {
       $ap_macs[] = $link['interface']['mac'];

       $gateway = $link['interface']['ipv4']['ipv4'];
       
       if (($dev->variable['firmware'] == 'Alchemy') or ($dev->variable['firmware'] == 'Talisman')) {
         _outln_nvram('wl_mode','wet');
         _outln_nvram('wl0_mode','wet');
         _outln_nvram('wl_ssid','guifi.net-'.guifi_get_ap_ssid($link['interface']['device_id'],$link['interface']['radiodev_counter']));
       }

       if (($dev->variable['firmware'] == 'DD-WRTv23') or ($dev->variable['firmware'] == 'DD-guifi')) {
         _outln_nvram('wl_mode','sta');
         _outln_nvram('wl0_mode','sta');
         _outln_nvram('wl_ssid','guifi.net-'.guifi_get_ap_ssid($link['interface']['device_id'],$link['interface']['radiodev_counter']));
       }
       _outln_nvram('wan_gateway',$gateway);
      }
     }
     if ($dev->variable['firmware'] == 'Alchemy') {
       $filter = implode(" ",$ap_macs);
       if ($filter == "" ) {
         _outln_comment(t('WARNING: AP MAC not set'));
         $filter = "FF:FF:FF:FF:FF:FF";
       }
       _outln_nvram('wl_macmode','allow');
       _outln_nvram('wl0_macmode','allow');
       _outln_nvram('wl_macmode1','other');
       _outln_nvram('wl0_macmode1','other');
       _outln_nvram('wl_maclist',$filter);
       _outln_nvram('wl0_maclist',$filter);
       _outln_nvram('wl_mac_list',$filter);
       _outln_nvram('wl0_mac_list',$filter);
     } else {
       _outln_nvram('wl_macmode','disabled');
       _outln_nvram('wl0_macmode','disabled');
       _outln_nvram('wl_macmode1','disabled');
       _outln_nvram('wl0_macmode1','disabled');
     }
       $lan = guifi_unsolclic_if($dev->id,'Lan');
       if ($lan) {
          guifi_unsolclic_ospf($dev,$zone);
          break;
         } else {
          guifi_unsolclic_gateway($dev);
          break;
         }
   } 
   _outln_comment();
}


function guifi_unsolclic_vlan_vars($dev,&$rc_startup) {
  global $otype;
 
  function vout($if, $ipv4, $link) {
    global $otype; 

    $output = '# '.$if.': '.guifi_get_hostname($link['interface'][device_id]);
    if ($otype == 'html') $output .= "\n<br />"; else $output .= "\n";
    $item = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
    if (!preg_match("/(Working|Testing|Building)/",$link[flag])) 
      $output .= '# '; 
    $output .= 'ifconfig '.$if.' '.$ipv4[ipv4].' netmask '.$ipv4[netmask].' broadcast '.$item['broadcast']; 
    if ($otype == 'html') $output .= "\n<br />"; else $output .= "\n";

    return $output;
  } 

  $vlans = false;
  $br0 = 0;
  $eth1 = 0;
  $rc = '';
  $bips = array();
  if (!empty($dev->interfaces)) foreach ($dev->interfaces as $interface_id => $interface) 
  if (!empty($interface[ipv4])) foreach ($interface[ipv4] as $ipv4_id => $ipv4) 
  if (!empty($ipv4[links]))     foreach ($ipv4[links] as $link_id => $link) 
  {

    // if interface is already created, skip
    if (!in_array($interface['ipv4'],$bips)) {
      $bips[] = $interface['ipv4'];

      switch ($interface['interface_type']) {
        case 'vlan':
        case 'vwlan':
          $br0++;
          $rc .= vout('br0:'.$br0,$ipv4,$link); 
          break; 
        case 'vwan':
          $eth1++;
          $rc .= vout('eth1:'.$eth1,$ipv4,$link); 
          break; 
        case 'vlan2':
        case 'vlan3':
        case 'vlan4':
          $rc .= vout($interface['interface_type'],$ipv4,$link); 
          $vlans = true;
          break;
      }
    }
  }
  if ($rc != '') {
    $rc_startup = '# VLANs -- radio: '.$dev->id.'-'.$dev->nick;
    if ($otype == 'html') $rc_startup .= "\n<br />"; else $rc_startup .= "\n";
    $rc_startup .= $rc;
  }
  if ($vlans) {
    _outln_comment();
    _outln_comment('VLANs -- radio: '.$dev->id.'-'.$dev->nick);
    switch ($dev->variable['model_id']) {
    case "1": //* WRT54Gv1-4 *//
    case "15"://* WHR-HP-G54, WHR-G54S (BUFFALO) *//
    case "17"://* WRT54GSv1-2 *//
     if (($dev->variable['firmware'] == 'DD-WRTv23') or ($dev->variable['firmware'] == 'DD-guifi')) {
    _outln_nvram('vlan2hwname','et0');
    _outln_nvram('vlan3hwname','et0');
    _outln_nvram('vlan4hwname','et0');
    _outln_nvram('vlan0ports','0 1 5*');
    _outln_nvram('vlan2ports','2 5');
    _outln_nvram('vlan3ports','3 5');
    _outln_nvram('vlan4ports','4 5');
	 } else {
    _outln_nvram('vlans','1');
    _outln_nvram('port2vlans','2');
    _outln_nvram('port3vlans','3');
    _outln_nvram('port4vlans','4');
    _outln_nvram('port5vlans','0 1 2 3 4 16');
    } 
    break;
// switch has turned ports for these models:
    case "16": //* WRT54GL *//
    case "18": //* WRT54GSv4 *//
    _outln_nvram('vlan2hwname','et0');
    _outln_nvram('vlan3hwname','et0');
    _outln_nvram('vlan4hwname','et0');
    _outln_nvram('vlan0ports','4 3 5*');
    _outln_nvram('vlan1ports','4 5');
    _outln_nvram('vlan2ports','2 5');
    _outln_nvram('vlan3ports','1 5');
    _outln_nvram('vlan4ports','0 5');

   }
  }
} // vlan_vars function

function guifi_unsolclic_wds_vars($dev) {
  
  global $rc_startup;

  $wds_links = array();
  $wds_str = '';
  if (!empty($dev->radios))       foreach ($dev->radios as $radio_id => $radio) 
  if (!empty($radio[interfaces])) foreach ($radio[interfaces] as $interface_id => $interface) 
  if ($interface['interface_type'] == 'wds/p2p') if (!empty($interface[ipv4]))   foreach ($interface[ipv4] as $ipv4_id => $ipv4) 
  if (!empty($ipv4[links]))       foreach ($ipv4[links] as $key => $link) {
    if ($link['link_type'] == 'wds')
     $wds_links[] = $link ;
     $iplocal[] = $ipv4 ;
     $iflocal[] = $interface ;
  }
  if (count($wds_links) == 0)
    return;

  _outln_comment('');
  _outln_comment(t('WDS Links for').' '.$dev->nick);
	  if (($dev->variable['firmware'] == 'DD-WRTv23') or ($dev->variable['firmware'] == 'DD-guifi'))
	    $ifcount = 2; else $ifcount = 1;
  foreach ($wds_links as $key => $wds) {
    $hostname = guifi_get_hostname($wds['device_id']);
    _outln_comment($wds['device_id'].'-'.$hostname);
    _outln_nvram('wl_wds'.($key+1).'_desc',$hostname);
    if (preg_match("/(Working|Testing|Building)/",$wds['flag'])) {
      $ifcount++;
      _outln_nvram('wl_wds'.($key+1).'_enable','1');
	  if (($dev->variable['firmware'] == 'DD-WRTv23') or ($dev->variable['firmware'] == 'DD-guifi'))
      _outln_nvram('wl_wds'.($key+1).'_if','wds0.4915'.$ifcount);
	  else
      _outln_nvram('wl_wds'.($key+1).'_if','wds0.'.$ifcount);
      $wds_str .= ' '.$wds['interface']['mac'];
      // Bug del Talisman 1.0.5
      if ($dev->variable['firmware'] == 'Talisman') 
        $rc_startup .= "ifconfig wds0.".$ifcount." up\n<br />";
    } else {
      _outln_nvram('wl_wds'.($key+1).'_enable','0');
    }
    _outln_nvram('wl_wds'.($key+1).'_ipaddr',$iplocal[$key][ipv4]);
    _outln_nvram('wl_wds'.($key+1).'_hwaddr',$wds['interface'][mac]);
    _outln_nvram('wl_wds'.($key+1).'_netmask',$iplocal[$key][netmask]);
  }
  if (count($wds_links) >= 11)
    return;

  _outln_comment();
  _outln_comment(t('Free WDS slots'));
  for ($key = count($wds_links) + 1; $key <= 10; $key++) {
    _outln_nvram('wl_wds'.($key).'_desc',t('free'));
    _outln_nvram('wl_wds'.($key).'_enable','0');
    _outln_nvram('wl_wds'.($key).'_ipaddr','172.0.0.0');
    _outln_nvram('wl_wds'.($key).'_hwaddr','00:13:00:00:00:00');
    _outln_nvram('wl_wds'.($key).'_netmask','255.255.255.252');
  }
  _out_nvram('wl0_wds',$wds_str.'"');
  _outln_nvram('wl0_lazywds','0');
  _outln_nvram('wl_lazywds','0');
} // wds_vars function
  _outln_comment();
  _outln_comment('unsolclic version: '.$version);
  if ($dev->variable[firmware] == 'DD-WRTv23') {
  _outln_comment(t("######################################################"));
  _outln_comment(t("WARNING! this unsolclic is for use only on DD-WRT v23beta2 firmware's."));
  _outln_comment(t("DD-WRT V23sp2 or V24 contains some changes in the user authentification method"));
  _outln_comment(t("V23beta2 uses plain-text password and V23sp2 and v24 need the password crypted!!"));
  _outln_comment(t("You can lost full acces to device, your are advised!!!!"));
  _outln_comment(t("Some parts of unsolclic for v23Beta2 work on V23sp2/v24 firmware's, if you want try it, replace"));
  _outln_comment(t("the password line: nvram set http_passwd=\"guifi\" to nvram set http_passwd=\"\" "));
  _outln_comment(t("######################################################"));
  _outln_comment();
}
  _outln_comment(t("open a telnet/ssh session on your device and run the script below."));
  _outln_comment(t("Note: Use Status/Wireless survey to verify that you have the"));
  _outln_comment(t("antenna plugged in the right connector. The right antena is probably"));
  _outln_comment(t("the one which is at the right, looking the WRT54G from the front"));
  _outln_comment(t("(where it have the leds). If needed, change the antenna connector"));
  _outln_comment(t("at Wireless->Advanced Settings."));
  _outln_comment(t('Security notes:'));
  _outln_comment(t('Once this script is executes, the router password for root/admin users is "guifi"'));
  _outln_comment(t('You must change this password if you want to keep it secret. If you like to still'));
  _outln_comment(t('be managed externally, you must install a trusted ssh key. Upon request, your setup'));
  _outln_comment(t('might be asked for being inspected to check the Wireless Commons compliance.'));
  _outln_comment(t('No firewall rules are allowed in the public network area.'));
  _outln_comment(t('By being in client mode, the router has the firewall enabled to distinguish between'));
  _outln_comment(t('private and public areas, and only SNMP, ssh and https 8080 ports are enabled'));
  _outln_comment(t('for external administration. Everything else is closed, therefore you might'));
  _outln_comment(t('have to open ports to share resources.'));
  _outln_comment();

  // network parameters
  guifi_unsolclic_network_vars($dev,$zone);
  guifi_unsolclic_vlan_vars($dev,$rc_startup);
  guifi_unsolclic_startup($dev,$version,$rc_startup);

  _outln_comment();
  _outln_comment(t('end of script and reboot'));
  _out('nvram commit');
  _out('reboot');

}

?>