<?php
/*
 * Created on 16/08/2008 by rroca
 *
 * functions for various tools
 */

function guifi_tools_ip_search($ipv4 = NULL) {

  $output = drupal_get_form('guifi_tools_ip_search_form',$ipv4);

  if (is_null($ipv4))
    return $output;

  $output .= '<h2>'.t('Query result for "ipv4 LIKE %ipv4"',
    array('%ipv4' => "'".$ipv4."'")).'</h2>';

  $headers = array(t('id'),
    array('data' => t('nipv4')),
    t('mask'),t('interface'),t('device'),t('node'));
  $sql = 'SELECT *,inet_aton(ipv4) AS nipv4 FROM {guifi_ipv4} WHERE ipv4 LIKE "%s" ORDER BY inet_aton(ipv4)';
  $sqla = pager_query($sql,50,0,NULL,$ipv4);
  while ($ipv4 = db_fetch_object($sqla)) {
    $row = array();
    $row[] = $ipv4->id.'/'.$ipv4->interface_id;
    $row[] = $ipv4->ipv4;
    $row[] = $ipv4->netmask;

    // interface
    if ($interface = db_fetch_object(db_query(
         'SELECT * from {guifi_interfaces} WHERE id=%d',
         $ipv4->interface_id))) {
      $row[] = $interface->id.'/'.$interface->radiodev_counter.' '.
        $interface->interface_type;
    } else {
      $row[] = t('Orphan');
      $rows[] = $row;
      continue;
    }

    // device
    if ($device = db_fetch_object(db_query(
         'SELECT * from {guifi_devices} WHERE id=%d',
         $interface->device_id))) {
      $row[] = $device->id.'-'.
        l($device->nick,'guifi/device/'.$device->id);
    } else {
      $row[] = t('Orphan');
      $rows[] = $row;
      continue;
    }

    // node
    if ($node = db_fetch_object(db_query(
         'SELECT id from {guifi_location} WHERE id=%d',
         $device->nid))) {
      $node = node_load(array('nid' => $node->id));
      $row[] = $node->id.'-'.
        l($node->title,'node/'.$node->id);
    } else {
      $row[] = t('Orphan');
      $rows[] = $row;
      continue;
    }

    $rows[] = $row;
  }

  $output .= theme('table',$headers,$rows);
  $output .= theme_pager(NULL, 50);
  return $output;
}

// IP search
function guifi_tools_ip_search_form($form_state, $params = array()) {

  $form['ipv4'] = array(
    '#type' => 'textfield',
    '#title' => t('Network IPv4 address'),
    '#required' => TRUE,
    '#default_value' => $params,
    '#size' => 16,
    '#maxlength' => 16,
    '#description' => t('Enter a valid ipv4 network address or pattern ' .
        'to get the related information available at the database for it.<br />' .
        'You can use valid SQL wilcards (%), for example, to query all the ' .
        'addresses begining with "10.138.0" you can use "10.138.0%"...'),
    '#weight' => 0,
  );
  $form['submit'] = array('#type' => 'submit','#value' => t('Get information'));

  return $form;
}

function guifi_tools_ip_search_form_submit($form, &$form_state) {
   drupal_goto('guifi/menu/ip/ipsearch/'.urlencode($form_state['values']['ipv4']));
   return;
}

// MAC Search
function guifi_tools_mac_search($mac = NULL) {
  $output = drupal_get_form('guifi_tools_mac_search_form',$mac);

  if (is_null($mac))
    return $output;

  $output .= '<h2>'.t('Query result for "mac LIKE %mac"',
    array('%ipv4' => "'".$mac."'")).'</h2>';

  $headers = array(t('mac'),t('interface'),t('device'),t('node'));
  $sqlm = pager_query('SELECT * FROM {guifi_interfaces} WHERE mac LIKE "%s"',50,0,NULL,$mac);
  while ($interface = db_fetch_object($sqlm)) {
    $row = array();
    $row[] = $interface->mac;
    $row[] = $interface->id.'/'.$interface->radiodev_counter.' '.
      $interface->interface_type;

    // device
    if ($device = db_fetch_object(db_query(
         'SELECT * from {guifi_devices} WHERE id=%d',
         $interface->device_id))) {
      $row[] = $device->id.'-'.
        l($device->nick,'guifi/device/'.$device->id);
    } else {
      $row[] = t('Orphan');
      $rows[] = $row;
      continue;
    }

    // node
    if ($node = db_fetch_object(db_query(
         'SELECT id from {guifi_location} WHERE id=%d',
         $device->nid))) {
      $node = node_load(array('nid' => $node->id));
      $row[] = $node->id.'-'.
        l($node->title,'node/'.$node->id);
    } else {
      $row[] = t('Orphan');
      $rows[] = $row;
      continue;
    }

    $rows[] = $row;
  }

  $output .= theme('table',$headers,$rows);
  $output .= theme_pager(NULL, 50);
  return $output;
}

