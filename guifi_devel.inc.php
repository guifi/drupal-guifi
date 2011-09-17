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
      ' ',
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
  $form['fid'] = array(
    '#type' => 'select',
    '#title' => t('Manufacturer'),
    '#required' => TRUE,
    '#default_value' => $dev->fid,
    '#options' => $manuf_array,
    '#prefix' => '<table><tr><td>',
    '#suffix' => '</td>',
    '#weight' => 1,
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
    '#weight' => 2,
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
    '#weight' => 3,
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
    '#weight' => 4,
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
    '#weight' => 5,
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
    '#weight' => 6,
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
    '#weight' => 7,
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
    '#weight' => 8,
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
    '#weight' => 9,
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
    '#weight' => 10,
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
      ' ',
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
      return drupal_get_form('guifi_devel_firmware_form',$firmid);
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
    '#weight' => 2,
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
    '#weight' => 3,
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
    '#weight' => 4,
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
      ' ',
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
      ' ',
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

?>
