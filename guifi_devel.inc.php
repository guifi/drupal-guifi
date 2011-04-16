<?php

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
  $url = guifi_img_icon('add.png').'';
  $value = t('Add a new device model');
  $output  = '<from>';
  $output .= '<input type="button" id="button" value="'.$value.'" onclick="location.href=\'/guifi/menu/devel/device/add\'"/>';
  $output .= '</form>';

  $headers = array(t('ID Model'), t('Manufacturer'), t('Model'), t('Edit'), t('Delete'));

  $sql = db_query('SELECT * FROM {guifi_model}');

  while ($dev = db_fetch_object($sql)) {
    $query = db_query('SELECT * FROM {guifi_manufacturer} WHERE fid = %d', $dev->fid);
    $manufacturer = db_fetch_object($query);
    $rows[] = array($dev->mid, $manufacturer->nom, $dev->model, l(guifi_img_icon('edit.png'),'guifi/menu/devel/device/'.$dev->mid.'/edit',
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
  return $output;
}

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
    '#description' => t('Device model name, please, use a clear and short descricption.'),
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
    '#weight' => 6,
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
    '#weight' => 7,
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

  return confirm_form(
    $form,
    t('Are you sure you want to delete the device model " %model "?',
      array('%model' => $mid)),
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
           t('The device model %name has been DELETED by %user.',array('%name' => $model->model, '%user' => $user->name)),
           $log);
    drupal_goto('guifi/menu/devel/device');
}

?>