function guifi_tools_mac_search_form($form_state, $params = array()) {

  $form['mac'] = array(
    '#type' => 'textfield',
    '#title' => t('MAC address'),
    '#required' => TRUE,
    '#default_value' => $params,
    '#size' => 20,
    '#maxlength' => 20,
    '#description' => t('Enter a valid MAC address or pattern ' .
        'to get the related information available at the database for it.<br />' .
        'You can use valid SQL wilcards (%), for example, to query all the MAC ' .
        'addresses begining with "00:0B" you can use "00:0B%"...'),
    '#weight' => 0,
  );
  $form['submit'] = array('#type' => 'submit','#value' => t('Get information'));

  return $form;
}

function guifi_tools_mac_search_form_submit($form, &$form_state) {
   drupal_goto('guifi/menu/ip/macsearch/'.$form_state['values']['mac']);
   return;
}

function guifi_tools_ip_rangesearch($params = NULL) {

  $output .=  drupal_get_form('guifi_tools_ip_rangesearch_form',$params);

  if (empty($params))
    return $output;

  // for testing, load a device with quite a few ip's'
  // $device = guifi_device_load(115);

  $tgetipsbegin = microtime(TRUE);

  $ips_allocated = guifi_ipcalc_get_ips('0.0.0.0','0.0.0.0');

  $tgetipsend = microtime(TRUE);

  $toutput = t('Got & sorted %num ips in %secs seconds',
    array('%num' => number_format(count($ips_allocated)),
          '%secs' => number_format($tgetipsend-$tgetipsbegin,4))).
    '<br />';

  list($mask,$network_type,$zone_id,$allocate) = explode(',',$params);

  if (!user_access('administer guifi networks'))
    $allocate = 'No';

  $net = guifi_ipcalc_get_subnet_by_nid($zone_id,
            $mask,
            $network_type,
            $ips_allocated,
            $allocate,   // never allocate the obatined range at guifi_networks
            TRUE);   // verbose output

  $tgetsubnetbynid = microtime(TRUE);

  $toutput .= t('Got %base/%net in %secs seconds',
    array('%base' => $net,
          '%net' => $mask,
          '%secs' => number_format($tgetsubnetbynid-$tgetipsend,4))).
    '<br />';
  $toutput .= t('Total elapsed was %secs seconds',
    array('%secs' => number_format($tgetsubnetbynid-$tgetipsbegin,4))).
    '<br />';

  $item=_ipcalc($net,$mask);
  if ($net) {
    foreach ($item as $k => $value) {
      $header[] = t($k);
      $row[] = $value;
    }
    $qoutput .= theme('box',
      t('Space found at %net',array('%net' => $net)),
      theme('table',$header,array($row)));
  } else
    drupal_set_message(t('Was not possible to find %type space for %mask',
      array('%type' => $network_type,
        '%mask' => $mask)),
      'error');

  return $qoutput.
         theme('box',t('Find available space for a subnetwork'),$output).
         theme('box',t('Performance'),'<small>'.$toutput.'</small>');
}

