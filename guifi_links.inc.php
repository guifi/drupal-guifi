<?php
/**
 * @file guifi_links.inc.php
 */

function guifi_links_form($link,$ipv4,$tree,$multilink) {
  $lweight = 0;

  // edit link details
  guifi_log(GUIFILOG_TRACE,'guifi_links_form()',$link);

  $ki = $tree[count($tree)-3];
  $ka = $tree[count($tree)-1];

  if (count($tree)>4)
    $rk = $tree[1];
  else
    $rk = NULL;

  // creating hidden form elements for non-edited fields
  if ($link['new'])
    $link['id']= -1;

  // link hidden vars
  $f['storage'] = guifi_form_hidden_var(
    $link,
    array('id','nid','device_id','interface_id','link_type'),
    array_merge($tree,array('links',$link['id']))
  );

  // remote interface hidden vars
  $f['interface'] = guifi_form_hidden_var(
    $link['interface'],
//  interface_type is no longer needed, apparently
//  array('id','interface_type','radiodev_counter'),
    array('id','radiodev_counter'),
    array_merge($tree,array('links',$link['id'],'interface'))
  );


  $f['remote_ipv4'] = guifi_form_hidden_var(
    $link['interface']['ipv4'],
    array('id','interface_id','netmask'),
    array_merge($tree,array('links',$link['id'],'interface','ipv4'))
  );

       // linked node-device
  if ($link['type'] != 'cable')
    $descr =  guifi_get_ap_ssid($link['device_id'],$link['radiodev_counter']);
  else
    $descr = guifi_get_interface_descr($link['interface_id']);


  $f['l'] = array(
    '#type' => 'fieldset',
    '#title'=>  guifi_get_nodename($link['nid']).'/'.
      guifi_get_hostname($link['device_id']),
    '#collapsible' => TRUE,
    '#collapsed' => !isset($link['unfold']),
  );
  if ($link['deleted'])
    $f['l']['#description'] = guifi_device_item_delete_msg('<b>Link deleted</b>.');

  $f['l']['beginTable'] = array('#value' => '<table style="width: 0">');

  if (user_access('administer guifi networks')) {
    if (!$multilink)
    $f['l']['ipv4'] = array(
      '#type'=> 'textfield',
      '#parents' => array_merge($tree,array('ipv4')),
      '#size'=> 16,
      '#maxlength' => 16,
      '#default_value' => $ipv4['ipv4'],
      '#title' => t('Local IPv4'),
      '#element_validate' => array('guifi_validate_ip'),
      '#prefix'=> '<td>',
      '#suffix'=> '</td>',
    );
    $f['l']['ipv4_remote'] = array(
      '#type'=> 'textfield',
      '#parents' => array_merge(
        $tree,array('links',$link['id'],'interface','ipv4','ipv4')),
      '#size'=> 16,
      '#maxlength' => 16,
      '#default_value' => $link['interface']['ipv4']['ipv4'],
      '#title' => t('Remote IPv4'),
      '#element_validate' => array(
        'guifi_validate_ip',
        'guifi_links_validate_subnet'),
      '#prefix'=> '<td>',
      '#suffix'=> '</td>',
    );
    if (!$multilink)
      $f['l']['netmask'] = array(
        '#type' => 'select',
        '#parents' => array_merge($tree,array('netmask')),
        '#title' => t("Network mask"),
        '#default_value' => $ipv4['netmask'],
        '#options' => guifi_types('netmask',30,0),
        '#prefix'=> '<td>',
        '#suffix'=> '</td>',
      );
   } else {
     if (!$multilink) {
       $f['l']['ipv4'] = array(
         '#type' => 'value',
         '#parents' => array_merge($tree,array('ipv4')),
         '#value' => $ipv4['ipv4']);
       $f['l']['netmask'] = array(
         '#type' => 'value',
         '#parents' => array_merge($tree,array('netmask')),
         '#value' => $ipv4['netmask']);
     }

    $f['l']['ipv4_remote'] = array(
      '#type' => 'value',
      '#parents' => array_merge(
        $tree,array('links',$link['id'],'interface','ipv4','ipv4')),
      '#value' => $link['interface']['ipv4']['ipv4']);

    $f['l']['ipv4_remote_display'] = array(
      '#type' =>         'item',
      '#parents'=>       array_merge(
         $tree,array('links',$link['id'],'interface','ipv4','ipv4')),
      '#title'=>         t('Remote IPv4'),
      '#value'=>         $link['interface']['ipv4']['ipv4'],
      '#description' =>  $link['interface']['ipv4']['netmask'],
      '#prefix'=>        '<td>',
      '#suffix'=>        '</td>',
    );
  } // if network administrator
  $f['l']['overlap'] = array(
     '#type' =>          'hidden',
     '#parents' => array_merge($tree,array('overlap')),
      '#value'=>         $ipv4['netmask'],
     '#element_validate' => array('guifi_links_check_overlap'),
  );
  // Routing
  $f['l']['routing'] = array(
    '#type' =>          'select',
    '#parents'=>        array_merge($tree,array('links',$link['id'],'routing')),
    '#title' =>         t("Routing"),
    '#default_value' => $link['routing'],
    '#options' =>       guifi_types('routing'),
    '#prefix'=>         '<td>',
    '#suffix'=>         '</td>',
  );
  // Status
  $f['l']['status'] = array(
    '#type' =>          'select',
    '#parents'=>        array_merge($tree,array('links',$link['id'],'flag')),
    '#title' =>         t("Status"),
    '#default_value' => $link['flag'],
    '#options' =>       guifi_types('status'),
    '#prefix'=>         '<td>',
    '#suffix'=>         '</td>',
  );

  // Remote cable interface dropdown list
  if ($link['link_type']=='cable') {
    $f['l']['remote_interface_type'] = array(
      '#type'           =>  'select',
      '#parents'        =>  array_merge($tree,array('links',$link['id'],'interface','id')),
      '#title'          =>  t("Remote interface"),
      '#default_value'  =>  $link['interface']['id'],
      '#options'        =>  guifi_get_device_interfaces($link['device_id']),
      '#prefix'         =>  '<td>',
      '#suffix'         =>  '</td>',
    );
  }

  // delete link button
  if ($link['deleted'])
    $f['deleted_link'] = array(
      '#type'=> 'hidden',
      '#parents'=> array_merge($tree,array('deleted_link')),
      '#value'=> TRUE,
    );
  else
    $f['l']['delete_link'] = array(
      '#type' => 'image_button',
      '#src' => drupal_get_path('module', 'guifi').'/icons/drop.png',
      '#parents' => array_merge($tree,array(
        'delete_link',
        $link['id'],
        $link['nid'],
        $link['device_id']
      )),
      '#attributes' => array(
        'title' => t('Delete link with').': '.
            guifi_get_interface_descr($link['interface_id'])
        ),
      '#executes_submit_callback' => TRUE,
      '#submit' => array('guifi_links_delete_submit'),
      '#prefix'=> '<td>',
     );
  $f['l']['endTable'] = array(
    '#value'=> '</td></tr></table>'
  );

  return $f;
}

