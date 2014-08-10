<?php
/**
 * @file guifi_switch.inc.php
 * Switch edit forms & functions
 */



/**
 * @param array containing editing device information  $edit
 * @param weight form ewlements $form_weight
 */
function guifi_ports_form($edit,&$form_weight) {
  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_ports_form()',$edit);

// Select device model from model_specs
  if (!empty($edit['mid'])) {
    $querymid = db_query("
      SELECT mid, model, etherdev_max, optoports_max, m.opto_interfaces, f.name manufacturer
      FROM guifi_model_specs m, guifi_manufacturer f
      WHERE f.fid = m.fid
      AND m.mid = ".$edit['mid']);

    $swmodel = db_fetch_object($querymid);
  }

  switch ($edit['type']) {
    case 'switch':
      $fs_title = t('Switch ports & connections');
      $swtype = true;
      break;
    default:
      $fs_title = t('Physical ports & connections');
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
      '/icons/ports-16.png"> '.t('%title section',array('%title'=>$fs_title)),
  );

  guifi_log(GUIFILOG_TRACE,'function guifi_ports_form()',$swmodel);

  $opto_interfaces = explode('|',$swmodel->opto_interfaces);
  guifi_log(GUIFILOG_TRACE,'function guifi_ports_form(opto_interfaces)',$opto_interfaces);

  // if no switch model selected / unknown number of ports, ask for save
  if (empty($swmodel->etherdev_max) and (empty($edit['mid']))) {
    $form['msg'] = array(
      '#type' =>'item',
      '#value'=>t('Number of ports is still unknown. Select a model above and save & continue edit to populate ports.'),
      '#weight'=>$form_weight++,
    );
  }

  $connector_types = array('RJ45'=>str_pad(t('RJ45 Cooper'),$type_length,'-'));
  if ($swmodel->opto_interfaces)
    $connector_types = array_merge($connector_types, guifi_types('fo_port'));

  // Loop across all existing interfaces
  $port_count = 0;
  $total_ports = count($edit['interfaces']);
  $first_port = true;
  $eCountOpts = array();
  for ($i = 0; $i <= $total_ports; $i++)
  	$eCountOpts[$i] = $i;

  $m = guifi_get_model_specs($edit[variable][model_id]);
  guifi_log(GUIFILOG_TRACE,'function guifi_ports_form(m)',$m);

  foreach ($edit['interfaces'] as $port => $interface) {

    guifi_log(GUIFILOG_TRACE,'function guifi_ports_form(interface)',$interface);

    // Skip ports:
    // -with no interface type
    // -with related interfaces
    // -no interface_class but 'wLan/Lan' (v1 schema)
    if (empty($interface['interface_type']) or
       (!empty($interface[related_interfaces])) or
       (empty($interface[interface_class]) and $interface[interface_type]=='wLan/Lan')
       )
    {
      guifi_log(GUIFILOG_TRACE,'function guifi_ports_form(interface)',$interface);
      continue;
    }

    $prefix = ''; $suffix = ''; $port_count++;

    $form[$port] = array(
      '#type'         => 'fieldset',
      '#attributes'   => array('class'=>'fieldset-interface-port'),
      '#prefix'       => '<div id="fieldset-port-'.$port.'">',
      '#suffix'       => '</div>',
      '#tree'         => TRUE,
//     '#collapsed'      => ($interface['deleted'])?true:false,
//     '#collapsible'    => ($interface['deleted'])?true:false,
    );

    $form[$port]['etherdev_counter'] = array(
      '#tree'         => TRUE,
      '#type'         => 'select',
      '#title'        => ($first_port) ? t('#') : false,
      '#options'	  => $eCountOpts,
      '#default_value'=> $port_count-1,
      '#attributes'   => array('class'=>'interface-item'),
      '#weight'       => $form_weight++,
    );

    $form[$port]['interface_type'] = array(
      '#tree'         => TRUE,
      '#type'         => 'textfield',
      '#title'        => ($first_port) ? t('name') : false,
      '#default_value'=> ($interface['deleted']) ? t('deleted').' - '.$interface['interface_type'] :
        $interface['interface_type'],
      '#size'         => in_array($edit['type'],array('switch')) ? 10 : 20,
      '#size'         => 10,
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
     if (!($interface[deleted]))
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
      '#type'         => (in_array($edit['type'],array('switch'))) ?
                           'hidden' : 'textfield',
      '#type'         => 'hidden',
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
    if ($interface[deleted]) {
      $form[$port]['comments']['#value'] = t('will be deleted. press "reset" to cancel');
      $form[$port]['comments']['#disabled'] = true;
    }

    if (!($interface[deleted]))
    $form[$port]['mac'] = array(
      '#type'            => 'textfield',
      '#title'           => ($first_port) ? t('mac') : false,
      '#required'        => FALSE,
      '#size'            => 20,
      '#maxlength'       => 17,
      '#default_value'   => $interface['mac'],
      '#element_validate' => array('guifi_mac_validate'),
      '#weight'          => $form_weight++,
    );
    if ((!$interface[deleted]) and !($port_count <= $m->ethermax))
    $form[$port]['delete'] = array(
      '#type'       => 'image_button',
      '#title'      => ($first_port) ? t('delete') : false,
      '#src'        => drupal_get_path('module', 'guifi').'/icons/drop.png',
      '#attributes' => array('title' => t('Delete interface'.$m->ethermax.'-'.$port_count)),
      '#submit'     => array('guifi_vinterfaces_delete_submit'),
      '#prefix'     => ($first_port) ?
        '<div class="form-item"><label>&nbsp</label>' : false,
      '#suffix'     => ($first_port) ?
        '</div>' : false,
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

/* guifi_switch_form(): Main switch form (Common parameters)*/
function guifi_switch_form($edit, &$form_weight) {
  global $hotspot;
  global $bridge;
  global $user;



  $querymid = db_query("
    SELECT mid, model, f.name manufacturer
    FROM {guifi_model_specs} m, {guifi_manufacturer} f
    WHERE f.fid = m.fid
    AND (instr(m.model_class,'switch') > 0)
    AND supported='Yes'
    ORDER BY manufacturer ASC");
  while ($model = db_fetch_array($querymid)) {
     $models_array[$model["mid"]] = $model["manufacturer"] .", " .$model["model"];
  }
  guifi_log(GUIFILOG_TRACE,'function guifi_switch_form(models)',$models_array);

  $form['settings'] = array(
    '#type' => 'fieldset',

    '#title' => t('Switch model, specs & MAC address').' ('.$models_array[$edit['variable']['model_id']].')',
    '#weight' => $form_weight++,
    '#collapsible' => TRUE,
    '#tree' => FALSE,
    '#collapsed' => !is_null($edit['id']),
    '#attributes'  => array('class'=>'fieldset-device-main'),
  );

  $form['settings']['variable'] = array('#tree' => TRUE);
  $form['settings']['variable']['model_id'] = array(
    '#type' => 'select',
    '#title' => t("Switch Model"),
    '#required' => TRUE,
    '#default_value' => $edit['variable']['model_id'],
    '#options' => $models_array,
    '#description' => t('Select the switch model that do you have.'),
  );

  $form['settings']['variable']['managed'] = array(
    '#type' => 'checkbox',
    '#title' => t("Yes"),
    '#default_value' => $edit['variable']['managed'],
    '#description' => t('Switch is managed?'),
    '#prefix' => '<div class="form-item"><label>'.t('Managed?').'</label>',
    '#suffix' => '</div>',
  );


  $form['settings']['mac'] = array(
    '#type' => 'textfield',
    '#title' => t('Device MAC Address'),
    '#required' => TRUE,
    '#size' => 17,
    '#maxlength' => 17,
    '#default_value' => $edit['mac'],
    '#element_validate' => array('guifi_mac_validate'),
    '#description' => t("Base/Main MAC Address.<br />Some configurations won't work if is blank"),
  );


  return $form;
}

/**
 * @param device id $did
 * @param ethernet ports $ports
 */
function guifi_vinterface_save($iid,$did,$nid,&$to_mail) {
  guifi_log(GUIFILOG_BASIC,"function guifi_vinterface_save()",$ports);

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


/**
 * @param array containing editing device information  $edit
 * @param weight form ewlements $form_weight
 */
function guifi_vinterfaces_form($iClass, $edit, &$form_weight) {
  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_vinterfaces_form(iClass)',$iClass);

  switch($edit[type]) {
  	case 'switch':
  	  if (!($edit[variable][managed]) or (in_array($iClass,'tunnels')))
  	    return;
  	  break;
  	case 'radio':
  	  if (empty($edit[variable][firmware_id]))
  	    return;
  	  $firm = guifi_get_firmware($edit[variable][firmware_id]);
  	  if (!(in_array($iClass,$firm->managed)))
  	    return;
  	  break;
  	default:
  	  return;
  }

  switch ($iClass) {
  	case 'aggregations':
  	  $icon = '/icons/aggr-16.png';
  	  $iconNew = '/icons/aggr-new.png';
  	  $msg  = t('Aggregations (bridges, bondings...) section');
  	  break;
  	case 'vlans':
  	  $icon = '/icons/vlans-16.png';
  	  $iconNew = '/icons/vlans-new.png';
  	  $msg  = t('vLans (vlans, wds, virtual APs, vrrp...) section');
  	  break;
  	default:
  	  $icon = '/icons/ports-16.png';
  	  $msg  = t('%iClass section',array('%iClass'=>$iClass));
  	  break;
  }

  // Build vinterface fieldset
  $form = array(
    '#type'        => 'fieldset',
    '#title'       => t($iClass).' - '.count($edit[$iClass]),
    '#collapsible' => TRUE,
    '#tree'        => TRUE,
    '#collapsed'   => TRUE,
    '#weight'      => $form_weight++,
    '#prefix'      =>
      '<br><img src="/'.drupal_get_path('module', 'guifi').$icon.'"> '.$msg,
  );

  if (empty($edit[$iClass])) {
    guifi_log(GUIFILOG_TRACE,'function guifi_ports_form(empty)',$iClass);
    guifi_vinterface_form($form, $iClass, 0, $edit, true, $form_weight);
    /* $form['msgnone'] = array(
      '#type' =>'item',
      '#value'=>t('None'),
      '#weight'=>$form_weight++,
    ); */
  }

  // Loop across all existing interfaces
  $vif_count = 0;
  $total_vif = count($iClass);
  $first_vif = true;

  // placeholder for the add interface form
  $form['vifs'] = array(
    '#parents'   => array('vifs'),
    '#type'      => 'fieldset',
    '#prefix'    => '<div id="add-'.$iClass.'">',
    '#suffix'    => '</div>',
    '#weight'    => $form_weight++,
  );

  guifi_log(GUIFILOG_TRACE,'function guifi_vinterfaces_form(edit)',$edit[$iClass]);

  foreach ($edit[$iClass] as $vifId => $vif) {
    guifi_log(GUIFILOG_TRACE,'function guifi_ports_form(vint LOOP)',$vifId);
    $form[vifs][$vifId] =
      guifi_vinterface_form($iClass, $vif, $first_vif,guifi_get_currentInterfaces($edit));
    $first_vif = false;
  }
  guifi_log(GUIFILOG_TRACE,'function guifi_ports_form(vint LOOP AFTER)',$form);
  // placeholder for the add interface form
/*  $form['vinew'] = array(
    '#type'   => 'hidden',
    '#prefix' => '<div id="add-'.$iClass.'">',
    '#suffix' => '</div>',
    '#weight' => $form_weight++,
  );*/

  $form['add'.$iClass] = array(
        '#type' => 'image_button',
        '#src'=> drupal_get_path('module', 'guifi').$iconNew,
        '#parents' => array('add'.$iClass),
        '#attributes' => array('title' => t('Add '.$iClass)),
        '#ahah' => array(
          'path' => 'guifi/js/add-vinterface/'.$iClass,
          'wrapper' => 'add-'.$iClass,
          'method' => 'replace',
          'effect' => 'fade',
         ),
         '#weight' => $form_weight++,
  );

  return $form;
}

function guifi_get_currentInterfaces($device) {
  guifi_log(GUIFILOG_TRACE,'function guifi_vinterface_form (iClass)',$iClass);
  $interfaces = array();

  foreach ($device[radios] as $k => $radio) {
    $interfaces[$device[id].','.$k] = 'wlan'.$k.' - '.$radio[ssid];
  }
  foreach (array('ports','interfaces','vlans','aggregations','tunnels') as $iClass)
    foreach ($device[$iClass] as $k => $interface) {
      if (empty($interface[interface_type]))
        continue;
      $interfaces[$k] = $interface[interface_type];
    }

  return $interfaces;
}

function guifi_vinterface_form($iClass, $vinterface, $first_port = true, $eInterfaces) {
  global $user;

  switch($iClass) {
  	case 'vlans':
  	  $iconNew = '/icons/vlans-new.png';
  	  $iType = 'vlan';
  	  break;
  	case 'aggregations':
  	  $iconNew = '/icons/aggr-new.png';
  	  $iType = 'aggregation';
  	  break;
  }

  guifi_log(GUIFILOG_TRACE,'function guifi_vinterface_form (iClass)',$iClass);

  guifi_log(GUIFILOG_TRACE,'function guifi_vinterface_form (vinterface)',$vinterface);

  if (empty($vinterface[interface_type]))
    $vinterface[interface_type] = substr($iType,0,4).($vinterface[id]);

  $prefix = ''; $suffix = '';

/*  if ($vinterface['deleted']) {
    $form['deleted'] = array(
      '#type'         => 'hidden',
      '#value'        => $vinterface['deleted'],
      '#weight'       => $form_weight++,
    );
    $form['deletedMsg'] = array(
      '#type'         => 'item',
      '#value'        => $vinterface[interface_type]. ' '. t('Will be deleted when saving the device'),
    );
    return $form;
  } */

  $form = array(
    '#type'         => 'fieldset',
    '#parents'      => array($iClass,$vinterface[id]),
    '#attributes'   => array('class'=>'fieldset-interface-port'),
    '#prefix'       => '<div id="fieldset-port-'.$vinterface[id].'">',
    '#suffix'       => '</div>',
    '#tree'         => TRUE,
  );

  $form['interface_class'] = array(
    '#type'         => 'select',
    '#title'        => ($first_port) ? t('%iClass type',array('%iClass'=>$iType)) : false,
    '#options'      => guifi_types($iType),
    '#default_value'=> $vinterface[interface_class],
    '#disabled'     => ($vinterface['deleted']) ? TRUE : FALSE,
    '#attributes'   => array('class'=>'interface-item'),
  );
  $form['interface_type'] = array(
    '#type'         => 'textfield',
    '#title'        => ($first_port) ? t('name') : false,
    '#default_value'=> $vinterface[interface_type],
    '#size'         => 20,
    '#maxlength'    => 40,
    '#disabled'     => (($vinterface['interface_type'] == 'wLan/Lan')
                       or ($vinterface['deleted'])) ?
                         TRUE : FALSE,
    '#attributes'   => array('class'=>'interface-item'),
   );
   if ($vinterface['interface_type'] == 'wLan/Lan')
     $form[interface_type]['#value'] = 'wLan/Lan';

   if ($form['interface_type']['#disabled']) {
     $form['interface_type']['#value'] = $vinterface[interface_type];
     $form['interface_type']['#attributes'] = array('class'=>'interface-item-disabled');
   }

   guifi_log(GUIFILOG_TRACE,'function guifi_ports_form(type)',$interface);

  $form['related_interfaces'] = array(
    '#type' => 'select',
    '#title'        => ($first_port) ? t('parent interface') : false,
    '#options'      => array_diff($eInterfaces,array($vinterface[interface_type])),
    '#default_value'=> $vinterface['related_interfaces'],
    '#disabled'     => ($vinterface['deleted']) ? TRUE : FALSE,
    '#attributes'   => array('class'=>'interface-item'),
  );
  if ($iType == 'aggregation') {
  // if ($iClass == 'vlans') {
    $form['related_interfaces']['#size'] = 4;
    $form['related_interfaces']['#multiple'] = true;
    $form['related_interfaces']['#title'] = ($first_port) ?
      t('parent interfaces') : false;
  }

  if (!$vinterface[deleted])
  $form['vlan'] = array(
    '#type'         => 'textfield',
    '#type'         => ($iClass=='vlans') ?
                         'textfield' : 'hidden',
    '#title'        => ($first_port) ? t('vlan id') : false,
    '#size'         => 6,
    '#maxlength'    => 10,
    '#default_value'=> $vinterface[vlan],
    '#attributes'   => array('class'=>'interface-item'),
  );

  $form['comments'] = array(
    '#type'         => 'textfield',
    '#title'        => ($first_port) ? t('comments') : false,
    '#size'         => 40,
    '#maxlength'    => 60,
    '#disabled'     => ($vinterface['deleted']) ? TRUE : FALSE,
    '#default_value'=> $vinterface[comments],
    '#attributes'   => ($vinterface['deleted']) ?
      array('class'=>'interface-item-disabled') :
      array('class'=>'interface-item'),
  );
  if ($vinterface[deleted])
    $form['comments']['#value'] = t('will be deleted. press "reset" to cancel');

  if (!$vinterface[deleted])
  $form['mac'] = array(
    '#type'            => 'textfield',
    '#title'           => ($first_port) ? t('mac') : false,
    '#required'        => FALSE,
    '#size'            => 20,
    '#maxlength'       => 17,
    '#default_value'   => $vinterface['mac'],
    '#element_validate' => array('guifi_mac_validate'),
  );
  if (!$vinterface[deleted])
  $form['delete'] = array(
    '#type' => 'image_button',
    '#title' => ($first_port) ? t('delete') : false,
    '#src' => drupal_get_path('module', 'guifi').'/icons/drop.png',
    '#parents' => array_merge($ptree,array('deleteInterface')),
    '#attributes' => array('title' => t('Delete interface')),
    '#submit' => array('guifi_vinterfaces_delete_submit'),
    '#prefix' => ($first_port) ?
      '<div class="form-item"><label>&nbsp</label>' : false,
    '#suffix' => ($first_port) ?
      '</div>' : false,
  );

  // Hidden fields
    $form['id'] = array(
      '#type'         => 'hidden',
      '#value'        => $vinterface['id'],
    );
    $form['device_id'] = array(
      '#type'         => 'hidden',
      '#value'        => $vinterface['device_id'],
    );
    $form['connto_did'] = array(
      '#type'         => 'hidden',
      '#value'        => $vinterface['connto_did'],
    );
    $form['connto_iid'] = array(
      '#type'         => 'hidden',
      '#value'        => $vinterface['connto_iid'],
    );
    if ($vinterface['deleted'])
      $form['deleted'] = array(
        '#type'       => 'hidden',
        '#value'      => $vinterface['deleted'],
      );
    if ($vinterface['new'])
      $form['new'] = array(
        '#type'       => 'hidden',
        '#value'      => $vinterface['new'],
      );
  return $form;
}

/* Delete interface */
function guifi_vinterfaces_delete_submit(&$form,&$form_state) {
  $values      = $form_state['clicked_button']['#parents'];
  $interface_id= $values[1];
  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_vinterface_delete_submit(radio: %d, interface: %d)',
    $radio_id,$interface_id),$form_state['clicked_button']['#parents']);
  $vinterface = &$form_state['values'][$values[0]][$interface_id];
  $vinterface['deleted'] = TRUE;
//  $form_state['deleteInterface']=($radio_id).','.($interface_id);
  $form_state['rebuild'] = TRUE;
//  $form_state['action'] = 'guifi_interface_delete';
  return TRUE;
}

?>
