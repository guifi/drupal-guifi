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
                      WHERE id = %d',
            $id);
  $edit = db_fetch_array($result);

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
                         'mesh' => t('mesh - for any device in Mesh'));

 // $network_types = array_merge($network_types,guifi_types('adhoc'));

  if (empty($form_state['values'])) {
    // first execution, initializing the form

    // if new network, initialize the zone
	  if ($params['add']) {
		  $zone_id=$params['add'];
      $zone = guifi_zone_load($zone_id);

		  // if is root zone, don't find next value'
		  if ($zone_id != guifi_zone_root()) {


			  // not root zone, fill default values looking to next available range
			  $zone = guifi_zone_load($zone_id);

			  $ndefined = db_fetch_object(db_query('SELECT count(*) c FROM guifi_networks WHERE zone=%d',$zone_id));

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
				  drupal_set_message(t('It was not possible to find %type space for %mask',
					  array('%type' => $network_type,
						  '%mask' => $mask)),
				  'error');
		  } // if is not the root zone

    }

    // if existent network, get the network and edit
    if ($params['edit'])
      $form_state['values'] = db_fetch_array(db_query('SELECT *
                                                       FROM {guifi_networks}
                                                       WHERE id = %d',
          $params['edit']));
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
                        WHERE base = "%s"
                         AND mask = "%s"',
        $form_state['values']['base'],
        $form_state['values']['mask']);
    if (db_affected_rows($result)>0)
      form_set_error('base', t('Network already in use.'));
  }

  $zone = guifi_zone_load($form_state['values']['zone']);

/* NO MORE AD-HOC ZONES
  if (($zone->master != 0) and
      ($zone->zone_mode != 'ad-hoc')) {
    if (!in_array($form_state['values']['network_type'],array('public','backbone')))
      form_set_error('network_type',
        t('Only ad-hoc/mesh or root zones can have ad-hoc/mesh ranges assigned'));
  }
  if (($zone->zone_mode == 'ad-hoc') and
     ($form_state['values']['network_type'] == 'backbone'))
    form_set_error('network_type',
      t('You must specify the protocol for backbone ranges on ad-hoc/mesh zones'));
*/
}

/* outputs the network information data
**/
function guifi_ipv4_print_data($zone,$list = 'parents',$ips_allocated) {
  global $user;

  $header = array(
//    array('data' => t('zone'),'style' => 'text-align: right;'),
    array('data' => t('network')),
    t('start / end'),
    array('data' => t('hosts'),'style' => 'text-align: right;'),
    t('type'),
    t('min / max'),
    array('data' => t('ips used'),'style' => 'text-align: right;'),
    array('data' => t('used %'),'style' => 'text-align: right;'),
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
    $pager = 0;
  }

  if (empty($zones))
    return t('There is no zones to look at');

  $sql = 'SELECT
            zone, id, base, mask, network_type
          FROM {guifi_networks}
          WHERE zone IN ('.implode(',',$zones).')
          ORDER BY FIND_IN_SET(zone,"'.implode(',',$zones).'")';

  $rows = array();

  $result = pager_query($sql,variable_get('guifi_pagelimit', 10));
  $current_zoneid = -1;
  while ($net = db_fetch_object($result)) {
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
    $output .= theme('table', $header, $rows);
    $output .= theme('pager', NULL, variable_get('guifi_pagelimit', 10));
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
      $f['local']['AddCableLink'] = array(
        '#type' => 'image_button',
        '#src' => drupal_get_path('module', 'guifi').'/icons/addcable.png',
        '#parents' => array_merge($tree,array('AddCableLink')),
        '#attributes' => array('title' => t('Link to another device using a public IPv4 address')),
        '#ahah' => array(
          'path' => 'guifi/js/add-cable-link/'.$ipv4['interface_id'].','.$ipv4['id'],
          'wrapper' => 'editInterface-'.$ipv4['interface_id'].'-'.$ipv4['id'],
          'method' => 'replace',
          'effect' => 'fade',
        ),
        '#prefix'=> '<div id="'.
        'editInterface-'.$ipv4['interface_id'].'-'.$ipv4['id'].'">',
        '#suffix'=> '</div>',
        //     '#submit' => array('guifi_radio_add_wds_submit'),
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
