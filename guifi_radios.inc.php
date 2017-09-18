<?php
/**
 * @file guifi_radios.inc.php
 * Radio edit forms & functions
 */

/* guifi_radio_form(): Main radio form (Common parameters)*/
function guifi_radio_form($edit, $form_weight) {
  global $hotspot;
  global $bridge;
  global $user;


  guifi_log(GUIFILOG_TRACE,'function guifi_radio_form()',$edit);

  $querymid = db_query("
    SELECT mid, model, f.name manufacturer
    FROM guifi_model_specs m, guifi_manufacturer f
    WHERE f.fid = m.fid
    AND supported = 'Yes'
    ORDER BY manufacturer, model ASC");
  while ($model = $querymid->fetchAssoc()) {
     $models_array[$model["mid"]] = $model["manufacturer"] .", " .$model["model"];
  }

  // codi pfc '#title' => t('Device model, firmware & MAC address').' ('.$models_array[$edit['mid']].')',
  $form['radio_settings'] = array(
    '#type' => 'fieldset',

    '#title' => t('Device model, firmware & MAC address').' ('.$edit['variable']['firmware'].')',
    '#weight' => $form_weight++,
    '#collapsible' => TRUE,
    '#tree' => FALSE,
    '#collapsed' => !is_null($edit['id']),
  );

  $form['radio_settings']['variable'] = array('#tree' => TRUE);
  // codi del PFC $form['radio_settings']['variable']['mid'] = array(
  // '#default_value' => $edit['mid'],
  $form['radio_settings']['variable']['model_id'] = array(
    '#type' => 'select',
    '#title' => t("Device model"),
    '#required' => TRUE,
     '#default_value' => $edit['variable']['model_id'],
    '#options' => $models_array,
    '#description' => t('Select the manufacturer and model of your device.'),
    '#ajax' => array(
      'callback' => 'guifi_ajax_select_firmware_by_model',
      'wrapper' => 'select-firmware',
      'method' => 'replace',
      'effect' => 'fade',
     ),
    '#weight' => 1,
  );

  $fid = $edit['variable']['firmware_id'];

  $options = array();
  $firm = guifi_types('firmware', NULL, NULL,$edit['variable']['model_id']);
  if (empty($firm))
    $options['1'] = t('n/a');
  else
    foreach( $firm as $key => $i) {
      $options[$firm[$key]['fid']] = t($firm[$key]['description']);
   }

  $form['radio_settings']['variable']['firmware_id'] = array(
    '#type' => 'select',
    '#title' => t('Firmware'),
    '#parents' => array('variable','firmware_id'),
    '#required' => TRUE,
    '#default_value' => $fid,
    '#prefix' => '<div id="select-firmware">',
    '#suffix' => '</div>',
    '#options' => $options,
    '#description' => t('Select the firmware version used by your device. This is used for automatic configuration.'),
    '#weight' => 2,
  );


  $form['radio_settings']['variable']['firmware'] = array(
    '#type' => 'hidden',
    '#default_value' => $edit['variable']['firmware'],
  );

  $form['radio_settings']['mac'] = array(
    '#type' => 'textfield',
    '#title' => t('Primary MAC address'),
    '#required' => TRUE,
    '#size' => 20,
    '#maxlength' => 20,
    '#default_value' => $edit['mac'],
    '#element_validate' => array('guifi_mac_validate'),
    '#description' => t("Write the primary MAC Address of your device in the 00:11:22:33:aa:bb format. Usually it is the MAC address of the first Ethernet interface (e.g. eth0)."),
    '#weight' => 3
  );

  $collapse = TRUE;

  $msg = (count($edit['radios'])) ?
    format_plural(count($edit['radios']),'1 radio','@count radios') :
    t('No radios');

  if (count($edit['radios']))
    foreach ($edit['radios'] as $value)
    if ($value['unfold'])
      $collapse = FALSE;

  $form['r'] = array(
    '#type' => 'fieldset',
    '#title' => $msg ,
    '#collapsible' => TRUE,
    '#collapsed' => $collapse,
    '#tree' => FALSE,
    '#prefix' => '<img src="/'.
      drupal_get_path('module', 'guifi').
     '/icons/wifi.png"> '.t('Wireless networking section'),
    '#weight' => $form_weight++,
  );

  $form['r']['radios'] = array('#tree' => TRUE);

  $rc = 0;
  $bridge = FALSE;
  $cinterfaces = 0;
  $cipv4 = 0;
  $clinks = 0;
  if (!empty($edit['radios'])) foreach ($edit['radios'] as $key => $radio) {
    $hotspot = FALSE;

//    if ($radio['deleted']) continue;

    $form['r']['radios'][$key] =
      guifi_radio_radio_form($radio,$key,$form_weight);

    $bw = $form_weight - 1000;

    // Going to paint the buttons

    // For AP Mode, clients_accepted
    if (!isset($radio['deleted'])) {
      $prefix = "<table><tr><td>";
      if ($radio['mode'] == 'ap')  {
        // If no wLan interface, allow to create one
        if ((count($radio['interfaces']) < 2) or (user_access('administer guifi networks'))) {
          $form['r']['radios'][$key]['AddwLan'] = array(
            '#type' => 'image_button',
            '#src' => drupal_get_path('module', 'guifi').'/icons/insertwlan.png',
            '#parents' => array('radios',$key,'AddwLan'),
            '#submit' => array('guifi_radio_add_wlan_submit'),
            '#attributes' => array('title' => t('Add a public network range to the wLan for clients')),
            '#prefix' => $prefix,
            '#suffix'  => '&nbsp;',
            '#weight' => $bw++);
        }
          $form['r']['radios'][$key]['AddWDSiface'] = array(
            '#type' => 'image_button',
            '#src' => drupal_get_path('module', 'guifi').'/icons/wdsiface.png',
            '#parents' => array('radios',$key,'AddWDSiface'),
            '#submit' => array('guifi_radio_add_wdsiface_submit'),
            '#attributes' => array('title' => t('Add wds interface')),
            '#prefix'  => '&nbsp;',
            '#suffix'  => '&nbsp;',
            '#weight' => $bw++);
        if (!$hotspot) {

          $form['r']['radios'][$key]['AddHotspot'] = array(
            '#type' => 'image_button',
            '#src' => drupal_get_path('module', 'guifi').'/icons/inserthotspot.png',
            '#attributes' => array('title' => t('Add a Hotspot for guests')),
            '#submit' => array('guifi_radio_add_hotspot_submit'),
            '#prefix'  => '&nbsp;',
            '#suffix'  => '&nbsp;',
            '#weight' => $bw++);
        }
        $prefix = "&nbsp;";
      }

      // Only allow delete and move functions if the radio has been saved
      if ($radio['new']==FALSE)  {
          $form['r']['radios'][$key]['delete'] = array(
            '#type' => 'image_button',
            '#src' => drupal_get_path('module', 'guifi').'/icons/drop.png',
            '#parents' => array('radios',$key,'delete'),
            '#attributes' => array('title' => t('Delete radio')),
            '#submit' => array('guifi_radio_delete_submit'),
            '#prefix'  => $prefix,
            '#suffix'  => '&nbsp;',
            '#weight' => $bw++);
        // TODO: Fix ticket about moving radios

              $form['r']['radios'][$key]['change'] = array(
                '#type' => 'image_button',
                '#src' => drupal_get_path('module', 'guifi').'/icons/move.png',
                '#parents' => array('radios',$key,'move'),
                '#attributes' => array('title' => t('Move radio to another device')),
                '#ahah' => array(
                  'path' => 'guifi/js/move-device/'.$key,
                  'wrapper' => 'move-device-'.$key,
                  'method' => 'replace',
                  'effect' => 'fade',
                ),
                '#prefix'  => '&nbsp;',
                '#suffix'  => '&nbsp;',
                '#weight' => $bw++);
      }

      // if not first, allow to move up
      if ($rc)
        $form['r']['radios'][$key]['up'] = array(
          '#type' => 'image_button',
          '#src' => drupal_get_path('module', 'guifi').'/icons/up.png',
          '#attributes' => array('title' => t('Move radio up')),
          '#submit' => array('guifi_radio_swap_submit'),
          '#parents' => array('radios',$key,'up'),
          '#prefix'  => '&nbsp;',
          '#suffix'  => '&nbsp;',
          '#weight' => $bw++);
      // if not last, allow to move down
      if (($rc+1) < count($edit['radios']))
        $form['r']['radios'][$key]['down'] = array(
          '#type' => 'image_button',
          '#src' => drupal_get_path('module', 'guifi').'/icons/down.png',
          '#attributes' => array('title' => t('Move radio down')),
          '#submit' => array('guifi_radio_swap_submit'),
          '#parents' => array('radios',$key,'down'),
          '#prefix'  => '&nbsp;',
          '#suffix'  => '&nbsp;',
          '#weight' => $bw++);

      $form['r']['radios'][$key]['to_did'] = array(
        '#type' => 'hidden',
        '#value' => $radio['id'],
        '#prefix' => '<div id="move-device-'.$key.'"',
        '#suffix' => '</div></td></tr></table>',
        '#weight' => $bw++
      );
      $rc++;

    } // radio not deleted
  } // foreach radio

    // Add radio?
  if ($rc) {
    $dradios = db_query(
       'SELECT radiodev_max max ' .
       'FROM {guifi_model_specs} ' .
       'WHERE mid = :mid',
       array(':mid' => $edit['variable']['model_id']))->fetchObject();

    if ($rc < $dradios->max)
      $form['r']['addRadio'] = array(
        '#type' => 'image_button',
        '#src'=> drupal_get_path('module', 'guifi').'/icons/addwifi.png',
        '#parents' => array('addRadio'),
        '#attributes' => array('title' => t('Add wireless radio to this device')),
        '#ajax' => array(
          'path' => 'guifi/js/add-radio',
          'wrapper' => 'add-radio',
          'method' => 'replace',
          'effect' => 'fade',
        ),
        '#prefix' => '<div id="add-radio">',
        '#suffix' => '</div>',
      );
  } else
    $form['r']['newRadio'] = guifi_radio_add_radio_form($edit);

  return $form;
}

function guifi_radio_add_radio_form($edit) {

  // Edit radio form or add new radio
  $cr = 0; $tr = 0; $firewall=FALSE;
  $maxradios = db_query('SELECT radiodev_max FROM {guifi_model_specs} WHERE mid = :mid', array(':mid' => $edit[variable][model_id]))->fetchObject();

  if (isset($edit[radios]))
  foreach ($edit[radios] as $k => $radio) {
    $tr++;
    if (!$radio['deleted'])
      $cr++;
    if ($radio['mode'] == 'client')
      $firewall = TRUE;
  } // foreach $radio

  // print "Max radios: ".$maxradios->radiodev_max." Current: $cr Total: $tr Firewall: $firewall Edit details: $edit[edit_details]\n<br />";
  $modes_arr = guifi_types('mode');
  //  print_r($modes_arr);

  if ($cr>0)
	  if (!$firewall)
		  $modes_arr = array_diff_key($modes_arr,array('client' => 0));
	  else
		  $modes_arr = array_intersect_key($modes_arr,array('client' => 0));

  $form['newradio_mode'] = array(
    '#type' => 'select',
    '#parents' => array('newradio_mode'),
    '#required' => FALSE,
    '#default_value' =>  'client',
    '#options' => $modes_arr,
    '#prefix' => '<table  style="width: 100%"><th colspan="0">'.t('New radio (mode)').'</th><tr><td  style="width: 0" align="right">',
    '#suffix' => '</td>',
//    '#weight' => 20
  );
  $form['AddRadio'] = array(
    '#type' => 'button',
    '#parents' => array('AddRadio'),
    '#default_value' => t('Add new radio'),
    '#executes_submit_callback' => TRUE,
    '#submit' => array(guifi_radio_add_radio_submit),
    '#prefix' => '<td style="width: 10em" align="left">',
    '#suffix' => '</td><td style="width: 100%" align="right">&nbsp</td></tr>',
//    '#weight' => 21,
  );
  $form['help_addradio'] = array(
    '#type' => 'item',
    '#description' => t('Usage:<br />Choose <strong>wireless client</strong> mode for a normal station with full access to the network. That\'s the right choice in general.<br />Use the other available options only for the appropiate cases and being sure of what you are doing and what does it means. Note that you might require to be authorized by networks administrators for doing this.<br />Youwill not be able to define you link and get connected to the network until you add at least one radio.'),
    '#prefix' => '<tr><td colspan="3">',
    '#suffix' => '</td></tr></table>',
//    '#weight' => 22,
  );
  return $form;
}

function guifi_radio_firmware_field($fid,$mid) {
//En desús a partir de Drupal 7
/* Consulta anterior al  PFC */
  $model = db_query(
        "SELECT model as name, mid as id " .
        "FROM {guifi_model_specs} " .
        "WHERE mid = :mid",
    array(':mid' => $mid))->fetchObject();

  $options = array();
  $firm = guifi_types('firmware', NULL, NULL,$model->id);

  foreach( $firm as $key => $i) {
    $options[$firm[$key]['fid']] = t($firm[$key]['description']);
  }

  return array(
    '#type' => 'select',
    '#title' => t('Firmware'),
    //'#parents' => array('variable','firmware_id'),
    '#required' => TRUE,
    '#default_value' => $fid,
    '#prefix' => '<div id="select-firmware">',
    '#suffix' => '</div>',
    '#options' => $options,
    '#description' => t('Select the firmware version used by your device. This is used for automatic configuration.'),
    '#weight' => 2,
  );
}

function guifi_radio_channel_field($rid, $channel, $protocol) {
  return array(
    '#type' => 'select',
    '#title' => t("Channel"),
    '#parents' => array('radios',$rid,'channel'),
    '#default_value' =>  $channel,
    '#options' => guifi_types('channel', NULL, NULL,$protocol),
    '#description' => t('Select the channel where this radio will operate.'),
    '#prefix' => '<div id="select-channel-'.$rid.'">',
    '#suffix' => '</div>',
     );
}

function guifi_radio_channel_bwith_field($rid, $channel) {
  return array(
    '#type' => 'select',
    '#title' => t("Channel Bandwith"),
    '#parents' => array('radios',$rid,'chbandwith'),
    '#default_value' =>  $channel,
    '#options' => guifi_types('chbandwith', NULL, NULL,NULL),
    '#description' => t('Select the channel bandwith where this radio will operate.'),
     );
}

function guifi_radio_radio_form($radio, $key, &$form_weight = -200) {
    guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_radio_radio_form(key=%d)',$key),$radio);

    $f['storage'] = guifi_form_hidden_var(
      $radio,
      array('radiodev_counter'),
      array('radios',$key)
    );

    $f = array(
      '#type' => 'fieldset',
      '#title' => t('Radio #').$key.' - '.$radio['mode'].' - '.$radio['ssid'].
        ' - '.count($radio['interfaces']).' '.t('interface(s)'),
      '#collapsible' => TRUE,
      '#collapsed' => !(isset($radio['unfold'])),
      '#tree'=> TRUE,
      '#weight' => $form_weight++,
    );
    if ($radio['deleted']) {
      $f['deletedMsg'] = array(
        '#type' => 'item',
        '#value' => guifi_device_item_delete_msg(
            "This radio and has been deleted, " .
            "deletion will cascade to all properties, including interfaces, " .
            "links and ip addresses."),
        '#weight' => $form_weight++);
      $f['deleted'] = array(
        '#type' => 'hidden',
        '#value' => TRUE);
    }
    if ($radio['new']) {
      $f['new'] = array(
        '#type' => 'hidden',
        '#parents' => array('radios',$key,'new'),
        '#value' => TRUE);
    }
    $f['mode'] = array(
        '#type' => 'hidden',
        '#parents' => array('radios',$key,'mode'),
        '#value' => $radio['mode']);

    $f['s'] = array(
      '#type' => 'fieldset',
      '#title' => t('Radio main settings (SSID, MAC, Channel...)'),
      '#collapsible' => TRUE,
      '#collapsed' => !(isset($radio['unfold_main'])),
      '#tree' => FALSE,
//      '#attributes' => array('class' => 'fieldset-device-main'),
//      '#weight' => $fw2++,
    );

    // DUMMY Radio MAC
    if (empty($radio['mac']))
      $radio['mac'] = "00:00:00:00:00:00";

    $f['s']['mac'] = array(
      '#type' => 'textfield',
//      '#parents' => array('radios',$key,'mac'),
      '#title' => t('MAC'),
      '#required' => TRUE,
      '#parents' => array('radios',$key,'mac'),
      '#size' => 17,
      '#maxlength' => 17,
      '#default_value' => $radio["mac"],
      '#element_validate' => array('guifi_mac_validate'),
      '#description' => t("Wireless MAC Address.<br />Some configurations won't work if is blank"),
    );

    switch ($radio['mode']) {
      case 'ap':
      case 'mesh':
        $f['s']['ssid'] = array(
          '#type' => 'textfield',
          '#title' => t('SSID'),
          '#parents' => array('radios',$key,'ssid'),
          '#required' => TRUE,
          // ssid: gufi.net-XXXXXXXX suffix is always guifi.net-,10 characters, 32 maximum.
          '#size' => 22,
          '#maxlength' => 22,
          '#default_value' => $radio["ssid"],
          '#description' => t("SSID to identify this radio signal."),
        );
        $f['s']['protocol'] = array(
          '#type' => 'select',
          '#title' => t("Protocol"),
          '#parents' => array('radios',$key,'protocol'),
          '#default_value' =>  $radio["protocol"],
          '#options' => guifi_types('protocol'),
          '#description' => t('Select the protocol used by this radio.'),
          '#ajax' => array(
            'callback' => 'guifi_ajax_select_channel',
            'wrapper' => 'select-channel-'.$key,
            'method' => 'replace',
            'effect' => 'fade',
          ),
        );

        $f['s']['channel'] = guifi_radio_channel_field($key, $radio["channel"], $radio['protocol']);
        $f['s']['chbandwith'] = guifi_radio_channel_bwith_field($key, $radio["chbandwith"]);

        if ($radio['mode'] == 'ap') {
          $f['ap'] = array(
            '#type' => 'fieldset',
            '#title' => t('Connection acceptance policy'),
            '#description' => t('Please specify if this radio do accept simple client connections and if a funding is required to contribute for the coverage infrastructure'),
            '#collapsible' => TRUE,
            '#collapsed' => ($radio["clients_accepted"]=='Yes')?false:true,
            '#tree'=> TRUE,
            '#attributes' => array('class' => array('fieldset-radio')),
          );
          $f['ap']['clients_accepted'] = array(
            '#type' => 'select',
            '#title' => t("Clients accepted?"),
            '#parents' => array('radios',$key,'clients_accepted'),
            '#default_value' =>  $radio["clients_accepted"],
            '#options' => drupal_map_assoc(array( 0 => 'Yes',1 => 'No')),
            '#description' => t('Do this radio accept connections from clients?'),
          );
          $f['ap']['fund_required'] = array(
            '#type' => 'select',
            '#title' => t("Policy"),
            '#parents' => array('radios',$key,'fund_required'),
            '#default_value' =>  $radio["fund_required"],
            '#options' => guifi_types('contribution'),
            '#description' => t('Funding required?'),
          );

            $fund_amount = $radio["fund_amount"] ?: 0.00; //Setting default value on fund

            $f['ap']['fund_amount'] = array(
            '#type' => 'textfield',
            '#size'  => 12,
            '#maxlength' => 15,
            '#required' => false,
            '#title' => t("Amount"),
            '#parents' => array('radios',$key,'fund_amount'),
            '#default_value' =>  $fund_amount,
            '#description' => t('Funding if required'),
          );
          $f['ap']['fund_currency'] = array(
          '#type' => 'select',
          '#title' => t("Currency"),
          '#parents' => array('radios',$key,'fund_currency'),
          '#default_value' =>  $radio["fund_currency"],
          '#options' => drupal_map_assoc(array( '€' => 'Euros','$' => 'US Dollar')),
          );
      } else {
          $f['s']['clients_accepted'] = array(
            '#type' => 'hidden',
            '#parents' => array('radios',$key,'clients_accepted'),
            '#value' =>  $radio["clients_accepted"],
          );
          $f['s']['fund_required'] = array(
            '#type' => 'hidden',
            '#parents' => array('radios',$key,'fund_required'),
            '#value' =>  $radio["fund_required"],
          );
          $f['s']['fund_amount'] = array(
            '#type' => 'hidden',
            '#parents' => array('radios',$key,'fund_amount'),
            '#value' =>  $radio["fund_amount"],
          );
          $f['s']['fund_currency'] = array(
            '#type' => 'hidden',
            '#parents' => array('radios',$key,'fund_currency'),
            '#value' =>  $radio["fund_currency"],
          );
      }
      break;
      case 'client':
        $inherit_msg = t('Will take it from the connected AP.');
        $f['s']['ssid'] = array(
          '#type' => 'hidden',
          '#parents' => array('radios',$key,'ssid'),
          '#title' => t('SSID'),
          '#default_value' => $radio["ssid"],
          '#description' => $inherit_msg,
        );
        $f['s']['protocol'] = array(
          '#type' => 'hidden',
          '#title' => t("Protocol"),
          '#parents' => array('radios',$key,'protocol'),
          '#value' =>  $radio["protocol"],
          '#description' => $inherit_msg,
          );
        $f['s']['channel'] = array(
          '#type' => 'hidden',
          '#title' => t("Channel"),
          '#parents' => array('radios',$key,'channel'),
          '#default_value' =>  $radio["channel"],
          '#options' => guifi_types('channel', NULL, NULL,$radio['protocol']),
          '#description' => $inherit_msg,
        );
        $f['s']['clients_accepted'] = array(
        '#type' => 'hidden',
        '#parents' => array('radios',$key,'clients_accepted'),
        '#value' =>  $radio["clients_accepted"],
        );
      break;
    }

    // Antenna settings group
    $f['antenna'] = array(
      '#type' => 'fieldset',
      '#title' => t('Antenna settings'),
      '#collapsible' => TRUE,
      '#collapsed' => !(isset($radio['unfold_antenna'])),
      '#tree' => FALSE,
      '#attributes' => array('class' => array('fieldset-radio')),
//      '#weight' => $fw2++,
    );
    $fw2 = 0;
    $f['antenna']['antenna_angle'] = array(
      '#type' => 'select',
      '#title' => t("Type (angle)"),
      '#parents' => array('radios',$key,'antenna_angle'),
      '#default_value' =>  $radio["antenna_angle"],
      '#options' => guifi_types('antenna'),
      '#description' => t('Beam width angle'),
//      '#weight' => $fw2++,
    );
    $f['antenna']['antenna_gain'] = array(
      '#type' => 'select',
      '#title' => t("Gain"),
      '#parents' => array('radios',$key,'antenna_gain'),
      '#default_value' =>  $radio["antenna_gain"],
      '#options' => guifi_types('antenna_gain'),
      '#description' => t('Gain (Db)'),
//      '#weight' => $fw2++,
    );
    $f['antenna']['antenna_azimuth'] = array(
      '#type' => 'textfield',
      '#title' => t('Degrees (º)'),
      '#parents' => array('radios',$key,'antenna_azimuth'),
      '#size' => 3,
      '#maxlength' => 3,
      '#default_value' => $radio["antenna_azimuth"],
      '#description' => t('Azimuth<br>(0-360º)'),
//      '#weight' => $fw2++,
    );
    $f['antenna']['antenna_mode'] = array(
      '#type' => 'select',
      '#title' => t("Connector"),
      '#parents' => array('radios',$key,'antenna_mode'),
  //    '#required' => TRUE,
      '#default_value' =>  $radio["antenna_mode"],
      '#options' => array(
        ''=> 'Don\'t change',
        'Main' => 'Main/Right/Internal',
        'Aux' => 'Aux/Left/External'),
      '#description' => t('Examples:<br />MiniPci: Main/Aux<br />Linksys: Right/Left<br />Nanostation: Internal/External'),
//      '#weight' => $fw2++,
    );

    foreach ($radio['interfaces'] as $iid => $interface)
      $f['interfaces'][$iid] =
        guifi_interfaces_form($interface,array('radios',$key,'interfaces',$iid));

  return $f;
}

/* guifi_radio_validate()): Validate radio device, called as a hook while validating the form */
function guifi_radio_validate($form,$edit) {
  guifi_log(GUIFILOG_TRACE,"function _guifi_radio_validate()");

  if (($edit['variable']['firmware'] != 'n/a') and
    ($edit['variable']['firmware'] != NULL)) {
    $radiof = db_query("
      SELECT model
      FROM {guifi_model_specs}
      WHERE mid = :mid",
      array(':mid' => $edit['variable']['model_id']))-fetchObject();
    if (!guifi_type_relation(
      'firmware',
      $edit['variable']['firmware'],
      $radiof->model)) {
      form_set_error('variable][firmware',
        t('This firmware with this radio model is NOT supported.'));
    }
  }
}

/* guifi_radios_validate()): Validate radio item, called as a hook while validating the form
 *                           once per each radio
 */
function guifi_radios_validate($rid,$radio,$edit) {
  guifi_log(GUIFILOG_TRACE,"function _guifi_radio_validate()");

  if (($radio['fund_amount']>0) and ($radio['fund_required']!='yes'))
    form_set_error('radios]['.$rid.'][fund_amount',
      t("Can't set an amount if funding is not required on radio :rid - :ssid",
        array(':rid'=>$rid,':ssid'=>$radio['ssid'])));
  if (empty($radio['fund_amount']) and ($radio['fund_required']=='yes'))
    form_set_error('radios]['.$rid.'][fund_amount',
      t("Funding amount has to be specified when funding is required on radio :rid - :ssid",
        array(':rid'=>$rid,':ssid'=>$radio['ssid'])));
}

/* guifi_radio_swap_submit(): Action */
function guifi_radio_swap_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_radio_swap_submit()'),$form_state['clicked_button']);
  $old = $form_state['clicked_button']['#parents'][1];
  switch ($form_state['clicked_button']['#parents'][2]) {
    case "up":
      $new = $old-1; break;
    case "down":
      $new = $old+1; break;
  }
  $form_state['swapRadios']=$old.','.$new;
  $form_state['action']='guifi_radio_swap';
  $form_state['rebuild'] = TRUE;
  return;
}

