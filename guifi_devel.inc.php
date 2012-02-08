<?php

// Services output
function guifi_devel_services($service_id , $op) {

  switch($service_id) {
    case 'add':
     $service_id = 'New';
     return drupal_get_form('guifi_devel_services_form',$service_id);
  }
  switch($op) {
    case 'edit':
      return drupal_get_form('guifi_devel_services_form',$service_id);
    case 'delete':
      guifi_log(GUIFILOG_TRACE,'guifi_devel_services_delete()',$service_id);
      return drupal_get_form(
      'guifi_devel_services_delete_confirm', $service_id);
    guifi_devel_services_delete($service_id);
  }
  $rows = array();
  $value = t('Add a new service');
  $output  = '<form>';
  $output .= '<input type="button" id="button" value="'.$value.'" onclick="location.href=\''.url("guifi/menu/devel/service/add").'\'"/>';
  $output .= '</form>';

  $headers = array(t('Service ID'), t('Text'), t('Description'), t('Edit'), t('Delete'));

  $sql = db_query('SELECT * FROM {guifi_types} where type="service" ORDER BY id ASC');

  while ($service = db_fetch_object($sql)) {
    $rows[] = array($service->id,
                    $service->text,
                    $service->description,
                    l(guifi_img_icon('edit.png'),'guifi/menu/devel/service/'.$service->id.'/edit',
            array(
              'html' => TRUE,
              'title' => t('edit service'),
              )).'</td><td>'.
                 l(guifi_img_icon('drop.png'),'guifi/menu/devel/service/'.$service->id.'/delete',
            array(
              'html' => TRUE,
              'title' => t('delete service'),
              )));
  }

  $output .= theme('table',$headers,$rows);
  print theme('page',$output, FALSE);
  return;
}

// Services Form
function guifi_devel_services_form($form_state, $service_id) {

  $sql = db_query('SELECT * FROM {guifi_types} WHERE type = "service" and id = %d', $service_id);
  $service = db_fetch_object($sql);

  if ($service_id == 'New' ) {
   $form['new'] = array('#type' => 'hidden', '#value' => TRUE);
  } else {
    $form['id'] = array('#type' => 'hidden','#value' => $service_id);
  }
  $form['text'] = array(
    '#type' => 'textfield',
    '#size' => 24,
    '#maxlength' => 24,
    '#title' => t('Text'),
    '#required' => TRUE,
    '#default_value' => $service->text,
    '#description' =>  t('Text abbreviation of the service.')
  );
  $form['description'] = array(
    '#type' => 'textfield',
    '#title' => t('Description'),
    '#required' => TRUE,
    '#default_value' => $service->description,
    '#description' =>  t('Text description of the service.')
  );

  $form['submit'] = array('#type' => 'submit', '#weight' => 98, '#value' => t('Save'));
  $form['cancel'] = array('#type' => 'markup', '#weight' => 99, '#value' => l(t('Cancel'), "guifi/menu/devel/service"),);

  return $form;
}


function guifi_devel_services_form_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_devel_services_form_submit()',$form_state);

  guifi_devel_services_save($form_state['values']);
  drupal_goto('guifi/menu/devel/service');
  return;
}

function guifi_devel_services_save($edit) {
  global $user;

  $to_mail = $edit->notification;
  $log ='';

  $edit['type'] = 'service';
  guifi_log(GUIFILOG_TRACE,'function guifi_devel_services_save()',$edit);

  _guifi_db_sql('guifi_types',array('id' => $edit['id'], 'type' => 'service'),$edit,$log,$to_mail);

  guifi_notify(
    $to_mail,
    t('The service !service has been created / updated by !user.',array('!service ' => $edit['model'], '!user' => $user->name)),
    $log);
}