// IP search
function guifi_tools_ip_rangesearch_form($form_state, $params = array()) {

  if (empty($params)) {
    $mask = '255.255.255.224';
    $network_type = 'public';
    $zone_id = guifi_zone_root();
  } else
    list($mask,$network_type,$zone_id,$allocate) = explode(',',$params);

  $form['mask'] = array(
    '#type' => 'select',
    '#title' => t("Mask"),
    '#required' => TRUE,
    '#default_value' => $mask,
    '#options' => guifi_types('netmask',30,0),
    '#description' => t('The mask of the network to search for. The number of the available hosts of each masks is displayed in the list box.'),
  );
  $form['network_type'] = array(
    '#type' => 'select',
    '#title' => t("Type"),
    '#required' => TRUE,
    '#default_value' => $network_type,
    '#options' => drupal_map_assoc(array('public','backbone')),
    '#description' => t('The type of network addresses you are looking for. <ul><li><em>public:</em> is for addresses which will allow the users connect to the services, therefore must be unique across all the network and assigned with care for not being wasted.</li><li><em>backbone:</em> internal addresses for network operation, could be shared across distinct network segments, do not neet to be known as a service address to the users</li></ul>'),
  );
  $form['zone_id'] = guifi_zone_select_field($zone_id,'zone_id');
  $form['allocate'] = array(
    '#type' => 'select',
    '#title' => t("Allocate"),
    '#required' => TRUE,
    '#access' => user_access('administer guifi networks'),
    '#default_value' => 'No',
    '#options' => drupal_map_assoc(array('Yes','No')),
    '#description' => t('If yes, the network found will be allocated at the database being assigned to the zone'),
  );

  $form['submit'] = array('#type' => 'submit','#value' => t('Find space for the subnetwork'));

  return $form;
}

function guifi_tools_ip_rangesearch_form_submit($form, $form_state) {
   drupal_goto('guifi/menu/ip/networksearch/'.
     $form_state['values']['mask'].','.
     $form_state['values']['network_type'].','.
     $form_state['values']['zone_id'].','.
     $form_state['values']['allocate']
   );
   return;
}


// Mail search & massive update
function guifi_tools_mail_search($mail = NULL) {

  $output = drupal_get_form('guifi_tools_mail_search_form',$mail);

  // if a vaild mail has given, allow massive update
  if ((!empty($mail)) and (valid_email_address($mail)))
    $output .= drupal_get_form('guifi_tools_mail_update_form',$mail);

  // Close the form table
  $output .= '</table></table>';

  if (is_null($mail))
    return $output;

  $output .= '<h2>'.t('Report for notification having LIKE "%mail"',
    array('%mail' => "'".$mail."'")).'</h2>';

  $headers = array(t('table'),t('notification'),t('title'));

  $tables = array('guifi_zone','guifi_location','guifi_devices','guifi_services','guifi_users');

  foreach ($tables as $table) {
    $sqlm = db_query('SELECT * FROM {%s} WHERE notification LIKE "%s"',$table,$mail);
    while ($amails = db_fetch_object($sqlm)) {
      $row = array();
      $row[] = $table;
      $row[] = $amails->notification;

      // Check that the user has update access and creates the link
      $continue = FALSE;
      if (!user_access('administer guifi networks'))
        switch ($table) {
          case 'guifi_users':
            if (guifi_user_access('update',$amails->id))
              $continue = TRUE;
            break;
          case 'guifi_devices':
            if (guifi_device_access('update',$amails->id))
              $continue = TRUE;
            break;
          case 'guifi_zone':
            if (guifi_zone_access('update',$amails->id))
              $continue = TRUE;
            break;
          case 'guifi_location':
            if (guifi_node_access('update',$amails->id))
              $continue = TRUE;
            break;
          case 'guifi_service':
            if (guifi_service_access('update',$amails->id))
              $continue = TRUE;
            break;
        } else
        $continue = TRUE;

      if (!$continue)
        continue;

      switch ($table) {
        case 'guifi_users':
          $row[] = l($amails->username,'guifi/user/'.$amails->id.'/edit');
          break;
        case 'guifi_devices':
          $row[] = l($amails->nick,'guifi/device/'.$amails->id.'/edit');
          break;
        default:
          $row[] = l($amails->nick,'node/'.$amails->id.'/edit');
      }

      $rows[] = $row;
    } // foreach row with the email found

  } // foreach table

  if (count($rows))
    $output .= theme('table',$headers,$rows);
  return $output;
}

