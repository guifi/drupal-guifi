<?php
// The source code packaged with this file is Free Software, Copyright (C) 2010 by
// Eduard Duran <eduard.duran at iglu.cat>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
// 		http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "LICENSE.txt".

/**
 * Client class for the guifi.net API
 *
 */
class guifiAPI {
  /**
   * Which is the HTTP interface used by PHP to open HTTP connections (either curl, fopen or autodetection)
   * @var string
   */
  const http_interface = 'auto';
  
  /**
   * guifi.net API URL used with normal metods
   * @var string
   */
  private $url = 'http://test.guifi.net/api';
  
  /**
   * guifi.net API URL used to authenticate the user
   * @var string
   */
  private $auth_url = 'http://test.guifi.net/api/auth';
  
  /**
   * Whether the class is using the Development mode or not
   * @var boolean
   */
  const dev_mode = false;
  
  /**
   * What is the input format of the incoming responses from the API
   * @var string
   */
  const input_format = 'json';
  
  /**
   * What is the output format of the outcoming parameters to the API
   * @var string
   */
  const output_format = 'get';
  
  private $username = '';
  private $password = '';
  
  private $auth_token = null;
  private $errors = array();
  
  /**
   * Adds a zone to guifi.net
   * @param $title Title of the zone
   * @param $master Parent zone of the new zone
   * @param $miny Latitude coordinate, in decimal degrees, of the lower-left corner of the zone (SW)
   * @param $minx Longitude coordinate, in decimal degrees, of the lower-left corner of the zone (SW)
   * @param $maxy Latitude coordinate, in decimal degrees, of the upper-right corner of the zone (NE)
   * @param $maxx Longitude coordinate, in decimal degrees, of the upper-right corner of the zone (NE)
   * @param $parameters Extra parameters to create the zone
   * @return mixed An array with the zone_id, or false in case of failure
   */
  public function addZone($title, $master, $miny, $minx, $maxy, $maxx, $parameters = array()) {
    $variables = array();
    
    foreach ($parameters as $key => $value) {
      $variables[$key] = $value;
    }
    
    $variables['command'] = 'guifi.zone.add';
    $variables['title'] = $title;
    $variables['master'] = $master;
    $variables['minx'] = $minx;
    $variables['miny'] = $miny;
    $variables['maxx'] = $maxx;
    $variables['maxy'] = $maxy;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    
    if ($body !== false) {
      return $body->responses;
    } else {
      return false;
    }
  }
  
  /**
   * Updates a guifi zone
   * @param $zone_id Zone ID to edit
   * @param $parameters Parameters to edit
   * @return boolean Whether the zone was edited or not
   */
  public function updateZone($zone_id, $parameters) {
    $variables = array();
    
    foreach ($parameters as $key => $value) {
      $variables[$key] = $value;
    }
    
    $variables['command'] = 'guifi.zone.update';
    $variables['zone_id'] = $zone_id;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    return $body !== false;
  }
  
  /**
   * Removes a guifi zone
   * @param $zone_id ID of the zone which should be removed
   * @return boolean Whether the zone was removed or not
   */
  public function removeZone($zone_id) {
    $variables = array();
    $variables['command'] = 'guifi.zone.remove';
    $variables['zone_id'] = $zone_id;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    return $body !== false;
  }
  
  /**
   * Gets the zone which can contain a certain point
   * @param $lat Latitude of the point
   * @param $lon Longitude of the point
   * @return mixed Nearest zones which can contain a certain point
   */
  public function nearestZone($lat, $lon) {
    $variables = array();
    $variables['command'] = 'guifi.zone.nearest';
    $variables['lat'] = $lat;
    $variables['lon'] = $lon;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    
    if ($body !== false) {
      return $body->responses;
    } else {
      return false;
    }
  }
  
  /**
   * Adds a new guifi.net node
   * @param $title Title of the node
   * @param $zone_id Zone ID of the node
   * @param $lat Latitude where the node is
   * @param $lon Longitude where the node is
   * @param $parameters Parameters to specify node settings
   * @return mixed Information of the newly created node (node_id)
   */
  public function addNode($title, $zone_id, $lat, $lon, $parameters = array()) {
    $variables = array();
    
    foreach ($parameters as $key => $value) {
      $variables[$key] = $value;
    }
    
    $variables['command'] = 'guifi.node.add';
    $variables['title'] = $title;
    $variables['zone_id'] = $zone_id;
    $variables['lat'] = $lat;
    $variables['lon'] = $lon;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    
    if ($body !== false) {
      return $body->responses;
    } else {
      return false;
    }
  }
  
