<?php
/**
 * @file guifi_devices.inc.php
 * Manage guifi_devices
 */

/*
 * guifi_device_load(): get a device and all its related information and builds an array
 */
function guifi_device_load($id,$ret = 'array') {
  guifi_log(GUIFILOG_FULL,'function guifi_device_load()');

  $device = db_fetch_array(db_query('
    SELECT d.*,
           m.model,
           f.nom firmware,
           z.zone_mode, l.nick as node_nick
    FROM {guifi_devices} d
           left join {guifi_model} m on m.mid = d.mid
           left join {guifi_firmware} f on f.id = d.fid,
          {guifi_location} l,
          {guifi_zone} z
    WHERE d.id = %d
     AND d.nid = l.id AND l.zone_id=z.id',
    $id));
  if (empty($device)) {
    drupal_set_message(t('Device (%num) does not exist.',array('%num' => $id)));
    return;
  }
  if (!empty($device['extra']))
    $device['variable'] = unserialize($device['extra']);
  else
    $device['variable'] = array();

  // sobreescribim a l'array variable provinent de extra de model amb els valors de mid i firmware que em venen de la consulta
  // hi afegim el nom del model i el identificador de firmware
  $device['variable']['model_id'] = $device['mid'];
  $device['variable']['model'] = $device['model'];
  $device['variable']['firmware_id'] = $device['fid'];
  $device['variable']['firmware'] = $device['firmware'];

  // getting device radios
  if ($device['type'] == 'radio') {
    // Get radio
    $qr = db_query('
      SELECT *
      FROM {guifi_radios}
      WHERE id = %d
      ORDER BY id, radiodev_counter',
      $id);

    $device['firewall'] = FALSE; // Default: No firewall

    $rc = 0;

    while ($radio = db_fetch_array($qr)) {

      $rc++;
      if (!$device['firewall'])
        if ($radio['mode'] == 'client')
           $device['firewall'] = TRUE;

      $device['radios'][$radio['radiodev_counter']] = $radio;

      // get interface
      $listi = array();
      $qi = db_query('
        SELECT *
        FROM {guifi_interfaces}
        WHERE device_id=%d AND radiodev_counter=%d
        ORDER BY FIND_IN_SET(interface_type,"wLan/Lan,wLan,wds/p2p"),id',
        $id,
        $radio['radiodev_counter']);
      while ($i = db_fetch_array($qi)) {

        // force first interface to wLan/Lan
        if ((!count($listi)) and ($i['interface_type'] == 'wLan') and ($rc==1))
          $i['interface_type'] = 'wLan/Lan';

        // can't have 2 wLan/Lan bridges
        if (in_array($i['interface_type'],$listi))
          if (($i['interface_type']) == 'wLan/Lan')
            $i['interface_type']='wLan';
        $listi[] = $i['interface_type'];

        if ($device['radios'][$radio['radiodev_counter']]['mac'] == '')
          $device['radios'][$radio['radiodev_counter']]['mac'] = $i['mac'];
        $device['radios'][$radio['radiodev_counter']]['interfaces'][$i['id']] = $i;

        // get ipv4
        $ipdec = array();
        $iparr = array();
        $qa = db_query('
          SELECT *
          FROM {guifi_ipv4}
          WHERE interface_id=%d',
          $i['id']);

        while ($a = db_fetch_array($qa)) {
          $ipdec[$a['id']] = ip2long($a['ipv4']);
          $iparr[$a['id']] = $a;
        }
        asort($ipdec);
        $zone = node_load(array('nid' => $iparr[0]['zone_id']));
        $iparr[0]['ospf_zone'] = guifi_get_ospf_zone($zone);

        foreach($ipdec as $ka => $foo) {
          $a = $iparr[$ka];
          $item = _ipcalc($a['ipv4'],$a['netmask']);
          $device['radios'][$radio['radiodev_counter']]['interfaces'][$i['id']]['ipv4'][$a['id']] = $a;
          // barrejem el qeu em ve de ipcalc aixi tinc totes les propietats de les ips definides a dins de (dev)
          $device['radios'][$radio['radiodev_counter']]['interfaces'][$i['id']]['ipv4'][$a['id']] = array_merge($device['radios'][$radio['radiodev_counter']]['interfaces'][$i['id']]['ipv4'][$a['id']],$item);
          // get linked devices
          $qlsql = sprintf('
            SELECT l2.*
            FROM {guifi_links} l1
              LEFT JOIN {guifi_links} l2 ON l1.id=l2.id
            WHERE l2.device_id != %d
              AND l1.device_id=%d
              AND l1.interface_id=%d
              AND l1.ipv4_id=%d',
            $id,
            $id,
            $i['id'],
            $a['id']);
          $ql = db_query($qlsql);

          $ipdec2 = array();
          $iparr2 = array();
          while ($l = db_fetch_array($ql)) {
            $qrasql = sprintf('
              SELECT *
              FROM {guifi_ipv4}
              WHERE id=%d
                AND interface_id=%d',
              $l['ipv4_id'],
              $l['interface_id']);
            $qra = db_query($qrasql);

            while ($ri = db_fetch_array($qra)) {
              $rinterface = db_fetch_array(db_query('
                SELECT *
                FROM {guifi_interfaces}
                WHERE id=%d',
                $l['interface_id']));
              $ipdec2[$l['id']] = ip2long($ri['ipv4']);
              $rinterface['ipv4']=$ri;
              $l['interface']=$rinterface;
              $iparr2[$l['id']] = $l;
            }
          } // each link

          asort($ipdec2);
          foreach ($ipdec2 as $ka2 => $foo) {
            $device['radios'][$radio['radiodev_counter']]['interfaces'][$i['id']]['ipv4'][$a['id']]['links'][$iparr2[$ka2]['id']] = $iparr2[$ka2];
            $device['radios'][$radio['radiodev_counter']]['interfaces'][$i['id']]['ipv4'][$a['id']]['links'][$iparr2[$ka2]['id']]['interface']['ipv4'] = array_merge($device['radios'][$radio['radiodev_counter']]['interfaces'][$i['id']]['ipv4'][$a['id']]['links'][$iparr2[$ka2]['id']]['interface']['ipv4'],array('host_name' => guifi_get_hostname($device['radios'][$radio['radiodev_counter']]['interfaces'][$i['id']]['ipv4'][$a['id']]['links'][$iparr2[$ka2]['id']]['interface']['device_id'])));
            
          }
        }
      }
    }
  }

  // getting other interfaces
  $qi = db_query('
    SELECT *
    FROM {guifi_interfaces}
    WHERE device_id=%d
      AND (radiodev_counter is NULL
      OR interface_type NOT IN ("wLan","wds/p2p","Wan","Hotspot"))
    ORDER BY interface_type, id',
    $id);


  $listi = array();
  while ($i = db_fetch_array($qi)) {

    // can't have 2 wLan/Lan bridges
    if (in_array($i['interface_type'],$listi))
      if (($i['interface_type']) == 'wLan/Lan')
        continue;
    $listi[] = $i['interface_type'];

    $device['interfaces'][$i['id']] = $i;

    // get ipv4
    $ipdec = array();
    $iparr = array();
    $qa = db_query('
      SELECT *
      FROM {guifi_ipv4}
      WHERE interface_id=%d',
      $i['id']);
    while ($a = db_fetch_array($qa)) {
      $ipdec[$a['id']] = ip2long($a['ipv4']);
      $iparr[$a['id']] = $a;
    }

    asort($ipdec);

    foreach($ipdec as $ka => $foo) {
      $a = $iparr[$ka];
      $item = _ipcalc($a['ipv4'],$a['netmask']);
      $device['interfaces'][$i['id']]['ipv4'][$a['id']] = $a;
      
      // barrejem el qeu em ve de ipcalc aixi tinc totes les propietats de les ips definides a dins de (dev)
      $device['interfaces'][$i['id']]['ipv4'][$a['id']] = array_merge($device['interfaces'][$i['id']]['ipv4'][$a['id']],$item);
      

      // get linked devices
      $ql = db_query('
        SELECT l2.*
        FROM {guifi_links} l1
        LEFT JOIN {guifi_links} l2 ON l1.id=l2.id
        WHERE l1.link_type NOT IN ("ap/client","wds/p2p")
        AND l1.device_id=%d
        AND l1.interface_id=%d
        AND l1.ipv4_id=%d
        AND l2.device_id!=%d',
        $id,
        $i['id'],
        $a['id'],
        $id);
      while ($l = db_fetch_array($ql)) {
        $ipdec2 = array();
        $iparr2 = array();
        $qra = db_query('
          SELECT *
          FROM {guifi_ipv4}
          WHERE id=%d
          AND interface_id=%d',
          $l['ipv4_id'],
          $l['interface_id']);
        while ($ra = db_fetch_array($qra)) {
          $ipdec2[$ra['id']] = ip2long($ra['ipv4']);
          $lr = $l;
          $lr['interface'] = db_fetch_array(db_query('
            SELECT *
            FROM {guifi_interfaces}
            WHERE id=%d',
            $l['interface_id']));
          $lr['interface']['ipv4'] = $ra;
          $iparr2[$ra['id']] = $lr;
        } // foreach remote interface

        asort($ipdec2);

        foreach ($ipdec2 as $ka2 => $foo)
          $device['interfaces'][$i['id']]['ipv4'][$a['id']]['links'][$l['id']] =
            $iparr2[$ka2];
      } // foreach link
    } // foreach ipv4
  }

  if ($ret == 'array')
    return $device;
  else {
    foreach ($device as $k => $field)
      $var->$k = $field;
    return array2object($device);
  }
}

