<?php
/**
 * @file guifi_graphs.inc.php
 */

/**
 * guifi_graph_detail
 * outputs a page with node detailed graphs
 */
function guifi_graph_detail() {
  $type = $_GET['type'];

  if (isset($_GET['device']))
    $device_id=$_GET['device'];
  else if (isset($_GET['radio']))
    $device_id=$_GET['radio'];
  if (isset($device_id)) {
      $query = db_query(
        "SELECT r.id, r.nick, n.title, r.nid, l.zone_id " .
        "FROM {guifi_devices} r, {node} n, {guifi_location} l " .
        "WHERE r.id = :did " .
        "  AND n.nid=r.nid " .
        "  AND n.nid = l.id",
        array(':did' => $device_id));
      $radio = $query->fetchObject();
      $zid = $radio->zone_id;
  }

  if ($type=='supernode') {
    $node = node_load($_GET['node']);
    if ($node->graph_server == -1) {
      $rows[] = array(t('This node has the graphs disabled.'));
      return array_merge($rows);
    }
    if (!empty($node->graph_server))
      $gs = node_load($node->graph_server);
    else
      $gs = node_load(guifi_graphs_get_server($node->id,'node'));
  } else {
    if ($radio->graph_server == -1) {
      $rows[] = array(t('This device has the graphs disabled.'));
      return array_merge($rows);
    }
    if (!empty($radio->graph_server))
      $gs = node_load($radio->graph_server);
    else
      $gs = node_load(guifi_graphs_get_server($radio->id,'device'));
  }

  $help = t('Here you have a detailed view of the available information for several periods of time (daily, weekly, monthly and yearly). You can obtain a detailed graph for a given period of time by entering the period in the boxes below.');

  $args = array('type' => $type,
    'node' => $_GET['node'],
    'device' => $device_id
    );
  if (isset($_GET['direction']))
    $args['direction']=$_GET['direction'];

  switch ($type) {
    case 'clients':
      $title = '<a href="'.base_path().'guifi/device/'.$radio->id.'">'.$radio->nick.'</a> '.t('at').' '.'<a href='.base_path().'node/'.$radio->nid.'>'.$radio->title.'</a>';
      $help .= '<br />'.t('The clients graph displays the top clients by transit.');
      break;
    case 'supernode':
      $zid = $node->zone_id;
      $title = '<a href='.base_path().'node/'.$_GET['node'].'>'.$node->title.'</a>';
      $help .= '<br />'.t('Supernode graph displays the transit of each radio.');
      break;
    case 'radio':
    case 'device':
      $help= '<br />'.t('The radio graph show in &#038; out transit.');
    case 'pings':
      if ($type != 'radio')
        $help= '<br />'.t('The ping graph displays the latency and availability. High latency usually means bad connection. Yellow means % of failed pings, could be some yellow on the graphs, but must not reach value of 100, if the value reaches 100, that means that the radio is offline.');
      $title = $radio->nick.' '.t('at').' '.'<a href='.base_path().'node/'.$radio->nid.'>'.$radio->title.'</a>';
      break;
  }

  $secs_day = 60*60*24;
  drupal_set_breadcrumb(guifi_zone_ariadna($zid));
  $output = '<div id="guifi">';

//  $rows[] = array(t('enter a timeframe to graph a customized period'));
  $output .= '<h3>'.$type.'</h3>'.$help;
  switch ($type) {
  }
  if (isset($_POST['date1']))
    $date1 = $_POST['date1'];
  else
    $date1 = date('d-m-Y H:i',time()-60*60*2);
  if (isset($_POST['date2']))
    $date2 = $_POST['date2'];
  else
    $date2 = date('d-m-Y H:i',time()-300);
  $str = '<form name="form_timespan_selector" method="post"><strong>&nbsp;'.t('From:');
  $str .= '&nbsp;</strong><input type="text" name="date1" id=\'date1\' size=\'14\' value="'.$date1.'">&nbsp;<input type="image"
src="'.base_path(). drupal_get_path('module', 'guifi').'/contrib/calendar.gif" alt="Start date selector" onclick="return showCalendar(\'date1\');">&nbsp;';
  $str .= '<strong>'.t('To:').'&nbsp;</strong> <input type="text" name="date2" id=\'date2\' size="14" value="'.$date2.'"> &nbsp;';
  $str .= '<input type="image" src="'.base_path(). drupal_get_path('module', 'guifi').'/contrib/calendar.gif" alt="End date selector" align="absmiddle" onclick="return showCalendar(\'date2\');"> &nbsp;&nbsp;';
  $str .= '<input type="submit" name="button_refresh" action="submit" value="refresh">';
  $rows[] = array($str);
  if (isset($_POST['date1'])) {
    list($day,$month,$year,$hour,$min) = sscanf($_POST['date1'],'%d-%d-%d %d:%d');
    $start = mktime($hour, $min, 0, $month, $day, $year);
    list($day,$month,$year,$hour,$min) = sscanf($_POST['date2'],'%d-%d-%d %d:%d');
    $end = mktime($hour, $min, 0, $month, $day, $year);
    $rows[] = array(t('customized graph'));
    $rows[] = array('<img src="'.guifi_cnml_call_service($gs->var['url'],'graph',$args,sprintf('start=%d&end=%d">',$start,$end)));
  }
  $rows[] = array(t('day'));
  $rows[] = array('<img src="'.guifi_cnml_call_service($gs->var['url'],'graph',$args,sprintf('start=-%d&end=%d">',$secs_day,-300)));
  $rows[] = array(t('week'));
  $rows[] = array('<img src="'.guifi_cnml_call_service($gs->var['url'],'graph',$args,sprintf('start=-%d&end=%d">',$secs_day * 7,-300)));
  $rows[] = array(t('month'));
  $rows[] = array('<img src="'.guifi_cnml_call_service($gs->var['url'],'graph',$args,sprintf('start=-%d&end=%d">',$secs_day * 31,-300)));
  $rows[] = array(t('year'));
  $rows[] = array('<img src="'.guifi_cnml_call_service($gs->var['url'],'graph',$args,sprintf('start=-%d&end=%d">',$secs_day * 365,-300)));
  $output .= theme('table', NULL, array_merge($rows));
  $output .= "</div>"._guifi_script_calendar();

  drupal_set_html_head('<script type="text/javascript" src="'.base_path(). drupal_get_path('module', 'guifi').'/contrib/calendar.js"></script> <script type="text/javascript" src="'.base_path(). drupal_get_path('module', 'guifi').'/contrib/lang/calendar-ca.js"></script></script> <script type="text/javascript" src="'.base_path(). drupal_get_path('module', 'guifi').'/contrib/calendar-setup.js"></script>');
  drupal_set_title(t('graph details for').' '.$title);
  return print theme('page', $output, t('graph details for').' '.$title);
}