/* guifi_radio_swap(): Action */
function guifi_radio_swap($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_radio_swap()'),$form_state['clicked_button']);
  list($old, $new) = explode(',',$form_state['swapRadios']);
  $old_radio = $form_state['values']['radios'][$old];
  $new_radio = $form_state['values']['radios'][$new];
  $form_state['values']['radios'][$new] = $old_radio;
  $form_state['values']['radios'][$old] = $new_radio;
  ksort($form_state['values']['radios']);
  drupal_set_message(t('Radio #%old moved to #%new.',
    array('%old' => $old,'%new' => $new)));
  return TRUE;
}

function _guifi_radio_add_wlan($radio, $nid, $edit = NULL) {
  $interface = array();
  $interface['new'] = TRUE;
  $interface['unfold'] = TRUE;
  $ips_allocated = guifi_ipcalc_get_ips('0.0.0.0','0.0.0.0', $edit, 1);
  $net = guifi_ipcalc_get_subnet_by_nid($nid,'255.255.255.224', 'public', $ips_allocated, 'Yes', TRUE);

  if (!$net) {
    drupal_set_message(t('Unable to allocate new range, no networks available'),'warning');
    return FALSE;
  }
  guifi_log(GUIFILOG_FULL, "IPs allocated: " . count($ips_allocated) . " Obtained new net: " . $net . "/27");
  $interface['ipv4'][$radio] = array();
  $interface['ipv4'][$radio]['new'] = TRUE;
  $interface['ipv4'][$radio]['unfold'] = TRUE;
  $interface['ipv4'][$radio]['ipv4_type'] = 1;
  $interface['ipv4'][$radio]['ipv4'] = long2ip(ip2long($net) + 1);
  $interface['ipv4'][$radio]['netmask'] = '255.255.255.224';
  $interface['ipv4'][$radio]['links'] = array();
  $interface['interface_type'] = 'wLan';

  return $interface;
}