function guifi_device_access($op, $id) {
  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_device_access()',$id);
  guifi_log(GUIFILOG_FULL,'user=',$user);

  if ($user->uid==0)
    return FALSE;

  if (empty($id) || ($id < 1))
   return FALSE;

  if (is_array($id))
    $device = $id;
  else
    $device = guifi_device_load($id);

  $node = node_load(array('nid' => $device['nid']));

  switch($op) {
    case 'create':
      return user_access("create guifi nodes");
    case 'update':
      if ((user_access('administer guifi networks')) ||
        (user_access('administer guifi zones')) ||
        ($device['user_created'] == $user->uid) ||
        ($node->uid == $user->uid))
        return TRUE;
      return FALSE;
  }
}

function guifi_device_admin_url($d,$ip) {
  if (is_numeric($d))
    $d = guifi_device_load($d);

  guifi_log(GUIFILOG_TRACE,'function guifi_device_admin_url()',$d['variable']['firmware']);

  if (in_array($d['variable']['firmware'],array(
    'Alchemy','Talisman','DD-guifi','DD-WRTv23'
    )))
    return 'https://'.$ip.':8080';

  return 'http://'.$ip;
}
/*
 * Device edit funcions
 * guifi_device_form_submit(): Performs submit actions
 */
function guifi_device_form_submit($form, &$form_state) {

  guifi_log(GUIFILOG_TRACE,'function guifi_device_form_submit()',
    $form_state);

  if ($form_state['values']['id'])
  if (!guifi_device_access('update',$form_state['values']['id']))
  {
    drupal_set_message(t('You are not authorized to edit this device','error'));
    return;
  }

  // Take the appropiate actions
  switch ($form_state['clicked_button']['#value']) {
  case t('Reset'):
    drupal_set_message(t('Reset was pressed, ' .
        'if there was any change, was not saved and lost.' .
        '<br />The device information has been reloaded ' .
        'from the current information available at the database'));
    drupal_goto('guifi/device/'.$form_state['values']['id'].'/edit');
    break;
  case t('Save & continue edit'):
  case t('Save & exit'):
    // save
//    print_r($_POST);
//    print_r($form_state['values']);
//    exit;
    $id = guifi_device_save($form_state['values']);
//    exit;
    if ($form_state['clicked_button']['#value'] == t('Save & exit'))
      drupal_goto('guifi/device/'.$id);
    drupal_goto('guifi/device/'.$id.'/edit');
    break;
  default:
//     drupal_set_message(t('Warning: The will be active only for this session. To confirm the changes you will have to press the save buttons.'));
    guifi_log(GUIFILOG_TRACE,
      'exit guifi_device_form_submit without saving...',$form_state['clicked_button']['#value']);
    return;
  }

}


