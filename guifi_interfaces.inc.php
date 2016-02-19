<?php
/**
 * @file guifi_interfaces.inc.php
 */

function guifi_interfaces_form(&$interface,$ptree) {
  global $hotspot;
  $cable = FALSE;

  guifi_log(GUIFILOG_TRACE,'function guifi_interfaces_form()',$interface);

  $key = $ptree[count($ptree)-1];

  // Interface type shoudn't be NULL
  if ($interface['interface_type'] == NULL)
    return;

  $it = $interface['interface_type'];
  if ($it == 'Wan') {
    $interface['unfold'] = TRUE;
    $msg = t('Connection to AP');
  } else
    $msg = $it;

  $f = array(
    '#type' => 'fieldset',
    '#title' => $msg,
    '#collapsible' => TRUE,
    '#collapsed' => !isset($interface['unfold']),
    '#description'   => t('Go to Port connections section to rename interfaces or edit physical links'),
    '#attributes' => array(array('class'=>'fieldset-interface-port')),
  );

  $f['interface'] = guifi_form_hidden_var(
    $interface,
    array('id','interface_type','radiodev_counter'),
    $ptree
  );

  // Cable interface buttons
  if (($ptree[0]=='interfaces')
       and (!$interface['deleted'])
     ) {
    $cable = TRUE;
    $f['interface']['niid'] = array(
        '#type'          => 'textfield',
        '#title'         => t('Name'),
        '#parents'       => array_merge($ptree,array('interface_type')),
        '#disabled'      => true,
        '#size'          => 10,
        '#maxlength'     => 60,
        '#value'         => $interface['interface_type'],
        '#attributes'    => array(array('class'=>'interface-item')),
    );
    if (!empty($interface['connto_did'])) {
      $dname = guifi_get_devicename($interface['connto_did']);
      $dinterfaces = guifi_get_device_interfaces($interface['connto_did'],$interface['connto_iid']);
      $f['interface']['dname'] = array(
        '#type'         => 'textfield',
        '#title'        => t('Connects to'),
        '#disabled'     => true,
        '#size'         => 40,
        '#maxsize'      => 256,
        '#value'        => ($interface['connto_iid']) ?
           $dname.' / '.$dinterfaces[$interface['connto_iid']] :
           $dname,
        '#attributes'   => array(array('class'=>'interface-item')),
      );
    }

    if (!$interface['new']) {
      $f['interface']['AddPublicSubnetMask'] = array(
        'selectNetmask' => array(
          '#type' => 'select',
          '#name' => 'selectAddPublicSubnetMask-'.$key,
          '#id' => 'selectAddPublicSubnetMask-'.$key,
          '#parents' => array('interface',$key,'selectNetmask'),
          '#attributes' => array('hidden' => ''),
          '#default_value' => "255.255.255.224",
          '#options' => guifi_types('netmask',30,23),
          '#prefix'=> '<div id="editInterface-'.$key.'">',
        ),
        'createNetmask' => array(
          '#type' => 'button',
          '#value' => t('Create'),
          '#name' => 'createAddPublicSubnetMask-'.$key,
          '#attributes' => array('type' => 'button', 'id' => 'createAddPublicSubnetMask-'.$key, 'hidden' => ''),
          '#parents' => array('interface',$key,'createNetmask'),
          '#submit' => array('guifi_interfaces_add_subnet_submit'),
          '#executes_submit_callback' => TRUE,
          '#suffix' => '</div>',
        ),
      );
      $f['interface']['CreateCableLink'] = array(
        'to_did' => array(
          '#type' => 'select',
          '#name' => 'selectCreateCableLink-'.$key,
          '#id' => 'selectCreateCableLink-'.$key,
          '#parents'=> array('interface',$key,'to_did'),
          '#attributes' => array('hidden' => ''),
          '#prefix'=> '<div id="editInterface-'.$key.'">',
        ),
        'createLink' => array(
          '#type' => 'button',
          '#value' => t('Create'),
          '#name' => 'createCreateCableLink-'.$key,
          '#attributes' => array('type' => 'button', 'id' => 'createCreateCableLink-'.$key, 'hidden' => ''),
          '#parents' => array('interface',$key,'createLink'),
          '#submit' => array('guifi_interfaces_add_cable_p2p_link_submit'),
          '#executes_submit_callback' => TRUE,
          '#suffix' => '</div>',
        ),
      );

      $f['interface']['AddCableLink'] = array(
        '#type' => 'image_button',
        '#src' => drupal_get_path('module', 'guifi').'/icons/addprivatecablelink.png',
        '#parents' => array_merge($ptree,array('AddCableLink')),
        '#attributes' => array('title' => t('Link to another device at the node using a private network')),
        '#ajax' => array(
          'callback' => 'guifi_ajax_add_cable_local_link',
          'wrapper' => 'editInterface-'.$key,
          'method' => 'replace',
          'effect' => 'fade',
        )
      );
      $f['interface']['AddPublicSubnet'] = array(
        '#type' => 'image_button',
        '#src' => drupal_get_path('module', 'guifi').'/icons/insertwlan.png',
        '#parents' => array_merge($ptree,array('AddPublicSubnet')),
        '#attributes' => array('title' => t('Allocate a Public Subnetwork to the interface')),
        '#ajax' => array(
          'callback' => 'guifi_ajax_add_subnet_mask',
          'wrapper' => 'editInterface-'.$key,
          'method' => 'replace',
          'effect' => 'fade',
        )
      );
    } else {
      $f['interface']['msg'] = array(
        '#type' => 'item',
        '#title' => t('New interface'),
        '#description' => t('Save to database to add links or allocate subnetworks')
      );
      $f['interface']['itype']['#disabled'] = false;
    }
  }

  // wds/p2p link, allow to create new links
  if ($it == 'wds/p2p' || $interface['interface_class'] == 'wds/p2p')
    $f['interface']['AddWDS'] = array(
      '#type' => 'image_button',
      '#src' => drupal_get_path('module', 'guifi').'/icons/wdsp2p.png',
      '#parents' => array_merge($ptree,array('AddWDS',$ptree[1],$ptree[2])),
      '#attributes' => array('title' => t('Add WDS/P2P link to extend the backbone')),
      '#submit' => array('guifi_radio_add_wds_submit'),
    );

  if ($interface['deleted']){
    $f['interface']['deleteMsg'] = array(
      '#type' => 'item',
      '#value' => t('Deleted'),
      '#description' => guifi_device_item_delete_msg(
         'This interface has been deleted, ' .
         'related addresses and links will be also deleted'),
    );
  } else {
    if (($it != 'wds/p2p') and ($interface['interface_class'] != 'wds/p2p') and ($it != 'wLan/Lan') and ($it != 'Wan'))
      $f['interface']['deleteInterface'] = array(
        '#type' => 'image_button',
        '#src' => drupal_get_path('module', 'guifi').'/icons/drop.png',
        '#parents' => array_merge($ptree,array('deleteInterface')),
        '#attributes' => array('title' => t('Delete interface')),
        '#submit' => array('guifi_interfaces_delete_submit'),
      );
  }


  $ipv4Count = 0;
  if (count($interface['ipv4']) > 0)
    foreach ($interface['ipv4'] as $ka => $ipv4) {

      if (!$ipv4['deleted'])
        $ipv4Count++;

      $f['ipv4'][$ka] =
        guifi_device_ipv4_link_form(
          $ipv4,
          array_merge(
            $ptree,
            array('ipv4',$ka)
          ),
          $cable
        );
    }   // foreach ipv4

  // Mode Client or client-routed, allow to link to AP
  if ( ($it == 'Wan') and ($ipv4Count == 0) )
    $f['interface']['Link2AP'] = array(
      '#type' => 'image_button',
      '#src' => drupal_get_path('module', 'guifi').'/icons/link2ap.png',
      '#parents' => array('Link2AP',$ptree[1],$interface['id']),
      '#attributes' => array('title' => t('Create a simple (ap/client) link to an Access Point')),
      '#submit' => array('guifi_radio_add_link2ap_submit'),
    );

  if ($it != 'HotSpot')
    $f['#title'] .= ' - '.$ipv4Count.' '.
      t('address(es)');
  else
    $hotspot = TRUE;

  return $f;
}

