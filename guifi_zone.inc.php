<?php
/**
 * @file guifi_zone.inc.php
 * Manage guifi_zone and guifi_networks
 */

/**
 * Implementation of hook_access().
 */
function guifi_zone_access($op, $node) {
  global $user;

  guifi_log(GUIFILOG_TRACE,
    'function guifi_zone_access()',
    $op);

  if (is_numeric($node))
    $node = node_load(array('nid' => $node));


  if ($op == 'view') {
    return TRUE;
  }
  if ($op == 'create') {
    return user_access('create guifi zones');
  }

  guifi_log(GUIFILOG_TRACE,
    'function guifi_zone_access(maintainers_load_uid)',
    // guifi_maintainers_load($node->nid,'zone','uid'));
    $node->nid);


  if (($op == 'update') or ($op == 'delete')) {
    if ((user_access('administer guifi zones')) ||
        (($node->uid == $user->uid) and (user_access('edit own guifi zones'))) ||
        (in_array($user->uid,guifi_maintainers_load($node->nid,'zone','uid')))
        ) {
      return TRUE;
    } else {
      if (in_array($user->uid,guifi_maintainers_parents($node->nid,'uid')))
        return TRUE;
      return FALSE;
    }
  }
  return FALSE;
}



/** zone editing functions
**/

/** guifi_zone_load(): Load the zone from the guifi database.
 */
function guifi_zone_load($node) {

  guifi_log(GUIFILOG_TRACE,
    'function guifi_zone_load()',
    $node);

  if (is_object($node)) {
    $k = $node->nid;
    if ($node->type != 'guifi_zone')
      return FALSE;
  } else
  if (is_numeric($node))
    $k = $node;


  $loaded = db_fetch_object(
    db_query("
    SELECT * FROM {guifi_zone} WHERE id = '%d'",
    $k));

  if (($loaded->nick == '') or ($loaded->nick == NULL))
    $loaded->nick = guifi_abbreviate($loaded->title);

  // if zone map not set, take from parents
  if ( ($loaded->minx==0) and
       ($loaded->miny==0) and
       ($loaded->maxx==0) and
       ($loaded->maxy==0) ) {
    $coords = guifi_zone_get_coords($loaded->master);

    $loaded->minx = $coords['minx'];
    $loaded->miny = $coords['miny'];
    $loaded->maxx = $coords['maxx'];
    $loaded->maxy = $coords['maxy'];
  }

  $loaded->maintainers=guifi_maintainers_load($loaded->id,'zone');

  guifi_log(GUIFILOG_TRACE,
    'function guifi_zone_load()',
    $loaded->maintainers);

  // if notification is NULL, take from the user who created the zone
  if (empty($loaded->notification)) {
    $u = user_load($node->uid);
    $loaded->notification = $u->mail;
  }

  if ($loaded->id != NULL)
    return $loaded;

  return FALSE;
}

function _guifi_get_supplier_name($node) {

  if (is_object($node))
    $k = $node->nid;
  else
    $k = $node;

  $node = db_fetch_object(
    db_query("SELECT title FROM {supplier} WHERE id = '%d'", $k));

  if (is_null($node->title))
    return FALSE;

  return $node->title;
}


function guifi_zone_get_coords($zid) {
  guifi_log(GUIFILOG_TRACE,
    'function guifi_zone_get_coords()',
    $zid);

  if (empty($zid))
    return FALSE;

  $coords = db_fetch_array(db_query(
    'SELECT master, minx, miny, maxx, maxy ' .
    'FROM {guifi_zone} ' .
    'WHERE id=%d',
    $zid));

  if ($coords['minx']==0 and $coords['miny']==0
      and $coords['maxx']==0 and $coords['maxy']==0)
    return guifi_zone_get_coords($coords['master']);
  else {
    unset($coords['master']);
    return $coords;
  }
}

function guifi_zone_root() {
  $root = db_fetch_object(db_query(
    "SELECT id " .
    "FROM {guifi_zone} " .
    "WHERE master = 0"));
  return $root->id;
}

function guifi_zone_autocomplete_field($zid,$fname) {
  if ($fname == 'master') {
    $title = t('Parent zone');
  }
  elseif ($fname == 'guifi_default_zone') {
    $title = t('Default zone for new nodes');
  }
  else {
    $title = t('Zone');
  }

  return array(
     '#type' => 'textfield',
     '#title' => t($title),
     '#description' => t('Find and select the appropriate zone'),
     '#size' => 80,
     '#default_value'=> ($zid!='') ?
         $zid.'-'.guifi_get_zone_name($zid) : NULL,
     '#maxsize'=> 256,
     '#autocomplete_path' => 'guifi/js/select-zone',
  );
}

function guifi_zone_select_field($zid,$fname) {

  $parents = array();
  $parent=$zid;
  $c = 1;
  while ($parent > 0) {
    $result = db_query('
      SELECT z.id zid, z.master master, z.title title
      FROM {guifi_zone} z
      WHERE z.id = %d',
      $parent);
    $row = db_fetch_object($result);
    $parent = $row->master;

    if ($row->zid == $zid) {
      $master = $parent;
      continue;
    }
    $parents[$row->zid] = $row->title;
    $c++;
  }

  $parents = array_reverse($parents, TRUE);

  $lzones['0'] = t('(root zone)');
  $ident = $c;
  foreach ($parents as $k => $value) {
    $lzones[$k] = str_repeat('-',($c+1)-$ident).$value;
    $ident--;
  }


  ob_start();
  print "<br />Zid: $zid Master: $master <br />";
  print_r($lzones);
  $txt = ob_get_clean();
  ob_end_clean();

  $has_peers = FALSE;
  $has_childs = FALSE;
  $qpeer = db_query(
    'SELECT z.id, z.title ' .
    'FROM {guifi_zone} z ' .
    'WHERE z.master=%d '.
    'ORDER BY z.title',
    $master);
  $qchilds = db_query(
    'SELECT z.id, z.title ' .
    'FROM {guifi_zone} z ' .
    'WHERE z.master=%d '.
    'ORDER BY z.title',
    $zid);

  while ($peer = db_fetch_object($qpeer)) {
    $lzones[$peer->id] = str_repeat('-',$c).$peer->title;
    if ($peer->id == $zid) {
      while ($child = db_fetch_object($qchilds)) {
        $has_childs = TRUE;
        $lzones[$child->id] = str_repeat('-',$c+1).$child->title;
      }
    } else {
      $has_peers = TRUE;
    }
  }

  $msg = t('Select to navigate through the available zones, only parent, peer and child zones are shown in the list.<br />By selecting any other zone, the list will be refreshed with the corresponding parents, peers and childs.');
  if ($has_childs)
    $msg .= '<br />'.t('<strong>Attention!</strong>: The currently selected zone has childs, click to view');

//  $msg .= $txt;

  if ($fname == 'master') {
    $title = t('Parent zone');
  }
  elseif ($fname == 'guifi_default_zone') {
    $title = t('Default zone for new nodes');
  }
  else {
    $title = t('Zone');
  }

  $var = explode(',',$fname);
  if (count($var)>1) {
    $zidFn = $var[0];
    $nidFn = $var[1];
  } else {
    $zidFn = $fname;
  }
//  $msg .= ("select zone zid: $zid, nid: $_POST[zid], fname: $fname, zidFn: $zidFn" .
//      " var[0]: $var[0] count(var): ".count($var));

  return array(
    '#type' => 'select',
    '#title' => $title,
    '#parents' => array($zidFn),
    '#default_value' => $zid,
    '#options' => $lzones,
    '#description' => $msg,
    '#prefix' => '<div id="select-zone">',
    '#suffix' => '</div>',
    '#ahah' => array(
      'path' => 'guifi/js/select-zone/'.$fname,
      'wrapper' => 'select-zone',
      'method' => 'replace',
      'effect' => 'fade',
     ),
    );
//'#weight' => $form_weight++,

}