function guifi_devel_services_delete_confirm($form_state,$id) {
  guifi_log(GUIFILOG_TRACE,'guifi_devel_service_delete_confirm()',$id);

  $form['id'] = array('#type' => 'hidden', '#value' => $id);
  $qry= db_fetch_object(db_query("SELECT text FROM {guifi_types} WHERE type = 'service' and id = %d", $id));
  return confirm_form(
    $form,
    t('Are you sure you want to delete the service " %service "?',
      array('%service' => $qry->text)),
      "guifi/menu/devel/service",
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel'));
}


function guifi_devel_services_delete_confirm_submit($form, &$form_state) {

  global $user;
  $depth = 0;
  if ($form_state['values']['op'] != t('Delete'))
    return;

  $to_mail = explode(',',$node->notification);
  $log = _guifi_db_delete('guifi_types',array('id' => $form_state['values']['id'], 'type' => 'service'),$to_mail,$depth);
  drupal_set_message($log);
  guifi_notify(
           $to_mail,
           t('The service %name has been DELETED by %user.',array('%name' => $form_state['values']['text'], '%user' => $user->name)),
           $log);
    drupal_goto('guifi/menu/devel/service');
}


// Device Models output
function guifi_devel_devices($devid , $op) {

  switch($devid) {
    case 'add':
     $devid = 'New';
     return drupal_get_form('guifi_devel_devices_form',$devid);
  }
  switch($op) {
    case 'edit':
      
      $jquery1 = HelperMultipleSelect('guifi-devel-devices-form','firmwaresCompatibles', 'firmwaresTots');
      drupal_add_js("if (Drupal.jsEnabled) { $jquery1 }", 'inline');
      return drupal_get_form('guifi_devel_devices_form',$devid);
    case 'delete':
      guifi_log(GUIFILOG_TRACE,'guifi_devel_devices_delete()',$devid);
      return drupal_get_form(
      'guifi_devel_devices_delete_confirm', $devid);
    guifi_devel_devices_delete($devid);
  }
  $rows = array();
  $value = t('Add a new device model');
  $output  = '<form>';
  $output .= '<input type="button" id="button" value="'.$value.'" onclick="location.href=\'/guifi/menu/devel/device/add\'"/>';
  $output .= '</form>';

  $headers = array(t('ID Model'), t('Manufacturer'), t('Model'), t('Edit'), t('Delete'));

  $sql = db_query('SELECT * FROM {guifi_model} ORDER BY mid ASC');

  while ($dev = db_fetch_object($sql)) {
    $query = db_query('SELECT * FROM {guifi_manufacturer} WHERE fid = %d', $dev->fid);
    $manufacturer = db_fetch_object($query);
    $rows[] = array($dev->mid,
                    '<a href="'.$manufacturer->url.'">'.$manufacturer->name.'</a>',
                    '<a href="'.$dev->url.'">'.$dev->model.'</a>',
                    l(guifi_img_icon('edit.png'),'guifi/menu/devel/device/'.$dev->mid.'/edit',
            array(
              'html' => TRUE,
              'title' => t('edit device'),
              )).'</td><td>'.
                 l(guifi_img_icon('drop.png'),'guifi/menu/devel/device/'.$dev->mid.'/delete',
            array(
              'html' => TRUE,
              'title' => t('delete device'),
              )));
  }

  $output .= theme('table',$headers,$rows);
  print theme('page',$output, FALSE);
  return;
}

// Device Models Form
function guifi_devel_devices_form($form_state, $devid) {

  $sql = db_query('SELECT * FROM {guifi_model} WHERE mid = %d', $devid);
  $dev = db_fetch_object($sql);

  if ($devid == 'New' ) {
   $form['new'] = array('#type' => 'hidden', '#value' => TRUE);
  } else {
    $form['mid'] = array('#type' => 'hidden','#value' => $devid);
}
  $query = db_query('SELECT * FROM {guifi_manufacturer}');
  while ($manufacturers = db_fetch_array($query)) {
     $manuf_array[$manufacturers["fid"]] = $manufacturers["name"];
  }
  $form['notification'] = array(
    '#type' => 'textfield',
    '#size' => 60,
    '#maxlength' => 1024,
    '#title' => t('contact'),
    '#required' => TRUE,
    '#element_validate' => array('guifi_emails_validate'),
    '#default_value' => $dev->notification,
    '#description' =>  t('Mailid where changes on the device will be notified, if many, separated by \',\'<br />used for network administration.')
  );
  $form_weight=0;
  $form['fid'] = array(
    '#type' => 'select',
    '#title' => t('Manufacturer'),
    '#required' => TRUE,
    '#default_value' => $dev->fid,
    '#options' => $manuf_array,
    '#prefix' => '<table><tr><td>',
    '#suffix' => '</td>',
    '#weight' => $form_weight++,
  );
  $form['model'] = array(
    '#type' => 'textfield',
    '#title' => t('Model Name'),
    '#required' => TRUE,
    '#default_value' => $dev->model,
    '#size' => 32,
    '#maxlength' => 32,
    '#description' => t('Device model name, please, use a clear and short description.'),
    '#prefix' => '<td>',
    '#suffix' => '</td>',
    '#weight' => $form_weight++,
  );
  $form['radiodev_max'] = array(
    '#type' => 'textfield',
    '#title' => t('Max Radios'),
    '#required' => TRUE,
    '#default_value' => $dev->radiodev_max,
    '#size' => 2,
    '#maxlength' => 2,
    '#description' => t('Maximum number of radios that can handle this device.'),
    '#prefix' => '<td>',
    '#suffix' => '</td>',
    '#weight' => $form_weight++,
  );

  $form['etherdev_max'] = array(
    '#type' => 'textfield',
    '#title' => t('Ethernet Ports'),
    '#required' => TRUE,
    '#default_value' => $dev->etherdev_max,
    '#size' => 2,
    '#maxlength' => 2,
    '#description' => t('Number of ethernet ports on this device.'),
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
    '#weight' => $form_weight++,
  );

  $form['AP'] = array(
    '#type' => 'select',
    '#title' => t('Acces Point'),
    '#required' => TRUE,
    '#default_value' => $dev->AP,
    '#options' => array('Yes' => t('Yes'), 'No' => t("No")),
    '#description' => t('Select yes if this device can be an Access Point.'),
    '#prefix' => '<table><tr><td>',
    '#suffix' => '</td>',
    '#weight' => $form_weight++,
  );

  $form['virtualAP'] = array(
    '#type' => 'select',
    '#title' => t('HostPot / Vlan'),
    '#required' => TRUE,
    '#default_value' => $dev->virtualAP,
    '#options' => array('Yes' => t('Yes'), 'No' => t("No")),
    '#description' => t('Select yes if this device can be a Hostpot or can create vlans.'),
    '#prefix' => '<td>',
    '#suffix' => '</td>',
    '#weight' => $form_weight++,
  );

  $form['client'] = array(
    '#type' => 'select',
    '#title' => t('Statiton capable'),
    '#required' => TRUE,
    '#options' => array('Yes' => t('Yes'), 'No' => t("No")),
    '#default_value' => $dev->client,
    '#description' => t('Select yes if this device can be a station.'),
    '#prefix' => '<td>',
    '#suffix' => '</td>',
    '#weight' => $form_weight++,
  );
  $form['supported'] = array(
    '#type' => 'select',
    '#title' => t('Supported'),
    '#required' => TRUE,
    '#options' => array('Yes' => t('Yes'), 'Deprecated' => t("Deprecated")),
    '#default_value' => $dev->supported,
    '#description' => t('Deprecated devices does not have any support and no appear on the device list select form.'),
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
    '#weight' => $form_weight++,
  );
  $form['interfaces'] = array(
    '#type' => 'textfield',
    '#title' => t('Interfaces'),
    '#required' => TRUE,
    '#default_value' => $dev->interfaces,
    '#size' => 64,
    '#maxlength' => 128,
    '#description' => t('Device interface names for this device model. User | to split de the names, ex: wlan1|wlan2|ether1|ether2'),
    '#prefix' => '<table><tr><td>',
    '#suffix' => '</td></tr></table>',
    '#weight' => $form_weight++,
  );
  $form['url'] = array(
    '#type' => 'textfield',
    '#title' => t('URL'),
    '#required' => TRUE,
    '#default_value' => $dev->url,
    '#size' => 64,
    '#maxlength' => 128,
    '#description' => t('Url where we can see a specs from device model.'),
    '#prefix' => '<table><tr><td>',
    '#suffix' => '</td></tr></table>',
    '#weight' => $form_weight++,
  );
  
  $query = db_query("select
                          usc.id, usc.mid, usc.fid, f.id as firmware_id, f.nom
                      from
                          guifi_pfc_configuracioUnSolclic usc
                          right join guifi_pfc_firmware f on f.id = usc.fid");
  
  $query = db_query("select
                    usc.id, usc.mid, usc.fid, f.id as firmware_id, f.nom
                    from
                    guifi_pfc_firmware f
                    left join
                    guifi_pfc_configuracioUnSolclic usc ON usc.fid = f.id  and usc.mid = %d order by nom asc", $devid);
                    
  
  while ($firmwares = db_fetch_array($query)) {
    //echo "<hr>firmwares[fid]=". $firmwares["fid"] ."firmwares[mid]=". $firmwares["mid"]. " firmwares[nom]=". $firmwares["nom"];
    //echo "<br>firmwares[fid]=". firmwares["fid"];
    if ($firmwares["mid"]==$devid)
    $firms_compatibles[$firmwares["firmware_id"]] = $firmwares["nom"];
    else
    $firms_tots[$firmwares["firmware_id"]] = $firmwares["nom"];
  }
  
  $form['firmwaresCompatibles'] = array(
      '#type' => 'select',
      '#title' => t('firmwares compatibles'),
      '#default_value' => 0,
      '#options' => $firms_compatibles,
      '#description' => t('firmwares compatibles amb aquest model.'),
      '#size' => 10,
      '#multiple' => true,
      '#required' => false,
      '#validated' => true,
      '#prefix' => '<tr><td class="mselects" align="center"><table><tr><td>',
      '#suffix' => '</td><td>',
      '#weight' => $form_weight++,
      '#attributes'=>array('style'=>'width:300px')
  );
  
  $disponiblesButtonOne = '<input type="button" value=">" id="associatsButtonOne" class="selectButtons">';
  $disponiblesButtonAll = '<input type="button" value=">>" id="associatsButtonAll" class="selectButtons">';
  $associatsButtonOne = '<input type="button" value="<" id="disponiblesButtonOne" class="selectButtons">';
  $associatsButtonAll = '<input type="button" value="<<" id="disponiblesButtonAll" class="selectButtons">';
  $botons = $disponiblesButtonOne.'<br>';
  $botons .= $disponiblesButtonAll.'<br>';
  $botons .= $associatsButtonOne.'<br>';
  $botons .= $associatsButtonAll.'<br>';
  
  $form['firmwaresTots'] = array(
        '#type' => 'select',
        '#title' => t('Tots els firmwares'),
        '#default_value' => 0,
        '#options' => $firms_tots,
        '#description' => t('Tots els firmwares.'),
        '#size' => 10,
        '#multiple' => true,
        '#required' => false,
        '#validated' => true,
        '#prefix' => $botons. '</td><td>',
        '#suffix' => '</td></tr></table></td></tr>',
        '#weight' => $form_weight++,
        '#attributes'=>array('style'=>'width:300px')
  );

  $form['submit'] = array('#type' => 'submit',    '#weight' => 99, '#value' => t('Save'));

  return $form;
}


function guifi_devel_devices_form_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_devel_devices_form_submit()',$form_state);

  guifi_devel_devices_save($form_state['values']);
  drupal_goto('guifi/menu/devel/device');
   return;
}

function guifi_devel_devices_save($edit) {
  global $user;

  $to_mail = $edit->notification;
  $log ='';

  guifi_log(GUIFILOG_TRACE,'function guifi_devel_devices_save()',$edit);

  // recollida de parametresFirmware actuals
  $sql= db_query("SELECT distinct(fid) FROM {guifi_pfc_configuracioUnSolclic} WHERE mid = %d order by fid asc", $edit['mid']);
  while ($oldFirms = db_fetch_object($sql)) {
    $alreadyPresent[] = $oldFirms->fid;
  }
  
  //echo "<pre>";var_dump($alreadyPresent);echo "</pre>";
  
  // recollida  de firmwares compatibles
  if (isset($edit['firmwaresCompatibles'])){
    // de tots els que em passen, mirar si ja els tenia a la BD
    foreach ($edit['firmwaresCompatibles'] as $firmware) {
      if (!in_array($firmware, $alreadyPresent)) {
        db_query("INSERT INTO  {guifi_pfc_configuracioUnSolclic} (mid, fid) VALUES (%d, %d)", $edit['mid'], $firmware);
        //echo "<pre>Inserim </pre>";
      }
    }
    

    // de tots els que tenia a la BD, mirar si me'ls han tret i esborrar-los
    foreach ($alreadyPresent as $firmware) {
      if (!in_array($firmware, $edit['firmwaresCompatibles'])) {
        db_query("DELETE FROM {guifi_pfc_configuracioUnSolclic} WHERE mid=%d and fid=%d ", $edit['mid'], $firmware);
      }
    }
  }
  

  _guifi_db_sql('guifi_model',array('mid' => $edit['mid']),$edit,$log,$to_mail);

  guifi_notify(
    $to_mail,
    t('The device model !device has been created / updated by !user.',array('!device' => $edit['model'], '!user' => $user->name)),
    $log);
}

function guifi_devel_devices_delete_confirm($form_state,$mid) {
  guifi_log(GUIFILOG_TRACE,'guifi_devel_device_delete_confirm()',$mid);

  $form['mid'] = array('#type' => 'hidden', '#value' => $mid);
  $qry= db_fetch_object(db_query("SELECT model FROM {guifi_model} WHERE mid = %d", $mid));
  return confirm_form(
    $form,
    t('Are you sure you want to delete the device model " %model "?',
      array('%model' => $qry->model)),
      'guifi/menu/devel/device',
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel'));
}


function guifi_devel_devices_delete_confirm_submit($form, &$form_state) {

  global $user;
  $depth = 0;
  if ($form_state['values']['op'] != t('Delete'))
    return;

  $to_mail = explode(',',$node->notification);
  $log = _guifi_db_delete('guifi_model',array('mid' => $form_state['values']['mid']),$to_mail,$depth);
  drupal_set_message($log);
  guifi_notify(
           $to_mail,
           t('The device model %name has been DELETED by %user.',array('%name' => $form_state['values']['model'], '%user' => $user->name)),
           $log);
    drupal_goto('guifi/menu/devel/device');
}


// Device Firmwares output
function guifi_devel_firmware($firmid , $op) {
  switch($firmid) {
    case 'add':
     $firmid = 'New';
     return drupal_get_form('guifi_devel_firmware_form',$firmid);
  }
  switch($op) {
    case 'edit':
      $jquery1 = HelperMultipleSelect('guifi-devel-firmware-form', 'parametres-associats', 'parametres-disponibles');
      drupal_add_js("if (Drupal.jsEnabled) { $jquery1 }", 'inline');
      $sortida = drupal_get_form('guifi_devel_firmware_form',$firmid);
      return $sortida;
    case 'delete':
      guifi_log(GUIFILOG_TRACE,'guifi_devel_firmware_delete()',$firmid);
      return drupal_get_form(
      'guifi_devel_firmware_delete_confirm', $firmid);
    guifi_devel_firmware_delete($firmid);
  }

  $rows = array();
  $value = t('Add a new firmware');
  $output  = '<from>';
  $output .= '<input type="button" id="button" value="'.$value.'" onclick="location.href=\'/guifi/menu/devel/firmware/add\'"/>';
  $output .= '</form>';

  $headers = array(t('ID'), t('Name'), t('Description'), t('Relations'), t('Edit'), t('Delete'));

  $sql= db_query("SELECT id, text, description, relations FROM {guifi_types} WHERE type='firmware'");


  while ($firmware = db_fetch_object($sql)) {
  $relations = explode('|',$firmware->relations);
  $relations2 = implode(' | ',$relations);
    $rows[] = array($firmware->id,
                    $firmware->text,
                    $firmware->description,
                    $relations2,
                    l(guifi_img_icon('edit.png'),'guifi/menu/devel/firmware/'.$firmware->id.'/edit',
            array(
              'html' => TRUE,
              'title' => t('edit firmware'),
              )).'</td><td>'.
                 l(guifi_img_icon('drop.png'),'guifi/menu/devel/firmware/'.$firmware->id.'/delete',
            array(
              'html' => TRUE,
              'title' => t('delete firmware'),
              )));
  }

  $output .= theme('table',$headers,$rows);
  print theme('page',$output, FALSE);
  return;
}

// Firmwares Form
function guifi_devel_firmware_form($form_state, $firmid) {

  $sql = db_query('SELECT * FROM {guifi_types} WHERE id = %d AND type = \'firmware\'', $firmid);
  $firmware = db_fetch_object($sql);

  if ($firmid == 'New' ) {
   $form['new'] = array('#type' => 'hidden', '#value' => TRUE);
   $form['type'] = array('#type' => 'hidden', '#value' => 'firmware');
  } else {
    $form['id'] = array('#type' => 'hidden','#value' => $firmid);
    $form['type'] = array('#type' => 'hidden', '#value' => $firmware->type);
}
  $form['text'] = array(
    '#type' => 'textfield',
    '#title' => t('Firmware short name'),
    '#required' => TRUE,
    '#default_value' => $firmware->text,
    '#size' => 32,
    '#maxlength' => 32,
    '#description' => t('The firmware name, please, use a clear and short name. ex: "FirmwarevXX" where XX = version'),
    '#prefix' => '<table><tr><td>',
    '#suffix' => '</td>',
    '#weight' => $form_weight++,
  );
  $form['description'] = array(
    '#type' => 'textfield',
    '#title' => t('Firmware long name'),
    '#required' => TRUE,
    '#default_value' => $firmware->description,
    '#size' => 64,
    '#maxlength' => 64,
    '#description' => t('The firmware description, please, use a clear and short description. ex: "FirmwarevXX from creator"'),
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#weight' => $form_weight++,
  );

  $query = db_query(" select
                          pf.fid, pf.pid, p.id, p.nom
                      from
                          guifi_pfc_parametresFirmware pf
                      right join guifi_pfc_parametres p on p.id = pf.pid order by p.id asc");
  while ($parametres = db_fetch_array($query)) {
    if ($parametres["pid"])
      $params_associats[$parametres["pid"]] = $parametres["nom"];
    else
      $params_disponibles[$parametres["id"]] = $parametres["nom"];
  }
  
  $form['parametres_associats'] = array(
    '#type' => 'select',
    '#title' => t('Parametres associats'),
    '#default_value' => 0,
    '#options' => $params_associats,
    '#description' => t('Parametres disponibles per a definir aquest firmware'),
    '#size' => 10,
    '#multiple' => true,
    '#required' => false,
    '#validated' => true,
    '#prefix' => '<tr><td class="mselects" align="center"><table><tr><td>',
    '#suffix' => '</td><td>',
    '#weight' => $form_weight++,
    '#attributes'=>array('style'=>'width:300px')
  );
  
  $disponiblesButtonOne = '<input type="button" value=">" id="associatsButtonOne" class="selectButtons">';
  $disponiblesButtonAll = '<input type="button" value=">>" id="associatsButtonAll" class="selectButtons">';
  $associatsButtonOne = '<input type="button" value="<" id="disponiblesButtonOne" class="selectButtons">';
  $associatsButtonAll = '<input type="button" value="<<" id="disponiblesButtonAll" class="selectButtons">';
  $botons = $disponiblesButtonOne.'<br>';
  $botons .= $disponiblesButtonAll.'<br>';
  $botons .= $associatsButtonOne.'<br>';
  $botons .= $associatsButtonAll.'<br>';
  
  $form['parametres_disponibles'] = array(
      '#type' => 'select',
      '#title' => t('Parametres disponibles'),
      '#default_value' => 0,
      '#options' => $params_disponibles,
      '#description' => t('Parametres associats a la definició d\'aquest firmware.'),
      '#size' => 10,
      '#multiple' => true,
      '#required' => false,
      '#validated' => true,
      '#prefix' => $botons. '</td><td>',
      '#suffix' => '</td></tr></table></td></tr>',
      '#weight' => $form_weight++,
      '#attributes'=>array('style'=>'width:300px')
  );
  
  $query = db_query(" SELECT mid, model FROM {guifi_model} " );
  $relations = explode('|',$firmware->relations);
  while ($models = db_fetch_array($query)) {
    $models_array[$models["mid"]] = $models["model"];
  }
  foreach ($relations as $relation) {
    foreach ($models_array as $id =>$mod) {
      if ($relation == $mod) {
        $value[$mod] = $id;
      }
    }
  }
  
  $form['relations'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Device model relations'),
      '#required' => TRUE,
      '#default_value' => $value,
      '#options' => $models_array,
      '#prefix' => '<tr><td>',
      '#suffix' => '</td></tr></table>',
      '#weight' => 8,
  );

  $form['submit'] = array('#type' => 'submit',    '#weight' => 99, '#value' => t('Save'));

  return $form;
}


function guifi_devel_firmware_form_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_devel_firmware_form_submit()',$form_state);

  guifi_devel_firmware_save($form_state['values']);
  drupal_goto('guifi/menu/devel/firmware');
   return;
}

function guifi_devel_firmware_save($edit) {
  global $user;

  $to_mail = $edit->notification;
  $log ='';
  $query = db_query(" SELECT mid, model FROM {guifi_model} " );
  $relations = $edit['relations'];
  
  // recollida de parametresFirmware actuals
  $sql= db_query("SELECT distinct(pid) FROM {guifi_pfc_parametresFirmware} WHERE fid = %d order by pid asc", $edit['id']);
  while ($oldParamsFirm = db_fetch_object($sql)) {
    $alreadyPresent[] = $oldParamsFirm->pid;
  }
  
  // recollida  de parametres del firmware
  if (isset($edit['parametres_associats'])){
    // de tots els que em passen, mirar si ja els tenia a la BD
    foreach ($edit['parametres_associats'] as $parametre) {
      if (!in_array($parametre, $alreadyPresent)) {
        db_query("INSERT INTO  {guifi_pfc_parametresFirmware} (fid, pid) VALUES (%d, %d)", $edit['id'], $parametre);
      }
    }
    // de tots els que tenia a la BD, mirar si me'ls han tret i esborrar-los
    foreach ($alreadyPresent as $parametre) {
      if (!in_array($parametre, $edit['parametres_associats'])) {
        db_query("DELETE FROM {guifi_pfc_parametresFirmware} WHERE fid=%d and pid=%d ", $edit['id'], $parametre);
      }
    }
  }

  while ($models = db_fetch_array($query)) {
    $models_array[$models["mid"]] = $models["model"];
  }
   if ($relations != '0')
   foreach ($relations as $relation) {
     foreach ($models_array as $id =>$mod) {
       if ($relation == $id) {
         $value[$id] = $mod;
       }
     }
  }




$edit['relations'] = implode('|',$value);
  guifi_log(GUIFILOG_TRACE,'function guifi_devel_firmware_save()',$edit);

  _guifi_db_sql('guifi_types',array('id' => $edit['id'],'type' => $edit['type']),$edit,$log,$to_mail);
drupal_set_message( $edit['relations']);
  guifi_notify(
    $to_mail,
    t('The firmware !firmware has been created / updated by !user.',array('!firmware' => $edit['text'], '!user' => $user->name)),
    $log);
}

function guifi_devel_firmware_delete_confirm($form_state,$id) {
  guifi_log(GUIFILOG_TRACE,'guifi_devel_device_delete_confirm()',$id);

  $form['id'] = array('#type' => 'hidden', '#value' => $id);
  $qry= db_fetch_object(db_query("SELECT text FROM {guifi_types} WHERE type='firmware' AND id=%d", $id));
  return confirm_form(
    $form,
    t('Are you sure you want to delete the firmware " %firmware "?',
      array('%firmware' => $qry->text)),
      'guifi/menu/devel/firmware',
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel'));
}


function guifi_devel_firmware_delete_confirm_submit($form, &$form_state) {

  global $user;
  $depth = 0;
  if ($form_state['values']['op'] != t('Delete'))
    return;

  $to_mail = explode(',',$node->notification);
  $log = _guifi_db_delete('guifi_types',array('id' => $form_state['values']['id'], 'type' => 'firmware'),$to_mail,$depth);
  drupal_set_message($log);
  guifi_notify(
           $to_mail,
           t('The firmware %name has been DELETED by %user.',array('%name' => $form_state['values']['text'], '%user' => $user->name)),
           $log);
    drupal_goto('guifi/menu/devel/firmware');
}

// Device Manufacturers output
function guifi_devel_manufacturer($mid , $op) {

  switch($mid) {
    case 'add':
     $mid = 'New';
     return drupal_get_form('guifi_devel_manufacturer_form',$mid);
  }
  switch($op) {
    case 'edit':
      return drupal_get_form('guifi_devel_manufacturer_form',$mid);
    case 'delete':
      guifi_log(GUIFILOG_TRACE,'guifi_devel_manufacturer_delete()',$mid);
      return drupal_get_form(
      'guifi_devel_manufacturer_delete_confirm', $mid);
    guifi_devel_manufacturer_delete($mid);
  }
  $rows = array();
  $value = t('Add a new device manufacturer');
  $output  = '<from>';
  $output .= '<input type="button" id="button" value="'.$value.'" onclick="location.href=\'/guifi/menu/devel/manufacturer/add\'"/>';
  $output .= '</form>';

  $headers = array(t('ID'), t('Manufacturer'), t('URL'), t('Edit'), t('Delete'));

  $sql = db_query('SELECT * FROM {guifi_manufacturer}');

  while ($mfr = db_fetch_object($sql)) {
    $rows[] = array($mfr->fid,
                    $mfr->name,
                    '<a href="'.$mfr->url.'">'.$mfr->url.'</a>',
                    l(guifi_img_icon('edit.png'),'guifi/menu/devel/manufacturer/'.$mfr->fid.'/edit',
            array(
              'html' => TRUE,
              'title' => t('edit manufacturer'),
              )).'</td><td>'.
                 l(guifi_img_icon('drop.png'),'guifi/menu/devel/manufacturer/'.$mfr->fid.'/delete',
            array(
              'html' => TRUE,
              'title' => t('delete manufacturer'),
              )));
  }

  $output .= theme('table',$headers,$rows);
  print theme('page',$output, FALSE);
  return;
}

// Device Manufacturers Form
function guifi_devel_manufacturer_form($form_state, $mid) {

  $sql = db_query('SELECT * FROM {guifi_manufacturer} WHERE fid = %d', $mid);
  $mfr = db_fetch_object($sql);

  if ($mid == 'New' ) {
   $form['new'] = array('#type' => 'hidden', '#value' => TRUE);
  } else {
    $form['fid'] = array('#type' => 'hidden','#value' => $mid);
}
  $form['notification'] = array(
    '#type' => 'textfield',
    '#size' => 60,
    '#maxlength' => 1024,
    '#title' => t('contact'),
    '#required' => TRUE,
    '#element_validate' => array('guifi_emails_validate'),
    '#default_value' => $mfr->notification,
    '#description' =>  t('Mailid where changes on the device will be notified, if many, separated by \',\'<br />used for network administration.')
  );
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Manufacturer Name'),
    '#required' => TRUE,
    '#default_value' => $mfr->name,
    '#size' => 32,
    '#maxlength' => 32,
    '#description' => t('Manufacturer name, please, use a clear and short description.'),
    '#prefix' => '<table><tr><td>',
    '#suffix' => '</td>',
    '#weight' => 1,
  );
  $form['url'] = array(
    '#type' => 'textfield',
    '#title' => t('URL'),
    '#required' => TRUE,
    '#default_value' => $mfr->url,
    '#size' => 40,
    '#maxlength' => 40,
    '#description' => t('TODO.'),
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
    '#weight' => 2,
  );

  $form['submit'] = array('#type' => 'submit',    '#weight' => 99, '#value' => t('Save'));

  return $form;
}


function guifi_devel_manufacturer_form_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_devel_manufacturer_form_submit()',$form_state);

  guifi_devel_manufacturer_save($form_state['values']);
  drupal_goto('guifi/menu/devel/manufacturer');
   return;
}

function guifi_devel_manufacturer_save($edit) {
  global $user;

  $to_mail = $edit->notification;
  $log ='';

  guifi_log(GUIFILOG_TRACE,'function guifi_devel_manufacturer_save()',$edit);

  _guifi_db_sql('guifi_manufacturer',array('fid' => $edit['fid']),$edit,$log,$to_mail);

  guifi_notify(
    $to_mail,
    t('The device manufacturer !manufacturer has been created / updated by !user.',array('!manufacturer' => $edit['name'], '!user' => $user->name)),
    $log);
}

function guifi_devel_manufacturer_delete_confirm($form_state,$mid) {
  guifi_log(GUIFILOG_TRACE,'guifi_devel_device_delete_confirm()',$mid);

  $form['fid'] = array('#type' => 'hidden', '#value' => $mid);
  $qry= db_fetch_object(db_query("SELECT name FROM {guifi_manufacturer} WHERE fid = %d", $mid));
  return confirm_form(
    $form,
    t('Are you sure you want to delete the device manufacturer " %manufacturer "?',
      array('%manufacturer' => $qry->name)),
      'guifi/menu/devel/manufacturer',
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel'));
}


function guifi_devel_manufacturer_delete_confirm_submit($form, &$form_state) {

  global $user;
  $depth = 0;
  if ($form_state['values']['op'] != t('Delete'))
    return;

  $to_mail = explode(',',$node->notification);
  $log = _guifi_db_delete('guifi_manufacturer',array('fid' => $form_state['values']['fid']),$to_mail,$depth);
  drupal_set_message($log);
  guifi_notify(
           $to_mail,
           t('The device manufacturer %manufacturer has been DELETED by %user.',array('%manufacturer' => $form_state['values']['name'], '%user' => $user->name)),
           $log);
    drupal_goto('guifi/menu/devel/manufacturer');
}

// Firmware Parameter output
function guifi_devel_parameter($id , $op) {
  switch($id) {
    case 'add':
      $id = 'New';
      return drupal_get_form('guifi_devel_parameter_form',$id);
  }
  switch($op) {
    case 'edit':
      return drupal_get_form('guifi_devel_parameter_form',$id);
    case 'delete':
      guifi_log(GUIFILOG_TRACE,'guifi_devel_parameter_delete()',$id);
      return drupal_get_form(
      'guifi_devel_parameter_delete_confirm', $id);
      guifi_devel_parameter_delete($id);
  }
  $rows = array();
  $value = t('Add a new firmware parameter');
  $output  = '<from>';
  $output .= '<input type="button" id="button" value="'.$value.'" onclick="location.href=\'/guifi/menu/devel/parameter/add\'"/>';
  $output .= '</form>';

  $headers = array(t('ID'), t('Parameter'), t('Origen'), t('Edit'), t('Delete'));

  $sql = db_query('SELECT * FROM {guifi_pfc_parametres}');
  while ($parameter = db_fetch_object($sql)) {
    $rows[] = array($parameter->id,
                    $parameter->nom,
                    $parameter->origen,
    l(guifi_img_icon('edit.png'),'guifi/menu/devel/parameter/'.$parameter->id.'/edit',
    array(
              'html' => TRUE,
              'title' => t('edit parameter'),
    )).'</td><td>'.
    l(guifi_img_icon('drop.png'),'guifi/menu/devel/parameter/'.$parameter->id.'/delete',
    array(
              'html' => TRUE,
              'title' => t('delete parameter'),
    )));
  }

  $output .= theme('table',$headers,$rows);
  print theme('page',$output, FALSE);
  return;
}

// FirmWare Parameter Form
function guifi_devel_parameter_form($form_state, $id) {

  $sql = db_query('SELECT * FROM {guifi_pfc_parametres} WHERE id = %d', $id);
  $parameter = db_fetch_object($sql);

  if ($id == 'New' ) {
    $form['new'] = array('#type' => 'hidden', '#value' => TRUE);
  } else {
    $form['id'] = array('#type' => 'hidden','#value' => $id);
  }
  $form['nom'] = array(
    '#type' => 'textfield',
    '#size' => 32,
    '#maxlength' => 32,
    '#title' => t('nom'),
    '#required' => TRUE,
    '#default_value' => $parameter->nom,
    '#description' =>  t('Parameter name.'),
  	'#prefix' => '<table><tr><td>',
    '#suffix' => '</td>',
  
  );
  $form['origen'] = array(
    '#type' => 'textfield',
    '#title' => t('Parameter Origin'),
    '#required' => TRUE,
    '#default_value' => $parameter->origen,
    '#size' => 32,
    '#maxlength' => 32,
    '#description' => t('Parameter name, please, use a clear and short description.'),
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
    '#weight' => 1,
  );


  $form['submit'] = array('#type' => 'submit',    '#weight' => 99, '#value' => t('Save'));

  return $form;
}

function guifi_devel_parameter_form_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_devel_parameter_form_submit()',$form_state);

  guifi_devel_parameter_save($form_state['values']);
  drupal_goto('guifi/menu/devel/parameter');
  return;
}

function guifi_devel_parameter_save($edit) {
  global $user;

  $to_mail = $edit->notification;
  $log ='';

  guifi_log(GUIFILOG_TRACE,'function guifi_devel_parameter_save()',$edit);

  _guifi_db_sql('guifi_pfc_parametres',array('id' => $edit['id']),$edit,$log,$to_mail);

  guifi_notify(
  $to_mail,
  t('The firmware parameter !parameter has been created / updated by !user.',array('!manufacturer' => $edit['name'], '!user' => $user->name)),
  $log);
}

function guifi_devel_parameter_delete_confirm($form_state,$id) {
  guifi_log(GUIFILOG_TRACE,'guifi_devel_parameter_delete_confirm()',$id);

  $form['id'] = array('#type' => 'hidden', '#value' => $id);
  $qry= db_fetch_object(db_query("SELECT nom FROM {guifi_pfc_parametres} WHERE id = %d", $id));
  return confirm_form(
  $form,
  t('Are you sure you want to delete the firmware parameter " %parameter "?',
  array('%parameter' => $qry->nom)),
      'guifi/menu/devel/parameter',
  t('This action cannot be undone.'),
  t('Delete'),
  t('Cancel'));
}


function guifi_devel_parameter_delete_confirm_submit($form, &$form_state) {

  global $user;
  $depth = 0;
  if ($form_state['values']['op'] != t('Delete'))
  return;

  $to_mail = explode(',',$node->notification);
  $log = _guifi_db_delete('guifi_pfc_parametres',array('id' => $form_state['values']['id']),$to_mail,$depth);
  drupal_set_message($log);
  guifi_notify(
  $to_mail,
  t('The firmware parameter %parametre has been DELETED by %user.',array('%parametre' => $form_state['values']['nom'], '%user' => $user->name)),
  $log);
  drupal_goto('guifi/menu/devel/parameter');
}

// Model Feature output
function guifi_devel_modelfeature($id , $op) {
  switch($id) {
    case 'add':
      $id = 'New';
      return drupal_get_form('guifi_devel_modelfeature_form',$id);
  }
  switch($op) {
    case 'edit':
      return drupal_get_form('guifi_devel_modelfeature_form',$id);
    case 'delete':
      guifi_log(GUIFILOG_TRACE,'guifi_devel_modelfeature_delete()',$id);
      return drupal_get_form(
      'guifi_devel_modelfeature_delete_confirm', $id);
      guifi_devel_modelfeature_delete($id);
  }
  $rows = array();
  $value = t('Add a new model feature');
  $output  = '<from>';
  $output .= '<input type="button" id="button" value="'.$value.'" onclick="location.href=\'/guifi/menu/devel/feature/add\'"/>';
  $output .= '</form>';

  $headers = array(t('ID'), t('name'), t('type'));

  $sql = db_query('SELECT * FROM {guifi_pfc_caracteristica}');
  while ($feature = db_fetch_object($sql)) {
    $rows[] = array($feature->id,
    $feature->nom,
    $feature->tipus,
    l(guifi_img_icon('edit.png'),'guifi/menu/devel/feature/'.$feature->id.'/edit',
    array(
              'html' => TRUE,
              'title' => t('edit feature'),
    )).'</td><td>'.
    l(guifi_img_icon('drop.png'),'guifi/menu/devel/feature/'.$feature->id.'/delete',
    array(
              'html' => TRUE,
              'title' => t('delete feature'),
    )));
  }

  $output .= theme('table',$headers,$rows);
  print theme('page',$output, FALSE);
  return;
}

// Model Feature Form
function guifi_devel_modelfeature_form($form_state, $id) {

  $sql = db_query('SELECT * FROM {guifi_pfc_caracteristica} WHERE id = %d', $id);
  $feature = db_fetch_object($sql);

  if ($id == 'New' ) {
    $form['new'] = array('#type' => 'hidden', '#value' => TRUE);
  } else {
    $form['id'] = array('#type' => 'hidden','#value' => $id);
  }
  $form['nom'] = array(
    '#type' => 'textfield',
    '#size' => 32,
    '#maxlength' => 32,
    '#title' => t('name'),
    '#required' => TRUE,
    '#default_value' => $feature->nom,
    '#description' =>  t('Feature name.'),
  	'#prefix' => '<table><tr><td>',
    '#suffix' => '</td>',

  );
  $form['tipus'] = array(
    '#type' => 'textfield',
    '#title' => t('Feature Type'),
    '#required' => TRUE,
    '#default_value' => $feature->tipus,
    '#size' => 32,
    '#maxlength' => 32,
    '#description' => t('Feature type, please, use a clear and short description.'),
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
    '#weight' => 1,
  );


  $form['submit'] = array('#type' => 'submit',    '#weight' => 99, '#value' => t('Save'));

  return $form;
}

function guifi_devel_modelfeature_form_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_devel_modelfeature_form_submit()',$form_state);

  guifi_devel_modelfeature_save($form_state['values']);
  drupal_goto('guifi/menu/devel/feature');
  return;
}

function guifi_devel_modelfeature_save($edit) {
  global $user;

  $to_mail = $edit->notification;
  $log ='';

  guifi_log(GUIFILOG_TRACE,'function guifi_devel_modelfeature_save()',$edit);

  _guifi_db_sql('guifi_pfc_caracteristica',array('id' => $edit['id']),$edit,$log,$to_mail);

  guifi_notify(
  $to_mail,
  t('The Model Feature !modelfeature has been created / updated by !user.',array('!modelfeature' => $edit['nom'], '!user' => $user->name)),
  $log);
}

function guifi_devel_modelfeature_delete_confirm($form_state,$id) {
  guifi_log(GUIFILOG_TRACE,'guifi_devel_modelfeature_delete_confirm()',$id);

  $form['id'] = array('#type' => 'hidden', '#value' => $id);
  $qry= db_fetch_object(db_query("SELECT nom FROM {guifi_pfc_caracteristica} WHERE id = %d", $id));
  return confirm_form(
  $form,
  t('Are you sure you want to delete the Model Feature %modelfeature "?',
  array('%modelfeature' => $qry->nom)),
      'guifi/menu/devel/feature',
  t('This action cannot be undone.'),
  t('Delete'),
  t('Cancel'));
}


function guifi_devel_modelfeature_delete_confirm_submit($form, &$form_state) {

  global $user;
  $depth = 0;
  if ($form_state['values']['op'] != t('Delete'))
  return;

  $to_mail = explode(',',$node->notification);
  $log = _guifi_db_delete('guifi_pfc_caracteristica',array('id' => $form_state['values']['id']),$to_mail,$depth);
  drupal_set_message($log);
  guifi_notify(
  $to_mail,
  t('The firmware parameter %parametre has been DELETED by %user.',array('%parametre' => $form_state['values']['nom'], '%user' => $user->name)),
  $log);
  drupal_goto('guifi/menu/devel/feature');
}

// Configuracio unsolclic output
function guifi_devel_configuracio_usc($id , $op) {
  switch($id) {
    case 'add':
      $id = 'New';
      return drupal_get_form('guifi_devel_configuracio_usc_form',$id);
  }
  switch($op) {
    case 'edit':
      return drupal_get_form('guifi_devel_configuracio_usc_form',$id);
    case 'delete':
      guifi_log(GUIFILOG_TRACE,'guifi_devel_configuracio_usc_delete()',$id);
      return drupal_get_form(
      'guifi_devel_configuracio_usc_delete_confirm', $id);
      guifi_devel_configuracio_usc_delete($id);
  }
  $rows = array();
  $value = t('Add a new Configuracio UnSolclic');
  $output  = '<from>';
  $output .= '<input type="button" id="button" value="'.$value.'" onclick="location.href=\'/guifi/menu/devel/configuraciousc/add\'"/>';
  $output .= '</form>';

  $headers = array(t('ID'), t('mid'), t('fid'), t('enabled'), t('plantilla'), t('tipologia'), t('Edit'), t('Delete'));

  $sql = db_query('SELECT * FROM {guifi_pfc_configuracioUnSolclic}');
  while ($configuraciousc = db_fetch_object($sql)) {
    $rows[] = array($configuraciousc->id,
    $configuraciousc->mid,
    $configuraciousc->fid,
    $configuraciousc->enabled,
    $configuraciousc->plantilla,
    $configuraciousc->tipologia,
    l(guifi_img_icon('edit.png'),'guifi/menu/devel/configuraciousc/'.$configuraciousc->id.'/edit',
    array(
              'html' => TRUE,
              'title' => t('edit configuracio unsolclic'),
    )).'</td><td>'.
    l(guifi_img_icon('drop.png'),'guifi/menu/devel/configuraciousc/'.$configuraciousc->id.'/delete',
    array(
              'html' => TRUE,
              'title' => t('delete configuracio unsolclic'),
    )));
  }

  $output .= theme('table',$headers,$rows);
  print theme('page',$output, FALSE);
  return;
}

// Configuracio unsolclic Form
function guifi_devel_configuracio_usc_form($form_state, $id) {

  $sql = db_query('SELECT * FROM {guifi_pfc_configuracioUnSolclic} WHERE id = %d', $id);
  $configuraciousc = db_fetch_object($sql);

  if ($id == 'New' ) {
    $form['new'] = array('#type' => 'hidden', '#value' => TRUE);
  } else {
    $form['id'] = array('#type' => 'hidden','#value' => $id);
  }
  $form['mid'] = array(
    '#type' => 'textfield',
    '#size' => 32,
    '#maxlength' => 32,
    '#title' => t('Model Id'),
    '#required' => TRUE,
    '#default_value' => $configuraciousc->mid,
    '#description' =>  t('Model Id.'),
  	'#prefix' => '<table><tr><td>',
    '#suffix' => '</td>',
  );
  $form['fid'] = array(
    '#type' => 'textfield',
    '#size' => 32,
    '#maxlength' => 32,
    '#title' => t('Firmware Id'),
    '#required' => TRUE,
    '#default_value' => $configuraciousc->fid,
    '#description' =>  t('Firmware Id.'),
  	'#prefix' => '<table><tr><td>',
    '#suffix' => '</td>',

  );
  $form['enabled'] = array(
    '#type' => 'textfield',
    '#title' => t('Enabled'),
    '#required' => TRUE,
    '#default_value' => $configuraciousc->enabled,
    '#size' => 32,
    '#maxlength' => 32,
    '#description' => t('Enabled'),
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
    '#weight' => 1,
  );
  $form['plantilla'] = array(
    '#type' => 'textfield',
    '#title' => t('Template File'),
    '#required' => TRUE,
    '#default_value' => $configuraciousc->plantilla,
    '#size' => 32,
    '#maxlength' => 32,
    '#description' => t('Template File'),
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
    '#weight' => 1,
  );
  $form['tipologia'] = array(
    '#type' => 'textfield',
    '#title' => t('Tipology'),
    '#required' => TRUE,
    '#default_value' => $configuraciousc->tipologia,
    '#size' => 32,
    '#maxlength' => 32,
    '#description' => t('Tipology'),
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
    '#weight' => 1,
  );


  $form['submit'] = array('#type' => 'submit',    '#weight' => 99, '#value' => t('Save'));

  return $form;
}

function guifi_devel_configuracio_usc_form_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_devel_configuracio_usc_form_submit()',$form_state);

  guifi_devel_configuracio_usc_save($form_state['values']);
  drupal_goto('guifi/menu/devel/configuraciousc');
  return;
}