function guifi_tools_mail_search_form($form_state, $params = array()) {

//  $form['submit'] = array(
//    '#type' => 'submit',
//    '#value' => t('Search'),
//    '#prefix'=> '<table><tr><td align="right">',
//    '#suffix'=> '</td>',
//  );
  $form['mail'] = array(
    '#type' => 'textfield',
    '#title' => t('e-mail address'),
    '#required' => TRUE,
    '#default_value' => $params,
    '#size' => 50,
    '#maxlength' => 50,
    '#description' => t('Enter a valid e-mail address to look for ' .
        'to get a report of where it appears in all tables.' .
        '<br />' .
        'You can use valid SQL wilcards (%), for example, to query all the mail ' .
        'addresses containing "guifi" you can use "%guifi%"...<br />' .
        'Note that:<ul><li>If you use wildcards, massive update option ' .
        'will not be enabled</li><li>You will get a list restricted to the items ' .
        'which you are granted to update</li></ul>'),
     '#prefix'=> '<table><tr><td>',
     '#suffix'=> '</td>',
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Search'),
    '#prefix'=> '<td align="left">',
    '#suffix'=> '</td></tr>',
  );
  return $form;
}

function guifi_tools_mail_update_form($form_state, $params = array()) {

  $form['mail_search'] = array(
    '#type' => 'value',
    '#value' => $params);
//  $form['submit'] = array(
//    '#type' => 'submit',
//    '#value' => t('Replace with'),
//    '#prefix'=> '<tr><td align="right">',
//    '#suffix'=> '</td>',
//  );
  $form['mail_replacewith'] = array(
    '#type' => 'textfield',
    '#title' => t('New e-mail address'),
    '#required' => FALSE,
    '#default_value' => $params,
    '#size' => 50,
    '#maxlength' => 50,
    '#description' => t('Enter a valid e-mail address to replace %mail for ' .
        'all the rows of the report below.',
        array('%mail' => $params)),
    '#prefix'=> '<td>',
    '#suffix'=> '</td>',
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Replace with'),
    '#prefix'=> '<td align="left">',
    '#suffix'=> '</td></tr>',
  );

  return $form;
}

function guifi_tools_mail_update_form_validate($form, &$form_state) {
  if (!valid_email_address($form_state['values']['mail_replacewith']))
    form_set_error('mail_replacewith',
      t('%email is not valid',
        array('%email' => $form_state['values']['mail_replacewith'])));
  if ($form_state['values']['mail_search'] ==
    $form_state['values']['mail_replacewith'])
    form_set_error('mail_replacewith',
      t('%email is equal to current value',
        array('%email' => $form_state['values']['mail_replacewith'])));
}

function guifi_tools_mail_search_form_submit($form, &$form_state) {
  drupal_goto('guifi/menu/ip/mailsearch/'.$form_state['values']['mail']);
}