function _guifi_radio_add_wdsiface($radio, $nid, $edit = NULL) {
  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_radio_add_wdsiface(%d)',$radio));

  $interface = array();
  $interface['new'] = TRUE;
  $interface['unfold'] = TRUE;
  $interface['interface_type'] = 'wds/p2p';

  return $interface;
}

/* _guifi_add_wlan_submit(): Action */
function guifi_radio_add_wlan_submit($form, &$form_state) {
  $radio = $form_state['clicked_button']['#parents'][1];
  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_radio_add_wlan(%d)',$radio));

  $interface = _guifi_radio_add_wlan($radio, $form_state['values']['nid'], $form_state['values']);

  $form_state['values']['radios'][$radio]['unfold'] = TRUE;
  $form_state['values']['radios'][$radio]['interfaces'][]=$interface;
  $form_state['rebuild'] = TRUE;
  drupal_set_message(t('wLan with %net/%mask added at radio#%radio',
    array('%net' => $net,'%mask' => '255.255.255.224','%radio' => $radio)));

  return TRUE;
}

/* _guifi_add_wds_submit(): Action */
function guifi_radio_add_wdsiface_submit($form, &$form_state) {
  $radio = $form_state['clicked_button']['#parents'][1];
  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_radio_add_wds(%d)',$radio),$form['ipv4']);

  $interface = _guifi_radio_add_wdsiface($radio, $form_state['values']['nid'], $form_state['values']);

  $form_state['values']['radios'][$radio]['unfold'] = TRUE;
  $form_state['values']['radios'][$radio]['interfaces'][]=$interface;
  $form_state['rebuild'] = TRUE;

  return TRUE;
}

