<?php

function guifi_user_access($op, $id) {
  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_user_access()',$id);
  guifi_log(GUIFILOG_FULL,'user=',$user);

  if ($user->uid==0)
    return FALSE;

  if (empty($id) || ($id < 1))
   return FALSE;

  if (is_array($id))
    $guifi_user = $id;
  else
    $guifi_user = guifi_user_load($id);

  $node = node_load(array('nid' => $guifi_user['nid']));

  switch($op) {
    case 'create':
      return user_access("create guifi nodes");
    case 'update':
      if ((user_access('administer guifi networks')) ||
        (user_access('administer guifi zones')) ||
        (user_access('administer guifi users')) ||
        ($guifi_user['user_created'] == $user->uid) ||
        ($node->user_created == $user->uid))
        return TRUE;
      return FALSE;
    case 'administer':
      if ((user_access('administer guifi networks')) ||
        (user_access('administer guifi zones')) ||
        (user_access('administer guifi users')))
        return TRUE;
      return FALSE;
    }
}


/**
 * user editing functions
**/

/**
 * Menu callback; handle the adding of a new user.
 */
function guifi_user_add($node) {
  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_user_edit()',$node);

  $guser['services']['proxy'] = guifi_zone_get_service($node->zone_id,'proxy_id');
  $guser['nid'] = $node->id;
  $guser['notification']=$node->notification;
  $guser['status']='New';
  $guser['content_filters']=array();

  return drupal_get_form('guifi_user_form',$guser);
}

/**
 * Menu callback; delete a single user.
 */
 function guifi_user_delete($id) {

  $guifi_user = guifi_user_load($id);
  guifi_log(GUIFILOG_TRACE,'guifi_delete_user()',$guifi_user);

  return drupal_get_form(
    'guifi_user_delete_confirm',
    $guifi_user['username'],
    $guifi_user['nid'],
    $guifi_user['id']);
}