/* guifi_device_form(): Present the guifi device main editing form. */
function guifi_device_form($form_state, $params = array()) {
  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_device_form()',$params);

  // Local javascript validations not actve because of bug in Firefox
  // Errors are not displayed when fieldset folder is collapsed
  // guifi_validate_js("#guifi-device-form");
  
  // $form['#attributes'] = array('onsubmit' => 'kk');
  if (empty($form_state['values']))
    $form_state['values'] = $params;

  $form_state['#redirect'] = FALSE;

  // if new device, initializing variables
  if (($form_state['values']['nid'] == NULL) && ($params['add'] != NULL)) {
    $form_state['values']['nid'] = $params['add'];
    $form_state['values']['new'] = TRUE;
    $form_state['values']['type'] = $params['type'];
    $form_state['values']['links'] = array();
    $form_state['values']['netmask'] = '255.255.255.224';
    if ($form_state['values']['type'] == 'radio') {
      $form_state['values']['variable']['firmware_id'] = '13';
      $form_state['values']['variable']['model_id'] = '25';
    }
  }

  drupal_set_breadcrumb(guifi_node_ariadna($form_state['values']['nid']));

  // Check permissions
  if ($params['edit']){
    if (!guifi_device_access('update',$params['edit'])){
      drupal_set_message(t('You are not authorized to edit this device','error'));
      return;
    }
  }

  // Loading node & zone where the device belongs to (some information will be used)
  $node = node_load(array('nid' => $form_state['values']['nid']));
  $zone = node_load($node->zone_id);

  // Setting the breadcrumb
  drupal_set_breadcrumb(guifi_node_ariadna($form_state['values']['nid']));

  // if contact is NULL, then get it from the node or the user logged in drupal
  if (is_null($form_state['values']['notification']))
    if (guifi_notification_validate($node->notification)) {
      $form_state['values']['notification'] = $node->notification;
    } else {
      drupal_set_message(t('The node has not a valid email address as a contact. Using your email as a default. Change the contact mail address if necessary.'));
      $form_state['values']['notification'] = $user->mail;
    }

  // if nick is NULL, get a default name
  if ($form_state['values']['nick'] == "") {
    $form_state['values']['nick'] = guifi_device_get_default_nick($node, $form_state['values']['type'], $form_state['values']['nid'] );
  }

  // if device zone_mode was NULL, get the zone mode (ad-hoc or infrastructure)
  if (is_null($form_state['values']['zone_mode'])) {
    $form_state['values']['zone_mode'] = $zone->zone_mode;

    // DEPRECATED IN FAVOR OF MESH (mesh)
    // That's a new device, because zone_mode was NULL, otherwise would have a value
    // If ad-hoc, add a radio & a public IP (/27)
/*
    if ($zone->zone_mode == 'ad-hoc') {
      if ($form_state['values']['type'] == 'radio') {
        $form_state['values']['newradio_mode'] = $zone->zone_mode;
        guifi_radio_add_radio_submit($form,$form_state);
      } else {
      	$intf=array();
        $intf['new']=TRUE;
        $intf['interface_type']='wLan/Lan';
        $ips_allocated=guifi_ipcalc_get_ips('0.0.0.0','0.0.0.0',$edit,1);
        $net = guifi_ipcalc_get_subnet_by_nid($form_state['values']['nid'],'255.255.255.224','public',$ips_allocated,'Yes', TRUE);
        $i = _ipcalc($net,'255.255.255.224');
        guifi_log(GUIFILOG_TRACE,"IPS allocated: ".count($ips_allocated)." got net: ".$net.'/27',$i);
        $intf['ipv4'][0]=array();
        $intf['ipv4'][0]['new']=TRUE;
        $intf['ipv4'][0]['ipv4_type']=1;
        $intf['ipv4'][0]['ipv4']=$net;
        guifi_log(GUIFILOG_TRACE,"Assigned IP: ".$intf['ipv4'][0]['ipv4']);
        $intf['ipv4'][0]['netmask']='255.255.255.224';
        $form_state['values']['interfaces'][0] = $intf;

      }
    }
*/
  }

  if (isset($form_state['action'])) {
    guifi_log(GUIFILOG_TRACE,'action',$form_state['action']);
    if (function_exists($form_state['action'])) {
      if (!call_user_func_array($form_state['action'],
        array(&$form,&$form_state)))
          return $form;
    }
  }

  $form_weight = -20;

  if ($form_state['values']['id'])
    $form['id'] = array(
      '#type' => 'hidden',
      '#name' => 'id',
      '#value'=> $form_state['values']['id']
    );
  else
    $form['new'] = array(
      '#type' => 'hidden',
      '#name' => 'new',
      '#value'=> TRUE
    );
  $form['type'] = array(
    '#type' => 'hidden',
    '#name' => 'type',
    '#value'=> $form_state['values']['type']
  );
  $form['zone_mode'] = array(
    '#type' => 'hidden',
    '#name' => 'zone_mode',
    '#value'=> $form_state['values']['zone_mode']
  );



//  guifi_form_hidden($form,$form_state['values']);

  if ($params['add'] != NULL){
    drupal_set_title(t('adding a new %device at %node',
      array('%node' => $node->nick,
            '%device' => $form_state['values']['type']
           )));
  } else {
    drupal_set_title(t('edit device %dname',array('%dname' => $form_state['values']['nick'])));
  }

  // All preprocess is complete, now going to create the form

  $form['main'] = array(
    '#type' => 'fieldset',
    '#title' => t('Device name, status and main settings').' ('.
      $form_state['values']['nick'].') - '.$form_state['values']['flag'],
    '#weight' => $form_weight++,
    '#collapsible' => TRUE,
    '#collapsed' => (is_null($params['edit'])),
  );

  $form['main']['movenode'] = array(
    '#type' => 'textfield',
    '#title' => t('Node'),
    '#maxlength' => 60,
    '#default_value' => $form_state['values']['nid'].'-'.
        guifi_get_zone_nick(guifi_get_zone_of_node(
          $form_state['values']['nid'])).', '.
        guifi_get_nodename($form_state['values']['nid']),
    '#autocomplete_path'=> 'guifi/js/select-node',
    '#element_validate' => array('guifi_nodename_validate'),
    '#description' => t('Select the node where the device is.<br />' .
        'You can find the node by introducing part of the node id number, ' .
        'zone name or node name. A list with all matching values ' .
        'with a maximum of 50 values will be created.<br />' .
        'You can refine the text to find your choice.')
  );
  $form['main']['nid'] = array(
    '#type' => 'hidden',
    '#value'=> $form_state['values']['nid'],
    //'#suffix' => '</div>'
  );

  $form['main']['nick'] = array(
    '#type' => 'textfield',
    '#size' => 20,
    '#maxlength' => 128,
    '#title' => t('nick'),
    '#required' => TRUE,
    '#attributes' => array('class' => 'required'),
    '#default_value' => $form_state['values']['nick'],
    '#description' =>  t('The name of the device.<br />Used as a hostname, SSID, etc...')
  );
  $form['main']['flag'] = array(
      '#type' => 'select',
      '#title' => t("Status"),
      '#required' => TRUE,
      '#default_value' => $form_state['values']['flag'],
      '#options' => guifi_types('status'),
      '#description' => t("Current status of this device."),
   );
  $form['main']['notification'] = array(
    '#type' => 'textfield',
    '#size' => 60,
    '#maxlength' => 1024,
    '#title' => t('contact'),
    '#required' => TRUE,
    '#element_validate' => array('guifi_emails_validate'),
    '#default_value' => $form_state['values']['notification'],
    '#description' =>  t('Mailid where changes on the device will be notified, if many, separated by \',\'<br />used for network administration.')
  );

  $form['main']['logserver'] = array(
    '#type' => 'textfield',
    '#size' => 60,
    '#maxlength' => 60,
    '#title' => t('Log Server'),
    '#description' =>  t('If you have a log server for mikrotik (dude), add your ip.')
  );

  if (user_access('administer guifi zones')
       and $form_state['values']['type'] == 'radio') {
    $form['main']['graph_server'] = array(
      '#type' => 'select',
      '#title' => t("Server which collects traffic and availability data"),
      '#required' => FALSE,
      '#default_value' => ($node->graph_server ? $node->graph_server : 0),
      '#options' => array('0' => t('Default'),'-1' => t('None')) + guifi_services_select('SNPgraphs'),
      '#description' => t("If not specified, inherits zone properties."),
    );
  }

  // create the device-type depenedent form
  // looking for a "guifi_"<device_type>"_form()" function
  if (function_exists('guifi_'.$form_state['values']['type'].'_form')){
    $form = array_merge($form,
      call_user_func('guifi_'.$form_state['values']['type'].'_form',
        $form_state['values'],
        $form_weight));
  }

  // Cable interfaces/links
//  foreach ($form_state['values']['interfaces'] as $iid => $interface)
//    $form['if'][$iid] =
//        guifi_device_interface_form($interface,array('interfaces',$iid));
  $form['if'] = guifi_interfaces_cable_form($form_state['values']);

  // Comments
  $form_weight = 200;

  $form['comment'] = array(
    '#type' => 'textarea',
//    '#parents' => 'comment',
    '#title' => t('Comments'),
    '#default_value' => $form_state['values']['comment'],
    '#description' => t('This text will be displayed as an information of the device.'),
    '#cols' => 60,
    '#rows' => 5,
    '#weight' => $form_weight++,
  );

  //  save/validate/reset buttons
  $form['dbuttons'] = guifi_device_buttons(FALSE,'',$form_weight);

  return $form;
}