/** guifi_zone_form(): Present the guifi zone editing form.
 */
function guifi_zone_form(&$node, &$param) {
  guifi_log(GUIFILOG_TRACE,
    'function guifi_zone_form()',
    $node);

  drupal_set_breadcrumb(guifi_zone_ariadna($node->id));
  $form_weight = -20;

  $type = node_get_types('type',$node);
  if (($type->has_title)) {
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => check_plain($type->title_label),
      '#required' => TRUE,
      '#default_value' => $node->title,
      '#weight' => $form_weight++,
    );
  }

  $form['jspath'] = array(
   '#type' => 'hidden',
   '#value'=> base_path().drupal_get_path('module','guifi').'/js/'
  );

  $form['master'] = guifi_zone_autocomplete_field($node->master,'master');
  $form['master']['#weight'] = $form_weight++;

  if (($type->has_body)) {
    $form['body_field'] = node_body_field(
      $node,
      $type->body_label,
      $type->min_word_count
    );
    $form['body_field']['#weight'] = $form_weight++;
  }

  // This is for non-admin users, they don't need to edit more information
  if (!user_access('administer guifi zones'))
    return $form;

  $form['nick'] = array(
    '#type' => 'textfield',
    '#title' => t('Short abreviation'),
    '#required' => FALSE,
    '#default_value' => $node->nick,
    '#size' => 8,
    '#maxlength' => 10,
    '#element_validate' => array('guifi_zone_nick_validate'),
    '#description' => t('Single word, 7-bits characters. Used while default values as hostname, SSID, etc...'),
    '#weight' => $form_weight++,
  );

  /*
   * maintainers fieldset
   */
  $form['maintainers'] = guifi_maintainers_form($node,$form_weight);


  $form['time_zone'] = array(
    '#type' => 'select',
    '#title' => t('Time zone'),
    '#required' => FALSE,
    '#default_value' => $node->time_zone,
    '#options' => guifi_types('tz'),
    '#weight' => $form_weight++,
  );
  $form['homepage'] = array(
    '#type' => 'textfield',
    '#title' => t('Zone homepage'),
    '#required' => FALSE,
    '#default_value' => $node->homepage,
    '#size' => 60,
    '#maxlength' => 128,
    '#description' => t('URL of the local community homepage, if exists. Useful for those who want to use this site just for network administration, but have their own portal.'),
    '#weight' => $form_weight++,
  );
  $form['notification'] = array(
    '#type' => 'textfield',
    '#title' => t('email notification'),
    '#required' => TRUE,
    '#default_value' => $node->notification,
    '#size' => 60,
    '#maxlength' => 1024,
    '#element_validate' => array('guifi_emails_validate'),
    '#description' => t('Mails where changes at the zone will be notified. Useful for decentralized administration. If more than one, separated by \',\''),
    '#weight' => $form_weight++,
  );

  // Service parameters
  $form['zone_services'] = array(
    '#type' => 'fieldset',
    '#title' => t('Zone services'),
    '#weight' => $form_weight++,
    '#collapsible' => FALSE,
    '#collapsed' => TRUE,
    '#prefix'=>'<div>',
  );

  $proxystr = guifi_service_str($node->proxy_id);

  function _service_descr($type) {
    return t('Select the default %type to be used at this ' .
        'zone.<br />' .
        'You can find the %type by introducing part of the id number, ' .
        'zone name or proxy name. A list with all matching values ' .
        'with a maximum of 50 values will be created.<br />' .
        'You can refine the text to find your choice.',
        array('%type' => $type));
  }

  $form['zone_services']['proxystr'] = array(
    '#type' => 'textfield',
    '#title' => t('default proxy'),
    '#maxlength' => 80,
    '#default_value'=> $proxystr,
    '#autocomplete_path'=> 'guifi/js/select-service/proxy',
    '#element_validate' => array('guifi_service_name_validate',
      'guifi_zone_service_validate'),
    '#description' => _service_descr('proxy')
  );
  $form['zone_services']['proxy_id'] = array(
    '#type' => 'hidden',
    '#value'=> $node->proxy_id,
  );

  $graphstr = guifi_service_str($node->graph_server);

  $form['zone_services']['graph_serverstr'] = array(
    '#type' => 'textfield',
    '#title' => t('default graphs server'),
    '#maxlength' => 80,
    '#required' => FALSE,
    '#default_value' => $graphstr,
    '#autocomplete_path'=> 'guifi/js/select-service/SNPgraphs',
    '#element_validate' => array('guifi_service_name_validate',
      'guifi_zone_service_validate'),
    '#description' => _service_descr('graph server')
  );
  $form['zone_services']['graph_server'] = array(
    '#type' => 'hidden',
    '#value'=> $node->graph_server,
    '#suffix'=>'</div>',
  );

  // Separació Paràmetre globals de xarxa
  $form['zone_network_settings'] = array(
    '#type'        => 'fieldset',
    '#title'       => t('Zone global network parameters'),
    '#weight'      => $form_weight++,
    '#collapsible' => TRUE,
    '#collapsed'   => TRUE,
    '#preffix'     => '<div>',
  );
  $form['zone_network_settings']['sep-global-param'] = array(
    '#value' => '<hr /><h2>'.t('zone global network parameters').'</h2>',
    '#weight' => $form_weight++,
  );
  $form['zone_network_settings']['dns_servers'] = array(
    '#type' => 'textfield',
    '#title' => t('DNS Servers'),
    '#required' => FALSE,
    '#default_value' => $node->dns_servers,
    '#size' => 60,
    '#maxlength' => 128,
    '#description' => t('The Name Servers of this zone, will inherit parent DNS servers if blank. Separated by ",".'),
    '#weight' => $form_weight++,
  );
  $form['zone_network_settings']['ntp_servers'] = array(
    '#type' => 'textfield',
    '#title' => t('NTP Servers'),
    '#required' => FALSE,
    '#default_value' => $node->ntp_servers,
    '#size' => 60,
    '#maxlength' => 128,
    '#description' => t('The network time protocol (clock) servers of this zone, will inherit parent NTP servers if blank. Separated by ",".'),
    '#weight' => $form_weight++,
  );
  $form['zone_network_settings']['ospf_zone'] = array(
    '#type' => 'textfield',
    '#title' => t('OSPF zone id'),
    '#required' => FALSE,
    '#default_value' => $node->ospf_zone,
    '#size' => 60,
    '#maxlength' => 128,
    '#element_validate' => array('guifi_zone_ospf_validate'),
    '#description' => t('The id that will be used when creating configuration files for the OSPF routing protocol so all the routers within the zone will share a dynamic routing table.'),
    '#weight' => $form_weight++,
    '#suffix'=>'</div>',
  );

  // Separació Paràmetres dels mapes
  $form['zone_mapping'] = array(
    '#type' => 'fieldset',
    '#title' => t('Zone mapping parameters'),
    '#weight' => $form_weight++,
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $form['zone_mapping']['sep-maps-param'] = array(
    '#value' => '<hr /><h2>'.t('zone mapping parameters').'</h2>',
    '#weight' => $form_weight++,
  );
  // if gmap key defined, prepare scripts anf launch google maps integration
  if (guifi_gmap_key()) {
    drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_gmap_zonelimits.js','module');
    $form['zone_mapping']['GMAP'] = array(
      '#type' => 'item',
      '#title' => t('Map'),
      '#description' => t('Drag the South-West/North-East corners over the map to change the zone boundaries.'),
      '#suffix' => '<div id="map" style="width: 100%; height: 437px; margin:5px;"></div>',
      '#weight' => $form_weight++,
    );
    $form['guifi_wms'] = array(
      '#type' => 'hidden',
      '#value' => variable_get('guifi_wms_service',''),
    );
  }


  $form['zone_mapping']['MIN_help'] = array(
    '#type' => 'item',
    '#title' => t('Bottom-left (SW) corner'),
    '#description' => t('Coordinates (Lon/Lat) of the bottom-left (South-West) corner of the map.'),
    '#weight' => $form_weight++,
  );

  $form['zone_mapping']['minx'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->minx,
    '#size' => 12,
    '#maxlength' => 24,
    '#prefix' => '<table style="width: 32em"><tr><td style="width: 12em">',
    '#suffix' => '</td>',
    '#element_validate' => array('guifi_lon_validate'),
    '#description' => t('Longitude'),
    '#weight' => $form_weight++,
  );
  $form['zone_mapping']['miny'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->miny,
    '#size' => 12,
    '#prefix' => '<td style="width: 12em">',
    '#suffix' => '</td></tr></table>',
    '#element_validate' => array('guifi_lat_validate'),
    '#description' => t('Latitude'),
    '#maxlength' => 24,
    '#weight' => $form_weight++,
  );
  $form['zone_mapping']['MAX_help'] = array(
    '#type' => 'item',
    '#title' => t('Upper-right (NE) corner'),
    '#description' => t('Coordinates (Lon/Lat) of the upper-right (North-East) corner of the map.'),
    '#weight' => $form_weight++,
  );
  $form['zone_mapping']['maxx'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->maxx,
    '#size' => 12,
    '#maxlength' => 24,
    '#prefix' => '<table style="width: 32em"><tr><td style="width: 12em">',
    '#suffix' => '</td>',
    '#element_validate' => array('guifi_lon_validate'),
    '#description' => t('Longitude'),
    '#weight' => $form_weight++,
  );
  $form['zone_mapping']['maxy'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->maxy,
    '#size' => 12,
    '#maxlength' => 24,
    '#prefix' => '<td style="width: 12em">',
    '#suffix' => '</td></tr></table>',
    '#element_validate' => array('guifi_lat_validate'),
    '#description' => t('Latitude'),
    '#weight' => $form_weight++,
  );

  $form['#attributes'] = array('class'=>zone_form);

  return $form;
}

