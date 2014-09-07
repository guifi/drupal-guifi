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
                     CONCAT(d.id,"-",d.nick,", ",l.nick,", ",z.title) str
                  FROM {guifi_location} l, {guifi_zone} z, {guifi_devices} d
                  WHERE l.zone_id=z.id AND l.id=d.nid
                    AND (UPPER(CONCAT(d.id,"-",d.nick,", ",l.nick,", ",z.title) LIKE "%'.
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

function guifi_ahah_delete_ipv4()
{
  guifi_ahah_select_device_interfacename(true);
}

/**
 * Select interfacename
 *
 * URL: http://guifi.net/guifi/js/select-device-interfacename/%
 */
function guifi_ahah_select_device_interfacename($delete = false) {
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');

  $ids = explode('-',arg(3));

  $device = explode('-',$_POST['ipv4'][$ids[0]]['subnet'][$ids[1]]['did']);

  guifi_log(GUIFILOG_TRACE,sprintf('guifi_ahah_select_device_interfacename (ids=%s did=%d)',arg(3),$device[0]));
  
  $device_interfaces = guifi_get_device_interfaces($device[0]);

  $rIpv4 = $_POST['ipv4'][$ids[0]]['subnet'][$ids[1]];

  if ($cache) {
    $form = $cache->data;
    
    if (!$delete) {
      $form['ipv4'][$ids[0]]['subnet'][$ids[1]]['iid']['#options'] = $device_interfaces;
      $form['ipv4'][$ids[0]]['subnet'][$ids[1]]['did']['#value'] = guifi_get_devicename($device[0],'large');
      $form['ipv4'][$ids[0]]['subnet'][$ids[1]]['ipv4']['#value'] = $_POST['ipv4'][$ids[0]]['subnet'][$ids[1]]['ipv4'];
    } else {
      $form['ipv4'][$ids[0]]['subnet'][$ids[1]]['deleted']['#type'] = 'hidden';
      $form['ipv4'][$ids[0]]['subnet'][$ids[1]]['deleted']['#value'] = true;
      $form['ipv4'][$ids[0]]['subnet'][$ids[1]]['iid']['#disabled'] = true;
      $form['ipv4'][$ids[0]]['subnet'][$ids[1]]['did']['#disabled'] = true;
      $form['ipv4'][$ids[0]]['subnet'][$ids[1]]['ipv4']['#disabled'] = true;
      drupal_set_message(t('The address below will be DELETED when the device is saved. Press "Reset" to discard changes.'));
    }

    cache_set($cid, $form, 'cache_form', $cache->expire);
    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
    $form['#post'] = $_POST;
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
//    $output = drupal_render($form['ipv4'][$ids[0]]['subnet'][$ids[1]]['iid']);
    $output = theme('status_messages') . drupal_render($form['ipv4'][$ids[0]]['subnet'][$ids[1]]);

    drupal_json(array('status' => TRUE, 'data' => $output));
  } else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}

/**
 * Select DEVICE SUBNETWORKS
 *
 * URL: http://guifi.net/guifi/js/select-device-subnets/%
 */
function guifi_ahah_select_device_subnets() {
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');
  $dname = $_POST[ipv4][ipv4sdialog][adddid];
  $device = explode('-',$dname);

  $sql = sprintf(
      "SELECT ip.ipv4, ip.netmask, ip.ipv4_type, i.id, i.device_id, i.interface_type
       FROM {guifi_ipv4} ip, {guifi_interfaces} i
       WHERE ip.interface_id=i.id
        AND i.device_id=%d",$device[0]);
  guifi_log(GUIFILOG_TRACE,sprintf('guifi_ahah_select_device_subnets (device=%d',$device[0]),$sql);

  $device_snets = array('none'=>t('select address'));
  $qifs = db_query($sql);
  $ips_allocated = guifi_ipcalc_get_ips();
  drupal_set_message(t('obtaining a select list with free address(es) from subnets allocated at %device',
    array('%device'=>$dname)
  ));
  while ($difs = db_fetch_object($qifs)) {
    $ips = _ipcalc($difs->ipv4,$difs->netmask);
    $newip = guifi_ipcalc_find_ip($ips[netid],$difs->netmask,$ips_allocated,false);
    if ($newip)
      $device_snets[$newip] = $newip.' '. t('from').' '.
        $ips[netid].'/'.$ips[maskbits].' - '.$difs->interface_type;
    else {
      if ($difs->ipv4_type == 'public')
        drupal_set_message(t('Network %net/%mask at %int is full',
          array('%net'  => $ips[netid], 
                '%mask' => $ips[maskbits],
                '%int'  => $difs->interface_type,
               )),
            'warning');
    }
  }

  if ($cache) {
    $form = $cache->data;

    $form[ipv4][ipv4sdialog][snet]['#options'] = $device_snets;
    $form[ipv4][ipv4sdialog][snet]['#type'] = 'select';
    $form[ipv4][ipv4sdialog][snet]['#description'] = 
      t('choose an address obtained from').':<br>'.
      $dname;
    cache_set($cid, $form, 'cache_form', $cache->expire);
    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
    $form['#post'] = $_POST;
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    $output = theme('status_messages') . drupal_render($form[ipv4][ipv4sdialog]);

    drupal_json(array('status' => TRUE, 'data' => $output));
  } else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}


/**
 * Select interface
 *
 * URL: http://guifi.net/guifi/js/select-device-interface/%
 */
function guifi_ahah_select_device_interface() {
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');

  $port = arg(3);

  guifi_log(GUIFILOG_TRACE,sprintf('guifi_ahah_select_device_interface (port=%d)',$port),$_POST);

  $device    = $_POST['interfaces'][$port]['did'];
  $dnamel    = guifi_get_devicename($device,'large');
  $dnames    = guifi_get_devicename($device);
  $interface = $_POST['interfaces'][$port]['if'];

  $device_interfaces = guifi_get_device_interfaces($device,$interface);

  guifi_log(GUIFILOG_TRACE,sprintf('guifi_ahah_select_device_interface (port=%d POST) FORM:',$port),$_POST);
  guifi_log(GUIFILOG_TRACE,sprintf('guifi_ahah_select_device_interface (port=%d DEVICE) FORM:',$port),$device);
  guifi_log(GUIFILOG_TRACE,sprintf('guifi_ahah_select_device_interface (port=%d) INTERFACE FORM:',$port),$interface);
  guifi_log(GUIFILOG_TRACE,sprintf('guifi_ahah_select_device_interface (port=%d) INTERFACES FORM:',$port),$device_interfaces	);
  guifi_log(GUIFILOG_TRACE,sprintf('guifi_ahah_select_device_interface (port=%d) CACHE FORM:',$port),($cache)?'TRUE':'FALSE');

  if ($cache) {
    $form = $cache->data;

    $form['interfaces'][$port]['conn']['if']['#options'] = $device_interfaces;
    $form['interfaces'][$port]['conn']['if']['#value'] = $interface;
    $form['interfaces'][$port]['conn']['did']['#value'] = $dnamel;
    $form['interfaces'][$port]['dname']['#value'] = (empty($dnames)) ? '' :
      $dnames.' / '.$device_interfaces[$interface];
    $form['interfaces'][$port]['dname']['#attributes'] = (empty($dnames)) ? array('class'=>'interface-item-available') :
      array('class'=>'interface-item-edited');

    cache_set($cid, $form, 'cache_form', $cache->expire);
    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
    $form['#post'] = $_POST;
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    guifi_log(GUIFILOG_TRACE,sprintf('guifi_ahah_select_device_interface (port=%d) replaced:',$port),$form['interfaces'][$port]['conn']['if']['#options']);
    $output = drupal_render($form['interfaces'][$port]);

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
 * Edit cable connection
 *
 * URL: http://guifi.net/guifi/js/edit-cableconn/%
 */
function guifi_ahah_edit_cableconn() {
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');

  $port = arg(3);
  $interface = $_POST['interfaces'][$port];
  $tree = array('interfaces',$port);
  $dname = guifi_get_devicename($interface['connto_did'],'large');
  guifi_log(GUIFILOG_TRACE,sprintf('guifi_ahah_edit_cableconn (port=%d did) interface:',$port),$interface);
  $device_interfaces = guifi_get_device_interfaces($interface['connto_did'],$interface['connto_iid']);
//  guifi_log(GUIFILOG_FILE,sprintf('guifi_ahah_edit_cableconn (port=%d did) device interfaces:',$port),$device_interfaces);

  $form_weight = -10000;

  if ($cache) {
    $form = $cache->data;

    $form['interfaces'][$port]['conn']['#type']          = 'fieldset';
    $form['interfaces'][$port]['conn']['#attributes']    =  array('class'=>'fieldset-interface-connection');
    $form['interfaces'][$port]['conn']['#description']   =  t('Links to device & interface');
    $form['interfaces'][$port]['conn']['#collapsible']   =  FALSE;
    $form['interfaces'][$port]['conn']['#tree']          =  FALSE;
    $form['interfaces'][$port]['conn']['#collapsed']     =  FALSE;

    unset($form['interfaces'][$port]['conn']['did']['#value']);
    $form['interfaces'][$port]['conn']['did']['#value']  = ($interface['deleted']) ? '' : $dname;
    $form['interfaces'][$port]['conn']['did']['#type']   = 'textfield';

    $form['interfaces'][$port]['conn']['if']['#options'] = $device_interfaces;
    $form['interfaces'][$port]['conn']['if']['#value'] = $interface['connto_iid'];

    guifi_log(GUIFILOG_TRACE,sprintf('guifi_ahah_edit_cableconn (port=%d did) FORM:',$conn),$form['interfaces'][$port]['conn']);

    cache_set($cid, $form, 'cache_form', $cache->expire);
    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
//    $form['#post'] = array();
    $form['#post'] = $_POST;
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    $output = drupal_render($form['interfaces'][$port]['conn']);
    drupal_json(array('status' => TRUE, 'data' => $output));
  } else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}

function guifi_ahah_edit_subnet() {
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');
  
  $SNet = arg(3);
  $ipv4 = $_POST['ipv4'][$SNet];
/*  
  // $tree = array('ipv4',$SNetId);
  guifi_log(GUIFILOG_TRACE,sprintf('guifi_ahah_edit_subnet (id=%d):',$SNet),$ipv4);
  
  if ($cache) {
    $form = $cache->data;
  
    // Render the new output.
    unset($form[ipv4][$SNet][subnet]['#prefix'], 
      $form[ipv4][$SNet][subnet]['#suffix'], // Prevent duplicate wrappers.
      $form[ipv4][$SNet][subnet][$delta]);
    cache_set($cid, $form, 'cache_form', $cache->expire);
    $form[ipv4][$SNet][subnet]['#type'] = 'fieldset';
    guifi_log(GUIFILOG_TRACE,
      sprintf('guifi_ahah_edit_subnet (id=%d):',$SNet),$form[ipv4][$SNet][subnet]);
    // build new form
    $form_state = array();
    $form['#post'] = $_POST;
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    $output = theme('status_messages') . drupal_render($form[ipv4][$SNet][subnet]);

    drupal_json(array('status' => TRUE, 'data' => $output));
  } else {
    drupal_json(array('status' => FALSE, 'data' => ''));

  drupal_json(array('status' => TRUE, 'data' => $output));
  }
  exit;
*/  
  

  // Build our new form element.
  $form_element =
    guifi_ipv4subnet_form($ipv4,$SNet, true);

  // Build the new form.
  $form_state = array('submitted' => FALSE);
  $form_build_id = $_POST['form_build_id'];
  // Add the new element to the stored form. Without adding the element to the
  // form, Drupal is not aware of this new elements existence and will not
  // process it. We retreive the cached form, add the element, and resave.
  $form = form_get_cache($form_build_id, $form_state);
  $choice_form = $form[ipv4][$SNet]['subnet'];
  $form['ipv4'][$SNet]['subnet'] = $form_element;
  form_set_cache($form_build_id, $form, $form_state);
  $form += array(
    '#post' => $_POST,
    '#programmed' => FALSE,
  );

  // Rebuild the old form.
  
  $form = form_builder('guifi_device_form', $form, $form_state);
  //if ($cache) {
  //  $form = $cache->data;
  
  // Render the new output.
  $choice_form = $form[ipv4][$SNet][subnet];
  unset($choice_form['#post']);
  // guifi_log(GUIFILOG_BASIC,sprintf('guifi_ahah_edit_subnet (id=%d):',$SNet),$choice_form);
  unset($choice_form['#prefix'], $choice_form['#suffix']); // Prevent duplicate wrappers.
  unset($choice_form[$delta]);
  cache_set($cid, $form, 'cache_form', $cache->expire);
  $choice_form['#type'] == 'fieldset';
  // build new form
  $fs = array();
  $form_element['#post'] = array();
  $form_element = form_builder($form_element['form_id']['#value'] , $form_element, $fs);
  $newfields = drupal_render($form_element);
//  guifi_log(GUIFILOG_BASIC,sprintf('choice_form %d',$delta),htmlspecialchars($newfield));
  $output = theme('status_messages') . drupal_render($choice_form) .
    $newfield;

  drupal_json(array('status' => TRUE, 'data' => $output));
  
  exit;

}


function guifi_ahah_add_remoteipv4() {
  $SNet = arg(3);
  $ipv4 = $_POST['ipv4'][$SNet];
//  $tree = array('ipv4',$SNetId);
  guifi_log(GUIFILOG_TRACE,sprintf('guifi_ahah_add_remoteipv4 (id=%d):',$SNet),$ipv4[subnet]);

  // find the next free ip address
  $ips = array(ip2long($ipv4[ipv4]));
  foreach ($ipv4[subnet] as $i)
    if ($ip = ip2long($i[ipv4]))
      if (!in_array($ip,$ips))
       $ips[] = $ip;
  sort($ips);
  guifi_log(GUIFILOG_TRACE,sprintf('guifi_ahah_add_remoteipv4 (id=%d):',$SNet),$ips);
    $ipc = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
  $lstart = ip2long($ipc[netstart]);
  $c = 0;
  while ($ips[$c] == ($lstart + $c)) $c++;
  $free = long2ip($lstart + $c);

  // Create new element
  $newI = array(
  	'new'     => true,
    'ipv4'    => $free,
    'netmask' => $ipv4['netmask'],
  );
  drupal_set_message(t('Next available address at %base/%maskbits is %addr',
    array('%addr'=>$free,
      '%base'    =>$ipc[netid],
      '%maskbits'=>$ipc[maskbits])));
  guifi_log(GUIFILOG_TRACE,sprintf('guifi_ahah_add_remoteipv4 (start=%s) new',long2ip($lstart)),$newI);
  guifi_log(GUIFILOG_TRACE,sprintf('guifi_ahah_add_remoteipv4 ipcalc'),$ipc);

  $k = 0;
  while (!empty($ipv4[subnet][$k][ipv4]))
    $k++;

  $ipv4[subnet][$k] = $newI;
  guifi_log(GUIFILOG_FULL,sprintf('guifi_ahah_add_remoteipv4 (id=%d):',$SNet),$ipv4[subnet]);

  // Build our new form element.
  $form_element =
    guifi_ipv4subnet_form($ipv4,$SNet, true);

  // Build the new form.
  $form_state = array('submitted' => FALSE);
  $form_build_id = $_POST['form_build_id'];
  // Add the new element to the stored form. Without adding the element to the
  // form, Drupal is not aware of this new elements existence and will not
  // process it. We retreive the cached form, add the element, and resave.
  $form = form_get_cache($form_build_id, $form_state);
  guifi_log(GUIFILOG_TRACE,sprintf('guifi_ahah_add_remoteipv4 (id=%d) state:',$SNet),$form_state);
  $choice_form = $form[ipv4][$SNet]['subnet'];
  $form['ipv4'][$SNet]['subnet'] = $form_element;
  $choice_form = $form[ipv4][$SNet]['subnet'];
  form_set_cache($form_build_id, $form, $form_state);
  $form += array(
    '#post' => $_POST,
    '#programmed' => FALSE,
  );

  // Rebuild the old form.
  $form = form_builder('guifi_device_form', $form, $form_state);

  // Render the new output.
  $choice_form = $form[ipv4][$SNet][subnet];
  unset($choice_form['#prefix'], $choice_form['#suffix']); // Prevent duplicate wrappers.
  unset($choice_form[$delta]);
  // build new form
//  $fs = array();
//  $form_element['#post'] = array();
//  $form_element = form_builder($form_element['form_id']['#value'] , $form_element, $fs);
//  $newfields = drupal_render($form_element);
//  guifi_log(GUIFILOG_BASIC,sprintf('choice_form %d',$delta),htmlspecialchars($newfield));
  $output = theme('status_messages') . drupal_render($choice_form);

  drupal_json(array('status' => TRUE, 'data' => $output));

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
        '#prefix' => '<div>&nbsp</div><table style="width: 0"><td align="left">',
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
        '#prefix'=> '<div>&nbsp</div><table style="width: 0"><td style="width: 0" align="LEFT">',
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
 * Add ipv4s dialog
 * Used at device/radio edit form, at IPv4 Networking section for adding
 *  Public, Private subnetworks, or get an IP from an already defined subnetwork
 *  at another device 
 *
 * URL: http://guifi.net/guifi/js/add-ipv4s
 */
function guifi_ahah_add_ipv4s() {
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');

  $iClass = arg(3);
  guifi_log(GUIFILOG_TRACE,'guifi_ahah_add_ipv4s('.$iClass.')',$_POST[ipv4][ipv4sdialog][netmask]);
  
  //if (!$net) {
  //  drupal_set_message(t(
  //    'Unable to allocate new range, no networks available'),'warning');
  //  return FALSE;
  //}  

  if ($cache) {
    $form = $cache->data;
    $form_dialog = $form['ipv4']['ipv4sdialog'];

    guifi_log(GUIFILOG_TRACE,'guifi_ahah_add_ipv4s(dialog)',$form_dialog);

    // Dialog fields
    $form['ipv4']['ipv4sdialog']['#type'] = 'fieldset';
    $form['ipv4']['ipv4sdialog']['#collapsed'] = false;
    $form['ipv4']['ipv4sdialog']['#collapsible'] = true;
    switch ($iClass) {
      case 'private':
        $form['ipv4']['ipv4sdialog']['adddid']['#type'] = 'hidden';
        $form['ipv4']['ipv4sdialog']['snet']['#type'] = 'hidden';
        $form['ipv4']['ipv4sdialog']['netmask']['#type'] = 'select';
        $form['ipv4']['ipv4sdialog']['netmask']['#value'] = '255.255.255.252';
        $form['ipv4']['ipv4sdialog']['ipv4_type']['#value'] = 2;
        $form['ipv4']['ipv4sdialog']['netmask']['#options'] = 
          guifi_types('netmask',30,26);
        $form['ipv4']['ipv4sdialog']['netmask']['#description'] = 
          t('%class subnetwork size (mask)', array('%class'=>$iClass));
        $form['ipv4']['ipv4sdialog']['#description'] = 
          t('Obtain a new %class subnetwork range and get an IPv4 address from it',
                   array('%class'=>$iClass));
        break;
      case 'public':
        $form['ipv4']['ipv4sdialog']['adddid']['#type'] = 'hidden';
        $form['ipv4']['ipv4sdialog']['snet']['#type'] = 'hidden';
        $form['ipv4']['ipv4sdialog']['netmask']['#type'] = 'select';
        $form['ipv4']['ipv4sdialog']['netmask']['#value'] = '255.255.255.224';
        $form['ipv4']['ipv4sdialog']['ipv4_type']['#value'] = 1;
        $form['ipv4']['ipv4sdialog']['ipv4']['#type'] = 'textfield';
        $form['ipv4']['ipv4sdialog']['netmask']['#options'] = 
          guifi_types('netmask',30,22);
        $form['ipv4']['ipv4sdialog']['netmask']['#description'] = 
          t('%class subnetwork size (mask)',
           array('%class'=>$iClass));
        $form['ipv4']['ipv4sdialog']['#description'] = 
          t('Obtain a new %class subnetwork range and get an IPv4 address from it',
                   array('%class'=>$iClass));
        break;
      case 'netmask':
        $form['ipv4']['ipv4sdialog']['netmask']['#value'] = 
          $_POST[ipv4][ipv4sdialog][netmask];
        break;
      case 'defined':
        $form['ipv4']['ipv4sdialog']['netmask']['#type'] = 'hidden';
        $form['ipv4']['ipv4sdialog']['adddid']['#type'] = 'textfield';
        $form['ipv4']['ipv4sdialog']['snet']['#type'] = 'select';
        $form['ipv4']['ipv4sdialog']['ipv4']['#type'] = 'hidden';
        $form['ipv4']['ipv4sdialog']['#description'] = 
          t('Get a new ip from an already defined subnetwork range at any existing device');
    }
    // Obtaining ipv4
    if ($iClass != 'defined') {
      $ntype = $form['ipv4']['ipv4sdialog']['ipv4_type']['#value']; 
      drupal_set_message(t('Obtaining a new %type range',
        array('%type'  => ($ntype==1) ? t('public') : t('private'))),
        'warning');
      $ips_allocated = guifi_ipcalc_get_ips('0.0.0.0','0.0.0.0', $_POST, $ntype);
      $net = guifi_ipcalc_get_subnet_by_nid(
        $_POST[nid],
        $form['ipv4']['ipv4sdialog']['netmask']['#value'],
        ($ntype == 1) ? 'public' : 'backbone',
        $ips_allocated, 'Yes', TRUE); 
      $form['ipv4']['ipv4sdialog']['ipv4']['#value'] = 
        long2ip(ip2long($net) + 1);
    }
                    

    unset($form['ipv4']['ipv4sdialog']['#prefix']);
    unset($form['ipv4']['ipv4sdialog']['#suffix']);

    guifi_log(GUIFILOG_TRACE,'guifi_ahah_add_ipv4s(dialog)',$form['ipv4']['ipv4sdialog']);

    cache_set($cid, $form, 'cache_form', $cache->expire);
    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
    $form['#post'] = $_POST;
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    $output = theme('status_messages') . drupal_render($form['ipv4']['ipv4sdialog']);
    drupal_json(array('status' => TRUE, 'data' => $output));
  } else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;

  // Build the new form.
  $form_state = array('submitted' => FALSE);
  $form_build_id = $_POST['form_build_id'];
  // Add the new element to the stored form. Without adding the element to the
  // form, Drupal is not aware of this new elements existence and will not
  // process it. We retreive the cached form, add the element, and resave.
  $form = form_get_cache($form_build_id, $form_state);
  $choice_form = $form[ipv4s][ipv4sdialog];
  $form[ipv4s][ipv4sdialog] = $form_dialog;
  form_set_cache($form_build_id, $form, $form_state);
  $form += array(
    '#post' => $_POST,
    '#programmed' => FALSE,
  );

  // Rebuild the old form.
  $form = form_builder('guifi_device_form', $form, $form_state);

  // Render the new output.
  $choice_form = $form[ipv4s][ipv4sdialog];
  unset($choice_form['#prefix'], $choice_form['#suffix']); // Prevent duplicate wrappers.
  unset($choice_form[$delta]);
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
 * Add virtual interface
 *
 * URL: http://guifi.net/guifi/js/add-vinterface
 */
function guifi_ahah_add_vinterface($iClass) {
  $iClass = arg(3);
  $vinterfaces = &$_POST[arg(3)];
  guifi_log(GUIFILOG_TRACE,'guifi_ahah_add_vinterface(iClass)',arg(3));
  guifi_log(GUIFILOG_TRACE,'guifi_ahah_add_vinterface(vinterfaces)',$vinterfaces);

  // Build our new form element.
  $newI['new'] = TRUE;
  // $newI['interface_type'] = $iClass.$delta;

  $delta = count($vinterfaces);
  $newI['id'] = $delta;
  $newI['interface_id'] = $delta;
  $vinterfaces[] = $newI;

  guifi_log(GUIFILOG_TRACE,'guifi_ahah_add_vinterface(newI)',$newI);

  $form_element =
    guifi_vinterface_form($iClass,$newI,!$delta,guifi_get_currentInterfaces($_POST));
//  drupal_alter('form', $form_element, array(), 'guifi_ahah_add_interface');

  // Build the new form.
  $form_state = array('submitted' => FALSE);
  $form_build_id = $_POST['form_build_id'];
  // Add the new element to the stored form. Without adding the element to the
  // form, Drupal is not aware of this new elements existence and will not
  // process it. We retreive the cached form, add the element, and resave.
  $form = form_get_cache($form_build_id, $form_state);
  $choice_form = $form[$iClass][vifs];
  $form[$iClass][vifs][$delta] = $form_element;
  form_set_cache($form_build_id, $form, $form_state);
  $form += array(
    '#post' => $_POST,
    '#programmed' => FALSE,
  );

  // Rebuild the old form.
  $form = form_builder('guifi_device_form', $form, $form_state);

  // Render the new output.
  $choice_form = $form[$iClass]['vifs'];
  unset($choice_form['#prefix'], $choice_form['#suffix']); // Prevent duplicate wrappers.
  unset($choice_form[$delta]);
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