function guifi_radio_add_hotspot_submit($form, &$form_state) {
  $radio = $form_state['clicked_button']['#parents'][1];
  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_radio_add_hotspot_submit(%d)',$radio));

    // filling variables
  $interface=array();
  $interface['new']=TRUE;
  $interface['interface_type']='HotSpot';
  $interface['unfold']=TRUE;
  $form_state['values']['radios'][$radio]['unfold']=TRUE;
  $form_state['values']['radios'][$radio]['interfaces'][] = $interface;
  $form_state['rebuild'] = TRUE;
  drupal_set_message(t('Hotspot added at radio#%radio',
    array('%radio' => $radio)));

  return;
}

function _guifi_radio_get_default() {
  $radio = array();
  $radio['new'] = TRUE;
  $radio['model_id'] = 16;
  $radio['protocol'] = '802.11b';
  $radio['channel'] = 0;
  $radio['antenna_gain'] = 14;
  $radio['antenna_azimuth'] = 0;
  $radio['antenna_mode'] = 'Main';
  $radio['interfaces'] = array();
  $radio['interfaces'][0] = array();
  $radio['interfaces'][0]['new'] = TRUE;
  $radio['interfaces'][0]['id'] = 0;
  return $radio;
}

function _guifi_radio_prepare_add_radio($edit) {
  // next id
  $rc = 0; // Radio radiodev_counter next pointer
  $tc = 0; // Total active radios

  // fills $rc & $tc proper values
  if (isset($edit['radios'])) {
    foreach ($edit['radios'] as $k => $r) {
      if ($k+1 > $rc) {
        $rc = $k+1;
        if (!$edit['radios'][$k]['delete']) {
          $tc++;
        }
      }
    }
  }

  $node = node_load($edit['nid']);
  $zone = node_load($node->zone_id);
  if (($zone->nick == '') or ($zone->nick == NULL)) {
    $zone->nick = guifi_abbreviate($zone->nick);
  }

  if (strlen($zone->nick.$edit['nick']) > 10) {
      $nick = guifi_abbreviate($edit['nick']);
  } else {
      $nick = $edit['nick'];
  }
  $ssid = $zone->nick . $nick;

  $radio = _guifi_radio_get_default();
  $radio['id'] = $edit['id'];
  $radio['nid'] = $edit['nid'];
  $radio['mode'] = $edit['newradio_mode'];

  switch ($radio['mode']) {
    case 'ap':
      $radio['antenna_angle'] = 120;
      $radio['clients_accepted'] = "Yes";
      $radio['ssid'] = $ssid.'AP'.$rc;
      $radio['mac'] = '';
      break;
    case 'client':
    case 'client-routed':
      $radio['antenna_angle'] = 30;
      $radio['clients_accepted'] = "No";
      $radio['ssid'] = $ssid . 'CPE' . $rc;
      $radio['interfaces'][0]['new'] = TRUE;
      $radio['interfaces'][0]['interface_type'] = 'Wan';
      $radio['mac'] = '';
	  break;
    case 'mesh':
      $radio['antenna_angle'] = 0;
      $radio['clients_accepted'] = "No";
      $radio['ssid'] = $ssid.t('MESH');
      // first radio, force wlan/Lan bridge and get an IP
      if ($tc == 0) {
        $radio['interfaces'][1] = array();
        $radio['interfaces'][1]['new'] = TRUE;
        $radio['interfaces'][1]['interface_type'] = 'wLan/Lan';
        $ips_allocated = guifi_ipcalc_get_ips('0.0.0.0','0.0.0.0',$edit,1);
        // $net = guifi_ipcalc_get_meship($edit['nid'],$ips_allocated);
        $net = guifi_ipcalc_get_subnet_by_nid($edit['nid'],'255.255.255.224','mesh',$ips_allocated,'Yes', TRUE);
        guifi_log(GUIFILOG_BASIC,"IPS allocated: ".count($ips_allocated)." got net: ".$net.'/27');
        $radio['interfaces'][1]['ipv4'][$rc] = array();
        $radio['interfaces'][1]['ipv4'][$rc]['new'] = TRUE;
        $radio['interfaces'][1]['ipv4'][$rc]['ipv4_type'] = 1;
        //$radio['interfaces'][1]['ipv4'][$rc]['ipv4'] = $net;
        $radio['interfaces'][1]['ipv4'][$rc]['ipv4'] = long2ip(ip2long($net)+1);
        guifi_log(GUIFILOG_TRACE,"Assigned IP: " . $radio['interfaces'][1]['ipv4'][$rc]['ipv4']);
        $radio['interfaces'][1]['ipv4'][$rc]['netmask'] = '255.255.255.224';
      }
        $radio['mac'] = '';
      break;
  }

  $radio['rc'] = $rc;

  return $radio;
}

