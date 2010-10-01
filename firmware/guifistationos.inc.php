<?php

function unsolclic_guifistationos($dev) {
  $version = "1.0";
  $loc = node_load(array('nid' => $dev->nid));
  $zone = node_load(array('nid' => $loc->zone_id));
  $wan = guifi_unsolclic_if($dev->id,'Wan');
  
  list($primary_dns,$secondary_dns) = explode(' ',guifi_get_dns($zone,2));
  $dns[] .=$primary_dns;
  $dns[] .=$secondary_dns;

  list($ntp1,$ntp2) = explode(' ',guifi_get_ntp($zone,2));
  $ntp[] .= $ntp1;
  $ntp[] .= $ntp2;
       
   foreach ($dev->radios[0]['interfaces'] as $interface_id => $interface) 
     foreach ($interface['ipv4'] as $ipv4_id => $ipv4) 
       if (isset($ipv4['links'])) foreach ($ipv4['links'] as $key => $link)
         $gateway = $link['interface']['ipv4']['ipv4'];

  $apssid = guifi_get_ap_ssid($link['interface']['device_id'],$link['interface']['radiodev_counter']);
   
  if (empty($dev->radios[0][antenna_mode]))
    $dev->radios[0][antenna_mode]= 'Main';

  if ($dev->radios[0][antenna_mode] == 'Main')
    $dev->radios[0][antenna_mode]= '1'; // Main on GuifiStation2 and GuifiStation5.
  else
    $dev->radios[0][antenna_mode]= '2'; // External on GuifiStation2 and GuifiStation5.

  $radiorx = $dev->radios[0][antenna_mode];
  $radiotx = $dev->radios[0][antenna_mode];

  switch ($dev->variable['model_id']) {
    case "49": // GuifiStation2
      $skin = 'skin.active=guifi-station-2';
      $net_mode= 'B';
      $lnet_mode= 'B Only (2,4Ghz 11MB)';
      $rate_max= '11M';
      $txpower= '10';
      $ack= '45';
      $extant = 'disabled';
      $mcastrate= '11';
      $iface = 'eth0';
      $wiface = 'ath0';
      $lanip = '192.168.2.66';
      $lanmask = '255.255.255.0';
      $wanip = $wan->ipv4;
      $wanmask = $wan->netmask;
      $iiface = '1';
      $specs = 'device.limitband.1.bands.1.band=B
device.limitband.1.bands.2.band=G
device.limitband.1.bands.3.band=PUREG';

    break;
    case "50": // GuifiStation5
      $skin = 'skin.active=guifi-station-5';
      $net_mode= 'A';
      $lnet_mode= 'A (5Ghz)';
      $rate_max= '54M';
      $txpower= '10';
      $ack= '45';
      $extant = 'disabled';
      $mcastrate= '54';
      $iface = 'ath0';
      $wiface = 'eth0';
      $lanip = $wan->ipv4;
      $lanmask = $wan->netmask;
      $wanip = '192.168.2.66';
      $wanmask = '255.255.255.0';
      $iiface = '2';
      $specs = 'device.limitband.1.bands.1.band=A
device.limitband.1.bands.1.status=enabled
device.limitband.1.bands.2.band=AST';
    break;

  }

  ## Create Script file
    $File = 'files/guifistation/'.$dev->nick.'.cfg';
    $Handle = fopen($File, 'w');
    $Data = "netconf.status=enabled
netconf.1.status=enabled
netconf.2.status=enabled
radio.countrycode=es
radio.status=enabled
radio.1.channel=0
radio.1.devname=ath0
radio.1.frag=off
radio.1.mode=managed
radio.1.parent=wifi0
radio.1.rate.auto=enabled
radio.1.rts=off
radio.1.rx_antenna_diversity=disabled
radio.1.status=enabled
radio.1.turbo=disabled
radio.1.tx_antenna_diversity=disabled
wireless.status=enabled
wireless.1.devname=ath0
wireless.1.fastframes=disabled
wireless.1.frameburst=disabled
wireless.1.l2_isolation=disabled
wireless.1.max_clients=64
wireless.1.ssid_broadcast=enabled
wireless.1.status=enabled
wireless.1.wmm=disabled
route.status=enabled
route.1.devname=ath0
route.1.ip=0.0.0.0
route.1.netmask=0
route.1.status=enabled
firewall.status=enabled
firewall.rule.1.chain=POSTROUTING
firewall.rule.1.out=ath0
firewall.rule.1.status=enabled
firewall.rule.1.table=nat
firewall.rule.1.target=MASQUERADE
dhcpd.status=enabled
dhcpd.1.devname=eth0
dhcpd.1.dns.1.server=192.168.2.66
dhcpd.1.end=192.168.2.254
dhcpd.1.gateway=192.168.2.66
dhcpd.1.lease_time=600
dhcpd.1.netmask=255.255.255.0
dhcpd.1.start=192.168.2.100
dhcpd.1.status=enabled
syslog.file=/var/log/messages
syslog.file.msg.level=info
syslog.file.umask=077
syslog.status=enabled
snmpd.contact=guifi@guifi.net
snmpd.rocommunity=public
snmpd.status=enabled
resolv.status=enabled
date.status=enabled
date.timezone=GMT-1
ntpd.status=enabled
ntpd.1.status=enabled
users.status=enabled
users.1.name=admin
users.1.password=84OZbhpCnpRZI
users.1.status=enabled
device.mode=router
device.status=enabled
discoveryd.status=enabled
httpd.backlog=100
httpd.external.status=disabled
httpd.max.connections=50
httpd.max.request=51200
httpd.port.admin=444
httpd.port.http=80
httpd.port.https=443
httpd.status=enabled
httpd.verbose=disabled
sshd.port=22
sshd.status=enabled
dnsmasq.status=enabled
dnsmasq.1.status=enabled
dnsmasq.1.devname=eth0
device.limitband.status=enabled
device.limitband.1.status=enabled
device.limitband.1.devname=ath0
wireless.1.ssid=guifi.net-$apssid
netconf.1.devname=$iface
netconf.1.ip=$lanip
netconf.1.netmask=$lanmask
netconf.2.devname=$wiface
netconf.2.ip=$wanip
netconf.2.netmask=$wanmask
route.1.gateway=$gateway
resolv.nameserver.1.ip=$primary_dns
resolv.nameserver.2.ip=$secondary_dns
snmpd.name=$dev->nick
snmpd.location=$loc->nick
radio.1.ieee_mode=$net_mode
radio.1.rate.max=$rate_max
radio.1.txpower=$txpower
radio.1.acktimeout=$ack
radio.1.rx_antenna=$radiorx
radio.1.tx_antenna=$radiotx
ntpd.1.server=$ntp1
skin.active=$skin
netconf.$iiface.duplex=full
netconf.$iiface.speed=100
netconf.$iiface.up=enabled
$specs
";

  fwrite($Handle, $Data);
  _outln_comment('Unsolclic version: '.$version);
  print '<br/><a href="'.base_path().'files/guifistation/'.$dev->nick.'.cfg"> Click here to download configuration file for: '.$dev->nick.' </a><br />';
  print 'Put the mouse cursor over the link. Right click the link and select "Save Link/Target As..." to save to your Desktop.<br /><br />';
  fclose($Handle);

  _outln_comment(' Method to upload/execute the file:');
  _outln('     1. Open your web browser and type the router IP address (Usually 192.168.2.66) and login');
  _outln('     2. Go to System Tab');
  _outln('     3. Press on restore button');
  _outln('     4. Select downloaded file and upload it');
  _outln('     5. When the saved new settings message appears on the screen, click on Reboot button');
  _outln('     6. Wait aproximate 2 minutes, then you can surf the network!');
  _outln();
  _outln_comment(' Notes:');
  _outln('   The script reconfigures IP addresses, so communication can be lost.');
  _outln(' -Changes are done in user passwords on the device,');
  _outln('  default user and password are changed to admin/guifi.');
  _outln(' -The ACK is set to 45 for 802.11b mode, and to 45 for 802.11a (600 meters aprox,)');
  _outln();
  _outln_comment(' Link to AP info:');
  _outln('
    Ap SSID = guifi.net-'.$apssid.'<br />
    WAN Ip address = '.$wan->ipv4.'<br />
    WAN Netmask = '.$wan->netmask.'<br />
    WAN Gateway = '.$gateway.'<br />
    Primary DNS Server = '.$primary_dns.'<br />
    Secondary DNS Server = '.$secondary_dns.'<br />
    Device HostName = '.$dev->nick.'<br />
    IEEE 802.11 Mode: = '.$lnet_mode.'<br />
        ');
}
?>