/* guifi_interfaces_form(): Main cable interface edit form */
function guifi_interfaces_cable_form(&$edit,&$form_weight) {

  guifi_log(GUIFILOG_TRACE,'guifi_interfaces_cable_form',$edit['interfaces']);

  $form = array();
  // skip device types which have their own forms for cable ports, or don't have to
  if (in_array($edit['type'],
       array('switch','rack','ppanel','splitter','torpedo')
       )
     )
    return $form;

  global $definedBridgeIpv4;

  $definedBridgeIpv4 = FALSE;

  if (empty($edit['interfaces']))
    return;

   $collapse = TRUE;
  switch (count($edit['interfaces'])) {
  case 0:
     $msg .= t('No interfaces');
     break;
  case 1:
     $msg .= t('1 interface');
     break;
  default:
     $msg .= count($edit['interfaces']).' '.t('interfaces');
  }

  foreach ($edit['interfaces'] as $value)
    if ($value['unfold'])
      $collapse = FALSE;

  $form['interfaces']['#type'] = 'fieldset';
  $form['interfaces']['#title'] = $msg;
//  $form['interfaces']['#title'] = '<img src="/'.
//    drupal_get_path('module', 'guifi').
//    '/icons/interface.png"> '.t('Cable networking section').' - '.$msg;
  $form['interfaces']['#collapsible'] = TRUE;
  $form['interfaces']['#collapsed'] = $collapse;
  $form['interfaces']['#tree'] = TRUE;
  $form['interfaces']['#weight'] = $form_weight++;
  $form['interfaces']['#prefix'] = '<div id="cable-networking"><div><img src="/'.
    drupal_get_path('module', 'guifi').
    '/icons/Cable-Network-16.png"> '.t('Cable networking section').'</div>';
  $form['interfaces']['#suffix'] = '</div>';

  // placeholder for the add interface form
  $form['interfaces']['ifs'] = array(
 //   '#type'   => 'hidden',
    '#prefix' => '<div id="add-interface">',
    '#suffix' => '</div>',
    '#weight' => $form_weight++,
  );

  foreach ($edit['interfaces'] as $iid => $interface) {
    guifi_log(GUIFILOG_TRACE,'guifi_interfaces_cable_form',$edit['interfaces']);
    $form['interfaces']['ifs'][$interface['interface_type']][$iid] =
      guifi_interfaces_form($interface,array('interfaces',$iid));
  } // foreach interface

  $form['interfaces']['addInterface'] = array(
        '#type' => 'image_button',
        '#src'=> drupal_get_path('module', 'guifi').'/icons/addinterface.png',
        '#parents' => array('addInterface'),
        '#attributes' => array('title' => t('Add Interface for cable connections')),
        '#ahah' => array(
          'path' => 'guifi/js/add-interface',
          'wrapper' => 'add-interface',
          'method' => 'replace',
          'effect' => 'fade',
         ),
         '#weight' => $form_weight++,
  );

  return $form;
}


