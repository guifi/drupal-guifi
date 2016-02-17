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