/* guifi_device_form_validate(): Confirm that an edited device has fields properly filled. */
function guifi_device_form_validate($form,&$form_state) {
//  print "Hola validate!!\n<br />";
//   print_r($edit);

  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_device_form_validate()',$form_state);

//   guifi_log(GUIFILOG_NONE,'function guifi_device_form_validate()',$form);

  // nick
  if (isset($form['main']['nick'])) {
    guifi_validate_nick($form_state['values']['nick']);

    $query = db_query("
      SELECT nick
      FROM {guifi_devices}
      WHERE lcase(nick)=lcase('%s')
       AND id <> %d",
      strtolower($form_state['values']['nick']),
      $form_state['values']['id']);

    while (db_fetch_object($query)) {
      form_set_error('nick', t('Nick already in use.'));
    }
  }

  // ssid
  if (empty($form_state['values']['ssid'])) {
    $form_state['values']['ssid'] = $form_state['values']['nick'];
  }

  // duplicated ip address
  if (!empty($form_state['values']['ipv4'])) {
    if (db_num_rows(db_query("
      SELECT i.id
      FROM {guifi_interfaces} i,{guifi_ipv4} a
      WHERE i.id=a.interface_id AND a.ipv4='%s'
        AND i.device_id != %d",
      $form_state['values']['ipv4'],
      $form_state['values']['id']))) {
      $message = t('IP %ipv4 already taken in the database. Choose another or leave the address blank.',
        array('%ipv4' => $form_state['values']['ipv4']));
      form_set_error('ipv4',$message);
    }
  }

  // no duplicate names on cable interfaces
  $ifs = array();
  if (count($form_state['values']['interfaces']))
  foreach ($form_state['values']['interfaces'] as $iid => $interface) {
    if (in_array($interface['interface_type'],$ifs)) {
      form_set_error("interfaces][$iid][interface_type",
        t('Interface name %name duplicated',
          array('%name' => $interface['interface_type'])));
      break;
    }
    $ifs[] = $interface['interface_type'];
  }
}

/* guifi_device_edit_save(): Save changes/insert devices */
function guifi_device_save($edit, $verbose = TRUE, $notify = TRUE) {
  global $user;
  global $bridge;

  $bridge = FALSE;
  $to_mail = array();
  $tomail[] = $user->mail;
  $log = "";
  $to_mail = array();

  // device
  
  // TODO : corretgir que agafi els midi fid de l'estructura qeu toca dins del edit
  // amb lo de sota els repliquem per poder-hi accedir directament
  $edit['mid'] = $edit['variable']['model_id'];
  $edit['fid'] = $edit['variable']['firmware_id'];

  if ($edit['type'] == 'radio') {
    if (!$edit['variable']['firmware']) {
      $firmware = db_fetch_object(db_query(
        "SELECT id, nom as name " .
        "FROM {guifi_firmware} " .
        "WHERE id = '%d'",
        $edit['fid']));
      $edit['variable']['firmware'] = $firmware->name;
    }
  }

  // TODO REMOVE EXTRA  comprovar que no es serialitzen els camps de mid, fid, etc.
  if ($edit['variable'])
    $edit['extra'] = serialize($edit['variable']);

  // busquem el id de la configuracioUSC per aquests mid i fid
  $sql = db_query('SELECT id as uscid, enabled FROM {guifi_configuracioUnSolclic} WHERE mid=%d and fid=%d ', $edit['mid'], $edit['fid']);
  $configuracio = db_fetch_object($sql);

  $edit['usc_id'] = $configuracio->uscid;
  $ndevice = _guifi_db_sql('guifi_devices',array('id' => $edit['id']),$edit,$log,$to_mail);

  guifi_log(GUIFILOG_TRACE,
    sprintf('device saved:'),
    $ndevice);

  $movenode = explode('-',$edit['movenode']);

  // radios
  $rc = 0;
  if (is_array($edit['radios']))
    ksort($edit['radios']);
  $rc = 0;
  if ($edit['radios']) foreach ($edit['radios'] as $radiodev_counter => $radio) {
    $keys['id'] = $ndevice['id'];
    $keys['radiodev_counter']=$radiodev_counter;
    $radio['id'] = $ndevice['id'];
    $radio['radiodev_counter'] = $rc;
    if ($movenode[0])
      $radio['nid']=$movenode[0];
    $radio['model_id']=$edit['variable']['model_id'];

    // check if device id has changed
    guifi_log(GUIFILOG_TRACE,
      sprintf('Checking radio (from:%d, to: %d): ',
        $radio['id'],$radio['to_id']),
      NULL);
    if (isset($radio['to_did']))

    // if radio has moved to another device:
    // -obtain the radiodev_counter of that device
    // -if has wLan/Lan interface:
    //   -convert to wlan
    //   -don't save it again at cable interfaces section'
    if ($radio['to_did'] != $radio['id']) {

      // -obtain the radiodev_counter of that device
      $radio['id'] = $radio['to_did'];
      $qry = db_query('SELECT  max(radiodev_counter) + 1 rc ' .
                      'FROM {guifi_radios} ' .
                      'WHERE id=%d',
                      $radio['to_did']);
      $nrc = db_fetch_array($qry);
      $radio['radiodev_counter'] = $nrc['rc'];

      drupal_set_message(t('Radio# %id has been moved to radio# %id2 at device %dname',
        array('%id' => $rc,
          '%id2' => $radio['radiodev_counter'],
          '%dname' => guifi_get_hostname($radio['to_did'])
        )));

      // -if has wLan/Lan interface:
      //   -convert to wlan if is not going a be the main radio
      //   -don't save it again at cable interfaces section'
      if ($radio['interfaces']) foreach ($radio['interfaces'] as $iid => $interface)
        if ($interface['interface_type'] == 'wLan/Lan') {
          foreach ($edit['interfaces'] as $ciid => $cinterface)
            // unset from cable section
            if ($cinterface['interface_type']=='wLan/Lan')
              unset($edit['interfaces'][$ciid]);
            // if not radio#0, set as wLan at the other device
            if ($nrc['rc'])
              $radio['interfaces'][$iid]['interface_type'] = 'wLan';
        }
    }

    // save the radio
    $nradio = _guifi_db_sql('guifi_radios',$keys,$radio,$log,$to_mail);
    if ((empty($nradio)) or ($radio['deleted']))
      continue;

    // interfaces
    if ($radio['interfaces']) foreach ($radio['interfaces'] as $interface_id => $interface) {
      $interface['device_id'] = $radio['id'];
      $interface['mac'] = $radio['mac'];
      $interface['radiodev_counter'] = $nradio['radiodev_counter'];

      // force wLan/Lan on radio#0
      if ($interface['interface_type'] == 'wLan/Lan')
        $interface['radiodev_counter'] = 0;

      $log .= guifi_device_interface_save($interface,$interface_id,$edit['id'],$ndevice['nid'],$to_mail);

    } // foreach interface
    $rc++;
  } // foreach radio

  if (!empty($edit['interfaces'])) foreach ($edit['interfaces'] as $iid => $interface) {
    $interface['device_id'] = $ndevice['id'];

    $log .= guifi_device_interface_save($interface,$iid,$edit['id'],$ndevice['nid'],$to_mail);
  }

  $to_mail = explode(',',$edit['notification']);

  if ($edit['new'])
    $subject = t('The device %name has been CREATED by %user.',
      array('%name' => $edit['nick'],
        '%user' => $user->name));
  else
    $subject = t('The device %name has been UPDATED by %user.',
      array('%name' => $edit['nick'],
        '%user' => $user->name));

//   drupal_set_message($subject);
  guifi_notify($to_mail,
    $subject,
    $log,
    $verbose,
    $notify);

  guifi_node_set_flag($edit['nid']);
  guifi_clear_cache($edit['nid']);
  guifi_clear_cache($edit['id']);
  variable_set('guifi_refresh_dns',time());
  return $ndevice['id'];

}

