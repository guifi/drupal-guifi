<?php
/**
 * @file guifi_ajax.inc.php
 * Created on 17/02/2016
 *
 * Functions for AJAX (Asynchronous Javascript And Xml) used in some forms.
 *
 * This file contains the AJAX functions called along several forms (e.g. a device's form)
 * to render them dynamically.
 *
 */


/**
 * Function guifi_ajax_add_public_subnet_mask
 *
 * This function returns the network mask size selection dropdown that allows
 * choosing the size of the new public subnetwork about to be created. It is
 * called by the image button used to allocate a new public subnetwork to an
 * interface. The function unhides two hidden elements of the form, adds a
 * title and a description and returns that part of the form, refreshing it.
 *
 * URL: http://guifi.net/guifi/js/add-subnet-mask/%
 */
function guifi_ajax_add_subnet_mask(&$form, &$form_state, $moreinfo) {
  $int_name = $form_state['triggering_element']['#array_parents'][3];
  $int_id = $form_state['triggering_element']['#array_parents'][4];

  $f = $form['if']['interfaces']['ifs'][$int_name][$int_id]['interface']['AddPublicSubnetMask'];
  $f['selectNetmask']['#title'] = t("Network mask");
  $f['selectNetmask']['#description'] = t('Size of the next available set of addresses to be allocated');
  $f['selectNetmask']['#prefix'] = '<div id="editInterface-'.$int_id.'"><table style="width: 0"><td align="left">';
  $f['selectNetmask']['#suffix'] = '</td>';
  unset($f['selectNetmask']['#attributes']['hidden']);

  $f['createNetmask']['#prefix'] = '<td align="left">';
  $f['createNetmask']['#suffix'] = '</td></table></div>';
  unset($f['createNetmask']['#attributes']['hidden']);

  return $f;
}

/**
 * Function guifi_ajax_add_cable_local_link
 * TODO info
 */
function guifi_ajax_add_cable_local_link(&$form, &$form_state, $moreinfo) {

  $int_name = $form_state['triggering_element']['#array_parents'][3];
  $int_id = $form_state['triggering_element']['#array_parents'][4];

  if ($form_state['triggering_element']['#array_parents'][5] == 'ipv4') {
    // Public address
    $public = TRUE;
    $ipv4_id = $form_state['triggering_element']['#array_parents'][6];
  } else {
    // Private address
    $public = FALSE;
  }
  $values = $form_state['values'];
  $orig_device_id = $values['id'];
  $node = explode('-',$values['movenode']);
  $qry = db_query('SELECT id, nick ' .
                  'FROM {guifi_devices} ' .
                  'WHERE nid = :nid',
                  array(':nid' => $node[0]));

  while ($value = $qry->fetchAssoc()) {
    if (!($value['id'] == $orig_device_id))
      $list[$value['id']] = $value['nick'];
  }
  if (count($values['interfaces'])) foreach ($values['interfaces'] as $iid => $intf)
    if (count($intf['ipv4'])) foreach ($intf['ipv4'] as $i => $ipv4)
      if (count($ipv4['links'])) foreach ($ipv4['links'] as $l => $link) {
        if (isset($list[$link['device_id']]))
          unset($list[$link['device_id']]);
      }

  if ($public == FALSE)
    $f = $form['if']['interfaces']['ifs'][$int_name][$int_id]['interface']['CreateCableLink'];
  else
    $f = $form['if']['interfaces']['ifs'][$int_name][$int_id]['ipv4'][$ipv4_id]['local']['CreateCableLink'];

  if ($node[0] != $values['nid']) {
    $f['msg'] = array(
      '#type' => 'item',
      '#title' => t('Device node changed. Option not available'),
      '#description' => t('Can\'t link this device to another device ' .
      'since has been changed the assigned node.<br />' .
      'To link the device to a device defined at another node, ' .
      'you should save the node of this device before proceeding.')
    );
  } else if (count($list)) {
    $f['to_did']['#description'] = t('Select the device which you want to link with');
    $f['to_did']['#options'] = $list;
    $f['to_did']['#prefix'] = '<div id="editInterface-'.$int_id.'"><table style="width: 0"><td align="left">';
    $f['to_did']['#suffix'] = '</td>';
    unset($f['to_did']['#attributes']['hidden']);

    $f['createLink']['#prefix'] = '<td align="left">';
    $f['createLink']['#suffix'] = '</td></table></div>';
    unset($f['createLink']['#attributes']['hidden']);
  } else {
    $f['msg'] = array(
      '#type' => 'item',
      '#title' => t('No devices available'),
      '#description' => t('Can\'t link this device to another device ' .
      'since there are no other devices defined on this node.'),
    );
  }
  return $f;
}

/**
 * Function guifi_ajax_select_firmware_by_model
 *
 * This function returns the firmware selection dropdown that allows choosing a
 * specific firmware for a device in the device edition form. The function is
 * called after changing the manufacturer/model of a device, so that the form
 * is refreshed and shows the list of valid firmwares for the new
 * manufacturer/model.
 *
 * @param  array  $form        The form generated for the device edition
 * @param  array  $form_state  The current state of the form
 * @return array               The firmware selection item in the form
 */
function guifi_ajax_select_firmware_by_model(&$form, &$form_state){
  return $form['radio_settings']['variable']['firmware_id'];
}

?>