  /**
   * Updates a guifi node
   * @param $node_id Node ID to edit
   * @param $parameters Parameters to edit
   * @return boolean Whether the node was edited or not
   */
  public function updateNode($node_id, $parameters) {
    $variables = array();
    
    foreach ($parameters as $key => $value) {
      $variables[$key] = $value;
    }
    
    $variables['command'] = 'guifi.node.update';
    $variables['node_id'] = $node_id;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    return $body !== false;
  }
  
  /**
   * Removes a guifi node
   * @param $zone_id ID of the node which should be removed
   * @return boolean Whether the node was removed or not
   */
  public function removeNode($node_id) {
    $variables = array();
    $variables['command'] = 'guifi.node.remove';
    $variables['node_id'] = $node_id;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    return $body !== false;
  }
  
  /**
   * Adds a guifi device to a node
   * @param $node_id ID of the node where the device should be added
   * @param $type Type of device which should be added (radio, mobile, server, nat, generic, adsl, cam, phone)
   * @param $parameters Other parameters depending on the type of device, such as model_id, MAC address or firmware
   * @return mixed The response with the newly created device_id or false in case of error 
   */
  public function addDevice($node_id, $type, $mac, $parameters = array()) {
    $variables = array();
    
    foreach ($parameters as $key => $value) {
      $variables[$key] = $value;
    }
    
    $variables['command'] = 'guifi.device.add';
    $variables['node_id'] = $node_id;
    $variables['type'] = $type;
    $variables['mac'] = $mac;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    if ($body !== false) {
      return $body->responses;
    } else {
      return false;
    }
  }
  
  /**
   * Updates a guifi device
   * @param $device_id Device ID to edit
   * @param $parameters Parameters to edit
   * @return boolean Whether the device was edited or not
   */
  public function updateDevice($device_id, $parameters) {
    $variables = array();
    
    foreach ($parameters as $key => $value) {
      $variables[$key] = $value;
    }
    
    $variables['command'] = 'guifi.device.update';
    $variables['device_id'] = $device_id;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    return $body !== false;
  }
  
  /**
   * Removes a guifi device from a node
   * @param $device_id ID of the device which should be removed
   * @return boolean Whether the device was removed or not
   */
  public function removeDevice($device_id) {
    $variables = array();
    $variables['command'] = 'guifi.device.remove';
    $variables['device_id'] = $device_id;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    return $body !== false;
  }
  
  /**
   * Adds a guifi Radio to a device
   * @param $mode Mode of the radio to be added
   * @param $device_id Device where the radio should be added
   * @param $mac MAC address of the radio
   * @return mixed Information about the added radio, such as radiodev_counter
   */
  public function addRadio($mode, $device_id, $mac = '', $parameters = array()) {
    $variables = array();
    
    foreach ($parameters as $key => $value) {
      $variables[$key] = $value;
    }
    
    $variables['command'] = 'guifi.radio.add';
    $variables['mode'] = $mode;
    $variables['device_id'] = $device_id;
    $variables['mac'] = $mac;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    if ($body !== false) {
      return $body->responses;
    } else {
      return false;
    }
  }
  
  /**
   * Updates a guifi radio of a device
   * @param $device_id Device ID of the radio to be updated
   * @param $radiodev_counter Position within the device where the radio is location
   * @return boolean Whether the radio was updated or not
   */
  public function updateRadio($device_id, $radiodev_counter, $parameters) {
    $variables = array();
    
    foreach ($parameters as $key => $value) {
      $variables[$key] = $value;
    }
    
    $variables['command'] = 'guifi.radio.update';
    $variables['device_id'] = $device_id;
    $variables['radiodev_counter'] = $radiodev_counter;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    return $body !== false;
  }
  
