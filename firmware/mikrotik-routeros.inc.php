<?php

function unsolclic_routeros($dev) {

  //Fixed testing mode
  $ospf_id = '0.0.0.0';
  $ospf_name = 'backbone';
  //

  $defined_ips = array();

  function bgp_peer($id, $ipv4, $disabled) {
    $peername=guifi_get_hostname($id);
    _outln('/ routing bgp peer');
    _outln(sprintf(':foreach i in [find name=%s] do={/routing bgp peer remove $i;}',$peername));
    _outln(sprintf('add name="%s" instance=default remote-address=%s remote-as=%s \ ',
           $peername,
           $ipv4,
           $id));
    _outln(sprintf('multihop=no route-reflect=no ttl=1 in-filter=ospf-in out-filter=ospf-out disabled=%s', $disabled));

  }
  function ospf_interface($iname, $netid, $maskbits, $ospf_name , $ospf_zone, $ospf_id, $disabled) {
    _outln('/ routing ospf interface');
    _outln(sprintf(':foreach i in [/routing ospf interface find interface=%s] do={/routing ospf interface remove $i;}',$iname));
    _outln(sprintf('add interface=%s',$iname));
    _outln('/ routing ospf network');
    _outln(sprintf(':foreach i in [/routing ospf network find network=%s/%d] do={/routing ospf network remove $i;}',$netid,$maskbits));
    _outln(sprintf('add network=%s/%d area=%s disabled=%s',$netid,$maskbits,$ospf_name, $disabled));

// TODO
//    _outln('/ routing ospf area');
//    _outln(sprintf(':foreach i in [/routing ospf area find name=%s] do={/routing ospf area remove $i;}',$ospf_name));
//    _outln(sprintf('add name=%s area-id=%s type=default translator-role=translate-candidate  authentication=none default-cost=1 disabled=no',$ospf_name, $ospf_id));
//

}


//  Check if there's any wLan/Lan interface defined on the device

  $wlanlan=false;
  foreach ($dev->radios as $ri)
    {
      $ii=$ri[interfaces];
      foreach ($ii as $iii)
	{
	    if ($iii[interface_type]=='wLan/Lan') $wlanlan=true;
	}

    }

  $node = node_load(array('nid' => $dev->nid));
  $zone = node_load(array('nid' => $node->zone_id));
  _outln(sprintf(':log info "Unsolclic for %d-%s going to be executed."',$dev->id,$dev->nick));
  _outln_comment();
  if ($dev->variable[firmware] == 'RouterOSv2.9') {
    _outln_comment(t('Configuration for RouterOS 2.9.51'));
  }
  if ($dev->variable[firmware] == 'RouterOSv3.x') {
    _outln_comment(t('Configuration for RouterOS 3.30'));
  }
  if ($dev->variable[firmware] == 'RouterOSv4.0+') {
    _outln_comment(t('Configuration for RouterOS 4.6'));
  }
  if ($dev->variable[firmware] == 'RouterOSv4.7+') {
    _outln_comment(t('Configuration for RouterOS 4.7 and newer'));
  }
  _outln_comment(t('Device').': '.$dev->id.'-'.$dev->nick);
  _outln_comment();
  _outln_comment(t('WARNING: Beta version'));
  _outln_comment();
  _outln_comment(t('Methods to upload/execute this script:'));
  _outln_comment(t('1.-As a script. Upload this output as a script either with:'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;'.t('a.Winbox (with Linux, wine required)'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;'.t('b.Terminal (telnet, ssh...)'));
  _outln_comment('&nbsp;&nbsp;&nbsp;'.t('Then execute the script with:'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.t('>&nbsp;/system script run script_name'));
  _outln_comment(t('2.-Imported file:'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;'.t('Save this output to a file, then upload it to the router'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;'.t('using ftp using a name like "script_name.rsc".'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;'.t('(note that extension ".rsc" is required)'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;'.t('Run the import file using the command:'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.t('>&nbsp;/import script_name'));
  _outln_comment(t('3.-Telnet cut&paste:'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;'.t('Open a terminal session, and cut&paste this output'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;'.t('directly on the terminal input.'));
  _outln_comment();
  _outln_comment(t('Notes:'));
  _outln_comment(t('-routing-test package is required, be sure you have it enabled at system packages'));
//  _outln_comment(t('-By default, OSPF is *DEACTIVATED*, and BGP activated, peers should be enabled'));
//  _outln_comment(t('&nbsp;&nbsp;manually. To enable ospf, enable the backbone network at'));
//  _outln_comment(t('&nbsp;&nbsp;/routing ospf network'));
  _outln_comment(t('-wlans should be enabled manually, be sure to set the correct antenna (a or b)'));
  _outln_comment(t('&nbsp;&nbsp;according in how did you connect the cable to the miniPCI. Keep the'));
  _outln_comment(t('&nbsp;&nbsp;power at the minimum possible and check the channel.'));
  _outln_comment(t('-The script doesn\'t reset the router, you might have to do it manually'));
  _outln_comment(t('-You must have write access to the router'));
  _outln_comment(t('-MAC access (winbox, MAC telnet...) method is recommended'));
  _outln_comment(t('&nbsp;&nbsp;(the script reconfigures some IP addresses, so communication can be lost)'));
  _outln_comment(t('-No changes are done in user passwords on the device'));
  _outln_comment(t('-A Read Only guest account with no password will be created to allow guest access'));
  _outln_comment(t('&nbsp;&nbsp;to the router with no danger of damage but able to see the config.'));
  _outln_comment(t('-Be sure that all packages are activated.'));
  _outln_comment(t('-Don\'t run the script from telnet and being connected through an IP connection at'));
  _outln_comment(t('&nbsp;&nbsp;the wLan/Lan interface: This interface will be destroyed during the script.'));
  _outln_comment();

  _outln('/ system identity set name='.$dev->nick);


  // DNS
  _outln_comment();
  _outln_comment('DNS (client &#038; server cache) zone: '.$node->zone_id);
  list($primary_dns,$secondary_dns) = explode(' ',guifi_get_dns($zone,2));
  $dns[] .=$primary_dns;
  $dns[] .=$secondary_dns;
  if ($secondary_dns != null) {
    if ($dev->variable[firmware] == 'RouterOSv4.7+')
      _outln(sprintf('/ip dns set servers=%s,%s allow-remote-requests=yes',$primary_dns,$secondary_dns));
    else
      _outln(sprintf('/ip dns set primary-dns=%s secondary-dns=%s allow-remote-requests=yes',$primary_dns,$secondary_dns));
  }
  else if ($primary_dns != null) {
    if ($dev->variable[firmware] == 'RouterOSv4.7+')
      _outln(sprintf('/ip dns set servers=%s allow-remote-requests=yes',$primary_dns));
    else
      _outln(sprintf('/ip dns set primary-dns=%s allow-remote-requests=yes',$primary_dns));
  }
  _outln(':delay 1');

  // NTP
  _outln_comment();
  _outln_comment('NTP (client &#038; server cache) zone: '.$node->zone_id);
  list($primary_ntp,$secondary_ntp) = explode(' ',guifi_get_ntp($zone));
  if ($secondary_ntp != null)
    _outln(sprintf('/system ntp client set enabled=yes mode=unicast primary-ntp=%s secondary-ntp=%s',$primary_ntp,$secondary_ntp));
  else if ($primary_ntp != null)
    _outln(sprintf('/system ntp client set enabled=yes mode=unicast primary-ntp=%s',$primary_ntp));
  if ($dev->variable[firmware] == 'RouterOSv2.9')
  _outln(sprintf('/system ntp server set manycast=no enabled=yes'));
  _outln(':delay 1');

  // Bandwidth-server
  _outln_comment();
  _outln_comment(t('Bandwidth-server'));
  _outln('/ tool bandwidth-server set enabled=yes authenticate=no allocate-udp-ports-from=2000');

  // SNMP
  _outln_comment();
  _outln_comment('SNMP');
  _outln(sprintf('/snmp set contact="guifi@guifi.net" enabled=yes location="%s"',$node->nick));

  // User guest
  _outln_comment();
  _outln_comment('Guest user');
  _outln('/user');
  _outln(':foreach i in [find group=read] do={/user remove $i;}');
  _outln('add name="guest" group=read address=0.0.0.0/0 comment="" disabled=no');

  // Graphing
  _outln_comment();
  _outln_comment(t('Graphing'));
  _outln(sprintf('/tool graphing interface add'));

  // LogServer
    if (isset($dev->logserver)) {
    $ipd = guifi_main_ip($dev->id);
    _outln_comment(t('Ip for ServerLogs'));
    _outln('/system logging');
    _outln(':foreach i in [/system logging find action=remote]');
    _outln('do={/system logging remove $i }');
    _outln(':foreach i in [/system logging action find name=guifi]');
    _outln('do=[/system logging action remove $i]');
    _outln('/system logging action add name='.$dev->nick target=remote.' remote=.'$dev->logserver.':514 src-address='.$ipd);
    _outln('/system logging add action=guifi_remot topics=critical');
    _outln('/system logging add action=guifi_remot topics=account');
  }

  if ($radio[mode] != 'client') {
    // Define wLan/Lan bridge (main interface)
    _outln_comment(t('Remove current wLan/Lan bridge if exists'));
    _outln(':foreach i in [/interface bridge find name=wLan/Lan] \ ');
    _outln('do={:foreach i in [/interface bridge port find bridge=wLan/Lan] \ ');
    _outln('do={/interface bridge port remove $i; \ ');
    _outln(':foreach i in [/ip address find interface=wLan/Lan] \ ');
    _outln('do={/ip address remove $i;};};');
    _outln('/interface bridge remove $i;}');

     // Construct bridge only if exists wlan/lan interface
     if ($wlanlan){
    _outln_comment(t('Construct main bridge on wlan1 &#038; ether1'));
    _outln('/ interface bridge');
    _outln('add name="wLan/Lan"');
    _outln('/ interface bridge port');
    _outln('add interface=ether1 bridge=wLan/Lan');
    _outln('add interface=wlan1 bridge=wLan/Lan');
  }

  _outln(':delay 1');
   }

  $firewall = false;
  // Going to setup wireless interfaces
  if (isset($dev->radios)) foreach ($dev->radios as $radio_id => $radio) {

    switch ($radio[mode]) {
    case 'ap':
      $mode = 'ap-bridge';
      $ssid = $radio[ssid];
      $gain = $radio[antenna_gain];
      if ($radio[channel] < 5000)
        $band = '2.4ghz-b';
      else
        $band = '5ghz';

      break;
    case 'client':
    case 'clientrouted':
      $mode = 'station';
      $gain = $radio[antenna_gain];
      foreach ($radio[interfaces] as $interface)
      foreach ($interface[ipv4] as $ipv4)
      foreach ($ipv4[links] as $link) {
        $ssid = guifi_get_ap_ssid($link['interface']['device_id'],$link['interface']['radiodev_counter']);
        $protocol = guifi_get_ap_protocol($link['interface']['device_id'],$link['interface']['radiodev_counter']);
        $channel = guifi_get_ap_channel($link['interface']['device_id'],$link['interface']['radiodev_counter']);
        if ($protocol == '802.11b')
          $band = '2.4ghz-b';
        if ($protocol == '802.11a')
          $band = '5ghz';
        if (($protocol == '802.11n') AND ($channel > 5000))
          $band = '5ghz-a/n';
      }
        $firewall=true;
      break;
    }


    _outln_comment();
    _outln_comment('Radio#: '.$radio_id.' '.$radio[ssid]);
    _outln(sprintf('/interface wireless set wlan%d name="wlan%d" \ ',$radio_id+1,$radio_id+1));
    _outln(sprintf('    radio-name="%s" mode=%s ssid="guifi.net-%s" \ ',$radio[ssid],$mode,$ssid));
    _outln(sprintf('    band="%s" \ ',$band));
    _outln(sprintf('    frequency-mode=regulatory-domain country=spain antenna-gain=%s \ ',$gain));
    if (($radio[channel] != 0) and ($radio[channel] != 5000)) { // if not auto.. set channel
      if ($radio[channel] < 20) {
        $incr = $radio[channel] * 5;
        $radio[channel] = 2407 + $incr;
      }
      _outln(sprintf('    frequency=%d \ ',$radio[channel]));
    }
    if (
         (($band == '5ghz') and ($radio[channel] == 5000 /* 5ghz auto */)) or
         (($band == '2.4ghz-b') and ($radio[channel] == 0 /* 2.4ghz auto */))
       )
      _outln('    dfs-mode=radar-detect \ ');
    else
      _outln('    dfs-mode=none \ ');

    if (empty($radio[antenna_mode])) {
	_outln(sprintf('    wds-mode=static wds-default-bridge=none wds-default-cost=100 \ '));
    } else {
    if ($radio[antenna_mode] != 'Main')
          $radio[antenna_mode]= 'ant-b';
        else
          $radio[antenna_mode]= 'ant-a';

    _outln(sprintf('    antenna-mode=%s wds-mode=static wds-default-bridge=none wds-default-cost=100 \ ',$radio[antenna_mode]));
    }
    _outln('    wds-cost-range=50-150 wds-ignore-ssid=yes hide-ssid=no');

    if (isset($radio[interfaces])) foreach ($radio[interfaces] as $interface_id => $interface) {
       _outln(':delay 1');
       _outln_comment('Type: '.$interface[interface_type]);
       if ($interface[interface_type] == 'wds/p2p') {
         _outln_comment(t('Remove all existing wds interfaces'));
         _outln(sprintf(':foreach i in [/interface wireless wds find master-interface=wlan%s] \ ',$radio_id+1));
         _outln('do={:foreach n in [/interface wireless wds get $i name] \ ');
         _outln('do={:foreach inum in [/ip address find interface=$n] \ ');
         _outln('do={/ip address remove $inum;};}; \ ');
         _outln('/interface wireless wds remove $i;}');
         if (isset($interface[ipv4])) foreach ($interface[ipv4] as $ipv4_id => $ipv4)
         if (isset($ipv4[links])) foreach ($ipv4[links] as $link_id => $link) {
           if (preg_match("/(Working|Testing|Building)/",$link['flag']))
             $disabled='no';
           else
             $disabled='yes';
           $wdsname = 'wds_'.guifi_get_hostname($link['device_id']);
           if ($link['interface']['mac'] == null)
             $link['interface'][mac]= 'FF:FF:FF:FF:FF:FF';
           _outln('/ interface wireless wds');
           _outln(sprintf('add name="%s" master-interface=wlan%d wds-address=%s disabled=%s',$wdsname,$radio_id+1,$link['interface'][mac],$disabled));
           $item = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
           $ospf_zone = guifi_get_ospf_zone($zone);
           _outln(sprintf('/ ip address add address=%s/%d network=%s broadcast=%s interface=%s disabled=%s comment="%s"',$ipv4[ipv4],$item[maskbits],$item[netid],$item[broadcast],$wdsname,$disabled,$wdsname));

           if ($link['routing'] == 'OSPF') {
            ospf_interface($wdsname, $item[netid], $item[maskbits], $ospf_name, $ospf_zone, $ospf_id, 'no');
            bgp_peer($link['device_id'],$link['interface']['ipv4']['ipv4'],'yes');
            } else {
            ospf_interface($wdsname, $item[netid], $item[maskbits], $ospf_name, $ospf_zone, $ospf_id, 'yes');
            bgp_peer($link['device_id'],$link['interface']['ipv4']['ipv4'],'no');
}
         } // each wds link (ipv4)
       } else { // wds
         // wLan, wLan/Lan, Hotspot or client

         // Defining all networks and IP addresses at the interface
         if (isset($interface[ipv4])) foreach ($interface[ipv4] as $ipv4_id => $ipv4) {
           if ($interface[interface_type] == 'wLan/Lan') {
             $iname = $interface[interface_type];
             $ospf_routerid=$ipv4[ipv4];
           } else {
             $iname = 'wlan'.($radio_id+1);
           }
           $item = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
           _outln('/ip address');
           if ($interface[interface_type]=='Wan')
             _outln(sprintf(':foreach i in [find interface=%s] do={remove $i}',$iname));
           _outln(sprintf(':foreach i in [find address="%s/%d"] do={remove $i}',$ipv4[ipv4],$item[maskbits]));
           _outln(sprintf('/ ip address add address=%s/%d network=%s broadcast=%s interface=%s disabled=no',$ipv4[ipv4],$item[maskbits],$item[netid],$item[broadcast],$iname));
           $defined_ips[$ipv4[ipv4]] = $item;
           $ospf_zone = guifi_get_ospf_zone($zone);
           if ($radio[mode] != 'client') {
             ospf_interface($iname, $item[netid], $item[maskbits], $ospf_name, $ospf_zone, $ospf_id, 'no');
           } else {
             ospf_interface($iname, $item[netid], $item[maskbits], $ospf_name, $ospf_zone, $ospf_id, 'yes');
           }           
         }


           // HotSpot
         if ($interface[interface_type] == 'HotSpot') {
           _outln_comment();
           _outln_comment('HotSpot');
           _outln('/interface wireless');
           _outln(sprintf(':foreach i in [find name=hotspot%d] do={remove $i}',$radio_id+1));
           _outln(sprintf('add name="hotspot%d" arp=enabled master-interface=wlan%d ssid="guifi.net-%s" disabled="no"',$radio_id+1,$radio_id+1,variable_get("hotspot_ssid","HotSpot")));
           _outln('/ip address');
           _outln(sprintf(':foreach i in [find address="192.168.%d.1/24"] do={remove $i}',$radio_id+100));
           _outln(sprintf('/ip address add address=192.168.%d.1/24 interface=hotspot%d disabled=no',$radio_id+100,$radio_id+1));
           _outln('/ip pool');
           _outln(sprintf(':foreach i in [find name=hs-pool-%d] do={remove $i}',$radio_id+100));
           _outln(sprintf('add name="hs-pool-%d" ranges=192.168.%d.2-192.168.%d.254',$radio_id+100,$radio_id+100,$radio_id+100));
           _outln('/ip dhcp-server');
           _outln(sprintf(':foreach i in [find name=hs-dhcp-%d] do={remove $i}',$radio_id+100));
           _outln(sprintf('add name="hs-dhcp-%d" interface=hotspot%d lease-time=1h address-pool=hs-pool-%d bootp-support=static authoritative=after-2sec-delay disabled=no',$radio_id+100,$radio_id+1,$radio_id+100));
           _outln('/ip dhcp-server network');
           _outln(sprintf(':foreach i in [find address="192.168.%d.0/24"] do={remove $i}',$radio_id+100));
           _outln(sprintf('add address=192.168.%d.0/24 gateway=192.168.%d.1 domain=guifi.net comment=dhcp-%s',$radio_id+100,$radio_id+100,$radio_id));
           _outln('/ip hotspot profile');
           _outln(sprintf(':foreach i in [find name=hsprof%d] do={remove $i}',$radio_id+1));
           _outln(sprintf('add name="hsprof%d" hotspot-address=192.168.%d.1 dns-name="guests.guifi.net" html-directory=hotspot smtp-server=0.0.0.0 login-by=http-pap,trial split-user-domain=no trial-uptime=30m/1d trial-user-profile=default use-radius=no',$radio_id+1,$radio_id+100));
           _outln('/ip hotspot user profile');
           _outln('set default name="default" advertise-url=http://guifi.net/trespassos/');
           _outln('/ip hotspot');
           _outln(sprintf(':foreach i in [find name=hotspot%d] do={remove $i}',$radio_id+1));
           _outln(sprintf('add name="hotspot%d" interface=hotspot%d address-pool=hs-pool-%d profile=hsprof%d idle-timeout=5m keepalive-timeout=none addresses-per-mac=2 disabled=no',$radio_id+1,$radio_id+1,$radio_id+100,$radio_id+1));
           _outln_comment('end of HotSpot');
         } // HotSpot


         _outln(':delay 1');
         if ($interface[interface_type] != 'HotSpot') {
           // Not link only (AP), setting DHCP
           if ($mode=='ap-bridge') {
             $maxip = ip2long($item[netstart]) + 1;
             if (($maxip + 5) > (ip2long($item[netend]) - 5)) {
               $maxip = ip2long($item['netend']);
               $dhcp_disabled='yes';
             } else {
               $maxip = $maxip + 5;
               $dhcp_disabled='no';
             }

             _outln_comment();
             _outln_comment('DHCP');
             _outln(sprintf(':foreach i in [/ip pool find name=dhcp-%s] do={/ip pool remove $i;}',$iname));
             _outln(sprintf('/ip pool add name=dhcp-%s ranges=%s-%s',$iname,long2ip($maxip),$item[netend]));
             _outln(sprintf(':foreach i in [/ip dhcp-server find name=dhcp-%s] do={/ip dhcp-server remove $i;}',$iname));
             _outln(sprintf('/ip dhcp-server add name=dhcp-%s interface=%s address-pool=dhcp-%s disabled=%s',$iname,$iname,$iname,$dhcp_disabled));
             _outln(sprintf(':foreach i in [/ip dhcp-server network find address="%s/%d"] do={/ip dhcp-server network remove $i;}',$item[netid],$item[maskbits]));
             _outln(sprintf('/ip dhcp-server network add address=%s/%d gateway=%s domain=guifi.net comment=dhcp-%s',$item[netid],$item[maskbits],$item[netstart],$iname));

             $dhcp = array();
             $dhcp[] = '/ip dhcp-server lease';
             $dhcp[] = ':foreach i in [find comment=""] do={remove $i;}';
             $dhcp[] = ':delay 1';
             if (isset($ipv4[links])) foreach ($ipv4[links] as $link_id => $link) {
               if (isset($link['interface'][ipv4][ipv4]))
               if (ip2long($link['interface'][ipv4][ipv4]) >= $maxip)
                 $maxip = ip2long($link['interface'][ipv4][ipv4]) + 1;
               if ($link['interface'][mac] == null)
                 $rmac = 'ff:ff:ff:ff:ff:ff';
               else 
                 $rmac = $link['interface'][mac];
                 $dhcp[] = sprintf('add address=%s mac-address=%s client-id=%s server=dhcp-%s',$link['interface'][ipv4][ipv4],$rmac,guifi_get_hostname($link[device_id]),$iname);
             }
             foreach ($dhcp as $outln)
               _outln($outln);
           }
         }
       } // wLan, wLan/Lan or client
       _outln_comment();
    } // foreach radio->interface

    _outln(':delay 1');

  } // foreach radio

  if ($firewall) {
     _outln_comment();
     _outln_comment('Device has firewall (setting up as CPE)');

    // Setting gateway
     _outln(sprintf('/ip route add gateway=%s',$link['interface'][ipv4][ipv4]));

    // Setting proxy-arp
    _outln('/interface ethernet set ether1 arp=proxy-arp');
    _outln('/ip address');

    // Setting private network and DHCP
    _outln(':foreach i in [find address="192.168.1.1/24"] do={remove $i}');
    _outln('/ip address add address=192.168.1.1/24 network=192.168.1.0 broadcast=192.168.1.255 interface=ether1 comment="" disabled=no');
    _outln(':delay 1');
    _outln('/ip pool');
    _outln(':foreach i in [find name=private] do={remove $i}');
    _outln('add name="private" ranges=192.168.1.100-192.168.1.200');
    _outln(':delay 1');
    _outln('/ip dhcp-server');
    _outln(':foreach i in [find name=private] do={remove $i}');
    _outln('add name="private" interface=ether1 lease-time=3d address-pool=private bootp-support=static authoritative=after-2sec-delay disabled=no');
    _outln(':delay 1');
    _outln('/ip dhcp-server network');
    _outln(':foreach i in [find] do={remove $i}');
  if ($secondary_dns != null)
    _outln(sprintf('add address=192.168.1.0/24 gateway=192.168.1.1 netmask=24 dns-server=%s,%s domain="guifi.net" comment=""',$primary_dns,$secondary_dns));
  else if ($primary_dns != null)
    _outln(sprintf('add address=192.168.1.0/24 gateway=192.168.1.1 netmask=24 dns-server=%s domain="guifi.net" comment=""',$primary_dns));
    _outln(':delay 1');

    // be sure that there is no dhcp client requests since having a static ip
    _outln('/ip dhcp-client');
    _outln(':foreach i in [find] do={remove $i}');
    _outln(':delay 1');

    // NAT private network
    _outln('/ip firewall nat');
    _outln(':foreach i in [find] do={remove $i}');
    _outln(':delay 1');
    _outln('add chain=srcnat out-interface=wlan1 action=masquerade comment="" disabled=no');

    // Firewall enabled, allowing winbox, ssh and snmp
    _outln('/ip firewall filter');
    _outln(':foreach i in [find] do={remove $i}');
    _outln('add chain=input connection-state=established action=accept comment="Allow Established connections" disabled=no');
    _outln('add chain=input protocol=udp action=accept comment="Allow UDP" disabled=no');
    _outln('add chain=input src-address="192.168.1.0/24" action=accept comment="Allow access to router from known network" disabled=no');
    _outln('add chain=input protocol=tcp dst-port=22 action=accept comment="Allow remote ssh" disabled=no');
    _outln('add chain=input protocol=udp dst-port=161 action=accept comment="Allow snmp" disabled=no');
    _outln('add chain=input protocol=tcp dst-port=8291 action=accept comment="Allow remote winbox" disabled=no');
    _outln('add chain=input protocol=icmp action=accept comment="Allow ping" disabled=no');
    _outln('add chain=forward connection-state=established action=accept comment="Allow already established connections" disabled=no');
    _outln('add chain=forward connection-state=related action=accept comment="Allow related connections" disabled=no');
    _outln('add chain=forward src-address="192.168.1.0/24" action=accept comment="Allow access to router from known network" disabled=no');
    _outln('add chain=input protocol=tcp connection-state=invalid action=drop comment="" disabled=no');
    _outln('add chain=forward protocol=tcp connection-state=invalid action=drop comment="Drop invalid connections" disabled=no');
    _outln('add chain=forward action=drop comment="Drop anything else" disabled=no');
    _outln('add chain=input action=drop comment="Drop anything else" disabled=no');
    _outln(':delay 1');

    // End of Unsolclic
    _outln_comment();
    _outln(sprintf(':log info "Unsolclic for %d-%s executed."',$dev->id,$dev->nick));
    _outln('/');
    return;
  }

  _outln_comment();
  _outln_comment('Routed device');


  // Now, defining other interfaces (if they aren't yet)
  _outln_comment();
  _outln_comment(t('Other cable connections'));
  if (isset($dev->interfaces)) foreach ($dev->interfaces as $interface_id => $interface) {
    switch ($interface[interface_type]) {
    case 'vlan':  $iname = 'wLan/Lan'; break;
    case 'vlan2': $iname = 'ether2'; break;
    case 'vlan3': $iname = 'ether3'; break;
    case 'vlan4': $iname = 'wLan/Lan'; break;
    case 'Wan':   $iname = 'wLan/Lan'; break;
    default:
      $iname = $interface[interface_type];
      break;
    }
    $ospf_intrefaces[] = $iname;
    if (isset($interface[ipv4])) foreach ($interface[ipv4] as $ipv4_id => $ipv4) {
      if (!isset($defined_ips[$ipv4[ipv4]])) {
        $disabled='yes';
        if (isset($ipv4[links])) {
          unset($comments);
          foreach ($ipv4[links] as $link_id => $link) {
            if (($disabled='yes') and (preg_match("/(Working|Testing|Building)/",$link['flag'])))
              $disabled='no';
            $comments[] = guifi_get_hostname($link[device_id]);
            $ospf_zone = guifi_get_ospf_zone($zone);
            $item = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
               if ($link['routing'] == 'OSPF') {
                ospf_interface($iname, $item[netid], $item[maskbits], $ospf_name, $ospf_zone, $ospf_id, 'no');
                bgp_peer($link['device_id'],$link['interface']['ipv4']['ipv4'], 'yes');
                } else {
                ospf_interface($iname, $item[netid], $item[maskbits], $ospf_name, $ospf_zone, $ospf_id, 'yes');
                bgp_peer($link['device_id'],$link['interface']['ipv4']['ipv4'], 'no');
                }
          }
        } else
          $disabled='no';
        $item = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
        _outln(sprintf(':foreach i in [/ip address find address="%s/%d"] do={/ip address remove $i;}',$ipv4[ipv4],$item[maskbits]));
        _outln(':delay 1');
        _outln(sprintf('/ ip address add address=%s/%d network=%s broadcast=%s interface=%s disabled=%s comment="%s"',$ipv4[ipv4],$item[maskbits],$item[netid],$item[broadcast],$iname,$disabled,implode(',',$comments)));
        $defined_ips[$ipv4[ipv4]] = $item;
      }
    }
  }

  // NAT for internal addresses while being used inside the router
  
  _outln_comment();
  _outln_comment(t('Internal addresses NAT'));
  _outln(':foreach i in [/ip firewall nat find src-address="172.16.0.0/12"] do={/ip firewall nat remove $i;}');
  _outln(':foreach i in [/ip firewall nat find src-address="192.168.0.0/16"] do={/ip firewall nat remove $i;}');
  _outln('/ip firewall nat');
  if ($dev->variable[firmware] == 'RouterOSv2.9') {
  _outln(sprintf('add chain=srcnat src-address="192.168.0.0/16" dst-address=!192.168.0.0/16 action=src-nat to-addresses=%s to-ports=0-65535 comment="" disabled=no',$ospf_routerid));
  _outln(sprintf('add chain=srcnat src-address="172.16.0.0/12" dst-address=!172.16.0.0/12 protocol=!ospf action=src-nat to-addresses=%s to-ports=0-65535 comment="" disabled=no',$ospf_routerid));
  }
  if (($dev->variable[firmware] == 'RouterOSv3.x') or ($dev->variable['firmware'] == 'RouterOSv4.0+') or ($dev->variable['firmware'] == 'RouterOSv4.7+')) {
  _outln(sprintf('add chain=srcnat src-address="192.168.0.0/16" dst-address=!192.168.0.0/16 action=src-nat to-addresses=%s comment="" disabled=no',$ospf_routerid));
  _outln(sprintf('add chain=srcnat src-address="172.16.0.0/12" dst-address=!172.16.0.0/12 protocol=!ospf action=src-nat to-addresses=%s comment="" disabled=no',$ospf_routerid));
  }
  // BGP
  _outln_comment();
  _outln_comment(t('BGP Routing'));
  _outln_comment(t('BGP &#038; OSPF Filters'));
  _outln(':foreach i in [/routing filter find chain=ospf-in] do={/routing filter remove $i;}');
  _outln(':foreach i in [/routing filter find chain=ospf-out] do={/routing filter remove $i;}');
  _outln("/ routing filter");
  _outln('add chain=ospf-out prefix=10.0.0.0/8 prefix-length=8-32 invert-match=no action=accept comment="" disabled=no');
  _outln('add chain=ospf-out invert-match=no action=discard comment="" disabled=no');
  _outln('add chain=ospf-in prefix=10.0.0.0/8 prefix-length=8-32 invert-match=no action=accept comment="" disabled=no');
  _outln('add chain=ospf-in invert-match=no action=reject comment="" disabled=no');
  _outln_comment();
  _outln_comment(t('BGP instance'));
  _outln("/ routing bgp instance");
  _outln(sprintf('set default name="default" as=%d router-id=%s redistribute-static=yes \ ',$dev->id,$ospf_routerid));
  _outln('redistribute-connected=yes redistribute-rip=yes redistribute-ospf=yes \ ');
  _outln('redistribute-other-bgp=yes out-filter=ospf-out \ ');
  _outln('client-to-client-reflection=yes comment="" disabled=no');



  // OSPF
       _outln_comment();
       _outln_comment(t('OSPF Routing'));
  if (($dev->variable[firmware] == 'RouterOSv3.x') or ($dev->variable['firmware'] == 'RouterOSv2.9')) {
       _outln(sprintf('/routing ospf set router-id=%s distribute-default=never redistribute-connected=no 
     redistribute-static=no redistribute-rip=no redistribute-bgp=as-type-1',$ospf_routerid));
  }
  if (($dev->variable[firmware] == 'RouterOSv4.0+') or ($dev->variable['firmware'] == 'RouterOSv4.7+')) {
       _outln(sprintf('/routing ospf instance set default name=default router-id=%s comment="" disabled=no distribute-default=never 
     redistribute-bgp=as-type-1 redistribute-connected=no redistribute-other-ospf=no redistribute-rip=no redistribute-static=no in-filter=ospf-in out-filter=ospf-out',$ospf_routerid));
  }

  // End of Unsolclic
  _outln_comment();
  _outln(sprintf(':log info "Unsolclic for %d-%s executed."',$dev->id,$dev->nick));
  _outln('/');
}
?>