function guifi_device_interface_save($interface,$iid,$did,$nid,&$to_mail) {
  $log = '';

  guifi_log(GUIFILOG_TRACE,sprintf('guifi_device_edit_interface_save (id=%d)',$iid),$interface);

  $ninterface = _guifi_db_sql(
    'guifi_interfaces',
    array('id' => $iid),$interface,$log,$to_mail);
  if (!isset($ninterface['id']))
    $ninterface['id'] = $iid;

  if (empty($ninterface))
    return $log;

  guifi_log(GUIFILOG_TRACE,'SQL interface',$ninterface);
  // ipv4
  if ($interface['ipv4']) foreach ($interface['ipv4'] as $ipv4_id => $ipv4) {
    $ipv4['interface_id'] = $ninterface['id'];
    guifi_log(GUIFILOG_TRACE,sprintf('SQL ipv4 local (id=%d, iid=%d)', $ipv4_id, $ipv4['interface_id']), $ipv4);

    if (($ipv4['netmask']=='255.255.255.252') and (!count($ipv4['links'])))
      $ipv4['deleted'] = TRUE;

    $nipv4 = _guifi_db_sql(
      'guifi_ipv4',
      array('id' => $ipv4_id,'interface_id' => $ipv4['interface_id']),$ipv4,$log,$to_mail);

    if (empty($nipv4) or ($ipv4['deleted']))
      continue;

    // links (local)
    if ($ipv4['links']) foreach ($ipv4['links'] as $link_id => $link) {
      $llink = $link;
      $llink['nid'] = $nid;
      $llink['device_id'] = $interface['device_id'];
      $llink['interface_id'] = $ninterface['id'];
      $llink['ipv4_id'] = $nipv4['id'];
      guifi_log(GUIFILOG_TRACE,'going to SQL for local link',$llink);
      $nllink = _guifi_db_sql(
        'guifi_links',
        array('id' => $link['id'],'device_id' => $did),$llink,$log,$to_mail);
      if (empty($nllink) or ($llink['deleted']))
        continue;

      // links (remote)
      if ($link['interface']) {
        if (!isset($link['interface']['device_id']))
          $link['interface']['device_id'] = $link['device_id'];

        guifi_log(GUIFILOG_TRACE,sprintf('remote interface (id=%d)',
          $link['interface']['id']),
          $link['interface']);
        $rinterface = _guifi_db_sql(
          'guifi_interfaces',
          array('id' => $link['interface']['id'],
            'radiodev_counter' => $link['interface']['radiodev_counter']),
            $link['interface'],$log,$to_mail);
      }
      if ($link['interface']['ipv4']) {
        if ($ipv4['netmask'] != $link['interface']['ipv4']['netmask']) {
          $log .= t('Netmask on remote link %nname - %type was adjusted to %mask',
            array('%nname' => guifi_get_hostname($llink['device_id']),
              '%type' => $interface['interface_type'],
              '%mask' => $ipv4['netmask']));
          $link['interface']['ipv4']['netmask'] = $ipv4['netmask'];
        }
        $link['interface']['ipv4']['interface_id'] = $rinterface['id'];
        $link['interface']['ipv4']['ipv4_type'] = $ipv4['ipv4_type'];

        guifi_log(GUIFILOG_TRACE,sprintf('SQL ipv4 remote (id=%d, iid=%d)',
          $link['interface']['ipv4']['id'],
          $link['interface']['ipv4']['interface_id']),
          $link['interface']);
        $ripv4 = _guifi_db_sql(
          'guifi_ipv4',
          array('id' => $link['interface']['ipv4']['id'],
            'interface_id' => $link['interface']['ipv4']['interface_id']),
            $link['interface']['ipv4'],$log,$to_mail);
      }
      if (!$llink['deleted']) {
        $link['id'] = $nllink['id'];
        $link['ipv4_id'] = $ripv4['id'];
        $link['interface_id'] = $rinterface['id'];
        $nrlink = _guifi_db_sql(
          'guifi_links',
          array('id' => $link['id'],
          'device_id' => $link['device_id']),
          $link,$log,$to_mail);
        guifi_log(GUIFILOG_TRACE,'going to SQL for remote link',$nllink);
      }
    }
  } // foreach ipv4

  return $log;
}

function guifi_device_buttons($continue = FALSE,$action = '', $nopts = 0, &$form_weight = 1000) {
  $form['reset'] = array(
    '#type' => 'button',
    '#executes_submit_callback' => TRUE,
    '#value' => t('Reset'),
    '#weight' => $form_weight++,
  );

  if ($continue) {
    $form['ignore_continue'] = array(
      '#type' => 'button',
      '#executes_submit_callback' => TRUE,
      '#value' => t('Ignore & back to main form'),
      '#weight' => $form_weight++,
    );
    if ($nopts > 0) {
      $form['confirm_continue'] = array(
        '#type' => 'button',
        '#submit' => array($action),
        '#executes_submit_callback' => TRUE,
        '#value' => t('Select device & back to main form'),
        '#weight' => $form_weight++,
      );
    }
    return $form;
  }
  $form['validate'] = array(
    '#type' => 'button',
    '#value' => t('Validate'),
    '#weight' => $form_weight++,
  );
  $form['save_continue'] = array(
    '#type' => 'submit',
    '#value' => t('Save & continue edit'),
    '#weight' => $form_weight++,
  );
  $form['save_exit'] = array(
    '#type' => 'submit',
    '#value' => t('Save & exit'),
    '#weight' => $form_weight++,
  );

  return $form;
}
/* guifi_device_delete(): Delete a device */
function guifi_device_delete_confirm($form_state,$params) {

  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure you want to delete this device?'),
    '#value' => $params['name'],
    '#description' => t('WARNING: This action cannot be undone. The device and it\'s related information will be <strong>permanently deleted</strong>, that includes:<ul><li>The device</li><li>The related interfaces</li><li>The links where this device is present</li></ul>If you are really sure that you want to delete this information, press "Confirm delete".'),
    '#weight' => 0,
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Confirm delete'),
    '#name'  => 'confirm',
    '#weight' => 1,
  );
  drupal_set_title(t('Delete device: (%name)',array('%name' => $params['name'])));

  return $form;
}

function guifi_device_delete($device, $notify = TRUE, $verbose = TRUE) {
  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_device_delete()');

  $to_mail = explode(',',$device['notification']);

  if ($_POST['confirm']) {
    $log = _guifi_db_delete('guifi_devices',
        array('id' => $device['id']),
        $to_mail);
    drupal_set_message($log);

    $subject = t('The device %name has been DELETED by %user.',
      array('%name' => $device['nick'],
        '%user' => $user->name));
    drupal_set_message($subject);
    guifi_notify($to_mail,
      $subject,
      $log,
      $verbose,
      $notify);
    guifi_node_set_flag($device['nid']);

    drupal_goto('node/'.$device['nid']);
  }

  $node = node_load(array('nid' => $device['nid']));
  drupal_set_breadcrumb(guifi_node_ariadna($node));

  $output = drupal_get_form('guifi_device_delete_confirm',
    array('name' => $device['nick'],'id' => $device['id']));
  print theme('page',$output, FALSE);
  return;
}

