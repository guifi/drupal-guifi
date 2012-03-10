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

  $headers = array(t('ID Model'), t('Manufacturer'), t('Model'), t('Enabled Firms'), t('Edit'), t('Delete'), t('Duplicate'));

  $sql = db_query('SELECT
                        m.mid, m.url as model_url , m.model,  mf.name as manufacturer_name, mf.url as manufacturer_url
                    FROM
                        {guifi_model} m
                        inner join {guifi_manufacturer} mf on mf.fid  = m.fid
                    ORDER BY mf.name asc , m.model ASC');
  $sql = db_query('SELECT
                          m.mid, m.url as model_url , m.model,  mf.name as manufacturer_name, mf.url as manufacturer_url, count(usc.id) as enabledUSCs
                      FROM
                          {guifi_model} m
                          inner join {guifi_manufacturer} mf on mf.fid  = m.fid
                          left join {guifi_pfc_configuracioUnSolclic} usc on usc.mid = m.mid and usc.enabled = 1
                      GROUP BY  m.mid, model_url, m.model,manufacturer_name, manufacturer_url
                      ORDER BY enabledUSCs desc , mf.name asc , m.model ASC');
  
  while ($dev = db_fetch_object($sql)) {
    $rows[] = array($dev->mid,
                      '<a href="'.$dev->manufacturer_url.'">'.$dev->manufacturer_name.'</a>',
                      '<a href="'.$dev->model_url.'">'.$dev->model.'</a>',
                      $dev->enabledUSCs,
    l(guifi_img_icon('edit.png'),'guifi/menu/devel/device/'.$dev->mid.'/edit',
    array(
                'html' => TRUE,
                'title' => t('edit device'),
    )).'</td><td>'.
    l(guifi_img_icon('drop.png'),'guifi/menu/devel/device/'.$dev->mid.'/delete',
    array(
                'html' => TRUE,
                'title' => t('delete device'),
    )).'</td><td>'.
    l(guifi_img_icon('edit.png'),'guifi/menu/devel/device/'.$dev->mid.'/duplicate',
    array(
                'html' => TRUE,
                'title' => t('duplicatedevice')
    ))
    );
  }
  
  $output .= theme('table',$headers,$rows);
  print theme('page',$output, FALSE);
  return;
}

// Duplicate Device Form
function guifi_devel_devices_duplicate_form($form_state, $devid) {

  $sql = db_query('SELECT * FROM {guifi_model} WHERE mid = %d', $devid);
  $dev = db_fetch_object($sql);

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
  $form_weight=0;
  $form['fid'] = array(
    '#type' => 'select',
    '#title' => t('Manufacturer'),
    '#required' => TRUE,
    '#default_value' => $dev->fid,
    '#options' => $manuf_array,
    '#description' => t('Select Device Manufacturer.'),
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
    '#maxlength' => 50,
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
    '#prefix' => '<tr><td colspan="2" align="left">',
    '#suffix' => '</td></tr>',
    '#weight' => $form_weight++,
  );

  
  $query = db_query("select
                    usc.id, usc.mid, usc.fid, f.id as firmware_id, f.nom
                    from
                    guifi_pfc_firmware f
                    left join
                    guifi_pfc_configuracioUnSolclic usc ON usc.fid = f.id  and usc.mid = %d order by nom asc", $devid);

//   $query = db_query(" select
//                             pf.fid, pf.pid, p.id, p.nom
//                         from
//                             guifi_pfc_parametres p
//                                 left join
//                             guifi_pfc_parametresFirmware pf ON pf.pid = p.id and pf.fid = %d
//                         order by p.nom asc",$devid);
    
  
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
      '#prefix' => '<tr><td class="mselects" align="left" colspan="2"><table style="width:575px"><tr><td style="width:250px">',
      '#suffix' => '</td><td  style="width:50px">',
      '#weight' => $form_weight++,
      '#attributes'=>array('style'=>'width:250px;height:350px')
  );
  
  $disponiblesButtonOne = '<input type="button" value=">" id="associatsButtonOne" class="selectButtons" style="width:40px;margin:5px 0;">';
  $disponiblesButtonAll = '<input type="button" value=">>" id="associatsButtonAll" class="selectButtons" style="width:40px;margin:5px 0;">';
  $associatsButtonOne = '<input type="button" value="<" id="disponiblesButtonOne" class="selectButtons" style="width:40px;margin:5px 0;">';
  $associatsButtonAll = '<input type="button" value="<<" id="disponiblesButtonAll" class="selectButtons" style="width:40px;margin:5px 0;">';
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
        '#prefix' => $botons. '</td><td  style="width:250px">',
        '#suffix' => '</td></tr></table></td></tr>',
        '#weight' => $form_weight++,
        '#attributes'=>array('style'=>'width:250px;height:350px')
  );
  
  $form['notification'] = array(
      '#type' => 'textfield',
      '#size' => 60,
      '#maxlength' => 1024,
      '#title' => t('contact'),
      '#required' => TRUE,
      '#element_validate' => array('guifi_emails_validate'),
      '#default_value' => $dev->notification,
      '#weight' => $form_weight++,
      '#description' =>  t('Mailid where changes on the device will be notified, if many, separated by \',\'<br />used for network administration.'),
      '#prefix' => '<tr><td colspan="2">',
      '#suffix' => '</td></tr>',
  
  );

  $form['submit'] = array(
  '#type' => 'submit',
  '#prefix' => '<tr><td colspan="2">',
        '#suffix' => '</td></tr></table>',
  '#weight' => 99, '#value' => t('Save'));

  return $form;
}


