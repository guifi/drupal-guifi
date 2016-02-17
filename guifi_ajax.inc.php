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

  $form['if']['interfaces']['ifs'][$int_name][$int_id]['interface']['AddPublicSubnetMask']['selectNetmask']['#title']
    = t("Network mask");
  $form['if']['interfaces']['ifs'][$int_name][$int_id]['interface']['AddPublicSubnetMask']['selectNetmask']['#description']
    = t('Size of the next available set of addresses to be allocated');
  unset($form['if']['interfaces']['ifs'][$int_name][$int_id]['interface']['AddPublicSubnetMask']['selectNetmask']['#attributes']['hidden']);

  unset($form['if']['interfaces']['ifs'][$int_name][$int_id]['interface']['AddPublicSubnetMask']['createNetmask']['#attributes']['hidden']);

  return $form['if']['interfaces']['ifs'][$int_name][$int_id]['interface']['AddPublicSubnetMask'];
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
function guifi_ajax_select_firmware_by_model($form, &$form_state){
  return $form['radio_settings']['variable']['firmware_id'];
}
?>
