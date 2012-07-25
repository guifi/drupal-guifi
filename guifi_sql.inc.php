<?php
// $Id: guifi.module x$

/**
 * @file guifi_sql.inc.php
 * Manage the SQL statements against the guifi.net schema
 **/
/** _guifi_db_sql(): UPSERT (SQL insert or update) on
 * node, device, radios, interfaces, ipv4, links...
 **/
function _guifi_db_sql($table, $key, $idata, &$log = NULL, &$to_mail = array()) {
  global $user;

//  print_r($key);
//  print_r($data);
//  exit(0);

  $insert = FALSE;
  if (!empty($key)) {
    guifi_log(GUIFILOG_TRACE,
      sprintf("guifi_sql(table: %s, keys=".implode(',',$key).")",
                  $table,key),
                  $idata);
  }
  if (is_object($idata)) {
    foreach ($idata as $k => $v) {
      $data[$k] = $v;
    }
  }
  else {
    $data = &$idata;
  }


  // delete?
  if ($data['deleted']) {
    $log .= _guifi_db_delete($table,$key,$to_mail);
    return $log;
  }

  // insert?
  if ($data['new'])
    $insert = TRUE;

  $qc = db_query('SHOW COLUMNS FROM ' . $table);
  while ($column = db_fetch_object($qc))
    $columns[] = $column->Field;

  // cleanup columns which doesn't exists into the database
  foreach ($columns as $cname) {
    if (isset($data[$cname]))
      $sqldata[$cname] = $data[$cname];
  }
  $data = $sqldata;

  // processing insert triggers to fill new ids etc...
  if ($insert) {
    switch ($table) {
	  case 'guifi_model':
	    $next_id = db_fetch_array(db_query("SELECT max(mid)+1 mid FROM {$table} "));
	    $data['mid'] = $next_id['mid'];
	    $data['user_created'] = $user->uid;
	    $data['timestamp_created'] = time();
            break;
	  case 'guifi_manufacturer':
	    $next_id = db_fetch_array(db_query("SELECT max(fid)+1 fid FROM {$table} "));
	    $data['fid'] = $next_id['fid'];
	    $data['user_created'] = $user->uid;
	    $data['timestamp_created'] = time();
            break;
	  case 'guifi_types':
	    $next_id = db_fetch_array(db_query("SELECT max(id)+1 id FROM {$table}WHERE type = '%s'",$data['type']));
	    $data['id'] = $next_id['id'];
            break;
	  case 'budget_funds':
	    $data['timestamp_created'] = time();
	  case 'budget_items':
	    $next_id = db_fetch_array(db_query("SELECT max(id)+1 id FROM {$table} " .
	        "WHERE budget_id = %d",$data['budget_id']));
	    if (is_null($next_id['id']))
	      $next_id['id'] = 1;
	    $data['id'] = $next_id['id'];
	    break;
	  case 'guifi_devices':
	  case 'guifi_dns_domains':
	  case 'guifi_users':
	    $next_id = db_fetch_array(db_query("SELECT max(id)+1 id FROM {$table}"));
	    if (is_null($next_id['id']))
	      $next_id['id'] = 1;
	    $data['id'] = $next_id['id'];
	  case 'guifi_zone':
	  case 'guifi_services':
	  case 'guifi_location':
	  case 'guifi_networks':
	  case 'guifi_dns_hosts':
	    $data['user_created'] = $user->uid;
	    $data['timestamp_created'] = time();
	    break;
	//  case 'guifi_radios':
	//      // radio id already comes (device exists), looking for next radio id  at this device  (radiodev_counter)
	//      $next_id = db_fetch_array(db_query('SELECT max(radiodev_counter)+1 id FROM {guifi_radios} WHERE id=%d',$data['id']));
	//      if (is_null($next_id['id']))
	//        $next_id['id'] = 0;
	//      $data['radiodev_counter']=$next_id['id'];
	//    break;
	  case 'guifi_interfaces':
	    $new_id=db_fetch_array(db_query('SELECT max(id)+1 id FROM {guifi_interfaces}'));
	    $data['id']=$new_id['id'];
	    break;
	  case 'guifi_ipv4':
	    $next_id = db_fetch_array(db_query('SELECT max(a.id) + 1 id FROM {guifi_ipv4} a, {guifi_interfaces} i WHERE a.interface_id=i.id AND i.id=%d',$data['interface_id']));
	    if (is_null($next_id['id']))
	      $next_id['id'] = 0;
	    $data['id'] = $next_id['id'];
	    break;
	  case 'guifi_links':
	    // fill only if insert (remote id already know the id)
	    if (($insert) && ($data['id']==-1))  {
	      $next_id=db_fetch_array(db_query('SELECT max(id)+1 id FROM {guifi_links}'));
	      if (is_null($next_id['id']))
	        $next_id['id'] = 1;
	      $data['id']=$next_id['id'];
	    }
	    case 'guifi_caracteristica':
	    case 'guifi_caracteristiquesModel':
	    case 'guifi_configuracioUnSolclic':
	    case 'guifi_firmware':
	    case 'guifi_parametres':
	    case 'guifi_parametresConfiguracioUnsolclic':
	    case 'guifi_parametresFirmware':
	      $new_id=db_fetch_array(db_query("SELECT max(id)+1 id FROM {$table}"));
	      $data['id']=$new_id['id'];
	      $data['user_created'] = $user->uid;
	      $data['timestamp_created'] = time();
	    break;
	  } // insert triggers switch table
  }
  // processing update triggers
  else {
    switch ($table) {
      case 'guifi_zone':
      case 'guifi_location':
      case 'guifi_devices':
      case 'guifi_dns_domains':
      case 'guifi_services':
      case 'guifi_dns_hosts':
      case 'guifi_users':
      case 'guifi_model':
      case 'guifi_manufacturer':
      case 'guifi_caracteristica':
      case 'guifi_caracteristiquesModel':
      case 'guifi_configuracioUnSolclic':
      case 'guifi_firmware':
      case 'guifi_parametres':
      case 'guifi_parametresConfiguracioUnsolclic':
      case 'guifi_parametresFirmware':
      case 'guifi_manufacturer':
        $data['user_changed'] = $user->uid;
        $data['timestamp_changed'] = time();
        break;
    }
  }

  // processing all cases triggers
  switch ($table) {
    case 'guifi_ipv4':
      if (isset($data['ipv4']))
        $data['ipv4'] = trim($data['ipv4']);
      if (!isset($data['zone_id']) and (isset($data['interface_id']))) {
        $z = db_fetch_object(db_query(
          'SELECT l.zone_id ' .
          'FROM {guifi_interfaces} i, {guifi_devices} d, {guifi_location} l ' .
          'WHERE i.id=%d ' .
          '  AND i.device_id=d.id AND d.nid=l.id',$data['interface_id']));
        $data['zone_id'] = $z->zone_id;
      }
      break;
  }

// // set numeric & refix if ipv4
// if ($table == 'guifi_ipv4') {
//   if (isset($data['ipv4']))
//     $data['lipv4'] = ip2long($data['ip4']);
// }

  $sql_str = '';
  $values_data = array();
  if ($insert) {
      // insert
    foreach ($data as $k => $value) {
      if (is_numeric($value)) {
        if (is_float($value)) {
          $values_data[$k] = "%f";
        } else
          $values_data[$k] = "%d";
      } else
        $values_data[$k] = "'%s'";
    }
    $sql_str .= "INSERT INTO {" . $table . "} (" . implode(',', array_keys($data)) . ") VALUES (" . implode(',', $values_data) . ")";
    $new_data = $data;
  } else {
   // update

   // constructing where with primary keys
   $where_data = array();
   foreach ($key as $k => $value)
     if (is_null($value))
       $where_data[$k] = $k.' is NULL';
     else
       if ( $table == 'guifi_types')
         $where_data[$k] = $k.' = \''.$value.'\'';
       else
         $where_data[$k] = $k.' = '.$value;

   // check what's being changed
   $sqlqc = 'SELECT '.implode(',',array_keys($data)).
           ' FROM {'.$table.'}'.
           ' WHERE '.implode(' AND ',$where_data);
   $ck = 0;
   $qc = db_query($sqlqc);

   while ($odata = db_fetch_array($qc)) {
     $orig_data = $odata;
     $ck++;
   }
//
//   print "SQL:$sqlqc\n<br />Rows: $ck\n<br />";
//   print_r($orig_data);
//   exit;

   if ($ck != 1)
   {
     drupal_set_message(
       t('Can\'t update %table while primary key (%where) doesn\'t give 1 row' .
          '<br />%sql gives %rows rows.',
       array(
         '%table' => $table,
         '%sql' => $sqlqc,
         '%rows' => $ck,
         '%where' => implode(' AND ',$where_data))),
       'error'
     );

     return;
   }
   //$orig_data = db_fetch_array($qc);
   // cast floats to compare
   foreach ($data as $k => $value)
     if (is_float($value)) {
       $orig_data[$k] = (float) $orig_data[$k];
     }

   // perform the update only if there are changes in real data (excluding
   // user changed and timestamp)
   $input = $orig_data;
   $output = $data;
   if (isset($input['timestamp_changed'])) {
     unset($input['timestamp_changed']);
     unset($output['timestamp_changed']);
   }
   if (isset($input['user_changed'])) {
     unset($input['user_changed']);
     unset($output['user_changed']);
   }

   $new_data = array_diff_assoc($output,$input);
   if (count($new_data) == 0)
     return $orig_data;

   $new_data = array_diff_assoc($data,$orig_data);

   // constructing update
   $log .= $table.' '.t('UPDATED').":<br />";
   foreach ($new_data as $k => $value) {
     $log .= "\t - ".$k.': '.$orig_data[$k].' -> '.$value.'<br />';
     if (is_float($value))
       $values_data[$k] = $k.'=%f';
     else if (is_numeric($value))
       $values_data[$k] = $k.'=%d';
     else
       $values_data[$k] = $k."='%s'";
   }
   $sql_str .= "UPDATE {".$table."} SET ".implode(', ',$values_data).
               " WHERE ".implode(' AND ',$where_data);
 }
    
  // execute SQL statement
  $log .= $sql_str . ' (' . implode(', ', $new_data) . ')<br />';
  db_query($sql_str, $new_data);
  return ($data);
}