/* Add  a radio to the device */
function guifi_radio_add_radio_submit(&$form, &$form_state) {
  guifi_log(GUIFILOG_TRACE, "function guifi_radio_add_radio_submit()",$form_state['values']);

  // wrong form navigation, can't do anything
  if ($form_state['values']['newradio_mode'] == NULL) {
    return TRUE;
  }

  $edit = $form_state['values'];

  $radio = _guifi_radio_prepare_add_radio($edit);

  $radio['unfold'] = TRUE;
  $radio['unfold_main'] = TRUE;
  $radio['unfold_antenna'] = TRUE;

  $form_state['rebuild'] = TRUE;
  $form_state['values']['radios'][] = $radio;

  drupal_set_message(t('Radio %ssid added in mode %mode.',
     array('%ssid' => $radio['ssid'],
           '%mode' => $radio['mode'])));

  return;
}

function guifi_radio_delete_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,"function guifi_radio_delete_submit()",$form_state['clicked_button]']);
  $radio_id = $form_state['clicked_button']['#parents'][1];
  $form_state['values']['radios'][$radio_id]['deleted'] = TRUE;
  $form_state['values']['radios'][$radio_id]['unfold'] = TRUE;
  drupal_set_message(t('Radio#%num has been deleted.',
    array('%num' => $radio_id)));
  $form_state['rebuild'] = TRUE;
  return;
}

