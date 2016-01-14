<?php

// Services output
function guifi_devel_services($service_id=null, $op=null) {

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

  $sql = db_query('SELECT * FROM {guifi_types} where type = \'service\' ORDER BY id ASC');

  while ($service = $sql->fetchObject()) {
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

  $output .= theme('table',array('header' => $headers, 'rows' => $rows, 'attributes' => array('class'=> array('device-data-med'))));

  return $output;
}

// Services Form
function guifi_devel_services_form($none, $form_state, $service_id,$kk) {

  $sql = db_query('SELECT * FROM {guifi_types} WHERE type = \'service\' and id = :id', array(':id' => $service_id));
  $service = $sql->fetchObject();

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
dsm($edit);
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

function guifi_devel_services_delete_confirm($form_state,$id2, $id) {
  guifi_log(GUIFILOG_TRACE,'guifi_devel_service_delete_confirm()',$id);

  $form['id'] = array('#type' => 'hidden', '#value' => $id);
  $qry = db_query("SELECT text FROM {guifi_types} WHERE type = 'service' and id = :id", array(':id' => $id))->fetchObject();
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
function guifi_devel_devices($devid=null, $op=null) {

  switch($devid) {
    case 'add':
     $devid = 'New';
     $jquery1 = HelperMultipleSelect('guifi-devel-devices-form','firmwaresCompatibles', 'firmwaresTots');
      drupal_add_js($jquery1, 'inline');
     return drupal_get_form('guifi_devel_devices_form',$devid);
  }
  switch($op) {
    case 'edit':

      $jquery1 = HelperMultipleSelect('guifi-devel-devices-form','firmwaresCompatibles', 'firmwaresTots');
      drupal_add_js($jquery1, 'inline');
      return drupal_get_form('guifi_devel_devices_form',$devid);

    case 'duplicate':
      return drupal_get_form('guifi_devel_devices_duplicate_form',$devid);

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

  $headers = array(t('Manufacturer'), t('Model'), t('Class'), t('#<br>Radios'), t('# Eth<br>ports'),
    t('# Opt.<br>ports'), t('Enabled<br>Firms'),
    array('data'=>t('Action'),'colspan'=>3));

  $sql = db_query('SELECT
                        m.mid, m.url as model_url , m.model,  mf.name as manufacturer_name, mf.url as manufacturer_url
                    FROM
                        {guifi_model_specs} m
                        inner join {guifi_manufacturer} mf on mf.fid  = m.fid
                    ORDER BY mf.name asc , m.model ASC');
  $sql = db_query('SELECT
                          m.mid, m.model_class, m.etherdev_max, m.optoports_max, m.radiodev_max, m.url as model_url , m.model,  mf.name as manufacturer_name, mf.url as manufacturer_url, count(usc.id) as enabledUSCs
                      FROM
                          {guifi_model_specs} m
                          inner join {guifi_manufacturer} mf on mf.fid  = m.fid
                          left join {guifi_configuracioUnSolclic} usc on usc.mid = m.mid and usc.enabled = 1
                      GROUP BY  m.mid, model_url, m.model,manufacturer_name, manufacturer_url
                      ORDER BY mf.name asc , m.model ASC, enabledUSCs desc');

  $manufacturer = 'none';
  $manufacturers = array();
  while ($dev = $sql->fetchObject()) {

  	$aclass = array();
  	if (!empty($dev->model_class)) {
  	  foreach (explode('|',$dev->model_class) as $k => $v)
  	    $aclass[] = t($v);
  	}

    if ($manufacturer != $dev->manufacturer_name) {
      $manufacturer = $dev->manufacturer_name;
      $manufacturers[] = $manufacturer;
      $l_manufacturer =  '<a name="M'.count($manufacturers).
        '"><a href="'.$dev->manufacturer_url.'">'.$dev->manufacturer_name.'</a>';
    } else {
      $l_manufacturer = '';
    }

    $rows[] = array(
      $l_manufacturer,
      '<a href="'.$dev->model_url.'">'.$dev->mid.'-'.$dev->model.'</a>',
      implode(', ',$aclass),
      $dev->radiodev_max,
      $dev->etherdev_max,
      $dev->optoports_max,
      $dev->enabledUSCs,
        l(guifi_img_icon('edit.png'),'guifi/menu/devel/device/'.$dev->mid.'/edit',
          array(
            'html' => TRUE,
            'attributes' => array('target' => '_blank','title' => t('edit device'))
          )),
        l(guifi_img_icon('copy.png'),'guifi/menu/devel/device/'.$dev->mid.'/duplicate',
          array(
            'html' => TRUE,
            'attributes' => array('target' => '_blank','title' => t('copy device'))
          )),
        l(guifi_img_icon('drop.png'),'guifi/menu/devel/device/'.$dev->mid.'/delete',
          array(
            'html' => TRUE,
            'attributes' => array('target' => '_blank','title' => t('delete device'))
          )),
      );
  }

  $output .= '<hr>';

  foreach ($manufacturers as $k => $v)
    $output .= '<a href="#M'.($k + 1).'">'.$v.'</a> - ';

  $output .= '<hr>'.theme('table', array('header' => $headers, 'rows' => $rows, ));
  return $output;
}

// Duplicate Device Form
function guifi_devel_devices_duplicate_form($form_state, $devid) {

  $sql = db_query('SELECT * FROM {guifi_model_specs} WHERE mid = :did', array(':did' => $devid));
  $dev = $sql->fetchObject();

  // indicador de new pq volem insertar
  $form['new'] = array('#type' => 'hidden', '#value' => TRUE);

  // id del que volem copiar les propietats
  $form['originalmid'] = array('#type' => 'hidden','#value' => $devid);

  $form['model'] = array(
    '#type' => 'textfield',
    '#title' => t('Model Name'),
    '#required' => TRUE,
    '#default_value' => 'Copy of '. $dev->model,
    '#size' => 32,
    '#maxlength' => 50,
    '#description' => t('Device model name, please, use a clear and short description.<br />
        All Other properties will be automatically transferred.'),
    '#prefix' => '<td>',
    '#suffix' => '</td>',
    '#weight' => $form_weight++,
  );

  $form['submit'] = array('#type' => 'submit',    '#weight' => 99, '#value' => t('Save'));

  return $form;
}

// Device Models Form
function guifi_devel_devices_form($form_state, $formx, $devid) {
  global $user;

  if ($devid == 'New' ) {
    $form['new'] = array('#type' => 'hidden', '#value' => TRUE);

  } else {
    $sql = db_query('SELECT * FROM {guifi_model_specs} WHERE mid = :mid', array(':mid' => $devid));
    $dev = $sql->fetchObject();
    $dev->arr_model_class = explode('|',$dev->model_class);
    $form['mid'] = array('#type' => 'hidden','#value' => $devid);
  }

  $query = db_query('SELECT * FROM {guifi_manufacturer}');
  while ($manufacturers = $query->fetchAssoc()) {
     $manuf_array[$manufacturers["fid"]] = $manufacturers["name"];
  }
  $form_weight=0;

  $form['general'] = array(
    '#type'        => 'fieldset',
    '#title'       => (isset($form['new'])) ? t('General'):
      t('General').' ('.$dev->model.' - '.implode(', ',$dev->arr_model_class).')',
    '#attributes'  => array('class' => array("model-item")),
    '#collapsible' => TRUE,
    '#collapsed'   => (isset($form['new'])) ? FALSE : TRUE,
 );
  $form['general']['model'] = array(
    '#type' => 'textfield',
    '#title' => t('Model Name'),
    '#required' => TRUE,
    '#default_value' => $dev->model,
    '#size' => 32,
    '#maxlength' => 50,
    '#description' => t('Device model name, please,<br>use a clear and short description.'),
//    '#prefix' => '<div>',
//   '#suffix' => '</div>',
    '#weight' => $form_weight++,
  );
  $form['general']['fid'] = array(
    '#type' => 'select',
    '#title' => t('Manufacturer'),
    '#required' => TRUE,
    '#default_value' => $dev->fid,
    '#options' => $manuf_array,
    '#description' => t('Select Device Manufacturer.'),
    '#weight' => $form_weight++,
  );
  $form['general']['arr_model_class'] = array(
    '#type'=>'select',
    '#title'=>t('Model Class'),
    '#required' => TRUE,
    '#options'=>guifi_types('model_class'),
    '#multiple'=>true,
    '#size'=>8,
    '#default_value'=>explode('|',$dev->model_class),
    '#description' => t('Once saved, available fieldsets will change depending on the class selected.'),
    '#prefix' => '<div>',
    '#suffix' => '</div>',
    '#weight' => $form_weight++,
  );
  $form['general']['url'] = array(
    '#type' => 'textfield',
    '#title' => t('URL'),
    '#required' => TRUE,
    '#default_value' => $dev->url,
    '#size' => 80,
    '#maxlength' => 128,
    '#description' => t('Url where we can see a specs from device model.'),
    '#weight' => $form_weight++,
  );
  $form['general']['rackeable'] = array(
    '#type' => 'select',
    '#title' => t('Rackeable'),
    '#required' => TRUE,
    '#default_value' => $dev->rackeable,
    '#options' => array('no',1,2,3,4,5,6,7,8,9,10,11,12),
    '#description' => t("Not rackeable or<br>number of U's"),
    '#weight' => $form_weight++,
  );
  $form['general']['notification'] = array(
    '#type' => 'textfield',
    '#size' => 80,
    '#maxlength' => 1024,
    '#title' => t('contact'),
    '#required' => FALSE,
    '#element_validate' => array('guifi_emails_validate'),
    '#default_value' => (empty($dev->notification)) ? $user->mail : $dev->notification,
    '#weight' => $form_weight++,
//    '#prefix'=>'<br><br>',
    '#description' =>  t('Mailid where changes on the device will be notified, if many, separated by \',\'<br />used for network administration.'),
  );

  $form['continue'] = array(
    '#type' => 'submit',
    '#weight' => 99, '#value' => t('Save & continue edit'));
  $form['submit'] = array(
    '#type' => 'submit',
    '#weight' => 100, '#value' => t('Save'));

  if (isset($form['new'])) {
    return $form;
  }


  $form['ethernet'] = array(
    '#type'        => 'fieldset',
    '#title'       => ($dev->etherdev_max) ?
      t('Ethernet').' ('.$dev->etherdev_max.' ports)' :
      t('Ethernet'),
    '#attributes'  => array('class' => array("model-item")),
    '#collapsible' => TRUE,
    '#collapsed'   => ($dev->etherdev_max) ? TRUE: FALSE,
    '#weight' => $form_weight++,
  );
  $form['ethernet']['etherdev_max'] = array(
    '#type' => 'textfield',
    '#title' => t('Ethernet Ports'),
    '#required' => TRUE,
    '#default_value' => ($dev->etherdev_max) ? ($dev->etherdev_max) : 0,
    '#size' => 2,
    '#maxlength' => 2,
    '#description' => t('# of ethernet ports<br>on this device.'),
    '#weight' => $form_weight++,
  );
  $form['ethernet']['interfaces'] = array(
    '#type' => 'textfield',
    '#title' => t('Interfaces'),
    '#required' => FALSE,
    '#default_value' => $dev->interfaces,
    '#size' => 80,
    '#maxlength' => 240,
    '#description' => t('Device interface names for this device model.<br>Use | to split de the names, ex: p1|p2|p3.... Default is port# (1|2|3...|max)'),
    '#weight' => $form_weight++,
  );

  $form['fiber'] = array(
    '#type'        => 'fieldset',
    '#access'      => in_array('fiber',$dev->arr_model_class),
    '#title'       => ($dev->optoports_max) ?
      t('Fiber Optics').' ('.$dev->optoports_max.' ports)' :
      t('Fiber Optics'),
    '#attributes'  => array('class' => array("model-item")),
    '#collapsible' => TRUE,
    '#collapsed'   => ($dev->optoports_max) ? TRUE : FALSE,
    '#weight' => $form_weight++,
  );
  $form['fiber']['optoports_max'] = array(
    '#type' => 'textfield',
    '#access'      => in_array('fiber',$dev->arr_model_class),
    '#title' => t('Optical ports'),
    '#required' => FALSE,
    '#default_value' => $dev->optoports_max,
    '#size' => 2,
    '#maxlength' => 2,
    '#description' => t('# of optical ports<br>on this device.'),
    '#weight' => $form_weight++,
  );
  $form['fiber']['opto_interfaces'] = array(
    '#type' => 'textfield',
    '#access'      => in_array('fiber',$dev->arr_model_class),
    '#title' => t('Optical Interfaces'),
    '#required' => FALSE,
    '#default_value' => $dev->opto_interfaces,
    '#size' => 80,
    '#maxlength' => 240,
    '#description' => t('Port numbers with SFP slot<br>Use | to split de the numbers (i.e. 21|22|23|24)<br>Use same names as ethernet for shared ports'),
    '#weight' => $form_weight++,
  );

  $form['wireless'] = array(
    '#type'        => 'fieldset',
    '#access'      => in_array('wireless',$dev->arr_model_class),
    '#title'       => ($dev->radiodev_max) ?
      t('Wireless').' ('.$dev->radiodev_max.' ports)' :
      t('Wireless'),
    '#attributes'  => array('class' => array("model-item")),
    '#collapsible' => TRUE,
    '#collapsed'   => ($dev->radiodev_max) ? TRUE : FALSE,
    '#weight' => $form_weight++,
  );
  $form['wireless']['radiodev_max'] = array(
    '#type' => 'textfield',
    '#access'      => in_array('wireless',$dev->arr_model_class),
    '#title' => t('Max Radios'),
    '#required' => TRUE,
    '#default_value' => $dev->radiodev_max,
    '#size' => 2,
    '#maxlength' => 2,
    '#description' => t('Maximum number of radios<br>that can handle this device.'),
    '#weight' => $form_weight++,
  );
  $form['wireless']['winterfaces'] = array(
    '#type' => 'textfield',
    '#title' => t('Wireless Interfaces'),
    '#required' => FALSE,
    '#default_value' => $dev->winterfaces,
    '#size' => 80,
    '#maxlength' => 240,
    '#description' => t('Device Wireless interface names for this device model.<br>Use | to split de the names, ex: wlan1|wlan2|wlan3.... Default is port# (1|2|3...|max)'),
    '#weight' => $form_weight++,
  );
  $form['wireless']['AP'] = array(
    '#type' => 'select',
    '#access'      => in_array('wireless',$dev->arr_model_class),
    '#title' => t('Acces Point'),
    '#required' => TRUE,
    '#default_value' => $dev->AP,
    '#options' => array('Yes' => t('Yes'), 'No' => t("No")),
    '#description' => t('Select yes if this device<br>can be an Access Point.'),
    '#weight' => $form_weight++,
  );
  $form['wireless']['virtualAP'] = array(
    '#type' => 'select',
    '#access'      => in_array('wireless',$dev->arr_model_class),
    '#title' => t('HostPot / Vlan'),
    '#required' => TRUE,
    '#default_value' => $dev->virtualAP,
    '#options' => array('Yes' => t('Yes'), 'No' => t("No")),
    '#description' => t('Select yes if this device<br>can be a Hostpot or can create vlans.'),
    '#weight' => $form_weight++,
  );
  $form['wireless']['client'] = array(
    '#type' => 'select',
    '#access'      => in_array('wireless',$dev->arr_model_class),
    '#title' => t('Statiton capable'),
    '#required' => TRUE,
    '#options' => array('Yes' => t('Yes'), 'No' => t("No")),
    '#default_value' => $dev->client,
    '#description' => t('Select yes if this device<br>can be a station.'),
    '#weight' => $form_weight++,
  );


  $form['firmware'] = array(
    '#type'        => 'fieldset',
    '#access'      => in_array('wireless' ,$dev->arr_model_class) || in_array('router',$dev->arr_model_class),
    '#title'       => t('Firmware'),
    '#attributes'  => array('class' => array("model-item")),
    '#collapsible' => TRUE,
    '#collapsed'   => FALSE,
    '#weight' => $form_weight++,
  );
  $form['firmware']['supported'] = array(
    '#type' => 'select',
    '#access'      => in_array('wireless' ,$dev->arr_model_class) || in_array('router',$dev->arr_model_class),
    '#title' => t('Supported'),
    '#required' => TRUE,
    '#options' => array('Yes' => t('Yes'), 'Deprecated' => t("Deprecated")),
    '#default_value' => $dev->supported,
    '#description' => t('Deprecated devices does not have any support and no appear on the device list select form.'),
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
    '#weight' => $form_weight++,
  );
  $query = db_query("select
                    usc.id, usc.mid, usc.fid, f.id as firmware_id, f.nom
                    from
                    guifi_firmware f
                    left join
                    guifi_configuracioUnSolclic usc ON usc.fid = f.id  and usc.mid = :did order by nom asc", array(':did' => $devid));


  while ($firmwares = $query->fetchAssoc()) {
    if ($firmwares["mid"]==$devid)
      $firms_compatibles[$firmwares["firmware_id"]] = $firmwares["nom"];
    else
      $firms_tots[$firmwares["firmware_id"]] = $firmwares["nom"];
  }

  $form['firmware']['firmwaresCompatibles'] = array(
      '#id' => 'edit-firmwaresCompatibles',
      '#type' => 'select',
      '#access'      => in_array('wireless' ,$dev->arr_model_class) || in_array('router',$dev->arr_model_class),
      '#title' => t('Compatible firmware'),
      '#default_value' => 0,
      '#options' => $firms_compatibles,
      '#description' => t('Compatible firmwares with this model.'),
      '#size' => 10,
      '#multiple' => true,
      '#required' => false,
      '#validated' => true,
      '#prefix' => '<tr><td class="mselects" align="left" colspan="2"><table style="width:575px"><tr><td style="width:250px">',
      '#suffix' => '</td><td  style="width:50px">',
      '#weight' => $form_weight++,
      '#attributes'=>array('style' => array('width:250px;height:350px'))
  );

  $disponiblesButtonOne = '<input type="button" value=">" id="associatsButtonOne" class="selectButtons" style="width:40px;margin:5px 0;">';
  $disponiblesButtonAll = '<input type="button" value=">>" id="associatsButtonAll" class="selectButtons" style="width:40px;margin:5px 0;">';
  $associatsButtonOne = '<input type="button" value="<" id="disponiblesButtonOne" class="selectButtons" style="width:40px;margin:5px 0;">';
  $associatsButtonAll = '<input type="button" value="<<" id="disponiblesButtonAll" class="selectButtons" style="width:40px;margin:5px 0;">';
  $botons = $disponiblesButtonOne.'<br>';
  $botons .= $disponiblesButtonAll.'<br>';
  $botons .= $associatsButtonOne.'<br>';
  $botons .= $associatsButtonAll.'<br>';

  $form['firmware']['firmwaresTots'] = array(
        '#id' => 'edit-firmwaresTots',
        '#type' => 'select',
        '#access'      => in_array('wireless' ,$dev->arr_model_class) || in_array('router',$dev->arr_model_class),
        '#title' => t('All firmwares'),
        '#default_value' => 0,
        '#options' => $firms_tots,
        '#description' => t('All firmwares.'),
        '#size' => 10,
        '#multiple' => true,
        '#required' => false,
        '#validated' => true,
        '#prefix' => $botons. '</td><td  style="width:250px">',
        '#suffix' => '</td></tr></table></td></tr>',
        '#weight' => $form_weight++,
        '#attributes'=>array('style' => array('width:250px;height:350px'))
  );

  return $form;
}


function guifi_devel_devices_form_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_devel_devices_form_submit()',$form_state['values']);

  if (isset($form_state['values']['new']))
    $form_state['values']['etherdev_max'] = 0;

  $id = guifi_devel_devices_save($form_state['values']);

  if ($form_state['values']['op']==t('Save & continue edit'))
    drupal_goto('guifi/menu/devel/device/'.$id.'/edit');
  else
    drupal_goto('guifi/menu/devel/device');
  return;
}

function guifi_devel_devices_duplicate_form_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_devel_devices_duplicate_form_submit()',$form_state);

  $originalmid = $form_state['values']['originalmid'];

  $sql = db_query('SELECT * FROM {guifi_model_specs} WHERE mid = :mid', array(':mid' => $originalmid));
  $originaldev = $sql->fetchObject();

  foreach($originaldev as $property => $value)  {

    // el nom del model no el volem duplicar
    if ($property!='model') $form_state['values'][$property] = $value;
  }

  // eliminem els camps addicionals del control de formularis
  unset($form_state['values']['originalmid']);
  unset($form_state['values']['op']);
  unset($form_state['values']['submit']);
  unset($form_state['values']['form_build_id']);
  unset($form_state['values']['form_token']);
  unset($form_state['values']['form_id']);

  // recuperar els firmwares suportats del model original
  $sql= db_query("SELECT distinct(fid) FROM {guifi_configuracioUnSolclic} WHERE mid = :mid order by fid asc", array(':mid' => $originalmid));
  while ($originalFirms = $sql->fetchObject()) {
    $form_state['values']['firmwaresCompatibles'][$originalFirms->fid] = $originalFirms->fid;
  }

  // desar el nou device
  guifi_devel_devices_save($form_state['values'], $originalmid);

  drupal_goto('guifi/menu/devel/device');
  return;
}

// El parametre OPCIONAL $originalmid serveix per si estem copiant un model , poder aplicar al nou model algunes de les seves propietats
function guifi_devel_devices_save($edit, $originalmid = NULL) {
  global $user;

  $edit['model_class'] = implode('|',$edit['arr_model_class']);

  $to_mail = $edit['notification'];
  $log ='';

  guifi_log(GUIFILOG_TRACE,'function guifi_devel_devices_save()',$edit);

  // guardem primer perque si vinc de un insert  poder tenir el id i operar amb els camps relacionats
  $edit2 = _guifi_db_sql('guifi_model_specs',array('mid' => $edit['mid']),$edit,$log,$to_mail);

  // si estic fent un model nou, copia de $originalmid vull qeu la consulta dels firmwares es basi en les de l'original
  $midFirms = $edit2['mid'];
  if ($originalmid){
    $midFirms = $originalmid;
  }

  // recollida de parametresFirmware actuals
  $sql= db_query("SELECT distinct(fid), plantilla FROM {guifi_configuracioUnSolclic} WHERE mid = :mid order by fid asc", array(':mid' => $midFirms));
  while ($oldFirms = $sql->fetchObject()) {
    $alreadyPresent[] = $oldFirms->fid;
    $plantilles[$oldFirms->fid] =  $oldFirms->plantilla;
  }

  // recollida  de firmwares compatibles
  if (isset($edit['firmwaresCompatibles'])){
    // de tots els que em passen, mirar si ja els tenia a la BD
    foreach ($edit['firmwaresCompatibles'] as $firmware) {

      // si ja el teniem abans no l'insertem, pero si estem fent una copia aleshores si (quan $originalmid != null)
      if (!in_array($firmware, $alreadyPresent)||$originalmid) {

        $params = array(
          'mid' => $edit2['mid'],
          'fid'=> $firmware,
          'notification' => $edit['notification'],
          'plantilla' => unsolclicDefaultTemplate($edit2['model'], $firmwareName),
          'enabled' => 0,
          'new' => true
        );
        // per copiar el valor de la plantilla origen  'planitlla' => $plantilles[$firmware]

        // insertem una nova configuracio USC
        $params = _guifi_db_sql('guifi_configuracioUnSolclic',array('mid' => $params['mid']),$params,$log,$to_mail);

         // TODO si fem el params = de sobre aleshores no cal tornar a el ultim mid, ja em ve del insert!
         // recuperem el id del uscid que acabem de crear
         $uscid = db_query("SELECT max(id) mid FROM {guifi_configuracioUnSolclic} ")->fetchAssoc();

         // aqui cal agafar tots els parametres del firmware i entrarlos a la taula guifi_parametresConfiguracioUnsolclic
         crearParametresConfiguracioUSC($uscid['mid'], $firmware, $to_mail, $user->id);

      }
      // guardem els firmwares que hem afegit
      $kept[] = $firmware;
    }

    // de tots els que tenia a la BD, mirar si me'ls han tret i esborrar-los
    foreach ($alreadyPresent as $firmware) {
      if (!in_array($firmware, $edit['firmwaresCompatibles'])) {
        // IMPORTANT abans de esborrar comprovar que no tinguin configuracions USC validades

        $sql = db_query('SELECT id as uscid, enabled  FROM {guifi_configuracioUnSolclic} WHERE mid = :mid and fid = :fid ', array(':mid' => $edit['mid'], ':fid' => $firmware));
        $configuracions = $sql->fetchObject();

        // si la configuracion USC no esta enabled la borrem
        if (!$configuracions->enabled) {

          // si esborrem la configuracio USC tambe hauriem d'esborrarli els parametres associats
          db_query("DELETE FROM {guifi_configuracioUnSolclic} WHERE mid = :mid and fid = :fid ", array(':mid' => $edit['mid'], ':fid' => $firmware));

          // si esborrem la configuracio USC tambe hauriem d'esborrarli els parametres associats
          db_query("DELETE FROM {guifi_parametresConfiguracioUnsolclic} WHERE uscid = :uid", array(':uid' => $configuracions->uscid));

          $deleted[] = $firmware;
        } else {

          // si la configuracio USC estava enabled la desem per notificar que no s'ha esobrrat
          $kept[] = $firmware;

        }
      }
    }
    if (count($kept)>0) {
      $guardats = implode(', ', $kept);
      $strGuardats = ' Firmwares guardats '. $guardats;
    }
    if (count($deleted)>0) {
      $borrats = implode(', ', $deleted);
      $strBorrats = ' Firmwares borrats '. $borrats;
    }
  }

  drupal_set_message( 'Model Actualitzat : '. $edit['model']);

  guifi_notify(
    $to_mail,
    t('The device model !device has been created / updated by !user.',array('!device' => $edit['model'], '!user' => $user->name)),
    $log);

  return $edit2['mid'];
}

function guifi_devel_devices_delete_confirm($form_state,$mid) {
  guifi_log(GUIFILOG_TRACE,'guifi_devel_device_delete_confirm()',$mid);

  $form['mid'] = array('#type' => 'hidden', '#value' => $mid);
  $qry= db_query("SELECT model FROM {guifi_model_specs} WHERE mid = :mid", array(':mid' => $mid))->fetchObject();
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
  $log = _guifi_db_delete('guifi_model_specs',array('mid' => $form_state['values']['mid']),$to_mail,$depth);
  drupal_set_message($log);
  guifi_notify(
           $to_mail,
           t('The device model %name has been DELETED by %user.',array('%name' => $form_state['values']['model'], '%user' => $user->name)),
           $log);
    drupal_goto('guifi/menu/devel/device');
}


// Device Firmwares output
function guifi_devel_firmware($firmid=null, $op=null) {
  switch($firmid) {
    case 'add':
     $firmid = 'New';
     $jquery1 = HelperMultipleSelect('guifi-devel-firmware-form', 'parametres-associats', 'parametres-disponibles');
      drupal_add_js('jQuery(document).ready(function ()  { '.$jquery1.' });', 'inline');
     return drupal_get_form('guifi_devel_firmware_form',$firmid);
  }
  switch($op) {
    case 'edit':
      $jquery1 = HelperMultipleSelect('guifi-devel-firmware-form', 'parametres-associats', 'parametres-disponibles');
      drupal_add_js('jQuery(document).ready(function ()  { '.$jquery1.' });', 'inline');
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

  $headers = array(t('Id'), t('Name'), t('Description'), t('Enabled'), t('Manages'), t('#USC'),
    array('data'=>t('Operations'),'colspan'=>'2')); //,  t('Edit'), t('Delete'));

  $sql= db_query('select
                      f.id, f.nom, f.descripcio, f.relations, f.enabled, f.managed,
                      count(usc.fid) as enabledUSC
                  from
                      {guifi_firmware{ f
                      left join {guifi_configuracioUnSolclic} usc on usc.fid = f.id and usc.enabled = 1
                  group by f.id, f.nom, f.descripcio, f.relations, f.enabled
                  order by enabled desc ,nom asc');

  while ($firmware = $sql->fetchObject()) {
    ($firmware->enabled) ? $enabled='Enabled' : $enabled='Disabled';
    $rows[] = array($firmware->id,
                    $firmware->nom,
                    $firmware->descripcio,
                    array('data'=>t($enabled),'class'=>$enabled),
                    str_replace('|',', ',$firmware->managed),
                    $firmware->enabledUSC,
                    l(guifi_img_icon('edit.png'),'guifi/menu/devel/firmware/'.$firmware->id.'/edit',
                      array(
                       'html' => TRUE,
                       'attributes'=>array('title' => t('edit firmware')),
                      )),
                    l(guifi_img_icon('drop.png'),'guifi/menu/devel/firmware/'.$firmware->id.'/delete',
                      array(
                        'html' => TRUE,
                        'attributes'=>array('title' => t('delete firmware')),
                    )));
  }

  $output .= theme('table',$headers,$rows,array('class'=>'device-data'));
  print theme('page',$output, FALSE);
  return;
}

// Firmwares Form
function guifi_devel_firmware_form($form_state, $firmid) {

  global $user;

  $firmware = guifi_get_firmware($firmid);

  if ($firmid == 'New' ) {
   $form['new'] = array('#type' => 'hidden', '#value' => TRUE);
  } else {
    $form['id'] = array('#type' => 'hidden','#value' => $firmid);
}
  $form['relations'] = array('#type' => 'hidden', '#value' => $firmware->relations);
  $form['nom'] = array(
    '#type' => 'textfield',
    '#title' => t('Firmware short name'),
    '#required' => TRUE,
    '#default_value' => $firmware->nom,
    '#size' => 32,
    '#maxlength' => 32,
    '#description' => t('The firmware name, please, use a clear and short name. ex: "FirmwarevXX" where XX = version'),
  );

  $form['managed'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Manageability'),
      '#default_value' => $firmware->managed,
      '#description' => t('Capabilities'),
      '#options'=> array(
          'vlans' => t('vlans'),
          'aggregations'=>t('aggregations'),
          'tunnels'=>t('tunnels')
        ),
  );

  $form['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => $firmware->enabled,
      '#description' => t('Check if firmware is avialable for use'),
  );

  $form['notification'] = array(
        '#type' => 'textfield',
        '#size' => 60,
        '#maxlength' => 1024,
        '#title' => t('contact'),
        '#required' => TRUE,
        '#element_validate' => array('guifi_emails_validate'),
        '#default_value' => (empty($firmware->notification)) ?
           $user->mail : $firmware->notification,
        '#description' =>  t('Mailid where changes on the device will be notified, if many, separated by \',\'<br />used for network administration.'),
  );

  $form['descripcio'] = array(
    '#type' => 'textarea',
    '#title' => t('Firmware Description'),
    '#required' => TRUE,
    '#default_value' => $firmware->descripcio,
    '#size' => 64,
    '#maxlength' => 64,
    '#description' => t('The firmware description, please, use a clear and short description. ex: "FirmwarevXX from creator"'),
  );

  $query = db_query(" select
                          pf.fid, pf.pid, p.id, p.nom
                      from
                          guifi_parametres p
                              left join
                          guifi_parametresFirmware pf ON pf.pid = p.id and pf.fid = :fid
                      order by p.nom asc", array(':fid' => $firmid));

  while ($parametres = $query->fetchAssoc()) {
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
    '#description' => t('Parametres associats a la definició d\'aquest firmware.'),
    '#size' => 10,
    '#multiple' => true,
    '#required' => false,
    '#validated' => true,
    '#prefix' => '<table style="width:575px"><tr><td style="width:250px">',
    '#suffix' => '</td><td  style="width:50px">',
    '#weight' => $form_weight++,
    '#attributes'=>array('style' => array('width:250px;height:350px'))
  );

  $disponiblesButtonOne = '<input type="button" value=">" id="associatsButtonOne" class="selectButtons" style="width:40px;margin:5px 0;">';
  $disponiblesButtonAll = '<input type="button" value=">>" id="associatsButtonAll" class="selectButtons" style="width:40px;margin:5px 0;">';
  $associatsButtonOne = '<input type="button" value="<" id="disponiblesButtonOne" class="selectButtons" style="width:40px;margin:5px 0;">';
  $associatsButtonAll = '<input type="button" value="<<" id="disponiblesButtonAll" class="selectButtons" style="width:40px;margin:5px 0;">';
  $botons = $disponiblesButtonOne.'<br>';
  $botons .= $disponiblesButtonAll.'<br>';
  $botons .= $associatsButtonOne.'<br>';
  $botons .= $associatsButtonAll.'<br>';

  $form['parametres_disponibles'] = array(
    '#type' => 'select',
    '#title' => t('Parametres disponibles'),
    '#default_value' => 0,
    '#options' => $params_disponibles,
    '#description' => t('Parametres disponibles per a definir aquest firmware'),
    '#size' => 10,
    '#multiple' => true,
    '#required' => false,
    '#validated' => true,
    '#prefix' => $botons. '</td><td>',
    '#suffix' => '</td></tr></table>',
    '#weight' => $form_weight++,
    '#attributes'=>array('style' => array('width:250px;height:350px'))
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#weight' => 99, '#value' => t('Save')
  );

  //$form['submit'] = array('#type' => 'submit',    '#weight' => $form_weight, '#value' => t('Save'));

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

  $to_mail = $user->mail;
  $log ='';
  $query = db_query(" SELECT id, nom, descripcio, relations, enabled FROM {guifi_firmware} " );

  // Serialize managed field
  foreach($edit['managed'] as $m)
    if (!empty($m))
      $newManaged[] = $m;
  $edit[managed] = implode('|',$newManaged);

  // recollida de parametresFirmware actuals
  $sql= db_query("SELECT distinct(pid) FROM {guifi_parametresFirmware} WHERE fid = :fid order by pid asc", array(':fid' => $edit['id']));
  while ($oldParamsFirm = $sql->fetchObject()) {
    $alreadyPresent[] = $oldParamsFirm->pid;
  }

  // recollida  de parametres del firmware
  if (isset($edit['parametres_associats'])){

    // de tots els que em passen, si encara no els tenia afegir-los
    foreach ($edit['parametres_associats'] as $parametre) {
      if (!in_array($parametre, $alreadyPresent)) {
        $params = array(
          'fid'=> $edit['id'],
          'pid'=> $parametre,
          'notification' => $to_mail,
          'new' => true
        );
        _guifi_db_sql('guifi_parametresFirmware',array('mid' => $params['mid']),$params,$log,$to_mail);

        // busquem les propietats d'aquest nou parametre, dinamic i default_value
        $sql = db_query('SELECT id, dinamic, default_value FROM {guifi_parametres} WHERE id = :id', array(':id' => $parametre));
        $paramInfo = $sql->fetchAssoc();

        // aqui cal agafar tots els unsolclic que funcionen amb aquest firmware i a cadascun afegir-li el parametre nou.
        afegirParametreConfiguracionsUSC($edit['id'], $paramInfo, $to_mail, $user->id);
      }
    }
    // de tots els que tenia a la BD, mirar si me'ls han tret i esborrar-los
    foreach ($alreadyPresent as $parametre) {
      if (!in_array($parametre, $edit['parametres_associats'])) {

        // IMPORTANT abans de esborrar comprovar que no formin part de configuracions USC validades
        $sql = db_query('select
                                  pusc.pid, usc.enabled, usc.id uscid
                              from
                                  {guifi_parametresConfiguracioUnsolclic} pusc
                                  inner join {guifi_configuracioUnSolclic} usc on usc.id = pusc.uscid
                              where usc.fid = :fid and pusc.pid = :pid ', array(':fid' => $edit['id'], ':pid' => $parametre));

        $configuracions = $sql->fetchObject();

        if (!$configuracions->enabled) {

          db_query("DELETE FROM {guifi_parametresFirmware} WHERE fid=%d and pid=%d ", $edit['id'], $parametre);
          db_query("DELETE FROM {guifi_parametresConfiguracioUnsolclic} WHERE uscid=%d and pid=%d ", $configuracions->uscid, $parametre);

          $deleted[] = $parametre;
        } else {

          // si la configuracio USC estava enabled la desem per notificar que no s'ha esobrrat
          $kept[] = $parametre;

        }
      }
    }
    if (count($kept)>0) {
      $guardats = implode(', ', $kept);
      $strGuardats = ' Parametres guardats '. $guardats;
    }
    if (count($deleted)>0) {
      $borrats = implode(', ', $deleted);
      $strBorrats = ' Parametres borrats '. $borrats;
    }
  }


  //guifi_log(GUIFILOG_TRACE,'function guifi_devel_firmware_save()',$edit);
  _guifi_db_sql('guifi_firmware',array('id' => $edit['id']),$edit,$log,$to_mail);

drupal_set_message( $edit['nom'].' Actualitzat : '. $strGuardats . $strBorrats);
  guifi_notify(
    $to_mail,
    t('The firmware !firmware has been created / updated by !user.',array('!firmware' => $edit['text'], '!user' => $user->name)),
    $log);
}

function guifi_devel_firmware_delete_confirm($form_state,$id) {
  guifi_log(GUIFILOG_TRACE,'guifi_devel_device_delete_confirm()',$id);

  $form['id'] = array('#type' => 'hidden', '#value' => $id);
  $qry= db_query("SELECT text FROM {guifi_types} WHERE type='firmware' AND id = :id", array(':id' => $id))->fetchObject();
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
function guifi_devel_manufacturer($mid=null, $op=null) {

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

  $sql = db_query('SELECT * FROM {guifi_manufacturer} order by name asc	');

  while ($mfr = $sql->fetchObject()) {
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

  $output .= theme('table',$headers,$rows,array('class'=>'device-data-med'));
  print theme('page',$output, FALSE);
  return;
}

// Device Manufacturers Form
function guifi_devel_manufacturer_form($form_state, $mid) {

  $sql = db_query('SELECT * FROM {guifi_manufacturer} WHERE fid = :fid', array(':fid' => $mid));
  $mfr = $sql->fetchObject();

  if ($mid == 'New' ) {
   $form['new'] = array('#type' => 'hidden', '#value' => TRUE);
  } else {
    $form['fid'] = array('#type' => 'hidden','#value' => $mid);
}

  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Manufacturer Name'),
    '#required' => TRUE,
    '#default_value' => $mfr->name,
    '#size' => 32,
    '#maxlength' => 32,
    '#description' => t('Manufacturer name, please, use a clear and short description.'),
    '#prefix' => '<table  style="width:700px"><tr><td valign="top" style="width:350px">',
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
    '#description' => t('Manufacturer Url for more info.'),
    '#prefix' => '<td valign="top"  style="width:350px">',
    '#suffix' => '</td></tr>',
    '#weight' => 2,
  );
  $form['notification'] = array(
      '#type' => 'textfield',
      '#size' => 32,
      '#maxlength' => 1024,
      '#title' => t('contact'),
      '#required' => TRUE,
      '#element_validate' => array('guifi_emails_validate'),
      '#default_value' => $mfr->notification,
      '#description' =>  t('Mailid where changes on the device will be notified, if many, separated by \',\' used for network administration.'),
      '#prefix' => '<tr><td>',
      '#suffix' => '</td></tr></table>',
      '#weight' => 3,
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
  $qry= db_query("SELECT name FROM {guifi_manufacturer} WHERE fid = :fid", array(':fid' => $mid))->fetchObject();
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
function guifi_devel_parameter($id=null, $op=null) {
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

  $headers = array(t('ID'), t('Parameter'), t('Default Value'), t('Dynamic'), t('Origen'), t('Edit'), t('Delete'));

  $sql = db_query('SELECT * FROM {guifi_parametres} order by nom ASC');
  while ($parameter = $sql->fetchObject()) {
    $rows[] = array($parameter->id,
                    $parameter->nom,
                    $parameter->default_value,
                    $parameter->dinamic,
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

  $output .= theme('table',$headers,$rows,array('class'=>'device-data'));
  print theme('page',$output, FALSE);
  return;
}

// FirmWare Parameter Form
function guifi_devel_parameter_form($form_state, $id) {

  $sql = db_query('SELECT * FROM {guifi_parametres} WHERE id = :id', array(':id' => $id));
  $parameter = $sql->fetchObject();

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
    '#suffix' => '</td></tr>',
    '#weight' => $form_weight++
  );

  $form['origen'] = array(
    '#type' => 'textfield',
    '#title' => t('Parameter Origin'),
    '#required' => false,
    '#default_value' => $parameter->origen,
    '#size' => 80,
    '#maxlength' => 80,
    '#description' => t('Parameter name, please, use a clear and short description.'),
    '#prefix' => '<tr><td>',
    '#suffix' => '</td></tr>',
    '#weight' => $form_weight++
  );

  $form['default_value'] = array(
    '#type' => 'textfield',
    '#title' => t('Default Value'),
    '#required' => false,
    '#default_value' => $parameter->default_value,
    '#size' => 32,
    '#maxlength' => 32,
    '#description' => t('Parameter default value'),
    '#prefix' => '<tr><td>',
    '#suffix' => '</td></tr>',
    '#weight' => $form_weight++
  );

  $form['dinamic'] = array(
    '#type' => 'checkbox',
    '#title' => t('Dinamic'),
    '#default_value' => $parameter->dinamic,
    '#prefix' => '<tr><td>',
    '#suffix' => '</td></tr>',
    '#weight' => $form_weight++
  );
  $form['submit'] = array(
      '#type' => 'submit',
      '#prefix' => '<tr><td>',
      '#suffix' => '</td></tr></table>',
      '#weight' => 99, '#value' => t('Save'));

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

  $to_mail = $user->mail;
  $log ='';

  guifi_log(GUIFILOG_TRACE,'function guifi_devel_parameter_save()',$edit);

  _guifi_db_sql('guifi_parametres',array('id' => $edit['id']),$edit,$log,$to_mail);

  guifi_notify(
  $to_mail,
  t('The firmware parameter !parameter has been created / updated by !user.',array('!manufacturer' => $edit['name'], '!user' => $user->name)),
  $log);
}

function guifi_devel_parameter_delete_confirm($form_state,$id) {
  guifi_log(GUIFILOG_TRACE,'guifi_devel_parameter_delete_confirm()',$id);

  $form['id'] = array('#type' => 'hidden', '#value' => $id);
  $qry= db_query("SELECT nom FROM {guifi_parametres} WHERE id = :id", array(':id' => $id))->fetchObject();
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
  $log = _guifi_db_delete('guifi_parametres',array('id' => $form_state['values']['id']),$to_mail,$depth);
  drupal_set_message($log);
  guifi_notify(
  $to_mail,
  t('The firmware parameter %parametre has been DELETED by %user.',array('%parametre' => $form_state['values']['nom'], '%user' => $user->name)),
  $log);
  drupal_goto('guifi/menu/devel/parameter');
}

// Model Feature output
function guifi_devel_modelfeature($id=null, $op=null) {
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

  $sql = db_query('SELECT * FROM {guifi_caracteristica}');
  while ($feature = $sql->fetchObject()) {
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

  $output .= theme('table',$headers,$rows,array('class'=>'device-data-med'));
  print theme('page',$output, FALSE);
  return;
}

// Model Feature Form
function guifi_devel_modelfeature_form($form_state, $id) {

  $sql = db_query('SELECT * FROM {guifi_caracteristica} WHERE id = :id', array(':id' => $id));
  $feature = $sql->fetchObject();

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

  $to_mail = $user->mail;
  $log ='';

  guifi_log(GUIFILOG_TRACE,'function guifi_devel_modelfeature_save()',$edit);

  _guifi_db_sql('guifi_caracteristica',array('id' => $edit['id']),$edit,$log,$to_mail);

  guifi_notify(
  $to_mail,
  t('The Model Feature !modelfeature has been created / updated by !user.',array('!modelfeature' => $edit['nom'], '!user' => $user->name)),
  $log);
}

function guifi_devel_modelfeature_delete_confirm($form_state,$id) {
  guifi_log(GUIFILOG_TRACE,'guifi_devel_modelfeature_delete_confirm()',$id);

  $form['id'] = array('#type' => 'hidden', '#value' => $id);
  $qry= db_query("SELECT nom FROM {guifi_caracteristica} WHERE id = :id", array(':id' => $id))->fetchObject();
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
  $log = _guifi_db_delete('guifi_caracteristica',array('id' => $form_state['values']['id']),$to_mail,$depth);
  drupal_set_message($log);
  guifi_notify(
  $to_mail,
  t('The firmware parameter %parametre has been DELETED by %user.',array('%parametre' => $form_state['values']['nom'], '%user' => $user->name)),
  $log);
  drupal_goto('guifi/menu/devel/feature');
}

// Configuracio unsolclic output
function guifi_devel_configuracio_usc($id=null, $op=null) {
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

  $headers = array(t('Manufacturer'), t('Model'), t('FirmWare'), t('enabled'), t('#parameters'), t('Edit'), t('Delete'));

  $sql = db_query('SELECT
        usc.id, usc.mid, usc.fid, usc.enabled, usc.tipologia,
        mf.name as fabricant, m.model,
        f.nom as nomfirmware,
        count(pusc.id) as numparameters
    FROM
        {guifi_configuracioUnSolclic} usc
        inner {join guifi_firmware} f on f.id = usc.fid
        inner {join guifi_model_specs} m on m.mid = usc.mid
        inner {join guifi_manufacturer} mf on mf.fid = m.fid
        left {join guifi_parametresConfiguracioUnsolclic} pusc on pusc.uscid = usc.id
     group by usc.id, usc.mid, usc.fid, usc.enabled, usc.tipologia
     order by usc.enabled desc, fabricant asc, model asc, nomfirmware asc
  ');



  $radioMode  = array(0 => "Ap or AP with WDS",
                      1 => "Wireless Client",
                      2 => "Wireless Bridge",
                      3 => "Routed Client");
  while ($configuraciousc = $sql->fetchObject()) {
    $rows[] = array(
      $configuraciousc->fabricant,
      $configuraciousc->model,
      $configuraciousc->nomfirmware,
      $configuraciousc->enabled,
      //$radioMode[$configuraciousc->tipologia],
      $configuraciousc->numparameters,
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

  $output .= theme('table',$headers,$rows,array('class'=>'device-data'));
  print theme('page',$output, FALSE);
  return;
}

// Configuracio unsolclic Form
function guifi_devel_configuracio_usc_form($form_state, $id) {

  $sql = db_query('SELECT
      usc.id, usc.mid, usc.fid, usc.enabled, usc.snmp_id, usc.plantilla, mf.fid as mfid, mf.name as manufacturer, m.model, f.nom as nomfirmware
  FROM
      guifi_configuracioUnSolclic usc
      inner join guifi_firmware f on f.id = usc.fid
      inner join guifi_model_specs m on m.mid = usc.mid
      inner join guifi_manufacturer mf on mf.fid = m.fid
   where usc.id= :uid', array(':uid' => $id));
  $configuraciousc = $sql->fetchObject();

  if ($id == 'New' ) {
    $form['new'] = array('#type' => 'hidden', '#value' => TRUE);
  } else {
    $form['id'] = array('#type' => 'hidden','#value' => $id);
  }

  $manufacturerName = $configuraciousc->manufacturer;
  $modelName = $configuraciousc->model;
  $firmwareName = $configuraciousc->nomfirmware;

  $manufacturerLink = '<a href="'.base_path().'guifi/menu/devel/manufacturer/'.$configuraciousc->mfid.'/edit">'.$configuraciousc->manufacturer.'</a>';
  $modelLink = '<a href="'.base_path().'guifi/menu/devel/device/'.$configuraciousc->mid.'/edit">'.$configuraciousc->model.'</a>';
  $firmwareLink = '<a href="'.base_path().'guifi/menu/devel/firmware/'.$configuraciousc->fid.'/edit">'.$configuraciousc->nomfirmware.'</a>';

  $form['mid'] = array(
    '#type' => 'hidden',
    '#size' => 32,
    '#maxlength' => 32,
    '#title' => t('Model Id'),
    '#required' => TRUE,
    '#default_value' => $configuraciousc->mid,
    '#description' =>  t('Model Id.'),
  );
  $form['fid'] = array(
    '#type' => 'hidden',
    '#size' => 32,
    '#maxlength' => 32,
    '#title' => t('Firmware Id'),
    '#required' => TRUE,
    '#default_value' => $configuraciousc->fid,
    '#description' =>  t('Firmware Id.'),
  );
  $form_weight=0;
  $form['enabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enabled'),
    '#default_value' => $configuraciousc->enabled,
    '#prefix' => "<table><tr><th>$manufacturerLink</th><th>$modelLink</th><th>$firmwareLink</th><th>",
    '#suffix' => '</th></tr>',
    '#weight' => $form_weight++,
  );


  $sql = db_query('select
                        usc.id, usc.pid, usc.valor, p.dinamic, p.nom, p.origen
                    from
                        guifi_parametresConfiguracioUnsolclic usc
                        inner join guifi_parametres p on p.id = usc.pid
                    where
                        usc.uscid = :uid
                    order by usc.dinamic asc, p.nom asc', array(':uid' => $id));
  $totalParams = 0;
  while ($paramUSC = $sql->fetchObject()) {
    $rows[] = array(
    $paramUSC->nom,
    ($paramUSC->dinamic)?$paramUSC->origen:' - ',
    ($paramUSC->dinamic)?' - ':$paramUSC->valor,
    ((!$paramUSC->dinamic)?l(
      guifi_img_icon('edit.png'),
      'guifi/menu/devel/paramusc/'.$paramUSC->id.'/edit',
      array('html' => TRUE,
            'title' => t('edit configuracio unsolclic'),
      )):' - ').'</td><td>'.
    l(
      guifi_img_icon('drop.png'),
      'guifi/menu/devel/paramusc/'.$paramUSC->id.'/delete',
      array(
                'html' => TRUE,
                'title' => t('delete paramusc'),
      ))
    );
    $totalParams++;
  }

  $form['snmp_id'] = array(
      '#type' => 'textarea',
      '#type' => 'textfield',
      '#title' => t('Default SNMP Interface(s) or ID'),
      '#required' => TRUE,
      '#default_value' => $configuraciousc->snmp_id,
      '#description' => t('<b>Use this format: eth0|eth1 </b>, ( ap|client ) where the first interface is in <b>AP</b> mode and the second in <b>STA ( client )</b> mode.'),
      '#prefix' => '<td>',
      '#suffix' => '</td>',
      '#weight' => $form_weight++,
      '#cols' => 60,
      '#rows' => 30,
  );

  $form['plantilla'] = array(
      '#type' => 'textarea',
      '#title' => t('Template File'),
      '#required' => TRUE,
      '#default_value' => $configuraciousc->plantilla,
      '#description' => t('Template File'),
      '#prefix' => '<tr><td colspan="4">',
      '#suffix' => '</td></tr><tr><td colspan="4">Paràmetres Associats al USC : '. $totalParams .'<br>',
      '#weight' => $form_weight++,
      '#cols' => 60,
      '#rows' => 30,
  );
  //$form['submit'] = array('#type' => 'submit',    '#weight' => 99, '#value' => t('Save'));
  $headers = array(t('Params'), t('Origin'), t('Fixed Value'),t('Edit'), t('Delete'));
  $output .= theme('table',$headers,$rows);
  $form['submit'] = array(
    '#type' => 'submit',
    '#prefix' => $output. '</td></tr><tr><td colspan="4">',
          '#suffix' => '</td></tr></table>',
    '#weight' => 99, '#value' => t('Save'));


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

  $to_mail = $user->mail;
  $log ='';

  guifi_log(GUIFILOG_TRACE,'function guifi_devel_configuracio_usc_save()',$edit);

  _guifi_db_sql('guifi_configuracioUnSolclic',array('id' => $edit['id']),$edit,$log,$to_mail);

  // TODO inicialitzar el nom de la combinacio per treure-la despres de gravar
  guifi_notify(
  $to_mail,
  t('The Configuracio Unsolclic !configuraciousc has been created / updated by !user.',array('!configuraciousc' => '', '!user' => $user->name)),
  $log);
}

function guifi_devel_configuracio_usc_delete_confirm($form_state,$id) {
  guifi_log(GUIFILOG_TRACE,'guifi_devel_configuracio_usc_delete_confirm()',$id);

  $form['id'] = array('#type' => 'hidden', '#value' => $id);
  $qry= db_query("SELECT plantilla FROM {guifi_configuracioUnSolclic} WHERE id = :id", array(':id' => $id))->fetchObject();
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
  $log = _guifi_db_delete('guifi_configuracioUnSolclic',array('id' => $form_state['values']['id']),$to_mail,$depth);
  drupal_set_message($log);
  guifi_notify(
  $to_mail,
  t('The Configuracio Unsolclic %configuraciousc has been DELETED by %user.',array('%configuraciousc' => $form_state['values']['plantilla'], '%user' => $user->name)),
  $log);
  drupal_goto('guifi/menu/devel/configuraciousc');
}

// Firmware Parameter output
function guifi_devel_paramusc($id=null, $op=null) {

  switch($id) {
    case 'add':
      $id = 'New';
      return drupal_get_form('guifi_devel_paramusc_form',$id);
  }
  switch($op) {
    case 'edit':
      return drupal_get_form('guifi_devel_paramusc_form',$id);
    case 'delete':
      guifi_log(GUIFILOG_TRACE,'guifi_devel_paramusc_delete()',$id);
      return drupal_get_form(
      'guifi_devel_paramusc_delete_confirm', $id);
      guifi_devel_paramusc_delete($id);
  }
  $rows = array();
  $value = t('Add a new firmware parameter');
  $output  = '<from>';
  $output .= '<input type="button" id="button" value="'.$value.'" onclick="location.href=\'/guifi/menu/devel/parameter/add\'"/>';
  $output .= '</form>';

  $headers = array(t('ID'), t('Parameter'), t('Origen'), t('Edit'), t('Delete'));

  $sql = db_query('SELECT * FROM {guifi_parametres}');
  while ($parameter = $sql->fetchObject()) {
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
function guifi_devel_paramusc_form($form_state, $id) {

  $sql = db_query('select
                      pusc.id, pusc.uscid, pusc.valor, pusc.dinamic, p.id as pid, p.nom, f.id, f.nom as nomfirmware, d.mid, d.model,  mf.fid, mf.name as manufacturer
                  from
                      guifi_parametresConfiguracioUnsolclic pusc
                      inner join guifi_parametres p on p.id = pusc.pid
                      inner join guifi_configuracioUnSolclic usc on usc.id = pusc.uscid
                      inner join guifi_firmware f on f.id = usc.fid
                      inner join guifi_model_specs d on d.mid = usc.mid
                      inner join guifi_manufacturer mf on mf.fid = d.fid
                  where
                      pusc.id = :id', array(':id' => $id));
  $parameter = $sql->fetchObject();

  $manufacturerLink = '<a href="'.base_path().'guifi/menu/devel/device/'.$parameter->mfid.'/edit">'.$parameter->manufacturer.'</a>';
  $modelLink = '<a href="'.base_path().'guifi/menu/devel/model/'.$parameter->mid.'/edit">'.$parameter->model.'</a>';
  $firmwareLink = '<a href="'.base_path().'guifi/menu/devel/firmware/'.$parameter->fid.'/edit">'.$parameter->nomfirmware.'</a>';

  if ($id == 'New' ) {
    $form['new'] = array('#type' => 'hidden', '#value' => TRUE);
  } else {
    $form['id'] = array('#type' => 'hidden','#value' => $id);
    $form['uscid'] = array('#type' => 'hidden','#value' => $parameter->uscid);

  }
  if (!$parameter->dinamic){
    $form['valor'] = array(
      '#type' => 'textfield',
      '#size' => 32,
      '#maxlength' => 32,
      '#title' => t('Valor for '.  $parameter->nom),
      '#required' => TRUE,
      '#default_value' => $parameter->valor,
      '#description' =>  t('Parameter Fixed Value.'),
      '#prefix' => "<table><tr><th>$manufacturerLink</th><th>$modelLink</th><th>$firmwareLink</th><th>$parameter->nomparametre</th><tr><td>",
      '#suffix' => '</td>',
    );
  }
  $form['submit'] = array(
    '#type' => 'submit',
    '#prefix' => '<tr><td colspan="4">',
    '#suffix' => '</td></tr></table>',
    '#weight' => 99, '#value' => t('Save'));

  return $form;
}

function guifi_devel_paramusc_form_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_devel_paramusc_form_submit()',$form_state);

  guifi_devel_paramusc_save($form_state['values']);
  drupal_goto('guifi/menu/devel/configuraciousc/'. $form_state['values']['uscid'] .'/edit');
  return;
}

function guifi_devel_paramusc_save($edit) {
  global $user;

  $to_mail = $user->mail;
  $log ='';

  guifi_log(GUIFILOG_TRACE,'function guifi_devel_parameter_save()',$edit);

  _guifi_db_sql('guifi_parametresConfiguracioUnsolclic',array('id' => $edit['id']),$edit,$log,$to_mail);

  guifi_notify(
  $to_mail,
  t('The guifi_parametresConfiguracioUnsolclic !parameter has been created / updated by !user.',array('!manufacturer' => $edit['name'], '!user' => $user->name)),
  $log);
}

function guifi_devel_paramusc_delete_confirm($form_state,$id) {
  guifi_log(GUIFILOG_TRACE,'guifi_devel_parameter_delete_confirm()',$id);

  $form['id'] = array('#type' => 'hidden', '#value' => $id);
  $qry= db_query("SELECT
                                      p.nom, pusc.uscid as uscid
                                  FROM
                                      guifi_parametresConfiguracioUnsolclic pusc
                                      inner join guifi_parametres p on p.id = pusc.pid
                                  WHERE
                                      pusc.id = :id", array(':id' => $id))->fetchObject();

  return confirm_form(
  $form,
  t('Are you sure you want to delete the USC Configuration parameter " %parameter "?' ,
  array('%parameter' => $qry->nom )),
      "guifi/menu/devel/configuraciousc/$qry->uscid/edit",
  t('This action cannot be undone.'),
  t('Delete'),
  t('Cancel'));
}


function guifi_devel_paramusc_delete_confirm_submit($form, &$form_state) {

  global $user;
  $depth = 0;
  if ($form_state['values']['op'] != t('Delete'))
  return;

  $to_mail = explode(',',$node->notification);
  $log = _guifi_db_delete('guifi_parametresConfiguracioUnsolclic',array('id' => $form_state['values']['id']),$to_mail,$depth);
  drupal_set_message($log);
  guifi_notify(
  $to_mail,
  t('The USC Configuration parameter %parametre has been DELETED by %user.',array('%parametre' => $form_state['values']['nom'], '%user' => $user->name)),
  $log);
  drupal_goto('guifi/menu/devel/parameter');
}


function HelperMultipleSelect($formName, $nomSelectAssignats='assignats', $nomSelectDisponibles='disponibles') {

  $jquery1 = '
        jQuery(document).ready(function() {
          jQuery("#disponiblesButtonOne").click(function() {
            return !jQuery("#edit-'.$nomSelectDisponibles.' option:selected").remove().appendTo("#edit-'.$nomSelectAssignats.'");
          });
          jQuery("#associatsButtonOne").click(function() {
            return !jQuery("#edit-'.$nomSelectAssignats.' option:selected").remove().appendTo("#edit-'.$nomSelectDisponibles.'");
          });
          jQuery("#disponiblesButtonAll").click(function() {
            return !jQuery("#edit-'.$nomSelectDisponibles.' option").remove().appendTo("#edit-'.$nomSelectAssignats.'");
          });
          jQuery("#associatsButtonAll").click(function() {
            return !jQuery("#edit-'.$nomSelectAssignats.' option").remove().appendTo("#edit-'.$nomSelectDisponibles.'");
          });

          jQuery("#'.$formName.'").submit(function() {
            jQuery("#edit-'.$nomSelectAssignats.' option").each(function(i) {
              jQuery(this).attr("selected", "selected");
            });
          });
        });';
  return $jquery1;
}

function crearParametresConfiguracioUSC($uscid, $fid, $notification, $userid) {
  $sql = db_query("SELECT p.id, :uid, p.nom, dinamic, default_value
                   FROM guifi_parametres p
                   INNER JOIN guifi_parametresFirmware pf ON pf.pid = p.id  AND pf.fid = :fid", array(':uid' => $uscid,  ':fid' => $fid));

  while ($paramsFirmware = $sql->fetchObject()) {


    $params = array(
              'pid' => $paramsFirmware->id,
              'uscid' => $uscid,
              'dinamic' => $paramsFirmware->dinamic,
              'valor' => $paramsFirmware->default_value,
              'notification' => $notification,
              'user_created' => $userid,
              'new' => true
    );
    _guifi_db_sql('guifi_parametresConfiguracioUnsolclic',array('id' => $params['id']),$params,$log,$notification);



    //db_query("INSERT INTO  {guifi_parametresConfiguracioUnsolclic} (pid, uscid, dinamic, notification, user_created)
    //VALUES (%d, %d, 0,'%s', %d)", $paramsFirmware->id, $uscid, $notification, $userid);
  }
  return true;
}
/**
 *
 * Ressegueix tots els unsolclics que te un firmware i li afegeix un parametre
 * @param int $fid
 * @param int $parametre
 * @param string $notification
 * @param int $userid
 */
function afegirParametreConfiguracionsUSC($fid, $parametre, $notification, $userid) {
  $sql = db_query("select id , enabled from guifi_configuracioUnSolclic where fid = :fid", array(':fid' => $fid));
  while ($configuracioUSC = $sql->fetchObject()) {
    $params = array(
              'pid' => $parametre['id'],
              'uscid' => $configuracioUSC->id,
              'dinamic' => $parametre['dinamic'],
              'valor' => $parametre['default_value'],
              'notification' => $notification,
              'user_created' => $userid,
              'new' => true
    );
    _guifi_db_sql('guifi_parametresConfiguracioUnsolclic',array('id' => $params['id']),$params, $log, $notification);
  }
  return true;
}

function getUSCid($model, $firmware){
  $sql = db_query('SELECT
                      pusc.pid,
                      pusc.uscid,
                      pusc.valor,
                      pusc.dinamic,
                      usc.id,
                      usc.mid,
                      usc.fid
                  FROM
                      guifi_parametresConfiguracioUnsolclic pusc
                          inner join
                      guifi_configuracioUnSolclic usc ON usc.id = pusc.uscid
                  where
                      pusc.pid = :model and usc.fid = :firm', array(':model' => $model, ':firm' => $firmware));
  $usc = $sql->fetchObject();

  return $uscId->id;
}

function unsolclicDefaultTemplate($model, $firmware) {
  $version = "vX.XX-TODO";
  $listsURL = 'https://llistes.guifi.net/sympa/info/guifi-dev';
  $sourceURL = 'https://gitorious.org/guifi/drupal-guifi';
  $getStartedURL = 'http://wiki.guifi.net/wiki/Documentaci%C3%B3_de_guifi.net';

  $output  = _outln_comment_get();
  $output .= _outln_comment_get($model);
  $output .= _outln_comment_get($firmware. 'unsolclic version: '.$version);
  $output .= _outln_comment_get();
  $output .= _outln_comment_get(t("This firmware configuration is under construction or not yet started development."));
  $output .= _outln_comment_get(t("If you want to collaborate and contribute with code to make it work,"));
  $output .= _outln_comment_get(t("please subscribre to our development lists at:"));
  $output .= _outln_comment_get($listsURL);
  $output .= _outln_comment_get(t("The source for this application can be downloaded from the GIT repository:"));
  $output .= _outln_comment_get($sourceURL);
  $output .= _outln_comment_get(t("To get started with guifi.net development visit the documentation :"));
  $output .= _outln_comment_get($getStartedURL);
  $output .= _outln_comment_get(t("Contributions are always welcome!"));
  $output .= _outln_comment();
  return $output;
}

?>