  /**
   * Removes a guifi radio from a device
   * @param $device_id ID of the device where the radio to be removed is
   * @param $radiodev_counter Position within the device where the radio is
   * @return boolean Whether the radio was removed or not
   */
  public function removeRadio($device_id, $radiodev_counter) {
    $variables = array();
    $variables['command'] = 'guifi.radio.remove';
    $variables['device_id'] = $device_id;
    $variables['radiodev_counter'] = $radiodev_counter;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    return $body !== false;
  }
  
  /**
   * Searches the nearest radios from a given node
   * @param $node_id Node where to find the nearest radios
   * @param $parameters Parameters such as maximum or minimum distance 
   * @return mixed Nearest radios from a given node
   */
  public function nearestRadio($node_id, $parameters = array()) {
    $variables = array();
    
    foreach ($parameters as $key => $value) {
      $variables[$key] = $value;
    }
    
    $variables['command'] = 'guifi.radio.nearest';
    $variables['node_id'] = $node_id;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    
    if ($body !== false) {
      return $body->responses;
    } else {
      return false;
    }
  }
  
  /**
   * Adds a wLan interface to a radio to accept more clients
   * @param $device_id Device where the interface should be added
   * @param $radiodev_counter Position of the radio within the device where the interface should be added
   * @return mixed Information about the newly created interface, such as interface_id
   */
  public function addInterface($device_id, $radiodev_counter) {
    $variables = array();
    $variables['command'] = 'guifi.interface.add';
    $variables['device_id'] = $device_id;
    $variables['radiodev_counter'] = $radiodev_counter;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    if ($body !== false) {
      return $body->responses;
    } else {
      return false;
    }
  }
  
  /**
   * Removes a guifi interface from a radio
   * @param $interface_id ID of the interface to be removed
   * @return boolean Whether the interface was removed or not
   */
  public function removeInterface($interface_id) {
    $variables = array();
    $variables['command'] = 'guifi.interface.remove';
    $variables['interface_id'] = $interface_id;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    return $body !== false;
  }
  
  /**
   * Adds a link to an guifi.net interface
   * @param $from_device_id Device ID of the origin of the link
   * @param $from_radiodev_counter Position of the radio within its device of the origin of the link 
   * @param $to_device_id Device ID of the other extreme of the link
   * @param $to_radiodev_counter Position of the radio within its device of the other extreme of the link
   * @param $parameters Other parameters of the link to be added
   * @return mixed Information about the newly created link, such as link_id
   */
  public function addLink($from_device_id, $from_radiodev_counter, $to_device_id, $to_radiodev_counter, $parameters = array()) {
    $variables = array();
    
    foreach ($parameters as $key => $value) {
      $variables[$key] = $value;
    }
    
    $variables['command'] = 'guifi.link.add';
    $variables['from_device_id'] = $from_device_id;
    $variables['from_radiodev_counter'] = $from_radiodev_counter;
    $variables['to_device_id'] = $to_device_id;
    $variables['to_radiodev_counter'] = $to_radiodev_counter;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    if ($body !== false) {
      return $body->responses;
    } else {
      return false;
    }
  }
  
  /**
   * Updates a guifi link
   * @param $link_id Link ID to be updated
   * @param $parameters Parameters of the link to be updated
   * @return boolean Whether the link was updated or not
   */
  public function updateLink($link_id, $parameters) {
    $variables = array();
    
    foreach ($parameters as $key => $value) {
      $variables[$key] = $value;
    }
    
    $variables['command'] = 'guifi.link.update';
    $variables['link_id'] = $link_id;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    return $body !== false;
  }
  
  /**
   * Removes a link from guifi.net
   * @param $link_id Link ID to be removed
   * @return boolean Whether the link was removed or not
   */
  public function removeLink($link_id) {
    $variables = array();
    $variables['command'] = 'guifi.link.remove';
    $variables['link_id'] = $link_id;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    return $body !== false;
  }
  
  /**
   * Gets a list of devices models
   * @param $parameters string[] of possible parameters to retrieve filtered models
   * @return string[] Models retrieved from the server
   */
  public function getModels($parameters = array()) {
    $variables = array();
    
    foreach ($parameters as $key => $value) {
      $variables[$key] = $value;
    }
    
    $variables['command'] = 'guifi.misc.model';
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    if (!empty($body->responses->models)) {
      return $body->responses->models;
    } else {
      return false;
    }
  }
  