/* guifi_radio_link2ap(): Link client to an AP */
function guifi_radio_add_link2ap_submit(&$form,&$form_state) {
  $radio_id    =$form_state['clicked_button']['#parents'][1];
  $interface_id=$form_state['clicked_button']['#parents'][2];
  guifi_log(GUIFILOG_TRACE,
    sprintf("function guifi_radio_add_link2ap_submit(Radio: %d, Interface: %d)",
      $radio_id, $interface_id),
    $form_state['clicked_button']['#parents']);

  $form_state['rebuild'] = TRUE;
  $form_state['action'] = 'guifi_radio_add_link2ap_form';

  // initialize the filters
  $form_state['filters'] = array(
    'dmin'   => 0,
    'dmax'   => 10,
    'search' => NULL,
    'type'   => 'ap/client',
    'mode'   => $form_state['values']['radios'][$radio_id]['mode'],
    'from_node' => $form_state['values']['nid'],
    'from_device' => $form_state['values']['id'],
    'from_radio' => $radio_id,
    'azimuth' => "0,360",
  );

  $form_state['link2apInterface'] =
    &$form_state['values']['radios'][$radio_id]['interfaces'][$interface_id];

  return;
}

function guifi_radio_add_link2ap_form(&$form,&$form_state) {
  guifi_log(GUIFILOG_TRACE,sprintf("function guifi_radio_add_link2ap_form(Radio: %d)",
    $form_state['filters']['from_radio']),
//    $form_state['link2apInterface']);
    $form_state['filters']);

  // store all the form_stat values
  guifi_form_hidden($form,$form_state['values']);
  $form['link2apInterface'] = array(
    '#type' => 'hidden',
    '#value' => $form_state['link2apInterface']['id']
  );

  // Initialize filters
  if (empty($form_state['values']['filters']))
    $form_state['values']['filters'] = $form_state['filters'];

  drupal_set_title(t(
    'Choose an AP from the list to link with @hostname',
    array(
      '@hostname'=> $form_state['values']['nick'])
  ));

  // Filter form
  $form['filters_region'] = guifi_devices_select_filter($form_state,'guifi_radio_add_link2ap_confirm_submit');

  $form['list-devices'] = guifi_devices_select($form_state['values']['filters'],
     'guifi_radio_add_link2ap_confirm_submit');

  return FALSE;
}

function _guifi_radio_add_link2ap($nid, $device_id, $radiodev_counter, $ipv4 = NULL, $link_id = 0, $edit = array()) {
  // get list of the current used ips
  $ips_allocated = guifi_ipcalc_get_ips('0.0.0.0', '0.0.0.0', $edit, 1);

  $queryAP = db_query(
    'SELECT i.id, i.radiodev_counter, i.mac, a.ipv4, a.netmask, a.id aid ' .
    'FROM {guifi_interfaces} i, {guifi_ipv4} a ' .
    'WHERE i.device_id = :did ' .
    '  AND i.interface_type in (\'wLan/Lan\',\'wLan\') ' .
    '  AND i.radiodev_counter = :rc ' .
    '  AND a.interface_id=i.id',
    array(':did' => $device_id, ':rc' => $radiodev_counter));

  $link = array();

  while ($ipAP = $queryAP->fetchAssoc()) {
    $item = _ipcalc($ipAP['ipv4'], $ipAP['netmask']);

    if( $ipv4 ) {
      $link['ipv4'] = NULL;
      if( !isset($ips_allocated[ip2long($ipv4)])) {
        $ip_dec = ip2long($ipv4);
        $begin_dec = ip2long($item['netid']);
        $end_dec = ip2long($item['broadcast']);
        if( $ip_dec > $begin_dec && $ip_dec < $end_dec ) {
          $link['ipv4'] = $ipv4;
          break;
        }
      }
    } else {
      $link['ipv4'] = guifi_ipcalc_find_ip($item['netid'], $ipAP['netmask'], $ips_allocated);
    }

    if ($link['ipv4'] != NULL) {
      break;
    }

    drupal_set_message(t('Network was full, looking for more networks available...'));
  }

  // if network was full, delete link
  if ($link['ipv4'] == NULL) {
    return -1;
    $form_state['action'] = 'guifi_radio_add_link2ap_form';
    drupal_set_message(
      t('The Link was not created: ' .
        'The selected node have already all possible connections allocated. ' .
        'Now you can:' .
        '<ul><li>select another Access Point</li>' .
        '<li><b>contribute</b> to create a new Access Point ' .
        'to increase the possible network connections..<li>'.
        '<li>...or check the status and usage of the currently defined connections ' .
        'at this device to find an unused but defined connection ' .
        'to be reused.</li></ul>' .
        'Note that Open Networks are expanding thanks to ' .
        'the <b>contributions of their participants</b>.'),'error');
    return;
  }

  drupal_set_message(t('Got IP address %net/%mask',
    array(
      '%net' => $link['ipv4'],
      '%mask' => $ipAP['netmask']))
  );

  // local ipv4 information
  $lipv4['new'] = TRUE;
  $lipv4['ipv4'] = $link['ipv4'];
  $lipv4['ipv4_type'] = 1;
  $lipv4['netmask'] = $ipAP['netmask'];

  // link
  $lipv4['links'][$link_id]['id'] = $link_id;
  $lipv4['links'][$link_id]['new'] = TRUE;
  $lipv4['links'][$link_id]['unfold'] = TRUE;
  $lipv4['links'][$link_id]['interface_id'] = $ipAP['id'];
  $lipv4['links'][$link_id]['device_id'] = $device_id;
  $lipv4['links'][$link_id]['nid'] = $nid;
  $lipv4['links'][$link_id]['routing'] = 'Gateway';
  $lipv4['links'][$link_id]['link_type'] = 'ap/client';

  // remote interface
  $lipv4['links'][$link_id]['interface']['id'] = $ipAP['id'];
  $lipv4['links'][$link_id]['interface']['radiodev_counter'] = $ipAP['radiodev_counter'];

  // remote ipv4
  $lipv4['links'][$link_id]['interface']['ipv4']['id'] = $ipAP['aid'];
  $lipv4['links'][$link_id]['interface']['ipv4']['ipv4'] = $ipAP['ipv4'];
  $lipv4['links'][$link_id]['interface']['ipv4']['netmask'] = $ipAP['netmask'];

  return $lipv4;
}

