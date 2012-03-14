<?php

// carreguem el Twig
include_once('contrib/twig_1.6/lib/Twig/Autoloader.php');


function array_flatten(array $array, array $return = array(), $prefix=null, $parentName=null, $nivell=0) {

  $counter = 1;
  foreach ($array as $k => $item) {

    if (is_array($item)){

      if ((strcmp($parentName, 'interfaces')==0)||(strcmp($parentName, 'links')==0)||(strcmp($parentName, 'radios')==0)) {

        $name = $counter;
      } else
        $name = $k;
      
      if ($prefix) $name = $prefix.'_'.$name;
      
      $return = array_flatten($item, $return, $name, $k, $nivell++);
    }elseif ($item) {
      if ((strcmp($parentName, 'interfaces')==0)||(strcmp($parentName, 'links')==0)||(strcmp($parentName, 'radios')==0)) {
        $name = $counter;
      } else
        $name = $k;
      
      if ($prefix)$name = $prefix.'_'.$name;
      $return[$name] = $item;
    }
    $counter++;
  }
  return $return;
}

// Generador dels unsolclic
function guifi_unsolclic($dev, $format = 'html') {
  global $rc_startup;
  global $ospf_zone;
  global $otype;
  
  $paramPrefixes = array("zone", "node", "user", "device", "firmware", "radio", "interface", "ipv4", "link", "linkedto_");

  $otype = $format;

  $dev = (object)$dev;
  
  $flattenDev = array_flatten((array)$dev,array());
  
  if (isValidConfiguracioUSC($dev->usc_id)) {

    // FINAL. Treure el fitxer unsolclic resultant com a mime text/plain
    //drupal_set_header('Content-Type: text/plain; charset=utf-8');

    // PFC passos
    // 1. Recuperar informacio del trasto
    // 1.a Recuperar el id de model del trasto (del camp extra de device)
    $modelId = $dev->variable['model_id'];
    // recollir la configuracio unscolclic actual
    $uscId = $dev->usc_id;

    // 1.b recollir de la BD la informacio del model
    $model = guifi_get_model($modelId);

    // 1.c recollir les característiques del model

    // aixo no es fa servir per res!!!!
    $caractModel = guifi_get_caractmodel($modelId);

    // 2. Recuperar informacio del firmware
    // 2.a Recuperar el id del firmware del trasto(del camp extra de device)
    $firmwareName = $dev->variable['firmware'];
    $firmwareId = $dev->fid;

    // 2.b recollir de la BD la informacio del firmware
    $firmware = guifi_get_firmware($firmwareName);

    // 2.c recollir els parametres del firmware
    // tampoc es fa servir per RES!!!!!!
    $paramsFirmware = guifi_get_paramsFirmware($firmwareId);

    // 3. Recuperar la configuracióUnSolClic tq modelid i firmware:id
    $configuracioUSC = guifi_get_configuracioUSC($modelId,$firmwareId,$uscId);

    // 3.a recuperar la plantilla de la configuracio
    $plantilla = $configuracioUSC['plantilla'];    // a plantilla hi ha el contingut de la plantilla del unsolclic

    // 4. recuperar TOTS els parametres variables associats al trasto
    //$paramsDevice = guifi_get_paramsDevice($dev->id);
    $paramsDevice = guifi_get_paramsClientDevice($dev->id);

    // 5. Indexar els els parametres variables associats al trasto
    $indexedParamsDevice = guifi_indexa_paramsDevice($paramsDevice, $paramPrefixes);

    // 6. recuperar els parametres de la plantilla
    $paramsconfiguracioUSC = guifi_get_paramsconfiguracioUSC($uscId);
    
    // 6.B. recuperar els la informacio de la configuracio de fabricant-model-firmware
    $paramsMMF = guifi_get_paramsMMF($dev->id);
    
    $totalParameters = array_merge($indexedParamsDevice, $paramsMMF, $flattenDev);
    
    // altres parametres fixes; TODO posar-lo com a parametre fixe de la plantilla
      $totalParameters['ospf_name'] ='backbone';
      // proves de twig
      $zone = guifi_zone_load($totalParameters['zone_id']);
      list($primary_dns,$secondary_dns) = explode(' ',guifi_get_dns($zone,2));
      $totalParameters['zone_primary_dns'] = $primary_dns;
      $totalParameters['zone_secondary_dns'] = $secondary_dns;
      
      list($primary_ntp,$secondary_ntp) = explode(' ',guifi_get_ntp($zone));
      $totalParameters['zone_primary_ntp'] = $primary_ntp;
      $totalParameters['zone_secondary_ntp'] = $secondary_ntp;
    
    if ($paramsconfiguracioUSC) {

      // 7. substituir els parametres a la plantilla
      foreach ($paramsconfiguracioUSC as $tupla) {
          $param = $tupla['nom'];
          $valor = $tupla['valor'];
          $dinamic = $tupla['dinamic'];
          $origen = $tupla['origen'];

          if($dinamic==true) {
            // DINAMIC s'ha de fer una segona passatda per buscar el origen de veritat
            $valor = $totalParameters[$origen];
          }
          //echo "\nparam=$param = $valor $origen";
          $twigVars[$param] = $valor;
      }

      Twig_Autoloader::register();
      
      $loader = new Twig_Loader_String();
      //$loader = new Twig_Loader_Filesystem('/home/albert/workspace/guifinet/drupal-6.22/sites/all/modules/guifi/firmware');
      $twig = new Twig_Environment($loader);
      
      $totalParameters['dev'] = $dev;
//       var_dump($totalParameters);die;
//       $twigVars['dev'] = $dev;
//       $twigVars['all'] = $totalParameters;

      $twig->addFunction('ip2long', new Twig_Function_Function('ip2long'));
      $twig->addFunction('long2ip', new Twig_Function_Function('long2ip'));
      $twig->addFunction('t', new Twig_Function_Function('t'));
      
       //$plantilla  = $twig->render($configuracioUSC['template_file'], $twigVars);
       $plantilla  = $twig->render($plantilla, $totalParameters);
//
    }
    $plantilla = str_replace("\n", "\n<br />", $plantilla);
    echo $plantilla;
    die;
  }
  
  if ($dev->variable['firmware'] == 'n/a') {
	_outln_comment(t("ERROR: I do need a firmware selected at the radio web interface: ").'<a href=/guifi/device/'.$dev->id.'/edit>http://guifi.net/guifi/device/'.$dev->id.'/edit');
        return;
  } else {
	_outln_comment(t("Generated for:"));
	_outln_comment($dev->variable['firmware']);
  }


  foreach (glob(drupal_get_path('module', 'guifi') .'/firmware/*.inc.php', GLOB_BRACE) as $firm_inc_php){
    include_once("$firm_inc_php");
   // echo "<br>$firm_inc_php";
  }
 if ($dev->radios[0]['mode'] == 'client') {
    $links = 0;
     foreach ($dev->radios[0]['interfaces'] as $interface_id => $interface)
     foreach ($interface['ipv4'] as $ipv4_id => $ipv4)
     if (isset($ipv4['links'])) foreach ($ipv4['links'] as $key => $link) {
       if ($link['link_type'] == 'ap/client') {
        $links++;
        break;
      }
    }

    if ($links == 0) {
	_outln_comment(t("ERROR: Radio is in client mode but has no AP selected, please add a link to the AP at: ").'<a href='.base_path().'guifi/device/'.$dev->id.'/edit>http://guifi.net/guifi/device/'.$dev->id.'/edit');
        return;
    }
  }

  switch ($dev->variable['firmware']) {
    case 'RouterOSv2.9':
    case 'RouterOSv3.x':
    case 'RouterOSv4.0+':
    case 'RouterOSv4.7+':
    case 'RouterOSv5.x':
      unsolclic_routeros($dev);
      exit;
      break;
    case 'DD-guifi':
    case 'DD-WRTv23':
    case 'Alchemy':
    case 'Talisman':
      unsolclic_wrt($dev);
      exit;
      break;
    case 'AirOsv221':
    case 'AirOsv30':
    case 'AirOsv3.6+':
      unsolclic_airos($dev);
      exit;
      break;
//    case 'AirOsv52':
//    unsolclic_airos52($dev);
//      exit;
//      break;
    case 'GuifiStationOS1.0':
      unsolclic_guifistationos($dev);
      exit;
      break;
  }

  $unsolclic='unsolclic_'.$dev->variable['firmware'];
   
  if(function_exists(${unsolclic})){

     ${unsolclic}($dev);
    exit;
  }
  else
    unsolclic_todo($dev);

}