  /**
   * Gets a list of device manufacturers
   * @return string[] Manufacturers retrieved from the server
   */
  public function getManufacturers() {
    $variables = array();
    $variables['command'] = 'guifi.misc.manufacturer';
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    if (!empty($body->responses->manufacturers)) {
      return $body->responses->manufacturers;
    } else {
      return false;
    }
  }
  
  /**
   * Gets a list of supported firmwares to be used with devices
   * @param $parameters Firmware filters to be applied
   * @return string[] Firmwares retrieved from the server
   */
  public function getFirmwares($parameters = array()) {
    $variables = array();
    
    foreach ($parameters as $key => $value) {
      $variables[$key] = $value;
    }
    
    $variables['command'] = 'guifi.misc.firmware';
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    if (!empty($body->responses->firmwares)) {
      return $body->responses->firmwares;
    } else {
      return false;
    }
  }
  
  /**
   * Gets a list of supported protocols to be used with links
   * @return string[] Protocols retrieved from the server
   */
  public function getProtocols() {
    $variables = array();
    $variables['command'] = 'guifi.misc.protocol';
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    if (!empty($body->responses->protocols)) {
      return $body->responses->protocols;
    } else {
      return false;
    }
  }
  
  /**
   * Gets a list of channels to be used with links
   * @param $protocol Protocol the channels apply to
   * @return string[] Channels retrieved from the server
   */
  public function getChannels($protocol) {
    $variables = array();
    $variables['command'] = 'guifi.misc.channel';
    $variables['protocol'] = $protocol;
    
    $response = $this->sendRequest($this->url, $variables);
    $body = $this->parseResponse($response);
    if (!empty($body->responses->channels)) {
      return $body->responses->channels;
    } else {
      return false;
    }
  }
  
  /**
   * Constructor function for all new guifiAPI instances
   * 
   * Set up authentication with guifi and gets authentication token
   *
   * @param String $username Username of the guifi.net account wanted to authenticate
   * @param String $password Password of the guifi.net account wanted to authenticate
   * @param String $token If any token is given, no need to send the username and password to the server
   */
  public function __construct($username, $password, $token = null) {
    $this->username = $username;
    $this->password = $password;
    if (!empty($token)) {
      $this->auth_token = $token;
    } else {
      $this->authenticateUser($username, $password);
    }
  }
  
