<?php
/**
 * @file guifi_ahah.inc.php
 * Created on 01/06/2008
 * Functions for Asynchronous HTTP and HTML (AHAH) at some forms
 *
 */

/**
 * @param $fields
 *
 * @param $name
 *
 * @return
 *
 * @todo unused?
 */
function guifi_ahah_render_newfields($fields, $name) {
  $form_state = array('submitted' => FALSE);
  $form_build_id = $_POST['form_build_id'];
  // Add the new element to the stored form. Without adding the element to the
  // form, Drupal is not aware of this new elements existence and will not
  // process it. We retreive the cached form, add the element, and resave.
  $form = form_get_cache($form_build_id, $form_state);
  $form[$name] = $fields;
  form_set_cache($form_build_id, $form, $form_state);
  $form += array(
    '#post' => $_POST,
    '#programmed' => FALSE,
  );
  // Rebuild the form.
  $form = form_builder($_POST['form_id'], $form, $form_state);

  // Render the new output.
  $new_form = $form[$name];
  return drupal_render($new_form);
}

/**
 *
 * @param $field
 *
 * @todo unused?
 */
function guifi_ahah_render_field($field){
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');

  if ($cache) {
    $form = $cache->data;

    // Validate the firmware.
    $form['replacedField'] = $field;
    cache_set($cid, $form, 'cache_form', $cache->expire);

    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
    $form['#post'] = array();
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    $output = drupal_render($form['replacedField']);
    drupal_json(array('status' => TRUE, 'data' => $output));
  }
  else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}

/**
 * Select server
 *
 * URL: http://guifi.net/guifi/js/select-server/%
 */
function guifi_ahah_select_server(){
  $matches = array();

  $string = strtoupper(arg(3));

  $qry = db_query('SELECT ' .
                  '  CONCAT(d.id,"-",z.nick,", ",l.nick," ",d.nick) str '.
                  'FROM {guifi_devices} d, {guifi_location} l, {guifi_zone} z ' .
                  'WHERE d.type IN ("server","cam") ' .
                  '  AND d.nid=l.id AND l.zone_id=z.id ' .
                  '  AND (UPPER(CONCAT(d.id,"-",z.nick,", ",l.nick," ",d.nick) LIKE "%'.
                       strtoupper($string).'%")'.
                  '  OR (l.id like "%'.$string.'%"'.
                  '  OR l.nick like "%'.$string.'%"'.
                  '  OR d.nick like "%'.$string.'%"'.
                  '  OR z.nick like "%'.$string.'%"))'
                 );
  $c = 0;
  $na = t('Not assigned');
  $matches[$na] = $na;
  while (($value = db_fetch_array($qry)) and ($c < 50)) {
    $c++;
    $matches[json_encode($value['str'],JSON_UNESCAPED_UNICODE)] =
       json_encode($value['str'],JSON_UNESCAPED_UNICODE);
  }
  print drupal_to_js($matches);
  exit();
}

/**
 * Select service
 *
 * URL: http://guifi.net/guifi/js/select-service/%/%
 *
 */
function guifi_ahah_select_service(){
  $matches = drupal_map_assoc(array(t('No service'),t('Take from parents')));

  $service_type = arg(3);
  $string = strtoupper(arg(4));

  $qry = db_query('SELECT ' .
                  '  CONCAT(s.id,"-",z.title,", ",s.nick) str '.
                  'FROM {guifi_services} s, {guifi_zone} z ' .
                  'WHERE s.service_type like "%'.$service_type.'%" ' .
                  '  AND s.zone_id=z.id ' .
                  '  AND (UPPER(CONCAT(s.id,"-",z.title,", ",s.nick) LIKE "%'.
                       $string.'%")'.
                  '  OR (s.id like "%'.$string.'%"'.
                  '  OR s.nick like "%'.$string.'%"'.
                  '  OR z.title like "%'.$string.'%"))'
                 );
  $c = 0;
  while (($value = db_fetch_array($qry)) and ($c < 50)) {
    $c++;
    $matches[$value['str']] = $value['str'];
  }
  print drupal_to_js($matches);
  exit();
}


/**
 * Select user
 *
 * URL: http://guifi.net/guifi/js/select-user/%
 */