function guifi_tools_mail_update_form_submit($form, &$form_state) {
  global $user;

  guifi_log(GUIFILOG_TRACE,'guifi_tools_mail_update_submit()',
    $form_state['values']);

  // perform the massive update to the granted rows, using guifi db api
  // instead of straight SQL to create the notificaton messages.

  $tables = array('guifi_zone','guifi_location','guifi_devices','guifi_services','guifi_users');

  foreach ($tables as $table) {
    $sqlm = db_query('SELECT * FROM {%s} WHERE notification LIKE "%s"',
      $table,
      $form_state['values']['mail_search']);

    while ($amails = db_fetch_object($sqlm)) {
      // Check that the user has update access and creates the link
      $continue = FALSE;
      if (!user_access('administer guifi networks'))
        switch ($table) {
          case 'guifi_users':
            $title = $amails->username;
            $type = t('User');
            if (guifi_user_access('update',$amails->id))
              $continue = TRUE;
            break;
          case 'guifi_devices':
            $title = $amails->nick;
            $type = t('Device');
            if (guifi_device_access('update',$amails->id))
              $continue = TRUE;
            break;
          case 'guifi_zone':
            $title = $amails->nick;
            $type = t('Zone');
            if (guifi_zone_access('update',$amails->id))
              $continue = TRUE;
            break;
          case 'guifi_location':
            $title = $amails->nick;
            $type = t('Node');
            if (guifi_node_access('update',$amails->id))
              $continue = TRUE;
            break;
          case 'guifi_service':
            $title = $amails->nick;
            $type = t('Service');
            if (guifi_service_access('update',$amails->id))
              $continue = TRUE;
            break;
        } else
        $continue = TRUE;

      if (!$continue)
        continue;

      // here we have update access, so perform the update

      // Notify prevuious mail id, just in case...
      $to_mail = $amails->notification;

      $amails->notification = str_ireplace(
        $form_state['values']['mail_search'],
        strtolower($form_state['values']['mail_replacewith']),
        $amails->notification
        );

      if ($to_mail == $amails->notification) {
        //no changes, so next
        continue;
      }

      $n = _guifi_db_sql(
        $table,
        array('id' => $amails->id),
        (array)$amails,
        $log,$to_mail);
      guifi_notify(
        $to_mail,
        t('The notification %notify for %type %title has been CHANGED to %new by %user.',
          array('%notify' => $form_state['values']['mail_search'],
            '%new' => $form_state['values']['mail_replacewith'],
            '%type' => $type,
            '%title' => $title,
            '%user' => $user->name)),
            $log);

    } // foreach row with the email found

  } // foreach table

  drupal_goto('guifi/menu/ip/mailsearch/'.$form_state['values']['mail_replacewith']);
}

// Administrative tools
function guifi_admin_notify($view = 'FALSE') {
  if ($view == 'FALSE')
    $send = TRUE;
  else
    $send = FALSE;

  include_once('guifi_cron.inc.php');
  $output = guifi_notify_send($send);
  if ($output == '')
    $output = t('Queue is empty');
  $now = time();
  if ($send) {
    variable_set('guifi_notify_last',$now);
    $output = '<h1>'.t('Notifications sent at %date',
      array('%date' => format_date($now))).'</h1>'.$output;
  } else {
    $output = '<h1<'.t('Messages to be sent.').'</h1>'.$output;
  }
  return $output;
}

// development tools

function guifi_admin_loadstats($server_id) {

  $output = drupal_get_form('guifi_admin_loadstats_form',$server_id);

  if (is_null($server_id))
    return $output;

  include_once('guifi_cron.inc.php');

  $output .= guifi_cron_loadCNMLstats($server_id, TRUE);

  return $output;
}

function guifi_admin_loadstats_form($form_state, $params = array()) {

  $form['zone_services']['graph_serverstr'] = array(
    '#type' => 'textfield',
    '#title' => t('CNML Graph server'),
    '#maxlength' => 60,
    '#required' => FALSE,
    '#default_value' => guifi_service_str($params),
    '#autocomplete_path'=> 'guifi/js/select-service/SNPgraphs',
    '#element_validate' => array('guifi_service_name_validate',
      'guifi_zone_service_validate'),
    '#description' => t('CNML graph server to load statistics from. Should support remote calls in v2 syntax.')
  );
  $form['submit'] = array('#type' => 'submit','#value' => t('Load statistics'));

  return $form;
}

function guifi_admin_loadstats_form_submit($form, &$form_state) {
   drupal_goto('guifi/menu/admin/loadstats/'.urlencode($form_state['values']['graph_server']));
   return;
}