function guifi_links_delete_submit(&$form,&$form_state) {
  $values = $form_state['clicked_button']['#parents'];

  $remote_did = array_pop($values);
  $remote_nid = array_pop($values);
  $link_id = array_pop($values);
  $dummy =  array_pop($values);
  $ipv4_id = array_pop($values);
  $dummy =  array_pop($values);
  $interface_id = array_pop($values);
  $dummy =  array_pop($values);

  if ($values['0'] == 'radios') {
    $radio_id = array_pop($values);
    $fbase = &$form_state['values']['radios'][$radio_id];
    $fbase['unfold'] = TRUE;
  } else
    $fbase = &$form_state['values'];

  guifi_log(GUIFILOG_TRACE,
    sprintf('function guifi_radio_interface_link_delete_submit(radio: %d-%s, interface: %d, ipv4: %d, lid: %d, rnid: %d rdid: %d)',
      $radio_id,
      $form_state['values']['radios'][$radio_id]['mode'],
      $interface_id,
      $ipv4_id,$link_id,$remote_nid,$remote_did),
    $values);

  $fbase['interfaces'][$interface_id]['unfold'] = TRUE;
  $fipv4 = &$fbase['interfaces'][$interface_id]['ipv4'][$ipv4_id];
  $fipv4['unfold'] = TRUE;

  $flink = &$fipv4['links'][$link_id];
  $flink['unfold'] = TRUE;
  $flink['deleted'] = TRUE;

  $flink['ipv4']['unfold'] = TRUE;

  // if P2P link or AP/Client link and radio is the client
  // delete also the local IP
  if (( $flink['ipv4']['netmask'] == '255.255.255.252' ) or  ($ipv4['netmask'] == '255.255.255.248') or  ($ipv4['netmask'] == '255.255.255.240') or
      ( $form_state['values']['radios'][$radio_id]['mode'] == 'client' )) {
    $fipv4['deleted'] = TRUE;
  }

  $form_state['rebuild'] = TRUE;

  drupal_set_message(t('%type link with %node/%device deleted.',
    array(
      '%type' => $fbase['interfaces'][$interface_id]['interface_type'],
      '%node' =>   guifi_get_nodename($remote_nid),
      '%device' => guifi_get_hostname($remote_did)
    )
  ));

  return TRUE;
}