  /**
   * Authenticate guifi.net account against guifi.net
   *
   * @param string $email
   * @param string $password
   * @return boolean Whether the authentication was successful or not
   */
  protected function authenticateUser($username, $password) {
    $variables = array('command' => 'guifi.auth.login', 'username' => $username, 'password' => $password, 'method' => 'password' );
    
    $response = $this->sendRequest($this->auth_url, $variables);
    
    // Parses the response from the guifi.net API
    $body = $this->parseResponse($response);
    if ($body !== false) {
      $responses = $body->responses;
      if (!empty($responses->authToken)) {
        $this->auth_token = $responses->authToken;
        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }
  
  /**
   * Retreives the authentication token used to authenticate the user in upcoming methods without sending the username and password each time
   * @return string Authentication token
   */
  public function getAuthToken() {
    return $this->auth_token;
  }
  
  /**
   * Generates the authentication header to authenticate using a token against guifi.net
   * @return mixed Header of authentication
   */
  protected function generateAuthHeader() {
    if ($this->auth_token) {
      return array('Authorization: GuifiLogin auth=' . $this->auth_token );
    } else {
      return array();
    }
  }
  
  /**
   * Performs the request to the guifi.net API server
   * @param $url URL to send the request to
   * @param $variables Variables to be formatted to be sent to the server
   * @return mixed response from the API server
   */
  protected function sendRequest($url, $variables) {
    $this->pendingUrl = $url;
    $this->pendingVariables = $variables;
    
    switch (guifiAPI::output_format) {
      case 'get':
        $get_variables = $variables;
        $post_variables = array();
        break;
      case 'post':
        $get_variables = array();
        $post_variables = $variables;
        break;
    }
    
    $response = $this->httpRequest($url, $get_variables, $post_variables, $this->generateAuthHeader());
    return $response;
  }
  
  /**
   * Parses a response from the server, according ti the input format
   * @param $response Response string to be parsed
   * @return mixed Returns the body of the response in case of success, false in case of failure
   */
  protected function parseResponse($response) {
    $code = $response['code'];
    
    switch (guifiAPI::input_format) {
      case 'json':
        $body = json_decode($response['body']);
        break;
      case 'url':
        parse_str(str_replace(array("\n", "\r\n" ), '&', $response['body']), $body);
        break;
    }
    
    if (substr($code, 0, 1) != '2' || !is_object($body)) {
      throw new Exception('guifiAPI: Failed to parse response. Error: "' . strip_tags($response['body']) . '"');
    }
    
    if (!empty($body->errors)) {
      if ($body->errors[0]->code == 502) {
        unset($this->auth_token);
        $pendingUrl = $this->pendingUrl;
        $pendingVariables = $this->pendingVariables;
        $authenticated = $this->authenticateUser($this->username, $this->password);
        
        if ($authenticated) {
          $response = $this->sendRequest($pendingUrl, $pendingVariables);
          return $this->parseResponse($response);
        }
      }
      $this->errors = $body->errors;
      
      return false;
    }
    
    if (empty($body->code) || substr($body->code->code, 0, 1) != '2') {
      return false;
    }
    
    $this->responseCode = $body->code;
    if (isset($body->responses)) {
      $this->responses = $body->responses;
    }
    
    return $body;
  }
  
  /**
   * Retreives the possible errors commited during a method
   * @return string[]
   */
  public function getErrors() {
    return $this->errors;
  }
  
  /**
   * Retreives a list of the errors parsed as a string
   *
   * @param string $format Format of the list, either 'html' or 'plain'
   * @return string List of formatted errors
   */
  public function getErrorsStr($format = 'html') {
    $ret = '';
    if (!$this->errors) {
      return $ret;
    }
    if ($format == 'html') {
      $ret .= '<ul>';
    }
    foreach ($this->errors as $error) {
      if ($format == 'html') {
        $ret .= '<li>';
      }
      $ret .= "Code $error->code: $error->str";
      if (isset($error->extra)) {
        $ret .= " (Extra: $error->extra)";
      }
      if ($format == 'html') {
        $ret .= '</li>';
      } else if ($format == 'plain') {
        $ret .= "\n";
      }
    }
    if ($format == 'html') {
      $ret .= '</ul>';
    }
    return $ret;
  }
  
  /**
   * Perform HTTP request
   *
   * @param array $get_variables
   * @param array $post_variables
   * @param array $headers
   */
  protected function httpRequest($url, $get_variables = null, $post_variables = null, $headers = null) {
    $interface = guifiAPI::http_interface;
    
    if (guifiAPI::http_interface == 'auto') {
      if (function_exists('curl_exec')) {
        $interface = 'curl';
      } else {
        $interface = 'fopen';
      }
    }
    
    if ($interface == 'curl') {
      return $this->curlRequest($url, $get_variables, $post_variables, $headers);
    } elseif ($interface == 'fopen') {
      return $this->fopenRequest($url, $get_variables, $post_variables, $headers);
    } else {
      throw new Exception('Invalid http interface defined. No such interface "' . GA_api::http_interface . '"');
    }
  }
  /**
   * HTTP request using PHP CURL functions
   * Requires curl library installed and configured for PHP
   * 
   * @param array $get_variables
   * @param array $post_variables
   * @param array $headers
   */
  private function curlRequest($url, $get_variables = null, $post_variables = null, $headers = null) {
    $ch = curl_init();
    
    if (is_array($get_variables)) {
      $get_variables = '?' . str_replace('&amp;', '&', http_build_query($get_variables));
    } else {
      $get_variables = null;
    }
    
    curl_setopt($ch, CURLOPT_URL, $url . $get_variables);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if (is_array($post_variables)) {
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_variables);
    }
    
    if (is_array($headers)) {
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    
    curl_close($ch);
    
    return array('body' => $body, 'headers' => $headers, 'code' => $code );
  }
  
  /**
   * Switches to test mode
   * @param $test Whether to switch to test mode or not
   */
  public function testMode($test = true) {
    if ($test == true) {
      $this->url = 'http://test.guifi.net/api';
      $this->auth_url = 'http://test.guifi.net/api/auth';
    } else {
      $this->url = 'http://guifi.net/api';
      $this->auth_url = 'http://guifi.net/api/auth';
    }
  }
}

?>