function guifi_devel_devices_form_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_devel_devices_form_submit()',$form_state);

  guifi_devel_devices_save($form_state['values']);
  
  drupal_goto('guifi/menu/devel/device');
   return;
}

function guifi_devel_devices_duplicate_form_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_devel_devices_duplicate_form_submit()',$form_state);

  $originalmid = $form_state['values']['originalmid'];
  
  $sql = db_query('SELECT * FROM {guifi_model} WHERE mid = %d', $originalmid);
  $originaldev = db_fetch_object($sql);
  
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
  $sql= db_query("SELECT distinct(fid) FROM {guifi_pfc_configuracioUnSolclic} WHERE mid = %d order by fid asc", $originalmid);
  while ($originalFirms = db_fetch_object($sql)) {
    $form_state['values']['firmwaresCompatibles'][$originalFirms->fid] = $originalFirms->fid;
  }
  
  // desar el nou device
  guifi_devel_devices_save($form_state['values'], $originalmid);

  drupal_goto('guifi/menu/devel/device');
  return;
}

// El parametre OPCIONAL $originalmid serveix per si estem copiant un model , poder aplicar al nou model algunes de les seves propietats
function guifi_devel_devices_save($edit, $originalmid=null) {
  global $user;

  $to_mail = $edit['notification'];
  $log ='';

  guifi_log(GUIFILOG_TRACE,'function guifi_devel_devices_save()',$edit);
  
  // guardem primer perque si vinc de un insert  poder tenir el id i operar amb els camps relacionats
  $edit2 = _guifi_db_sql('guifi_model',array('mid' => $edit['mid']),$edit,$log,$to_mail);
  
  // si estic fent un model nou, copia de $originalmid vull qeu la consulta dels firmwares es basi en les de l'original
  $midFirms = $edit2['mid'];
  if ($originalmid){
    $midFirms = $originalmid;
  }
  
  // recollida de parametresFirmware actuals
  $sql= db_query("SELECT distinct(fid), plantilla FROM {guifi_pfc_configuracioUnSolclic} WHERE mid = %d order by fid asc", $midFirms);
  while ($oldFirms = db_fetch_object($sql)) {
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
        $params = _guifi_db_sql('guifi_pfc_configuracioUnSolclic',array('mid' => $params['mid']),$params,$log,$to_mail);
         
         // TODO si fem el params = de sobre aleshores no cal tornar a el ultim mid, ja em ve del insert!
         // recuperem el id del uscid que acabem de crear
         $uscid = db_fetch_array(db_query("SELECT max(id) mid FROM {guifi_pfc_configuracioUnSolclic} "));
         
         // aqui cal agafar tots els parametres del firmware i entrarlos a la taula guifi_pfc_parametresConfiguracioUnsolclic
         crearParametresConfiguracioUSC($uscid['mid'], $firmware, $to_mail, $user->id);
         
      }
      // guardem els firmwares que hem afegit
      $kept[] = $firmware;
    }
    
    // de tots els que tenia a la BD, mirar si me'ls han tret i esborrar-los
    foreach ($alreadyPresent as $firmware) {
      if (!in_array($firmware, $edit['firmwaresCompatibles'])) {
        // IMPORTANT abans de esborrar comprovar que no tinguin configuracions USC validades
        
        $sql = db_query('SELECT id as uscid, enabled  FROM {guifi_pfc_configuracioUnSolclic} WHERE mid=%d and fid=%d ', $edit['mid'], $firmware);
        $configuracions = db_fetch_object($sql);
        
        // si la configuracion USC no esta enabled la borrem
        if (!$configuracions->enabled) {
          
          // si esborrem la configuracio USC tambe hauriem d'esborrarli els parametres associats
          db_query("DELETE FROM {guifi_pfc_configuracioUnSolclic} WHERE mid=%d and fid=%d ", $edit['mid'], $firmware);
          
          // si esborrem la configuracio USC tambe hauriem d'esborrarli els parametres associats
          db_query("DELETE FROM {guifi_pfc_parametresConfiguracioUnsolclic} WHERE uscid=%d", $configuracions->uscid);
          
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

  drupal_set_message( 'Model Actualitzat : '. $strGuardats . $strBorrats);
  
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

  $headers = array(t('ID'), t('Name'), t('Description'), t('Enabled'), t('used on #USC'),  t('Edit'), t('Delete'));

  $sql= db_query('select
                      f.id, f.nom, f.descripcio, f.relations, f.enabled,
                      count(usc.fid) as enabledUSC
                  from
                      {guifi_pfc_firmware{ f
                      left join {guifi_pfc_configuracioUnSolclic} usc on usc.fid = f.id and usc.enabled = 1
                  group by f.id, f.nom, f.descripcio, f.relations, f.enabled
                  order by enabled desc ,nom asc');

  while ($firmware = db_fetch_object($sql)) {
    $rows[] = array($firmware->id,
                    $firmware->nom,
                    $firmware->descripcio,
                    $firmware->enabled,
                    $firmware->enabledUSC,
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

  $sql= db_query('SELECT id, nom, descripcio, relations, notification FROM {guifi_pfc_firmware} WHERE id = %d', $firmid);
  $firmware = db_fetch_object($sql);

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
    '#prefix' => '<table><tr><td>',
    '#suffix' => '',
    '#weight' => $form_weight++,
  );
  
  $form['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => $firmware->enabled,
      '#description' => t('Check if firmware is avialable for use'),
      '#prefix' => '',
      '#suffix' => '</td></tr>',
      '#weight' => $form_weight++,
  );
  
  $form['descripcio'] = array(
    '#type' => 'textarea',
    '#title' => t('Firmware Description'),
    '#required' => TRUE,
    '#default_value' => $firmware->descripcio,
    '#size' => 64,
    '#maxlength' => 64,
    '#description' => t('The firmware description, please, use a clear and short description. ex: "FirmwarevXX from creator"'),
    '#prefix' => '<tr><td>',
    '#suffix' => '</td></tr>',
    '#weight' => $form_weight++,
  );

  $query = db_query(" select
                          pf.fid, pf.pid, p.id, p.nom
                      from
                          guifi_pfc_parametres p
                              left join
                          guifi_pfc_parametresFirmware pf ON pf.pid = p.id and pf.fid = %d
                      order by p.nom asc",$firmid);
  
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
    '#description' => t('Parametres associats a la definició d\'aquest firmware.'),
    '#size' => 10,
    '#multiple' => true,
    '#required' => false,
    '#validated' => true,
    '#prefix' => '<tr><td class="mselects" align="left" colspan="2"><table style="width:575px"><tr><td style="width:250px">',
    '#suffix' => '</td><td  style="width:50px">',
    '#weight' => $form_weight++,
    '#attributes'=>array('style'=>'width:250px;height:350px')
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
    '#suffix' => '</td></tr></table></td></tr>',
    '#weight' => $form_weight++,
    '#attributes'=>array('style'=>'width:250px;height:350px')
  );
  
  
  $form['notification'] = array(
        '#type' => 'textfield',
        '#size' => 60,
        '#maxlength' => 1024,
        '#title' => t('contact'),
        '#required' => TRUE,
        '#element_validate' => array('guifi_emails_validate'),
        '#default_value' => $firmware->notification,
        '#weight' => $form_weight++,
        '#description' =>  t('Mailid where changes on the device will be notified, if many, separated by \',\'<br />used for network administration.'),
        '#prefix' => '<tr><td colspan="2">',
        '#suffix' => '</td></tr>',
  
  );
  
  $form['submit'] = array(
    '#type' => 'submit',
    '#prefix' => '<tr><td>',
          '#suffix' => '</td></tr></table>',
    '#weight' => 99, '#value' => t('Save'));
  
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
  $query = db_query(" SELECT id, nom, descripcio, relations, enabled FROM {guifi_pfc_firmware} " );
  
  // recollida de parametresFirmware actuals
  $sql= db_query("SELECT distinct(pid) FROM {guifi_pfc_parametresFirmware} WHERE fid = %d order by pid asc", $edit['id']);
  while ($oldParamsFirm = db_fetch_object($sql)) {
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
        _guifi_db_sql('guifi_pfc_parametresFirmware',array('mid' => $params['mid']),$params,$log,$to_mail);
        
        // aqui cal agafar tots els unsolclic que funcionen amb aquest firmware i a cadascun afegir-li el parametre nou.
        afegirParametreConfiguracionsUSC($edit['id'], $parametre, $to_mail, $user->id);
      }
    }
    // de tots els que tenia a la BD, mirar si me'ls han tret i esborrar-los
    foreach ($alreadyPresent as $parametre) {
      if (!in_array($parametre, $edit['parametres_associats'])) {
        
        // IMPORTANT abans de esborrar comprovar que no formin part de configuracions USC validades
        $sql = db_query('select
                                  pusc.pid, usc.enabled, usc.id uscid
                              from
                                  {guifi_pfc_parametresConfiguracioUnsolclic} pusc
                                  inner join {guifi_pfc_configuracioUnSolclic} usc on usc.id = pusc.uscid
                              where usc.fid = %d and pusc.pid = %d ', $edit['id'], $parametre);
        
        $configuracions = db_fetch_object($sql);
        
        if (!$configuracions->enabled) {
        
          db_query("DELETE FROM {guifi_pfc_parametresFirmware} WHERE fid=%d and pid=%d ", $edit['id'], $parametre);
          db_query("DELETE FROM {guifi_pfc_parametresConfiguracioUnsolclic} WHERE uscid=%d and pid=%d ", $configuracions->uscid, $parametre);
          
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
  _guifi_db_sql('guifi_pfc_firmware',array('id' => $edit['id']),$edit,$log,$to_mail);
  
drupal_set_message( $edit['nom'].' Actualitzat : '. $strGuardats . $strBorrats);
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

  $sql = db_query('SELECT * FROM {guifi_manufacturer} order by name asc	');

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

  $sql = db_query('SELECT * FROM {guifi_pfc_parametres} order by nom ASC');
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

  $to_mail = $user->mail;
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

  $to_mail = $user->mail;
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

  $headers = array(t('Manufacturer'), t('Model'), t('FirmWare'), t('enabled'), t('#parameters'), t('Edit'), t('Delete'));

  $sql = db_query('SELECT
        usc.id, usc.mid, usc.fid, usc.enabled, usc.tipologia,
        mf.name as fabricant, m.model,
        f.nom as nomfirmware,
        count(pusc.id) as numparameters
    FROM
        {guifi_pfc_configuracioUnSolclic} usc
        inner {join guifi_pfc_firmware} f on f.id = usc.fid
        inner {join guifi_model} m on m.mid = usc.mid
        inner {join guifi_manufacturer} mf on mf.fid = m.fid
        left {join guifi_pfc_parametresConfiguracioUnsolclic} pusc on pusc.uscid = usc.id
     group by usc.id, usc.mid, usc.fid, usc.enabled, usc.tipologia
     order by usc.enabled desc, fabricant asc, model asc, nomfirmware asc
  ');
  
  
  
  $radioMode  = array(0 => "Ap or AP with WDS",
                      1 => "Wireless Client",
                      2 => "Wireless Bridge",
                      3 => "Routed Client");
  while ($configuraciousc = db_fetch_object($sql)) {
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

  $output .= theme('table',$headers,$rows);
  print theme('page',$output, FALSE);
  return;
}

// Configuracio unsolclic Form
function guifi_devel_configuracio_usc_form($form_state, $id) {

  $sql = db_query('SELECT
      usc.id, usc.mid, usc.fid, usc.enabled, usc.plantilla, mf.fid as mfid, mf.name as manufacturer, m.model, f.nom as nomfirmware
  FROM
      guifi_pfc_configuracioUnSolclic usc
      inner join guifi_pfc_firmware f on f.id = usc.fid
      inner join guifi_model m on m.mid = usc.mid
      inner join guifi_manufacturer mf on mf.fid = m.fid
   where usc.id= %d', $id);
  $configuraciousc = db_fetch_object($sql);

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
                        usc.id, usc.pid, usc.valor, usc.dinamic, p.nom, p.origen
                    from
                        guifi_pfc_parametresConfiguracioUnsolclic usc
                        inner join guifi_pfc_parametres p on p.id = usc.pid
                    where
                        usc.uscid = %d
                    order by usc.dinamic asc, p.nom asc', $id);
  $totalParams = 0;
  while ($paramUSC = db_fetch_object($sql)) {
    $rows[] = array(
    $paramUSC->nom,
    $paramUSC->dinamic,
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
  $headers = array(t('Parametre'), t('Dinamic'), t('Origen'), t('Valor Fixe'),t('Edit'), t('Delete'));
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

// Firmware Parameter output
function guifi_devel_paramusc($id , $op) {

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
function guifi_devel_paramusc_form($form_state, $id) {

  $sql = db_query('select
                      pusc.id, pusc.uscid, pusc.valor, pusc.dinamic, p.id as pid, p.nom, f.id, f.nom as nomfirmware, d.mid, d.model,  mf.fid, mf.name as manufacturer
                  from
                      guifi_pfc_parametresConfiguracioUnsolclic pusc
                      inner join guifi_pfc_parametres p on p.id = pusc.pid
                      inner join guifi_pfc_configuracioUnSolclic usc on usc.id = pusc.uscid
                      inner join guifi_pfc_firmware f on f.id = usc.fid
                      inner join guifi_model d on d.mid = usc.mid
                      inner join guifi_manufacturer mf on mf.fid = d.fid
                  where
                      pusc.id = %d', $id);
  $parameter = db_fetch_object($sql);
  
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

  _guifi_db_sql('guifi_pfc_parametresConfiguracioUnsolclic',array('id' => $edit['id']),$edit,$log,$to_mail);

  guifi_notify(
  $to_mail,
  t('The guifi_pfc_parametresConfiguracioUnsolclic !parameter has been created / updated by !user.',array('!manufacturer' => $edit['name'], '!user' => $user->name)),
  $log);
}

function guifi_devel_paramusc_delete_confirm($form_state,$id) {
  guifi_log(GUIFILOG_TRACE,'guifi_devel_parameter_delete_confirm()',$id);

  $form['id'] = array('#type' => 'hidden', '#value' => $id);
  $qry= db_fetch_object(db_query("SELECT
                                      p.nom, pusc.uscid as uscid
                                  FROM
                                      guifi_pfc_parametresConfiguracioUnsolclic pusc
                                      inner join guifi_pfc_parametres p on p.id = pusc.pid
                                  WHERE
                                      pusc.id = %d", $id));
  
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
  $log = _guifi_db_delete('guifi_pfc_parametresConfiguracioUnsolclic',array('id' => $form_state['values']['id']),$to_mail,$depth);
  drupal_set_message($log);
  guifi_notify(
  $to_mail,
  t('The USC Configuration parameter %parametre has been DELETED by %user.',array('%parametre' => $form_state['values']['nom'], '%user' => $user->name)),
  $log);
  drupal_goto('guifi/menu/devel/parameter');
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

function crearParametresConfiguracioUSC($uscid, $fid, $notification, $userid) {
  $sql = db_query("SELECT
                        p.id, %d, p.nom, '0'
                     FROM
                      guifi_pfc_parametres p
                     INNER JOIN guifi_pfc_parametresFirmware pf ON pf.pid = p.id  AND pf.fid = %d", $uscid,  $fid);
  
  while ($paramsFirmware = db_fetch_object($sql)) {
    
    
    $params = array(
              'pid' => $paramsFirmware->id,
              'uscid' => $uscid,
              'dinamic' => 1,
              'notification' => $notification,
              'user_created' => $userid,
              'new' => true
    );
    _guifi_db_sql('guifi_pfc_parametresConfiguracioUnsolclic',array('id' => $params['id']),$params,$log,$notification);
    
    
    
    //db_query("INSERT INTO  {guifi_pfc_parametresConfiguracioUnsolclic} (pid, uscid, dinamic, notification, user_created)
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
  $sql = db_query("select id , enabled from guifi_pfc_configuracioUnSolclic where fid = %d", $fid);
  while ($configuracioUSC = db_fetch_object($sql)) {
    $params = array(
              'pid' => $parametre,
              'uscid' => $configuracioUSC->id,
              'dinamic' => 1,
              'notification' => $notification,
              'user_created' => $userid,
              'new' => true
    );
    _guifi_db_sql('guifi_pfc_parametresConfiguracioUnsolclic',array('id' => $params['id']),$params,$log,$notification);
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
                      guifi_pfc_parametresConfiguracioUnsolclic pusc
                          inner join
                      guifi_pfc_configuracioUnSolclic usc ON usc.id = pusc.uscid
                  where
                      pusc.pid = %d and usc.fid = %d', $model, $firmware);
  $usc = db_fetch_object($sql);
  
  return $uscId->id;
}

function unsolclicDefaultTemplate($model, $firmware) {
  $version = "vX.XX-TODO";
  $listsURL = 'https://lists.guifi.net/listinfo/guifi-rdes';
  $sourceURL = 'https://gitorious.org/guifi/drupal-guifi';
  $getStartedURL = 'http://wiki.guifi.net/wiki/Documentaci%C3%B3_de_guifi.net';

  $output  = _outln_comment_get();
  $output .= _outln_comment_get($model);
  $output .= _outln_comment_get($firmware. 'unsolclic version: '.$version);
  $output .= _outln_comment_get();
  $output .= _outln_comment_get(t("This firmware configuration is under construction or not yet started development."));
  $output .= _outln_comment_get(t("If you want to collaborate and contribute with code to make it work,"));
  $output .= _outln_comment_get(t("please subscibre to our development lists at:"));
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