/** _guifi_db_delete(): Delete SQL statements for node, devices, radios, users, services, interfaces, ipv4, links, zones...
***/
function _guifi_db_delete($table,$key,&$to_mail = array(),$depth = 0,$cascade = TRUE) {
  global $user;

  $log = str_repeat('- ',$depth);
  $depth++;
  $to_mail = array();
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_db_delete(%s,%s)',$table,var_export($key, TRUE)));
  if (!in_array($user->mail,$to_mail))
    $to_mail[] = $user->mail;

  switch ($table) {

  // Node (location)
  case 'guifi_location':
    // cascade to node devices
    $qc = db_query("SELECT id FROM {guifi_devices} where nid = '%s'",
                    $key['id']);
    while ($device = db_fetch_array($qc))
      $log .= '<br />'._guifi_db_delete('guifi_devices',$device,$to_mail,$depth);

    // cascade to node users
    $qc = db_query("SELECT id FROM {guifi_users} where nid = '%s'",
                    $key['id']);
    while ($quser = db_fetch_array($qc))
      $log .= '<br />'._guifi_db_delete('guifi_users',$quser,$to_mail,$depth);

    break;
  // delete Device

  case 'guifi_dns_domains':
    $item=db_fetch_object(db_query(
      'SELECT d.id did, d.name dname, d.notification, d.sid,
        l.nick nname, l.notification ncontact
       FROM {guifi_dns_domains} d LEFT JOIN {guifi_services} l ON d.sid=l.id
       WHERE d.id = %d',
       $key['id']));
    $log .= t('Domain %id-%name at node %nname deleted.',array('%id' => $key['id'],'%name' => $item->dname,'%nname' => $item->nname));
    // cascade to dns_hosts
    $qc = db_query('SELECT id, counter FROM {guifi_dns_hosts} WHERE id=%d',$key['id']);
    while ($host = db_fetch_array($qc))
      $log .= '<br />'._guifi_db_delete('guifi_dns_hosts',$host,$to_mail,$depth);
  break;

  case 'guifi_devices':
    $item=db_fetch_object(db_query(
      'SELECT d.nick dname, d.notification, d.nid, d.type, d.comment,
        l.nick nname, l.notification ncontact
       FROM {guifi_devices} d LEFT JOIN {guifi_location} l ON d.nid=l.id
       WHERE d.id = %d',
       $key['id']));
    $log .= t('Device (%type) %id-%name at node %nname deleted.',array('%type' => $item->type,'%id' => $key['id'],'%name' => $item->dname,'%nname' => $item->nname));

    // cascade to device radios
    $qc = db_query('SELECT id, radiodev_counter FROM {guifi_radios} WHERE id=%d',$key['id']);
    while ($radio = db_fetch_array($qc))
      $log .= '<br />'._guifi_db_delete('guifi_radios',$radio,$to_mail,$depth);

    // cascade to device interfaces
    $qc = db_query('SELECT id, radiodev_counter FROM {guifi_interfaces} WHERE device_id=%d',$key['id']);
    while ($interface = db_fetch_array($qc))
      $log .= '<br />'._guifi_db_delete('guifi_interfaces',$interface,$to_mail,$depth);

    break;

  // delete Radio
  case 'guifi_radios':
    $item=db_fetch_object(db_query(
       'SELECT
          r.protocol, r.ssid sid, r.mode, r.radiodev_counter,
          d.nick dname, d.notification, d.nid, l.nick nname
        FROM {guifi_radios} r, {guifi_devices} d, {guifi_location} l
        WHERE  r.id = %d AND r.radiodev_counter = %d AND
          r.id=d.id AND d.nid=l.id',
        $key['id']), $key['radiodev_counter']);
    $log .= t('Radio (%mode-%protocol) %id-%rc %ssid at device %dname deleted.',array('%mode' => $item->mode,'%protocol' => $item->protocol,'%id' => $key['id'],'%rc' => $key['radiodev_counter'],'%ssid' => $item->sid,'%dname' => $item->dname));

    // cascade to radio interfaces
    $qc = db_query('SELECT id, radiodev_counter FROM {guifi_interfaces} WHERE device_id=%d AND radiodev_counter=%d',$key['id'],$key['radiodev_counter']);
    while ($interface = db_fetch_array($qc))
      $log .= '<br />'._guifi_db_delete('guifi_interfaces',$interface,$to_mail,$depth);

    break;

  // delete Interfaces
  case 'guifi_interfaces':
    $item=db_fetch_object(db_query(
       'SELECT i.interface_type, i.radiodev_counter,
               d.nick dname,
               d.notification, d.nid, l.nick nname
        FROM {guifi_interfaces} i LEFT JOIN {guifi_devices} d ON i.device_id=d.id
             LEFT JOIN {guifi_location} l ON d.nid=l.id
        WHERE i.id = %d',
        $key['id']));
    $log .= t('interface (%type) %id - %rc at device %dname deleted.',array('%type' => $item->interface_type,'%id' => $key['id'],'%rc' => $item->radiodev_counter,'%dname' => $item->dname));


    // cascade ipv4
    $qc = db_query('SELECT id, interface_id FROM {guifi_ipv4} WHERE interface_id=%d',$key['id']);
    while ($ipv4 = db_fetch_array($qc))
      $log .= '<br />'._guifi_db_delete('guifi_ipv4',$ipv4,$to_mail,$depth);
    break;

  // delete ipv4
  case 'guifi_ipv4':
    $item=db_fetch_object(db_query(
       'SELECT a.id, a.interface_id, a.ipv4, i.interface_type, d.nick dname, d.notification, d.nid, l.nick nname
        FROM {guifi_ipv4} a LEFT JOIN {guifi_interfaces} i ON a.interface_id=i.id LEFT JOIN {guifi_devices} d ON i.device_id=d.id LEFT JOIN {guifi_location} l ON d.nid=l.id
        WHERE a.id = %d AND a.interface_id=%d',
        $key['id'],$key['interface_id']));
    $log .= t('address (%addr) at device %dname deleted.',array('%addr' => $item->ipv4,'%dname' => $item->dname));

    if (!$cascade)
      break;
    // cascade links
    $qc = db_query('SELECT id, device_id FROM {guifi_links} WHERE ipv4_id=%d AND interface_id=%d',$key['id'],$key['interface_id']);
    while ($link = db_fetch_array($qc))
      $log .= '<br />'._guifi_db_delete('guifi_links',$link,$to_mail,$depth);
    break;

  // delete links
  case 'guifi_links':
    $item=db_fetch_object(db_query(
       'SELECT l.id, l.link_type, l.ipv4_id, i.id interface_id, ' .
       '       d.nick dname, d.id device_id, d.notification, d.nid, n.nick nname
        FROM {guifi_links} l ' .
        '    LEFT JOIN {guifi_interfaces} i ON l.interface_id=i.id ' .
        '    LEFT JOIN {guifi_devices} d ON l.device_id=d.id ' .
        '    LEFT JOIN {guifi_location} n ON l.nid=n.id
        WHERE l.id = %d' .
        '    AND l.device_id=%d',
        $key['id'],$key['device_id']));
    $log .= t('link %id-%did (%type) at %nname-%dname deleted.',
        array('%id' => $key['id'],
            '%did' => $key['device_id'],
            '%type' => $item->link_type,
            '%nname' => $item->nname,
            '%dname' => $item->dname));

    if (!$cascade)
      break;

    // cascade to remote link
    $qc = db_query('SELECT id, ipv4_id, interface_id, device_id ' .
        'FROM {guifi_links} ' .
        'WHERE id=%d ' .
        '  AND device_id !=%d',
        $key['id'],$key['device_id']);

    while ($link = db_fetch_array($qc)) {
      $log .= '<br />'._guifi_db_delete('guifi_links',$link,$to_mail,$depth, FALSE);

      // cleanup of remote ipv4 addresses when appropriate
      $qar = db_query('SELECT * ' .
          'FROM {guifi_ipv4} '.
          'WHERE id=%d AND interface_id=%d',
          $link['ipv4_id'],$link['interface_id']);
      while ($ripv4 = db_fetch_array($qar)) {
        $aitem = _ipcalc($ripv4['ipv4'],$ripv4['netmask']);

        if ($ripv4['ipv4_type'] == '2') {
          // type 2: link is backbone
          // if the addres is a:
          // /30 (single p2p link)
          // or /29 and /28 ( Multilink )
          if (($ripv4['netmask'] == '255.255.255.252') or  ($ripv4['netmask'] == '255.255.255.248') or  ($ripv4['netmask'] == '255.255.255.240')) {
            $log .= '<br />'._guifi_db_delete(
              'guifi_ipv4',
              array('id' => $link['ipv4_id'],
              'interface_id' => $link['interface_id']),
              $to_mail,
              $depth,
              FALSE);

           // cascade to local ipv4
            $log .= '<br />'._guifi_db_delete(
              'guifi_ipv4',
              array('id' => $item->ipv4_id,
              'interface_id' => $item->interface_id),
              $to_mail,
              $depth,
              FALSE);
          }
       } else {

      $mlinks = db_fetch_array(db_query('SELECT count(id) AS links ' .
          'FROM {guifi_links} '.
          'WHERE ipv4_id=%d AND interface_id=%d',
          $link['ipv4_id'],$link['interface_id']));

       if  (($ripv4['ipv4'] != $aitem['netstart']) and ( $mlinks['links'] < 1)) {
              $log .= '<br />'._guifi_db_delete(
              'guifi_ipv4',
              array('id' => $link['ipv4_id'],
              'interface_id' => $link['interface_id']),
              $to_mail,
              $depth,
              FALSE);
       }
     }
     // guifi_log(GUIFILOG_BASIC,'function delete cascade remote address()',$link);
     // cleanup remote interface when appropriate
        $qir = db_query('SELECT i.id id, i.interface_type, count(a.id) na ' .
           'FROM {guifi_interfaces} i ' .
           '  LEFT OUTER JOIN {guifi_ipv4} a ' .
           '  ON i.id=a.interface_id ' .
           'WHERE i.id=%d ' .
           'GROUP BY i.id, i.interface_type',
           $link['interface_id']);

        while ($na = db_fetch_array($qir)) {

//          guifi_log(GUIFILOG_BASIC,'function delete cascade remote interface()',$na);

          // delete the interface, if has no other ipv4 address and is not
          // in the list Wan, wLan/Lan, Lan/Lan, Lan or wds/p2p
          if ((in_array($na['interface_type'],array('Wan','wLan/Lan','Lan/Lan','Lan','wds/p2p')))
              or ($na['na'] != 0)
          )
            continue;

          $log .= '<br />'._guifi_db_delete(
            'guifi_interfaces',
            array('id' => $na['id']),
            $to_mail,
            $depth,
            FALSE);
        }
      }
    }

  // delete services
  case 'guifi_services':
    // cascade interfaces
    break;

  // delete users
  case 'guifi_users':
    $item=db_fetch_object(db_query(
     'SELECT * ' .
     'FROM {guifi_users} u ' .
     'WHERE id = %d',
      $key['id']));
    $log .= t('User %id-%name deleted.',
        array('%id' => $key['id'],
            '%name' => $item->username));
    break;
  case 'guifi_zone':
    break;

  case 'budgets':
    if (!$cascade)
      break;
    $qc = db_query(
      'SELECT id, budget_id ' .
      'FROM {budget_items} ' .
      'WHERE budget_id=%d',
      $key['id']);

    while ($item = db_fetch_array($qc))
      $log .= '<br />'._guifi_db_delete('budget_items',$item,$to_mail,$depth);

    $qc = db_query(
      'SELECT id, budget_id ' .
      'FROM {budget_funds} ' .
      'WHERE budget_id=%d',
      $key['id']);

    while ($fund = db_fetch_array($qc))
      $log .= '<br />'._guifi_db_delete('budget_funds',$fund,$to_mail,$depth);
    break;

  }

  $where_str = '';

  foreach ($key as $k => $value) {
    if ($where_str != '')
      $where_str .= ' AND ';
       if ( $table == 'guifi_types')
         $where_str .= $k.' = \''.$value.'\'';
       else
         $where_str .= $k.' = '.$value;
  }
  $count = db_fetch_array(db_query("
    SELECT count(*) c
    FROM {".$table."}
    WHERE ".$where_str));
  if ($count['c'] != 1)
    return $log.'<br />'.t('There was nothing to delete at %table with (%where)',array('%table' => $table,'%where' => $where_str));
  if (!in_array($item->notification,$to_mail))
    $to_mail[] = $item->notification;
  if (!in_array($item->ncontact,$to_mail))
    $to_mail[] = $item->ncontact;

  $where_str = '';
  foreach ($key as $k => $value) {
    if ($where_str != '')
      $where_str .= ' AND ';
       if ( $table == 'guifi_types')
         $where_str .= $k.' = \''.$value.'\'';
       else
         $where_str .= $k.' = '.$value;
  }
  $delete_str = 'DELETE FROM {'.$table.'} WHERE '.$where_str;
  $log .= '<br />'.$delete_str;
  guifi_log(GUIFILOG_TRACE,$delete_str);
  db_query($delete_str);

  return $log;
}
?>