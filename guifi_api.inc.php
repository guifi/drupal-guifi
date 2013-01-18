<?php
/**
 * @file guifi_api.inc.php
 */

// The source code packaged with this file is Free Software, Copyright (C) 2010 by
// Eduard Duran <eduard.duran@iglu.cat>.
// It's licensed under the GENERAL PUBLIC LICENSE v2.0 unless stated otherwise.
// You can get copies of the licenses here:
// 		http://www.gnu.org/licenses/gpl-2.0.html
// GENERAL PUBLIC LICENSE v2.0 is also included in the file called "LICENSE.txt".

/**
 *
 * @return
 *   NULL
 */
function guifi_api() {
  $gapi = new GuifiAPI();
  if( $gapi->ready ) {
    $gapi->parseRequest($_GET);
    $gapi->executeRequest();
    $gapi->printResponse();
  }
  return NULL;
}

/**
 * Try to authenticate the user using any method
 * At the moment there is only one method available: 'password'
 * 
 * @param GuifiAPI $gapi GuifiAPI object
 * @param $parameters Parameters to login
 * @return boolean Whether the user authenticated or not
 */
function guifi_api_auth_login($gapi, $parameters) {
  global $user;
  
//  if (!guifi_api_check_fields($gapi, array('method' ), $parameters)) {
//    return FALSE;
//  }

  if (!empty( $parameters['method'])) {
  	$method = $parameters['method'];
  } else {
  	$method = 'password';
  }
  
  switch ($method) {
    case 'password':
      if (!guifi_api_check_fields($gapi, array('username', 'password' ), $parameters)) {
        return FALSE;
      }
      
      $account = user_load(array('name' => $parameters['username'], 'pass' => trim($parameters['password']), 'status' => 1 ));
      
      if ($account->uid) {
        $user = $account;
        $time = time();
        $rand_key = rand(100000, 999999);
        $token = base64_encode($user->uid . ':' . md5($user->mail . $user->pass . $user->created . $user->uid . $time . $rand_key) . ':' . $time);
        db_query("DELETE FROM {guifi_api_tokens} WHERE uid = %d", $user->uid);
        db_query("INSERT INTO {guifi_api_tokens} (uid, token, created, rand_key) VALUES (%d, '%s', FROM_UNIXTIME(%d), %d)", $user->uid, $token, $time, $rand_key);
        $gapi->addResponseField('authToken', $token);
        return TRUE;
      } else {
        $gapi->addError(403, "Either the supplied username or password are not correct");
        return FALSE;
      }
      
      break;
  }
  return FALSE;
}

/**
 * Builds an array with the user information and submits it
 * @param $type
 *
 * @param $title
 *
 * @return submitted node
 * @todo node_submit() doesn't exist?
 */
function _guifi_api_prepare_node($type, $title) {
  global $user;
  $edit = array();
  $edit['type'] = $type;
  $edit['name'] = $user->name;
  $edit['uid'] = $user->uid;
  $edit['comment'] = variable_get('comment_' . $edit['type'], 2);
  $edit['status'] = 1;
  $edit['format'] = FILTER_FORMAT_DEFAULT;
  $edit['title'] = $title;
  
  if (!node_access('create', $edit['type'])) {
    return FALSE;
  }
  
  $node = node_submit($edit);
  return $node;
}

/**
 * Checks zone parameters data types (coherency)
 * 
 * @param GuifiAPI $gapi GuifiAPI object
 * @param mixed[] $parameters
 *
 * @return TRUE if the parameters values passed all checks, FALSE otherwise
 */
function _guifi_api_zone_check_parameters($gapi, &$parameters) {
  extract($parameters);
  
  if (isset($minx) || isset($maxx) || isset($miny) || isset($maxy)) {
    if (isset($minx) && isset($maxx) && isset($miny) && isset($maxy)) {
      if (!is_numeric($minx)) {
        $gapi->addError(403, "minx: $minx");
      }
      if (!is_numeric($maxx)) {
        $gapi->addError(403, "maxx: $maxx");
      }
      if (!is_numeric($miny)) {
        $gapi->addError(403, "miny: $miny");
      }
      if (!is_numeric($maxy)) {
        $gapi->addError(403, "maxy: $maxy");
      }
      if ($minx > $maxx || $miny > $maxy) {
        $gapi->addError(403, "Coordinates are wrong");
      }
    } else {
      $gapi->addError(403, "all coordinates should be specified");
    }
  }
  
  if (isset($ospf_zone)) {
    if (($ospf_zone != htmlentities($ospf_zone)) || str_word_count($ospf_zone) > 1) {
      $gapi->addError(403, "ospf_zone: $ospf_zone");
      return FALSE;
    }
  }
  
  if (isset($notification)) {
    if (!guifi_notification_validate($notification)) {
      $gapi->addError(403, "notification: $notification");
      return FALSE;
    }
  }
  
  //Checks the service id exists and its type is 'SNPgraphs'
  if (!empty($graph_server)) {
    $server = db_fetch_object(db_query("SELECT id FROM {guifi_services} WHERE id = '%d' AND service_type = 'SNPgraphs'", $graph_server));
    if (!$server->id) {
      $gapi->addError(403, "graph_server: $graph_server");
      return FALSE;
    }
  }
  
  //Checks the service id exists and its type is 'Proxy'
  if (isset($proxy_server)) {
    $server = db_fetch_object(db_query("SELECT id FROM {guifi_services} WHERE id = '%d' AND service_type = 'Proxy'", $proxy_server));
    if (!empty($proxy_server) && !$server->id) {
      $gapi->addError(403, "proxy_server: $proxy_server");
      return FALSE;
    } else {
      $parameters['proxy_id'] = $proxy_server;
    }
  }
  
  //Checks the zone_mode is a valid type ('infrastructure' or 'ad-hoc')
  // ad-hoc zone mode deprecated and pending for delete (mesh radio only in every zone)
  if (isset($zone_mode)) {
    $zone_modes = array('infrastructure', 'ad-hoc' );
    if (!in_array($zone_mode, $zone_modes)) {
      $gapi->addError(403, "zone_mode: $zone_mode");
      return FALSE;
    }
  }
  
  return TRUE;
}

/**
 * Adds a Guifi Zone to the DB
 *
 * @param GuifiAPI $gapi
 * @param mixed $parameters Paramaters passed to specify zone properties
 *
 * @return
 */