function get_SSID_radio($radio) {
  $querySSID = db_query("SELECT r.ssid FROM {guifi_radios} r WHERE r.id = :radio", array(':radio' => $radio));
  $SSID = $querySSID->fetchObject();
   return $SSID->ssid;
}

/**
 *
 * @param $id
 *
 * @param $type
 *   Possible values are 'device' (default), 'node', 'zone', 'radio'
 *
 * @todo 'device' and 'radio' are the same. Unify.
 */
function guifi_graphs_get_server($id, $type='device') {
	switch ($type) {
    case 'node':
      $n = node_load($id);
      if ($n->graph_server)
        return $n->graph_server;
      else
        return guifi_graphs_get_server($n->zone_id,'zone');
    case 'zone':
      $z = node_load($id);
      if ($z->graph_server)
        return $z->graph_server;
      else
        if ($z->master)
          return guifi_graphs_get_server($z->master,'zone');
        else
          return FALSE;
	case 'device':
    case 'radio':
      $d = db_query('SELECT nid, graph_server FROM {guifi_devices} WHERE id = :id', array(':id' => $id))->fetchObject();
      if ($d->graph_server)
        return $d->graph_server;
      $countRadios = db_query(
      "SELECT count(*) c " .
      "FROM {guifi_radios} " .
      "WHERE nid = :nid " .
      "  AND mode='ap'", array(':nid' => $d->nid))->fetchObject();
      if ($countRadios->c > 0)
        // node has APs, inherits node graph server
        return guifi_graphs_get_server($d->nid,'node');

      // client: finding an ap/client link for this node, inherits from remote node
      $link = db_query(
	      "SELECT " .
        "  l2.device_id ".
				"FROM {guifi_links} l1, " .
				"  {guifi_links} l2 " .
				"WHERE l1.id=l2.id " .
				"  AND l1.nid != l2.nid " .
				"  AND l1.link_type='ap/client' " .
				"  AND l1.nid = :nid ",
				array(':nid' => $d->nid))->fetchObject();
      if ($link)
        return guifi_graphs_get_server($link->device_id,'device');
      else
        return guifi_graphs_get_server($d->nid,'node');
	}

  return FALSE;
}