/* guifi_device_add(): Provides a form to create a new device */
function guifi_device_add() {
  guifi_log(GUIFILOG_TRACE,'function guifi_device_add()');

  $output = drupal_get_form('guifi_device_form',array('add' => arg(3),
                                                    'type' => arg(4)));
  // To gain space, save bandwith and CPU, omit blocks
  print theme('page', $output, FALSE);
}

/* guifi_device_create_form(): generates html output form with a listbox,
 * choose the device type to create
 */
function guifi_device_create_form($form_state, $node) {

  $types = guifi_types('device');

  $zone = guifi_zone_load($node->zone_id);

  if (!guifi_node_access('create',$node->nid)) {
    $form['text_add'] = array(
     '#type' => 'item',
     '#value' => t('You are not allowed to update this node.'),
     '#weight' => 0
   );
   return $form;
  }
  $form['nid'] = array(
    '#type' => 'hidden',
    '#value' => $node->id
  );

  $form['device_type'] = array(
    '#type' => 'select',
    '#title' => t('Add a new device'),
    '#description' => t('Type of device to be created'),
    '#options' => $types,
    '#prefix' => '<table style="width: 0px"><tr><td>',
    '#suffix' => '</td>',
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('add'),
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
  );

  return $form;
}

function guifi_device_create_form_submit($form, &$form_state) {

  $form_state['redirect'] =
    'guifi/device/add/'.$form_state['values']['nid'].
    '/'.$form_state['values']['device_type'];
}

function guifi_device_create($nid) {
  $form = drupal_get_form('guifi_device_create_form',$nid);
  print theme('page',$form);
}