function _out_file($txt,$file) {
  global $otype;

  if ($otype == 'html')
    print '<pre>echo "'.$txt.'" > '.$file.'</pre>';
  else
    print 'echo "'.$txt.'" > '.$file;
}

function _outln($string = '') {
  global $otype;

  print $string;
  if ($otype == 'html') print "\n<br />"; else print "\n";
}

function _outln_comment($string = '') {
  global $otype;

  print "# ".$string;
  if ($otype == 'html') print "\n<br />"; else print "\n";
}
function _outln_comment_get($string = '') {
  global $otype;

  $output = "# $string \n";
  if ($otype == 'html') $output .= "<br />";
  return $output;
}


function _outln_nvram($parameter, $value) {
  global $otype;

  print "nvram set ".$parameter.'="';
 
  if (strlen($value) <= 80) {
    print $value;
  } else {
    $pos = 0;
    if ($otype == 'html') print "\n<br />"; else print "\n";
    do {
      print substr($value, $pos * 80, 80).'\\';
      $pos ++;
      if ($otype == 'html') print "\n<br />"; else print "\n";
    } while (strlen(substr($value,($pos-1) * 80)) > 80);
  }
  print('"');
  if ($otype == 'html') print "\n<br />"; else print "\n";
}

function _out_nvram($parameter,$value = NULL) {
  global $otype;
  print "nvram set ".$parameter.'="';
  if (!empty($value))
    print $value;
  if ($otype == 'html') print "\n<br />"; else print "\n";
}

