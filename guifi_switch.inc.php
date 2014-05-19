<?php
/**
 * @file guifi_switch.inc.php
 * Switch edit forms & functions
 */

// Port connectors section
function guifi_ports_form($edit,&$form_weight) {
  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_ports_form()',$edit);

// Select device model from model_specs
  if (isset($edit['mid'])) {
    $querymid = db_query("
      SELECT mid, model, etherdev_max, optoports_max, m.opto_interfaces, f.name manufacturer
      FROM guifi_model_specs m, guifi_manufacturer f
      WHERE f.fid = m.fid
      AND m.mid = ".$edit['mid']);

    $swmodel = db_fetch_object($querymid);
  }

  switch ($edit['type']) {
    case 'switch':
      $fs_title = t('Switch ports');
      $swtype = true;
      break;
    default:
      $fs_title = t('Physical port connectors');
      $swtype = false;
  }

  // Build port fieldset
  $form = array(
    '#type'        => 'fieldset',
    '#title'       => t('Ports'),
    '#collapsible' => TRUE,
    '#tree'        => TRUE,
    '#collapsed'   => FALSE,
    '#weight'      => $form_weight++,
    '#prefix'      => '<br><img src="/'.
      drupal_get_path('module', 'guifi').
      '/icons/ports-16.png"> '.t('Port connectors section'),
  );

  guifi_log(GUIFILOG_TRACE,'function guifi_ports_form()',$swmodel);

  $opto_interfaces = explode('|',$swmodel->opto_interfaces);
  guifi_log(GUIFILOG_TRACE,'function guifi_ports_form(opto_interfaces)',$opto_interfaces);

  // if no switch model selected / unkown number of ports, ask for save
  if (empty($swmodel->etherdev_max)) {
    $form['msg'] = array(
      '#type' =>'item',
      '#value'=>t('Number of ports is still unknown. Select a model above and save & continue edit to populate ports.'),
      '#weight'=>$form_weight++,
    );
  }

  $connector_types = array('RJ45'=>str_pad(t('RJ45 Cooper'),$type_length,'-'));
  if (!empty($swmodel->opto_interfaces))
      $connector_types = array_merge($connector_types, guifi_types('fo_port'));

  // Loop across all existing interfaces
  $port_count = 0;
  $total_ports = count($edit['interfaces']);
  $first_port = true;

  foreach ($edit['interfaces'] as $port => $interface) {

    guifi_log(GUIFILOG_TRACE,'function guifi_ports_form(interface)',$interface);

    if (empty($interface['interface_type'])) {
      guifi_log(GUIFILOG_TRACE,'function guifi_ports_form(interface)',$interface);
      continue;
    }

    $prefix = ''; $suffix = ''; $port_count++;

   $form[$port] = array(
     '#type'         => 'fieldset',
     '#attributes'   => array('class'=>'fieldset-interface-port'),
     '#prefix'       => '<div id="fieldset-port-'.$port.'">',
     '#suffix'       => '</div>',
     '#tree' => TRUE,
//     '#collapsed'      => ($interface['deleted'])?true:false,
//     '#collapsible'    => ($interface['deleted'])?true:false,
   );


   $form[$port]['interface_type'] = array(
      '#tree'         => TRUE,
      '#type'         => 'textfield',
      '#title'        => ($first_port) ? t('name') : false,
      '#default_value'=> ($interface['deleted']) ? t('deleted').' - '.$interface['interface_type'] :
        $interface['interface_type'],
      '#size'         => in_array($edit['type'],array('switch')) ? 10 : 20,
      '#maxlength'    => 40,
      '#disabled'     => ($interface['interface_type'] == 'wLan/Lan')
                         or ($interface['deleted'])
                         or (in_array($edit['type'],array('switch'))) ?
                           TRUE : FALSE,
      '#attributes'   => array('class'=>'interface-item'),
      '#weight'       => $form_weight++,
     );
     if ($form[$port]['interface_type']['#disabled']) {
       $form[$port]['interface_type']['#value'] = $interface['interface_type'];
       $form[$port]['interface_type']['#attributes'] = array('class'=>'interface-item-disabled');
     }

     guifi_log(GUIFILOG_TRACE,'function guifi_ports_form(type)',$interface);

     $form[$port]['connector_type'] = array(
      '#tree'         => TRUE,
      '#type'         => 'select',
      '#title'        => ($first_port) ? t('connector') : false,
      '#options'	  => $connector_types,
      '#disabled'     => (in_array($interface['interface_type'],$opto_interfaces)) ? false : true,
      '#default_value'=> $interface['connector_type'],
      '#attributes'   => (in_array($interface['interface_type'],$opto_interfaces)) ?
         array('class'=>'interface-item') : array('class'=>'interface-item-disabled'),
      '#weight'       => $form_weight++,
     );

     if (!empty($interface['connto_did'])) {
       $dname = guifi_get_devicename($interface['connto_did']);
       $dinterfaces = guifi_get_device_interfaces($interface['connto_did'],$interface['connto_iid']);
     } else
       $dname = '';

     $form[$port]['dname'] = array(
       '#type'         => 'textfield',
       '#title'        => ($first_port) ? t('connects to') : false,
       '#disabled'     => true,
       '#size'         => 30,
       '#maxsize'      => 256,
       '#value'        => ($interface['connto_iid']) ?
          $dname.' / '.$dinterfaces[$interface['connto_iid']] :
          $dname,
       '#attributes'   => (empty($dname)) ? array('class'=>'interface-item-available') :
          array('class'=>'interface-item-disabled'),
       '#weight'       => $form_weight++,
     );
     $form[$port]['addLink'] = array(
       '#type' => 'image_button',
       '#src'=> ($interface['deleted']) ?
         drupal_get_path('module', 'guifi').'/icons/drop.png' :
         drupal_get_path('module', 'guifi').'/icons/edit.png',
       '#attributes' => array(
          'title' => t('Edit dialog for connecting to another device'),
          'class' => 'interface-item.form-button',
        ),
       '#ahah' => array(
         'path' => 'guifi/js/edit_cableconn/'.$port,
         'wrapper' => 'interface-cableconn-'.$port,
         'method' => 'replace',
         'effect' => 'fade',
        ),
        '#prefix' => ($first_port) ? '<div class="form-item"><div>&nbsp</div>':'<div class="form-item">',
        '#suffix' => '</div>',
        '#weight' => $form_weight++,
     );

    $form[$port]['vlan'] = array(
      '#tree'         => TRUE,
      '#type'         => 'textfield',
      '#title'        => ($first_port) ? t('vlan') : false,
      '#size'         => 6,
      '#maxlength'    => 10,
      '#default_value'=> $interface['vlan'],
      '#attributes'   => array('class'=>'interface-item'),
      '#weight'       => $form_weight++,
     );
    $form[$port]['comments'] = array(
      '#tree'         => TRUE,
      '#type'         => 'textfield',
      '#title'        => ($first_port) ? t('comments') : false,
      '#size'         => 40,
      '#maxlength'    => 60,
      '#default_value'=> $interface['comments'],
      '#attributes'   => array('class'=>'interface-item'),
      '#weight'       => $form_weight++,
    );
    $form[$port]['mac'] = array(
      '#type'            => 'textfield',
      '#title'           => ($first_port) ? t('mac') : false,
      '#required'        => FALSE,
      '#size'            => 17,
      '#maxlength'       => 17,
      '#default_value'   => $interface['mac'],
      '#element_validate' => array('guifi_mac_validate'),
      '#attributes'   => array('class'=>'interface-eol'),
      '#suffix'          => '<div>&nbsp</div>', // Force new line on some browsers
      '#weight'          => $form_weight++,
    );

      $form[$port]['conn'] = array(
        '#type'         => 'hidden',
        '#prefix'       => '<div id="interface-cableconn-'.$port.'">',
        '#suffix'       => '</div>',
        '#weight'       => $form_weight++,
      );
      $form[$port]['conn']['did'] = array(
        '#type'            => 'textfield',
        '#parents'         => array('interfaces',$port,'did'),
        '#value'           => guifi_get_devicename($interface['connto_did'],'large'),
        '#autocomplete_path' => 'guifi/js/select-node-device',
        '#size'            => 60,
        '#maxlength'       => 128,
        '#element_validate'=> array('guifi_devicename_validate'),
        '#attributes'   => array('class'=>'interface-item'),
        '#ahah'         => array(
          'path'          => 'guifi/js/select-device-interface/'.$port,
          'wrapper'       => 'fieldset-port-'.$port,
          'method'        => 'replace',
          'effect'        => 'fade',
        ),
//        '#weight'       => $form_weight++,
      );
      $form[$port]['conn']['if'] = array(
        '#parents'      => array('interfaces',$port,'if'),
        '#type'         => 'select',
        '#value'        => $interface['connto_iid'],
        '#attributes'   => array('class'=>'interface-item'),
        '#options'      => $dinterfaces,
        '#ahah'         => array(
          'path'          => 'guifi/js/select-device-interface/'.$port,
          'wrapper'       => 'fieldset-port-'.$port,
          'method'        => 'replace',
          'effect'        => 'fade',
        ),
    );

    // Hidden fields
      $form[$port]['id'] = array(
        '#type'         => 'hidden',
        '#value'        => $interface['id'],
        '#weight'       => $form_weight++,
      );
      $form[$port]['device_id'] = array(
        '#type'         => 'hidden',
        '#value'        => $interface['device_id'],
        '#weight'       => $form_weight++,
      );
      $form[$port]['connto_did'] = array(
        '#type'         => 'hidden',
        '#value'        => $interface['connto_did'],
        '#weight'       => $form_weight++,
      );
      $form[$port]['connto_iid'] = array(
        '#type'         => 'hidden',
        '#value'        => $interface['connto_iid'],
        '#weight'       => $form_weight++,
      );
      if ($interface['deleted'])
        $form[$port]['deleted'] = array(
          '#type'         => 'hidden',
          '#value'        => $interface['deleted'],
          '#weight'       => $form_weight++,
        );
      if ($interface['new'])
        $form[$port]['new'] = array(
          '#type'         => 'hidden',
          '#value'        => $interface['new'],
          '#weight'       => $form_weight++,
        );
      $first_port = false;
  }

  return $form;
}

function guifi_ports_create($did,$ports) {
  null;
}

function guifi_ports_save($did,$ports) {
  guifi_log(GUIFILOG_BASIC,"function _guifi_ports_save()",$ports);

  foreach ($ports as $kport => $vport) {

  	$dev = explode('-',$vport['did']);

  	$if = array(
  	  'id'               => $vport['iid'],
  	  'device_id'        => $did,
  	  'etherdev_counter' => trim($vport['id']),
  	  'interface_type'   => trim($vport['id']),
  	  'connector_type'   => $vport['type'],
  	  'vlan'             => $vport['vlan'],
  	  'comments'         => $vport['comment'],
  	  'connto_did'       => (is_numeric($dev[0])) ? $dev[0] : 0,
  	  'connto_iid'       => (is_numeric($dev[0])) ? $vport['if'] : 0,
  	);

  	// retrieve existing values to $if_current
  	$sql_if_exists =
      'SELECT id, device_id, etherdev_counter, interface_type, connector_type, vlan, comments, connto_did, connto_iid ' .
      'FROM {guifi_interfaces} i ' .
      'WHERE id = ' .$if['id'] .
      ' AND device_id = '.$did;
  	$if_current = db_fetch_array(db_query($sql_if_exists));

    // if new interface, insert
    if (!$if_current)
      $if['new'] = true;
    else {
  	  // if there is no chages, next
      $changes = array_diff_assoc($if,$if_current);
      guifi_log(GUIFILOG_TRACE,"function _guifi_switch_save(existing ".$kport.")",$if_current);
      guifi_log(GUIFILOG_TRACE,"function _guifi_switch_save(posted ".$kport.")",$if);
      guifi_log(GUIFILOG_TRACE,"function _guifi_switch_save(changes ".$kport.")",$changes);
      if (!count($changes))
        continue;
    }

    // if remote interface defined, update remote interface
    if ((!empty($dev[0])) and (!empty($vport['if']))) {
      $if_remote = array(
        'device_id'      =>$dev[0],
        'id'             =>$vport['if'],
        'connector_type' =>$vport['type'],
        'connto_did'     =>(string)$did,
        'connto_iid'     =>(string)$vport['iid'],
      );
      guifi_log(GUIFILOG_TRACE,"function _guifi_switch_save(remote-set)",$if);
      _guifi_db_sql('guifi_interfaces',array('device_id'=>$dev[0],'id'=>$vport['if']),$if_remote);
    }

    // if update, check for the remote reference
  	if (empty($if['new'])) {
      // if had another device, clear remote reference
      if ((!empty($if_current['connto_did'])) and (!empty($if_current['connto_iid']))) {
        $if_remote = array(
          'device_id'      => (string) $if_current['connto_did'],
          'id'             => (string) $if_current['connto_iid'],
          'connto_did'     => (string) null,
          'connto_iid'     => (string) null,
        );
        guifi_log(GUIFILOG_TRACE,"function _guifi_switch_save(remote-clear)",$if_remote);
        _guifi_db_sql('guifi_interfaces',array('device_id'=>$if_remote['device_id'],'id'=>$if_remote['id']),$if_remote);
      } // Clear remote interface
  	}

    guifi_log(GUIFILOG_TRACE,"function _guifi_switch_save(local)",$if);
  	_guifi_db_sql('guifi_interfaces',array('device_id'=>$if['device_id'],'id'=>$if['id']),$if);

  }

}



/* guifi_switch_form(): Main radio form (Common parameters)*/
function guifi_switch_form($edit,&$form_weight) {
  global $user;

//  (empty($form_weight)) ? $form_weight = 0: null;

  guifi_log(GUIFILOG_TRACE,'function guifi_switch_form()',$edit);

  $querymodels = db_query("
    SELECT mid, model, etherdev_max, optoports_max, f.name manufacturer
    FROM {guifi_model_specs} m, {guifi_manufacturer} f
    WHERE f.fid = m.fid
    AND LOCATE('switch',m.model_class) > 1
    ORDER BY manufacturer ASC, model ASC");

  while ($swmodels = db_fetch_array($querymodels)) {
     $swmodels_array[$swmodels["mid"]] = $swmodels["manufacturer"] .", " .$swmodels["model"];
  }

// Select device model from model_specs
  $querymid = db_query("
    SELECT mid, model, etherdev_max, optoports_max, m.opto_interfaces, f.name manufacturer
    FROM guifi_model_specs m, guifi_manufacturer f
    WHERE f.fid = m.fid
    AND m.mid = ".$edit['mid']);

  $swmodel = db_fetch_object($querymid);

  $txt_model = t('Switch model & MAC address');
  if (!empty($edit['mid']))
    $txt_model .= ' - ('.$swmodel->manufacturer.', '.$swmodel->model.')';

  $form['swmodel'] = array(
    '#type'        => 'fieldset',
    '#title'       => $txt_model,
    '#collapsible' => TRUE,
    '#tree'        => FALSE,
    '#collapsed'   => !is_null($edit['id']),
    '#weight'      => $form_weight++,
  );

  $form['swmodel']['mid'] = array(
    '#type'          => 'select',
    '#title'         => t("Switch Model"),
    '#required'      => TRUE,
    '#options'       => $swmodels_array,
    '#default_value' => $edit['mid'],
    '#description'   => t('Select the switch model that do you have.'),
    '#prefix'       => '<div class="float-item">',
    '#suffix'        => '</div>',
    '#weight'        => $form_weight++,
  );

  $form['swmodel']['mac'] = array(
    '#type'             => 'textfield',
    '#title'            => t('Device MAC Address'),
    '#required'         => TRUE,
    '#size'             => 17,
    '#maxlength'        => 17,
    '#default_value'    => $edit['mac'],
    '#element_validate' => array('guifi_mac_validate'),
    '#description'      => t("Base/Main MAC Address.<br />Some configurations won't work if is blank"),
    '#weight'           => $form_weight++,
  );

  return $form;
}

function guifi_swwitch_save($did,$ports) {
  guifi_log(GUIFILOG_TRACE,"function _guifi_switch_save()",$ports);

  foreach ($ports as $kport => $vport) {

  	$dev = explode('-',$vport['did']);

  	$if = array(
  	  'id'               => $vport['iid'],
  	  'device_id'        => $did,
  	  'etherdev_counter' => trim($vport['id']),
  	  'interface_type'   => trim($vport['id']),
  	  'connector_type'   => $vport['type'],
  	  'vlan'             => $vport['vlan'],
  	  'comments'         => $vport['comment'],
  	  'connto_did'       => (is_numeric($dev[0])) ? $dev[0] : 0,
  	  'connto_iid'       => (is_numeric($dev[0])) ? $vport['if'] : 0,
  	);

  	// retrieve existing values to $if_current
  	$sql_if_exists =
      'SELECT id, device_id, etherdev_counter, interface_type, connector_type, vlan, comments, connto_did, connto_iid ' .
      'FROM {guifi_interfaces} i ' .
      'WHERE id = ' .$if['id'] .
      ' AND device_id = '.$did;
  	$if_current = db_fetch_array(db_query($sql_if_exists));

    // if new interface, insert
    if (!$if_current)
      $if['new'] = true;
    else {
  	  // if there is no chages, next
      $changes = array_diff_assoc($if,$if_current);
      guifi_log(GUIFILOG_TRACE,"function _guifi_switch_save(existing ".$kport.")",$if_current);
      guifi_log(GUIFILOG_TRACE,"function _guifi_switch_save(posted ".$kport.")",$if);
      guifi_log(GUIFILOG_TRACE,"function _guifi_switch_save(changes ".$kport.")",$changes);
      if (!count($changes))
        continue;
    }

    // if remote interface defined, update remote interface
    if ((!empty($dev[0])) and (!empty($vport['if']))) {
      $if_remote = array(
        'device_id'      =>$dev[0],
        'id'             =>$vport['if'],
        'connector_type' =>$vport['type'],
        'connto_did'     =>(string)$did,
        'connto_iid'     =>(string)$vport['iid'],
      );
      guifi_log(GUIFILOG_TRACE,"function _guifi_switch_save(remote-set)",$if);
      _guifi_db_sql('guifi_interfaces',array('device_id'=>$dev[0],'id'=>$vport['if']),$if_remote);
    }

    // if update, check for the remote reference
  	if (empty($if['new'])) {
      // if had another device, clear remote reference
      if ((!empty($if_current['connto_did'])) and (!empty($if_current['connto_iid']))) {
        $if_remote = array(
          'device_id'      => (string) $if_current['connto_did'],
          'id'             => (string) $if_current['connto_iid'],
          'connto_did'     => (string) null,
          'connto_iid'     => (string) null,
        );
        guifi_log(GUIFILOG_TRACE,"function _guifi_switch_save(remote-clear)",$if_remote);
        _guifi_db_sql('guifi_interfaces',array('device_id'=>$if_remote['device_id'],'id'=>$if_remote['id']),$if_remote);
      } // Clear remote interface
  	}

    guifi_log(GUIFILOG_TRACE,"function _guifi_switch_save(local)",$if);
  	_guifi_db_sql('guifi_interfaces',array('device_id'=>$if['device_id'],'id'=>$if['id']),$if);

  }

}

?>