function guifi_devel_configuracio_usc_save($edit) {
  global $user;

  $to_mail = $edit->notification;
  $log ='';

  guifi_log(GUIFILOG_TRACE,'function guifi_devel_configuracio_usc_save()',$edit);

  _guifi_db_sql('guifi_pfc_configuracioUnSolclic',array('id' => $edit['id']),$edit,$log,$to_mail);

  guifi_notify(
  $to_mail,
  t('The Configuracio Unsolclic !configuraciousc has been created / updated by !user.',array('!configuraciousc' => $edit['plantilla'], '!user' => $user->name)),
  $log);
}

function guifi_devel_configuracio_usc_delete_confirm($form_state,$id) {
  guifi_log(GUIFILOG_TRACE,'guifi_devel_configuracio_usc_delete_confirm()',$id);

  $form['id'] = array('#type' => 'hidden', '#value' => $id);
  $qry= db_fetch_object(db_query("SELECT plantilla FROM {guifi_pfc_configuracioUnSolclic} WHERE id = %d", $id));
  return confirm_form(
  $form,
  t('Are you sure you want to delete the Configuracio Unsolclic " %configuraciousc "?',
  array('%configuraciousc' => $qry->plantilla)),
      'guifi/menu/devel/configuraciousc',
  t('This action cannot be undone.'),
  t('Delete'),
  t('Cancel'));
}


