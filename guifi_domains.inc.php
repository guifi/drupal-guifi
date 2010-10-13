<?php
/**
 * @file
 * Manage guifi_domains
 */

/*
 * guifi_domain_load(): get a domain and all its related information and builds an array
 */
function guifi_domain_load($id,$ret = 'array') {
  guifi_log(GUIFILOG_FULL,'function guifi_domain_load()');

  $domain = db_fetch_array(db_query('
    SELECT d.*
    FROM {guifi_dns_domains} d, {guifi_services} l
    WHERE d.id = %d
    AND d.sid = l.id',
    $id));
  if (empty($domain)) {
    drupal_set_message(t('Domain (%num) does not exist.',array('%num' => $id)));
    return;
  }

  $qr = db_query('
    SELECT *
    FROM {guifi_dns_hosts}
    WHERE id = %d ORDER BY counter',
    $id
  );

  $rc = 0;
  while ($host = db_fetch_array($qr)) {
    $rc++;
    $domain['hosts'][$host['counter']] = $host;
  }

  if ($ret == 'array')
    return $domain;
  else {
    foreach ($domain as $k => $field)
      $var->$k = $field;
    return array2object($domain);
  }
}

function guifi_domain_access($op, $id) {
  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_domain_access()',$id);
  guifi_log(GUIFILOG_FULL,'user=',$user);

  if ($user->uid==0)
    return FALSE;

  if (empty($id) || ($id < 1))
   return FALSE;

  if (is_array($id))
    $domain = $id;
  else
    $domain = guifi_domain_load($id);

  $node = node_load(array('nid' => $domain['sid']));

  if ($op == 'create') {
    if ((user_access('administer guifi dns')) || (user_access('create guifi dns'))) {
      return TRUE;
    } else {
      return FALSE;
    }
  }
  if ($op == 'update' or $op == 'delete') {
    if ((user_access('administer guifi dns')) || (user_access('edit own guifi dns'))) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

}

/*
 * Domain edit funcions
 * guifi_domain_form_submit(): Performs submit actions
 */
function guifi_domain_form_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_domain_form_submit()',
    $form_state);

  if ($form_state['values']['id'])
  if (!guifi_domain_access('update',$form_state['values']['id']))
  {
    drupal_set_message(t('You are not authorized to edit this domain','error'));
    return;
  }

  switch ($form_state['clicked_button']['#value']) {
  case t('Reset'):
    drupal_set_message(t('Reset was pressed, ' .
        'if there was any change, was not saved and lost.' .
        '<br />The domain information has been reloaded ' .
        'from the current information available at the database'));
    drupal_goto('guifi/domain/'.$form_state['values']['id'].'/edit');
    break;
  case t('Save & continue edit'):
  case t('Save & exit'):

    $id = guifi_domain_save($form_state['values']);
    if ($form_state['clicked_button']['#value'] == t('Save & exit'))
      drupal_goto('guifi/domain/'.$id);
    drupal_goto('guifi/domain/'.$id.'/edit');
    break;
  default:
    guifi_log(GUIFILOG_TRACE,
      'exit guifi_domain_form_submit without saving...',$form_state['clicked_button']['#value']);
    return;
  }

}