function guifi_radio_add_link2ap_confirm_submit(&$form,&$form_state) {
  guifi_log(GUIFILOG_TRACE,
    sprintf("function guifi_radio_add_link2ap_confirm_submit(Radio: %d)",
      $form_state['values']['filters']['from_radio']),
  //    $form_state['link2apInterface']);
    $form_state['values']['filters']);

  $form_state['rebuild'] = TRUE;

  $interface_id = $form_state['values']['link2apInterface'];
  $local_ipv4 = &$form_state['values']['radios']
      [$form_state['values']['filters']['from_radio']]
      ['interfaces'][$interface_id]['ipv4'];

  list(
    $nid,
    $device_id,
    $radiodev_counter) =
        explode(',',$form_state['values']['linked']);

  // get list of the current used ips
  $ips_allocated = guifi_ipcalc_get_ips('0.0.0.0','0.0.0.0',$form_state['values'],1);

  $qAP = db_query(
    'SELECT i.id, i.radiodev_counter, i.mac, a.ipv4, a.netmask, a.id aid ' .
    'FROM {guifi_interfaces} i, {guifi_ipv4} a ' .
    'WHERE i.device_id = :did ' .
    '  AND i.interface_type in (\'wLan/Lan\',\'wLan\') ' .
    '  AND i.radiodev_counter = :rc ' .
    '  AND a.interface_id = i.id',
    array(':did' => $device_id, ':rc' => $radiodev_counter));

  $link = array();

  while ($ipAP = $qAP->fetchAssoc()) {
    $item = _ipcalc($ipAP['ipv4'],$ipAP['netmask']);
    $link['ipv4'] = guifi_ipcalc_find_ip($item['netid'],$ipAP['netmask'],$ips_allocated);
    if ($link['ipv4'] != NULL)
      break;

    drupal_set_message(
      t('Network was full, looking for more networks available...'));
  }

  // if network was full, delete link
  if ($link['ipv4'] == NULL) {
    $form_state['action'] = 'guifi_radio_add_link2ap_form';
    drupal_set_message(
      t('The Link was not created: ' .
        'The selected node have already all possible connections allocated. ' .
        'Now you can:' .
        '<ul><li>select another Access Point</li>' .
        '<li><b>contribute</b> to create a new Access Point ' .
        'to increase the possible network connections..<li>'.
        '<li>...or check the status and usage of the currently defined connections ' .
        'at this device to find an unused but defined connection ' .
        'to be reused.</li></ul>' .
        'Note that Open Networks are expanding thanks to ' .
        'the <b>contributions of their participants</b>.'),'error');
    return;
  }

  drupal_set_message(t('Got IP address %net/%mask',
    array(
      '%net' => $link['ipv4'],
      '%mask' => $ipAP['netmask']))
  );

  // local ipv4 information
  $lipv4['new'] = TRUE;
  $lipv4['ipv4'] = $link['ipv4'];
  $lipv4['ipv4_type'] = 1;
  $lipv4['netmask'] = $ipAP['netmask'];

  // link
  $lipv4['links'][0]['new'] = TRUE;
  $lipv4['links'][0]['unfold'] = TRUE;
  $lipv4['links'][0]['interface_id'] = $ipAP['id'];
  $lipv4['links'][0]['device_id'] = $device_id;
  $lipv4['links'][0]['nid'] = $nid;
  $lipv4['links'][0]['routing'] = 'Gateway';
  $lipv4['links'][0]['link_type'] = 'ap/client';

  // remote interface
  $lipv4['links'][0]['interface']['id'] =
    $ipAP['id'];
  $lipv4['links'][0]['interface']['radiodev_counter'] =
    $ipAP['radiodev_counter'];

  // remote ipv4
  $lipv4['links'][0]['interface']['ipv4']['id'] =
    $ipAP['aid'];
  $lipv4['links'][0]['interface']['ipv4']['ipv4'] =
    $ipAP['ipv4'];
  $lipv4['links'][0]['interface']['ipv4']['netmask'] =
    $ipAP['netmask'];

  // unfold return
  $form_state['values']['radios']
    [$form_state['values']['filters']['from_radio']]['unfold'] = TRUE;
  $lipv4['unfold'] = TRUE;

  $local_ipv4[] = $lipv4;

  // guifi_log(GUIFILOG_BASIC,'WDS added',$form_state['values']);
  unset($form_state['action']);

  return;
}

function guifi_radio_add_wds_confirm(&$form,&$form_state) {

  $radio_id = $form_state['values']['filters']['from_radio'];
  $interface_id = key($form_state['values']['newInterface']);

  guifi_log(GUIFILOG_TRACE,
    sprintf("function guifi_radio_add_wds_confirm (radio: %d, interface: %d)",
    $radio_id,$interface_id),$form_state['values']);


  foreach ( $form_state['values']['newInterface'][$interface_id]['ipv4'] as $newInterface) {
    $newInterface['links'][0]['unfold'] = TRUE;
    $form_state['values']['radios'][$radio_id]['interfaces'][$interface_id]['ipv4'][] = $newInterface;
    guifi_log(GUIFILOG_TRACE,"new Interface added: ",$newInterface);
  }

  $form_state['values']['radios'][$radio_id]['unfold'] = TRUE;
  $form_state['values']['radios'][$radio_id]['interfaces'][$interface_id]['unfold'] = TRUE;

  guifi_log(GUIFILOG_TRACE,
    sprintf("function guifi_radio_add_wds_confirm POST (radio: %d, interface: %d)",
    $radio_id,$interface_id),$form_state['values']);

  return TRUE;
}