/* guifi_ADSL_form(): Create form for editiong DSL devices */
function guifi_ADSL_form($edit) {

  if (!isset($edit['variable']['download']))
    $edit['variable']['download'] = 4000000;
  if (!isset($edit['variable']['upload']))
    $edit['variable']['upload'] = 640000;
  if (!isset($edit['variable']['mrtg_index']))
    $edit['variable']['mrtg_index'] = 5;

  $form['variable'] = array(
    '#type' => 'fieldset',
    '#title' => t('DSL information & MRTG parameters'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#tree' => TRUE
  );
  $form['variable']['download'] = array(
    '#type' => 'select',
    '#title' => t('Download'),
    '#default_value' => $edit['variable']['download'],
    '#options' => guifi_bandwidth_types(),
    '#description' => t('Download bandwidth')
  );
  $form['variable']['upload'] = array(
    '#type' => 'select',
    '#title' => t('Upload'),
    '#default_value' => $edit['variable']['upload'],
    '#options' => guifi_bandwidth_types(),
    '#description' => t('Upload bandwidth')
  );
  $form['variable']['mrtg_index'] =
    guifi_device_mrtg_form($edit['variable']['mrtg_index']);
  return $form;
}

function guifi_generic_form($edit) {

  $form['variable'] = array(
    '#type' => 'fieldset',
    '#title' => t('DSL information & MRTG parameters'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#tree' => TRUE
  );
  if (!isset($edit['variable']['mrtg_index']))
    $edit['variable']['mrtg_index'] = 4;
  $form['variable']['mrtg_index'] =
    guifi_device_mrtg_form($edit['variable']['mrtg_index']);
  return $form;
}

function guifi_device_mrtg_form($mrtg) {
  return array(
    '#type' => 'textfield',
    '#title' => t('MRTG config'),
    '#default_value' => $mrtg,
    '#size' => 5,
    '#maxlength' => 5,
    '#description' => t('SNMP interface index for getting traffic information ' .
                        'of this device. User tools like cacti or snmpwalk ' .
                        'to determine the index. Example:').
                        '<br /><pre>snmpwalk -Os -c public -v 1 10.138.25.66 ' .
                        'interface</pre>'
  );
}

function guifi_bandwidth_types() {
  return    array(  '64000' => '64k',
                               '128000' => '128k',
                               '256000' => '256k',
                               '512000' => '512k',
                               '640000' => '640k',
                               '1000000' => '1M',
                               '2000000' => '2M',
                               '4000000' => '4M',
                               '8000000' => '8M',
                               '10000000' => '10M',
                               '12000000' => '12M',
                               '20000000' => '20M',
                               '40000000' => '40M');
}

/****************************************
   device output information functions
*****************************************/
/* guifi_device_print_data(): outputs a detailed device information data */
function guifi_device_print_data($device) {

  $radios = db_query(
      'SELECT *
       FROM {guifi_radios}
       WHERE id=%d
       ORDER BY id',
      $device['id']);

  $rows[] = array(t($device[type]),'<b>' .$device[nick] .'</b>');

  // If radio, print model & firmware
  if ($device['type'] == 'radio') {
    $model = db_fetch_object(db_query("
      SELECT model, name
      FROM {guifi_model} m LEFT JOIN {guifi_manufacturer} f ON m.fid=f.fid
      WHERE m.mid=%d",
      $device['variable']['model_id']));
    $rows[] = array($model->model,$device['variable']['firmware']);
    // going to list all device radios
    if (count($device['radios'])) {
      foreach ($device['radios'] as $radio_id => $radio) {
        $rowsr[] = array(
          $radio['ssid'],
          $radio['mode'],
          $radio['protocol'],
          $radio['channel'],
          $radio['mac'],
          $radio['clients_accepted']
        );
      }
      $rows[] =  array(array('data' => theme('table',
        array(t('ssid'),t('mode'),t('protocol'),t('ch'),t('wireless mac'),
            t('clients')),$rowsr),'colspan' => 2));
    }
  }

  // If ADSL, print characteristics
  if (($device['type'] == 'ADSL') and ($device['variable'] != '')) {
    $bandwidth = guifi_bandwidth_types();
    $rows[] = array(t('bandwidth'),$bandwidth[$device['variable']['download']].
            '/'.$bandwidth[$device['variable']['upload']]);
    $rows[] = array(t('SNMP index to graph'),$device['variable']['mrtg_index']);
  }
  if (($device['type'] == 'generic') and ($device['variable'] != '')) {
    $rows[] = array(t('SNMP index to graph'),$device['variable']['mrtg_index']);
  }

  if ($device['graph_server']>0)
    $gs = node_load(array('nid' => $device['graph_server']));
  else
    $gs = node_load(array('nid' => guifi_graphs_get_server($device['id'],'device')));

  $rows[] = array(t('graphs provided from'),array(
    'data' => l(guifi_service_str($device['graph_server']),
              $gs->l, array('attributes' => array('title' => $gs->nick.' - '.$gs->title))),
    'colspan' => 2));

  $ip = guifi_main_ip($device[id]);
  $rows[] = array(t('IP address & MAC'),$ip[ipv4].'/'.$ip[maskbits].' '.$device[mac]);

  $status_url = guifi_cnml_availability(
       array('device' => $device['id'],'format' => 'long'),$gs);

  $rows[] = array(t('status &#038; availability'),array('data' => t($device[flag]).$status_url,'class' => $device['flag']));

  $rows[] = array(array('data' => theme_guifi_contacts($device),'colspan' => 0));

  return array_merge($rows);
}

/* guifi_device_links_print_data(): outputs the device link data, create an array of rows per each link */
function guifi_device_links_print_data($id) {
  $query = db_query("
    SELECT i.*,a.ipv4,a.netmask
    FROM {guifi_interfaces} i, {guifi_ipv4} a
    WHERE i.id=a.interface_id AND i.device_id=%d
    ORDER BY i.interface_type",
    $id);
  while ($if = db_fetch_object($query)) {
    $ip = _ipcalc($if->ipv4,$if->metmask);
    $rows[] = array($if->interface_type,$if->ipv4.'/'.$ip['netid'],$if->netmask,$if->mac);
  }
  return array_merge($rows);
}

/* guifi_device_interfaces_print_data(): outputs the device interfaces data */
function guifi_device_interfaces_print_data($id) {
  $rows = array();
  $query = db_query("
    SELECT i.*,a.ipv4,a.netmask, a.id ipv4_id
    FROM {guifi_interfaces} i, {guifi_ipv4} a
    WHERE i.id=a.interface_id AND i.device_id=%d
    ORDER BY i.interface_type",
    $id);
  while ($if = db_fetch_object($query)) {
    $ip = _ipcalc($if->ipv4,$if->netmask);
    $rows[] = array($if->id.'/'.$if->ipv4_id,$if->interface_type,$if->ipv4.'/'.$ip['maskbits'],$if->netmask,$if->mac);
  }
  return array_merge($rows);
}

/* guifi_device_print(): main print function, outputs the device information and call the others */
function guifi_device_print($device = NULL) {
  if ($device == NULL) {
    print theme('page',t('Not found'), FALSE);
    return;
  }

  $output = '<div id="guifi">';

  $node = node_load(array('nid' => $device[nid]));
  $title = t('Node:').' <a href="'.url('node/'.$node->nid).'">'.$node->nick.'</a> &middot; '.t('Device:').'&nbsp;'.$device[nick];

  drupal_set_breadcrumb(guifi_node_ariadna($node));

  switch (arg(4)) {
  case 'all': case 'data': default:
    $table = theme('table', NULL, guifi_device_print_data($device));
    $output .= theme('box', $title, $table);
    if (arg(4) == 'data') break;
  case 'graphs':
    // device graphs
    $table = theme('table', array(t('traffic overview')), guifi_device_graph_overview($device));
    $output .= theme('box', t('device graphs'), $table);
    if (arg(4) == 'graphs') break;
  case 'links':
    // links
    $output .= theme('box', NULL, guifi_device_links_print($device));
    if (arg(4) == 'links') break;
  case 'interfaces':
    $header = array(t('id'),t('type'),t('ip address'),t('netmask'),t('mac'));
    $table = theme('table', $header, guifi_device_interfaces_print_data($device[id]));
    $output .= theme('box', t('interfaces information'), $table);
    break;
  case 'services':
    $output .= theme('box', t('services information'), theme_guifi_services_list($device['id']));
    $output .= '</div>';
    return;
  }

  $output .= '</div>';

  drupal_set_title(t('View device %dname',array('%dname' => $device['nick'])));
  $output .= theme_links(module_invoke_all('link', 'node', $node, FALSE));
  print theme('page',$output, FALSE);
  return;
}

function guifi_device_traceroute($dev) {
  //guifi_log(GUIFILOG_BASIC,'device traceroute',$dev);
  drupal_goto('guifi/menu/ip/traceroute/'.$dev['id']);
}
function guifi_device_links_print($device,$ltype = '%') {
  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_device_links_print(%s)',$ltype),$device);

  $oGC = new GeoCalc();
  $dtotal = 0;
  $ltotal = 0;
  if ($ltype == '%')
    $title = t('links');
  else
    $title = t('links').' ('.$ltype.')';

  $rows_wds[] = array(array('data' => '<strong>'.t('bridge wds/p2p').'</strong>','colspan' => 2));
  $rows_ap_client[] = array(array('data' => '<strong>'.t('ap/client').'</strong>','colspan' => 2));
  $rows_cable[] = array(array('data' => '<strong>'.t('cable').'</strong>','colspan' => 2));
  $rows=array();
  $loc1 = db_fetch_object(db_query(
    'SELECT lat, lon, nick ' .
    'FROM {guifi_location} WHERE id=%d',
    $device['nid']));

  $curr_radio = 0;

  switch ($ltype) {
  case '%':
  case 'wds':
  case 'ap/client':
    if ($device['radios']) foreach ($device['radios'] as $radio_id => $radio)
    if ($radio['interfaces']) foreach ($radio['interfaces'] as $interface_id => $interface)
    if ($interface['ipv4']) foreach ($interface['ipv4'] as $ipv4_id => $ipv4)
    if ($ipv4['links']) foreach ($ipv4['links'] as $link_id => $link) {
      guifi_log(GUIFILOG_FULL,'going to list link',$link);
      $loc2 = db_fetch_object(db_query(
        'SELECT lat, lon, nick FROM {guifi_location} WHERE id=%d',
        $link['nid']));
      $gDist = round($oGC->EllipsoidDistance($loc1->lat, $loc1->lon, $loc2->lat, $loc2->lon),3);
      $dAz = round($oGC->GCAzimuth($loc1->lat, $loc1->lon, $loc2->lat,$loc2->lon));
          // Calculo orientacio
          if ($dAz < 23) $dOr =t("N"); else
          if ($dAz < 68) $dOr =t("NE"); else
          if ($dAz < 113) $dOr =t("E"); else
          if ($dAz < 158) $dOr =t("SE"); else
          if ($dAz < 203) $dOr =t("S"); else
          if ($dAz < 248) $dOr =t("SW"); else
          if ($dAz < 293) $dOr =t("W"); else
          if ($dAz < 338) $dOr =t("NW"); else
            $dOr =t("N");
      $item = _ipcalc( $ipv4['ipv4'],  $ipv4['netmask']);
      $ipdest = explode('.',$link['interface']['ipv4']['ipv4']);

      $status_url = guifi_cnml_availability(
          array('device' => $link['device_id'],'format' => 'short'));

      $cr = db_fetch_object(db_query("SELECT count(*) count FROM {guifi_radios} r WHERE id=%d",$link['device_id']));
      if ($cr->count > 1) {
        $rn = db_fetch_object(db_query("SELECT ssid FROM {guifi_radios} r WHERE r.id=%d AND r.radiodev_counter=%d",$link['device_id'],$link['interface']['radiodev_counter']));
        $dname = guifi_get_hostname($link['device_id']).'<br />'.$rn->ssid;
      }
      else
        $dname = guifi_get_hostname($link['device_id']);

      $wrow = array('<small>'.$radio['ssid'].'</small>',
                    array('data' => $link_id,'align' => 'right'),
                    '<a href="'.base_path().'guifi/device/'.$link['device_id'].'">'.$dname.'</a>',
                    '<a href="'.base_path().'node/'.$link['nid'].'">'.$loc2->nick.'</a>',
                    $ipv4['ipv4'].'/'.$item['maskbits'],'.'.$ipdest[3],
                    array('data' => t($link['flag']).$status_url,
                          'class' => $link['flag']),
                    $link[routing],
                    $gDist,
                    $dAz.'-'.$dOr);
      if ($interface['interface_type'] == 'wds/p2p')
        $rows_wds[] = $wrow;
      if ($link['link_type'] == 'ap/client')
        $rows_ap_client[] = $wrow;
      $dtotal = $dtotal + $gDist;;
      $ltotal++;

    }
    if ($ltype != '%') break;
  case 'cable':
    if ($device['interfaces']) foreach ($device['interfaces'] as $interface_id => $interface)
    if ($interface['ipv4']) foreach ($interface['ipv4'] as $ipv4_id => $ipv4)
    if ($ipv4['links']) foreach ($ipv4['links'] as $link_id => $link) {
      $loc2 = db_fetch_object(db_query('SELECT lat, lon, nick FROM {guifi_location} WHERE id=%d',$link['nid']));
      $gDist = round($oGC->EllipsoidDistance($loc1->lat, $loc1->lon, $loc2->lat, $loc2->lon),3);
      $item = _ipcalc( $ipv4['ipv4'],  $ipv4['netmask']);
      $ipdest = explode('.',$link['interface']['ipv4']['ipv4']);
      if ($gs->var['url'] != NULL)
        $img_url = ' <img src='.$gs->var['url'].'?device='.$link['device_id'].'&type=availability&format=short>';
      else
        $img_url = NULL;
      $rows_cable[] = array($interface['interface_type'].'/'.$link['interface']['interface_type'],
                       array('data' => $link_id,'align' => 'right'),
                       '<a href="'.base_path().'guifi/device/'.$link['device_id'].'">'.guifi_get_hostname($link['device_id']).'</a>',
                       array('data' => '-','align' => 'center'),
                       $ipv4['ipv4'].'/'.$item['maskbits'],'.'.$ipdest[3],
                       array('data' => t($link['flag']). $img_url,
                             'class' => $link['flag']),
                       $link[routing],
                       array('data' => '-','align' => 'center') ,
                       array('data' => '-','align' => 'center'));
      $ltotal++;
    }
    if ($ltype == 'cable') break;
  }

  if (count($rows_wds)> 1)
    $rows = $rows_wds;
  if (count($rows_ap_client) > 1)
    $rows = array_merge($rows_ap_client,$rows);
  if (count($rows_cable) > 1)
    $rows = array_merge($rows,$rows_cable);
  return '<h2>'.$title.'</h2>'.
         '<h3>'.t('Totals').': '.$ltotal.' '.t('links').', '.$dtotal.' '.t('kms.').'</h3>'.
         theme('table',array(t('interface'),t('id'),t('device'),t('node'),t('ip address'),'&nbsp;',t('status'),t('routing'),t('kms.'),t('az.')),$rows);
}

function guifi_device_link_list($id = 0, $ltype = '%') {
  $oGC = new GeoCalc();

  $total = 0;
  if ($ltype == '%')
    $title = t('links');
  else
  $title = t('links').' ('.$ltype.')';

  $header = array(t('type'),t('linked devices'), t('ip'), t('status'), t('routing'), t('kms.'),t('az.'));

  $queryloc1 = db_query("
    SELECT
      c.id, c.link_type, c.routing, l.nick, c.device_id, d.nick
      device_nick, a.ipv4 ip, i.interface_type itype, c.flag,
      l.lat, l.lon
    FROM {guifi_links} c
      LEFT JOIN {guifi_devices} d ON c.device_id=d.id
      LEFT JOIN {guifi_interfaces} i ON c.interface_id = i.id
      LEFT JOIN {guifi_ipv4} a ON i.id=a.interface_id AND a.id=c.ipv4_id
      LEFT JOIN {guifi_location} l ON d.nid = l.id
    WHERE c.device_id = %d
      AND link_type like '%s'
    ORDER BY c.link_type, c.device_id",
    $id,$ltype);
  if (db_num_rows($queryloc1)) {
    while ($loc1 = db_fetch_object($queryloc1)) {
      $queryloc2 = db_query("
        SELECT
          c.id, l.nick, r.ssid, c.device_id, d.nick device_nick,
          a.ipv4 ip, i.interface_type itype, l.lat, l.lon
        FROM {guifi_links} c
          LEFT JOIN {guifi_devices} d ON c.device_id=d.id
          LEFT JOIN {guifi_interfaces} i ON c.interface_id = i.id
          LEFT JOIN {guifi_ipv4} a ON i.id=a.interface_id
            AND a.id=c.ipv4_id
          LEFT JOIN {guifi_location} l ON d.nid = l.id
          LEFT JOIN {guifi_radios} r ON d.id=r.id
            AND i.radiodev_counter=r.radiodev_counter
        WHERE c.id = %d
          AND c.device_id != %d",
          $loc1->id,
          $loc1->device_id);
      while ($loc2 = db_fetch_object($queryloc2)) {
        $gDist = round($oGC->EllipsoidDistance($loc1->lat, $loc1->lon, $loc2->lat, $loc2->lon),3);
        if ($gDist) {
          $total = $total + $gDist;
          $dAz = round($oGC->GCAzimuth($loc1->lat, $loc1->lon, $loc2->lat,$loc2->lon));
          // Calculo orientacio
          if ($dAz < 23) $dOr =t("N"); else
          if ($dAz < 68) $dOr =t("NE"); else
          if ($dAz < 113) $dOr =t("E"); else
          if ($dAz < 158) $dOr =t("SE"); else
          if ($dAz < 203) $dOr =t("S"); else
          if ($dAz < 248) $dOr =t("SW"); else
          if ($dAz < 293) $dOr =t("W"); else
          if ($dAz < 338) $dOr =t("NW"); else
            $dOr =t("N");
        }
        else
          $gDist = 'n/a';

        $cr = db_fetch_object(db_query("SELECT count(*) count FROM {guifi_radios} r WHERE id=%d",$loc2->device_id));
        if ($cr->count > 1)
          $dname = $loc2->device_nick.'/'.$loc2->ssid;
        else
          $dname = $loc2->device_nick;

        $rows[] = array($loc1->id.'-'.$loc1->link_type.' ('.$loc1->itype.'-'.$loc2->itype.')','<a href="'.base_path().'guifi/device/'.$loc2->device_id.'">'.$dname.'</a>',
                     $loc1->ip.'/'.$loc2->ip,
                   array('data' => t($loc1->flag), 'class' => $loc1->flag),
                   array('data' => $gDist,'class' => 'number'),
                   $loc1->routing,
                   $dAz.'-'.$dOr);
      }
    }
    $output .= theme('table', $header, $rows);
    $output = theme('box',$title,$output);
    if ($total)
      $output .= t('Total:').'&nbsp;'.$total.'&nbsp;'.t('kms.');
    return $output;
  }
  return NULL;
}

function guifi_device_item_delete_msg($msg) {
  return t($msg).'<br />'.
    t('Press "<b>Save</b>" to confirm deletion or ' .
      '"<b>Reset</b>" to discard changes and ' .
      'recover the values from the database.');
}

function guifi_device_edit($device) {
  $output = drupal_get_form('guifi_device_form',$device);

  // To gain space, save bandwith and CPU, omit blocks
  print theme('page', $output, FALSE);
}

function guifi_device_get_service($id, $type ,$path = FALSE) {
  if (is_numeric($id))
    $z = guifi_device_load($id);
  else
    $z = $id;

  $ret = NULL;
  if (!empty($z->$type))
    $ret = $z->$type;
  else
    $ret = guifi_node_get_service($z->nid,$type);

  if ($path)
    if ($ret)
      $ret = 'node/'.$ret;

  return $ret;
}

function guifi_device_get_default_nick($node, $type, $nid) {
    $devs = db_fetch_object(db_query("
      SELECT count(*) count
      FROM {guifi_devices}
      WHERE type = '%s'
        AND nid = %d",
      $type, $nid));
    return $node->nick.ucfirst(guifi_trim_vowels($type)).($devs->count + 1);
}

?>