/* guifi_domain_form(): Present the guifi domain main editing form. */
function guifi_domain_form($form_state, $params = array()) {
  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_domain_form()',$params);

  if (empty($form_state['values']))
    $form_state['values'] = $params;
    $form_state['#redirect'] = FALSE;

  // if new domain, initializing variables
  if (($form_state['values']['sid'] == NULL) && ($params['add'] != NULL)) {
    $form_state['values']['sid'] = $params['add'];
    $form_state['values']['new'] = TRUE;
    if ($params['mname']) {
      $masteridqry = db_query("SELECT name FROM {guifi_dns_domains} WHERE name = '%s'",$params['mname']);
      $mname = db_fetch_object($masteridqry);
      $form_state['values']['mname'] = $mname->name;
      $form_state['values']['name'] = $params['dname'].'.'.$params['mname'];
    } else {
      $form_state['values']['name'] = $params['dname'];
    }
    $form_state['values']['type'] = $params['type'];
    $form_state['values']['ipv4'] = $params['ipv4'];
    $form_state['values']['scope'] = $params['scope'];
    $form_state['values']['management'] = $params['management'];
    $form_state['values']['allow'] = 'disabled'; 
    $form_state['values']['hosts']['0']['new'] = TRUE;
    $form_state['values']['hosts']['0']['counter'] = '0';
    $form_state['values']['hosts']['0']['host'] = 'ns1';
    $form_state['values']['hosts']['0']['ipv4'] = $params['ipv4'];
    $form_state['values']['hosts']['0']['opt']['options'] = array( 'NS' => 'NS', 'MX' => '0' );
  }

  drupal_set_breadcrumb(guifi_node_ariadna($form_state['values']['sid']));

  // Check permissions
  if ($params['edit']){
    if (!guifi_domain_access('update',$params['edit'])){
      drupal_set_message(t('You are not authorized to edit this domain','error'));
      return;
    }
  }

  // Loading node & zone where the domain belongs to (some information will be used)
  $node = node_load(array('nid' => $form_state['values']['sid']));

  // Setting the breadcrumb
  drupal_set_breadcrumb(guifi_node_ariadna($form_state['values']['sid']));

  // if contact is NULL, then get it from the node or the user logged in drupal
  if (is_null($form_state['values']['notification']))
    if (guifi_notification_validate($node->notification)) {
      $form_state['values']['notification'] = $node->notification;
    } else {
      drupal_set_message(t('The service has not a valid email address as a contact. Using your email as a default. Change the contact mail address if necessary.'));
      $form_state['values']['notification'] = $user->mail;
    }

  if (isset($form_state['action'])) {
    guifi_log(GUIFILOG_TRACE,'action',$form_state['action']);
    if (function_exists($form_state['action'])) {
      if (!call_user_func_array($form_state['action'],
        array(&$form,&$form_state)))
          return $form;
    }
  }

  $form_weight = -20;
  if ($form_state['values']['id'])
    $form['id'] = array(
      '#type' => 'hidden',
      '#name' => 'id',
      '#value'=> $form_state['values']['id']
    );
  else
    $form['new'] = array(
      '#type' => 'hidden',
      '#name' => 'new',
      '#value'=> TRUE
    );

  if ($params['add'] != NULL){
    drupal_set_title(t('adding a new type %domain at %node',
      array('%node' => $node->nick,
            '%domain' => $form_state['values']['type']
           )));
  } else {
    drupal_set_title(t('edit domain %dname',array('%dname' => $form_state['values']['name'])));
  }

  // All preprocess is complete, now going to create the form

  $form['main'] = array(
    '#type' => 'fieldset',
    '#title' => t('Domain name main settings').' ('.
      $form_state['values']['name'].')',
    '#weight' => $form_weight++,
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $form['main']['sid'] = array(
    '#type' => 'hidden',
    '#value'=> $form_state['values']['sid'],
  );

  $form['main']['type'] = array(
    '#type' => 'hidden',
    '#value'=> $form_state['values']['type'],
  );

  $form['main']['mname'] = array(
    '#type' => 'hidden',
    '#value'=> $form_state['values']['mname'],
  );

  $form['main']['scope'] = array(
    '#type' => 'hidden',
    '#value'=> $form_state['values']['scope'],
  );

  $form['main']['name'] = array(
    '#type' => 'textfield',
    '#size' => 32,
    '#maxlength' => 32,
    '#title' => t('Domain Name'),
    '#required' => TRUE,
    '#default_value' => $form_state['values']['name'],
    '#description' =>  t('The Domain Name'),
  );

  $form['main']['management'] = array(
    '#type' => 'select',
    '#title' => t('Management'),
    '#default_value' => $form_state['values']['management'],
    '#options' => array('automatic' => 'automatic', 'manual' => 'manual'),
    '#description' =>  t('Choose <b>Automatic</b> if you want to use your domain management with the utility for servers "DNSServices"<br \>'
                                  .'Choose <b>Manual</b>, if you just want to keep track of your domain/hosts here but want to do the management in your server manually.'),
  );
  $form['main']['public'] = array(
    '#type' => 'select',
    '#title' => t('Public domain'),
    '#default_value' => $form_state['values']['public'],
    '#options' => array('yes' => 'Yes', 'no' => 'No'),
    '#description' =>  t('Choose <b>Yes</b> if you want your domain/subdomain can be delegated subdomains from other users. (Ex: DELEGATED.yourdomain.net.<br/>'
                                  .'Choose <b>No</b> if your domain/subdomain is private and you do not want to allow delegates subdomains.'),
  );
  $form['main']['allow'] = array(
    '#type' => 'radios',
    '#required' => TRUE,
    '#title' => t('Transfer Options'),
    '#default_value' => $form_state['values']['allow'],
    '#options' => array('slave' => t('Allow to be enslaved (Recommended)'), 'forward' => t('Allow Forward'), 'disabled' => t('Disabled')),
    '#description' =>  t('<b>Ensalved</b>, Allow other DNS servers on the network have an exact copy of the domain, so, if the original DNS does not work, can access to the hosts.'
                                   .'<br \><b>Forward</b>, Allow other DNS servers on the network to forward the request to the master server transparently saving bandwidth.'
                                   .'<br \><b>Disabled</b>, If you select this option, the management of your domain may not be transferred in any way, your domain will not be visible to other network servers.'),
  );

  if ($form_state['values']['scope'] == 'external') {
    $form['main']['ipv4'] = array(
    '#type' => 'textfield',
    '#title' => t('Nameserver IP Address'),
    '#size' => 16,
    '#maxlength' => 16,
    '#required' => TRUE,
    '#default_value'=> $form_state['values']['ipv4'],
    '#element_validate' => array('guifi_ipv4_validate'),
  );
  } else {
    $form['main']['ipv4'] = array(
      '#type' => 'hidden',
      '#title' => t('Nameserver IP Address'),
      '#default_value'=> $form_state['values']['ipv4'],
    );
  }

  $form['main']['defipv4'] = array(
    '#type' => 'textfield',
    '#title' => t("Default Domain IP Address"),
    '#default_value' => $form_state['values']['defipv4'],
    '#element_validate' => array('guifi_ipv4_validate'),
    '#description' => t("Ex: domain.net without hostname resolve this IP Address, tends to be the same address as hostname: www.<br> leave it blank if not needed."),
    '#required' => FALSE,
  );

  $form['main']['notification'] = array(
    '#type' => 'textfield',
    '#size' => 60,
    '#maxlength' => 1024,
    '#title' => t('contact'),
    '#required' => TRUE,
    '#element_validate' => array('guifi_emails_validate'),
    '#default_value' => $form_state['values']['notification'],
    '#description' =>  t('Mailid where changes on the domain will be notified, if many, separated by \',\'<br />used for network administration.')
  );

   if ($form_state['values']['management'] == 'automatic') {
  if (function_exists('guifi_host_form')){
    $form = array_merge($form,
      call_user_func('guifi_host_form',
        $form_state['values'],
        $form_weight));
  }
}
  // Comments
  $form_weight = 200;

  $form['comment'] = array(
    '#type' => 'textarea',
    '#title' => t('Comments'),
    '#default_value' => $form_state['values']['comment'],
    '#description' => t('This text will be displayed as an information of the domain.'),
    '#cols' => 60,
    '#rows' => 5,
    '#weight' => $form_weight++,
  );

  //  save/validate/reset buttons
  $form['dbuttons'] = guifi_domain_buttons(FALSE,'',$form_weight);

  return $form;
}

/* guifi_domain_form_validate(): Confirm that an edited domain has fields properly filled. */
function guifi_domain_form_validate($form,&$form_state) {

  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_domain_form_validate()',$form_state);

  if (isset($form['main']['name'])) {
    if (ereg('[^a-z0-9.-]', $form_state['values']['name']))
      form_set_error('name', t('Error: <strong> %name </strong> - Domain Name can only contain lowercase letters, numbers, dashes and dots.', array('%name' => $form_state['values']['name'])));

    $query = db_query("
      SELECT name
      FROM {guifi_dns_domains}
      WHERE lcase(name)=lcase('%s')
      AND id <> %d AND scope = '%s'",
      strtolower($form_state['values']['name']),
      $form_state['values']['id'],$form_state['values']['scope']);

    while (db_fetch_object($query)) {
      form_set_error('name', t('Domain Name already in use in scope: %scope.',array('%scope' => $form_state['values']['scope'])));
    }
  }

  $dlgdomain = $form_state['values']['name'];
  $hostname = strstr($dlgdomain, '.', true);

  $queryhosts = db_query("
      SELECT *
      FROM {guifi_dns_hosts}
      WHERE id <> %d",
      $form_state['values']['id']);

  while ($hosts = db_fetch_array($queryhosts)) {
    $hostx = $hosts['host'];
    if ($hostx == $hostname) {
      form_set_error('name', t('Subdomain name <b>%hostname</b> already in use as <b>HOSTNAME</b> from master domain : <b>%domain</b>'
                                             .'<br>Delete hostame first if you want use this name as delegated domain.', array('%hostname' => $hostname,'%domain' => $form_state['values']['mname'])));
    }
    $aliases = unserialize($hosts['aliases']);
    if ($aliases) {
      foreach ($aliases as $alias) {
        if ($alias == $hostname) {
          form_set_error('name', t('Subdomain name <b>%alias</b> already in use as <b>ALIAS</b> from hostname: <b>%hostname</b>  on master domain : <b>%domain</b>'
                                             .'<br>Delete alias first if you want use this name as delegated domain.', array('%alias' => $alias, '%hostname' => $hosts['host'], '%domain' => $form_state['values']['mname'])));
        }
      }
    }
  }

  if (empty($form_state['values']['ipv4'])) {
    $qry = db_query("
      SELECT *
      FROM {guifi_services}
      WHERE id = '%d'", $form_state['values']['sid']);
    $dev = db_fetch_object($qry);
    $devqry = db_query("
      SELECT *
      FROM {guifi_devices}
      WHERE id = '%d'", $dev->device_id);  
    $device = db_fetch_object($devqry);
      form_set_error('ipv4', t('Server <strong> %nick </strong> does not have an IP address. Please, check it.', array('%nick' => $device->nick)));
  }

  $hostsd = array();
  $mxpriord = array();
  if (count($form_state['values']['hosts'])){
    foreach ($form_state['values']['hosts']  as $host_id => $hosts) {

      $qrydomain = db_query("
        SELECT name
        FROM {guifi_dns_domains}
        WHERE mname = '%s'", $form_state['values']['name']);
      while ($hostdom = db_fetch_array($qrydomain)) {
        $hostdomx = strstr($hostdom['name'], '.', true);
        if ($hostdomx == $hosts['host']) {
          form_set_error('hosts]['.$host_id.'][host', t('Hostname Error! There is already a delegate domain with this name: <b>%hostname</b>.', array('%hostname' => $hosts['host'])));
        }
      }

      if (ereg('[^a-z0-9.-]', $hosts['host']))
        form_set_error('hosts]['.$host_id.'][host', t('Error! Hostname: <strong> %hostname </strong> can only contain lowercase letters, numbers, dashes and dots.', array('%hostname' => $host['host'])));

      $host = $hosts['host'];
      if (in_array($host,$hostsd)) {
        form_set_error('hosts]['.$host_id.'][host', t('Error!! Hostname: <strong>%host</strong> duplicated.', array('%host' => $hosts['host'])));
        break;
      }
      $hostsd[] = $host;
      $aliasd = array();
      foreach($hosts['aliases'] as $aliasa_id => $aliasa){

      $qrydomain = db_query("
        SELECT name
        FROM {guifi_dns_domains}
        WHERE mname = '%s'", $form_state['values']['name']);
      while ($hostdom = db_fetch_array($qrydomain)) {
        $hostdomx = strstr($hostdom['name'], '.', true);
        if ($hostdomx == $aliasa) {
          form_set_error('hosts]['.$host_id.'][aliases]['.$aliasa_id, t('Alias Error! There is already a delegate domain with this name: <b>%alias</b>.', array('%alias' => $aliasa)));
        }
      }

        if (ereg('[^a-z0-9.-]', $aliasa))
          form_set_error('hosts]['.$host_id.'][aliases]['.$aliasa_id, t('Error! Alias: <strong> %alias </strong> can only contain lowercase letters, numbers, dashes and dots.', array('%alias' => $aliasa)));

        if (!empty($aliasa)) {
          if (empty($hosts['ipv4'])) {
            $checkdot = substr($aliasa, -1);
            $dot = '.';
            if ( strcmp($checkdot,$dot) != 0 ) {
              form_set_error('hosts]['.$host_id.'][aliases]['.$aliasa_id, t('Error!! Hostname <strong>%host</strong> don\'t have and IPv4 and contain aliases, must put a DOT "<strong>.</strong>" at the end of the alias domain name if the alias points to an external domain. ex: " outsidehost.dyndns.org<strong>.</strong> ".', array('%host' => $hosts['host'])));
            }
            $aliasdomain = strstr($aliasa, '.');
            $dotdomain = '.'.$dlgdomain.'.';
            if ( $aliasdomain == $dotdomain ) {
              form_set_error('hosts]['.$host_id.'][aliases]['.$aliasa_id, t('Error!! Can\'t use your own Domain/Subdomain as external domain.'));
            }
          }
        }
        if (!empty($aliasa)) {
          if (in_array($aliasa,$aliasd)) {
            form_set_error('hosts]['.$host_id.'][aliases]['.$aliasa_id, t('Error!! Alias: <strong>%alias</strong> duplicated.', array('%alias' => $aliasa)));             break;
          }
        }
        $aliasd[] = $aliasa;
        foreach($form_state['values']['hosts'] as $host_id2 => $hosts2) {
          if (!empty($hosts2['host']) && (!empty($aliasa))) {
            if($hosts2['host'] != $host){
              if(in_array($aliasa,$hosts2['aliases'])){
                form_set_error('hosts]['.$host_id.'][aliases]['.$aliasa_id, t('Error!! Alias: <strong>%alias</strong> duplicated.', array('%alias' => $aliasa)));
                break;
              }             }
            if($hosts2['host'] == $aliasa){
                form_set_error('hosts]['.$host_id.'][aliases]['.$aliasa_id,  t('Error!! Alias or Hostname: <strong>%aliashost</strong> alredy exists as hostname or alias!!', array('%aliashost' => $aliasa)));
            }
          }
        }
      }
      if (!empty($hosts['opt']['mxprior'])) {
        $mxprior = $hosts['opt']['mxprior'];
        if (in_array($mxprior,$mxpriord)) {
          form_set_error('hosts]['.$host_id.'][opt][mxprior', t('Error!! MX Priority: <strong>%mxprior</strong> duplicated.', array('%mxprior' => $hosts['opt']['mxprior'])));
          break;
        }
        $mxpriord[] = $mxprior;
      }
      if (empty($hosts['ipv4'])) {
        if (empty($hosts['aliases']['0'])) {
          form_set_error('hosts]['.$host_id.'][host', t('Error!! IP ADDRES and ALIAS ara empty for hostname: <b>%hostname</b>', array('%hostname' => $hosts['host'])));
        } 
      }

      if (!empty($form_state['values']['hosts'][$host_id]['opt']['mxprior']) AND $form_state['values']['hosts'][$host_id]['opt']['mxprior'] <> '0') {
        $priority = $form_state['values']['hosts'][$host_id]['opt']['mxprior'];
        $number = $priority / '10';
        $result = strstr($number, '.');
          if ($result == true) {
            form_set_error('hosts]['.$host_id.'][opt][mxprior', t('NOOO es multiple xDD'));
          }
       } 
    }
  }
}

/* guifi_domain_edit_save(): Save changes/insert domains */
function guifi_domain_save($edit, $verbose = TRUE, $notify = TRUE) {
  global $user;

  $to_mail = array();
  $tomail[] = $user->mail;
  $log = "";
  $to_mail = array();

  // domain
  $ndomain = _guifi_db_sql('guifi_dns_domains',array('id' => $edit['id']),$edit,$log,$to_mail);

  guifi_log(GUIFILOG_TRACE,
    sprintf('domain saved:'),
    $ndomain);


  // hosts
  $rc = 0;
  if (is_array($edit['hosts']))
    ksort($edit['hosts']);
  $rc = 0;

  if ($edit['hosts'])
    foreach ($edit['hosts'] as $counter => $host) {
      $keys['id'] = $ndomain['id'];
      $keys['counter']=$counter;
      $host['id'] = $ndomain['id'];
      $host['counter'] = $rc;

      if ($host['aliases']) {
        foreach ($host['aliases'] as $key => $name) {
          if (empty($name)) {
            unset($host['aliases'][$key]);
          }
        }
        $host['aliases'] =  serialize($host['aliases']);
      } else {
        unset($host['aliases']); 
      }
     if (($host['host'] == 'ns1' ) AND ($host['counter'] == '0')) {
      $host['opt']['options']['NS'] = 'NS';
     }
    $host['opt']['options']['mxprior'] = $host['opt']['mxprior'];
    $host['options'] =  serialize($host['opt']['options']);

    // save the host
    $nhost = _guifi_db_sql('guifi_dns_hosts',$keys,$host,$log,$to_mail);
    if ((empty($nhost)) or ($host['deleted']))
      continue;
    $rc++;
  } // foreach host

  $to_mail = explode(',',$edit['notification']);

  if ($edit['new'])
    $subject = t('The domain %name has been CREATED by %user.',
      array('%name' => $edit['name'],
        '%user' => $user->name));
  else
    $subject = t('The domain %name has been UPDATED by %user.',
      array('%name' => $edit['name'],
        '%user' => $user->name));

//   drupal_set_message($subject);
  guifi_notify($to_mail,
    $subject,
    $log,
    $verbose,
    $notify);
  variable_set('guifi_refresh_dns',time());
  guifi_clear_cache($edit['sid']);
  guifi_clear_cache($edit['id']);

  return $ndomain['id'];

}


function guifi_domain_buttons($continue = FALSE,$action = '', $nopts = 0, &$form_weight = 1000) {
  $form['reset'] = array(
    '#type' => 'button',
    '#executes_submit_callback' => TRUE,
    '#value' => t('Reset'),
    '#weight' => $form_weight++,
  );

  if ($continue) {
    $form['ignore_continue'] = array(
      '#type' => 'button',
      '#executes_submit_callback' => TRUE,
      '#value' => t('Ignore & back to main form'),
      '#weight' => $form_weight++,
    );
    if ($nopts > 0) {
      $form['confirm_continue'] = array(
        '#type' => 'button',
        '#submit' => array($action),
        '#executes_submit_callback' => TRUE,
        '#value' => t('Select domain & back to main form'),
        '#weight' => $form_weight++,
      );
    }
    return $form;
  }
  $form['validate'] = array(
    '#type' => 'button',
    '#value' => t('Validate'),
    '#weight' => $form_weight++,
  );
  $form['save_continue'] = array(
    '#type' => 'submit',
    '#value' => t('Save & continue edit'),
    '#weight' => $form_weight++,
  );
  $form['save_exit'] = array(
    '#type' => 'submit',
    '#value' => t('Save & exit'),
    '#weight' => $form_weight++,
  );

  return $form;
}
/* guifi_domain_delete(): Delete a domain */
function guifi_domain_delete_confirm($form_state,$params) {

  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure you want to delete this domain?'),
    '#value' => $params['name'],
    '#description' => t('WARNING: This action cannot be undone. The domain and it\'s related information will be <strong>permanently deleted</strong>, that includes:<ul><li>The domain</li><li>The related hosts</li></ul>If you are really sure that you want to delete this information, press "Confirm delete".'),
    '#weight' => 0,
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Confirm delete'),
    '#name'  => 'confirm',
    '#weight' => 1,
  );
  drupal_set_title(t('Delete domain: (%name)',array('%name' => $params['name'])));
  variable_set('guifi_refresh_dns',time());
  return $form;
}

function guifi_domain_delete($domain, $notify = TRUE, $verbose = TRUE) {
  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_domain_delete()');

  $to_mail = explode(',',$domain['notification']);

  if ($_POST['confirm']) {
    $log = _guifi_db_delete('guifi_dns_domains',
        array('id' => $domain['id']),
        $to_mail);
    drupal_set_message($log);

    $subject = t('The domain %name has been DELETED by %user.',
      array('%name' => $domain['name'],
        '%user' => $user->name));
    drupal_set_message($subject);
    guifi_notify($to_mail,
      $subject,
      $log,
      $verbose,
      $notify);
    guifi_node_set_flag($domain['sid']);

    drupal_goto('node/'.$domain['sid']);
  }

  $node = node_load(array('nid' => $domain['sid']));
  drupal_set_breadcrumb(guifi_node_ariadna($node));

  $output = drupal_get_form('guifi_domain_delete_confirm',
    array('name' => $domain['name'],'id' => $domain['id']));
  print theme('page',$output, FALSE);
  return;
}

/* guifi_domain_add(): Provides a form to create a new domain */
function guifi_domain_add() {
  guifi_log(GUIFILOG_TRACE,'function guifi_domain_add()');

  $output = drupal_get_form('guifi_domain_form',array(
    'add' => arg(3),
    'dname' => arg(4),
    'type' => arg(5),
    'ipv4' => arg(6),
    'mname' => arg(7),
    'scope' => arg(8),
    'management' => arg(9)));
    
  // To gain space, save bandwith and CPU, omit blocks
  print theme('page', $output, FALSE);
}

/* guifi_domain_create_form(): generates html output form with a listbox,
 * choose the domain type to create
 */
function guifi_domain_create_form($form_state, $node) {

  $ip = guifi_main_ip($node->device_id);
  if (guifi_domain_access('create',$node->sid)) {
    $form['text_add'] = array(
      '#type' => 'item',
      '#value' => t('You are not allowed to create a domain on this service.'),
      '#weight' => 0
   );
   return $form;
  }
  if (empty($ip['ipv4'])) {
    $device = db_fetch_object(db_query('SELECT nick FROM {guifi_devices} WHERE id = %d', $node->device_id));
    $url = url('guifi/device/'.$node->device_id);
    $form['text'] = array(
      '#type' => 'item',
      '#value' => t('The server <a href='.$url.'>'.$device->nick.'</a> does not have an IPv4 address, can not create a domain.')
     );
    return $form;
} 
  $form['domain_type'] = array(
    '#type' => 'select',
    '#title' => t('Select new domain type'),
    '#default_value' => 'none',
    '#options' => array('NULL' => 'none', 'master' => 'Master, ex: newdomain.net','delegation' => 'Delegation, ex: newdomain.guifi.net'),
    '#ahah' => array(
      'path' => 'guifi/js/add-domain',
      'wrapper' => 'select_type',
      'effect' => 'fade',
    )
  );
  $form['domain_type_form'] = array(
    '#prefix' => '<div id="select_type">',
    '#suffix' => '</div>',
    '#type' => 'fieldset',
  );

  if ($form_state['values']['domain_type'] === 'master') {
    $form['domain_type_form']['sid'] = array(
      '#type' => 'hidden',
      '#value' => $node->id
    );
    $form['domain_type_form']['name'] = array(
      '#type' => 'textfield',
      '#size' => 64,
      '#maxlength' => 32,
      '#title' => t('Add a new domain'),
      '#description' => t('Insert domain name'),
      '#prefix' => '<table style="width: 0px"><tr><td>',
      '#suffix' => '</td>',
    );
    $form['domain_type_form']['type'] = array(
      '#type' => 'hidden',
      '#value' => 'master',
    );
    $form['domain_type_form']['ipv4'] = array(
      '#type' => 'hidden',
      '#value' => $ip[ipv4],
    );
    $form['domain_type_form']['scope'] = array(
      '#type' => 'select',
      '#title' => t('Scope'),
      '#options' => array('internal' => 'internal', 'external' => 'external'),
      '#prefix' => '<td>',
      '#suffix' => '</td>',
    );
    $form['domain_type_form']['management'] = array(
      '#type' => 'select',
      '#title' => t('Management'),
      '#options' => array('automatic' => 'automatic', 'manual' => 'manual'),
      '#prefix' => '<td>',
      '#suffix' => '</td>',
    );
    $form['domain_type_form']['mname'] = array(
      '#type' => 'hidden',
      '#value' => '0',
    );
    $form['domain_type_form']['submit'] = array(
    '#type' => 'image_button',
    '#src' => drupal_get_path('module', 'guifi').'/icons/add.png',
    '#attributes' => array('title' => t('add')),
    '#executes_submit_callback' => TRUE,
    '#submit' => array(guifi_domain_create_form_submit),
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
    );
  }

  if ($form_state['values']['domain_type'] === 'delegation') {
    $ip = guifi_main_ip($node->device_id);
    $form['domain_type_form']['sid'] = array(
      '#type' => 'hidden',
       '#value' => $node->id
    );
    $form['domain_type_form']['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Add a new delegated Domain Name'),
      '#description' => t('Just the hostname (HOSTNAME.domain.com), master domain will be added before'),
      '#prefix' => '<table style="width: 0px"><tr><td>',
      '#suffix' => '</td>',
    );
    $domqry= db_query("
      SELECT *
      FROM {guifi_dns_domains}
      WHERE type = 'master'
      AND public = 'yes'
      ORDER BY name"
    );
    $values = array();
    while ($type = db_fetch_object($domqry)) {
      $values[$type->name] = $type->name;
    }
    $form['domain_type_form']['mname'] = array(
      '#type' => 'select',
      '#options' => $values,
      '#prefix' => '<td>',
      '#suffix' => '</td>',
    );
    $form['domain_type_form']['scope'] = array(
      '#type' => 'select',
      '#title' => t('Scope'),
      '#options' => array('internal' => 'internal', 'external' => 'external'),
      '#prefix' => '<td>',
      '#suffix' => '</td>',
    );
    $form['domain_type_form']['management'] = array(
      '#type' => 'select',
      '#title' => t('Management'),
      '#options' => array('automatic' => 'automatic', 'manual' => 'manual'),
      '#prefix' => '<td>',
      '#suffix' => '</td>',
    );
    $form['domain_type_form']['type'] = array(
      '#type' => 'hidden',
      '#value' => 'delegation',
    );
    $form['domain_type_form']['ipv4'] = array(
      '#type' => 'hidden',
      '#value' => $ip[ipv4],
    );
    $form['domain_type_form']['submit'] = array(
    '#type' => 'image_button',
    '#src' => drupal_get_path('module', 'guifi').'/icons/add.png',
    '#attributes' => array('title' => t('add')),
    '#executes_submit_callback' => TRUE,
    '#submit' => array(guifi_domain_create_form_submit),
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
    );
  }
  return $form;
}

function guifi_domain_create_form_submit($form, &$form_state) {

  $form_state['redirect'] =
    'guifi/domain/add/'.$form_state['values']['sid'].
    '/'.$form_state['values']['name'].
    '/'.$form_state['values']['type'].
    '/'.$form_state['values']['ipv4'].
    '/'.$form_state['values']['mname'].
    '/'.$form_state['values']['scope'].
    '/'.$form_state['values']['management'];
}

function guifi_domain_create($nid) {
  $form = drupal_get_form('guifi_domain_create_form',$nid);
  print theme('page',$form);
}


/****************************************
   domain output information functions
*****************************************/
/* guifi_domain_print_data(): outputs a detailed domain information data */
function guifi_domain_print_data($domain) {

  $rows[] = array($domain['name']);
  $rows[] = array(array('data' => theme_guifi_contacts($domain),'colspan' => 0));

  return array_merge($rows);
}


/* guifi_domain_print(): main print function, outputs the domain information and call the others */
function guifi_domain_print($domain = NULL) {
  if ($domain == NULL) {
    print theme('page',t('Not found'), FALSE);
    return;
  }

  $output = '<div id="guifi">';

  $node = node_load(array('nid' => $domain['sid']));
  $title = t('Servei:').' <a href="'.url('node/'.$node->nid).'">'.$node->nick;

  drupal_set_breadcrumb(guifi_node_ariadna($node));

  switch (arg(4)) {
  case 'all': case 'data': default:
    $table = theme('table', NULL, guifi_domain_print_data($domain));
    $output .= theme('box', $title, $table);
    if (arg(4) == 'data') break;
  case 'hosts':
    $header = array(t('HostName'),t('Alias'),t('Ip Address'),t('Namserver'),t('MailServer'),t('MX Priority'));
    $table = theme('table', $header, guifi_hosts_print_data($domain[id]));
    $output .= theme('box', t('Hostnames'), $table);
    break;
  }

  $output .= '</div>';

  drupal_set_title(t('View domain %dname',array('%dname' => $domain['name'])));
  $output .= theme_links(module_invoke_all('link', 'node', $node, FALSE));
  print theme('page',$output, FALSE);
  return;
}

function guifi_domain_item_delete_msg($msg) {
  return t($msg).'<br />'.
    t('Press "<b>Save</b>" to confirm deletion or ' .
      '"<b>Reset</b>" to discard changes and ' .
      'recover the values from the database.');
}

function guifi_domain_edit($domain) {
  $output = drupal_get_form('guifi_domain_form',$domain);

  // To gain space, save bandwith and CPU, omit blocks
  print theme('page', $output, FALSE);
}


?>