/* _guifi_add_subnet_submit(): Action */
function guifi_interfaces_add_subnet_submit(&$form,&$form_state) {
  $values = $form_state['clicked_button']['#parents'];
  $iid    = $values[count($values)-2];
  $mask   = $form_state['input']['selectAddPublicSubnetMask-'.$iid];
  guifi_log(GUIFILOG_TRACE,
    sprintf('function guifi_interfaces_add_subnet_submit(%d)',$iid),
    $mask);

  $ips_allocated=guifi_ipcalc_get_ips('0.0.0.0','0.0.0.0',$form_state['values'],1);
  $net = guifi_ipcalc_get_subnet_by_nid($form_state['values']['nid'],$mask,'public',$ips_allocated);

  drupal_set_message(t('New subnetwork @net/@mask will be allocated.',
    array('@net' => $net,
      '@mask' => $mask)));

  $ipv4['new'] = TRUE;
  $ipv4['ipv4_type'] = 1;
  $ipv4['ipv4'] = long2ip(ip2long($net) + 1);
  $ipv4['netmask'] = $mask;
  $ipv4['interface_id'] = $iid;
  $fipv4 = &$form_state['values']['interfaces'][$iid]['ipv4'];
  $fipv4[] = $ipv4;
  end($fipv4);
  $delta = key($fipv4);
  $fipv4[$delta]['id'] = $delta;
  $form_state['values']['interfaces'][$iid]['unfold'] = TRUE;
  $form_state['rebuild'] = TRUE;

  return TRUE;
}

function guifi_interfaces_add_cable_p2p_link_submit(&$form,&$form_state) {
  $values = $form_state['clicked_button']['#parents'];
  $iid    = $values[1];
  $to_did   = $form_state['input']['selectCreateCableLink-'.$iid];
  $rdevice = guifi_device_load($to_did);

  $dlinked = db_query(
    'SELECT d.id, d.type ' .
    'FROM {guifi_devices} d ' .
    'WHERE d.id = :to_did AND type not IN (\'switch\',\'rack\',\'torpedo\',\'splitter\') ',
    array(':to_did' => $to_did))->fetchAssoc();

  $ips_allocated = guifi_ipcalc_get_ips('0.0.0.0','0.0.0.0',$form_state['values'],2);

  // get backbone /30 subnet
  $mask = '255.255.255.252';
  $net = guifi_ipcalc_get_subnet_by_nid(
    $form_state['values']['nid'],
    $mask,
    'backbone',
    $ips_allocated);

  if (!$net) {
    drupal_set_message(t('Unable to create link, no networks available'),'warning');
    return FALSE;
  }

  $dnet = ip2long($net);
  $ip1 = long2ip($dnet + 1);
  $ip2 = long2ip($dnet + 2);

  $newlk['new']=TRUE;
  $newlk['interface']=array();
  $newlk['link_type']='cable';
  $newlk['flag']='Planned';
  $newlk['nid']=$form_state['values']['nid'];
  $newlk['device_id'] = $to_did;
  if ($dlinked['type']=='radio')
    $newlk['routing'] = 'BGP';
  else
    $newlk['routing'] = 'Gateway';

  $newlk['interface']['new'] = TRUE;
  $newlk['interface']['device_id'] = $to_did;
  $free = guifi_get_free_interfaces($to_did,$rdevice);
  $newlk['interface']['interface_type']= array_shift($free);
//  $newlk['interface']['interface_type']= 'ether3';
  $newlk['interface']['ipv4']['new'] = TRUE;
  $newlk['interface']['ipv4']['ipv4_type'] = 2;
  $newlk['interface']['ipv4']['ipv4'] = $ip2;
  $newlk['interface']['ipv4']['netmask'] = $mask;


  $ipv4['new']=TRUE;
  $ipv4['ipv4']=$ip1;
  $ipv4['ipv4_type']=2;
  $ipv4['netmask']=$mask;
  $ipv4['interface_id'] = $iid;
  $ipv4['links'][]=$newlk;

  $form_state['values']['interfaces'][$iid]['ipv4'][]=$ipv4;
  $form_state['values']['interfaces'][$iid]['unfold']=TRUE;
  $form_state['rebuild'] = TRUE;

  return TRUE;
}

