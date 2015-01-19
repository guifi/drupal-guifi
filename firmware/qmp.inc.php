<?php

function unsolclic_qmp($dev) {
  $version = "v1.0.3";

//  sed 's/<br \/>//g'

//  echo "<pre>";
//  _outln_comment("<pre>");
  _outln_comment("<style type=\"text/css\"> x {font-family:courier;} </style> <x>");
  _outln_comment("qMp Guifi-oneclick ".$version);
  _outln_comment("&nbsp;&nbsp;__&nbsp;_&nbsp;&nbsp;/\/\&nbsp;&nbsp;_&nbsp;__");
  _outln_comment("&nbsp;/&nbsp;_`&nbsp;|/&nbsp;&nbsp;&nbsp;&nbsp;\|&nbsp;'_&nbsp;\ ");
  _outln_comment("|&nbsp;(_|&nbsp;/&nbsp;/\/\&nbsp;\&nbsp;|_)&nbsp;| ");
  _outln_comment("&nbsp;\__,&nbsp;\/&nbsp;&nbsp;&nbsp;&nbsp;\/&nbsp;.__/ ");
  _outln_comment("&nbsp;&nbsp;&nbsp;&nbsp;|_|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|_| ");
  _outln_comment("&nbsp;quick MESH project </x> ");
  _outln_comment("");
  _outln_comment("<b>Important:</b> You should have <b>'qmp-guifi'</b> package installed in your node.");
  _outln_comment("");
  _outln_comment("To apply this configuration in your node, you can follow this instructions: ");
  _outln_comment("<a href='http://dev.qmp.cat/projects/qmp/wiki/Guifi_oneclick' target='_blank'>http://dev.qmp.cat/projects/qmp/wiki/Guifi_oneclick</a> ");
  _outln_comment("");
 
  // ONLY THE FIRST MESH IP FOUND IS GET 
  // TO DO: Check if there are more than one MESH radios and/or interfaces
  //
  $mesh="no";
  $ipv4="-";
  $netmask="-";
  $devmodel="-";

  foreach ($dev->radios as $radio) {
    if ($radio['mode'] == 'mesh') {
      $mesh="yes";
      $ipd = guifi_main_ip($dev->id);
      if ($ipd != '') {
        $ipv4 = $ipd['ipv4'];
        $netmask = $ipd['netmask'];
        $maskbits = $ipd['maskbits'];
      }
      else { $ipv4="-"; $netmask="-"; $maskbits="-"; }
    }
  }

  // GET ZONE NICK (MAYBE ID?) 
  //
  $node = node_load(array('nid' => $dev->nid));
  $zone = node_load(array('nid' => $node->zone_id));
  $zonename = $zone->nick;


  _outln();
  _outln("meshradio='".$mesh."'");

  if ($mesh == 'yes') {
    _outln("nodename='".$node->nick."'"); // This is the node name
    _outln("latitude='".$node->lat."'");
    _outln("longitude='".$node->lon."'");
    _outln("devname='".$dev->nick."'");   // This is the device name with mesh radio
    _outln("devmodel='".$dev->model."'");
    _outln("ipv4='".$ipv4."'");
    _outln("netmask='".$netmask."'");
    _outln("zoneid='".$zonename."'");
  }
  else {
    _outln();
    _outln_comment(" <b>You don't have any Mesh radio!</b>");
    _outln_comment(" If you want to use Guifi-oneclick, make sure you configure it properly.");
    _outln_comment(" You can follow the instructions in the wiki: <a href='./unsolclic' target='_self'>EN (not yet)</a>, <a href='http://es.wiki.guifi.net/wiki/Mesh#Conectarse_a_una_red_Mesh' target='_blank'>ES</a>, <a href='http://ca.wiki.guifi.net/wiki/Mesh#Connectar-se_a_una_xarxa_Mesh' target='_blank'>CA</a>");
  }

//  var_dump($node->nid['zone_id']);

}

?>