function _out($value = '', $end = '') {
  global $otype;
  print "    ".$value.$end;
  if ($otype == 'html') print "\n<br />"; else print "\n";
}

function guifi_unsolclic_if($id, $itype) {
  return db_fetch_object(db_query("SELECT i.id, a.ipv4, a.netmask FROM {guifi_interfaces} i LEFT JOIN {guifi_ipv4} a ON i.id=a.interface_id AND a.id=0 WHERE device_id = %d AND interface_type = '%s' LIMIT 1",$id,$itype));
}

function guifi_get_dns($zone,$max = 3) {

  $dns = array();
  if (!empty($zone->dns_servers))
    $dns = explode(",",$zone->dns_servers);
  while (count($dns) < $max) {
    $zone = db_fetch_object(db_query("SELECT dns_servers, master FROM {guifi_zone} WHERE id=%d",$zone->master));
    if (!empty($zone->dns_servers))
      $dns = array_merge($dns,explode(",",$zone->dns_servers));
    if ($zone->master == 0) {
      break;
    }
  }
  while (count($dns) > $max)
    array_pop($dns);

  return implode(" ",$dns);
}

function guifi_get_ospf_zone($zone) {

  $ospf = array();
  if (!empty($zone->ospf_zone))
    return $zone->ospf_zone;
  do {
    $zone = db_fetch_object(db_query("SELECT dns_servers, master FROM {guifi_zone} WHERE id=%d",$zone->master));
    if (!empty($zone->ospf_zone))
      return $zone->ospf_zone;
  } while ($zone->master > 0);

  return '0';
}

function guifi_get_ntp($zone,$max = 3) {
  $ntp = array();
  if (!empty($zone->ntp_servers))
    $ntp = explode(",",$zone->ntp_servers);
  while (count($ntp) < $max) {
    $zone = db_fetch_object(db_query("SELECT ntp_servers, master FROM {guifi_zone} WHERE id=%d",$zone->master));
    if (!empty($zone->ntp_servers))
      $ntp = array_merge($ntp,explode(",",$zone->ntp_servers));
    if ($zone->master == 0) {
      break;
    }
  }
  while (count($ntp) > $max)
    array_pop($ntp);

  return implode(" ",$ntp);
}

function guifi_get_model($mid) {

    $modelInfo = db_fetch_array(db_query("select * from {guifi_model} where mid=%d limit 1",$mid));
    if (!empty($modelInfo)){
      //var_dump($modelInfo);
    }
  return $modelInfo;
}

function guifi_get_caractmodel($mid) {

  $caractModelInfo = db_fetch_array(db_query("select * from {guifi_pfc_caracteristiquesModel} where mid=%d limit 1",$mid));
  if (!empty($caractModelInfo)){
    //var_dump(caractModelInfo);
  }
  return $caractModelInfo;
}

function guifi_get_firmware($nom) {

  $firmwareInfo = db_fetch_array(db_query("select * from {guifi_pfc_firmware} where nom='%s'",$nom));
  if (!empty($firmwareInfo)){
    //var_dump($firmwareInfo);
  }
  return $firmwareInfo;
}

function guifi_get_paramsFirmware($fid) {

  $paramsFirmwareInfo = db_fetch_array(db_query("select * from {guifi_pfc_parametresFirmware} where fid='%d'",$fid));
  if (!empty($paramsFirmwareInfo)){
    //var_dump($paramsFirmwareInfo);
  }
  return $paramsFirmwareInfo;
}