function guifi_radio_add_wds_confirm_submit(&$form,&$form_state) {
  $radio_id = $form_state['values']['filters']['from_radio'];
  $interface_id = key($form_state['values']['newInterface']);

  guifi_log(GUIFILOG_TRACE,
    sprintf(
      "function guifi_radio_add_wds_confirm_submit (radio: %d, interface: %d)",
      $radio_id,$interface_id
    ),
    $form_state['values']);

  $newLink = &$form_state['values']['newInterface'][$interface_id]['ipv4'][0]['links'][0];

  list(
    $newLink['nid'],
    $newLink['device_id'],
    $newLink['interface']['radiodev_counter']) =
        explode(',',$form_state['values']['linked']);

  // getting remote interface
  $remote_interface =
    db_query(
        "SELECT id " .
        "FROM {guifi_interfaces} " .
        "WHERE device_id = :did " .
        "   AND (interface_type = 'wds/p2p' OR interface_class = 'wds/p2p') " .
        "   AND radiodev_counter = :rc",
        array(':did' => $newLink['device_id'], ':rc' => $newLink['interface']['radiodev_counter']))->fetchAssoc();

  if ( $remote_interface['id'] == NULL ) {
    drupal_set_message(
      t('Error! The remote radio has no WDS/p2p interface. You must create it before performing the link.'),'error');
    return -1;
  }

  $newLink['interface']['id'] = $remote_interface['id'];
  $newLink['interface']['device_id'] = $newLink['device_id'];
  $newLink['interface']['ipv4']['interface_id'] = $remote_interface['id'];

  guifi_log(GUIFILOG_FULL,"newlk: ", $newLink);

  array_merge(
    $form_state['values']['radios'][$radio_id]['interfaces'],
    $form_state['values']['newInterface']
  );

  $form_state['values']['newInterface']['unfold'] = TRUE;

  guifi_log(GUIFILOG_FULL,"finished link: ", $form_state['values']['radios'][$radio_id]['interfaces']);

  $form_state['rebuild'] = TRUE;
  $form_state['newinterface'] = $form_state['values']['newInterface'];
  $form_state['action'] = 'guifi_radio_add_wds_confirm';

  return;
}

/* _guifi_add_wds(): Add WDS/p2p link */
function guifi_radio_add_wds_form(&$form,&$form_state) {
  guifi_log(GUIFILOG_TRACE,"function guifi_radio_add_wds_form",$form_state['newInterface']);

  $form_weight = 0;
  $form_state['values']['newInterface']=$form_state['newInterface'];

  // store all the form_stat values
  guifi_form_hidden($form,$form_state['values'],$form_weight);

  // Initialize filters
  if (empty($form_state['values']['filters']))
    $form_state['values']['filters'] = $form_state['filters'];

  drupal_set_title(t(
    'Choose an AP from the list to link with @ssid',
    array(
      '@ssid'=> $form_state['values']['radios']
        [$form_state['filters']['from_radio']]['ssid'])));

  // Filter form
  $form['filters_region'] = guifi_devices_select_filter($form_state, 'guifi_radio_add_wds_confirm_submit');

  $form['list-devices'] = guifi_devices_select($form_state['values']['filters'], 'guifi_radio_add_wds_confirm_submit');

  return FALSE;
}

function _guifi_radio_add_wds_get_new_interface($nid, $ips_allocated = array()) {
  //
  // initializing WDS/p2p link parameters
  //
  $newlk = array();

  $newlk['new'] = TRUE;
  $newlk['interface'] = array();

  $newlk['link_type'] = 'wds';
  $newlk['flag'] = 'Planned';
  $newlk['routing'] = 'BGP';

  if( !$ips_allocated ) {
    // get list of the current used ips
    $ips_allocated = guifi_ipcalc_get_ips('0.0.0.0', '0.0.0.0', array(), 2);
  }

  // get an ip addres for local-remote interfaces
  $net = guifi_ipcalc_get_subnet_by_nid($nid, '255.255.255.252', 'backbone', $ips_allocated);

  if (!$net) {
    return -1;
  }

  $dnet = ip2long($net);
  $ip1 = long2ip($dnet + 1);
  $ip2 = long2ip($dnet + 2);

  $newlk['interface']['interface_type'] = 'wds/p2p';

  // remote ipv4
  $newlk['interface']['ipv4'] = array();
  $newlk['interface']['ipv4']['new'] = TRUE;
  $newlk['interface']['ipv4']['ipv4_type'] = 2;
  $newlk['interface']['ipv4']['ipv4'] = $ip2;
  $newlk['interface']['ipv4']['netmask'] = '255.255.255.252';

  // initializing local interface
  $newif = array();
  $newif['new'] = TRUE;
  $newif['ipv4_type'] = 2;
  $newif['ipv4'] = $ip1;
  $newif['netmask'] = '255.255.255.252';
  // agregating into the main array
  $newif['links'] = array();
  $newif['links'][0] = $newlk;

  return $newif;
}

function guifi_radio_add_wds_submit(&$form, &$form_state) {
  $radio_id = $form_state['clicked_button']['#parents'][1];
  $interface_id = $form_state['clicked_button']['#parents'][3];

  guifi_log(GUIFILOG_TRACE,sprintf("function guifi_radio_add_wds(Radio: %d, Interface: %d)",$radio_id,$interface_id),
    $form_state['clicked_button']['#parents']);

  $form_state['rebuild'] = TRUE;
  $form_state['action'] = 'guifi_radio_add_wds_form';

  // get list of the current used ips
  $ips_allocated = guifi_ipcalc_get_ips('0.0.0.0', '0.0.0.0', $form_state['values'], 2);

  $newif = _guifi_radio_add_wds_get_new_interface($form_state['values']['nid'], $ips_allocated);
  if( $newif == -1 ) {
    drupal_set_message(t('Unable to create link, no networks available'),'warning');
    return FALSE;
  }

  // Initialize filters
  $form_state['filters'] = array(
    'dmin'   => 0,
    'dmax'   => 15,
    'search' => NULL,
//    'max' => 25,
    'type'   => 'wds',
    'mode'   => $form_state['values']['radios'][$radio_id]['mode'],
    'from_node' => $form_state['values']['nid'],
    'from_device' => $form_state['values']['id'],
    'from_radio' => $radio_id,
    'azimuth' => "0,360",
  );

  $form_state['newInterface'][$interface_id]['ipv4'][] = $newif;

  return TRUE;
}

?>