function guifi_zone_service_validate($element, &$form_state) {
  switch ($element['#name']) {
    case 'proxystr':
      $s = &$form_state['values']['proxy_id']; break;
    case 'graph_serverstr':
      $s = &$form_state['values']['graph_server']; break;
  }
  switch ($element['#value']) {
  case t('No service'):
    $s = '-1';
    break;
  case t('Take from parents'):
    $s = '';
    break;
  default:
    $nid = explode('-',$element['#value']);
    $s = $nid[0];
  }
}



/** guifi_zone_prepare(): Default values
 */
function guifi_zone_prepare(&$node) {
  global $user;

  // Init default values
  if ($node->id=='') {
    if ($node->notification == '')
      $node->notification = $user->mail;
    $node->time_zone = '+01 2 2';
    $node->minx = -70;
    $node->miny = -50;
    $node->maxx = 70;
    $node->maxy = 50;
  }

}

/** guifi_zone_map_help Print help text for embedded maps
 */
function guifi_zone_map_help($rid) {
  $output = '<a href="'.variable_get("guifi_maps", 'http://maps.guifi.net').'/world.phtml?REGION_ID='.$rid.'" target=_top>'.t('View the map in full screen and rich mode').'</a>';
  $output .= '<p>'.t('Select the lens controls to zoom in/out or re-center the map at the clicked position. If the image has enough high resolution, you can add a node at the red star position by using the link that will appear.').'</p>';
  return $output;
}


function guifi_zone_hidden_map_fileds($node) {
  $output  = '<form><input type="hidden" id="minx" value="'.$node->minx.'"/>';
  $output .= '<input type="hidden" id="miny" value="'.$node->miny.'"/>';
  $output .= '<input type="hidden" id="maxx" value="'.$node->maxx.'"/>';
  $output .= '<input type="hidden" id="maxy" value="'.$node->maxy.'"/>';
  $output .= '<input type="hidden" id="zone_id" value="'.$node->id.'"/>';
  $output .= '<input type="hidden" id="guifi-wms" value="'.variable_get('guifi_wms_service','').'"/></form>';
  return $output;
}

/** guifi_zone_simple_map(): Print the page, show the zone map and nodes without zoom.
 */
function guifi_zone_simple_map($node) {
  if (guifi_gmap_key()) {
    drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_gmap_zone.js','module');
    $output = '<div id="map" style="width: 100%; height: 380px; margin:5px;"></div>';
    $output .= guifi_zone_hidden_map_fileds($node);
  } else {
    $output .= '
      <IFRAME FRAMEBORDER="0" ALIGN=right SRC="'.
      variable_get(
        "guifi_maps",
        'http://maps.guifi.net').
      '/world.phtml?IFRAME=Y&MapSize=300,240&REGION_ID='.
      $node->id.
      '" WIDTH="350" HEIGHT="290" MARGINWIDTH="0" MARGINHEIGHT="0" SCROLLING="AUTO">';
    $output .= t('Sorry, your browser can\'t display the embedded map');
    $output .= '</IFRAME>';
  }
  return $output;
}

function guifi_zone_title_validate($element, &$form_state) {
  if (empty($element['#value']))
    form_error($element,t('You must specify a title for the zone.'));
}

function guifi_zone_tree_recurse($zid, $children) {
  $childs = array();
  if ($children[$zid]) {
    foreach ($children[$zid] as $id => $zone) {
      $childs[$id] = $zone;
      $childs[$id]->childs = guifi_zone_tree_recurse($id,$children);
    }
  }

  return $childs;
}

