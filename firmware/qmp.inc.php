<?php

function unsolclic_qmp($dev) {
  $version = "v1.0b3";

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
  _outln_comment("To apply this configuration in your node, follow the instructions: ");
  _outln_comment("<a href='http://dev.qmp.cat/wiki/LINK' target='_blank'>http://dev.qmp.cat/wiki/[LINK NOT YET]</a> ");
  _outln_comment("");
  _outln_comment("<b>Important:</b> If you don't have qMp v2.x (>201311xx) assure you have <b>'Guifi-oneclick'</b> installed in your node. ");
  _outln_comment("");
 
  // ONLY THE FIRST MESH IP FOUND IS GET 
  // NEED TO CHECK IF TWO MESH RADIOS AND/OR TWO INTERFACES
  //
  $mesh="no";
  $ipv4="-";
  $netmask="-";
  $devmodel="-";

  foreach ($dev->radios as $radio) {
    if ($radio['mode'] == 'mesh') {
      $mesh="yes";

      $i=0;
      foreach ($radio['interfaces'] as $interface) {
        // If interface has IP addresses we get the first one
        if (isset($interface['ipv4'])) {
          $ipv4 = $interface['ipv4'][0]['ipv4'];
          $netmask = $interface['ipv4'][0]['netmask'];
          $maskbits = $interface['ipv4'][0]['maskbits'];
        }
        else { $ipv4="-"; $netmask="-"; $maskbits="-"; }

       }
      break;
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
    _outln("nodename='".$node->nick."'");  // THIS IS NODE NAME
    _outln("devname='".$dev->nick."'");  // THIS IS DEVICE NAME
    _outln("devmodel='".$dev->model."'");
    _outln("ip='".$ipv4."'");
    _outln("mask='".$netmask."'");
    _outln("zone='".$zonename."'");
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