function guifi_api_zone_add($gapi, $parameters) {
  global $user;
  
  if (!guifi_api_check_fields($gapi, array('title', 'master', 'minx', 'miny', 'maxx', 'maxy' ), $parameters)) {
    return FALSE;
  }
  
  extract($parameters);
  
  $node = _guifi_api_prepare_node('guifi_zone', $title);
  
  // Set defaults
  $node->nick = guifi_abbreviate($title);
  $node->notification = $user->mail;
  $node->dns_servers = '';
  $node->ntp_servers = '';
  $node->graph_server = '';
  $node->homepage = '';
  $node->time_zone = '+01 2 2';
  $node->ospf_zone = '';
  
  if (!_guifi_api_zone_check_parameters($gapi, $parameters)) {
    return FALSE;
  }
  
  foreach ($parameters as $key => $value) {
    $node->$key = $value;
  }
  
  if (!guifi_zone_access('create', $node)) {
    $gapi->addError(501);
    return FALSE;
  }
  
  node_validate($node);
  if ($errors = form_get_errors()) {
    foreach ($errors as $err) {
      $gapi->addError(403, $err);
    }
  }
  
  node_save($node);
  
  $gapi->addResponseField('zone_id', $node->id);
  return TRUE;
}

/**
 * Updates a Guifi Zone to the DB
 *
 * @param GuifiAPI $gapi
 * @param mixed $parameters Paramaters passed to specify zone properties
 *
 * @return
 */