function guifi_zone_nick_validate($element, &$form_state) {
  if (empty($element['#value'])) {
    $nick = guifi_abbreviate($form_state['values']['title']);
    drupal_set_message(t('Zone nick has been set to:').' '.$nick);
    $form_state['values']['nick'] = $nick;
  }
}

function guifi_lat_validate($element, &$form_state) {
  if ($element['#value'] != '0') {
    if (empty($element['#value']))
      form_error($element,t('Latitude must be specified.'));
  }
  if (!is_numeric($element['#value']))
    form_error($element,t('Latitude must be numeric'));
  if (($element['#value'] > 90) || ($element['#value'] < -90))
    form_error($element,t('Latitude must be between -90 and 90.'));
}

function guifi_lon_validate($element, &$form_state) {
  if ($element['#value'] != '0') {
    if (empty($element['#value']))
      form_error($element,t('Longitude must be specified.'));
  }
  if (!is_numeric($element['#value']))
    form_error($element,t('Longitude must be numeric'));
  if (($element['#value'] > 180) || ($element['#value'] < -180))
    form_error($element,t('Longitude must be between -180 and 180.'));
}

function guifi_zone_ospf_validate($element, &$form_state) {
  if  ($element['#value'] != htmlentities($element['#value'], ENT_QUOTES))
    form_error(
      t('No special characters allowed for OSPF id, use just 7 bits chars.')
    );

  if (str_word_count($element['#value']) > 1)
    form_error(
      t('OSPF zone id have to be a single word.'));
}

function guifi_emails_validate($element, &$form_state) {
  if (empty($element['#value']))
    form_error($element,t('You have to specify at least one notification email address.'));
  $emails = guifi_notification_validate($element['#value']);
  if (!$emails)
    form_error($element,
      t('Error while validating email address'));
  else
    form_set_value($element,$emails,$form_state);
}