function guifi_interfaces_add_cable_public_link_submit(&$form,&$form_state) {
  $values = $form_state['clicked_button']['#parents'];
  $iid    = $values[1];
  $ipv4_id= $values[3];
  $to_did = $form_state['values']['interfaces'][$iid]['ipv4'][$ipv4_id]['to_did'];
  $rdevice = guifi_device_load($to_did);

  guifi_log(GUIFILOG_TRACE,
    sprintf('function guifi_interfaces_add_cable_public_link_submit(%d)',$iid),
//      $form_state['values']);
      $form_state['clicked_button']['#parents']);

  $ips_allocated=guifi_ipcalc_get_ips('0.0.0.0','0.0.0.0',$form_state['values'],1);

  // get next available ip address
  $base_ip=
    $form_state['values']['interfaces'][$iid]['ipv4'][$ipv4_id];
  $item = _ipcalc($base_ip['ipv4'],$base_ip['netmask']);
  $ip= guifi_ipcalc_find_ip($item['netid'],$base_ip['netmask'],$ips_allocated);

  // no IP was given, so raise a message and don't create the link'
  if (empty($ip)) {
    drupal_set_message(t('Unable to assign a free ip, link not created, ' .
        'contact the administrator.'));
    return;
  }

  $newlk['new']=TRUE;
  $newlk['interface']=array();
  $newlk['link_type']='cable';
  $newlk['flag']='Planned';
  $newlk['nid']=$form_state['values']['nid'];
  $newlk['device_id'] = $to_did;
  if ($rdevice['type']=='radio')
    $newlk['routing'] = 'BGP';
  else
    $newlk['routing'] = 'Gateway';

  $newlk['interface']['new'] = TRUE;
  $newlk['interface']['device_id'] = $to_did;
  $free = guifi_get_free_interfaces($to_did,$rdevice);
  $newlk['interface']['interface_type']= array_shift($free);
  $newlk['interface']['ipv4']['new'] = TRUE;
  $newlk['interface']['ipv4']['ipv4_type'] = 1;
  $newlk['interface']['ipv4']['ipv4'] = $ip;
  $newlk['interface']['ipv4']['netmask'] = $base_ip['netmask'];

  $form_state['values']['interfaces'][$iid]['ipv4'][$ipv4_id]['links'][]=$newlk;
  $form_state['values']['interfaces'][$iid]['unfold']=TRUE;
//  print_r($form_state['values']);
  $form_state['rebuild'] = TRUE;

  return TRUE;
}

/* Delete interface */
function guifi_interfaces_delete_submit(&$form,&$form_state) {
  $values      = $form_state['clicked_button']['#parents'];
  $radio_id    = $values[count($values)-4];
  $interface_id= $values[count($values)-2];
  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_interface_delete_submit(radio: %d, interface: %d)',
    $radio_id,$interface_id),$form_state['clicked_button']['#parents']);
  if ($values[0]=='interfaces') {
    $interface = &$form_state['values']['interfaces'][$interface_id];
  } else {
    $form_state['values']['radios'][$radio_id]['unfold'] = TRUE;
    $interface = &$form_state['values']['radios'][$radio_id]['interfaces'][$interface_id];
  }
  $interface['unfold'] = TRUE;
  $interface['deleted'] = TRUE;
//  $form_state['deleteInterface']=($radio_id).','.($interface_id);
  $form_state['rebuild'] = TRUE;
//  $form_state['action'] = 'guifi_interface_delete';
  return TRUE;
}

function guifi_interfaces_cmp($a, $b) {
  if ($a[etherdev_counter] == $b[etherdev_counter])
    return ($a[iid] < $b[iid]) ? -1 : 1;
  return ($a[etherdev_counter] < $b[etherdev_counter]) ? -1 : 1;
}

?>