//data review
function guifi_tools_datareview() {
  $data = array();
  $output = '';

  $headers = array(t('Working nodes'),t('Total'),t('Dif'),t('Alert'));
  
  $sql = 'SELECT count(*) as num FROM guifi_location where status_flag="Working";';
  if ($reg = db_fetch_object(db_query($sql))){
    $data['nodeswork']=$reg->num;
  }
  $sql = 'SELECT count(distinct nid) as num FROM guifi_devices t1
            inner join guifi_location t2 on t1.nid=t2.id
            where flag="Working" and status_flag="Working";';
  if ($reg = db_fetch_object(db_query($sql))){
    $data['nodes_deviceswork']=$reg->num;
  }
  $sql = 'SELECT count(distinct nid) as num FROM guifi_devices t1
            inner join guifi_location t2 on t1.nid=t2.id
            where flag="Working" and type="radio" and status_flag="Working";';
  if ($reg = db_fetch_object(db_query($sql))){
    $data['nodes_radiowork']=$reg->num;
  }
    
  $row = array();
  $rows[] = array(t('working nodes'),$data['nodeswork'],'','');
  $rows[] = array(t('nodes with working devices'),$data['nodes_deviceswork'],$data['nodeswork']-$data['nodes_deviceswork'],t('nodes without devices'));
  $rows[] = array(t('nodes with work radio devices'),$data['nodes_radiowork'],$data['nodes_deviceswork']-$data['nodes_radiowork'],t('nodes without radio devices'));
  
  $output .= theme('table',$headers,$rows);
  //$output .= theme_pager(NULL, 50);
  return $output;
}
/*
 * look if two devices are connected
 * return 1 is connected   0 not is connected
 */
function guifi_tools_isdevconnect($fromdev, $todev) {

  $from = $fromdev; //device
  $to[] = $todev; //device

  $routes = array();
  guifi_tools_isdevconnect_search(array($from => array()),$to,$routes);
  return count($routes);
}

function guifi_tools_isdevconnect_search($path, $to, &$routes, $maxhops = 50, $alinks = array()) {
  static $a=0;
  $a++;
  $btime = microtime(TRUE);

  $hop = count($path);
  $kpath = array_keys($path);
  end($path);
  $parent = key($path);

  // if links array not loaded, fill the array
  if (!count($alinks)) {
    $lbegin = microtime(TRUE);
    $qry = db_query('SELECT * FROM {guifi_links} WHERE flag = "Working"');
    while ($link = db_fetch_array($qry)) {
      // alinks[devices] will contain all the links for every device
      $alinks['devices'][$link['device_id']][] = $link['id'];

      if (isset($alinks['links'][$link['id']])) {
        // link data is alredy filled just adding a peer
        // the other peer is the other key

        end($alinks['links'][$link['id']]);
        $peer = key($alinks['links'][$link['id']]);

        // fill counterpart at previous peer
        $alinks['links'][$link['id']][$peer][0] = $link['device_id'];

        // fill the data of this peer
        $alinks['links'][$link['id']][$link['device_id']] = array(
          $peer,
          $link['nid'],
          $link['interface_id'],
          $link['ipv4_id']
        );
      } else
        // first peer of the link, the other peer is still unknown
        $alinks['links'][$link['id']] = array(
          0 => array($link['link_type'],$link['flag']),
          $link['device_id'] => array(
            0,  // peer still unknown
            $link['nid'],
            $link['interface_id'],
            $link['ipv4_id']
          ),
        );
    }
  }

  $c = 0;
  $links = array();

  foreach ($alinks['devices'][$parent] as $lid) {
    $link = $alinks['links'][$lid];
    if (!(count($link) == 3))
      // link ins incomplete, ignore
      continue;

    $dest = $link[$parent][0];

    // if loopback, ignore this link
    if (in_array($link[$parent][0],$kpath))
      continue;

    $c++;

//    print "Linked $parent to $dest \n<br />";

    $npath = $path;
    $npath[$parent]['from'] = array(
      $lid,              // 0 link id
      $link[0][0],       // 1 type
      $link[0][1],       // 2 flag
      $link[$parent][2], // 3 interface_id
      $link[$parent][3], // 4 ipv4_id
      $link[$parent][1]  // 5 node id

    );
    $npath[$dest]['to'] = array(
      $link[$dest][1],   // 0 dest nid
      $link[$dest][2],   // 1 dest interface_id
      $link[$dest][3]    // 2 dest ipv4 id
    );

    // if linked device in target destinations, add to routes
    if (in_array($dest,$to)) {
      $routes[] = array($ncost,$npath);
    }

    // if #hops < #maxhops and cost < 200, next hop
    if ((count($npath) < $maxhops) and (!count($routes))) {
      $c += guifi_tools_isdevconnect_search($npath,$to,$routes,$maxhops,$alinks);
    }
  }
  return $c;

}


?>