function guifi_api_zone_update($gapi, $parameters) {
  if (!guifi_api_check_fields($gapi, array('zone_id' ), $parameters)) {
    return FALSE;
  }
  
  extract($parameters);
  
  $node = node_load($zone_id);
  
  if (!_guifi_api_zone_check_parameters($gapi, $parameters)) {
    return FALSE;
  }
  
  foreach ($parameters as $key => $value) {
    $node->$key = $value;
  }
  
  if (!guifi_zone_access('update', $node)) {
    $gapi->addError(501);
    return FALSE;
  }
  
  if ($node->type != 'guifi_zone') {
    $gapi->addError(500, "zone_id = $node->id is not a zone");
    return FALSE;
  }
  
  node_validate($node);
  if ($errors = form_get_errors()) {
    foreach ($errors as $err) {
      $gapi->addError(403, $err);
    }
  }
  
  node_save($node);
  
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function guifi_api_zone_remove($gapi, $parameters) {
  if (!guifi_api_check_fields($gapi, array('zone_id' ), $parameters)) {
    return FALSE;
  }
  
  $node = node_load($parameters['zone_id']);
  
  if (!$node->id) {
    $gapi->addError(500, "zone_id = {$parameters['zone_id']}");
    return FALSE;
  }
  
  if ($node->type != 'guifi_zone') {
    $gapi->addError(500, "zone_id = $node->id is not a zone");
    return FALSE;
  }
  
  if (node_access('delete', $node) && guifi_zone_access('update', $node)) {
    node_delete($node->id);
  } else {
    $gapi->addError(501);
    return FALSE;
  }
  
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function guifi_api_zone_nearest($gapi, $parameters) {
  if (!guifi_api_check_fields($gapi, array('lat', 'lon' ), $parameters)) {
    return FALSE;
  }

  $candidates = guifi_zone_get_nearest_candidates($parameters['lat'], $parameters['lon']);
  foreach( $candidates as &$candidate ) {
    $candidate['zone_id'] = $candidate['id'];
    unset( $candidate['min_lon'], $candidate['max_lon'], $candidate['min_lat'], $candidate['max_lat'], $candidate['d'], $candidate['id'] );
  }
  $nearest = guifi_zone_get_nearest($parameters['lat'], $parameters['lon'], $candidates);
  unset( $nearest['d'] );
  $gapi->addResponseField('candidates', $candidates);
  $gapi->addResponseField('nearest', $nearest);
  return TRUE;
}

function _guifi_api_node_check_parameters($gapi, $parameters) {
  extract($parameters);
  
  if (isset($status)) {
    if (guifi_validate_types('status', $status)) {
      $parameters['status_flag'] = $status;
      unset($parameters['status']);
    } else {
      $gapi->addError(403, "status: $status");
      return FALSE;
    }
  }
  
  if (isset($lat) || isset($lon)) {
    if (isset($lat) && isset($lon)) {
      if (!is_numeric($lat)) {
        $gapi->addError(403, "lat: $lat");
      }
      if (!is_numeric($lon)) {
        $gapi->addError(403, "lon: $lon");
      }
    } else {
      $gapi->addError(403, "all coordinates should be specified");
    }
  }
  
  if (isset($notification)) {
    if (!guifi_notification_validate($notification)) {
      $gapi->addError(403, "notification: $notification");
      return FALSE;
    }
  }
  
  if (isset($graph_server)) {
    $server = db_fetch_object(db_query("SELECT id FROM {guifi_services} WHERE id = '%d' AND service_type = 'SNPgraphs'", $graph_server));
    if (!$server->id) {
      $gapi->addError(403, "graph_server: $graph_server");
      return FALSE;
    }
  }
  
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function guifi_api_node_add($gapi, $parameters) {
  global $user;
  
  if (!guifi_api_check_fields($gapi, array('title', 'zone_id', 'lat', 'lon' ), $parameters)) {
    return FALSE;
  }
  
  extract($parameters);
  
  $title = $parameters['title'];
  
  $node = _guifi_api_prepare_node('guifi_node', $title);
  // Set defaults
  $node->nick = guifi_abbreviate($title);
  $node->notification = $user->mail;
  $node->graph_server = 0;
  $node->status_flag = 'Planned';
  $node->zone_description = '';
  $node->elevation = 0;
  $node->stable = 'Yes';
  
  if (!_guifi_api_node_check_parameters($gapi, $parameters)) {
    return FALSE;
  }
  
  foreach ($parameters as $key => $value) {
    $node->$key = $value;
  }
  
  if (!guifi_node_access('create', $node)) {
    $gapi->addError(501);
    return FALSE;
  }
  
  node_validate($node);
  if ($errors = form_get_errors()) {
    foreach ($errors as $err) {
      $gapi->addError(403, $err);
    }
  }
  
  node_save($node);
  
  $gapi->addResponseField('node_id', $node->id);
  return TRUE;
}

/**
 * Updates a guifi.net node
 * @param GuifiAPI $gapi
 * @param mixed[] $parameters
 * @return unknown_type
 */
function guifi_api_node_update($gapi, $parameters) {
  if (!guifi_api_check_fields($gapi, array('node_id' ), $parameters)) {
    return FALSE;
  }
  
  extract($parameters);
  
  $node = node_load($node_id);
  
  if (!_guifi_api_node_check_parameters($gapi, $parameters)) {
    return FALSE;
  }
  
  foreach ($parameters as $key => $value) {
    $node->$key = $value;
  }
  $gapi->addResponseField('node', $node);
  if (!guifi_node_access('update', $node)) {
    $gapi->addError(501);
    return FALSE;
  }
  
  if ($node->type != 'guifi_node') {
    $gapi->addError(500, "zone_id = $node->id is not a guifi node");
    return FALSE;
  }
  
  node_validate($node);
  if ($errors = form_get_errors()) {
    foreach ($errors as $err) {
      $gapi->addError(403, $err);
    }
  }
  node_save($node);
  
  return TRUE;
}

/**
 * Removes a node from guifi.net
 * @param GuifiAPI $gapi
 * @param mixed[] $parameters
 * @return unknown_type
 */
function guifi_api_node_remove($gapi, $parameters) {
  if (!guifi_api_check_fields($gapi, array('node_id' ), $parameters)) {
    return FALSE;
  }
  
  $node = node_load($parameters['node_id']);
  
  if (!$node->id) {
    $gapi->addError(500, "node_id = {$parameters['node_id']}");
    return FALSE;
  }
  
  if ($node->type != 'guifi_node') {
    $gapi->addError(500, "node_id = $node->id is not a guifi node");
    return FALSE;
  }
  
  if (node_access('delete', $node) && guifi_node_access('update', $node)) {
    node_delete($node->id);
  } else {
    $gapi->addError(501);
    return FALSE;
  }
  
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function _guifi_api_device_check_parameters($gapi, &$parameters) {
  extract($parameters);
  
  if (isset($status)) {
    if (guifi_validate_types('status', $status)) {
      $parameters['flag'] = $status;
    } else {
      $gapi->addError(403, "status: $status");
      return FALSE;
    }
  }
  
  if (isset($mac)) {
    if (!_guifi_validate_mac($mac)) {
      $gapi->addError(403, "mac: $mac");
      return FALSE;
    }
  }
  
  if (!empty($nick)) {
    guifi_validate_nick($nick);
    if ($errors = form_get_errors()) {
      foreach ($errors as $err) {
        $gapi->addError(403, $err);
      }
      return FALSE;
    }
    
    $query = db_query("SELECT nick FROM {guifi_devices} WHERE lcase(nick) = lcase('%s') AND id != %d", strtolower($nick), intval($parameters['device_id']));
    
    while (db_fetch_object($query)) {
      $gapi->addError(403, 'nick already in use');
      return FALSE;
    }
  }
  
  switch ($type) {
    case 'radio':
      if (!guifi_api_check_fields($gapi, array('mac', 'model_id', 'firmware' ), $parameters)) {
        return FALSE;
      }
      $model = db_fetch_object(db_query("SELECT model name FROM {guifi_model} WHERE mid = '%d' LIMIT 1", $model_id));
      if (!guifi_validate_types('firmware', $firmware, $model->name)) {
        $gapi->addError(403, "firmware is not supported: $firmware");
        return FALSE;
      }
      break;
    case 'mobile':
      break;
    case 'server':
      break;
    case 'nat':
      break;
    case 'generic':
      break;
    case 'adsl':
      if (!guifi_api_check_fields($gapi, array('download', 'upload', 'mrtg_index' ), $parameters)) {
        return FALSE;
      }
      break;
    case 'cam':
      break;
    case 'phone':
      break;
  }
  
  return TRUE;
}

/**
 * Method the API uses to add a device into the DB
 *
 * @param GuifiAPi $gapi
 * @param mixed $parameters Parameters of the device to be added
 *
 * @return
 */
function guifi_api_device_add($gapi, $parameters) {
  global $user;
  if (!guifi_api_check_fields($gapi, array('node_id', 'type' ), $parameters)) {
    return FALSE;
  }
  
  // default values
  $device = array();
  $device['type'] = $parameters['type'];
  $device['nid'] = $parameters['node_id'];
  $device['notification'] = $user->mail;
  
  $node = (array) node_load($device['nid']);
  
  if (!$node['id']) {
    $gapi->addError(500, "node_id = {$parameters['node_id']}");
    return FALSE;
  }
  
  $device['nick'] = guifi_device_get_default_nick((object)$node, $device['type'], $device['nid']);
  
  if (!_guifi_api_device_check_parameters($gapi, $parameters)) {
    return FALSE;
  }
  
  foreach ($parameters as $key => $value) {
    $device[$key] = $value;
  }

  $firmware=db_fetch_object(db_query(
        "SELECT id, nom " .
        "FROM {guifi_firmware} " .
        "WHERE nom = '%s'",
    $device['firmware']));

  $device['new'] = TRUE;
  $device['variable'] = array('model_id' => $device['model_id'], 'firmware' => $device['firmware'], 'firmware_id' => $firmware->id );
  $device['mid'] = $device['model_id'];
  $device['fid'] = $firmware->id;

  if (!guifi_device_access('create', $device)) {
    $gapi->addError(501);
    return FALSE;
  }
  
  $device_id = guifi_device_save($device);
  
  //  $data = _guifi_db_sql('guifi_devices', array('id' => $device->id ), $device, $log, $to_mail);
  //  $device->id = $data['id'];
  $gapi->addResponseField('device_id', $device_id);
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function guifi_api_device_update($gapi, $parameters) {
  if (!guifi_api_check_fields($gapi, array('device_id' ), $parameters)) {
    return FALSE;
  }
  
  $device = guifi_device_load($parameters['device_id']);
  
  if (!$device['id']) {
    $gapi->addError(500, "device_id = {$parameters['device_id']}");
    return FALSE;
  }
  
  if (!guifi_device_access('update', $device)) {
    $gapi->addError(501);
    return FALSE;
  }
  
  if (!_guifi_api_device_check_parameters($gapi, $parameters)) {
    return FALSE;
  }
  
  foreach ($parameters as $key => $value) {
    $device[$key] = $value;
  }

  $firmware=db_fetch_object(db_query(
        "SELECT id, nom " .
        "FROM {guifi_firmware} " .
        "WHERE nom = '%s'",
    $device['firmware']));

  $device['variable'] = array('model_id' => $device['model_id'], 'firmware' => $device['firmware'], 'firmware_id' => $firmware->id );
  $device['mid'] = $device['model_id'];
  $device['fid'] = $firmware->id;

  $device_id = guifi_device_save($device);
}

/**
 * Remove a device from guifi.net
 * 
 * @param GuifiAPI $gapi
 * @param mixed[] $parameters Parameters to remove the device (device_id, basically)
 * @return boolean Whether the device was removed or not
 */
function guifi_api_device_remove($gapi, $parameters) {
  if (!guifi_api_check_fields($gapi, array('device_id' ), $parameters)) {
    return FALSE;
  }
  
  global $user;
  
  $device = guifi_device_load($parameters['device_id']);
  if (!$device['id']) {
    $gapi->addError(500, "device_id = {$parameters['device_id']}");
    return FALSE;
  }
  
  guifi_log(GUIFILOG_TRACE, 'function guifi_device_delete()');
  
  $to_mail = explode(',', $device['notification']);
  
  $log = _guifi_db_delete('guifi_devices', array('id' => $device['id'] ), $to_mail);
  drupal_set_message($log);
  
  $subject = t('The device %name has been DELETED by %user.', array('%name' => $device['nick'], '%user' => $user->name ));
  guifi_notify($to_mail, $subject, $log, $verbose, $notify);
  guifi_node_set_flag($device['nid']);
  
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function _guifi_api_radio_check_parameters($gapi, $parameters) {
  extract($parameters);
  
  if (!empty($mac)) {
    if (!_guifi_validate_mac($mac)) {
      $gapi->addError(403, "mac: $mac");
      return FALSE;
    }
  }
  
  if (isset($antenna_angle)) {
    $antenna_angles = array(0, 6, 60, 90, 120, 360 );
    if (!in_array($anntena_angle, $antenna_angles)) {
      $gapi->addError(403, "antenna_angle: $antenna_angle");
      return FALSE;
    }
  }
  
  if (isset($antenna_gain)) {
    if (is_numeric($antenna_gain)) {
      $antenna_gain = (int) $antenna_gain;
    }
    $antenna_gains = array(2, 8, 12, 14, 18, 21, 24, 'more' );
    if (!in_array($antenna_gain, $antenna_gains)) {
      $gapi->addError(403, "antenna_gain: $antenna_gain");
      return FALSE;
    }
  }
  
  if (isset($antenna_azimuth)) {
    $antenna_azimuth = (int) $antenna_azimuth;
    if (!is_numeric($antenna_azimuth) || $antenna_azimuth > 360 || $antenna_azimuth < 0) {
      $gapi->addError(403, "antenna_azimuth: $antenna_azimuth");
      return FALSE;
    }
  }
  
  if (isset($antenna_mode)) {
    $antenna_modes = array('Main', 'Aux' );
    if (!in_array($antenna_mode, $antenna_modes)) {
      $gapi->addError(403, "antenna_mode: $antenna_mode");
      return FALSE;
    }
  }
  
  switch ($mode) {
    case 'ap':
      if (isset($clients_accepted)) {
        $clients_accepted_modes = array('Yes', 'No' );
        if (!in_array($clients_accepted, $clients_accepted_modes)) {
          $gapi->addError(403, "clients_accepted: $clients_accepted");
          return FALSE;
        }
      }
    // Pending to revise, for mesh
    case 'ad-hoc':
      if (isset($protocol)) {
        if (!guifi_validate_types('protocol', $protocol)) {
          $gapi->addError(403, "protocol is not supported: $protocol");
          return FALSE;
        }
      }
      if (isset($channel)) {
        if (!guifi_validate_types('channel', $channel, $protocol)) {
          $gapi->addError(403, "channel is not supported: $channel");
          return FALSE;
        }
      }
      break;
  }
  
  if (isset($graph_server)) {
    $server = db_fetch_object(db_query("SELECT id FROM {guifi_services} WHERE id = '%d' AND service_type = 'SNPgraphs'", $graph_server));
    if (!$server->id) {
      $gapi->addError(403, "graph_server: $graph_server");
      return FALSE;
    }
  }
  
  return TRUE;
}

/**
 * Adds a radio to a device
 * @param GuifiAPI $gapi
 * @param mixed[] $parameters Parameters of the radio
 * @return unknown_type
 */
function guifi_api_radio_add($gapi, $parameters) {
  if (!guifi_api_check_fields($gapi, array('mode', 'device_id' ), $parameters)) {
    return FALSE;
  }
  
  $device = guifi_device_load($parameters['device_id']);
  
  if (!$device['id']) {
    $gapi->addError(500, "device not found: {$parameters['device_id']}");
    return FALSE;
  }
  
  $maxradios = db_fetch_object(db_query('SELECT radiodev_max FROM {guifi_model} WHERE mid=%d', $device['variable']['model_id']));
  $maxradios = $maxradios->radiodev_max;
  
  if (count($device['radios']) >= $maxradios) {
    $gapi->addError(404, "This device already has the maximum number of radios allowed: $maxradios");
    return FALSE;
  }
  
  if (count($device['radios']) > 0) {
    if (!guifi_api_check_fields($gapi, array('mac' ), $parameters)) {
      return FALSE;
    }
  }
  
  $device['newradio_mode'] = $parameters['mode'];
  
  $radio = _guifi_radio_prepare_add_radio($device);
  
  $fields = array('mac', 'antenna_angle', 'antenna_gain', 'antenna_azimuth', 'antenna_mode' );
  if ($parameters['mode'] == 'ap') {
    $fields = array_merge($fields, array('ssid', 'protocol', 'channel', 'clients_accepted' ));
  } else if ($parameters['mode'] == 'mesh') {
    $fields = array_merge($fields, array('ssid', 'protocol', 'channel' ));
  }
  
  if (!_guifi_api_radio_check_parameters($gapi, $parameters)) {
    return FALSE;
  }
  
  if (!guifi_device_access('update', $device)) {
    $gapi->addError(501);
    return FALSE;
  }
  
  foreach ($fields as $field) {
    if (isset($parameters[$field])) {
      $radio[$field] = $parameters[$field];
    }
  }
  
  $device['radios'][] = $radio;
  
  guifi_device_save($device);
  
  $gapi->addResponseField('radiodev_counter', count($device['radios']) - 1);
  $interfaces = array();
  if (!empty($radio['interfaces'])) {
    foreach ($radio['interfaces'] as $if) {
      $interface = array();
      $interface['interface_type'] = $if['interface_type'];
      
      if (!empty($if['ipv4'])) {
        $interface['ipv4'] = array();
        foreach ($if['ipv4'] as $if_ipv4) {
          $ipv4 = array();
          $ipv4['ipv4_type'] = $if_ipv4['ipv4_type'];
          $ipv4['ipv4'] = $if_ipv4['ipv4'];
          $ipv4['netmask'] = $if_ipv4['netmask'];
          
          $interface['ipv4'][] = $ipv4;
        }
      }
      
      $interfaces[] = $interface;
    }
  }
  if (!empty($interfaces)) {
    $gapi->addResponseField('interfaces', $interfaces);
  }
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function guifi_api_radio_update($gapi, $parameters) {
  if (!guifi_api_check_fields($gapi, array('device_id', 'radiodev_counter' ), $parameters)) {
    return FALSE;
  }
  
  $device = guifi_device_load($parameters['device_id']);
  
  $radiodev_counter = $parameters['radiodev_counter'];
  
  if (!$device['id']) {
    $gapi->addError(500, "device not found: {$parameters['device_id']}");
    return FALSE;
  }
  
  if (!isset($device['radios'][$radiodev_counter])) {
    $gapi->addError(500, "radio not found: $radiodev_counter");
    return FALSE;
  }
  
  $radio = $device['radios'][$radiodev_counter];
  
  $fields = array('mac', 'antenna_angle', 'antenna_gain', 'antenna_azimuth', 'antenna_mode' );
  if ($radio['mode'] == 'ap') {
    $fields = array_merge($fields, array('ssid', 'protocol', 'channel', 'clients_accepted' ));
  } else if ($radio['mode'] == 'mesh') {
    $fields = array_merge($fields, array('ssid', 'protocol', 'channel' ));
  }
  
  if (!_guifi_api_radio_check_parameters($gapi, $parameters)) {
    return FALSE;
  }
  
  if (!guifi_device_access('update', $device)) {
    $gapi->addError(501);
    return FALSE;
  }
  
  foreach ($fields as $field) {
    if (isset($parameters[$field])) {
      $radio[$field] = $parameters[$field];
    }
  }
  
  $device['radios'][$radiodev_counter] = $radio;
  
  guifi_device_save($device);
  
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function guifi_api_radio_remove($gapi, $parameters) {
  if (!guifi_api_check_fields($gapi, array('device_id', 'radiodev_counter' ), $parameters)) {
    return FALSE;
  }
  
  $device = guifi_device_load($parameters['device_id']);
  
  if (!$device['id']) {
    $gapi->addError(500, "device not found: {$parameters['device_id']}");
    return FALSE;
  }
  
  if (!guifi_device_access('update', $device)) {
    $gapi->addError(501);
    return FALSE;
  }
  
  $radiodev_counter = intval($parameters['radiodev_counter']);
  
  if (isset($device['radios'][$radiodev_counter])) {
    $device['radios'][$radiodev_counter]['deleted'] = TRUE;
  } else {
    $gapi->addError(500, "radio not found: $radiodev_counter");
    return FALSE;
  }
  
  guifi_device_save($device);
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function guifi_api_radio_nearest($gapi, $parameters) {
   if (!guifi_api_check_fields($gapi, array('node_id' ), $parameters)) {
    return FALSE;
  }
  
  $node = node_load($parameters['node_id']);
  
  if (!$node->id) {
    $gapi->addError(500, "node not found: {$parameters['node_id']}");
    return FALSE;
  }
  
  if ($node->type != 'guifi_node') {
    $gapi->addError(500, "zone_id = $node->id is not a node");
    return FALSE;
  }
  
  if( empty( $parameters['dmax'] ) ) {
    $parameters['dmax'] = 15;
  }
  if( empty( $parameters['dmin'] ) ) {
    $parameters['dmin'] = 0;
  }
  
  $query = sprintf("
      SELECT
        l.lat, l.lon, r.id, r.radiodev_counter, r.nid, z.id zone_id,
        r.radiodev_counter, r.ssid, r.mode, r.antenna_mode
      FROM {guifi_radios} r, {guifi_location} l, {guifi_zone} z
      WHERE l.id <> %d
        AND r.nid = l.id
        AND r.mode = 'ap' 
        AND l.zone_id = z.id",
        $node->id);
  
  $devdist = array();
  $devarr = array();
  $k = 0;
  $devsq = db_query($query);
  
  while ($device = db_fetch_object($devsq)) {
    $k++;
    $l = FALSE;
    
    $oGC = new GeoCalc();
    $distance = round( $oGC->EllipsoidDistance($device->lat, $device->lon, $node->lat, $node->lon), 3);
    
    if (($distance > $parameters['dmax']) or ($distance < $parameters['dmin'])) {
      continue;
    }

    $l = TRUE;
    
    if ($l) {
      $devdist[$k] = $distance;
      $devarr[$k] = $device;
      $devarr[$k]->distance = $distance;
    }
  }
  
  asort($devdist);
  
  $devices = array();
  
  foreach ($devdist as $id => $foo) {
    $device = $devarr[$id];
 
    $devices[] = array('device_id' => $device->id, 'radiodev_counter' => $device->radiodev_counter, 'ssid' => $device->ssid, 'distance' => $device->distance);
    
    if( count( $devices ) == 50 ) {
      break;
    }
  }
  
  $gapi->addResponseField('radios', $devices);
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function guifi_api_interface_add($gapi, $parameters) {
  if (!guifi_api_check_fields($gapi, array('device_id', 'radiodev_counter' ), $parameters)) {
    return FALSE;
  }
  
  $device = guifi_device_load($parameters['device_id']);
  
  if (!guifi_device_access('update', $device)) {
    $gapi->addError(501);
    return FALSE;
  }
  
  if (!$device['id']) {
    $gapi->addError(500, "device not found: {$parameters['device_id']}");
    return FALSE;
  }
  
  $radiodev_counter = intval($parameters['radiodev_counter']);
  
  if (!isset($device['radios'][$radiodev_counter])) {
    $gapi->addError(500, "radio not found: $radiodev_counter");
    return FALSE;
  }
  
  $interface = _guifi_radio_add_wlan($radiodev_counter, $device['nid']);
  
  $old_interfaces = array_keys($device['radios'][$radiodev_counter]['interfaces']);
  
  $device['radios'][$radiodev_counter]['interfaces'][] = $interface;
  
  $device_id = guifi_device_save($device);
  $device = guifi_device_load($device_id);
  
  $new_interfaces = array_keys($device['radios'][$radiodev_counter]['interfaces']);
  
  $interface_id = array_shift(array_diff($new_interfaces, $old_interfaces));
  
  $interface = $device['radios'][$radiodev_counter]['interfaces'][$interface_id];
  
  if (!empty($interface['ipv4'])) {
    $ipv4 = array();
    foreach ($interface['ipv4'] as $if_ipv4) {
      $new_ipv4 = array();
      $new_ipv4['ipv4_type'] = $if_ipv4['ipv4_type'];
      $new_ipv4['ipv4'] = $if_ipv4['ipv4'];
      $new_ipv4['netmask'] = $if_ipv4['netmask'];
      $ipv4[] = $new_ipv4;
    }
    
    $gapi->addResponseField('ipv4', $ipv4);
  }
  
  $gapi->addResponseField('interface_id', $interface_id);
  
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function guifi_api_interface_remove($gapi, $parameters) {
  if (!guifi_api_check_fields($gapi, array('interface_id' ), $parameters)) {
    return FALSE;
  }
  
  $interface_id = $parameters['interface_id'];
  
  $device_info = db_fetch_object(db_query('SELECT device_id, radiodev_counter FROM {guifi_interfaces} WHERE id = %d', $interface_id));
  $device_id = $device_info->device_id;
  $radiodev_counter = $device_info->radiodev_counter;
  
  if (!$device_id) {
    $gapi->addError(500, "interface not found: {$interface_id}");
    return FALSE;
  }
  
  $device = guifi_device_load($device_id);
  
  if (!guifi_device_access('update', $device)) {
    $gapi->addError(501);
    return FALSE;
  }
  
  if (isset($device['radios'][$radiodev_counter]['interfaces'][$interface_id])) {
    $interface = $device['radios'][$radiodev_counter]['interfaces'][$interface_id];
    
    if ($interface['interface_type'] != 'wLan') {
      $gapi->addError(404, "only extra wLan can be removed");
      return FALSE;
    } else {
      $device['radios'][$radiodev_counter]['interfaces'][$interface_id]['deleted'] = TRUE;
    }
  } else {
    $gapi->addError(500, "interface not found: $interface_id");
    return FALSE;
  }
  
  guifi_device_save($device);
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function _guifi_api_link_check_parameters($gapi, &$parameters) {
  extract($parameters);
  
  if (isset($status)) {
    if (guifi_validate_types('status', $status)) {
      $parameters['flag'] = $status;
    } else {
      $gapi->addError(403, "status: $status");
      return FALSE;
    }
  } else {
    $parameters['flag'] = 'Planned';
  }
  
  if (isset($routing)) {
    if (!guifi_validate_types('routing', $routing)) {
      $gapi->addError(403, "routing: $routing");
    }
  }
  
  return TRUE;
}


/**
 *
 * @param $l_ipv4
 *
 * @param $r_ipv4
 *
 * @return
 */
function _guifi_api_link_validate_local_ipv4($l_ipv4, $r_ipv4) {
  $item1 = _ipcalc($l_ipv4['ipv4'], $l_ipv4['netmask']);
  $item2 = _ipcalc($r_ipv4['ipv4'], $r_ipv4['netmask']);
  
  if (($item1['netstart'] != $item2['netstart']) or ($item1['netend'] != $item2['netend'])) {
    return FALSE;
  } else {
    return TRUE;
  }
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function guifi_api_link_add($gapi, $parameters) {
  if (!guifi_api_check_fields($gapi, array('from_device_id', 'from_radiodev_counter' ), $parameters)) {
    return FALSE;
  }
  
  $from_device_id = $parameters['from_device_id'];
  $from_radiodev_counter = $parameters['from_radiodev_counter'];
  
  $from_device = guifi_device_load($from_device_id);
  
  if (!$from_device['id']) {
    $gapi->addError(500, "from_device not found: $from_device_id");
    return FALSE;
  }
  
  if (!guifi_device_access('update', $from_device)) {
    $gapi->addError(501);
    return FALSE;
  }
  
  if (!guifi_api_check_fields($gapi, array('to_device_id', 'to_radiodev_counter' ), $parameters)) {
    return FALSE;
  }
  $to_device_id = $parameters['to_device_id'];
  $to_radiodev_counter = $parameters['to_radiodev_counter'];
  
  $to_device = guifi_device_load($to_device_id);
  
  if (!$to_device['id']) {
    $gapi->addError(500, "to_device not found: $to_device_id");
    return FALSE;
  }
  
  $from_radio = &$from_device['radios'][$from_radiodev_counter];
  
  if ($from_radio['mode'] == 'client') {
    $from_interface_id = array_pop(array_keys($from_radio['interfaces']));
    $from_interface = &$from_device['radios'][$from_radiodev_counter]['interfaces'][$from_interface_id];
  } else if ($from_radio['mode'] == 'ap') {
    // If radio mode is AP, find the wds/p2p interface (could be others, like wLan/Lan)
    foreach ($from_radio['interfaces'] as $from_interface_id => $from_interface) {
      if ($from_interface['interface_type'] == 'wds/p2p') {
        break;
      }
    }
  }
  
  if (!_guifi_api_link_check_parameters($gapi, $parameters)) {
    return FALSE;
  }
  
  // Check if the link is allowed
  // Between Wan and wLan - wLan/Lan?
  if ($from_interface['interface_type'] == 'Wan') {
    /* client2ap link */
    if (!empty($from_interface['ipv4'])) {
      $gapi->addError(404, "radio already has a link: $from_radiodev_counter");
      return FALSE;
    }
    
    $ipv4 = _guifi_radio_add_link2ap($to_device['nid'], $to_device_id, $to_radiodev_counter, $parameters['ipv4'], -1);
    $gapi->addResponseField('ipv4', $ipv4);
    
    if ($ipv4 == -1) {
      $str = "radio is full or IPv4 parameters are wrong";
      if ($parameters['ipv4']) {
        $str .= " (ipv4: {$parameters['ipv4']})";
      }
      $gapi->addError(404, $str);
      return FALSE;
    }
    
    $ipv4['links'][-1]['flag'] = $parameters['flag'];
    
    $from_interface['ipv4'][] = $ipv4;
    
    guifi_device_save($from_device);
    
    $from_device = guifi_device_load($from_device['id']);
    $from_interface = array_pop($from_device['radios'][$from_radiodev_counter]['interfaces']);
    $link_id = array_pop(array_keys($from_interface['ipv4'][0]['links']));
    
    $ipv4_return = array();
    $ipv4_return['ipv4_type'] = $ipv4['ipv4_type'];
    $ipv4_return['ipv4'] = $ipv4['ipv4'];
    $ipv4_return['netmask'] = $ipv4['netmask'];
    
    $gapi->addResponseField('link_id', $link_id);
    $gapi->addResponseField('ipv4', $ipv4_return);
    
    return TRUE;
  } else if ($from_interface['interface_type'] == 'wds/p2p') {
    /* WDS link */
    $new_interface = array();
    $new_interface[$from_interface_id]['ipv4'][] = _guifi_radio_add_wds_get_new_interface($from_device['nid']);
    $new_link = &$new_interface[$from_interface_id]['ipv4'][0]['links'][0];
    $new_link['id'] = -1;
    $new_link['flag'] = $parameters['flag'];
    if (!empty($parameters['routing'])) {
      $new_link['routing'] = $parameters['routing'];
    }
    
    // getting remote interface
    $remote_interface = db_fetch_array(db_query("SELECT id FROM {guifi_interfaces} WHERE device_id = %d AND interface_type = 'wds/p2p' AND radiodev_counter = %d", $to_device['id'], $to_radiodev_counter));
    
    $new_link['nid'] = $to_device['nid'];
    $new_link['device_id'] = $to_device['id'];
    $new_link['interface']['id'] = $remote_interface['id'];
    $new_link['interface']['device_id'] = $to_device['id'];
    $new_link['interface']['radiodev_counter'] = $to_radiodev_counter;
    $new_link['interface']['ipv4']['interface_id'] = $remote_interface['id'];
    
    foreach ($new_interface[$from_interface_id]['ipv4'] as $newInterface) {
      $from_device['radios'][$from_radiodev_counter]['interfaces'][$from_interface_id]['ipv4'][] = $newInterface;
    }
    guifi_device_save($from_device);
    
    $from_device = guifi_device_load($from_device['id']);
    $from_interface = array_pop($from_device['radios'][$from_radiodev_counter]['interfaces']);
    $ipv4 = array_pop($from_interface['ipv4']);
    $link_id = array_pop(array_keys($ipv4['links']));
    
    $ipv4_return = array();
    $ipv4_return['ipv4_type'] = $ipv4['ipv4_type'];
    $ipv4_return['ipv4'] = $ipv4['ipv4'];
    $ipv4_return['netmask'] = $ipv4['netmask'];
    
    $gapi->addResponseField('link_id', $link_id);
    $gapi->addResponseField('ipv4', $ipv4_return);
    return TRUE;
  } else {
    $gapi->addError(404, "interface doesn't allow to create the link. from_interface_type = {$from_interface['interface_type']}");
    return FALSE;
  }
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function guifi_api_link_update($gapi, $parameters) {
  if (!guifi_api_check_fields($gapi, array('link_id' ), $parameters)) {
    return FALSE;
  }
  
  if (!_guifi_api_link_check_parameters($gapi, $parameters)) {
    return FALSE;
  }
  
  $link_id = $parameters['link_id'];
  
  $link_query = db_query('SELECT * FROM {guifi_links} WHERE id = %d', $link_id);
  
  if ($link = db_fetch_object($link_query)) {
    do {
      if (!$link->device_id) {
        $gapi->addError(500, "link not found: $link_id");
        return FALSE;
      }
      $device = guifi_device_load($link->device_id);
      if (!guifi_device_access('update', $device)) {
        $gapi->addError(501);
        return FALSE;
      }
      
      $interface = db_fetch_object(db_query('SELECT * FROM {guifi_interfaces} WHERE id = %d LIMIT 1', $link->interface_id));
      
      $lipv4 = &$device['radios'][$interface->radiodev_counter]['interfaces'][$link->interface_id]['ipv4'][$link->ipv4_id];
      $rlink = &$device['radios'][$interface->radiodev_counter]['interfaces'][$link->interface_id]['ipv4'][$link->ipv4_id]['links'][$link->id];
      
      if ($parameters['flag']) {
        $rlink['flag'] = $parameters['flag'];
      }
      
      if ($interface->interface_type == 'Wan') {
        if ($parameters['ipv4'] && $lipv4['ipv4'] != $parameters['ipv4']) {
          $ipv4 = $parameters['ipv4'];
          
          $ipv4_link_query = db_query('SELECT * FROM {guifi_links} WHERE id = %d AND interface_id != %d', $link_id, $link->interface_id);
          $to_link = db_fetch_object($ipv4_link_query);
          $to_interface = db_fetch_object(db_query('SELECT * FROM {guifi_interfaces} WHERE id = %d LIMIT 1', $to_link->interface_id));
          
          $ipv4_check = _guifi_radio_add_link2ap($to_link->nid, $to_link->device_id, $to_interface->radiodev_counter, $ipv4, -1);
          
          if ($ipv4_check == -1) {
            $str = "IPv4 parameters are wrong (ipv4: $ipv4)";
            $gapi->addError(404, $str);
            return FALSE;
          } else {
            $lipv4['ipv4'] = $ipv4;
          }
        }
      } else {
        if ($parameters['routing']) {
          $rlink['routing'] = $parameters['routing'];
        }
      }
      
      guifi_device_save($device);
    } while ($link = db_fetch_object($link_query));
  } else {
    $gapi->addError(500, "link not found: $link_id");
    return FALSE;
  }
  
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function guifi_api_link_remove($gapi, $parameters) {
  if (!guifi_api_check_fields($gapi, array('link_id' ), $parameters)) {
    return FALSE;
  }
  
  $link_id = $parameters['link_id'];
  
  $link_query = db_query('SELECT * FROM {guifi_links} WHERE id = %d', $link_id);
  
  if ($link = db_fetch_object($link_query)) {
    do {
      if (!$link->device_id) {
        $gapi->addError(500, "link not found: $link_id");
        return FALSE;
      }
      $device = guifi_device_load($link->device_id);
      if (!guifi_device_access('update', $device)) {
        $gapi->addError(501);
        return FALSE;
      }
      
      $interface = db_fetch_object(db_query('SELECT * FROM {guifi_interfaces} WHERE id = %d LIMIT 1', $link->interface_id));
      
      if ($interface->interface_type == 'Wan' || $interface->interface_type == 'wds/p2p') {
        $device['radios'][$interface->radiodev_counter]['interfaces'][$link->interface_id]['ipv4'][$link->ipv4_id]['deleted'] = TRUE;
        $device['radios'][$interface->radiodev_counter]['interfaces'][$link->interface_id]['ipv4'][$link->ipv4_id]['links'][$link->id]['deleted'] = TRUE;
      } else {
        $device['radios'][$interface->radiodev_counter]['interfaces'][$link->interface_id]['ipv4'][$link->ipv4_id]['links'][$link->id]['deleted'] = TRUE;
      }
      
      guifi_device_save($device);
    } while ($link = db_fetch_object($link_query));
  } else {
    $gapi->addError(500, "link not found: $link_id");
    return FALSE;
  }
  
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function _guifi_api_misc_model_check_parameters($gapi, &$parameters) {
  if (isset($parameters['type'])) {
    $types = array('Extern', 'PCMCIA', 'PCI' );
    if (!in_array($parameters['type'], $types)) {
      $gapi->addError(403, "type invalid: {$parameters['type']}");
      return FALSE;
    }
  }
  
  if (isset($parameters['fid'])) {
    $fid_query = db_query("SELECT * FROM {guifi_manufacturer} WHERE fid = %d", $parameters['fid']);
    $fid = db_fetch_object($fid_query);
    if (!$fid->fid) {
      $gapi->addError(403, "fid invalid: {$parameters['fid']}");
      return FALSE;
    }
  }
  
  if (isset($parameters['supported'])) {
    $values = array('Yes', 'Deprecated' );
    if (!in_array($parameters['supported'], $values)) {
      $gapi->addError(403, "supported invalid: {$parameters['supported']}");
      return FALSE;
    }
  }
  
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function guifi_api_misc_model($gapi, $parameters) {
  $sql = "SELECT mid, fid, model, type, supported FROM {guifi_model}";

  
  if (!_guifi_api_misc_model_check_parameters($gapi, $parameters)) {
    return FALSE;
  }
  
  $params = array();
  $conds = array();
  
  if ($parameters['type']) {
    $conds[] = "tipus LIKE '%s'";
    $params[] = $parameters['type'];
  }
  if ($parameters['fid']) {
    $conds[] = "fid = %d";
    $params[] = $parameters['fid'];
  }
  if ($parameters['supported']) {
    $conds[] = "supported LIKE '%s'";
    $params[] = $parameters['supported'];
  }
  
  if ($conds) {
    $sql .= " WHERE ";
    $sql .= implode(' AND ', $conds);
  }
  
  $query = db_query($sql, $params);
  while ($model = db_fetch_object($query)) {
    $models[] = $model;
  }
  $gapi->addResponseField('models', $models);
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function guifi_api_misc_manufacturer($gapi, $parameters) {
  $sql = "SELECT fid, name, url FROM {guifi_manufacturer} ORDER BY fid ASC";
  
  $manufacturers = array();
  $query = db_query($sql);
  while ($manufacturer = db_fetch_object($query)) {
    $manufacturers[] = $manufacturer;
  }
  $gapi->addResponseField('manufacturers', $manufacturers);
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function guifi_api_misc_firmware($gapi, $parameters) {
  $relation = '';
  if( !empty( $parameters['model_id'] ) ) {
    $query = db_query("SELECT model FROM {guifi_model} WHERE mid = %d", $parameters['model_id'] );
    $model = db_fetch_object($query);
    if($model->model) {
      $relation = $model->model;
    } else {
      $gapi->addError(403, "model not found: {$parameters['model_id']}");
      return FALSE;
    }
  }
  
  $types = guifi_types('firmware', NULL, NULL, $parameters['model_id']);
  
  $firmwares = array();
  
  foreach( $types as $type) {
    $firmwares[] = array('id' =>$type['fid'], 'title' => $type['name'], 'description' => $type['description'] );
  }

  $gapi->addResponseField('firmwares', $firmwares);
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function guifi_api_misc_protocol($gapi, $parameters) {
  $types = guifi_types('protocol');
  
  $protocols = array();
  
  foreach( $types as $type_title => $type_description ) {
    $protocols[] = array('title' => $type_title, 'description' => $type_description );
  }
  
  $gapi->addResponseField('protocols', $protocols);
  return TRUE;
}


/**
 *
 * @param GuifiAPI $gapi GuifiAPI object
 *
 * @param mixed[] $parameters
 *
 * @return
 */
function guifi_api_misc_channel($gapi, $parameters) {
  if (!guifi_api_check_fields($gapi, array('protocol' ), $parameters)) {
    return FALSE;
  }
  if( !guifi_validate_types('protocol', $parameters['protocol'])) {
    $gapi->addError(403, "protocol not found: {$parameters['protocol']}");
    return FALSE;
  }
  
  $types = guifi_types('channel', NULL, NULL, $parameters['protocol']);
  
  $channels = array();
  
  foreach( $types as $type_title => $type_description ) {
    $channels[] = array('title' => $type_title, 'description' => $type_description );
  }
  
  $gapi->addResponseField('channels', $channels);
  return TRUE;
}

/**
 * Check if a set of fields are present in the parameters array passed to the API
 *
 * @param GuifiAPI $gapi GuifiAPI object
 * @param string[] $required Array with required fieldnames
 * @param mixed[] $parameters Array of parameters to be checked
 *
 * @return
 */
function guifi_api_check_fields($gapi, $required, $parameters) {
  $success = TRUE;
  foreach ($required as $req) {
    if (!isset($parameters[$req])) {
      $gapi->addError(402, $req);
      $success = FALSE;
    }
  }
  return $success;
}

?>
