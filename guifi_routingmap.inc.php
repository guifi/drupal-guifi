<?php
/**
 * @file guifi_routingmap.inc.php
 * Created on 01/12/2009 by Eduard
 * Functions for routing map tools
 */

function guifi_routingmap($action = 'init',$actionid) {
  if (!is_numeric($actionid)) return;
		
	switch ($action) {
	case 'init':
		guifi_routingmap_init();
	case 'allinit':
    guifi_routingmap_all_init();
	case 'search':
		$json=guifi_routingmap_search($_GET["lat1"],$_GET["lon1"],$_GET["lat2"],$_GET["lon2"]);
		echo $json;
		return;
		break;
	case 'allsearch':
		$json=guifi_routingmap_all_search($_GET["lat1"],$_GET["lon1"],$_GET["lat2"],$_GET["lon2"]);
		echo $json;
		return;
		break;
	}
}
 
function guifi_routingmap_init(){
  $output = "";
  if (guifi_gmap_key()) {
    drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_gmap_routingmap.js');
    $output .=  '<form>' .
        '<input type=hidden value='.base_path().drupal_get_path('module','guifi').'/js/'.' id=edit-jspath />' .
        '<input type=hidden value='.variable_get('guifi_wms_service','').' id=guifi-wms />' .
        '</form>';
    $output .= '<div id="topmap" style="margin:5px;text-align:center;font-size:14px"></div>';
    $output .= '<div id="map" style="width: 100%; height: 600px; margin:5px;"></div>';
    $output .= '<div id="bottommap" style="margin:5px;"></div>';
  }

  guifi_log(GUIFILOG_TRACE,'routingmap',1);

  return $output;
}
function guifi_routingmap_search($plat1,$plon1,$plat2,$plon2){

   $result = db_query('SELECT t1.id as nid,t1.lat,t1.lon
            FROM guifi_location as t1
            where t1.status_flag=\'Working\' and t1.lat between (:plat1) and (:plat2) and t1.lon between (:plon1) and (:plon2)', 
            array(':plat1' => $plat1, ':plat2' => $plat2, ':plon1' => $plon1, ':plon2' => $plon2));

	$cnmlid=0;
  while ($record = $result->fetchObject()){
    $cnmlid = $record->nid;
	  break;
	}
	
  $nmax=200;   //max nodes
  $networks = Array(); //array de subxarxes de la zona
  $nodes = Array(); //array sequential id nodes
  $nodesid=Array(); //array node data + repetition control
  $devices=Array(); //array sequential id devices
  $devicesid=Array(); //array devices repetition control
  $subnets=Array(); //array de subxarxes agrupades
  $azones=Array(); //array de zones implicades
  $aznets=Array(); //array de xarxes de les zones implicades
  $alinks=Array(); //Array links
  $nreg = 0;
  $n=0;

  $nodesid["$cnmlid"]="";
  $nodes[]=$cnmlid;
  //Search first device
  $tnodes=0;
  if(isset($nodes[$n])){
    $v=guifi_routingmap_search_firstdevice($nodes[$n]);
    if ($v>0){
      $devices[]=$v;
      $devicesid["$v"]=$nodes[$n];
    }
  }

  //Devices for ospf area
  $tnodes=1;
  $n=0;
  while (isset($devices[$n])){
    $tnodes=$tnodes + guifi_routingmap_search_links($nodes,$nodesid,$devices,$devicesid,$alinks,$devices[$n]);
    if ($tnodes<$nmax){
      $n++;
    } else {
      break;
    }
  }
  ksort($devicesid);
  ksort($nodesid);

  //search device subnets
  $nreg=count($devices);
  for($n=0;$n<$nreg;$n++){
    guifi_routingmap_add_device_networks($networks,$devices[$n],$devicesid["$devices[$n]"]);
  }
  ksort($networks);
   
  //search node data, zone list
  $nreg=count($nodes);
  for($n=0;$n<$nreg;$n++){
    $result = db_query('SELECT t1.nick as nnick, t1.zone_id as zid, t1.lat, t1.lon, t2.nick as znick
               FROM guifi_location as t1
               join guifi_zone as t2 on t1.zone_id = t2.id 
               where t1.id = (:node)', array(':node' => $nodes[$n]));

    if ($record = $result->fetchObject()){
      $nodesid["$nodes[$n]"]=Array("nnick" => $record->nnick,"zid" => $record->zid,"lat" => $record->lat,"lon" => $record->lon);
      if (!isset($azones[$record->zid])){
        $azones[$record->zid]=$record->znick;
      }
    };
  }

  //search zone subnets
  if (count($azones)) foreach ($azones as $key => $azone){
    $result=db_query('SELECT t1.base as netid, t1.mask as mask
               FROM guifi_networks as t1
               where t1.zone = (:zone)', array(':zone' => $key));
    while ($record = $result->fetchObject()){
      $a = _ipcalc($record->netid,$record->mask);
      $splitip=explode(".",$a["netid"]);
      $c=$splitip[0]*pow(256,3)+$splitip[1]*pow(256,2)+$splitip[2]*256+$splitip[3];
      if (!isset($aznets[$c])){
        $aznets[$c]=Array("netid" => $a["netid"],"maskbits" => $a["maskbits"],"broadcast" => $a["broadcast"],"zid" => $key,"swagr" => 0,"znick" => $azone,"intnetid" => ip2long($a["netid"]),"intbroadcast" => ip2long($a["broadcast"]));
      }elseif ($aznets[$c]["maskbits"]>$a["maskbits"]){
        $aznets[$c]=Array("netid" => $a["netid"],"maskbits" => $a["maskbits"],"broadcast" => $a["broadcast"],"zid" => $key,"swagr" => 0,"znick" => $azone,"intnetid" => ip2long($a["netid"]),"intbroadcast" => ip2long($a["broadcast"]));
      }
    }
  }
  ksort($aznets);
  
  //agregate subnets
  $subnets=Array();
  foreach ($networks as $key => $network){
    $subnets[$key]=Array("netid" => $network["netid"],"maskbits" => $network["maskbits"]);
  }
  //$subnets=array_values($networks);
  for($nmaskbits=30;$nmaskbits>16;$nmaskbits--){
      $net1="";
      $knet1=0;
      $nreg=0;
      if (count($subnets)) foreach ($subnets as $key => $subnet){
         if ($subnet["maskbits"]==$nmaskbits){
            $nreg++;
            $a = _ipcalc_by_netbits($subnet["netid"],$nmaskbits-1);
            if ($a["netid"]!=$net1){
               $net1=$a["netid"];
               $knet1=$key;
            }else{
               $subnets[$knet1]["maskbits"]=$nmaskbits-1;
               unset($subnets[$key]);
               $net1="";
               $knet1=0;
            }
         }else{
            $net1="";
            $knet1=0;
         }
      }
   }
   
  //search networks in zone networks
  $aggregate=Array();
  foreach ($networks as $key => $network){
    $a = _ipcalc($network["ipv4"],$network["netmask"]);
    $b = ip2long($a["netid"]);
    $z = ip2long($a["broadcast"]);
    //return json_encode(array($a,$b,$z));
    $sw=0;
    foreach ($aznets as $zkey => $znet){
      if($b>=$znet["intnetid"] && $z<=$znet["intbroadcast"]){
        $sw=1;
        break;
      }
    }
    if($sw==0){
      $aggregate[$key]=Array("netid" => $network["netid"],"maskbits" => $network["maskbits"],"zone" => "");
    }
  }

    //aggregate subnets ultimate
  foreach ($aznets as $key => $znet){
    $aggregate[$key]=Array("netid" => $znet["netid"],"maskbits" => $znet["maskbits"],"zone" => "zone");
  }
  ksort($aggregate);
  
  for($nmaskbits=30;$nmaskbits>16;$nmaskbits--){
      $net1="";
      $knet1=0;
      $nreg=0;
      if (count($aggregate)) foreach ($aggregate as $key => $subnet){
         if ($subnet["maskbits"]==$nmaskbits){
            $nreg++;
            $a = _ipcalc_by_netbits($subnet["netid"],$nmaskbits-1);
            if ($a["netid"]!=$net1){
               $net1=$a["netid"];
               $knet1=$key;
            }else{
               $aggregate[$knet1]["maskbits"]=$nmaskbits-1;
               unset($aggregate[$key]);
               $net1="";
               $knet1=0;
            }
         }else{
            $net1="";
            $knet1=0;
         }
      }
   } 
 
  return json_encode(array($cnmlid,$nodesid,$alinks,$subnets,$aznets,$aggregate));
   //   $networks[$c]=Array("ipv4" => $record->ipv4,"netmask" => $record->netmask,"netid" => $a["netid"],"maskbits" => $a["maskbits"],"nid" => $nid);
}

function guifi_routingmap_search_firstdevice($nid){
  $k=0;
  $result = db_query('SELECT id, device_id FROM guifi_links where nid = (:nid) and routing=\'OSPF\' and (link_type =\'cable\' or link_type =\'wds\')', array(':nid' => $nid));
  if ($record = $result->fetchObject() ){
    $k=$record->device_id;  
  };
  return $k;
}

function guifi_routingmap_search_links(&$nodes,&$nodesid,&$devices,&$devicesid,&$alinks,$deviceid){
  $k=0;
  $resultlinks = db_query('SELECT id FROM guifi_links where device_id = (:did) and routing=\'OSPF\' and (link_type=\'cable\' or link_type=\'wds\') and flag=\'Working\'', array(':did' => $deviceid));
  while ($recordlink = $resultlinks->fetchObject()){
    $result = db_query('SELECT nid, device_id, routing, link_type FROM guifi_links where id = (:lid) and device_id != (:did)', array(':lid' => $recordlink->id, ':did' => $deviceid));
    if ($record = $result->fetchObject()){
      if (!isset($devicesid["$record->device_id"])){
        $devicesid["$record->device_id"]=$record->nid;
        $devices[]=$record->device_id;
      };
      if (!isset($nodesid["$record->nid"])){
        $nodesid["$record->nid"]="";
        $nodes[]=$record->nid;
        $k++;
      };
      if (!isset($alinks["$recordlink->id"])){
        $alinks["$recordlink->id"]=Array("nid1" => $devicesid["$deviceid"],"nid2" => $record->nid);
      }
    };
  };
  return $k;
}

function guifi_routingmap_add_device_networks(&$networks,$deviceid,$nid){
   $v="";
   $result = db_query('SELECT t3.ipv4, t3.netmask
               FROM guifi_interfaces as t2
               join guifi_ipv4 as t3 on t2.id = t3.interface_id
               where t2.device_id = (:did) and t3.ipv4_type=1', array(':did' => $deviceid));
   while ($record = $result->fetchObject()){
      $a = _ipcalc($record->ipv4,$record->netmask);
      $c=ip2long($a["netid"]);
      if (!isset($networks[$c])){
         $networks[$c]=Array("ipv4" => $record->ipv4,"netmask" => $record->netmask,"netid" => $a["netid"],"maskbits" => $a["maskbits"],"nid" => $nid);
      }elseif ($networks[$c]["maskbits"]>$a["maskbits"]){
         $networks[$c]=Array("ipv4" => $record->ipv4,"netmask" => $record->netmask,"netid" => $a["netid"],"maskbits" => $a["maskbits"],"nid" => $nid);
      }
   };
   return 0;
}
 
//******all routingmap  
function guifi_routingmap_all_init(){
  $output = "";
  if (guifi_gmap_key()) {
    drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_gmap_routingmapall.js');
    $output .=  '<form>' .
        '<input type=hidden value='.base_path().drupal_get_path('module','guifi').'/js/'.' id=edit-jspath />' .
        '<input type=hidden value='.variable_get('guifi_wms_service','').' id=guifi-wms />' .
        '</form>';
    $output .= '<div id="topmap" style="margin:5px;text-align:center;font-size:14px"></div>';
    $output .= '<div id="map" style="width: 100%; height: 600px; margin:5px;"></div>';
    $output .= '<div id="bottommap" style="margin:5px;"></div>';
  }

  guifi_log(GUIFILOG_TRACE,'routingmap',1);

  return $output;
} 
function guifi_routingmap_all_search($plat1,$plon1,$plat2,$plon2){

   $result = db_query('SELECT t1.nid as nid,t2.id as lid, t2.link_type, t2.routing, t3.lat, t3.lon
            FROM guifi_location as t3
            inner join guifi_devices as t1 on t1.nid = t3.id
            left join guifi_links as t2 on t1.id = t2.device_id
            where (t2.routing=\'OSPF\' or t2.routing=\'BGP\') and (t2.link_type=\'cable\' or t2.link_type=\'wds\') and t2.flag=\'Working\'
            and t3.lat between (:plat1) and (:plat2) and t3.lon between (:plon1) and (:plon2)
            order by t1.nid', array(':plat1' => $plat1, ':plat2' => $plat2, ':plon1' => $plon1, ':plon2' => $plon2));
	
  $nmax=600;   //max nodes
  $nodes=Array(); //array node data + repetition control
  $links=Array(); //Array links
  $n=0;
  $vospf=0;
  $vbgp=0;

  while ($record = $result->fetchObject()){
    if($record->routing=="OSPF") $vospf=1; else $vospf=0;
    if($record->routing=="BGP") $vbgp=1; else $vbgp=0;
    if(!isset($nodes[$record->nid])){
      $n++;
      if($n>$nmax) break;
      $nodes["$record->nid"]=array("lat" => $record->lat,"lon" => $record->lon,"ospf" => $vospf,"bgp" => $vbgp);
    }else{
      $nodes["$record->nid"]["ospf"]=$nodes["$record->nid"]["ospf"]+$vospf;
      $nodes["$record->nid"]["bgp"]=$nodes["$record->nid"]["bgp"]+$vbgp;
    }
    if(!isset($links[$record->lid])){
      $links["$record->lid"]=array("n1" => $record->nid,"n2" => 0,"routing" => $record->routing,"type" => $record->link_type);
    }else{
      $links["$record->lid"]["n2"]=$record->nid;
    }
  }
  $narea=0;
  foreach ($nodes as $nkey => $node){
    $sw=0;
    if($node["ospf"]>0){
      if(!isset($node["area"])){
        $aret=guifi_routingmap_all_search_ospfarea($nkey);
        foreach($aret[0] as $nakey => $nanode){
          if(isset($nodes[$nakey]) and !isset($nodes[$nakey]["area"])){
              $nodes[$nakey]["area"]=$narea;
              $sw=1;
          }
        }
        foreach($aret[1] as $nakey => $nalink){
          if(isset($links[$nakey]) and !isset($links[$nakey]["area"])){
            $links[$nakey]["area"]=$narea;
            $sw=1;
          }
        }
        if($sw==1){
          $narea++;
        }
      }
    }
  }
  return json_encode(array(Array($n,$narea),$nodes,$links));
}

function guifi_routingmap_all_search_ospfarea($pnode){
	
  $nmax=200;   //max nodes
  $nodesid=Array(); //array node data + repetition control
  $devices=Array(); //array sequential id devices
  $devicesid=Array(); //array devices repetition control
  $alinks=Array(); //Array links
  $nreg = 0;
  $n=0;

  $nodesid["$pnode"]=$pnode;
  //Search first device
  $tnodes=0;
  $v=guifi_routingmap_all_search_firstdevice($pnode);
  if ($v>0){
    $devices[]=$v;
    $devicesid["$v"]=$pnode;
  }

  //Devices for ospf area
  $tnodes=1;
  $n=0;
  while (isset($devices[$n])){
    $tnodes=$tnodes + guifi_routingmap_all_search_links($nodesid,$devices,$devicesid,$alinks,$devices[$n]);
    if ($tnodes<$nmax){
      $n++;
    } else {
      break;
    }
  }
  return(Array($nodesid,$alinks));
}

function guifi_routingmap_all_search_firstdevice($nid){
  $k=0;
  $result = db_query('SELECT id, device_id FROM guifi_links where nid = (:nid) and routing=\'OSPF\' and (link_type=\'cable\' or link_type=\'wds\')', array(':nid' => $nid));
  if ($record = $result->fetchObject()){
    $k=$record->device_id;  
  };
  return $k;
}

function guifi_routingmap_all_search_links(&$nodesid,&$devices,&$devicesid,&$alinks,$deviceid){
  $k=0;
  $resultlinks = db_query('SELECT id FROM guifi_links where device_id = (:did) and routing=\'OSPF\' and (link_type=\'cable\' or link_type=\'wds\') and flag=\'Working\'', array(':did' => $deviceid));
  while ($recordlink = $resultlinks->fetchObject()){
    $result = db_query('SELECT nid, device_id, routing, link_type FROM guifi_links where id = (:lid) and device_id != (:did)', array(':lid' => $recordlink->id, ':did' => $deviceid));
    if ($record = $result->fetchObject()){
      if (!isset($devicesid["$record->device_id"])){
        $devicesid["$record->device_id"]=$record->nid;
        $devices[]=$record->device_id;
      };
      if (!isset($nodesid["$record->nid"])){
        $nodesid["$record->nid"]=$record->nid;
        $k++;
      };
      if (!isset($alinks["$recordlink->id"])){
        $alinks["$recordlink->id"]=$recordlink->id;
      }
    };
  };
  return $k;
}
?>