function guifi_ahah_select_user(){
  $matches = array();

  $string = strtoupper(arg(3));

  $qry = db_query('SELECT ' .
                  '  CONCAT(u.uid,"-",u.name," (",u.mail,")") str '.
                  'FROM {users} u '.
                  'WHERE (UPPER(CONCAT(u.uid,"-",u.name," (",u.mail,")")) ' .
                  ' like "%'.$string.'%")'
                 );
  $c = 0;
  while (($value = db_fetch_array($qry)) and ($c < 50)) {
    $c++;
    $matches[$value['str']] = $value['str'];
  }
  print drupal_to_js($matches);
  exit();
}


/**
 * Select node
 *
 * URL: http://guifi.net/guifi/js/select-node/%
 */
function guifi_ahah_select_node(){
  $matches = array();

  $string = strtoupper(arg(3));

  $qry = db_query('SELECT ' .
                  '  CONCAT(l.id,"-",z.nick,", ",l.nick) str '.
                  'FROM {guifi_location} l, {guifi_zone} z ' .
                  'WHERE l.zone_id=z.id ' .
                  '  AND (UPPER(CONCAT(l.id,"-",z.nick,", ",l.nick) LIKE "%'.
                       $string.'%")'.
                  '  OR (l.id like "%'.$string.'%"'.
                  '  OR l.nick like "%'.$string.'%"'.
                  '  OR z.nick like "%'.$string.'%"))'
                 );
  $c = 0;
  while (($value = db_fetch_array($qry)) and ($c < 50)) {
    $c++;
    $matches[$value['str']] = $value['str'];
  }
  print drupal_to_js($matches);
  exit();
}

/**
 * Select device by node/zone/device nickname
 *
 * URL: http://guifi.net/guifi/js/select-node-device/%
 */