function guifi_devel_configuracio_usc_delete_confirm_submit($form, &$form_state) {

  global $user;
  $depth = 0;
  if ($form_state['values']['op'] != t('Delete'))
  return;

  $to_mail = explode(',',$node->notification);
  $log = _guifi_db_delete('guifi_pfc_configuracioUnSolclic',array('id' => $form_state['values']['id']),$to_mail,$depth);
  drupal_set_message($log);
  guifi_notify(
  $to_mail,
  t('The Configuracio Unsolclic %configuraciousc has been DELETED by %user.',array('%configuraciousc' => $form_state['values']['plantilla'], '%user' => $user->name)),
  $log);
  drupal_goto('guifi/menu/devel/configuraciousc');
}

function HelperMultipleSelect($formName, $nomSelectAssignats='assignats', $nomSelectDisponibles='disponibles') {
  
  $jquery1 = '
        $().ready(function() {
          $("#disponiblesButtonOne").click(function() {
            return !$("#edit-'.$nomSelectDisponibles.' option:selected").remove().appendTo("#edit-'.$nomSelectAssignats.'");
          });
          $("#associatsButtonOne").click(function() {
            return !$("#edit-'.$nomSelectAssignats.' option:selected").remove().appendTo("#edit-'.$nomSelectDisponibles.'");
          });
          $("#disponiblesButtonAll").click(function() {
            return !$("#edit-'.$nomSelectDisponibles.' option").remove().appendTo("#edit-'.$nomSelectAssignats.'");
          });
          $("#associatsButtonAll").click(function() {
            return !$("#edit-'.$nomSelectAssignats.' option").remove().appendTo("#edit-'.$nomSelectDisponibles.'");
          });
          
          $("#'.$formName.'").submit(function() {
            $("#edit-'.$nomSelectAssignats.' option").each(function(i) {
              $(this).attr("selected", "selected");
            });
          });
        });';
  return $jquery1;
}

?>
