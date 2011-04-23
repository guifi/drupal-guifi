<?php

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
  $output  = '<from>';
  $output .= '<input type="button" id="button" value="'.$value.'" onclick="location.href=\'/guifi/menu/devel/device/add\'"/>';
  $output .= '</form>';

  $headers = array(t('ID Model'), t('Manufacturer'), t('Model'), t('Edit'), t('Delete'));

  $sql = db_query('SELECT * FROM {guifi_model}');

  while ($dev = db_fetch_object($sql)) {
    $query = db_query('SELECT * FROM {guifi_manufacturer} WHERE fid = %d', $dev->fid);
    $manufacturer = db_fetch_object($query);
    $rows[] = array($dev->mid,
                    '<a href="'.$manufacturer->url.'">'.$manufacturer->nom.'</a>',
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
     $manuf_array[$manufacturers["fid"]] = $manufacturers["nom"];
  }
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
    '#suffix' => '</td></tr></table>',
    '#weight' => 7,
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
    '#suffix' => '</td>',
    '#weight' => 8,
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
    '#weight' => 9,
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
  guifi_log(GUIFILOG_TRACE,'guifi_devl_device_delete_confirm()',$mid);

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
                    $mfr->nom,
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

  $form['nom'] = array(
    '#type' => 'textfield',
    '#title' => t('Manufacturer Name'),
    '#required' => TRUE,
    '#default_value' => $mfr->nom,
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
    t('The device manufacturer !manufacturer has been created / updated by !user.',array('!manufacturer' => $edit['nom'], '!user' => $user->name)),
    $log);
}

function guifi_devel_manufacturer_delete_confirm($form_state,$mid) {
  guifi_log(GUIFILOG_TRACE,'guifi_devl_device_delete_confirm()',$mid);

  $form['fid'] = array('#type' => 'hidden', '#value' => $mid);
  $qry= db_fetch_object(db_query("SELECT nom FROM {guifi_manufacturer} WHERE fid = %d", $mid));
  return confirm_form(
    $form,
    t('Are you sure you want to delete the device manufacturer " %manufacturer "?',
      array('%manufacturer' => $qry->nom)),
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
           t('The device manufacturer %manufacturer has been DELETED by %user.',array('%manufacturer' => $form_state['values']['nom'], '%user' => $user->name)),
           $log);
    drupal_goto('guifi/menu/devel/manufacturer');
}

?>