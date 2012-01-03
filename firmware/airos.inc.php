<?php

function unsolclic_airos($dev) {
  $version = "1.1";
  $loc = node_load(array('nid' => $dev->nid));
  $zone = node_load(array('nid' => $loc->zone_id));
  $wan = guifi_unsolclic_if($dev->id,'Wan');
  
  list($primary_dns,$secondary_dns) = explode(' ',guifi_get_dns($zone,2));
  $dns[] .=$primary_dns;
  $dns[] .=$secondary_dns;

   foreach ($dev->radios[0]['interfaces'] as $interface_id => $interface)
     foreach ($interface['ipv4'] as $ipv4_id => $ipv4)
       if (isset($ipv4['links'])) foreach ($ipv4['links'] as $key => $link)
         $gateway = $link['interface']['ipv4']['ipv4'];

  $apssid = guifi_get_ap_ssid($link['interface']['device_id'],$link['interface']['radiodev_counter']);
   
  if (empty($dev->radios[0][antenna_mode]))
    $dev->radios[0][antenna_mode]= 'Main';
  if ($dev->radios[0][antenna_mode] == 'Main') {
    if ($dev->variable['model_id'] == '34') // NanoStation Loco2.
      $dev->radios[0][antenna_mode]= '1'; // Main on NanoStation Loco2.
    else
      $dev->radios[0][antenna_mode]= '2'; // Main on NanoStation2, Nanostation5 and  Loco5.
  } else {
    if ($dev->variable['model_id'] == '34') // NanoStation Loco2.
      $dev->radios[0][antenna_mode]= '2'; // External on NanoStation Loco2.
    else
      $dev->radios[0][antenna_mode]= '1'; // External on NanoStation2, Nanostation5 and  Loco5.
  }

  $radiorx = $dev->radios[0][antenna_mode];
  $radiotx = $dev->radios[0][antenna_mode];

  switch ($dev->variable['model_id']) {
    case "25": //NanoStation2
      $net_mode= 'b';
      $lnet_mode= 'B Only (2,4Ghz 11MB)';
      $rate_max= '11M';
      $txpower= '6';
      $ack= '45';
      $extant = 'disabled';
      $mcastrate= '11';
    break;
    case "26": //NanoStation5
      $net_mode= 'a';
      $lnet_mode= 'A (5Ghz)';
      $rate_max= '54M';
      $txpower= '6';
      $ack= '25';
      $extant = 'disabled';
      $mcastrate= '54';
    break;
    case "34": //NanoStation Loco2
      $net_mode= 'b';
      $lnet_mode= 'B Only (2,4Ghz 11MB)';
      $rate_max= '11M';
      $txpower= '6';
      $ack= '44';
      $extant = 'enabled';
      $mcastrate= '11';
    break;
    case "35": //NanoStation Loco5
      $net_mode= 'a';
      $lnet_mode= 'A (5Ghz)';
      $rate_max= '54M';
      $txpower= '6';
      $ack= '25';
      $extant= 'disabled';
      $mcastrate= '54';

  }

  ## Create Script file
    $File = 'files/nanostation/'.$dev->nick.'.cfg';
    $Handle = fopen($File, 'w');
    $Data = "aaa.1.status=disabled
aaa.status=disabled
bridge.1.devname=br0
bridge.1.fd=1
bridge.1.port.1.devname=eth0
bridge.1.port.2.devname=ath0
bridge.status=disabled
dhcpc.1.devname=br0
dhcpc.1.status=disabled
dhcpc.status=disabled
dhcpd.1.devname=eth0
dhcpd.1.end=192.168.1.254
dhcpd.1.lease_time=3600
dhcpd.1.netmask=255.255.255.0
dhcpd.1.start=192.168.1.33
dhcpd.1.status=enabled
dhcpd.status=enabled
ebtables.1.cmd=-t nat -A PREROUTING --in-interface ath0 -j arpnat --arpnat-target ACCEPT
ebtables.1.status=enabled
ebtables.2.cmd=-t nat -A POSTROUTING --out-interface ath0 -j arpnat --arpnat-target ACCEPT
ebtables.2.status=enabled
ebtables.3.cmd=-t broute -A BROUTING --protocol 0x888e --in-interface ath0 -j DROP
ebtables.3.status=enabled
ebtables.status=disabled
httpd.https.status=enabled
httpd.port.http=80
httpd.status=enabled
iptables.1.status=enabled
iptables.1.cmd=-t nat -I POSTROUTING -o ath0 -j MASQUERADE
iptables.2.status=disabled
iptables.status=enabled
netconf.1.devname=eth0
netconf.1.ip=192.168.1.1
netconf.1.netmask=255.255.255.0
netconf.1.promisc=enabled
netconf.1.status=enabled
netconf.1.up=enabled
netconf.2.allmulti=enabled
netconf.2.devname=ath0
netconf.2.status=enabled
netconf.2.up=enabled
netconf.3.devname=br0
netconf.3.ip=192.168.1.20
netconf.3.netmask=255.255.255.0
netconf.3.status=disabled
netconf.3.up=enabled
netconf.status=enabled
netmode=router
ppp.1.password=
ppp.1.status=disabled
ppp.status=disabled
radio.1.ack.auto=enabled
radio.1.ackdistance=450
radio.1.ani.status=enabled
radio.1.chanshift=0
radio.1.clksel=0
radio.1.countrycode=724
radio.1.devname=ath0
radio.1.frag=off
radio.1.mode=managed
radio.1.rate.auto=enabled
radio.1.rts=off
radio.1.tx_antenna_diversity=disabled
radio.1.rx_antenna_diversity=disabled
radio.1.status=enabled
radio.1.thresh62a=28
radio.1.thresh62b=28
radio.1.thresh62g=28
radio.ratemodule=ath_rate_minstrel
radio.countrycode=724
radio.status=enabled
resolv.host.1.status=enabled
resolv.nameserver.1.status=enabled
resolv.nameserver.2.status=enabled
resolv.status=enabled
route.1.devname=ath0
route.1.ip=0.0.0.0
route.1.netmask=0
route.1.status=enabled
route.status=enabled
snmp.community=public
snmp.contact=guifi@guifi.net
snmp.status=enabled
telnetd.status=enabled
sshd.status=enabled
tshaper.status=disabled
users.1.name=root
users.1.password=JjYNUu92yMZd.
users.1.status=enabled
users.status=enabled
wireless.1.ap=
wireless.1.authmode=1
wireless.1.compression=0
wireless.1.devname=ath0
wireless.1.fastframes=0
wireless.1.frameburst=0
wireless.1.hide_ssid=disabled
wireless.1.l2_isolation=enabled
wireless.1.macclone=disabled
wireless.1.rssi_led1=1
wireless.1.rssi_led2=15
wireless.1.rssi_led3=22
wireless.1.rssi_led4=30
wireless.1.security=none
wireless.1.status=enabled
wireless.1.wds=disabled
wireless.1.wmm=disabled
wireless.1.wmmlevel=-1
wireless.status=enabled
wpasupplicant.device.1.status=disabled
wpasupplicant.status=disabled
wireless.1.ssid=guifi.net-$apssid
netconf.2.ip=$wan->ipv4
netconf.2.netmask=$wan->netmask
route.1.gateway=$gateway
resolv.nameserver.1.ip=$primary_dns
resolv.nameserver.2.ip=$secondary_dns
resolv.host.1.name=$dev->nick
snmp.location=$loc->nick
radio.1.ieee_mode=$net_mode
radio.1.rate.max=$rate_max
radio.1.txpower=$txpower
radio.1.acktimeout=$ack
radio.1.rx_antenna=$radiorx
radio.1.tx_antenna=$radiotx
radio.1.ext_antenna=$extant
radio.1.mcastrate=$mcastrate
";

  fwrite($Handle, $Data);
  print '<br/><a href="'.base_path().'files/nanostation/'.$dev->nick.'.cfg"> Click here to download configuration file for: '.$dev->nick.' </a><br />';
  print 'Put the mouse cursor over the link. Right click the link and select "Save Link/Target As..." to save to your Desktop.<br /><br />';
  fclose($Handle);

  if ( $radiorx == '2') {
    if ($dev->variable['model_id'] == '34') // NanoStation Loco2.
      $ant = 'Horizontal';
    if ($dev->variable['model_id'] == '35') // NanoStation Loco5.
      $ant = 'Vertical';
    if (($dev->variable['model_id'] == '25') || ($dev->variable['model_id'] == '26')) // NanoStation2 and NanoStation5.
      $ant = 'Main/Internal - Vertical';
  } else {
    if ($dev->variable['model_id'] == '34') // NanoStation Loco2.
      $ant = 'Vertical';
    if ($dev->variable['model_id'] == '35') // NanoStation Loco5.
      $ant = 'Horizontal';
    if (($dev->variable['model_id'] == '25') || ($dev->variable['model_id'] == '26')) // NanoStation2 and NanoStation5.
      $ant = 'Aux/External - Vertical';
  }
  _outln_comment('Configuration for AirOs> Unsolclic version:'.$version.' !! WARNING: Beta version !!');
  _outln_comment(' Device: '.$dev->nick.'');
  _outln_comment();
  _outln_comment(' Methods to upload/execute the file:');
  _outln_comment(' 1.- As a file. Upload this through web managment:');
  _outln_comment('     a.System->Configuratuion Managment->Locate file');
  _outln_comment('     b.Upload');
  _outln_comment(' 2.- Telnet: Open a terminal session, create new /tmp/system.cfg file and cut&paste');
  _outln_comment('     the contents of the file. Save it an execute the command:');
  _outln_comment();
  _outln_comment('     /usr/etc/rc.d/rc.softrestart save');
  _outln_comment();
  _outln_comment(' Notes:');
  _outln_comment(' -Web access method is recommended');
  _outln_comment('   (the script reconfigures some IP addresses, so communication can be lost.');
  _outln_comment('   192.168.1.1 will be the new one)');
  _outln_comment(' -Changes are done in user passwords on the device, default user and password are');
  _outln_comment('  changed to root/guifi.');
  _outln_comment(' -The ACK is set to 45 for 802.11b mode, and to 25 for 802.11a (600 meters aprox,)');
  _outln_comment();
  _outln('## Link to AP info');
  _outln('
    Ap SSID = guifi.net-'.$apssid.'<br />
    WAN Ip address = '.$wan->ipv4.'<br />
    WAN Netmask = '.$wan->netmask.'<br />
    WAN Gateway = '.$gateway.'<br />
    Primary DNS Server = '.$primary_dns.'<br />
    Secondary DNS Server = '.$secondary_dns.'<br />
    Device HostName = '.$dev->nick.'<br />
    IEEE 802.11 Mode: = '.$lnet_mode.'<br />
    Antenna Selection or/and Polarization: = '.$ant.'<br />
        ');
}
?>