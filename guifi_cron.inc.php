<?php
/**
 * @file guifi_cron.inc.php
 * Cron jobs to be executed periodically
 *
 * Created on 27/09/2008
 */


/**
 * It delivers all the notification messages and empties the queue
 *
 * @param $send
 *   If FALSE, the messages won't be sent nor removed from the queue
 *
 * @return
 *   Message sent or to be sent, HTML formatted
 */
function guifi_notify_send($send = TRUE) {
  global $user;

  $destinations = array();
  $messages     = array();
  // Get all the queue to be processesed, grouping to every single destination
  $qt = db_query("
    SELECT *
    FROM {guifi_notify}");

  while ($message = $qt->fetchAssoc()) {
    $messages[$message['id']] = $message;
    foreach (unserialize($message['to_array']) as $dest)
       $destinations[$dest][] = $message['id'];
  }

  // For every destination, construct a single mail with all messages
  $errors = FALSE;
  $output = '';
  foreach ($destinations as $to => $msgs) {
    $body = str_repeat('-',72)."\n\n".
      t('Complete trace messages (for trace purposes, to be used by developers)')."\n".str_repeat('-',72)."\n";
    $subjects = t('Summary of changes:')."\n".str_repeat('-',72)."\n";
    foreach ($msgs as $msg_id) {
      $subjects .= format_date($messages[$msg_id]['timestamp'],'small').' ** '.
        $messages[$msg_id]['who_name'].' '.
        $messages[$msg_id]['subject']."\n";
      $body .=
        format_date($messages[$msg_id]['timestamp'],'small').' ** '.
        $messages[$msg_id]['who_name'].' '.
        $messages[$msg_id]['subject']."\n".
        $messages[$msg_id]['body']."\n".str_repeat('-',72)."\n";
    }

    $subject = t('[guifi.net notify] Report of changes at !date',
        array('!date' => format_date(time(),'small')));
    $output .= '<h2>'.t('Sending a mail to: %to',
      array('%to' => $to)).'</h2>';
    $output .= '<h3>'.$subject.'</h3>';
    $output .= '<pre><small>'.$subjects.$body.'</small></pre>';

    $params['mail']['subject']= $subject;
    $params['mail']['body']=$subjects.$body;

    $return = FALSE;
    if ($send) {
      $return = drupal_mail('guifi_notify','notify',
        $to,
        user_preferred_language($user),
        $params,
        variable_get('guifi_contact',$user->mail));

        guifi_log(GUIFILOG_TRACE,'return code for email sent:',$return);
    }

    if ($return['result'])
      watchdog('guifi','Report of changes sent to %name',
        array('%name' => $to));
    else {
      watchdog('guifi',
        'Unable to notify %name',
        array('%name' => $to),WATCHDOG_ERROR);
      $errors = TRUE;
    }

  }
  // delete messages
  if ((!$errors) and ($send))
     db_query("DELETE FROM {guifi_notify}
       WHERE id in (".implode(',',array_keys($messages)).")");

  return $output;
}

/**
 * Converts string to epoch date
 *
 * @param $str
 *  Date string in the form of "YYYY/MM/DD HH:mm"
 *
 * @return
 *   int representing epoch time
 */
function to_date($str) {
  if ($str == 'n/a')
    return NULL;

  $datestr = str_replace(array(" ",":","/"),"",$str);
  if (strlen($datestr) != 12)
    return NULL;

  return mktime(
    (int)substr($datestr,8,2),
    (int)substr($datestr,10,2),
    (int)0,
    (int)substr($datestr,4,2),
    (int)substr($datestr,6,2),
    (int)substr($datestr,0,4));
}

/**
 * Load statistics from remote CNML graph servers into the database
 *
 * @param $graph_server
 *   Graph server ID.
 *
 * @param $verbose
 *   If TRUE, it will return theme()
 *
 * @return
 *   theme() if $verbose==TRUE
 */
function guifi_cron_loadCNMLstats($graph_server,$verbose=FALSE) {
  if (is_null($gs))
    $gs = guifi_service_load($graph_server);

  if ($gs->var['version'] >= 2.0)
    $handle = fopen(
      guifi_cnml_call_service($gs,'stats',
        array()),
      "r");
  else
    $output .= t("This graph server doesn't support v2.0 CNML calls syntax.\n");

  if ($handle) {
    $c = 0;
    $u = 0;
    while (!feof($handle)) {
      $c++;
      $updatestr = array();
      $stat = stream_get_line($handle, 4096,"\n");

      $vstats = explode('|',$stat);
      $device_id = array_shift($vstats);
      $availability_stats = array_shift($vstats);


      list($latmax,$latavg,$availability,$lastonline,$laststatdate,$laststattime,$lastavailability) =
        explode(',',$availability_stats);

      $tlaststat = to_date($laststatdate.$laststattime);
      if (!$tlaststat)
        continue;

      $dev = db_fetch_object(db_query(
        'SELECT last_stats,last_online,last_flag,ly_availability ' .
        'FROM {guifi_devices} ' .
        'WHERE id=%d',
        $device_id));

      if ($tlaststat <= $dev->last_stats)
        continue;

      $u++;

      $tlastonline = to_date($lastonline);
      if ($tlastonline > $dev->last_online)
        $updatestr[] = 'last_online='.$tlastonline;

      if (($lastavailability <= 100) and ($lastavailability >= 0)) {
        if ($lastavailability == 0)
          $updatestr[] = 'last_flag="Down"';
        else
          $updatestr[] = 'last_flag="Up"';
      }

      $updatestr[] = 'last_stats='.$tlaststat;
      if ($availability <= 100 and $availability >= 0)
        $updatestr[] = 'ly_availability='.$availability;
      if ($latavg)
        $updatestr[] = 'latency_avg='.$latavg;
      if ($latmax)
        $updatestr[] = 'latency_max='.$latmax;

      // Update availability statistics
      db_query('UPDATE {guifi_devices} SET '.
        implode(', ',$updatestr).
        ' WHERE id=%d',$device_id);

      // Now going to update traffic statistics
      $nradios = db_fetch_object(db_query(
        'SELECT count(*) count ' .
        'FROM {guifi_radios} ' .
        'WHERE id=%d',$device_id));

      if ($nradios->count == 1) {
        // if just one radio, sum all values
        $in = 0; $out = 0;
        foreach ($vstats as $value) {
        	$traffic = explode(',',$value);
          $in += $traffic[1];
          $out += $traffic[2];
        }
        if ($in or $out)
          db_query(
            'UPDATE {guifi_radios} ' .
            'SET ly_mb_in=%d, ly_mb_out=%d ' .
            'WHERE id=%d',$in,$out,$device_id);
      } else {
      	// more than one radio, give the value to each radio
        foreach ($vstats as $value)
          db_query(
            'UPDATE {guifi_radios} ' .
            'SET ly_mb_in=%d, ly_mb_out=%d ' .
            'WHERE id=%d AND radiodev_counter=%d',
            $traffic[1],$traffic[2],$device_id,$traffic[0]);
      }


      $stats .= $c.' '.$stat."\n";
    }
    watchdog('guifi','Loaded statistics from %name, %ndevices updated',
        array(
          '%name' => guifi_service_str($graph_server),
          '%ndevices' => $u), WATCHDOG_NOTICE);//    $stats =  stream_get_contents($handle);
    fclose($handle);
    $output .= '<pre>'.$stats.'</pre>';
  } else
    $output .= t('Get stats failed.');

  if ($verbose)
    return theme('box',t("Load statistics from '%server'",
      array('%server' => guifi_service_str($graph_server))),
      $output);
}

?>