/**
 * guifi_device_graph_overview
 * outputs an overiew graph of the device
**/
function guifi_device_graph_overview($radio) {

 guifi_log(GUIFILOG_TRACE,'guifi_device_graph_overview()',$radio);

 if (isset($radio['mode']))
   $radio['type'] = 'radio';

 if ($radio['graph_server'] == -1) {
   $rows[] = array(t('This device has the graphs disabled.'));
   return array_merge($rows);
 }

 if (empty($radio['graph_server']))
   $gs = node_load(guifi_graphs_get_server($radio['id'],'device'));
 else
   $gs = node_load($radio['graph_server']);

      $clients = db_query(
        "SELECT count(c.id) count " .
        "FROM {guifi_links} c " .
        "WHERE c.device_id = :rid " .
        "  AND c.link_type IN ('wds','ap/client','bridge')",
        array(':rid' => $radio['id']))->fetchObject();
      $args = array('type' => 'clients',
          'node' => $radio['nid'],
          'device' => $radio['id']);

      if ($clients->count > 1)  // several clients, Totals In & Out
      {
        $rows[] = array(array(
          'data'=> '<a href='.base_path().'guifi/graph_detail?'.
                   guifi_cnml_args($args,'direction=in').
                   '><img src="'.
                   guifi_cnml_call_service($gs->var['url'],'graph',$args,'direction=in').
                   '"></a>',
          'align' => 'center'));
        $rows[] = array(array(
          'data'=> '<a href='.base_path().'guifi/graph_detail?'.
                   guifi_cnml_args($args,'direction=out').
                   '><img src="'.
                   guifi_cnml_call_service($gs->var['url'],'graph',$args,'direction=out').
                   '"></a>',
          'align' => 'center'));
      } else if (($radio['type']=='radio') or
        ($radio['variable']['mrtg_index']!='')) {
        $args['type'] = 'device';
        $rows[] = array(array(
          'data'=> '<a href='.base_path().'guifi/graph_detail?'.
                   guifi_cnml_args($args).
                   '><img src="'.
                   guifi_cnml_call_service($gs->var['url'],'graph',$args).
                   '"></a>',
          'align' => 'center'));
      }
      $args['type'] = 'pings';
      $rows[] = array(array(
        'data'=> '<a href='.base_path().'guifi/graph_detail?'.
                   guifi_cnml_args($args).
                   '><img src="'.
                   guifi_cnml_call_service($gs->var['url'],'graph',$args).
                   '"></a>',
        'align' => 'center'));
      return array_merge($rows);
}

/**
 * guifi_get_availability
**/

function guifi_graphs_get_pings($hostname, $start = NULL, $end = NULL) {
  $var = array();
  $var['max_latency'] = 0;
  $var['min_latency'] = NULL;
  $var['last'] = NULL;
  $var['avg_latency'] = 0;
  $var['succeed'] = 0;
  $var['samples'] = 0;

  if ($start == NULL)
    $start = time() - 60*60*24*7;
  if ($end == NULL)
    $end = time() - 300;
//  print 'Start/end: '.$start.' '.$end."\n<br />";
  $fn = variable_get('rrddb_path','/home/comesfa/mrtg/logs/').guifi_rrdfile($hostname)."_ping.rrd";
//  print $fn."\n<br />";
  if (file_exists($fn)) {
    $cmd = sprintf("%s fetch %s AVERAGE --start=%d --end=%d",variable_get('rrdtool_path','/usr/bin/rrdtool'),$fn,$start,$end);
//    print $cmd."\n<br />";
    $fp = popen($cmd, "r");
    if (isset($fp)) {
      while (!feof($fp)) {
        $failed = 'nan';
        $n = sscanf(fgets($fp),"%d: %f %f",$interval,$failed,$latency);
        if (is_numeric($failed) && ($n == 3)) {
          $var['succeed'] += $failed;
          $last_suceed = $failed;
          if ($latency > 0) {
//            print $interval.' '.$failed.' '.$latency."\n<br />";
            $var['avg_latency'] += $latency;
            if ($var['max_latency'] < $latency)
              $var['max_latency']    = $latency;
            if (($var['min_latency'] > $latency) || ($var['min_latency'] == NULL))
              $var['min_latency']    = $latency;
          }
          $var['last'] = $interval;
          $var['samples']++;
        }
      }
    }
    pclose($fp);
  }
  if ($var['samples'] > 0) {
    $var['succeed'] = 100 - ($var['succeed'] / $var['samples']);
    $var['avg_latency'] = $var['avg_latency'] / $var['samples'];
    $var['last_sample'] = date('H:i',$var['last']);
    $var['last_succeed'] = 100 - $last_suceed;
  }
  return $var;
}

function _guifi_script_calendar() {
  return "<script type='text/javascript'>
  // Initialize the calendar
  calendar=NULL;

  // This function displays the calendar associated to the input field 'id'
  function showCalendar(id) {
    var el = document.getElementById(id);
    if (calendar != NULL) {
      // we already have some calendar created
      calendar.hide();  // so we hide it first.
    } else {
      // first-time call, create the calendar.
      var cal = new Calendar(TRUE, NULL, selected, closeHandler);
      cal.weekNumbers = FALSE;  // Do not display the week number
      cal.showsTime = TRUE;     // Display the time
      cal.time24 = TRUE;        // Hours have a 24 hours format
      cal.showsOtherMonths = FALSE;    // Just the current month is displayed
      calendar = cal;                  // remember it in the global var
      cal.setRange(1900, 2070);        // min/max year allowed.
      cal.create();
    }

    calendar.setDateFormat('%d-%m-%Y %H:%M');    // set the specified date format
    calendar.parseDate(el.value);                // try to parse the text in field
    calendar.sel = el;                           // inform it what input field we use

    // Display the calendar below the input field
    calendar.showAtElement(el, \"Br\");        // show the calendar

    return FALSE;
  }

  // This function update the date in the input field when selected
  function selected(cal, date) {
    cal.sel.value = date;      // just update the date in the input field.
  }

  // This function gets called when the end-user clicks on the 'Close' button.
  // It just hides the calendar without destroying it.
  function closeHandler(cal) {
    cal.hide();                        // hide the calendar
    calendar = NULL;
  }
        </script>";
}

?>
