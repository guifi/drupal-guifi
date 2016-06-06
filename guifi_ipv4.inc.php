<?php
/**
 * @file guifi_ipv4.inc.php
 * ipv4 editing functions
 */


/**
 * Form callback; handle the submit .
 */
function guifi_ipv4_form_submit($form, &$form_state) {
  guifi_ipv4_save($form_state['values']);
  $form_state['redirect'] = 'node/'.$form_state['values']['zone'].'/view/ipv4';
}

/**
 * Menu callback; handle the adding of a new guifi.
 */

function guifi_ipv4_add($zone) {
  drupal_set_title(t('Adding an ipv4 network range'));

  return drupal_get_form('guifi_ipv4_form',array('add' => $zone->id));
}

/**
 * Menu callback; delete a single ipv4 network.
 */
function guifi_ipv4_delete($id) {
  $result = db_query('SELECT *
                      FROM {guifi_networks}
                      WHERE id = :id', array(':id' => $id));
  $edit = $result->fetchAssoc();

  if ($_POST['confirm']) {
    $msg = t('The network %base/%mask (%type) has been DELETED by %user.',
      array('%base' => $edit['base'],
        '%mask' => $edit['mask'],
        '%type' => $edit['network_type'],
        '%user' => $user->name));
    $edit['deleted'] = TRUE;

    $nnetwork = _guifi_db_sql(
      'guifi_networks',
      array('id' => $edit['id']),
      (array)$edit,
      $log,$to_mail);
    guifi_notify(
      $to_mail,
      $msg,
      $log);

    drupal_goto('node/'.$edit['zone'].'/view/ipv4');
  }
  return drupal_get_form('guifi_ipv4_confirm_delete',$edit['base'],$edit['mask'],$edit['zone']);
}

/**
 * Hook callback; delkete a network
 */
function guifi_ipv4_confirm_delete($form_state,$base,$mask,$zone) {
  return confirm_form(array(),
                     t('Are you sure you want to delete the network range %base/%mask?', array('%base' => $base,'%mask' => $mask)),
                     'node/'.$zone.'/view/ipv4',
                     t('This action cannot be undone.'),
                     t('Delete'),
                     t('Cancel'));
}


/**
 * Menu callback; dispatch to the appropriate guifi network edit function.
 */
function guifi_ipv4_edit($id = 0) {
  return drupal_get_form('guifi_ipv4_form',array('edit' => $id));
}

/**
 * Present the guifi zone editing form.
 */
function guifi_ipv4_form($form_state, $params = array()) {
  guifi_log(GUIFILOG_TRACE,'guifi_ipv4_form()',$params);

  $network_types = array('public'   => t('public - for any device available to everyone'),
                         'backbone' => t("backbone - used for internal management, links..."),
                         'mesh' => t('mesh - for any device in Mesh'),
                         'reserved' => t('reserved - used for reserved addressing'));

  if (empty($form_state['values'])) {
    // first execution, initializing the form

    // if new network, initialize the zone
    if ($params['add']) {
      $zone_id=$params['add'];
      $zone = node_load($zone_id);

      // if is root zone, don't find next value'
      if ($zone_id != guifi_zone_root()) {
        // not root zone, fill default values looking to next available range
        $zone = node_load($zone_id);
        $ndefined = db_query('SELECT count(*) c FROM guifi_networks WHERE zone = :zid', array(':zid' => $zone_id))->fetchObject();

        switch ($ndefined->c) {
          case 0: $mask='255.255.255.0'; break;
          case 1: $mask='255.255.254.0'; break;
          case 2: $mask='255.255.252.0'; break;
          case 3: $mask='255.255.248.0'; break;
          default: $mask='255.255.240.0';
        }

        $form_state['values']['zone'] = $zone_id;
        $ips_allocated = guifi_ipcalc_get_ips('0.0.0.0','0.0.0.0', NULL,1);
        $network_type='public';
        $allocate = 'No';
        $net = guifi_ipcalc_get_subnet_by_nid($zone->master,
        $mask,
        $network_type,
        $ips_allocated,
        $allocate,   // never allocate the obtained range at guifi_networks
        TRUE);   // verbose output

        if ($net) {
          $item=_ipcalc($net,$mask);
          $form_state['values']['base']=$net;
          $form_state['values']['mask']=$mask;
        } else
          drupal_set_message(t('It was not possible to find @type space for @mask',
          array('@type' => $network_type, '@mask' => $mask)),'error');
        } // if is not the root zone
    }

    // if existent network, get the network and edit
    if ($params['edit'])
      $form_state['values'] = db_query('SELECT *
                                        FROM {guifi_networks}
                                        WHERE id = :id', array(':id' => $params['edit']))->fetchAssoc();
  }

  $form['base'] = array(
    '#type' => 'textfield',
    '#title' => t('Network base IPv4 address'),
    '#required' => TRUE,
    '#default_value' => $form_state['values']['base'],
    '#size' => 16,
    '#maxlength' => 16,
    '#description' => t('A valid base ipv4 network address.'),
    '#weight' => 0,
  );
  $form['mask'] = array(
    '#type' => 'select',
    '#title' => t("Mask"),
    '#required' => TRUE,
    '#default_value' => $form_state['values']['mask'],
    '#options' => guifi_types('netmask',24,0),
    '#description' => t('The mask of the network. The number of valid hosts of each masks is displayed in the list box.'),
    '#weight' => 1,
  );


  $form['zone'] = guifi_zone_select_field($form_state['values']['zone'],'zone');
  $form['zone']['#weight'] = 2;

  $form['network_type'] = array(
    '#type' => 'select',
    '#title' => t("Network type"),
    '#required' => TRUE,
    '#default_value' => $form_state['values']['network_type'],
    '#options' => $network_types,
    '#description' => t('The type of usage that this network will be used for.'),
    '#weight' => 3,
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
    '#weight' => 4);
  $form['id'] = array(
    '#type' => 'hidden',
    '#value' => $form_state['values']['id'],
    '#weight' => 5);
  $form['valid'] = array(
    '#type' => 'hidden',
    '#value' => $form_state['values']['valid'],
    '#weight' => 6);

  return $form;
}

/**
 * Confirm that an edited guifi network has fields properly filled in.
 */
function guifi_ipv4_form_validate($form,$form_state) {
  if (empty($form_state['values']['base'])) {
    form_set_error('base', t('You must specify a base network for the zone.'));
  }
  $item = _ipcalc($form_state['values']['base'],$form_state['values']['mask']);
  if ($item == -1) {
    form_set_error('base', t('You must specify a valid ipv4 notation.'));
  }
  if ( $form_state['values']['base'] != $item['netid']  ) {
    form_set_error('base',
      t('You must specify a valid ipv4 network base address. Base address for:').
        $form_state['values']['base'].'/'.
        $form_state['values']['mask'].' '.
        t('is').' '.$item['netid'] );
  }
  if (empty($form_state['values']['id'])) {
    $result = db_query('SELECT base, mask
                        FROM {guifi_networks}
                        WHERE base = :base
                         AND mask = :mask',
        array(':base' => $form_state['values']['base'],
              ':mask' => $form_state['values']['mask']));
    if (count($result) > 0)
      form_set_error('base', t('Network already in use.'));
  }

  $zone = node_load($form_state['values']['zone']);
}

/* outputs the network information data
**/
function guifi_ipv4_print_data($zone, $list = 'parents', $ips_allocated) {
  global $user;

  $header = array(

    array('data' => t('network')),
  );

  if (user_access('administer guifi networks'))
    $header = array_merge($header,array(t('operations')));

  if ($list == 'childs') {
    $zones = guifi_zone_childs($zone->id);
    $pager = 1;
    $k = array_search($zone->id,$zones);
    unset($zones[$k]);
  } else {
    $zones = guifi_zone_get_parents($zone->id);
    $pager = 1;
  }

  if (empty($zones))
    return t('There is no zones to look at');
  $rows = array();

  $sql = db_select('guifi_networks', 'n')
    ->fields('n', array('zone','id','base','mask','network_type'))
    ->condition('zone', $zones, 'IN')
    ->orderBy('FIND_IN_SET(zone,\''.implode(',',$zones).'\')');
  if ($list == 'childs')
    $sql = $sql->extend('PagerDefault')->limit(variable_get("guifi_pagelimit", 50));
  $result = $sql->execute();

  $current_zoneid = -1;
  while ($net = $result->fetchObject()) {
    $item = _ipcalc($net->base,$net->mask);

    // obtaing the used ip's
    $min = ip2long($item['netstart']);
    $max = ip2long($item['netend']);

    $ips = 0;
    $k = $min;
    $amin = NULL;
    $amax = NULL;

    while ($k <= $max) {
      if (isset($ips_allocated[$k])) {
        $ips++;
        $amax = $k;
        if ($ips == 1)
          $amin = $k;
      }
      $k++;
    }

    if ($current_zoneid != $net->zone) {
      $current_zoneid = $net->zone;
      $rows[] = array(array('data' => l(guifi_get_zone_name($net->zone),'node/'.$net->zone.'/view/ipv4'),
        'colspan' => '0'));
    }

    $row = array(
//      $zonelink,
      $net->base.'/'.$item['maskbits'].' ('.$net->mask.')',
      $item['netstart'].' / '.$item['netend'],
      array('data' => number_format($item['hosts']),'align' => 'right'),
      $net->network_type,
      long2ip($amin).' / '.long2ip($amax),
      array('data' => number_format($ips),'align' => 'right'),
      array('data' => round(($ips*100)/$item['hosts']).'%','align' => 'right'),
    );
    if (user_access('administer guifi networks'))
    $row[] = array('data' => l(guifi_img_icon('edit.png'),'guifi/ipv4/'.$net->id.'/edit',
          array(
            'html' => TRUE,
            'title' => t('edit network'),
            'attributes' => array('target' => '_blank'))).
        l(guifi_img_icon('drop.png'),'guifi/ipv4/'.$net->id.'/delete',
          array(
            'html' => TRUE,
            'title' => t('delete device'),
            'attributes' => array('target' => '_blank'))),
        'align' => 'center');
    $rows[] = $row;
  }


  if (count($rows)) {
  $output .= theme('table',array('header' => $header, 'rows' => $rows));
  if ($list == 'childs')
    $output .= theme('pager');
  } else
    $output .= t('None');

  return $output;
}



/**
 * Save changes to a guifi network into the database.
 */

function guifi_ipv4_save($edit) {

  global $user;

  if (!isset($edit['id'])) {
    $edit['new'] = TRUE;
    $msg = t('The network %base/%mask (%type) has been CREATED by %user.',
      array('%base' => $edit['base'],
        '%mask' => $edit['mask'],
        '%type' => $edit['network_type'],
        '%user' => $user->name));
  } else
    $msg = t('The network %base/%mask (%type) has been UPDATED by %user.',
      array('%base' => $edit['base'],
        '%mask' => $edit['mask'],
        '%type' => $edit['network_type'],
        '%user' => $user->name));

  $nnetwork = _guifi_db_sql(
    'guifi_networks',
    array('id' => $edit['id']),
    (array)$edit,
    $log,$to_mail);
  guifi_notify(
    $to_mail,
    $msg,
    $log);
}

function guifi_ipv4subnet_form($ipv4,$k,$view = false) {
  define('MAXNIPS',12);

  guifi_log(GUIFILOG_TRACE,sprintf('guifi_ipv4subnet_form (id=%d)',$k),$ipv4);

  $ipc = _ipcalc($ipv4['ipv4'],$ipv4['netmask']);
  if (!empty($ipv4['subnet']))
    $subnet = $ipv4['subnet'];
  else
    $subnet = $ipv4['snet'];
  guifi_log(GUIFILOG_FULL,sprintf('guifi_ipv4subnet_form (id=%d)',$k),$subnet);
    
  $ips = array(ip2long($ipv4['ipv4']));
  
  foreach ($subnet as $ki=>$ip) {
    if (empty($ip['ipv4']) and !(empty($ip['ipv4value'])))
      $subnet[$ki]['ipv4'] = $ip['ipv4value'];
    if (ip2long($ip['ipv4']))
      $ips[] = ip2long($ip['ipv4']);
  }
  sort($ips);

  $form = array(
    '#type'         => (!$view) ? 'hidden' : 'fieldset',
    '#type'         => 'fieldset',
//    '#access'       => (!$view) ? false : TRUE,
    '#title'        => t('Subnet'). ' '.$ipc['netid'].'/'.$ipc['maskbits'].' - '.
       (count($ips)).' address(es)',
//    '#attributes'   => array('class'=>'fieldset-interface-port'),
    '#prefix'       => '<div class="ipv4-subnet" id="fieldset-ipv4subnet-'.$k.'">',
    '#suffix'       => '</div>',
    '#collapsible'  => TRUE,
    '#collapsed'    => ($view) ? false : TRUE,
//    '#parents'      => array('ipv4',$k,'subnet'),
  );

//  foreach ($subnet as $ks => $snet) {
  $nIps = 0; $newIps = 0;
  for ($ks=0; $ks < $ipc['hosts']; $ks++) {
    $snet = $subnet[$ks];

    if (!empty($snet['ipv4'])) {
    $form[$ks] = array(
      '#type' => 'fieldset',
 //     '#type' => 'fieldset',
      '#attributes' => array('class'=>'fieldset-interface-port'),
      '#prefix'     => '<div id="fieldset-ipv4subnet-'.$k.'-'.$ks.'">',
      '#suffix'     => '</div>',
    );
    }
    $form[$ks]['ipv4value'] = array('#type'=>'hidden','#value'=>$snet['ipv4']);
    
    if (isset($snet['id'])) 
      $form[$ks]['id'] = array('#type'=>'hidden','#value'=>$snet['id']);
    
    if (isset($snet['interface_id'])) 
      $form[$ks]['interface_id'] = array('#type'=>'hidden','#value'=>$snet['interface_id']);
        
    $form[$ks]['ipv4'] = array(
      '#type'          => 'textfield',
      '#default_value' => $snet['ipv4'],
      '#size'          => 24,
      '#maxlength'     => 16,
    );
    
    if ($snet['new']) {
      $form[$ks]['ipv4']['#type'] = 'hidden';
      $form[$ks]['ipv4']['#value'] = $snet['ipv4'];
      $form[$ks]['ipv4txt'] = array (
        '#type'=>'textfield','#value'=>$snet['ipv4'],'#disabled'=>TRUE, '#size'=>24
      );
      $newIps++;
      $form[$ks]['new'] = array(
        '#type' =>'hidden',
        '#value'=> TRUE,
      );
    }
    
    $form[$ks]['did'] = array(
      '#type'              => (($view) and (!empty($snet['ipv4']))) ? 'textfield' : 'hidden',
      '#type'              => 'textfield',
      '#default_value'     => guifi_get_devicename($snet['did'],'large'),
      '#autocomplete_path' => 'guifi/js/select-node-device',
      '#size'              => 60,
      '#maxlength'         => 128,
      '#element_validate'  => array('guifi_devicename_validate'),
      '#ahah'              => array(
        'event'            => 'blur',
        'path'             => 'guifi/js/select-device-interfacename/'.$k.'-'.$ks,
        'wrapper'          => 'fieldset-ipv4subnet-'.$k.'-'.$ks,
        'method'           => 'replace',
        'effect'           => 'fade',
      ),

    );
//    if (!empty($snet['ipv4'])) {
    $dinterfaces = guifi_get_device_allinterfaces($snet['did']);
    $form[$ks]['iid'] = array(
      '#type'    => 'select',
      '#value'   => $snet['iid'],
      '#required'=> TRUE,
      '#options' => $dinterfaces,
    );
//    }

    if (!($snet['deleted'] == TRUE)) $form[$ks]['deleted'] = array(
        '#type'       => 'image_button',
        '#src'        => drupal_get_path('module', 'guifi').'/icons/drop.png',
        '#attributes' => array('title' => t('Delete address')),
        '#ahah'       => array(
          'path'      => 'guifi/js/delete-ipv4/'.$k.'-'.$ks,
          'wrapper'   => 'fieldset-ipv4subnet-'.$k.'-'.$ks,
          'method'    => 'replace',
          'effect'    => 'fade',
        ),
      ); 
    else {
        $form[$ks]['deleted'] = array('#type'=>'hidden','#value' => TRUE);
        $form[$ks]['ipv4']['#type'] = 'hidden';
        $form[$ks]['ipv4']['#value'] = $snet['ipv4'];
        $form[$ks]['did']['#type'] = 'hidden';
        $form[$ks]['iid']['#type'] = 'hidden';
        $form[$ks]['deletedmsg'] = array(
          '#type'=>'item',
          '#value'=>t('Address %addr will be DELETED',array('%addr'=>$snet['ipv4']))
        );      
      }
      
    // force save if new addresses at the in-memory form is too high
    if (empty($snet['ipv4'])) 
      $nIps++;
    if ($nIps >= MAXNIPS)
        break;
  }

  if (count($ips) < $ipc['hosts']) 
    if ($newIps < MAXNIPS) 
      $form['add-ipv4'] = array(
        '#type'       => 'image_button',
        '#title'      => ($first_port) ? t('Add') : false,
        '#src'        => drupal_get_path('module', 'guifi').'/icons/ipv4-new.png',
        '#attributes' => array('title' => t('Add new ipv4 address to the subnetwork')),
        '#weight'     => 100,
        '#ahah'       => array(
          'path'      => 'guifi/js/add-remoteipv4/'.$k,
          'wrapper'   => 'fieldset-ipv4subnet-'.$k,
          'method'  => 'replace',
          'effect'    => 'fade',
        ),
      );
    else $form['add-ipv4'] = array(
      '#type'       => 'item',
      '#title'      => ($first_port) ? t('Add') : false,
      '#value'      => t('To many new addresses added. Is not safe to add more values, please confirm or discard changes'),
      '#weight'     => 100,
    );
    
  return $form;
}


function guifi_ipv4i_form($ipv4, $k, $first_port = TRUE, $eInterfaces) {
  
//  if (empty($ipv4[ipv4]))
//    return;
    
  guifi_log(GUIFILOG_TRACE,'function guifi_ipv4i_form (ipv4)',$ipv4);
  $prefix = ''; $suffix = '';
  $form = array(
    '#type'         => 'fieldset',
    '#parents'      => array('ipv4',$k),
    '#attributes'   => array('class'=>'fieldset-interface-port'),
    '#prefix'       => '<div id="fieldset-ipv4-'.$k.'">',
    '#suffix'       => '</div>',
    '#tree'         => TRUE,
  );

  $form['ipv4'] = array(
    '#type' => 'textfield',
    '#title' => ($first_port) ? t('IPv4 address') : false,
 //   '#disabled' => TRUE,
    '#default_value' => $ipv4[ipv4],
    '#size' => 24,
    '#maxlength' => 16,
  );
  $form['netmask'] = array(
    '#type' => 'select',
    '#title' => ($first_port) ? t("Mask") : false,
//    '#disabled' => TRUE,
    '#default_value' => $ipv4['netmask'],
    '#options' => guifi_types('netmask',30,0),
  );

  $iid_eInterfaces = array();
  foreach ($eInterfaces as $key => $iface) {
    if (strpos($key,','))
      continue;
    $radio_iid = db_query("SELECT * FROM {guifi_interfaces} WHERE id = :id", array(':id' => $key))->fetchAssoc();
    if ( $radio_iid['interface_type'] === 'wds/p2p')
      if ( empty($radio_iid['interface_class']))
        $iface = 'WDS-wlan'.($radio_iid['radiodev_counter']+1);
      else
        $iface = $radio_iid['interface_class'];
      if ( $radio_iid['interface_type'] === 'wLan')
        $iface = 'wlan'.($radio_iid['radiodev_counter']+1);

      $iid_eInterfaces[$key] = $iface;
  }

  $form['id'] = array('#type'=>'hidden','#value'=>$ipv4['id']);
  $form['interface_id'] = array(
    '#type' => 'select',
    '#title'        => ($first_port) ? t('interface') : false,
    '#options'      => $iid_eInterfaces,
    '#default_value'=> $ipv4['interface_id'],
  );
  
 /* Commenting from now: I was unable to do it successfully with ahah :() 
 $form['esubnet'] = array(
       '#type' => 'image_button',
       '#src'  => drupal_get_path('module', 'guifi').'/icons/Cable-Network-16-edit.png',
       '#attributes' => array(
          'title' => t('Edit subnetwork members').' - '.
            (count($ipv4[subnet]) + 1).
            ' '.t('address(es)'),
        ),
       '#ahah' => array(
         'path' => 'guifi/js/edit_subnet/'.$k,
         'wrapper' => 'fieldset-ipv4subnet-'.$k,
         'method' => 'replace',
         'effect' => 'fade',
        ),
        '#prefix' => ($first_port) ? '<div class="form-item"><div>&nbsp</div>':'<div class="form-item">',
        '#suffix' => '</div>',
        '#weight' => $form_weight++,
     ); 
  $form['nsnets'] = array(
    '#type'  => 'item',
    '#value' => (count($ipv4[subnet]) + 1).' '.t('address(es)'),
    '#title' => ($first_port) ? '&nbsp;' : false,
  ); 
  /* */
  
  if (!$ipv4[deleted])
  $form['delete'] = array(
    '#type' => 'image_button',
    '#title' => ($first_port) ? t('delete') : false,
    '#src' => drupal_get_path('module', 'guifi').'/icons/drop.png',
    '#attributes' => array('title' => t('Delete address')),
    '#submit' => array('guifi_ipv4i_delete_submit'),
    '#prefix' => ($first_port) ?
      '<div class="form-item"><label>&nbsp</label>' : false,
    '#suffix' => ($first_port) ?
      '</div>' : false,
  );

 // Subnet members
  $form['subnet'] = guifi_ipv4subnet_form($ipv4,$k,false);
  // guifi_form_hidden($form['snet'],$ipv4[subnet]);


/*  if (!$ipv4[deleted])
  $form['delete'] = array(
    '#type' => 'image_button',
    '#title' => ($first_port) ? t('delete') : false,
    '#src' => drupal_get_path('module', 'guifi').'/icons/drop.png',
    '#attributes' => array('title' => t('Delete ipv4')),
    '#submit' => array('guifi_ipv4i_delete_submit'),
    '#prefix' => ($first_port) ?
      '<div class="form-item"><label>&nbsp</label>' : false,
    '#suffix' => ($first_port) ?
      '</div>' : false,
  );
*/
  // Hidden fields
    $form['id'] = array(
      '#type'         => 'hidden',
      '#value'        => $ipv4['id'],
    );
    $form['ipv4_type'] = array(
      '#type'         => 'hidden',
      '#value'        => $ipv4['ipv4_type'],
    );
    $form['zone_id'] = array(
      '#type'         => 'hidden',
      '#value'        => $ipv4['zone_id'],
    );
    if ($ipv4['deleted'])
      $form['deleted'] = array(
        '#value'      => $ipv4['deleted'],
      );
    if ($ipv4['new'])
      $form['new'] = array(
        '#type'       => 'hidden',
        '#value'      => $ipv4['new'],
      );
  return $form;
}

function guifi_ipv4s_form($edit, &$form_weight) {
  if ($edit['new'])
    return;

  global $user;

//  if (empty($edit[ipv4]))
//    return;

  guifi_log(GUIFILOG_TRACE,'function guifi_ipv4s_form(ipv4)',$edit['ipv4']);
  
  // Clean empty subnets
  foreach ($edit[ipv4] as $k => $v) {
    if (empty($v[ipv4]))
      unset($edit[ipv4][$k]);
  }

  // Build ipv4 fieldset
  $form = array(
    '#type'        => 'fieldset',
    '#title'       => t('IPv4 addresses networking section').' - '.count($edit[ipv4]),
    '#collapsible' => TRUE,
    '#tree'        => TRUE,
    '#collapsed'   => TRUE,
    '#description' => 'Under development. Changes at this form take no effect.',
    '#weight'      => $form_weight++,
    '#prefix'      =>
      '<br><img src="/'.drupal_get_path('module', 'guifi').
      '/icons/ipv4.png"> '.t('ipv4 section')/*.
      '<div id="add-ipv4s-dialog">'*/,
  );

  // Loop across all existing addresses
  $ipv4_count = 0;
  $total_ipv4 = count($edit['ipv4']);
  $first = TRUE;

  foreach ($edit['ipv4'] as $k => $ipv4) {
    if (is_numeric($k)) {
      guifi_log(GUIFILOG_TRACE,'function guifi_ipv4s_form(vint LOOP)',$ipv4);
      $form[$k] =
        guifi_ipv4i_form($ipv4, $k, $first,guifi_get_currentInterfaces($edit, TRUE));
      $first = false;
    }
  }
  guifi_log(GUIFILOG_TRACE,'function guifi_ipv4s_form(vint LOOP AFTER)',$form);

  // placeholder for the add interface form
  $form['ipv4sdialog'] = array(
    '#parents'    => array('ipv4','ipv4sdialog'),
    '#type'       => 'hidden',
    '#title'      => t('New address properties dialog'),
    '#description'=> t('Get the address from'),
    '#prefix'     => /*'<div>&nbsp;<hr><img src="/'.drupal_get_path('module', 'guifi').'/icons/ipv4-new.png'.'"> '.
                    t('Get a new IPv4 address').*/
                    '<div id="add-ipv4s-dialog">',
    '#suffix'     => '</div>',
    '#collapsible'=> TRUE,
    '#collapsed'  => TRUE,
    '#attributes' => array('class'=>'fieldset-interface-port'),
    '#weight'     => $form_weight++,
    '#tree'       => TRUE,
  );
      
  $form['ipv4sdialog']['adddid'] = array(
//    '#parents' => array('ipv4','ipv4sdialog','adddid'),
    '#type'              => 'textfield',
    '#title'             => t('device'),
    '#description'       => t('select the device to obtain the ipv4 address from'),
//    '#value'             => '',
    '#autocomplete_path' => 'guifi/js/select-node-device',
    '#size'              => 60,
    '#maxlength'         => 128,
    '#element_validate'  => array('guifi_devicename_validate'),
    '#ahah'              => array(
      'event'            => 'blur',
      //'keypress'         => TRUE,
      'path'             => 'guifi/js/select-device-subnets',
      // 'wrapper'          => 'edit-ipv4-ipv4sdialog-snet-wrapper',
      'wrapper'          => 'add-ipv4s-dialog',   
      'method'           => 'replace',
      'effect'           => 'fade',
    ),
    //'#prefix'    => '<div>',
    //'#suffix'    => '</div>',
    '#weight'    => $form_weight++,
  );
  
  $form['ipv4sdialog']['ipv4'] = array(
    '#type'        => 'textfield',
    '#title'       => t('IPv4 address'),
    '#description' => t('Obtained address'),
    '#size'        => 20,
    '#maxlength'   => 20,
    '#weight'      => $form_weight++,
  );
  $form['ipv4sdialog']['ipv4_type'] = array(
    '#type'              => 'hidden',
    '#weight'    => $form_weight++,
  );
  
  $form['ipv4sdialog']['snet'] = array(
    // '#parents' => array('ipv4','ipv4sdialog','adddid'),
    '#type'        => 'select',
    '#options'     => array('none'=>t('select address')),
    '#title'       => t('address & remote interface'),
    '#description' => t('select a free address from the device<br>click on the select list to refresh values'),
    '#weight'      => $form_weight++,
  );
             
  $form['ipv4sdialog']['netmask'] = array(
    '#type' => 'select',
    '#ahah' => array(
      'path' => 'guifi/js/add-ipv4s/netmask',
      'wrapper' => 'add-ipv4s-dialog',
      'method' => 'replace',
      'effect' => 'fade',
     ),
    '#title' => 'network mask',
    '#weight'    => $form_weight++,
  );
  $form['ipv4sdialog']['iid'] = array (
    '#type' => 'select',
    '#options' => guifi_get_currentInterfaces($edit), // to be refreshed on the fly 
                         // on ahah event using existing interfaces at the form
    '#title' => t('interface'),
    '#description' => t('where the new ip addres should be given to'),
    '#weight'    => $form_weight++,
  );
    
  $form['ipv4sdialog']['addipv4'] = array(
    '#type'      => 'submit',
    '#value'     => 'Add IPv4, Save & continue edit',
    '#prefix'    => '<div style="clear: both">',
    '#suffix'    => '</div>',
    '#submit'    => array('guifi_device_add_ipv4s_submit','guifi_device_form_submit'),
    '#weight'    => $form_weight++,
  );
  

  $form['addpubipv4'] = array(
        '#type' => 'image_button',
        '#src'=> drupal_get_path('module', 'guifi').'/icons/ipv4-public-new.png',
//        '#parents' => array('addipv4'),
        '#attributes' => array('title' => t('Get a new IPv4 subnetwork range (10.x.x.x) publicly available')),
        '#ahah' => array(
          'path' => 'guifi/js/add-ipv4s/public',
          'wrapper' => 'add-ipv4s-dialog',
          'method' => 'replace',
          'effect' => 'fade',
         ),
         '#prefix' => '<div style="clear: both">&nbsp;<hr>&nbsp;</div>',
         '#weight' => $form_weight++,
  );

  $form['addprivipv4'] = array(
        '#type' => 'image_button',
        '#src'=> drupal_get_path('module', 'guifi').'/icons/ipv4-private-new.png',
//        '#parents' => array('ipv4','addipv4'),
        '#attributes' => array('title' => t('Get a new IPv4 private subnetwork range (172.x.x.x) for internal links/puropses only')),
        '#ahah' => array(
          'path' => 'guifi/js/add-ipv4s/private',
          'wrapper' => 'add-ipv4s-dialog',
          'method' => 'replace',
          'effect' => 'fade',
         ),
         '#weight' => $form_weight++,
  );
  $form['addexistentipv4'] = array(
        '#type' => 'image_button',
        '#src'=> drupal_get_path('module', 'guifi').'/icons/ipv4-new.png',
//        '#parents' => array('addipv4'),
        '#attributes' => array('title' => t('Get a new IPv4 address from an subnetwork already defined')),
        '#ahah' => array(
          'path' => 'guifi/js/add-ipv4s/defined',
          'wrapper' => 'add-ipv4s-dialog',
          'method' => 'replace',
          'effect' => 'fade',
         ),
         '#weight' => $form_weight++,
  );

  return $form;
}


function guifi_device_ipv4_link_form($ipv4,$tree, $cable = TRUE) {

  $ki = $tree[count($tree)-3];
  $ka = $tree[count($tree)-1];


  if (count($tree)>4)
    $rk = $tree[1];
  else
    $rk = NULL;

  guifi_log(GUIFILOG_TRACE,'guifi_device_ipv4_link_form()',$ipv4);

  $f['storage']['ipv4_local'] = guifi_form_hidden_var(
    $ipv4,
    array('interface_id','ipv4_type'),
    $tree
  );

  if ((count($ipv4['links']) > 1)
    or (($ipv4['netmask'] != '255.255.255.252')
      and (count($ipv4['links']) < 2) )
    )
  {
    // multilink set
    $multilink = TRUE;
    $f['local'] = array(
      '#type' => 'fieldset',
      '#parents' => $tree,
      '#title' => $ipv4['ipv4'].' / '.
        $ipv4['netmask'].' - '.
        (count($ipv4['links'])).' '.
        t('link(s)'),
      '#collapsible' => TRUE,
      '#collapsed' => !isset($ipv4['unfold']),
    );

    if ($cable)
      $f['local']['AddCablePublicLocalLink'] = array(
        '#type' => 'image_button',
        '#src' => drupal_get_path('module', 'guifi').'/icons/addcable.png',
        '#parents' => array_merge($tree,array('AddCablePublicLocalLink')),
        '#attributes' => array('title' => t('Link to another device using a public IPv4 address')),
        '#ajax' => array(
          //'path' => 'guifi/js/add-cable-link/'.$ipv4['interface_id'].','.$ipv4['id'],
          'callback' => 'guifi_ajax_add_cable_local_link',
          'wrapper' => 'editInterface-'.$ipv4['interface_id'].'-'.$ipv4['id'],
          'method' => 'replace',
          'effect' => 'fade',
        ),
        '#prefix'=> '<div id="'.
        'editInterface-'.$ipv4['interface_id'].'-'.$ipv4['id'].'">',
        '#suffix'=> '</div>',
        //     '#submit' => array('guifi_radio_add_wds_submit'),
      );

      $f['local']['CreateCableLink'] = array(
        'to_did' => array(
          '#type' => 'select',
          '#name' => 'selectCreateCableLink-'.$ipv4['interface_id'].'-'.$ipv4['id'],
          '#id' => 'selectCreateCableLink-'.$ipv4['interface_id'].'-'.$ipv4['id'],
          '#parents'=> array('interface', $ipv4['interface_id'], 'ipv4', $ipv4['id'], 'to_did'),
          '#attributes' => array('hidden' => ''),
          '#prefix'=> '<div id="editInterface-'.$ipv4['interface_id'].'-'.$ipv4['id'].'">',
        ),
        'createLink' => array(
          '#type' => 'button',
          '#value' => t('Create'),
          '#name' => 'createCreateCableLink-'.$ipv4['interface_id'].'-'.$ipv4['id'],
          '#attributes' => array('type' => 'button', 'id' => 'createCreateCableLink-'.$ipv4['interface_id'].'-'.$ipv4['id'], 'hidden' => ''),
          '#parents' => array('interface', $ipv4['interface_id'], 'ipv4', $ipv4['id'], 'createLink'),
          '#submit' => array('guifi_interfaces_add_cable_public_link_submit'),
          '#executes_submit_callback' => TRUE,
          '#suffix' => '</div>',
        ),
      );

    if ($ipv4['deleted'])
      $f['local']['deleteMsg'] = array(
        '#type' => 'item',
        '#value' => t('Deleted'),
        '#description' => guifi_device_item_delete_msg(
          'This IPv4 address has been deleted, ' .
          'related links will be also deleted'),
      );

    $f['local']['id'] = array(
        '#type'=> 'hidden',
        '#parents' => array_merge($tree,array('id')),
        '#default_value' => $ipv4['id'],
//        '#prefix' => '<table style="width: 0"><tr>',
    );

    if ((user_access('administer guifi networks'))
         and (!isset($ipv4['deleted']))
       ) {
      $f['local']['ipv4'] = array(
        '#type'=> 'textfield',
        '#parents' => array_merge($tree,array('ipv4')),
        '#size'=> 16,
        '#maxlength' => 16,
        '#default_value' => $ipv4['ipv4'],
        '#title' => t('Local IPv4'),
        '#prefix'=> '<td>',
        '#suffix'=> '</td>',
 //       '#weight'=> 0,
      );
      $f['local']['netmask'] = array(
        '#type' => 'select',
        '#parents' => array_merge($tree,array('netmask')),
        '#title' => t("Network mask"),
        '#default_value' => $ipv4['netmask'],
        '#options' => guifi_types('netmask',32,0),
        '#prefix'=> '<td align="left">',
        '#suffix'=> '</td>',
//        '#weight' => 1,
      );
    } else {
      $f['local']['ipv4'] = array(
        '#type' => 'value',
        '#parents' => array_merge($tree,array('ipv4')),
        '#value' => $ipv4['ipv4']);
      $f['local']['netmask'] = array(
        '#type' => 'value',
        '#parents' => array_merge($tree,array('netmask')),
        '#value' => $ipv4['netmask']);

      $f['local']['ipv4_display'] = array(
        '#type' => 'item',
        '#parents' => array_merge($tree,array('ipv4')),
        '#title' => t('Local IPv4'),
        '#value'=>  $ipv4['ipv4'],
        '#element_validate' => array('guifi_validate_ip'),
        '#description'=> $ipv4['netmask'],
        '#prefix'=> $prefix,
        '#prefix'=> '<td align="left">',
        '#suffix'=> '</td>',
//        '#weight' => 0,
      );
    }
  } else {
     // singlelink set
     $multilink = FALSE;
  }

  // Deleting the IP address
  if (($multilink) and (!$ipv4['deleted'])) {
    $f['local']['delete_address'] = array(
      '#type' => 'image_button',
      '#src' => drupal_get_path('module', 'guifi').'/icons/drop.png',
      '#parents' => array_merge($tree,array('delete_address')),
      '#attributes' => array('title' => t('Delete address')),
      '#submit' => array('guifi_ipv4_delete_submit'),
      '#prefix'=> '<td>',
      '#suffix'=> '</td>',
    );
  } else if (($ipv4['deleted']) and (!multilink)) {
    $f['local']['delete_address'] = array(
      '#type' => 'item',
      '#description'=> guifi_device_item_delete_msg("Address deleted:"),
      '#prefix'=> '<td>',
      '#suffix'=> '</td>',
    );
  }

  $f['local']['beginTable'] = array(
    '#value'=> '<table style="width: 0"><tr>',
    '#weight' => -99
  );
  $f['local']['endTable'] = array(
    '#value' => '</tr></table>'
  );

  // foreach link
  if (count($ipv4['links'])) foreach($ipv4['links'] as $kl => $link)  {

//    if ($link['deleted'])
//      continue;

     // linked node-device
    $f['local']['links'][$kl] =
      guifi_links_form(
        $link,
        $ipv4,
        $tree,
        $multilink);
  } // foreach link

  return $f;

}


/* guifi_ipv4_link_form(): edit an ipv4 within a link */

function guifi_ipv4_link_form(&$f,$ipv4,$interface,$tree,&$weight) {
  global $definedBridgeIpv4;

  $ki = $tree[count($tree)-3];
  $ka = $tree[count($tree)-1];
  if (count($tree)>4)
    $rk = $tree[1];
  else
    $rk = NULL;

  if ($interface['interface_type'] == 'wLan/Lan')
    $bridge = TRUE;
  if (($ipv4['netmask'] != '255.255.255.252')
    or (count($ipv4['links']) == 0))
  {
    // multilink set
    $multilink = TRUE;
    $f['local'] = array(
      '#type' => 'fieldset',
      '#parents' => $tree,
      '#title' => $ipv4['ipv4'].' / '.
        $ipv4['netmask'].' - '.
        (count($ipv4['links'])).' '.
        t('link(s)'),
      '#weight' => $weight++,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#weight' => $weight++,
    );
    $prefix = '<table><tr><td>';
    $f['local']['id'] = array(
        '#type'=> 'hidden',
        '#parents' => array_merge($tree,array('id')),
        '#default_value' => $ipv4['id']);
    if (user_access('administer guifi networks')) {
      $f['local']['ipv4'] = array(
        '#type'=> 'textfield',
        '#parents' => array_merge($tree,array('ipv4')),
        '#size'=> 16,
        '#maxlength' => 16,
        '#default_value' => $ipv4['ipv4'],
        '#title' => t('Local IPv4'),
        '#prefix'=> $prefix,
        '#suffix'=> '</td>',
        '#weight'=> 0,
      );
      $f['local']['netmask'] = array(
        '#type' => 'select',
        '#parents' => array_merge($tree,array('netmask')),
        '#title' => t("Network mask"),
        '#default_value' => $ipv4['netmask'],
        '#options' => guifi_types('netmask',30,0),
        '#prefix'=> '<td>',
        '#suffix'=> '</td>',
        '#weight' => 1,
      );
    } else {
      $f['local']['ipv4'] = array(
        '#type' => 'item',
        '#parents' => array_merge($tree,array('ipv4')),
        '#title' => t('Local IPv4'),
        '#value'=>  $ipv4['ipv4'],
        '#description'=> $ipv4['netmask'],
        '#prefix'=> $prefix,
        '#suffix'=> '</td>',
        '#weight' => 0,
      );
    }
  } else {
     // singlelink set
     $multilink = FALSE;
     $prefix = '<td>';
  }

  // foreach link
  if (count($ipv4['links'])) foreach($ipv4['links'] as $kl => $link)  {
    if ($link['deleted'])
      continue;

     // linked node-device
    guifi_link_form(
      $f['links'][$kl],
      $link,
      $ipv4,
      $tree,
      $multilink);

  } // foreach link

  // Deleting the IP address
  switch ($interface['interface_type']) {
  case 'wds/p2p':
    break;
  case 'wLan/Lan':
    if (!$definedBridgeIpv4) {
      $f['local']['delete_address'] = array(
        '#type' => 'item',
        '#parents' => array_merge($tree,array('comment_address')),
        '#value' => t('Main public address'),
        '#description' => t('wLan/Lan public IP address is required. No delete allowed.'),
        '#prefix'=> '<td>',
        '#suffix'=> '</td></tr></table>',
        '#description' => t('Can\'t delete this address. The device should have at least one public IP address.'),
        '#weight' =>  3,
      );
      $definedBridgeIpv4 = TRUE;
      break;
    }
  default:
    $f['local']['delete_address'] = array(
      '#type' => 'image_button',
      '#src' => drupal_get_path('module', 'guifi').'/icons/drop.png',
      '#parents' => array_merge($tree,array('delete_address')),
      '#attributes' => array('title' => t('Delete ipv4 address')),
      '#submit' => array('guifi_ipv4_delete_submit'),
      '#prefix'=> '<td>',
      '#suffix'=> '</td></tr></table>',
      '#weight' => 3
      // parameters $rk, $ki, $ka, $ipv4, $netmask
    );
  }  // switch $it (interface_type)

  return count($ipv4['links']);
}


/* _guifi_delete_ipv4_submit(): Action */

function guifi_ipv4_delete_submit(&$form,&$form_state) {
  $values = $form_state['clicked_button']['#parents'];
  $k = count($values);

  $ka = $values[$k -2]; // ipv4#
  $ki = $values[$k -4]; // interface#
  if ($k == 7) {
    $rk = $values[$k -6]; // radio#
    $form_state['values']['radios'][$rk]['unfold'] = TRUE;
    $interface =
      &$form_state['values']['radios'][$rk]['interfaces'][$ki];
  } else
    $rk = -1;
    $interface =
      &$form_state['values']['interfaces'][$ki];

  guifi_log(GUIFILOG_TRACE,
    sprintf('function _guifi_delete_ipv4_submit(radio %d, interface %d, address: %d)',
      $rk,$ki,$ka),
    $form_state['clicked_button']['#parents']);

  $form_state['rebuild'] = TRUE;
  $interface['unfold'] = TRUE;
  $interface['ipv4'][$ka]['unfold'] = TRUE;
  $interface['ipv4'][$ka]['deleted'] = TRUE;

  if ($interface['ipv4'][$ka]['new'])
    unset($interface['ipv4'][$ka]);

  return;
}

?>
