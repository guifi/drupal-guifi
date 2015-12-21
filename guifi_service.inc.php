<?php
/**
 * @file guifi_service.inc.php
 * Manage guifi_service
 */

/**
 * Implementation of hook_access().
 */
function guifi_service_access($op, $node) {
  global $user;

  if (is_numeric($node))
    $node = node_load($node);

  if ($op == 'create') {
    return user_access('create guifi nodes');
  }

  if ($op == 'update' or $op == 'delete') {
    if ((user_access('administer guifi zones')) || ($node->uid == $user->uid)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }
}

function guifi_service_access_callback($node) {
  $node = node_load($node);
  if ($node->type == 'guifi_service')
    return user_access('access content');
  else
    return FALSE;
}
/**
 * @todo Improve asserts in the beginning
 * @todo what object is expected to be $node?
 * @todo $node is over used :P

 * @return
 *   Object with the extra field ($node->var) and link to the node ($node->l)
 *   or FALSE if $node is not found in database
 */
function guifi_service_load($nodes) {

  foreach ($nodes as $node) {
    $service = db_query("SELECT * FROM {guifi_services} WHERE id = :nid", array(':nid' => $node->nid))->fetchObject();
    foreach ($service as $k => $value) {
      $nodes[$node->nid]->$k = $value;
      $nodes[$node->nid]->var = unserialize($service->extra);
      $nodes[$node->nid]->l = 'node/'.$service->id;
    }
  }
  return $nodes;
}

/**
 * Present the guifi zone editing form.
 */
function guifi_service_form($node, $param) {

  global $user;

  guifi_log(GUIFILOG_TRACE,'guifi_service_form()',$node);

  drupal_set_breadcrumb(guifi_zone_ariadna($node->zone_id,'node/%d/view/services'));


 // $f = guifi_form_hidden_var($node,array('id'));

  if ( (empty($node->nid)) and (is_numeric($node->title)) ) {
    $zone = node_load($node->title);
    $node->zone_id = $node->title;
    $node->contact = $user->mail;
    $default = t('<service>');
    $node->title = NULL;
    $node->nick = $zone->nick.$default;
    $node->status_flag = 'Planned';
  }

  if (isset($node->id))
  $f['id'] = array(
    '#type' => 'hidden',
    '#value' => $node->id
  );
  $type = db_query("SELECT description FROM {guifi_types} WHERE type='service' AND text = :type", array(':type' => $node->service_type))->fetchObject();
  if ($node->nid > 0)
    $f['service_type'] = array(
     '#type' => 'item',
     '#value' => t('Service type'),
     '#description' => t($type->description),
    );
    //$output = form_item(t('Service type'),$node->service_type,t($type->description));


  $type = node_type_get_type($node);

  if (($type->has_title)) {
    $f['title'] = array(
      '#type' => 'textfield',
      '#title' => check_plain($type->title_label),
      '#required' => TRUE,
      '#default_value' => $node->title,
    );
  }

  $f['nick'] = array(
    '#type' => 'textfield',
    '#title' => t('Nick'),
    '#required' => FALSE,
    '#size' => 20,
    '#maxlength' => 20,
    '#default_value' => $node->nick,
    '#element_validate' => array('guifi_service_nick_validate'),
    '#collapsible' => FALSE,
    '#tree'=> TRUE,
    '#description' => t("Unique identifier for this service. Avoid generic names such 'Disk Server', use something that really describes what is doing and how can be distinguished from the other similar services.<br />Short name, single word with no spaces, 7-bit chars only."),
    );

  //$output .= form_textfield(t("Nick"), "nick", $node->nick, 20, 20, t("Unique identifier for this service. Avoid generic names such 'Disk Server', use something that really describes what is doing and how can be distinguished from the other similar services.<br />Short name, single word with no spaces, 7-bit chars only.") . ($error['nick'] ? $error["nick"] : ''), NULL, TRUE);

  $f['notification'] = array(
    '#type' => 'textfield',
    '#title' => t('Contact'),
    '#required' => FALSE,
    '#size' => 60,
    '#maxlength' => 128,
    '#default_value' => $node->notification,
    '#element_validate' => array('guifi_emails_validate'),
    '#description' => t("Who did possible this service or who to contact with regarding this service if it is distinct of the owner of this page."),
  );

  //$output .= form_textfield(t("Contact"), "contact", $node->contact, 60, 128, t("Who did possible this service or who to contact with regarding this service if it is distinct of the owner of this page.") . ($error['contact'] ? $error["contact"] : ''));
////  $output .= form_select(t('Zone'), 'zone_id', $node->zone_id, guifi_zones_listbox(), t('The zone where this node where this node belongs to.'));

  $f['server'] = array(
    '#type' => 'textfield',
    '#title' => t("Device"),
    '#size' => 60,
    '#maxlength' => 128,
    '#default_value' => guifi_server_descr($node->device_id),
    '#element_validate' => array('guifi_servername_validate'),
    '#autocomplete_path'=> 'guifi/js/select-server',
    '#description' => t('Where it runs.'),
  );
  //$params .= guifi_form_column(form_select(t('Device'), "device_id", $node->device_id, guifi_servers_select(),t('Where it runs.')));
  if (!$node->nid) {
    $f['service_type'] = array(
      '#type' => 'select',
      '#title' => t("Service"),
      '#default_value' => $node->service_type,
      '#options' => guifi_types('service'),
      '#description' => t('Type of service'),
    );
    //$types = guifi_types('service');
    //array_shift($types);
    //$params.= guifi_form_column(form_select(t('Service'), "service_type", $node->service_type, $types,t('Type of service')));
  } else
    $f['protocol'] = array(
      '#type' => 'hidden',
      '#title' => t("service_type"),
      '#value' => $node->service_type,
    );
    //$output .= form_hidden("service_type",$node->service_type);

    $f['status_flag'] = array(
      '#type' => 'select',
      '#title' => t("Status"),
      '#default_value' => $node->status_flag,
      '#options' => guifi_types('status'),
      '#description' => t('Current status'),
    );
  //$params .= guifi_form_column(form_select(t('Status'), 'status_flag', $node->status_flag, guifi_types('status'), t('Current status')));
  //$output .= guifi_form_column_group(t('General parameters'),$params, NULL);
  // $node->var = unserialize($node->extra);

  if ($node->nid > 0)
  $f['var'] = array(
      '#type' => 'fieldset',
      '#title' => $node->service_type.' '.t("settings"),
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE
    );

  if ($node->nid > 0)
  switch ($node->service_type) {
    case 'mail':
      $f['var']['in'] = array(
        '#type' => 'textfield',
        '#title' => t('Inbound mail server'),
        '#required' => TRUE,
        '#size' => 60,
        '#maxlength' => 60,
        '#default_value' => $node->var['in'],
        //'#collapsible' => FALSE,
        //'#tree'=> TRUE,
        '#description' => t('Where email clients have to be configured for getting email messages')
      );
      $f['var']['out'] = array(
        '#type' => 'textfield',
        '#title' => t('Outbound mail server'),
        '#required' => TRUE,
        '#size' => 60,
        '#maxlength' => 60,
        '#default_value' => $node->var['out'],
        '#description' => t('Where email clients have to be configured for sending email messages')
      );
      $f['var']['webmail'] = array(
        '#type' => 'textfield',
        '#title' => t('Webmail url'),
        '#required' => TRUE,
        '#size' => 60,
        '#maxlength' => 60,
        '#default_value' => $node->var['webmail'],
        '#description' => t('URL for accessing to this mail server, if there is')
      );
      $f['var']['admin'] = array(
        '#type' => 'textfield',
        '#title' => t('Admin web interface'),
        '#required' => TRUE,
        '#size' => 60,
        '#maxlength' => 60,
        '#default_value' => $node->var['admin'],
        '#description' => t('Where to create/edit/delete users, change passwords, etc...')
      );
      break;
    case 'asterisk':
      $f['var']['prefix'] = array(
        '#type' => 'textfield',
        '#title' => t('Dial prefix'),
        '#required' => TRUE,
        '#size' => 60,
        '#maxlength' => 60,
        '#default_value' => $node->var['prefix'],
        '#description' => t('Dial prefix for calling this server extensions')
      );
      $f['var']['incoming'] = array(
        '#type' => 'textfield',
        '#title' => t('Incoming calls'),
        '#required' => TRUE,
        '#size' => 60,
        '#maxlength' => 60,
        '#default_value' => $node->var['incoming'],
        '#description' => t('Server or IP address where the calls have to be sent')
      );
      $f['var']['protocols'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Protocols'),
        '#required' => TRUE,
        '#default_value' => $node->var['protocols'],
        '#options' => array('IAX' => 'IAX','SIP' => 'SIP')
      );
      break;
    case 'NTP':
      $f['var']['ntp'] = array(
        '#type' => 'textfield',
        '#title' => t('IP address or hostname'),
        '#required' => TRUE,
        '#size' => 60,
        '#maxlength' => 60,
        '#default_value' => $node->var['ntp']
      );
      break;
    case 'ftp':
      $f['var']['ftphost'] = array(
        '#type' => 'textfield',
        '#title' => t('IP address or hostname'),
        '#required' => TRUE,
        '#size' => 60,
        '#maxlength' => 60,
        '#default_value' => $node->var['ftphost']
      );
      $f['var']['protocols'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Protocols'),
        '#required' => TRUE,
        '#default_value' => $node->var['protocols'],
        '#options' => array('SMB' => 'SMB (Samba)','ftp' => 'FTP','nfs' => 'NFS')
      );
      break;
    case 'Proxy': case 'ADSL':
      $f['var']['down'] = array(
        '#type' => 'select',
        '#title' => t('Download'),
        '#default_value' => $node->var['down'],
        '#options' => guifi_bandwidth_types(),
        '#description' => t('Download bandwidth')
      );
      $f['var']['up'] = array(
        '#type' => 'select',
        '#title' => t('Upload'),
        '#options' => guifi_bandwidth_types(),
        '#default_value' => $node->var['up'],
        '#description' => t('Upload bandwidth')
      );
      if ($node->service_type == 'ADSL')
        break;
      if (empty($node->var['fed']))
        $node->var['fed'] = array('IN' => '0', 'OUT' => '0' );
      $f['var']['fed'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Proxy federation'),
        '#default_value' => $node->var['fed'],
        '#options' => array('IN' => t('Allow login of users from OUT federated proxys'),'OUT' => t('Allow proxy users to use other IN federated proxys'))
      );
      $f['var']['proxy'] = array(
        '#type' => 'textfield',
        '#title' => t("Name"),
        '#default_value' => $node->var['proxy'],
        '#size' => 60,
        '#maxlength' => 60,
      );
      $f['var']['port'] = array(
        '#type' => 'textfield',
        '#title' => t("Port"),
        '#default_value' => $node->var['port'],
        '#size' => 60,
        '#maxlength' => 60,
      );
      $f['var']['type'] = array(
        '#type' => 'select',
        '#title' => t("Type"),
        '#default_value' => $node->var['type'],
        '#options' => array('HTTP' => 'HTTP','Socks4' => 'SOCKS4','Socks5' => 'SOCKS5','arp' => 'ARP','ftp' => 'FTP')
      );
      break;
    case 'SNPgraphs':
      $f['var']['version'] = array(
        '#type' => 'select',
        '#title' => t('version'),
        '#default_value'=> $node->var['version'],
        '#options' => drupal_map_assoc(array('1.0','2.0')),
        '#description' => t('version of the CNML services'),
      );
      $f['var']['url'] = array(
        '#type' => 'textfield',
        '#title' => t('url'),
        '#size' => 60,
        '#maxlength' => 250,
        '#default_value' => $node->var['url'],
        '#description' => t('Base url to call CNML services'),
      );
      break;
    default:
      $f['var']['url'] = array(
        '#type' => 'textfield',
        '#title' => t('url'),
        '#size' => 60,
        '#maxlength' => 250,
        '#default_value' => $node->var['url'],
      );
      break;
  }


  // multiple fields
  $delta = 0;
  if ($node->nid > 0)
  switch ($node->service_type) {
  case 'mail':
    $f['var']['domains'] =
      guifi_service_multiplefield($node->var['domains'],'domains',
        t('Managed domains'));
    break;
  case 'web':
    $f['var']['homepages'] =
      guifi_service_multiplefield($node->var['homepages'],'homepages',
        t('URL pointing to the website homepage'));
    break;
  case 'irc':
    $f['var']['irc'] =
      guifi_service_multiplefield($node->var['irc'],'irc',
        t('IRC server hostname'));
    break;
  }

  if (($type->has_body)) {
    $f['body_field'] = node_body_field(
      $node,
      $type->body_label,
      $type->min_word_count
    );
  }

  return $f;
}

function guifi_service_multiplefield($field, $fname, $descr) {
  $f = array(
    '#type' => 'fieldset',
    '#tree' => TRUE,
    '#collapsible' => FALSE,
    '#title' => $descr,
//    '#prefix' => '<div id="mfield-'.$fname.'">',
//    '#suffix' => '</div>',
    '#description' => t('Save or press "Preview" to get more entries')
  );
  if (count($field))
    foreach ($field as $delta => $value)
    if (!empty($value))
      $f[] = array(
        '#type' => 'textfield',
        '#size' => 60,
        '#maxlength' => 60,
        '#default_value' => $value,
      );
  for ($i = 0; $i < 2; $i++)
    $f[] = array(
      '#type' => 'textfield',
      '#size' => 60,
      '#maxlength' => 60,
    );

  return $f;
}

function guifi_service_nick_validate($element, &$form_state) {
  if (empty($element['#value'])) {
    $nick = guifi_abbreviate($form_state['values']['title']);
    drupal_set_message(t('Service nick has been set to:').' '.$nick);
    $form_state['values']['nick'] = $nick;

    return;
  }
  guifi_validate_nick($element['#value']);

  $query = db_query("SELECT nick FROM {guifi_services} WHERE lcase(nick)=:nick AND id <> :id",
    array(':nick' => strtolower($element['#value']), ':id' => $form_state['values']['nid']));
  if ($query->fetchField()){
    form_set_error('nick', t('Nick already in use.'));
  }
}

function guifi_service_str($id, $emptystr = 'Take from parents') {
  if (empty($id))
    return t($emptystr);
  if ($id == -1)
    return t('No service');

  // there is a value, create the string
  $proxy = node_load($id);
  $proxystr = $id.'-'.
    guifi_get_zone_name($proxy->zone_id).', '.
    $proxy->nick;

  return $proxystr;
}

function guifi_service_url($id) {
  if (empty($id))
    // get from parents

  return l(guifi_service_str($id),
     'node/'.$id);
}

function guifi_service_name_validate($nodestr,&$form_state) {
  if ($form_state['clicked_button']['#value'] == t('Reset'))
    return;

  if ($nodestr['#value'] == t('No service') or
     ($nodestr['#value'] == t('Take from parents')))
    return;

  $nid = explode('-',$nodestr['#value']);
  $qry = db_query('SELECT id FROM {guifi_services} WHERE id = :id',array(':id' => $nid[0]));
  foreach ($qry->fetchObject() as $node)
    return $nodestr;

  form_error($nodestr,
    t('Service %name not valid.',array('%name' => $nodestr['#value'])),'error');

  return $nodestr;
}

/**
 * Save changes to a guifi item into the database.
 */
function guifi_service_insert($node) {
  global $user;
  $log = '';
  $to_mail = explode(',',$node->notification);
  $node->extra = serialize($node->var);

  guifi_log(GUIFILOG_TRACE,'function guifi_service_insert()',$node);

  $node->new = TRUE;
  $node->id   = $node->nid;
  $nnode = _guifi_db_sql(
    'guifi_services',
    array('id' => $node->id),
    (array)$node,
    $log,$to_mail);
  guifi_notify(
    $to_mail,
    t('The service %name has been CREATED by %user.',array('%name' => $node->nick, '%user' => $user->name)),
    $log);

  //db_query("INSERT INTO {guifi_services} ( id, zone_id, nick, service_type, device_id, contact, status_flag, extra, timestamp_created, user_created) VALUES (%d, %d, '%s', '%s', %d, '%s', '%s', '%s', %d, %d)", $node->nid, $node->zone_id, $node->nick, $node->service_type, $node->device_id, $node->contact, $node->status_flag, serialize($node->var), time(), $user->uid);

// Refresh maps?
}

function guifi_service_delete(&$node) {
  global $user;
  $log = '';

  $delete = TRUE;

  $to = explode(',',$node->notification);
  $to[] = variable_get('guifi_contact','webmestre@guifi.net');

  // perform deletion
  $node->deleted = TRUE;
  $nzone = _guifi_db_sql(
    'guifi_services',
    array('id' => $node->id),
    (array)$node,
    $log,
    $to);
  guifi_notify(
    $to,
    t('Service %nick has been deleted by %user',
      array('%nick' => $node->nick,'%user' => $user->name)),
    $log);
  return;
}

function guifi_service_multiplefield_clean(&$mfield) {
  if (!empty($mfield))
    foreach($mfield as $k => $value)
      if (empty($value))
        unset($mfield[$k]);
}

function guifi_service_update($node) {
  global $user;
  $log = '';
  $to_mail = explode(',',$node->notification);

  guifi_log(GUIFILOG_TRACE,'function guifi_service_update()',$node);

  guifi_service_multiplefield_clean($node->var['domains']);
  guifi_service_multiplefield_clean($node->var['homepages']);
  guifi_service_multiplefield_clean($node->var['irc']);

  $node->extra = serialize($node->var);

  $nnode = _guifi_db_sql(
    'guifi_services',
    array('id' => $node->id),
    (array)$node,
    $log,$to_mail);
  guifi_notify(
    $to_mail,
    t('The service %name has been UPDATED by %user.',array('%name' => $node->nick, '%user' => $user->name)),
    $log);
}

/**
 * outputs the zone information data
**/
function theme_guifi_service_data($node) {

  if (empty($node->nid))
    $node = node_load($node);

  guifi_log(GUIFILOG_TRACE,'guifi_service_print_data()',$node);

  $zone         = db_query('SELECT title FROM {guifi_zone} WHERE id = :zid', array(':zid' => $node->zone_id))->fetchObject();
  $type         = db_query('SELECT description FROM {guifi_types} WHERE type=\'service\' AND text = :text', array(':text' => $node->service_type))->fetchObject();

  $rows[] = array(t('service'),$node->nid .'-' .$node->nick,'<b>' .$node->title .'</b>');
  $rows[] = array(t('type'),$node->service_type,t($type->description));
  if ($node->device_id > 0) {
    $device = db_query('SELECT nick FROM {guifi_devices} WHERE id = :did', array(':did' => $node->device_id));
    $url = url('guifi/device/'.$node->device_id);
    $rows[] = array(t('device & status'),'<a href='.$url.'>'.$device->nick.'</a>',
              array('data' => t($node->status_flag),'class' => $node->status_flag));
  }

  $node->var = unserialize($node->extra);
  switch ($node->service_type) {
    case 'mail':
      $rows[] = array(t('inbound and outbound servers'),$node->var['in'],$node->var['out']);
      $rows[] = array(t('webmail and admin url'),guifi_url($node->var['webmail']),guifi_url($node->var['admin']));
      break;
    case 'Proxy': case 'ADSL':
      $rows[] = array(t('bandwidth (Down/Up)'),$node->var['down'],$node->var['up']);
      $rows[] = array(t('proxy name &#038; port'),$node->var['proxy'],$node->var['port']);
      $rows[] = array(t('type'),$node->var['type'], NULL);
      if (is_array($node->var['fed'])) $rows[] = array(t('federation'),implode(", ",$node->var['fed']), NULL);
      else $rows[] = array(t('federation'),t('This proxy is not federated yet'), NULL);
      break;
    case 'ftp':
      $rows[] = array(t('ftphost'),$node->var['ftphost'], NULL);
      $rows[] = array(t('supported protocols'),implode(", ",$node->var['protocols']), NULL);
      break;
    case 'ntp':
      $rows[] = array(t('IP address or hostname'),$node->var['ntp'], NULL);
      break;
    case 'asterisk':
      $rows[] = array(t('dial prefix and incoming calls'),$node->var['prefix'],$node->var['incoming']);
      if (isset($node->var['protocols']))
        $rows[] = array(t('supported protocols'),implode(", ",$node->var['protocols']), NULL);
      break;
    default:
      if (!empty($node->var['url'])) {
        if (preg_match('/^http:\/\//',$node->var[url]))
          $url = $node->var[url];
        else
          $url = 'http://'.$node->var[url];
        $rows[] = array(t('url'),'<a href="'.$url.'">'.$node->var['url'].'</a>', NULL);
      }
      break;
  }

  if (isset($node->var['homepages']))
  if (count($node->var['homepages'] > 0)) {
    $rows[] = array(t('homepages'), NULL, NULL);
    foreach ($node->var['homepages'] as $homepage) {
      if (preg_match('/^http:\/\//',$homepage))
        $url = $homepage;
      else
        $url = 'http://'.$homepage;
      $rows[] = array(NULL,'<a href='.$url.'>'.$homepage.'</a>', NULL);
    }
  }

  if (isset($node->var['ircs']))
  if (count($node->var['ircs'] > 0)) {
    $rows[] = array(t('ircs'), NULL, NULL);
    foreach ($node->var['ircs'] as $irc)
      $rows[] = array(NULL,$irc, NULL);
  }

  if (isset($node->var['domains']))
  if (count($node->var['domains'] > 0)) {
    $rows[] = array(t('domains'), NULL, NULL);
    foreach ($node->var['domains'] as $domain)
      $rows[] = array(NULL,$domain, NULL);
  }

  $output = theme('table', array('header' => NULL, 'rows' => $rows));
  $output .= theme_guifi_contacts($node);


  drupal_set_breadcrumb(guifi_location_ariadna($service));

  return $output;
}

function guifi_list_services_query($param, $typestr = 'by zone', $service = '%') {

  $rows = array();
  $sqlprefix =
    "SELECT s.*,z.title zonename " .
    "FROM {guifi_services} s " .
    "  LEFT JOIN {guifi_devices} d ON s.device_id = d.id " .
    "  LEFT JOIN {guifi_zone} z ON s.zone_id = z.id " .
    "  LEFT JOIN {guifi_location} l ON d.nid = l.id " .
    "WHERE ";
  switch ($typestr) {
    case t('by zone'):
      $childs = guifi_zone_childs($param->id);
      $sqlwhere = 's.zone_id IN (:value) ';
      $value = $childs;
      break;
    case t('by node'):
      $sqlwhere = 'd.nid = :value ';
      $value = $param->nid;
      break;
    case t('by device'):
      $sqlwhere = 'd.id = :value ';
      $value = $param;
      break;
  }
  $query = db_query($sqlprefix.$sqlwhere.' ORDER BY s.service_type, s.zone_id, s.nick', array(':value' => $value));

  $current_service = '';
  while ($service = $query->fetchObject()) {
    $node = node_load($service->id);
    $node_srv = node_load($service->id);
    
    if ($current_service != $service->service_type) {
      $typedescr = db_query("SELECT * FROM {guifi_types} WHERE type='service' AND text = :text", array(':text' => $service->service_type))->fetchObject();
      $rows[] = array('<strong>'.t($typedescr->description).'</strong>', NULL, NULL, NULL,NULL);
      $current_service = $service->service_type;
    }

    $status_url = guifi_cnml_availability(
       array('device' => $service->device_id,'format' => 'short'));
    
    $rows[] = array('<a href="' .base_path() .'node/'.$service->id.'">'.$node->title.'</a>',
                    '<a href="' .base_path() .'node/'.$service->zone_id.'">'.$service->zonename.'</a>',
                    '<a href="' .base_path() .'guifi/device/'.$service->device_id.'">'.guifi_get_hostname($service->device_id).'</a>',
                    array('data' => t($node_srv->status_flag),'class' => $node_srv->status_flag),
                    $status_url,);
  }

  return array_merge($rows);
}

/*
 * guifi_list_services
 */
function theme_guifi_services_list($node, $service = '%') {

  if (empty($node->id))
    $node = node_load($node);

  if (is_numeric($node)) {
    $typestr = t('by device');
  } else {
    if ($node->type == 'guifi_location')
      $typestr = t('by node');
    else
      $typestr = t('by zone');
  }

  $rows = guifi_list_services_query($node,$typestr);

  ($rows) ?
     $box .= theme('table', array('header' =>
       array(t('service'),t('zone'),t('device'),t('status'), t('availability')),
       'rows' => array_merge($rows),
       'attributes' => array('width' => '100%')))
     : $box .= t('There are no services defined at the database');

    $table = theme('table',array(
    'header' => array(t('Services of @node (@by)',array('@node' => $node->title,'@by' => $typestr))),
    'rows' => array(array($box)),
    'attributes' => array('width' => '100%')));

  switch ($typestr) {
    case t('by node'):
      drupal_set_title(t('services @ %node',array('%node' => $node->title)));
      drupal_set_breadcrumb(guifi_location_ariadna($node,'node/%d/view/services'));
      $output .= theme_links(module_invoke_all('link', 'node', $node, FALSE));
      break;
    case t('by zone'):
      drupal_set_breadcrumb(guifi_zone_ariadna($node->id,'node/%d/view/services'));
      $output .= theme_links(module_invoke_all('link', 'node', $node, FALSE));
      break;
    case t('by device'):
      $device = guifi_device_load($node);
      drupal_set_title(t('View device %dname',
        array('%dname' => $device['nick'],
              '%nid' => $device['nid'])));
      $node = node_load($device['nid']);
      drupal_set_breadcrumb(guifi_location_ariadna($node));
      $output .= theme_links(module_invoke_all('link', 'node', $node, FALSE));
      break;
  }

  $output .= '<div>' . $table . '</div>';
  return $output;
}
/**
 * outputs the node information
**/
function guifi_service_view($node, $view_mode, $langcode = NULL) {

  if ($view_mode == 'teaser')
    return $node;
  if ($view_mode == 'bloc')
    return $node;

  drupal_set_breadcrumb(guifi_zone_ariadna($node->zone_id,'node/%d/view/services'));
  if ($view_mode == 'full') {

  $node->content['title'] = array(
    '#type' => 'markup',
    '#weight' => 0,
    '#markup' => theme('table',
                  array('header' => array(t('Description')), 
                        'rows' => NULL,
                        'attributes' => array('width' => '100%')
                        )
                  )
  );
  
  $service_data = theme('table',
                  array('header' => array(t('service information')), 
                        'rows' => array(
                          array(
                            array(
                              'data' => theme_guifi_service_data($node),
                              'width' => '100%'),
                          )
                        ),
                        'attributes' => array('width' => '100%')
                        )
                  );



    if ($node->service_type == 'DNS') {
      $form = drupal_render(drupal_get_form('guifi_domain_create_form',$node));
      $id = $node->id;
      $rows = array();
      $header = array( '<h2>'.t('Domain').'</h2>', array('data' => t('type'),'style' => 'text-align: left;'), array('data' => t('Scope')));
      $query = db_query("SELECT d.id FROM {guifi_dns_domains} d WHERE sid = :sid", array(':sid' => $id));
        while ($d = $query->fetchObject()) {
          $domain = guifi_domain_load($d->id);
          if (guifi_domain_access('update',$domain['id'])) {
            $edit_domain = l(guifi_img_icon('edit.png'),'guifi/domain/'.$domain['id'].'/edit',
            array(
              'html' => TRUE,
              'title' => t('edit domain'),
              'attributes' => array('target' => '_blank'))).'</td><td>'.
                 l(guifi_img_icon('drop.png'),'guifi/domain/'.$domain['id'].'/delete',
            array(
              'html' => TRUE,
              'title' => t('delete domain'),
              'attributes' => array('target' => '_blank')));
          }

          $rows[] = array(
            '<a href="'.url('guifi/domain/'.$domain[id]).'">'.$domain['name'].'</a>',
            array('data' => $domain['type'],'style' => 'text-align: left;'),
            array('data' => $domain['scope']),
            $edit_domain,
          );
        }
        if (count($rows)) {
          $node->content['data'] = array(
                '#type' => 'markup',
                '#weight' => 2,
                '#markup' => $service_data.theme('table', array('header' => $header, 'rows' => $rows)).$form
                );
        }
        else {
          $node->content['data'] = array(
            '#type' => 'markup',
            '#weight' => 2,
            '#markup' => $service_data.$form,
          );
        }
    }
    else {
  $node->content['data'] = array(
    '#type' => 'markup',
    '#weight' => 2,
    '#markup' => $service_data,
  );
    }
  }

  return $node;
}

function dump_guifi_proxy_federation($node) {

  $qryself=db_query("SELECT id,extra FROM {guifi_services} WHERE service_type = 'Proxy' AND id = :id AND (status_flag = 'Working' OR status_flag = 'Testing') ORDER BY id", array(':id' => $node->id));
  $qryothers = db_query("SELECT id,extra FROM {guifi_services} WHERE service_type = 'Proxy' AND id != :id AND (status_flag = 'Working' OR status_flag = 'Testing') ORDER BY id", array(':id' => $node->id));

  $ownproxy = $qryself->fetchAssoc();
  $extra=unserialize($ownproxy['extra']);
  $in = $extra['fed']['IN'];
  $out = $extra['fed']['OUT'];
  $in = strtolower(($in == '0')?'':$in);
  $out = strtolower(($out == '0')?'':$out);
  $inout = ($in.$out == '')?'private':$in.$out;
  if (!empty($ownproxy['id']))
    $head = $ownproxy['id']."-".$inout."\n";
  else
   $head = "0000-private\n";
  while ( ($row = $qryothers->fetchAssoc() ) != null) {
    unset($extra);
    $extra=unserialize($row['extra']);
    $in = $extra['fed']['IN'];
    $out = $extra['fed']['OUT'];
    $in = strtolower(($in == '0')?'':$in);
    $out = strtolower(($out == '0')?'':$out);
    $inout = ($in.$out == '')?'private':$in.$out;
    $head .= $row['id']."-".$inout."\n";
      }
  echo $head;
}

?>