function guifi_ahah_select_node_device(){
  $matches = array();

  $string = strtoupper(arg(3));

  $qry = db_query('SELECT
                     CONCAT(d.id,"-",d.nick," '.t('at').' ",z.title,", ",l.nick) str
                  FROM {guifi_location} l, {guifi_zone} z, {guifi_devices} d
                  WHERE l.zone_id=z.id AND l.id=d.nid
                    AND (UPPER(CONCAT(d.id,"-",d.nick," '.t('at').' ",z.title,", ",l.nick) LIKE "%'.
                       $string.'%")'.
                  '  OR (d.id like "%'.$string.'%"'.
                  '  OR l.nick like "%'.$string.'%"'.
                  '  OR d.nick like "%'.$string.'%"'.
                  '  OR z.title like "%'.$string.'%"))'
                 );
  $c = 0;
  while (($value = db_fetch_array($qry)) and ($c < 50)) {
    $c++;
    $matches[$value['str']] = $value['str'];
  }
  print drupal_to_js($matches);
  exit();
}

/**
 * Select zone
 *
 * URL: http://guifi.net/guifi/js/select-zone/%
 */
function guifi_ahah_select_zone() {
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');

  $fname = arg(3);

  $zid = $_POST[$fname];

  if ($cache) {
    $form = $cache->data;

    // zid field
    $form[$fname] =
      guifi_zone_select_field($zid,$fname);

    cache_set($cid, $form, 'cache_form', $cache->expire);
    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
    $form['#post'] = array();
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    $output = drupal_render($form[$fname]);

    drupal_json(array('status' => TRUE, 'data' => $output));
  } else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}

/**
 * Select device
 *
 * URL: http://guifi.net/guifi/js/select-device/%
 */
function guifi_ahah_select_device() {
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');

  $action = arg(3);

  if ($cache) {
    $form = $cache->data;

    if ($action == 'guifi_node_distances') {
      $node = guifi_node_load($_POST['filters']['from_node']);
      $form['list-devices'] =
        guifi_node_distances_list($_POST['filters'],$node);
    } else
      $form['list-devices'] =
        guifi_devices_select($_POST['filters'],$action);

    cache_set($cid, $form, 'cache_form', $cache->expire);
    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
    $form['#post'] = array();
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    $output = drupal_render($form['list-devices']);
    drupal_json(array('status' => TRUE, 'data' => $output));
  } else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}

/**
 * Add wireless radio
 *
 * URL: http://guifi.net/guifi/js/add-radio
 */
function guifi_ahah_add_radio() {
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');

  if ($cache) {
    $form = $cache->data;

    $form['r']['newRadio'] = guifi_radio_add_radio_form($_POST);

    cache_set($cid, $form, 'cache_form', $cache->expire);
    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
    $form['#post'] = array();
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    $output = drupal_render($form['r']['newRadio']);
    drupal_json(array('status' => TRUE, 'data' => $output));
  } else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}

/**
 * Add cable link
 *
 * URL: http://guifi.net/guifi/js/add-cable-link/%
 */
function guifi_ahah_add_cable_link() {
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');

  $values = explode(',',arg(3));
  $interface_id = $values[0];
  if (count($values)==2) {
    // create the cable link on an already allocated subnetwork
    $ipv4_id = $values[1];
    $submit =  array('guifi_interfaces_add_cable_public_link_submit');
    $parents = array('interfaces',$interface_id,'ipv4',$ipv4_id);
  } else {
    // create the cable link over an interface, with a backbone p2p network
    $submit =  array('guifi_interfaces_add_cable_p2p_link_submit');
    $parents = array('interfaces',$interface_id);
  }

  $node = explode('-',$_POST['movenode']);

  $orig_device_id = $_POST['id'];

  $qry = db_query('SELECT id, nick ' .
                  'FROM {guifi_devices} ' .
                  'WHERE nid=%d',
                  $node[0]);

  while ($value = db_fetch_array($qry)) {
    if (!($value['id']==$orig_device_id))
      $list[$value['id']] = $value['nick'];
  }

  if (count($_POST['interfaces'])) foreach ($_POST['interfaces'] as $iid => $intf)
    if (count($intf['ipv4'])) foreach ($intf['ipv4'] as $i => $ipv4)
      if (count($ipv4['links'])) foreach ($ipv4['links'] as $l => $link) {
        if (isset($list[$link['device_id']]))
          unset($list[$link['device_id']]);
      }

  if ($cache) {
    $form = $cache->data;

    if ($node[0] != $_POST['nid']) {
      $f['msg'] = array(
        '#type' => 'item',
        '#title' => t('Device node changed. Option not available'),
        '#description' => t('Can\'t link this device to another device ' .
          'since has been changed the assigned node.<br />' .
          'To link the device to a device defined at another node, ' .
        'you should save the node of this device before proceeding.')
      );
    } else if (count($list)) {
      $tree = $parents;
      $tree[] = 'to_did';
      $f['to_did'] = array(
        '#type' => 'select',
        '#parents'=> $tree,
        '#title' => t('Link to device'),
        '#description' => t('Select the device which you want to link with'),
        '#options' => $list,
        '#prefix' => '<table style="width: 0"><td align="left">',
        '#suffix' => '</td>'
      );
      $tree = $parents;
      $tree[] = 'addLink';
      $f['createLink'] = array(
        '#type' => 'button',
        '#default_value' => 'Create',
        '#parents' => $tree,
        '#submit' => $submit,
        '#executes_submit_callback' => TRUE,
        '#prefix' => '<td align="left">',
        '#suffix' => '</td></table>'
      );
    } else {
      $f['msg'] = array(
        '#type' => 'item',
        '#title' => t('No devices available'),
        '#description' => t('Can\'t link this device to another device ' .
        'since there are no other devices defined on this node.'),
      );
    }

    $form['if']['interfaces']['ifs'][$interface_id]['addLink'] = $f;

    cache_set($cid, $form, 'cache_form', $cache->expire);
    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
    $form['#post'] = array();
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    $output = drupal_render($form['if']['interfaces']['ifs'][$interface_id]['addLink']);
    drupal_json(array('status' => TRUE, 'data' => $output));
  } else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}

/**
 * Add public subnetwork mask
 *
 * URL: http://guifi.net/guifi/js/add-subnet-mask/%
 */
function guifi_ahah_add_subnet_mask() {
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');

  $interface_id = arg(3);

  if ($cache) {
    $form = $cache->data;

    $form['if']['interface'][$interface_id]['ifs']['interface']['selectNetmask'] = array(
        '#type' => 'select',
        '#parents' => array('interface',$interface_id,'newNetmask'),
        '#title' => t("Network mask"),
        '#description' => t('Size of the next available set of addresses to be allocated'),
        '#default_value' => '255.255.255.224',
        '#options' => guifi_types('netmask',30,23),
        '#prefix'=> '<table style="width: 0"><td style="width: 0" align="LEFT">',
        '#suffix'=> '</td>',
      );
    $form['if']['interface'][$interface_id]['ifs']['interface']['createNetmask'] = array(
      '#type' => 'button',
      '#default_value' => 'Create',
      '#parents' => array('interface',$interface_id,'addNetmask'),
      '#submit' => array('guifi_interfaces_add_subnet_submit'),
      '#executes_submit_callback' => TRUE,
      '#prefix' => '<td align="left">',
      '#suffix' => '</td></table>'
    );

    cache_set($cid, $form, 'cache_form', $cache->expire);
    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
    $form['#post'] = array();
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    $output = drupal_render($form['if']['interface'][$interface_id]['ifs']['interface']);
    drupal_json(array('status' => TRUE, 'data' => $output));
  } else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}

/**
 * Move device
 *
 * URL: http://guifi.net/guifi/js/move-device/%
 */
function guifi_ahah_move_device() {
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');

  $radio_id = arg(3);
  $node = explode('-',$_POST['movenode']);
  $orig_device_id = $_POST['id'];

  $qry = db_query('SELECT id, nick ' .
                  'FROM {guifi_devices} ' .
                  'WHERE nid=%d' .
                  ' AND type = "radio" ',
//                  ' AND id<>%d',
                  $node[0]);

  $list[$orig_device_id] = t('To move this radio to another device, ' .
      'select it from the list');
  while ($value = db_fetch_array($qry)) {
    if ($value['id']==$orig_device_id)
      $value['nick'] = t('To move this radio to another device, ' .
        'select it from the list');
    $list[$value['id']] = $value['nick'];
  }

  if ($cache) {
    $form = $cache->data;

    $form['r'][$radio_id]['moveradio'] = array (
      '#type' => 'fieldset',
      '#collapsible' => FALSE
    );
    if ($node[0] != $_POST['nid']) {
      $form['r'][$radio_id]['moveradio']['msg'] = array(
        '#type' => 'item',
        '#title' => t('Node changed. Option not available'),
        '#description' => t('Can\'t move this radio to another device ' .
            'since there has been changed the assigned node.<br />' .
            'To move the radio to a device defined at another node, ' .
            'you should save the node of this device before proceeding.')
      );
      $form['r'][$radio_id]['moveradio']['to_did'] = array(
        '#type' => 'hidden',
        '#parents'=> array('radios',$radio_id,'to_did'),
        '#value' => $orig_device_id,
      );
    } else if (count($list)>1) {
      $form['r'][$radio_id]['moveradio']['to_did'] = array(
        '#type' => 'select',
        '#parents'=> array('radios',$radio_id,'to_did'),
        '#title' => t('Move radio to device'),
        '#description' => t('Select the device which you want to assign this radio.<br />' .
            'Note that the change will not take effect until the device has been saved.'),
        '#options' => $list,
        '#default_value' => $orig_device_id
      );
    } else {
      $form['r'][$radio_id]['moveradio']['msg'] = array(
        '#type' => 'item',
        '#title' => t('No devices available'),
        '#description' => t('Can\'t move this radio to another device ' .
            'since there are no other devices defined on this node.<br />' .
            'To move the radio to a device defined at another node, ' .
            'you should reassign the node of this device before proceeding.')
      );
      $form['r'][$radio_id]['moveradio']['to_did'] = array(
        '#type' => 'hidden',
        '#parents'=> array('radios',$radio_id,'to_did'),
        '#value' => $orig_device_id,
      );
    }

    cache_set($cid, $form, 'cache_form', $cache->expire);
    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
    $form['#post'] = array();
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    $output = drupal_render($form['r'][$radio_id]['moveradio']);
    drupal_json(array('status' => TRUE, 'data' => $output));
  } else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}

/**
 * Add interface
 *
 * URL: http://guifi.net/guifi/js/add-interface
 */
function guifi_ahah_add_interface() {
  $interfaces = $_POST['interfaces'];

  // Build our new form element.
  $free = guifi_get_free_interfaces($_POST['id'],$_POST);

  $newI['interface_type'] = array_shift($free);
  $newI['new'] = TRUE;
  $newI['unfold'] = TRUE;

  $interfaces[] = $newI;
  end($interfaces);
  $delta = key($interfaces);

  $newI['interface_id'] = $delta;

//  guifi_log(GUIFILOG_TRACE,sprintf('add_interface %d',$delta),$newI);

  $form_element =
    guifi_interfaces_form($newI,array('interfaces',$delta));
//  drupal_alter('form', $form_element, array(), 'guifi_ahah_add_interface');

  // Build the new form.
  $form_state = array('submitted' => FALSE);
  $form_build_id = $_POST['form_build_id'];
  // Add the new element to the stored form. Without adding the element to the
  // form, Drupal is not aware of this new elements existence and will not
  // process it. We retreive the cached form, add the element, and resave.
  $form = form_get_cache($form_build_id, $form_state);
//  $choice_form = $form['if']['interfaces']['ifs'];
  $form['if']['interfaces']['ifs'][$newI['interface_type']][$delta] = $form_element;
  form_set_cache($form_build_id, $form, $form_state);
  $form += array(
    '#post' => $_POST,
    '#programmed' => FALSE,
  );

  // Rebuild the old form.
  $form = form_builder('guifi_device_form', $form, $form_state);

  // Render the new output.
  $choice_form = $form['if']['interfaces']['ifs'];
  unset($choice_form['#prefix'], $choice_form['#suffix']); // Prevent duplicate wrappers.
  unset($choice_form[$newI['interface_type']][$delta]);
  // build new form
  $fs = array();
  $form_element['#post'] = array();
  $form_element = form_builder($form_element['form_id']['#value'] , $form_element, $fs);
  $newfield = drupal_render($form_element);
//  guifi_log(GUIFILOG_BASIC,sprintf('choice_form %d',$delta),htmlspecialchars($newfield));
  $output = theme('status_messages') . drupal_render($choice_form) .
    $newfield;

  drupal_json(array('status' => TRUE, 'data' => $output));
  exit;
}

/**
 * Select firmware by model
 *
 * URL: http://guifi.net/guifi/js/firmware_by_model
 */
function guifi_ahah_select_firmware_by_model(){

  $cid = 'form_'. $_POST['form_build_id'];
//  $bid = $_POST['book']['bid'];
  $cache = cache_get($cid, 'cache_form');
  $mid = $_POST['variable']['model_id'];

  if ($cache) {
    $form = $cache->data;

    // Validate the firmware.
    if (isset($form['radio_settings']['variable']['model_id'])) {
      $form['radio_settings']['variable']['firmware_id'] =
        guifi_radio_firmware_field($_POST['variable']['firmware_id'],
          $mid);
      cache_set($cid, $form, 'cache_form', $cache->expire);

      // Build and render the new select element, then return it in JSON format.
      $form_state = array();
      $form['#post'] = array();
      $form = form_builder($form['form_id']['#value'] , $form, $form_state);
      $output = drupal_render($form['radio_settings']['variable']['firmware_id']);
      drupal_json(array('status' => TRUE, 'data' => $output));
    }
    else {
      drupal_json(array('status' => FALSE, 'data' => ''));
    }
  }
  else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}

/**
 * Select channel by protocol
 *
 * URL: http://guifi.net/guifi/js/channel/%
 *
 * @param $rid
 */
function guifi_ahah_select_channel($rid){
 $rid = arg(3);
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');
  $protocol = $_POST['radios'][$rid]['protocol'];
  $curr_channel = $_POST['radios'][$rid]['channel'];

  if ($cache) {
    $form = $cache->data;

    $form['r']['radios'][$rid]['s']['channel'] =
        guifi_radio_channel_field(
          $rid,
          $curr_channel,
          $protocol);

    cache_set($cid, $form, 'cache_form', $cache->expire);
    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
    $form['#post'] = array();
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    $output = drupal_render($form['r']['radios'][$rid]['s']['channel']);
    drupal_json(array('status' => TRUE, 'data' => $output));
  } else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}

/**
 * Add domain
 *
 * URL: http://guifi.net/guifi/js/add-domain
 */
function guifi_ahah_add_domain() {
  $form_state = array('storage' => NULL, 'submitted' => FALSE);
  $form_build_id = $_POST['form_build_id'];
  $form = form_get_cache($form_build_id, $form_state);

  $args = $form['#parameters'];
  $form_id = array_shift($args);
  $form_state['post'] = $form['#post'] = $_POST;
  $form['#programmed'] = $form['#redirect'] = FALSE;

  drupal_process_form($form_id, $form, $form_state);
  $form = drupal_rebuild_form($form_id, $form_state, $args, $form_build_id);

  $textfields = $form['domain_type_form'];
  $output = drupal_render($textfields);

  // Final rendering callback.
  print drupal_json(array('status' => TRUE, 'data' => $output));
  exit();
}
?>