function guifi_get_configuracioUSC($mid, $fid, $uscid) {

  //var_dump("Call guifi_get_configuracioUSC($mid, $fid, $uscid)");
  $configuracioUSCInfo = db_fetch_array(db_query("select id, mid, fid, enabled, tipologia, plantilla, template_file from {guifi_pfc_configuracioUnSolclic} where mid=%d and fid=%d and id = %d limit 1",$mid, $fid, $uscid));
  if (!empty($configuracioUSCInfo)){
  } else var_dump("NO m'arriba cap plantilla  per a Model:$mid Firmware:$fid USCid:$uscid !!!!");
  return $configuracioUSCInfo;
}
function guifi_get_paramsconfiguracioUSC($uscid) {
  $qry = db_query("select p.nom, c.valor, c.dinamic, p.origen
                   from {guifi_pfc_parametresConfiguracioUnsolclic} c,
                        {guifi_pfc_parametres} p
                   where c.pid = p.id and c.uscid= %d",$uscid);
  while ($param = db_fetch_array($qry)) {
    $params[] = $param;
  }
  //w($params);
  return $params;
}

function guifi_get_paramsMMF($devId) {
  $qry = db_query(" select
                        d.usc_id,
                        usc.tipologia, usc.enabled  ,
                        u.name usc_creator_nick,
                        m.model model_name,
                        mf.name as manufacturer_name,
                        f.nom as firmware_name,
                        f.descripcio as firmware_description
                    from
                        guifi_devices d
                        JOIN guifi_pfc_configuracioUnSolclic usc on usc.id = d.usc_id
                        JOIN users u ON u.uid = usc.user_created
                        JOIN guifi_model m on m.mid = usc.mid
                        JOIN guifi_manufacturer mf on mf.fid = m.fid
                        JOIN guifi_pfc_firmware f on f.id = usc.fid
                    
                    where d.id = %d",$devId);
  $param = db_fetch_array($qry);
  return $param;
}

// DEPRECATED!!!
function guifi_get_paramsDevice($device_id) {
  $qry = db_query("select
    z.id zone_id, z.title zone_name, z.dns_servers zone_dns_servers, z.ntp_servers zone_ntp_servers, z.graph_server zone_graph_server, z.ospf_zone zone_ospf_zone,  z.zone_mode zone_zone_mode, z.proxy_id zone_proxy_id, z.voip_id zone_voip_id,
    n.nid node_id, n.title node_name, loc.lat node_lat, loc.lon node_lon, loc.graph_server node_node_graph_server,
    u.uid user_id, u.name user_name, u.mail user_mail,
    d.nick as device_name, d.type device_type,
    r.radiodev_counter radio_order, r.ssid radio_ssid, r.mode radio_mode, r.protocol radio_protocol, r.channel radio_channel, r.antenna_gain radio_antenna_gain, r.antenna_angle radio_antenna_angle, r.antenna_azimuth radio_antenna_azimuth, r.clients_accepted radio_clients_accepted, r.antenna_mode radio_antenna_mode, r.ly_mb_in radio_ly_mb_in ,r.ly_mb_out radio_ly_mb_out,
    i.id interface_id, i.interface_type, i.mac interface_mac,
    ip.ipv4 ipv4_ip, ip.netmask ipv4_netmask, ip.ipv4_type,
    l.link_type, l.routing link_routing,l.flag link_working
    from
    node n

    JOIN guifi_devices d ON d.nid = n.nid
    JOIN guifi_location loc ON loc.id = n.nid
    JOIN guifi_zone z ON z.id = loc.zone_id
    JOIN users u ON u.uid = n.uid
    JOIN guifi_radios r ON r.id = d.id
    JOIN guifi_interfaces i ON i.device_id = d.id
    JOIN guifi_ipv4 ip ON ip.interface_id = i.id
    JOIN guifi_links l ON l.interface_id = i.id
    where
    d.id = %d
    order by radio_order asc, interface_type asc",$device_id);

  while ($param = db_fetch_array($qry)) {
    $params[] = $param;
  }
  //var_dump($params);
  return $params;
}


function guifi_get_paramsClientDevice($device_id) {
  $qry = db_query("
          SELECT
              z.id zone_id, z.title zone_name, z.dns_servers zone_dns_servers, z.ntp_servers zone_ntp_servers, z.graph_server zone_graph_server, z.ospf_zone zone_ospf_zone,  z.zone_mode zone_zone_mode, z.proxy_id zone_proxy_id, z.voip_id zone_voip_id,
              n.nid node_id, n.title node_name, loc.lat node_lat, loc.lon node_lon, loc.graph_server node_node_graph_server,
              u.uid user_id, u.name user_name, u.mail user_mail,
              d.nick as device_name, d.type device_type, d.id as device_id,
              r.radiodev_counter radio_order, r.ssid radio_ssid, r.mode radio_mode, r.protocol radio_protocol, r.channel radio_channel, r.antenna_gain radio_antenna_gain, r.antenna_angle radio_antenna_angle, r.antenna_azimuth radio_antenna_azimuth, r.clients_accepted radio_clients_accepted, r.antenna_mode radio_antenna_mode, r.ly_mb_in radio_ly_mb_in ,r.ly_mb_out radio_ly_mb_out,
              i.id interface_id, i.interface_type, i.mac interface_mac, i.radiodev_counter interface_radiodev_counter,
              ip.ipv4 ipv4_ip, ip.netmask ipv4_netmask, ip.ipv4_type ,
              l.link_type, l.routing link_routing,l.flag link_working,
              r2.ssid linkedto_ssid, r2.protocol linkedto_protocol, r2.channel linkedto_channel, r2.clients_accepted linkedto_clients_accepted,
              ip2.ipv4 as linkedto_gateway, ip2.netmask linkedto_netmask
          FROM
              node n
          
          JOIN guifi_devices d ON d.nid = n.nid
          JOIN guifi_location loc ON loc.id = n.nid
          JOIN guifi_zone z ON z.id = loc.zone_id
          JOIN users u ON u.uid = n.uid
          JOIN guifi_radios r ON r.id = d.id
          JOIN guifi_interfaces i ON i.device_id = d.id
          JOIN guifi_ipv4 ip ON ip.interface_id = i.id
          JOIN guifi_links l ON l.interface_id = i.id
          JOIN guifi_links l2 ON l.id = l2.id and l2.device_id != d.id
          JOIN guifi_interfaces i2 on i2.id = l2.interface_id
          JOIN guifi_radios r2 on r2.id = i2.device_id and r2.radiodev_counter = i2.radiodev_counter
          JOIN guifi_ipv4 ip2 ON ip2.interface_id = i2.id
          
          WHERE
            d.id = %d
          order by radio_order asc, interface_type asc",$device_id);

  while ($param = db_fetch_array($qry)) {
    $params[] = $param;
  }
  //var_dump($params);die;
  return $params;
}


function guifi_indexa_paramsDevice($arrayParametres, $paramPrefixes) {
  
  $index = 1;
  foreach ($arrayParametres as $registre) {
    //var_dump($registre);
    //echo "<hr>";
    
    foreach ($registre as $clau=>$valor) {
      // comprovar existencia del prefix a la clau
      if (guifi_usc_comprova_clau_prefix($clau, $paramPrefixes)){
        // comprovar que no sigui el mateix calor que el que hem guardat pel registre anterior
        $previ = $index-1;
        $inserir = true;
        if (!array_key_exists($clau.$index, $resultat)){
          // comprovem valor anterior
          /*
          if (isset($resultat[$clau.$previ])) {
            echo "<br>Existeix l'anterior resultat[$clau$previ]";
            if ($resultat[$clau.$previ]==$valor) {
              echo " i son iguals! no inserim ";
              $inserir = false;
            }
            else {
              echo " i son DIFERENTS!";
              $inserir = true;
            }
          }*/
        }
        //if ($inserir) {
          //echo "<br>afegim resultat[$clau$index]";
          
        // indexant
        //$resultat[$clau.".".$index] = $valor;
        
        // sense indexar
        $resultat[$clau] = $valor;
        
        
        
        //}
      }
    }
    $index++;
  }
  
  return $resultat;
}

function guifi_usc_comprova_clau_prefix($clau, $paramPrefixes) {
  foreach ($paramPrefixes as $prefix) {
    //echo "<br>buscant a ". $clau . " el ".$prefix;
    if (stripos($clau, $prefix)!==false) return true;
  }
  return false;
}

function isValidConfiguracioUSC($uscid){
  $result = db_fetch_object(db_query("SELECT id, enabled FROM {guifi_pfc_configuracioUnSolclic} WHERE  id = %d AND enabled = 1 LIMIT 1",$uscid));
  if (!empty($result->enabled)) return true;
  
  return false;
}
    

?>