function guifi_zone_validate($node) {

  // if node master is root, check that there is not another zone as root
  if ($node->master == 0) {
      $qry = db_query(
           'SELECT id, title, nick
            FROM {guifi_zone}
            WHERE master = 0');
     while ($rootZone = db_fetch_object($qry))
     {
        if ($node->nid != $rootZone->id)
          form_set_error('master',
            t('The root zone is already set to "%s". Only one root zone can be present at the database. Delete/change the actual root zone before assigning a new one or choose another parent.',
              array('%s' => $rootZone->title)));
     }
  }

  // check if master zone is a child
 $childs = guifi_zone_childs($node->nid);
  foreach ( $childs as $key => $child) {
    if ( $child == $node->master ) {
      $childname = db_fetch_object(db_query(
           'SELECT title
            FROM {guifi_zone}
            WHERE id = %s', $child));
       form_set_error('master',
         t("You can't use a child zone <strong>%child</strong> from %zone as master!!!", array('%child' => $childname->title, '%zone' => $node->title)));
    }
  }
  // check that master is not being assigned to itself
  if (!empty($node->nid))
  if ($node->master == $node->nid)
    form_set_error('master',
      t("Master zone can't be set to itself"));

  // check that zone area is consistent
  if ($node->minx > $node->maxx)
    form_set_error('minx', t("Longitude: Min should be less than Max").
      ' '.$node->minx.'/'.$node->maxx);
  if ($node->miny > $node->maxy)
    form_set_error('miny', t("Latitude: Min should be less than Max"),
      ' '.$node->miny.'/'.$node->maxy);

  /*
   * Validate maintainer(s)
   */
  guifi_maintainers_validate($node);
}

/** guifi_zone_insert(): Insert a zone into the database.
 */
function guifi_zone_insert($node) {
  $log = '';

  $node->new=TRUE;
  $node->id   = $node->nid;
  $node->minx = (float)$node->minx;
  $node->maxx = (float)$node->maxx;
  $node->miny = (float)$node->miny;
  $node->maxy = (float)$node->maxy;
  $to_mail = explode(',',$node->notification);

  // $node->maintainer=guifi_maintainers_save($node->maintaners);

  $nzone = _guifi_db_sql(
    'guifi_zone',
    array('id' => $node->id),(array)$node,$log,$to_mail);

  guifi_maintainers_save($nzone,'zone',$node->maintainers);

  guifi_notify(
    explode(',',$node->notification),
    t('A new zone %nick-%name has been created',
      array('%nick' => $node->nick,'%name' => $node->title)),
    $log);


 // if box set, maps should be rebuilt to add the new zone box in the lists
 if (($node->minx) || ($node->miny) || ($node->maxx) || ($node->maxy)) {
//   touch(variable_get('guifi_rebuildmaps','/tmp/ms_tmp/REBUILD'));
   variable_set('guifi_refresh_cnml',time());
   variable_set('guifi_refresh_maps',time());

 }
 guifi_clear_cache($node->id);
}

/** guifi_zone_update(): Save zone changes into the database.
 */
function guifi_zone_update($node) {

  global $user;
  $log = '';

  // if box changed, maps should be rebuilt
  $pz = db_fetch_object(db_query('SELECT * FROM {guifi_zone} z WHERE z.id = %d',$node->nid));
  if (($pz->maxx != $node->maxx) || ($pz->maxy != $node->maxy) || ($pz->minx != $node->minx) || ($pz->miny != $node->miny)) {
//    touch(variable_get('guifi_rebuildmaps','/tmp/ms_tmp/REBUILD'));
    variable_set('guifi_refresh_cnml',time());
    variable_set('guifi_refresh_maps',time());

    cache_clear_all();
  }

//  $node->maintainer=guifi_maintainers_save($node->maintainers);

  $node->minx = (float)$node->minx;
  $node->maxx = (float)$node->maxx;
  $node->miny = (float)$node->miny;
  $node->maxy = (float)$node->maxy;
  $to_mail = explode(',',$node->notification);
  $nzone = _guifi_db_sql(
    'guifi_zone',
    array('id' => $node->nid),
    (array)$node,
    $log,
    $to_mail);

  guifi_maintainers_save($node->nid,'zone',$node->maintainers);

  guifi_notify(
    explode(',',$node->notification),
    t('Zone %nick-%name has been updated',
      array('%nick' => $node->nick,'%name' => $node->title)),
    $log);

   guifi_clear_cache($node->nid);
}

/** guifi_zone_delete(): Delete a zone
**/
function guifi_zone_delete(&$node) {
  global $user;
  $log = '';

  $delete = TRUE;
  $qn = db_fetch_object(db_query("
    SELECT count(*) count
    FROM {guifi_networks}
    WHERE zone=%d",
    $node->nid));
  if ($qn->count) {
    drupal_set_message(t('FATAL ERROR: Can\'t delete a zone which have networks allocated. Database broken. Contact your system administrator'),'error');
    $delete = FALSE;
  }
  $ql = db_fetch_object(db_query("
    SELECT count(*) count
    FROM {guifi_location}
    WHERE zone_id=%d",
    $node->nid));
  if ($ql->count) {
    drupal_set_message(t('FATAL ERROR: Can\'t delete a zone with nodes. Database broken. Contact your system administrator'),'error');
    $delete = FALSE;
  }

  $to = explode(',',$node->notification);
  $to[] = variable_get('guifi_contact','webmestre@guifi.net');
  if (!$delete) {
    $messages = drupal_get_messages(NULL, FALSE);
    guifi_notify(
    $to,
    t('ALERT: Zone %nick-%name has been deleted, but have errors:',
      array('%nick' => $node->nick,'%name' => $node->title)),
    implode("\n",$messages['error']));
    return;
  }

  // perform deletion
  $node->deleted = TRUE;
  $nzone = _guifi_db_sql(
    'guifi_zone',
    array('id' => $node->id),
    (array)$node,
    $log,
    $to);
  guifi_notify(
    $to,
    t('Zone %nick-%name has been deleted',
      array('%nick' => $node->nick,'%name' => $node->title)),
    $log);
  cache_clear_all();
  variable_set('guifi_refresh_cnml',time());
  variable_set('guifi_refresh_maps',time());

  return;
}
/** guifi_zone_get_parents(): Get the guifi zone parents
 */
function guifi_zone_get_parents($id) {

  $parent=$id;
  $parents[] = $id;
  while ($parent > 0) {
    $result = db_query('
      SELECT z.master master
      FROM {guifi_zone} z
      WHERE z.id = %d',
      $parent);
    $row = db_fetch_object($result);
    $parent = $row->master;
    $parents[] = $parent;
  }

  return $parents;
}

/** guifi_zone_ariadna(): Get an array of zone hierarchy to breadcumb
**/
function guifi_zone_ariadna($id = 0, $link = 'node/%d') {
  $ret = array();
  $ret[] = l(t('Home'), NULL);
  $ret[] = l(t('Main menu'),'guifi');
  foreach (array_reverse(guifi_zone_get_parents($id)) as $parent)
  if ($parent > 0) {
    $parentData = db_fetch_array(db_query(
      'SELECT z.id, z.title ' .
      'FROM {guifi_zone} z ' .
      'WHERE z.id = %d ',
      $parent));
    $ret[] = l($parentData['title'],sprintf($link,$parentData['id']));
  }
  $ret[count($ret)-1] = '<b>'.$ret[count($ret)-1].'</b>';

  $child = array();
  $query = db_query('SELECT z.id, z.nick, z.title ' .
      'FROM {guifi_zone} z ' .
      'WHERE z.master = %d ' .
      'ORDER BY z.weight, z.title',
      $id);
  $c=0;
  while ($zoneChild = db_fetch_array($query) and ($c < 50)) {
    $child[] = l($zoneChild['nick'],sprintf($link,$zoneChild['id']),
      array(
        'attributes' => array('title' => $zoneChild['title'])
      ));
    $c++;
  }
  if ($c >= 50)
    $child[] = l(t('more...'),'node/'.$id.'/view/nodes');

  if (count($child)) {
    $child[0] = '<br /><small>('.$child[0];
    $child[count($child)-1] = $child[count($child)-1].')</small>';
    $ret = array_merge($ret,$child);
  }
  return $ret;
}

/** guifi_zone_data(): outputs the zone information data
**/
function guifi_zone_data($zone) {

  $rows[] = array(t('zone name'),$zone->nick.' - <b>' .$zone->title .'</b>');

  if (count($zone->maintainers)) {
    $rows[] = array(
      t('Maintenance & SLAs'),
      implode(', ',guifi_maintainers_links($zone->maintainers)));
  } else {
  	$pmaintainers = guifi_maintainers_parents($zone->id);
    if (!empty($pmaintainers))
      $rows[] = array(
        t('Maintenance & SLAs').' '.t('(from parents)'),
        implode(', ',guifi_maintainers_links($pmaintainers)));
  }

  if ($zone->homepage)
    $rows[] = array(t('homepage'),l($zone->homepage,$zone->homepage));
  $rows[] = array(t('default proxy'),
              l(guifi_service_str($zone->proxy_id),
                guifi_zone_get_service($zone,'proxy_id', TRUE))
              );

  if ($zone->graph_server > 0)
    $gs = node_load(array('nid' => $zone->graph_server));
  else
    $gs = node_load(array('nid' => guifi_graphs_get_server($zone->id,'zone')));

  $rows[] = array(t('default graph server'),array(
    'data' => l(guifi_service_str($zone->graph_server),
              $gs->l, array('attributes' => array('title' => $gs->nick.' - '.$gs->title))),
    'colspan' => 2));

  $rows[] = array(t('network global information').':', NULL);
//  $rows[] = array(t('Mode'),t($zone->zone_mode));
  $rows[] = array(t('DNS Servers'),$zone->dns_servers);
  $rows[] = array(t('NTP Servers'),$zone->ntp_servers);
  $rows[] = array(t('OSPF zone'),$zone->ospf_zone);
  $tz = db_fetch_object(db_query("SELECT description FROM {guifi_types} WHERE type = 'tz' AND text = '%s'",$zone->time_zone));
  $rows[] = array(t('Time zone'),$tz->description);

  return array_merge($rows);
}

function guifi_zone_get_service($id, $type ,$path = FALSE) {
  if (is_numeric($id))
    $z = guifi_zone_load($id);
  else
    $z = $id;

  $ret = NULL;
  if (!empty($z->$type))
    $ret = $z->$type;
  else
    if ($z->master)
      $ret = guifi_zone_get_service($z->master,$type);

  if ($path)
    if ($ret)
      $ret = 'node/'.$ret;

  return $ret;
}

/** guifi_zone_totals(): summary of a zone
**/
function guifi_zone_totals($zones) {

  $result = db_query(
    "SELECT status_flag, count(*) total " .
    "FROM {guifi_location} l " .
    "WHERE l.zone_id in (%s) " .
    "GROUP BY status_flag",
    implode(',',$zones));
  while ($sum = db_fetch_object($result)) {
    $summary[$sum->status_flag] = $sum->total;
    $summary['Total'] = $summary['Total'] + $sum->total;
  }

  return $summary;
}

function guifi_zone_childs_and_parents($zid) {
  return array_merge(guifi_zone_childs($zid),
           guifi_zone_get_parents($zid));
}

function guifi_zone_childs($zid) {
  return array_keys(guifi_zone_childs_tree($zid,9999));
}

function guifi_zone_childs_tree($parents, $maxdepth = 1, &$depth = 0) {

  if (is_numeric($parents))
    $parents = array($parents => array('depth' => 0,'master' => 0));
  guifi_log(GUIFILOG_TRACE,'function guifi_zone_childs_tree(childs)',$parents);

  // check only current depth
  $current_depth = array();
  foreach ($parents as $k => $v) {
    if ($v['depth'] == $depth)
      $current_depth[] = $k;
  }
  $depth++;

  $result = db_query('SELECT z.id, z.master ' .
                     'FROM {guifi_zone} z ' .
                     'WHERE z.master IN ('.implode(',',$current_depth).')');

  $childs = $parents;
  $found = FALSE;
  while ($child = db_fetch_object($result)) {
    $childs[$child->id] = array('depth' => $depth,'master' => $child->master);
    $found = TRUE;
  }

  if ($found and ($depth < $maxdepth))
    $childs = guifi_zone_childs_tree($childs,$maxdepth,$depth);

  return $childs;
}

function guifi_zone_childs_tree_depth($parents, $maxdepth = 1, &$depth = 0) {

  if (is_numeric($parents))
    $parents = array($parents => array('depth' => 0,'master' => 0));
  guifi_log(GUIFILOG_TRACE,'function guifi_zone_childs_tree_depth(childs)',$parents);

  // check only current depth
  $current_depth = array();
  foreach ($parents as $k => $v) {
    if ($v['depth'] == $depth)
      $current_depth[] = $k;
  }
  $depth++;

  $result = db_query('SELECT z.id, z.master ' .
                     'FROM {guifi_zone} z ' .
                     'WHERE z.master IN ('.implode(',',$current_depth).')');

  $childs = $parents;
  $found = FALSE;
  while ($child = db_fetch_object($result)) {
    $childs[$child->id] = array('depth' => $depth,'master' => $child->master);
    $found = TRUE;
  }

  if ($found and ($depth < $maxdepth))
    $childs = guifi_zone_childs_tree($childs,$maxdepth,$depth);

  return $childs;
}

function guifi_zone_availability($zone,$desc = "all") {
  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_zone_availability(%s)',
    $desc),$zone);

    $oneyearfromnow = (time()- '31622400');

  function _guifi_zone_availability_devices($nid) {
    $oneyearfromnow = (time()- '31622400');
    $qry  = db_query(
      'SELECT d.id did, d.nick dnick, d.flag dflag, d.timestamp_changed changed ' .
      'FROM {guifi_devices} d ' .
      'WHERE d.type = "radio" ' .
      '  AND d.nid=%d ' .
      'ORDER BY d.nick',
      $nid);

    $rows = array();

    while ($d = db_fetch_array($qry)) {
      $dev = guifi_device_load($d['did']);

      if (guifi_device_access('update',$dev))
        $edit =
          l(guifi_img_icon('edit.png'),'guifi/device/'.$d['did'].'/edit',
            array(
              'html' => TRUE,
              'attributes' => array(
                'title' => t('edit device'),
                'target' => '_blank'))).
          l(guifi_img_icon('drop.png'),'guifi/device/'.$d['did'].'/delete',
            array(
              'html' => TRUE,
              'attributes' => array(
                'title' => t('delete device'),
                'target' => '_blank')));
      else
        $edit = NULL;

      $ip = guifi_main_ip($d['did']);

      $status_url = guifi_cnml_availability(
         array('device' => $d['did'],'format' => 'long'));

      if ( !empty($d['changed'])) {
        if ( $d['changed'] < $oneyearfromnow )
          $dchanged = array('data' => '<b><font color="#AA0000">'.format_date($d['changed'],'custom', t('d/m/Y')).'</font></b>');
        else
          $dchanged = array('data' => format_date($d['changed'],'custom', t('d/m/Y')));
      } else
        $dchanged = array('data' => t('never changed'));

      $rows[] = array(
        array('data'=>
          $edit.l($d['dnick'],'guifi/device/'.$d['did'])
             ),
        array(
          'data' => l($ip['ipv4'].'/'.$ip['maskbits'],
          guifi_device_admin_url($d['did'],$ip['ipv4']),
          array('attributes' => array('title' => t('Connect to the device on a new window'),
            'target' => '_blank'))),
          'align' => 'right'
        ),
        array('data' => $d['dflag'].$status_url,'class' => $d['dflag']),
        $dchanged,
      );
    }
    guifi_log(GUIFILOG_TRACE,'function guifi_zone_availability_device()',$rows);

    return $rows;
  }

  $childs = guifi_zone_childs($zone->id);
  $childs[] = $zone->id;

  switch ($desc) {
    case 'pending':
      $msg = t('Pending/to review & date last changed.');
      $lbreadcrumb = 'node/%d/view/pending';
      $qstatus = "Working";
      break;
    case 'all':
    default:
      $msg = t('Availability');
      $lbreadcrumb = 'node/%d/view/availability';
      $qstatus = "all";
  }
  drupal_set_breadcrumb(guifi_zone_ariadna($zone->id,$lbreadcrumb));

  $output = '<h2>' .$msg.' @  ' .$zone->title .'</h2>';

  $sql =
    'SELECT z.id zid, z.title ztitle, z.nick znick, ' .
    '  l.id nid, l.nick nnick, l.status_flag nstatus, l.notification as contact, l.timestamp_created ncreated, l.timestamp_changed nchanged ' .
    'FROM {guifi_zone} z, {guifi_location} l ' .
    'WHERE z.id=l.zone_id ' .
    '  AND l.status_flag != "'.$qstatus.'"' .
    '  AND z.id IN ('.implode(',',$childs).') '.
    'ORDER BY ncreated DESC';
  guifi_log(GUIFILOG_TRACE,'function guifi_zone_availability()',$sql);

  $Msql = pager_query($sql,variable_get("guifi_pagelimit", 50));

  $rows = array();
  $currZ = -1;
  while ($d = db_fetch_array($Msql)) {
    $drows = _guifi_zone_availability_devices($d['nid']);

    $nsr = count($drows);
    if (empty($nsr))
      $nsr = 1;

    if (guifi_node_access('update',$d['nid']))
      $edit =
        l(guifi_img_icon('edit.png'),'node/'.$d['nid'].'/edit',
          array('html' => TRUE,'attributes' => array('target' => '_blank'))).
        l(guifi_img_icon('drop.png'),'node/'.$d['nid'].'/delete',
          array('html' => TRUE,'attributes' => array('target' => '_blank'))).
        l(guifi_img_icon('mail.png'),'mailto:'.$d['contact'],
          array('html' => TRUE,'attributes' => array('target' => '_blank')));
    else
      $edit = NULL;

   if ( !empty($d['nchanged'])) {
      if ( $d['nchanged'] < $oneyearfromnow )
        $dnchanged = array('data' => '<b><font color="#AA0000">'.format_date($d['nchanged'],'custom', t('d/m/Y')).'</font></b>', 'class' => $d['nchanged'], 'rowspan' => $nsr);
      else
        $dnchanged = array('data' => format_date($d['nchanged'],'custom', t('d/m/Y')), 'class' => $d['nchanged'], 'rowspan' => $nsr);
   } else
       $dnchanged = array('data' => t('never changed'));

    $rows[] = array(
      array('data' => $d['nid'],
       'align' => 'right',
       'rowspan' => $nsr),
      array('data'=> $edit.
        l($d['nnick'],'node/'.$d['nid'],
          array('attributes' => array('target' => '_blank'))),
       'rowspan' => $nsr),
      array('data' => $d['nstatus'],
       'class' => $d['nstatus'],
       'rowspan' => $nsr),
     array('data' => format_date($d['ncreated'],'custom', t('d/m/Y')),
       'class' => $d['ncreated'],
       'rowspan' => $nsr),
      $dnchanged,
    );
    end($rows);
    $krow = key($rows);

    if (count($drows)) {
      $rows[$krow] = array_merge($rows[$krow],array_shift($drows));

      foreach ($drows as $k2 => $v)
        $rows[] = $v;
    }

  }
  $header = array(
      array('data' => t('node ID')),
      array('data' => t('node'), NULL, NULL,'style' => 'text-align: center'),
      array('data' => t('status'), NULL, NULL,'style' => 'text-align: center'),
      array('data' => t('created'), NULL, NULL,'style' => 'text-align: center'),
      array('data' => t('updated'), NULL, NULL,'style' => 'text-align: center'),
      array('data' => t('device'), NULL, NULL,'style' => 'text-align: center'),
      array('data' => t('device IP'), NULL, NULL,'style' => 'text-align: center'),
      array('data' => t('device status'), NULL, NULL,'style' => 'text-align: center'),
      array('data' => t('device updated'), NULL, NULL,'style' => 'text-align: center')
  );

  $output .= theme('table', $header, $rows,array('width' => '100%'));
  $output .= theme_pager(NULL, variable_get("guifi_pagelimit", 50));
  $node = node_load(array('nid' => $zone->id));
  $output .= theme_links(module_invoke_all('link', 'node', $node, FALSE));
  print theme('page',$output, FALSE);
  return;
}

/**  guifi_zone_view(): zone view page
**/
function guifi_zone_view($node, $teaser = FALSE, $page = FALSE, $block = FALSE) {

  node_prepare($node);
  if ($teaser)
    return $node;
  if ($block)
    return $node;

  $node->content['data']= array(
    '#value' => theme_table(
      NULL,
      array(
        array(
          array(
            'data' => '<small>'.theme_guifi_zone_data($node, FALSE).'</small>'.
              theme_guifi_contacts($node),
            'width' => '35%'
          ),
          array(
            'data' => guifi_zone_simple_map($node),
            'width' => '65%'
          )
        )
      ),
      array('width' => '100%')
    ),
    '#weight' => 1);
  $node->content['graph']=array(
    '#value' => theme_guifi_zone_stats($node, FALSE),
    '#weight' => 2);
  $node->content['nodes']= array(
    '#value' => theme_guifi_zone_nodes($node, FALSE),
    '#weight' => 3);

  return $node;
}

/** Miscellaneous utilities related to zones
**/

/** guifi_zone_l(): Creates a link to the zone
**/
function guifi_zone_l($id, $title = NULL, $linkto = 'node/') {
  if ($id == 0)
    $id = guifi_zone_root();
  if (empty($title))
    $title = guifi_get_zone_name($id);
  return l($title, $linkto. $id);
}

/**
 * Gets nearest zone of a selected point
 * @param $lat Latitude of the point
 * @param $lon Longitude of the point
 * @return mixed[] The best selected zone which can contain a point
 */
function guifi_zone_get_nearest($lat, $lon, $zones = NULL) {
  if( $zones == NULL ) {
    $zones = guifi_zone_get_nearest_candidates($lat, $lon);
  }

  if( $zones ) {
    $maxd = 0;
    $oGC = new GeoCalc();
    foreach( $zones as $zone ) {
      if( empty( $zone['d']) ) {
        $d1 = $oGC->EllipsoidDistance($lat, $lon, $zone['max_lat'], $zone['max_lon']);
        $d2 = $oGC->EllipsoidDistance($lat, $lon, $zone['min_lat'], $zone['min_lon']);
        $zone['d'] = sqrt( $d1 * $d1 + $d2 * $d2 );
      }

      if( empty( $maxd ) || $zone['d'] < $maxd ) {
        $maxd = $zone['d'];
        $candidate_zone = $zone;
      }
    }
    return $candidate_zone;
  } else {
    return FALSE;
  }
}

/**
 * Get candidate zones to be inside a given point
 * @param $lat Latitude of the point
 * @param $lon Longitude of the point
 * @param $max_distance Maximum distance to be considered a candidate
 * @param $zones Zones to be looked after
 * @return mixed[] Array of zone which can contain a point and are small enough
 */
function guifi_zone_get_nearest_candidates($lat, $lon, $max_distance = 15, $zones = NULL) {
  if( $zones == NULL ) {
    $zones = guifi_zone_get_containing($lat, $lon);
  }

  if( !$zones ) {
    return FALSE;
  }

  $candidates = array();
  foreach( $zones as $zone ) {
    $oGC = new GeoCalc();
    $d1 = $oGC->EllipsoidDistance($lat, $lon, $zone['max_lat'], $zone['max_lon']);
    $d2 = $oGC->EllipsoidDistance($lat, $lon, $zone['min_lat'], $zone['min_lon']);
    $zone['d'] = sqrt( $d1 * $d1 + $d2 * $d2 );

    if( $zone['d'] < $max_distance ) {
      $candidates[] = $zone;
    }
  }
  return $candidates;
}

/**
 * Gets zones which can contain a selected point
 * @param $lat Latitude of the point
 * @param $lon Longitude of the point
 * @return mixed[] Array of the zones which can contain the specified point
 */
function guifi_zone_get_containing($lat, $lon) {
  $zones = array();
  $query = db_query("SELECT id, title, nick, minx AS min_lon, maxx AS max_lon, miny AS min_lat, maxy AS max_lat FROM {guifi_zone} WHERE minx < %f AND maxx > %f AND miny < %f AND maxy > %f", $lon, $lon, $lat, $lat);
  while ($zone = db_fetch_array($query)) {
    $zones[] = $zone;
  }
  return $zones;
}

function theme_guifi_zone_nodes($node,$links = TRUE) {

  if (!isset($node->id))
    $node->id=$node->nid;

  $output = '<h2>' .t('Nodes listed at') .' ' .$node->title .'</h2>';

  // Going to list child zones totals
  $result = db_query('SELECT z.id, z.title FROM {guifi_zone} z WHERE z.master = %d ORDER BY z.weight, z.title',$node->id);

  $rows = array();

  $header = array(
      array('data' => t('Zone name')),
      array('data' => t('Online'), NULL, NULL,'style' => 'text-align: right'),
      array('data' => t('Planned'), NULL, NULL,'style' => 'text-align: right'),
      array('data' => t('Building'), NULL, NULL,'style' => 'text-align: right'),
      array('data' => t('Testing'), NULL, NULL,'style' => 'text-align: right'),
      array('data' => t('Inactive'), NULL, NULL,'style' => 'text-align: right'),
      array('data' => t('Total'), NULL, NULL,'style' => 'text-align: right'));
  while ($zone = db_fetch_object($result)) {
    $summary = guifi_zone_totals(guifi_zone_childs($zone->id));
    $rows[] = array(
      array('data' => guifi_zone_l($zone->id,$zone->title,'node/'),'class' => 'zonename'),
      array('data' => number_format($summary['Working'] ,0, NULL,variable_get('guifi_thousand','.')),'class' => 'Working','align' => 'right'),
      array('data' => number_format($summary['Planned'] ,0, NULL,variable_get('guifi_thousand','.')),'class' => 'Planned','align' => 'right'),
      array('data' => number_format($summary['Building'],0, NULL,variable_get('guifi_thousand','.')),'class' => 'Building','align' => 'right'),
      array('data' => number_format($summary['Testing'] ,0, NULL,variable_get('guifi_thousand','.')),'class' => 'Testing','align' => 'right'),
      array('data' => number_format($summary['Inactive'] ,0, NULL,variable_get('guifi_thousand','.')),'class' => 'Inactive','align' => 'right'),
      array('data' => number_format($summary['Total']   ,0, NULL,variable_get('guifi_thousand','.')),'class' => 'Total','align' => 'right'));
    if (!empty($summary))
      foreach ($summary as $key => $sum)
        $totals[$key] = $totals[$key] + $sum;
  }
  $rows[] = array(
    array(
      'data' => NULL,
      'class' => 'zonename'),
    array('data' => number_format($totals['Working'] ,0, NULL,variable_get('guifi_thousand','.')), 'class' => 'Online','align' => 'right'),
    array('data' => number_format($totals['Planned'] ,0, NULL,variable_get('guifi_thousand','.')), 'class' => 'Planned','align' => 'right'),
    array('data' => number_format($totals['Building'],0, NULL,variable_get('guifi_thousand','.')),'class' => 'Building','align' => 'right'),
    array('data' => number_format($totals['Testing'] ,0, NULL,variable_get('guifi_thousand','.')), 'class' => 'Testing','align' => 'right'),
    array('data' => number_format($totals['Inactive'] ,0, NULL,variable_get('guifi_thousand','.')), 'class' => 'Inactive','align' => 'right'),
    array('data' => number_format($totals['Total']   ,0, NULL,variable_get('guifi_thousand','.')),'class' => 'Total','align' => 'right'));

   if (count($rows)>1)
     $output .= theme('table', $header, $rows);

  // Going to list the zone nodes
  $rows = array();
  $result = pager_query('
    SELECT l.id,l.nick, l.notification, l.zone_description,
      l.status_flag, count(*) radios
    FROM {guifi_location} l LEFT JOIN {guifi_radios} r ON l.id = r.nid
    WHERE l.zone_id = %d
    GROUP BY 1,2,3,4,5
    ORDER BY radios DESC, l.nick',
    50,0,
    'SELECT count(*)
    FROM {guifi_location}
    WHERE zone_id = %d',
  $node->id);
  $header = array(
    array('data' => t('nick (shortname)')),
    array('data' => t('supernode')),
    array('data' => t('area')),
    array('data' => t('status')));
  while ($loc = db_fetch_object($result)) {
    if ($loc->radios == 1)
      $loc->radios = t('No');
    $rows[] = array(
      array('data' => guifi_zone_l($loc->id,$loc->nick,'node/')),
      array('data' => $loc->radios),
      array('data' => $loc->zone_description),
      array('data' => t($loc->status_flag),'class' => $loc->status_flag));
  }
  if (count($rows)>0) {
    $output .= theme('table', $header, $rows);
    $output .= theme_pager(NULL, 50);
  }

  if ($links) {
    drupal_set_breadcrumb(guifi_zone_ariadna($node->id,'node/%d/view/nodes'));
    $node = node_load(array('nid' => $node->id));
    $output .= theme_links(module_invoke_all('link', 'node', $node, FALSE));
    print theme('page',$output, FALSE);
    return;
  }

  return $output;
}


/** * guifi_zone_map(): Print de page show de zone map and nodes.
 */
function theme_guifi_zone_map($node) {

  drupal_set_breadcrumb(guifi_zone_ariadna($node->id,'node/%d/view/map'));
  $node = node_load(array('nid' => $node->id));

  if (guifi_gmap_key()) {
    drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_gmap_zone.js','module');
    $output = '<div id="map" style="width: 100%; height: 640px; margin:5px;"></div>';
    $output .= guifi_zone_hidden_map_fileds($node);
  } else {
    $output = guifi_zone_map_help($node->id);
    $output .= '<IFRAME FRAMEBORDER="0" SRC="'.variable_get("guifi_maps", 'http://maps.guifi.net').'/world.phtml?IFRAME=Y&MapSize=600,450&REGION_ID='.$node->id.'" ALIGN="CENTER" WIDTH="670" HEIGHT="500" MARGINWIDTH="0" MARGINHEIGHT="0" SCROLLING="AUTO">';
    $output .= t('Sorry, your browser can\'t display the embedded map');
    $output .= '</IFRAME>';
  }

  $output .= theme_links(module_invoke_all('link', 'node', $node, FALSE));

  print theme('page',$output, FALSE);
  return;
}

/** guifi_zone_networks(): outputs the zone networks
**/
function theme_guifi_zone_networks($zone) {

  drupal_set_breadcrumb(guifi_zone_ariadna($zone->id,'node/%d/view/ipv4'));
  $zone = node_load(array('nid' => $zone->id));

  $ips_allocated = guifi_ipcalc_get_ips();

  if (user_access('administer guifi networks'))
    $output = l(t('add network'),'node/'.$zone->id.'/view/ipv4/add');

  // zone & parents
  $table = guifi_ipv4_print_data($zone,'parents',$ips_allocated);
  $output .= theme('box', t('zone and zone parent(s) network allocation(s)'), $table);

  // zone childs
  $table = guifi_ipv4_print_data($zone,'childs',$ips_allocated);
  $output .= theme('box', t('zone child(s) network allocation(s)'), $table);

  $output .= theme_links(module_invoke_all('link', 'node', $zone, FALSE));

  print theme('page',$output, FALSE);

  return;
}

/** theme_guifi_zone_data():  outputs the zone information
**/
function theme_guifi_zone_data($zone,$links = TRUE) {

  $zone = node_load(array('nid' => $zone->id));

  drupal_set_breadcrumb(guifi_zone_ariadna($zone->id));

  $table = theme('table', NULL, guifi_zone_data($zone),array('width' => '100%'));
  $output .= theme('box', t('zone information'), $table);

  if ($links) {
    drupal_set_breadcrumb(guifi_zone_ariadna($node->id));
    $node = node_load(array('nid' => $node->id));
    $output .= theme_links(module_invoke_all('link', 'node', $node, FALSE));
    print theme('page',$output, FALSE);
    return;
  }

  return $output;
}

/** theme_guifi_zone_stats():  outputs the stats graph
**/
function theme_guifi_zone_stats($zone,$links = TRUE) {
  global $base_url;
  $output = '<script type="text/javascript" src="'.$base_url.'/misc/collapse.js"></script>';
  $output .= '<fieldset class="collapsible collapsed">';
  $output .= '<legend>'.t('zone statistics').'</legend>';
  $output .= '<div>'.theme('table', NULL, guifi_zone_stats_data($zone->id),array('width' => '100%')).'</div>';
  $output .= '</fieldset><br />';
  return $output;
}
/** guifi_zone_stats_data(): outputs the zone statistics data
**/
function guifi_zone_stats_data($zone) {
  $rows[] = array('<a href="'.$base_url.'guifi/menu/stats/nodes?zone='.$zone.'"><img src="'.$base_url.'guifi/stats/chart?id=1&amp;zone='.$zone.'&amp;width=400&amp;height=300&amp;title=void" /></a>','&nbsp;');
  return array_merge($rows);
}
?>