function guifi_links_validate_subnet($remoteIp,&$form_state) {
  if ($form_state['clicked_button']['#value'] == t('Reset'))
    return;

  $keys         = count($remoteIp['#parents']);
  $radio_id     = $remoteIp['#parents'][$keys - 10];
  $interface_id = $remoteIp['#parents'][$keys - 8];
  $ipv4_id      = $remoteIp['#parents'][$keys - 6];
  $link_id      = $remoteIp['#parents'][$keys - 4];

  if ($keys == 11)
    $ipv4 = &$form_state['values']['radios'][$radio_id]
                                  ['interfaces'][$interface_id]
                                  ['ipv4'][$ipv4_id];
  else
    $ipv4 = &$form_state['values']['interfaces'][$interface_id]
                                  ['ipv4'][$ipv4_id];

  if ($ipv4['links'][$link_id]['deleted'])
    return;

  $item1 = _ipcalc($ipv4['ipv4'],$ipv4['netmask']);
  $item2 = _ipcalc($remoteIp['#value'],$ipv4['netmask']);
  if (($item1[netstart] != $item2[netstart]) or ($item1[netend] != $item2[netend])) {
    form_error($remoteIp,
      t('Error in linked ipv4 addresses (%addr1/%mask - %addr2), not at same subnet.',
          array(
            '%addr1' => $ipv4['ipv4'],
            '%addr2' => $remoteIp['#value'],
            '%mask' => $ipv4['netmask']
          )
        ),
        'error');
  }


  return;
}

function guifi_links_check_overlap($overlap,&$form_state) {

  if ($form_state['clicked_button']['#value'] == t('Reset'))
    return;

  $keys         = count($overlap['#parents']);
  $radio_id     = $overlap['#parents'][$keys - 6];
  $interface_id = $overlap['#parents'][$keys - 4];
  $ipv4_id      = $overlap['#parents'][$keys - 2];
  if ($keys == 7)
    $ipv4 = &$form_state['values']['radios'][$radio_id]
                                  ['interfaces'][$interface_id]
                                  ['ipv4'][$ipv4_id];
  else
    $ipv4 = &$form_state['values']['interfaces'][$interface_id]
                                  ['ipv4'][$ipv4_id];

  if ($ipv4['links'][$link_id]['deleted'])
    return;

  if ( ip2long($ipv4['netmask']) >= ip2long($ipv4['overlap']) )
    return;

  $net = _ipcalc($ipv4['ipv4'],$ipv4['netmask']);
  $net_overlap =  _ipcalc($ipv4['ipv4'],$ipv4['overlap']);
  $old_netid = $net_overlap['netid'];
  $new_netid = $net['netid'];
  $old_netmask = $ipv4['overlap'];
  $new_netmask = $ipv4['netmask'];
  $new_broadcast = $net['broadcast'];
  $old_broadcast = $net_overlap['broadcast'];

  //guifi_log(GUIFILOG_BASIC,'<br />Old Netid: '.$old_netid.'<br />New Netid: '.$new_netid.'<br />Old Netmask: '.$old_netmask.'<br />New NetMask: '.$new_netmask.'<br />Old broadcast: '.$old_broadcast.'<br />New Broadcast: '.$new_broadcast.'<br /><br /> ');
  $sql = db_query("SELECT INET_ATON(ipv4) as ip FROM guifi_ipv4 WHERE ipv4 BETWEEN '%s' AND '%s' ", $old_broadcast, $new_broadcast);

  while ($item = db_fetch_array($sql)) {
    $ip = long2ip($item['ip']);
    $s = ip2long($old_netid);
    $e = ip2long($new_broadcast);
    for($i = $s; $i < $e+1; $i++) {
      $ipnow = long2ip($i);
      if ($ip == $ipnow) {
        drupal_set_message(t('Ip address: %ip is already taken on another device!!', array('%ip' => $ip)),'error');
        $error = TRUE;
        //guifi_log(GUIFILOG_BASIC,'Tipus de check: Broadcast<br />');
      }
    }
    $count = $e-$s+1;
  }

  $sql = db_query("SELECT INET_ATON(ipv4) as ip FROM guifi_ipv4 WHERE ipv4 BETWEEN '%s' AND '%s' ", $new_netid, $old_netid);

  while ($item = db_fetch_array($sql)) {
    $ip = long2ip($item['ip']);
    $s = ip2long($new_netid);
    $e = ip2long($old_netid);
    for($i = $s; $i < $e+1; $i++) {
      $ipnow = long2ip($i);
      if ($ip == $ipnow) {
        drupal_set_message(t('Ip address: %ip is already taken on another device!!', array('%ip' => $ip)),'error');
        $error = TRUE;
         //guifi_log(GUIFILOG_BASIC,'Tipus de check: Netid<br>');
      }
    }
    $count = $e-$s+1;
  }

  if ($error == TRUE) {
    form_error($overlap, t('Error! Your new netmask: /%bit ( %mask ) is overlapping another existing subnet, you can\'t expand it!<br />'
                                       .'Then, We will find a range of network in your area with the size needed, just for information. You can use it if it thinks fit.',
          array('%mask' => $new_netmask, '%bit' => $net['maskbits'])));

    $nid =$form_state['values']['nid'];
    $ips_allocated = guifi_ipcalc_get_ips('0.0.0.0', '0.0.0.0', array(), 2);
    guifi_ipcalc_get_subnet_by_nid($nid,$new_netmask, 'backbone', $ips_allocated, 'Yes', TRUE);
  }
}

?>
