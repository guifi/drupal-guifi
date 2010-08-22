<?php
// The source code packaged with this file is Free Software, Copyright (C) 2010 by
// Eduard Duran <eduard.duran@iglu.cat>.
// It's licensed under the GENERAL PUBLIC LICENSE v2.0 unless stated otherwise.
// You can get copies of the licenses here:
// 		http://www.gnu.org/licenses/gpl-2.0.html
// GENERAL PUBLIC LICENSE v2.0 is also included in the file called "LICENSE.txt".

class GuifiAPI {
  public $command = '';
  
  public $area;
  public $operation;
  public $parameters = array();
  
  private $codes = array();
  
  private $errors = array();
  
  private $responses = array();
  
  private $_input_format = 'rest';
  private $_output_format = 'json';

  private $_response_code = 200;
  
  /**
   * If the API has initialized correctly, and is ready to parse requests
   * @var boolean
   */
  public $ready = false;
  
  function __construct() {
    $this->codes[200] = "Request completed successfully";
    $this->codes[201] = "Request could not be completed, errors found";
    
    $this->codes[400] = "Request is not well-formatted: input command is empty or invalid";
    $this->codes[401] = "Request is not valid: input command is not implemented";
    $this->codes[402] = "Request is not valid: some mandatory fields are missing";
    $this->codes[403] = "Request is not valid: some input data is incorrect";
    $this->codes[404] = "Request is not valid: operation is not allowed";
    
    $this->codes[500] = "Request could not be completed. The object was not found";
    $this->codes[501] = "You don't have the required permissions";
    $this->codes[502] = "The given Auth token is invalid";
    
    $headers = apache_request_headers();
    foreach ($headers as $key => $val) {
      if ($key == 'Authorization') {
        $authorized = $this->parseAuthorization($val);
        if (!$authorized) {
          $this->addError(502);
          $this->printResponse();
          return false;
        }
      }
    }
    $this->ready = true;
    return true;
  }
  
  private function parseAuthorization($auth) {
    if (eregi("^GuifiLogin", $auth)) {
      $auth = trim(substr($auth, 10));
      parse_str($auth, $auth);
      if (isset($auth['auth'])) {
        $this->auth_token = $auth['auth'];
        return $this->tokenLogin();
      }
    }
    
    return false;
  }
  
  private function tokenLogin() {
    global $user;
    
    if (empty($this->auth_token)) {
      return false;
    }
    
    $max_date = time() - 12 * 3600; // 12 hours since created
    

    db_query("DELETE FROM {guifi_api_tokens} WHERE created < FROM_UNIXTIME(%d)", $max_date);
    
    $dbtoken = db_fetch_object(db_query("SELECT * FROM {guifi_api_tokens} WHERE token = '%s'", $this->auth_token));
    
    if (!$dbtoken->uid) {
      return false;
    }
    
    $token = base64_decode($this->auth_token);
    $token = explode(':', $token);
    if (count($token) < 3) {
      return false;
    }
    
    $uid = $token[0];
    $hash = $token[1];
    $time = $token[2];
    
    if ($dbtoken->uid != $uid) {
      return false;
    }
    
    $account = user_load($uid);
    
    $check = md5($account->mail . $account->pass . $account->created . $account->uid . $time . $dbtoken->rand_key);
    
    if ($check == $hash) {
      $user = $account;
      return true;
    }
    
    return false;
  }
  
  function parseRequest($method = false) {
    if (!$method) {
      $method = $_POST;
    }
    unset($method['q']);
    
    $cmd = $method['command'];
    unset($method['command']);
    
    if (!empty($cmd)) {
      $this->command = $cmd;
    }
    
    $cmd = explode(".", $cmd);
    if (count($cmd) < 3 || $cmd[0] != 'guifi') {
      $this->addError(400);
      return false;
    }
    
    $this->setArea($cmd[1]);
    $this->setOperation($cmd[2]);
    
    foreach ($method as $key => $value) {
      $this->parameters[$key] = urldecode($value);
      unset($method[$key]);
    }
    
    return true;
  }
  
  function setArea($area) {
    $this->area = $area;
  }
  
  function setOperation($operation) {
    $this->operation = $operation;
  }
  
  function getErrors() {
    return $this->errors;
  }
  function addError($code, $extra = '') {
    $error = array('code' => $code, 'str' => $this->codes[$code] );
    if (!empty($extra)) {
      $error['extra'] = $extra;
    }
    $this->errors[] = $error;
    $this->_response_code = 201; // errors found
  }
  
  function executeRequest() {
    if (!$this->area || !$this->operation) {
      $this->addError(400);
      return false;
    }
    
    $func = "guifi_api_{$this->area}_{$this->operation}";
    if (!function_exists($func)) {
      $this->addError(401);
      return false;
    }
    
    call_user_func_array($func, array($this, $this->parameters ));
  }
  
  function printResponse() {
    $resp = array();
    
    $resp['command'] = $this->command;
    $resp['code'] = array('code' => $this->_response_code, 'str' => $this->codes[$this->_response_code] );
    
    $errors = $this->getErrors();
    if ($errors) {
      $resp['errors'] = $errors;
    } else { 
      if ($this->responses) {
        $resp['responses'] = $this->responses;
      }
    }
    
    switch ($this->_output_format) {
      case 'json':
        echo json_encode($resp);
        break;
    }
  }
  
  function addResponseField($key, $value) {
    $this->responses[$key] = $value;
  }
}

?>