function guifi_user_delete_confirm($form_state,$username,$nid,$id) {
  guifi_log(GUIFILOG_TRACE,'guifi_delete_user_confirm()',$username.'-'.$nid);

  $form['id'] = array('#type' => 'hidden', '#value' => $id);

  return confirm_form(
    $form,
    t('Are you sure you want to delete the user %username?',
      array('%username' => $username)),
    'node/'.$nid.'/view/users',
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel'));
}

function guifi_user_delete_confirm_submit($form, &$form_state) {
//  global $user;

  guifi_log(GUIFILOG_TRACE,'guifi_delete_user_confirm_submit()',
    $form_state['values']);

  if ($form_state['values']['op'] != t('Delete'))
    return;

  $guifi_user = guifi_user_load($form_state['values']['id']);
  $node = guifi_node_load($guifi_user['nid']);
  $to_mail = array();

  $subject = t('User %username deleted by %user.',
    array('%username' => $guifi_user['username'],
          '%user' => $user->name));
  $log .= '<br />'._guifi_db_delete(
    'guifi_users',
     array('id' => $guifi_user['id']),
     $to_mail);
  guifi_notify($to_mail,
      $subject,
      $log);
  drupal_goto('node/'.$guifi_user['nid'].'/view/users');
}

/**
 * Menu callback; dispatch to the appropriate user edit function.
 */
function guifi_user_edit($id = 0) {

  guifi_log(GUIFILOG_TRACE,'function guifi_user_edit()',$id);

  $output = drupal_get_form('guifi_user_form',$id);

  print theme('page',$output, FALSE);
  return;
}

function guifi_user_reset_password($edit) {
  global $user;

  if (is_numeric($edit))
    $edit = guifi_user_load($edit);
  else
    $edit = guifi_user_load($edit['id']);

  if (empty($edit['notification'])) {
    form_set_error('notification', t('Don\'t know where to email a new password. ' .
        'You need to have an email properly filled to get a new password. ' .
        'You should contact network administrators ' .
        'for getting a new password.'));
    return;
  }

  $edit['pass'] = user_password();

  $params['account']=$user;
  $params['username']=$edit['username'];
  $params['pass']=$edit['pass'];
  $mail_success = drupal_mail(
    'guifi_user_password',
    'reset',
    $edit['notification'],
    user_preferred_language($user),
    $params);

    if ($mail_success) {
      watchdog('user',
        'Password mailed to %name for %email.',
        array('%name' => $edit['notification'], '%email' => $edit['username']));
      drupal_set_message(t('Your password and further instructions ' .
          'have been sent to your e-mail address.'));
      $edit['password'] = crypt($edit['pass']);
      guifi_user_save($edit);
    }
    else {
      watchdog('user',
        'Error mailing password to %name at %email.',
        array('%name' => $edit['username'], '%email' => $edit['notification']),
        WATCHDOG_ERROR);
      drupal_set_message(t('Unable to send mail to %email. ' .
          'Please contact the site admin.',
          array('%email' => $edit['notification'])));
    }
  drupal_goto('node/'.$edit['nid'].'/view/users');
}

function guifi_user_password_mail($key, &$message, $params) {
  $language = $message['language'];
  $variables = user_mail_tokens($params['account'], $language);
  switch($key) {
    case 'reset':
      $message['subject'] = t('New password for user !username at guifi.net',
        array('!username' => $params['username']),
        $language->language);
      $message['body'] = t(
          "!loggeduser has requested to change the password for the account " .
          "!username, and has been set to:\n\t !pass",
        array('!username' => $params['username'],
          '!pass' => $params['pass'],
          '!loggeduser' => $params['account']->name),
        $language->language);
      break;
  }
}

/**
 * Get user information
**/
function guifi_user_load($id) {

  $item = db_fetch_array(db_query('SELECT * FROM {guifi_users} WHERE id = %d', $id));
  $item['services'] = unserialize($item['services']);
  $item['vars'] = unserialize($item['extra']);
  if (!empty($item['content_filters']))
    $item['content_filters'] = unserialize($item['content_filters']);
  $user = user_load(array('uid' => $item['user_created']));
  $item['username_created'] = $user->name;

  return $item;
}


function guifi_user_form($form_state, $params = array()) {
  _user_password_dynamic_validation();

  guifi_log(GUIFILOG_TRACE,'function guifi_user_form()',$form_state);

  guifi_validate_js("#guifi-user-form");

  if (empty($form_state['values'])) {
    if (is_numeric($params))
      $form_state['values'] = guifi_user_load($params);
    else
      $form_state['values'] = $params;
  }

  if (isset($form_state['values']['id'])) {
    $f['id'] = array('#type' => 'hidden','#value' => $form_state['values']['id']);
    drupal_set_title(t('edit user').' '.$form_state['values']['username']);
  } else {
    $f['new'] = array('#type' => 'hidden','#value' => TRUE);
    drupal_set_title(t('add user').' @ '.guifi_get_nodename($form_state['values']['nid']));
  }

  $f['firstname'] = array(
    '#type' => 'textfield',
    '#size' => 60,
    '#maxlength' => 128,
    '#title' => t('Firstname'),
    '#required' => TRUE,
    '#attributes' => array('class' => 'required'),
    '#default_value' => $form_state['values']['firstname'],
    '#description' => t('The real user name (Firstname), ' .
        'will be used while building the username.<br />' .
        'If username results duplicated, add more words ' .
        '(i.e. middle initial).<br />' .
        'Please enter real data, if fake information is entered, ' .
        'administrators might <strong>remove</strong> this user')
  );
  $f['lastname'] = array(
    '#type' => 'textfield',
    '#size' => 60,
    '#maxlength' => 128,
    '#title' => t('Lastname'),
    '#required' => TRUE,
    '#attributes' => array('class' => 'required'),
    '#default_value' => $form_state['values']['lastname'],
    '#description' => t('The real user name (Lastname).')
  );
  if (!empty($form_state['values']['username']))
    $f['username'] = array(
      '#type' => 'item',
      '#value' => $form_state['values']['username'],
      '#description' => t('The resulting username.')
    );

  if ((user_access('administer guifi users')) or
      (user_access('manage guifi users'))) {
    $f['status'] = array(
      '#type' => 'select',
      '#title' => t('Status'),
      '#options' => guifi_types('user_status'),
      '#default_value' => $form_state['values']['status']
    );
    $f['node'] = array(
      '#type' => 'textfield',
      '#title' => t('Node'),
      '#maxlength' => 60,
      '#default_value' => $form_state['values']['nid'].'-'.
        guifi_get_zone_nick(guifi_get_zone_of_node(
        $form_state['values']['nid'])).', '.
        guifi_get_nodename($form_state['values']['nid']),
        '#autocomplete_path'=> 'guifi/js/select-node',
        '#element_validate' => array('guifi_nodename_validate'),
        '#description' => t('Select the node where the user is.<br />' .
          'You can find the node by introducing part of the node id number, ' .
          'zone name or node name. A list with all matching values ' .
          'with a maximum of 50 values will be created.<br />' .
        'You can refine the text to find your choice.')
    );
  } else {
    $f['status'] = array(
      '#type' => 'item',
      '#title' => t('Status'),
      '#value' => $form_state['values']['status']
    );
    $f['node'] = array (
      '#type' => 'item',
      '#title' => t('Node'),
      '#value' => $form_state['values']['nid'].'-'.
        guifi_get_zone_nick(guifi_get_zone_of_node(
        $form_state['values']['nid'])).', '.
        guifi_get_nodename($form_state['values']['nid']),
    );
    if (!isset($f['new']))
      $f['previous_pwd'] = array(
        '#type' => 'password',
        '#title' => t('Current password'),
        '#description' => t('To proceed for any change, you have to ' .
          'know the current password.')
      );
    if (!isset($f['new']))
      $f['resetPwd'] = array (
        '#type' => 'submit',
        '#value' => t('Reset password')
      );
  }

  $f['nid'] = array(
    '#type' => 'hidden',
    '#value'=> $form_state['values']['nid'],
  );

  $f['pass'] = array(
    '#type' => 'password_confirm',
    '#required' => isset($f['new']),
    '#title' => t('Set a new password'),
    '#description' => t('To change/set the current user password, enter the new password in both fields.'),
    '#size' => 25,
  );
  $f['notification'] = array(
    '#type' => 'textfield',
    '#size' => 60,
    '#maxlength' => 1024,
    '#title' => t('contact'),
    '#required' => TRUE,
    '#element_validate' => array('guifi_emails_validate'),
    '#default_value' => $form_state['values']['notification'],
    '#description' =>  t('Mailid where changes on this user will be notified, ' .
        'if many, separated by \',\'<br />' .
        'Also where the user can be contacted.')
  );

  // services
  $f['services'] = array(
   '#type' => 'fieldset',
   '#title' => t('services'),
   '#collapsible' => TRUE,
   '#collapsed' => FALSE,
   '#tree' => TRUE,
  );

  if ((user_access('administer guifi users'))
      or (user_access('manage guifi users'))) {

    $f['services']['proxystr'] = array(
      '#type' => 'textfield',
      '#title' => t('proxy'),
      '#maxlength' => 60,
      '#default_value'=> guifi_service_str($form_state['values']['services']['proxy']),
      '#autocomplete_path'=> 'guifi/js/select-service/proxy',
      '#element_validate' => array('guifi_service_name_validate',
        'guifi_user_proxy_validate'),
      // '#description' => _service_descr('proxy')
    );
  } else {
    $f['services']['proxystr'] = array(
      '#type' => 'item',
      '#title' => t('proxy'),
      '#value' => guifi_service_str($form_state['values']['services']['proxy'])
    );
  }

  $f['services']['proxy'] = array(
    '#type' => 'hidden',
    '#value'=> $form_state['values']['services']['proxy'],
  );

  $f['services']['filters'] = array(
    '#type' => 'checkboxes',
    '#parents' => array('content_filters'),
    '#title' => t('content filters'),
    '#options'=> guifi_types('filter'),
    '#multiple' => TRUE,
//    '#default_value'=> $form_state['values']['content_filters'],
    '#description' => t('Content to be filtered.<br />Check the type of content ' .
        'which will be filtered to this user. ' .
        'Note that this filters will work only on those sites ' .
        'which have enabled this feature, ' .
        'so don\'t think that is safe to rely on this.')
  );
  if (!empty($form_state['values']['content_filters']))
    $f['services']['filters']['#default_value'] =
      $form_state['values']['content_filters'];

  $f['author'] = array(
    '#type' => 'fieldset',
    '#access' => user_access('administer guifi users'),
    '#title' => t('Authoring information'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $f['author']['username_created'] = array(
    '#type' => 'textfield',
    '#title' => t('Authored by'),
    '#maxlength' => 60,
    '#autocomplete_path' => 'user/autocomplete',
    '#default_value' => $form_state['values']['username_created'],
    '#description' => t('Leave blank for %anonymous.', array('%anonymous' => variable_get('anonymous', t('Anonymous')))),
  );


  $f['submit'] = array (
    '#type' => 'submit',
    '#value' => t('Save')
  );
  if (!isset($f['new']))
    $f['delete'] = array (
      '#type' => 'submit',
      '#value' => t('Delete')
    );


  return $f;
}


function guifi_user_proxy_validate($element, &$form_state) {
  $s = &$form_state['values']['services']['proxy'];
  switch ($element['#value']) {
  case t('No service'):
    $s = '-1';
    break;
  case t('Take from parents'):
    $n = node_load($form_state['values']['nid']);
    $s = guifi_zone_get_service($n->zone_id,'proxy_id');
    break;
  default:
    $nid = explode('-',$element['#value']);
    $s = $nid[0];
  }
}

/**
 * Confirm that an edited user fields properly filled in.
 */

function _guifi_user_queue_device_form_submit($form, $form_state) {
    guifi_log(GUIFILOG_TRACE,'function guifi_user_queue_device_form_submit()',$form_state['clicked_button']['#post']);

    switch ($form_state['clicked_button']['#name']) {
    case 'approve':
      $edit = &$form_state['clicked_button']['#post'];
      $d = guifi_device_load($edit['did']);
      $d['flag'] = 'Working';
      $d['radios'][0]['mac'] =  $edit['mac'];
      $d['radios'][0]['interfaces'][$edit['iid']]['mac'] =  $edit['mac'];
      $d['radios'][0]['interfaces'][$edit['iid']]['ipv4'][0]['links'][$edit['lid']]['flag'] =
        'Working';
      guifi_device_save($d);
      $n = guifi_node_load($form_state['clicked_button']['#post']['nid']);
      $form_state['values']['status'] = 'Approved';
    case 'saveUser':
      $u = guifi_user_load($form_state['clicked_button']['#post']['uid']);
      $u['status'] = $form_state['values']['status'];
      guifi_user_save($u);
      break;
    }
    return;
}

function guifi_user_form_validate($form, &$form_state) {

  guifi_log(GUIFILOG_TRACE,'function guifi_user_form_validate()',$form_state);

  $edit = &$form_state['values'];

  if (isset($edit['username_created'])) {
    if (user_load(array('name' => $edit['username_created']))==FALSE)
        form_set_error('username_created',t('Invalid user name.'));
  }

  if ((isset($edit['id'])) and (isset($edit['previous_pwd']))) {
    if ($form_state['clicked_button']['#value'] != t('Reset password')) {
      if (empty($edit['previous_pwd']))
        form_set_error('previous_pwd',
          t('You need to specify the current password to submit any change'));
      $prevUser = guifi_user_load($edit['id']);
      if ((crypt($edit['previous_pwd'],$prevUser['password']) != $prevUser['password'])) {
        form_set_error('previous_pwd',t('Unable to submit changes: Password failure.'));
      }
    }
  }

  if (empty($edit['firstname'])) {
   form_set_error('firstname', t('Firstname field cannot be blank .'));
  }
  if (empty($edit['lastname'])) {
   form_set_error('lastname', t('Lastname field cannot be blank .'));
  }

  $edit['firstname']=trim($edit['firstname']);
  $edit['lastname']=trim($edit['lastname']);
  $edit['username']=str_replace(" ",".",strtolower(guifi_to_7bits($edit['firstname']).'.'.guifi_to_7bits($edit['lastname'])));
//  $edit['username'] = str_replace(" ",".",strtolower(guifi_to_7bits($edit['firstname'].'.'.$edit['lastname'])));

  if (!empty($edit['username'])) {
    if (isset($edit['id']))
      $query = db_query(
        "SELECT username, services " .
        "FROM {guifi_users} " .
        "WHERE username ='%s' " .
        " AND id <> %d",
        $edit['username'], $edit['id']);
    else
      $query = db_query(
        "SELECT username, nid, services " .
        "FROM {guifi_users} " .
        "WHERE username ='%s'",
        $edit['username']);

    while ($proxy_id = db_fetch_object($query)) {
      $services = unserialize($proxy_id->services);
      $qry2 = db_query(
        "SELECT nick " .
        "FROM {guifi_services} " .
        "WHERE id = %d",
        $services['proxy']);
      $proxy_name = db_fetch_object($qry2);

      form_set_error('username', t('The user %username is already defined ' .
        'at the node %nodename ' .
        'for service %servicename. Use middle initial, 2nd lastname or a prefix ' .
        'with the proxy to get a unique username.',
        array('%username' => $edit['username'],
          '%nodename' => guifi_get_nodename($edit['nid']),
          '%servicename' => $proxy_name->nick
        ))
      );
    }
  }

  if (!empty($edit['pass']))
    $edit['password'] = crypt($edit['pass']);

  if (!empty($edit['notification'])) {
    if (isset($edit['id']))
      $query = db_query(
        "SELECT username, notification, nid, services " .
        "FROM {guifi_users} " .
        "WHERE notification ='%s' " .
        " AND id <> %d",
        $edit['notification'], $edit['id']);
    else
      $query = db_query(
        "SELECT username, notification, nid, services " .
        "FROM {guifi_users} " .
        "WHERE notification ='%s'",
        $edit['notification']);

    while ($guifi_users = db_fetch_object($query)) {

      form_set_error('notification', t('The e-mail address: %notification is already defined ' .
        'for the user: %username on node %nodename ' .
        '<p>Each user must use a real e-mail and this must not be repeated in others.',
        array('%notification' => $edit['notification'],
          '%username' => $guifi_users->username,
          '%nodename' => guifi_get_nodename($guifi_users->nid),
        ))
      );
    }
  }
}

function guifi_users_queue($zone) {

  function _guifi_user_queue_device_form($form_state, $d = array()) {

    guifi_log(GUIFILOG_TRACE,'function guifi_user_queue_device_form()',$d);

    if (count($d['radios']) != 1)
      return;
    if ($d['radios'][0]['mode'] != 'client')
      return;

    if (!isset($d['radios'][0]['interfaces']))
      return;
    $iid = key($d['radios'][0]['interfaces']);
    if (!isset($d['radios'][0]['interfaces'][$iid]['ipv4'][0]['links']))
      return;
    $lid = key($d['radios'][0]['interfaces'][$iid]['ipv4'][0]['links']);
    if ((empty($iid)) or (empty($lid)))
      return;

    if (empty($form_state['values'])) {
      $form_state['values'] = $d;
    }
    $f['flag'] = array(
      '#type' => 'item',
      '#value' => $form_state['values']['flag'],
      '#prefix' => '<table><tr><td>',
      '#suffix' => '</td>'
    );
    $f['mac'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#size' => 17,
      '#maxlength' => 17,
      '#default_value' => $form_state['values']['radios'][0]['mac'],
      '#element_validate' => array('guifi_mac_validate'),
      '#prefix' => '<td>',
      '#suffix' => '</td>'
    );

    $f['did'] = array('#type' => 'hidden','#value' => $form_state['values']['id']);
    $f['nid'] = array('#type' => 'hidden','#value' => $form_state['values']['nid']);
    $f['uid'] = array('#type' => 'hidden','#value' => $form_state['values']['uid']);
    $f['iid'] = array('#type' => 'hidden','#value' => $iid);
    $f['lid'] = array('#type' => 'hidden','#value' => $lid);

    $f['approve'] = array(
      '#type' => 'image_button',
      '#src'=> drupal_get_path('module', 'guifi').'/icons/ok.png',
      '#attributes' => array('title' => t('Set the device and link Online, confirm MAC & approve user.')),
      '#prefix' => '<td>',
      '#suffix' => '</td></tr></table>'
    );
    return $f;
  }

  function _guifi_user_queue_form($form_state, $params = array()) {

    guifi_log(GUIFILOG_TRACE,'function guifi_user_queue_form()',$params);

    if (empty($form_state['values'])) {
      $form_state['values'] = $params;
    }
    $f['status'] = array(
      '#type' => 'select',
//      '#title' => t('Status'),
      '#options' => guifi_types('user_status'),
      '#default_value' => $form_state['values']['status'],
      '#prefix' => '<table><tr><td>',
      '#suffix' => '</td>'
    );
    $f['uid'] = array('#type' => 'hidden','#value' => $form_state['values']['id']);
    $f['saveUser'] = array(
      '#type' => 'image_button',
      '#src'=> drupal_get_path('module', 'guifi').'/icons/save.png',
      '#attributes' => array('title' => t('Change & Save users Status.')),
      '#submit' => array('_guifi_user_queue_device_form_submit'),
      '#prefix' => '<td>',
      '#suffix' => '</td></tr></table>'
    );
    return $f;
  }

  function _guifi_user_queue_devices($u) {

    $query = db_query(
      'SELECT d.id ' .
      'FROM {guifi_devices} d ' .
      'WHERE d.nid=%d' .
      '  AND type="radio"',
      $u['nid']
    );
    $rows = array();
    while ($d = db_fetch_array($query)) {
     $d = guifi_device_load($d['id']);
     $d['uid']=$u['id'];

     if (guifi_device_access('update',$d['id'])) {
       $edit_device_icon =
         l(guifi_img_icon('edit.png'),'guifi/device/'.$d['id'].'/edit',
           array('html' => TRUE,'attributes' => array('target' => '_blank'))).
         l(guifi_img_icon('drop.png'),'guifi/device/'.$d['id'].'/delete',
           array('html' => TRUE,'attributes' => array('target' => '_blank')));
     } else
       $edit_device_icon = '';

     if (user_access('administer guifi users')) {
       $edit_ok_icon = drupal_get_form('_guifi_user_queue_device_form',$d);
     } else {
       $edit_ok_icon = $d['flag'];
       if ( (count($d['radios']) == 1) and ($d['radios'][0]['mode']=='client'))
         $edit_ok_icon .= ' '.$d['radios'][0]['mac'];
     }


     $ip = guifi_main_ip($d['id']);

     $status_url = guifi_cnml_availability(
       array('device' => $d['id'],'format' => 'short'));

     $rows[] = array(
       $edit_device_icon.
       l($d['nick'],'guifi/device/'.$d['id'],
         array('attributes' => array('target' => '_blank'))),
         array(
           'data' => l($ip['ipv4'].'/'.$ip['maskbits'],
             guifi_device_admin_url($d,$ip['ipv4']),
             array('attributes' => array('title' => t('Connect to the device on a new window'),
               'target' => '_blank'))),
               'align' => 'right'),
               array('data' => $edit_ok_icon, 'class' => $d['flag']),
               array('data' => $status_url, 'class' => $d['flag']),
     );
    }
    return $rows;
  }

  global $user;
  $owner = $user->uid;

  guifi_log(GUIFILOG_TRACE,'function guifi_users_node_list()',$zone);

  drupal_set_breadcrumb(guifi_zone_ariadna($zone->id,'node/%d/view/userqueue'));
  $title = t('Queue of pending users @') .' ' .$zone->title;
  drupal_set_title($title);

  $childs = guifi_zone_childs($zone->id);
  $childs[] = $zone->id;

  $sql =
    'SELECT ' .
    '  u.*, l.id nid, l.nick nnick, l.status_flag nflag, l.zone_id ' .
    'FROM {guifi_users} u, {guifi_location} l ' .
    'WHERE u.nid=l.id' .
    '  AND (l.status_flag != "Working" OR u.status != "Approved") ' .
    '  AND l.zone_id IN ('.implode(',',$childs).') ' .
    'ORDER BY FIND_IN_SET(u.status,"New,Pending,Approved,Rejected"),' .
    '  u.timestamp_created';
  $query = pager_query($sql,variable_get("guifi_pagelimit", 50));

  $rows = array();
  $nrow = 0;

  if ((user_access('administer guifi networks')) ||
    (user_access('administer guifi zones')) ||
    (user_access('administer guifi users')))
      $administer = TRUE;
    else
      $administer = FALSE;

  while ($u = db_fetch_array($query)) {
    $pUser = (object) guifi_user_load($u['id']);
    $proxy = node_load(array('nid' => $pUser->services['proxy']));

    $srows =  _guifi_user_queue_devices($u);
    $nsr   = count($srows);

    if (empty($nsr))
      $nsr = 1;

    $node = node_load(array('nid' => $u['nid']));
    if (guifi_node_access('update',$node)) {
      $edit_node_icon =
        l(guifi_img_icon('edit.png'),
          'node/'.$u['nid'].'/edit',
          array('html' => TRUE,'attributes' => array('target' => '_blank'))).
        l(guifi_img_icon('drop.png'),
          'node/'.$u['nid'].'/delete',
          array('html' => TRUE,'attributes' => array('target' => '_blank')));
    } else {
      $edit_node_icon = '';
    }

    if (guifi_user_access('update',$u)) {
      $edit_user_icon =
        l(guifi_img_icon('edit.png'),
          'guifi/user/'.$u['id'].'/edit',
           array('html' => TRUE,'attributes' => array('target' => '_blank'))).
        l(guifi_img_icon('drop.png'),
          'guifi/user/'.$u['id'].'/delete',
           array('html' => TRUE,'attributes' => array('target' => '_blank')));
    } else {
      $edit_user_icon = '';
    }

    if ($administer) {
      $edit_user_form = drupal_get_form('_guifi_user_queue_form',$u);
    } else {
      $edit_user_form = $u['status'];
    }

    $rows[] = array(
      array('data'=>
        $edit_user_icon.
        l($u['username'],'node/'.$u['nid'].'/view/users',
          array('attributes' => array(
            'title' => $u['lastname'].", ".$u['firstname'],
            'target' => '_blank')
          )) .
          "\n<br />".
          '<small>'.format_date($u['timestamp_created']).'<br />'.
          l(
            $proxy->nick,
           "node/".$proxy->id,
           array('attributes' => array('title' => $proxy->title))),
        'rowspan' => $nsr
        ),
      array(
        'data'=>
           guifi_get_zone_nick($u['zone_id'])."<br /><strong>".
           $edit_node_icon.
           l($u['nnick'],'node/'.$u['nid'],
             array('html' => TRUE,'attributes' => array('target' => '_blank'))).
           '</strong><br /><small>'.
           l(t('add a comment'),'comment/reply/'.$u['nid'],
             array('fragment' => 'comment-form',
               'html' => TRUE,
               'attributes' => array('title' => t('Add a comment to the page of this node'),
                 'target' => '_blank'))).'</small>',
        'class' => $u['nflag'],
        'rowspan' => $nsr
        ),
      array('data' => $edit_user_form,
        'rowspan' => $nsr
        )
      );

    end($rows);
    $krow = key($rows);

    if (count($srows)) {
      // merge current row with first element
      $rows[$krow] = array_merge($rows[$krow],array_shift($srows));
      // adding rest og the elements
      foreach ($srows as $k => $v)
        $rows[] = $v;
    }

  }

  $header = array(
    t('Username'),
    t('Node'),
    t('User status'),
    t('Device'),
    t('IP v4 address'),
    t('Status & MAC'),
    t('Current status')
  );

  $output .= theme('table', $header, $rows);
  $output .= theme_pager(NULL, variable_get("guifi_pagelimit", 50));

  // Full screen (no lateral bars, etc...)
  print theme('page', $output, FALSE);
  // If normal output, retrurn $output...
}

function guifi_users_node_list($node) {

  guifi_log(GUIFILOG_TRACE,'function guifi_users_node_list()',$node);
  $output = drupal_get_form('guifi_users_node_list_form',$node);

  $node = node_load(array('nid' => $node->id));
  drupal_set_breadcrumb(guifi_node_ariadna($node));
  $output .= theme_links(module_invoke_all('link', 'node', $node, FALSE));
  // To gain space, save bandwith and CPU, omit blocks
  print theme('page', $output, FALSE);
}

/**
 * outputs the user information data
**/
function guifi_users_node_list_form($form_state, $params = array()) {
  global $user;
  $owner = $user->uid;

  guifi_log(GUIFILOG_TRACE,'function guifi_users_node_list_form()',$params);

  if (empty($form_state['values'])) {
    if (is_numeric($params))
      $node = node_load($params);
    else
      $node = node_load(array('nid' => $params->id));
  }

//  $form_state['#redirect'] = FALSE;

//  if (!empty($op)) {
//    $edit=$_POST['edit'];
//    if ((empty($edit['user_checked'])) and ($op == t('Edit selected')))
//      form_set_error('',t('You must select a user checkbox for editing it'));
//    else
//      return guifi_edit_user($edit['user_checked']);
//  }

  drupal_set_breadcrumb(guifi_zone_ariadna($node->zone_id));
  $title = t('Users @') .' ' .$node->title;
  drupal_set_title($title);

  if ($node->type == 'guifi_node') {
    $query = db_query(
      "SELECT id " .
      "FROM {guifi_users} " .
      "WHERE nid = %d " .
      "ORDER BY lastname, firstname",
      $node->nid);
  } else
    $query = db_query(
      "SELECT id " .
      "FROM {guifi_users} " .
      "ORDER BY lastname, firstname");

  $rows[] = array();
  $num_rows = FALSE;

  $f = array(
    '#type'=> 'fieldset',
    '#collapsible' => FALSE,
    '#title' => t('Users')
  );

  $options = array();

  while ($guserid = db_fetch_object($query)) {
    $guser = (object)guifi_user_load($guserid->id);
    $services = $guser->services;
    if ($node->type == 'guifi_service') {
      if (($node->service_type != 'Proxy') or ($node->nid != $services['proxy']))
        continue;
    }
    if (!empty($guser->lastname))
      $realname = $guser->lastname.', '.$guser->firstname;
    else
      $realname = $guser->firstname;

    $service = node_load(array('nid' => $guser->services['proxy']));

    $options[$guser->id] = $realname.' ('.$guser->username.')'.' - '.
      l($service->nick,'node/'.$service->id,array('attributes' => array('title' => $service->title))).' - '.
      $guser->status.'<br />'.
      theme_guifi_contacts($guser, FALSE);
    if (!isset($default_user))
      $default_user = $guser->id;
  }

  if (count($options)) {
    $f['user_id'] = array(
      '#type' => 'radios',
      '#title' => $title,
      '#options' => $options,
      '#default_value' => $default_user
    );
    if ((user_access('administer guifi users')) or (user_access('manage guifi users')) or ($node->uid == $owner))
      $f['editUser'] = array(
        '#type' => 'submit',
        '#value' => t('Edit selected user')
      );
  } else
    $f['empty'] = array(
      '#type'=> 'item',
      '#title'=> t('There are no users to list at').' '.$node->title
    );
    if ((user_access('administer guifi users')) or (user_access('manage guifi users')) or ($node->uid == $owner))
      $f['addUser'] = array(
        '#type' => 'submit',
        '#value' => t('Add user')
      );
  return $f;
}

function guifi_users_node_list_form_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_users_node_list_form_submit()',$form_state);

  switch ($form_state['clicked_button']['#value']) {
    case t('Edit selected user'):
      if (empty($form_state['values']['user_id'])) {
        drupal_set_message(t('You must select a user from the list'));
        break;
      }
      drupal_goto('guifi/user/'.$form_state['values']['user_id'].'/edit');
      break;
    case t('Add user'):
      drupal_goto('node/'.arg(1).'/user/add');
      break;
  }
}

function guifi_user_form_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_user_form_submit()',$form_state);

  switch ($form_state['clicked_button']['#value']) {
    case t('Save'):
      guifi_user_save($form_state['values']);
      drupal_goto("node/".$form_state['values']['nid']."/view/users");
//        drupal_set_message(t('User save.'));
      break;
    case t('Reset password'):
      guifi_user_reset_password($form_state['values']);
      drupal_goto("node/".$form_state['values']['nid']."/view/users");
      break;
    case t('Delete'):
      drupal_goto("guifi/user/".$form_state['values']['id']."/delete");
      break;
  }
}


/**
 * Save changes to the database.
 */
function guifi_user_save($edit) {
  global $user;

  $n = node_load($edit['nid']);

  $to_mail = $n->notification;
  $log ='';

  if (isset($edit['services'])) {
    if (isset($edit['services']['proxystr']))
      unset($edit['services']['proxystr']);
    $edit['services'] = serialize($edit['services']);
  }
  if (isset($edit['var']))
    $edit['extra'] = serialize($edit['var']);
  if (isset($edit['content_filters']))
    $edit['content_filters'] = serialize($edit['content_filters']);
  if (isset($edit['username_created'])) {
    $cuser = user_load(array('name' => $edit['username_created']));
    $edit['user_created'] = $cuser->uid;
  }

  guifi_log(GUIFILOG_TRACE,'function guifi_user_save()',$edit);

  _guifi_db_sql('guifi_users',array('id' => $edit['id']),$edit,$log,$to_mail);

  drupal_set_message(t('%user saved. Note that in some cases the change will not take effect until after some time.', array('%user' => $edit['username'])));
  guifi_notify(
    $to_mail,
    t('The user !username has been UPDATED by !user.',array('!username' => $edit['username'], '!user' => $user->name)),
    $log);
}

function guifi_users_dump_passwd($node) {

  // listing all users for a given service in "passwd" format
  drupal_set_header('Content-Type: text/plain; charset=utf-8');
  print guifi_users_dump_return($node);
  exit;
}

function guifi_users_dump_return($node,$federated = FALSE,$ldif = FALSE) {

  /* Aquesta funcio retorna en una variable la llista d'usuaris i passwd dun proxy */

  $passwd = array();

  // query ALL zones, kept in memory zones array
  $zones = array();
  $query = db_query("SELECT id, title FROM {guifi_zone}");
  while ($item = db_fetch_object($query))
    $zones[$item->id] = $item->title;

  // query ALL node zones, kept in memory node_zones array
  $node_zones = array();
  $query = db_query("SELECT id, zone_id FROM {guifi_location}");
  while ($item = db_fetch_object($query))
    $node_zones[$item->id] = $item->zone_id;

  // query ALL users, kept in memory users array
  $query = db_query("SELECT * FROM {guifi_users} WHERE status='Approved'");
  $users = array();
  while ($item = db_fetch_object($query)) {
    $user = (object)NULL;
    $user->username = $item->username;
    $user->password = $item->password;
    $user->nid = $item->nid;
    $services = unserialize($item->services);
    $user->prId = $services['proxy'];
    $user->zId = $node_zones[$item->nid];
    $users[$user->prId][] = $user;
  }

  $passwd = array();

  // dumping requested proxy users, starting by the users from the same zone
  if (count($users[$node->id])) foreach ($users[$node->id] as $user)

    ($ldif) ? $passwd[$user->zId][] = guifi_user_dump_ldif($user) :
      $passwd[$user->zId][] = $user->username.':'.$user->password;

  $dump .=  "#\n";
  $dump .=  "# users for proxy: ".$node->nick." at zone ".$zones[$node->zone_id]."\n";
  $dump .=  "# users: ".count($passwd[$node->zone_id])."\n";
  $dump .=  "#\n";
  if (count($passwd[$node->zone_id]))
    foreach ($passwd[$node->zone_id] as $p)
      $dump .= $p."\n";
  else
      $dump .= '# '.t('there are no users at this proxy')."\n";
  unset($passwd[$node->zone_id]);

  // now dumping all other zones from the principal proxy
  foreach ($passwd as $zid => $zp) {
    $dump .= "# At zone ".$zones[$zid]."\n";
    $dump .= "# users: ".count($passwd[$zid])."\n";
    foreach ($zp as $p)
       $dump .= $p."\n";
  }

  if ($federated == FALSE)
    return $dump;

  unset($users[$node->id]);
  unset($passwd);
  foreach ($users as $prId => $prUsers)
    if (in_array($prId,$federated))
      foreach ($prUsers as $user)
        ($ldif) ? $passwd[$user->zId][] = guifi_user_dump_ldif($user) :
          $passwd[$user->zId][] = $user->username.':'.$user->password;

  $dump .=  "#\n";
  $dump .=  "# passwd file for ALL OTHER proxys\n";
  $dump .=  "#\n";
  foreach ($passwd as $zid => $zp) {
    $dump .= "#\n";
    $dump .= "# At zone ".$zones[$zid]."\n";
    $dump .= "# users: ".count($passwd[$zid])."\n";    
    $dump .= "#\n";
    foreach ($zp as $p)
       $dump .= $p."\n";
  }

  return $dump;
}

function _guifi_users_dump_federated($node,$ldif = FALSE) {

// Listing all the federated proxys
  if (is_array($node->var[fed]))
    if (($node->var['fed']['IN'] != '0') AND ($node->var['fed']['IN'] == 'IN')) {
      $head  = "#\n";
      $head .= '# Federated users list for Proxy : "'.$node->nick.'"'."\n";
      $head .= "#\n";
      $head .= "#  Includes users from the following proxys :\n";
      $head .= "#\n";
      $head .= '#   ' .$node->nid." - ".$node->title."\n";
      $query = db_query("SELECT id,extra FROM {guifi_services} WHERE service_type='Proxy'");
      while ($item = db_fetch_object($query)) {
        $extra = unserialize($item->extra);
	  if (($item->id!=$node->nid) & (is_array($extra[fed]))) {
            $p_node = node_load(array('nid' => $item->id));
              if (($extra['fed']['OUT'] != '0') AND ($extra['fed']['OUT'] == 'OUT')) {
                $head .= '#   ' .$p_node->nid." - ".$p_node->title."\n";
	        $federated_out[] = $item->id;
	      }
	  }
      }
      $head .="#\n";
    }
  $output .= $head;

  // Listings users
  $output .= guifi_users_dump_return($node,$federated_out,$ldif);
  return $output;
}

function guifi_users_dump_federated($node) {
  $output = _guifi_users_dump_federated($node);
  drupal_set_header('Content-Type: text/plain; charset=utf-8');
  print $output;
  exit;
}

function guifi_users_dump_federated_md5($node) {
  $dump = _guifi_users_dump_federated($node);
  drupal_set_header('Content-Type: text/plain; charset=utf-8');
  print md5($dump);
  exit;
}

function guifi_users_dump_ldif($service) {
  drupal_set_header('Content-Type: text/plain; charset=utf-8');
  print _guifi_users_dump_federated($service, TRUE);
  exit;
}

function guifi_user_dump_ldif($user) {
    /* format
dn: uid=eloi.alsina,ou=People,dc=guifi,dc=net
uid: eloi.alsina
cn: Eloi Alsina
objectClass: account
objectClass: posixAccount
objectClass: top
userPassword: {crypt}]lGnmr4S7ObLo
uidNumber: 0
gidNumber: 0
homeDirectory: eloi
host: esperanca

dn: cn=Eloi Alsina,uid=eloi.alsina,ou=People,dc=guifi,dc=net
givenName: eloi
sn: alsina
cn: Eloi Alsina
mail: eloi.alsina@guifi.net
homePhone: 938892062
mobile: 6639393906
homePostalAddress: Mas Seri Xic
objectClass: inetOrgPerson
objectClass: top
     */
  return
"dn: uid=$user->username,ou=People,dc=guifi,dc=net
uid:$user->username
cn:$user->lastname,$user->firstname
objectClass: account
objectClass: posixAccount
objectClass: top
userPassword: {crypt}$user->password
uidNumber: 99
gidNumber: 99
homeDirectory: /home/nobody
description: proxy($user->services['proxy'])";
}


?>