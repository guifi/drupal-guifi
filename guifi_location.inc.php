<?php
/**
 * @file guifi_location.inc.php
 * Manage guifi_location
 * rroca
 */

/* main node (locations) hooks */

/** guifi_location_access(): construct node permissions
*/
function guifi_location_access($op, $node) {
  global $user;

  if (is_numeric($node))
    $node = node_load($node);

  if ($op == 'view')
    return TRUE;

  if ($op == 'create') {
    return user_access('create guifi nodes');
  }

  if ($op == 'update' or $op == 'delete') {

  	guifi_log(GUIFILOG_TRACE,
      'function guifi_location_access()',
      $op. ' - '.$node->nid);

    if (((user_access('administer guifi zones')) || ($node->uid == $user->uid)) ||
        (($node->uid == $user->uid) and (user_access('edit own guifi nodes'))) ||
        // if it's a mantainer
        (in_array($user->uid,guifi_maintainers_load($node->nid,'location','uid'))) ||
        // if it's a funder
        (in_array($user->uid,guifi_funders_load($node->nid,'location','uid')))
       )
    {
      return TRUE;
    } else {
      // Check is authorized for being a maintainer of the zone and there is not maintainer
      if ((empty($node->maintainers)) and (guifi_zone_access($op,$node->zone_id)))
        return TRUE;

      return FALSE;
    }
  }
  return FALSE;
}

/** guifi_location_ariadna(): Get an array of zone hierarchy and node devices
 * to build the breadcrumb
**/
function guifi_location_ariadna($node, $nlink = 'node/%d',$dlink = 'guifi/device/%d', $zlink = 'node/%s') {
  if (is_numeric($node)) {
    $node = node_load($node);
    $node->title = $node->nick; // hack to show the node title
  }

  $ret = array();
  $ret[] = l(t('Home'), NULL);
  $ret[] = l(t('Main menu'),'guifi');

  foreach (array_reverse(guifi_zone_get_parents($node->zone_id)) as $parent)
  if ($parent > 0) {
    $parentData = db_query(
      'SELECT z.id, z.title ' .
      'FROM {guifi_zone} z ' .
      'WHERE z.id = :zid ',
      array(':zid' => $parent))->fetchAssoc();
    $ret[] = l($parentData['title'],sprintf($zlink,$parentData['id']));
  }
  $ret[] = l($node->title,sprintf($nlink,$node->id));

  $ret[count($ret)-1] = '<b>'.$ret[count($ret)-1].'</b>';

  $child = array();
  $query = db_query('SELECT d.id, d.nick, d.type ' .
      'FROM {guifi_devices} d ' .
      'WHERE d.nid = :nid ' .
      'ORDER BY d.id DESC',
      array(':nid' => $node->id));
  $c = 0;
  while ($dChild = $query->fetchAssoc() and ($c <= 10)) {
    $child[] = l($dChild['nick'],sprintf($dlink,$dChild['id']),
      array(
        'attributes' => array('title' => $dChild['type'])
      ));
    $c++;
  }

  if ($c>=10)
    $child[] = l(t('more...'),'node/'.$node->id.'/view/devices');


  if (count($child)) {
    $child[0] = '<br /><small>('.$child[0];
    $child[count($child)-1] = $child[count($child)-1].')</small>';
    $ret = array_merge($ret,$child);
  }

  return $ret;
}

function guifi_location_access_callback($node) {
  $node = node_load($node);
  if ($node->type === 'guifi_location')
    return user_access('access content');
  else
    return FALSE;
}

/** guifi_location_load(): load and constructs node array from the database
**/

function guifi_location_load($nodes) {

 foreach ($nodes as $node) {
   $location = db_query("SELECT * FROM {guifi_location} WHERE id = :nid", array(':nid' => $node->nid))->fetchObject();
     foreach ($location as $k => $value) {

      $nodes[$node->nid]->$k = $value;
// TODO MIQUEL
/*
    $nodes[$node->nid]->$value = guifi_maintainers_load($location->id,'location');
    $nodes[$node->nid]->$value = guifi_funders_load($location->id,'location');
*/
    }
  }
return $nodes;
}

/** node editing functions
**/

function guifi_location_prepare(&$node){
  global $user;

  if(empty($node->nid)){
    if(is_numeric($node->title)){
      $zone = node_load($node->title);
      $node->zone_id = $node->title;
      $default = t('<nodename>');
      $node->title = NULL;
      $node->nick = $zone->nick.$default;
    }
    $node->notification = $user->mail;
    $node->status_flag = 'Planned';
  }
  // Position
  // if given parameters get/post, fill lat/lon
  if (isset($_POST['lat'])){$node->lat = $_POST['lat'];}
  if (isset($_POST['lon'])){$node->lon = $_POST['lon'];}
  if (isset($_GET['lat'])){$node->lat = $_GET['lat'];}
  if (isset($_GET['lon'])){$node->lon = $_GET['lon'];}
  if (isset($_GET['Lat'])){$node->lat = $_GET['Lat'];}
  if (isset($_GET['Lon'])){$node->lon = $_GET['Lon'];}
  if (isset($_GET['zone'])){$node->zone_id = $_GET['zone'];}
  if (isset($_GET['zone'])){$node->zone_id = $_GET['zone'];}

  if (isset($_GET['lat']))
    if (isset($_GET['lon'])) {
          $defzone_qry = db_query("SELECT id, ((maxx-minx)+(maxy-miny)) as distance FROM {guifi_zone} WHERE minx < :minx AND maxx > :maxx AND miny < :miny AND maxy > :maxy ORDER by distance LIMIT 1",
          array(':minx' => $_GET['lon'], ':maxx' => $_GET['lon'], ':miny' => $_GET['lat'], ':maxy' => $_GET['lat']))->fetchAssoc();
          $node->ndfzone = $defzone_qry['id'];
    }
  $coord=guifi_coord_dtodms($node->lat);
  if($coord != NULL) {
    $node->latdeg = $coord[0];
    $node->latmin = $coord[1];
    $node->latseg = $coord[2];
  }
  $coord=guifi_coord_dtodms($node->lon);
  if ($coord != NULL) {
    $node->londeg = $coord[0];
    $node->lonmin = $coord[1];
    $node->lonseg = $coord[2];
  }
}

function guifi_location_form($node, $form_state) {
  global $user;

  $type = node_type_get_type($node);

  if (!empty($node->zone_id))
    drupal_set_breadcrumb(guifi_location_ariadna($node));
  else
    drupal_set_breadcrumb(NULL);

  // ----
  // El títol el primer de tot
  // ------------------------------------------------
  if (($type->has_title)) {
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => check_plain($type->title_label),
      '#required' => TRUE,
      '#default_value' => $node->title,
    );
  }

  $form_weight = 0;
  $form['nick'] = array(
    '#type' => 'textfield',
    '#title' => t('Nick'),
    '#required' => FALSE,
    '#size' => 20,
    '#maxlength' => 20,
    '#element_validate' => array('guifi_location_nick_validate'),
    '#default_value' => $node->nick,
    '#description' => t("Unique identifier for this node. Avoid generic names such 'MyNode', use something that really identifies your node.<br />Short name, single word with no spaces, 7-bit chars only, will be used for  hostname, reports, etc."),
    '#weight' => $form_weight++,
  );
  $form['notification'] = array(
    '#type' => 'textfield',
    '#title' => t('Contact'),
    '#required' => FALSE,
    '#size' => 60,
    '#maxlength' => 1024,
    '#element_validate' => array('guifi_emails_validate'),
    '#default_value' => $node->notification,
    '#description' => t("Who did possible this node or who to contact with regarding this node if it is distinct of the owner of this page. Use valid emails, if you like to have more than one, separated by commas.'"),
    '#weight' => $form_weight++,
  );

   /*
   * maintainers fieldset
   */
  $form['maintainers'] = guifi_maintainers_form($node,$form_weight);
   /*
   * funders fieldset
   */
  //TODO MIQUEL
 // $form['funders'] = guifi_funders_form($node,$form_weight);

  $form['settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Node settings'),
    '#weight' => $form_weight++,
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#weight' => $form_weight++,
  );
    // Si ets administrador pots definir el servidor de dades
  if (user_access('administer guifi zones')){
    $graphstr = guifi_service_str($node->graph_server);

    $form['settings']['graph_serverstr'] = array(
      '#type' => 'textfield',
      '#title' => t('default graphs server'),
      '#maxlength' => 60,
      '#required' => FALSE,
      '#default_value' => $graphstr,
      '#autocomplete_path'=> 'guifi/js/select-service/SNPgraphs',
      '#element_validate' => array('guifi_service_name_validate',
        'guifi_zone_service_validate'),
      '#description' => t('Select the <em>graph server</em> to be used at this node.<br />You can find the <em>graph server</em> by introducing part of the id number, zone name or graph server name. A list with all matching values with a maximum of 50 values will be created.<br />You can refine the text to find your choice.'),
    );
    $form['settings']['graph_server'] = array(
      '#type' => 'hidden',
      '#value'=> $node->graph_server,
    );
  }
  $form['settings']['stable'] = array(
    '#type' => 'select',
    '#title' => t("It's supposed to be a stable online node?"),
    '#required' => FALSE,
    '#default_value' => ($node->stable ? $node->stable : 'Yes'),
    '#options' => array(
      'Yes' => t('Yes, is intended to be kept always on,  avalable for extending the mesh'),
      'No' => t("I'm sorry. Will be connected just when I'm online")),
    '#description' =>
      t("That helps while planning a mesh network. We should know which locations are likely available to provide stable links."),
    '#weight' => 1,
  );

  // Ask for a license agreement if is a new node
  if (empty($node->nid)) {
    $form['license'] = array(
      '#type' => 'item',
      '#title' => t('License and usage agreement'),
      '#markup' => t(variable_get('guifi_license', NULL)),
      '#description' => t('You must accept this agreement to be authorized to create new nodes.'),
      '#weight' => $form_weight++,
    );
    if (empty($node->agreement))
      $agreement = 'No';
    else
      $agreement = $node->agreement;

    $form['agreement']= array(
      '#type' => 'radios',
      '#default_value' =>$agreement,
      '#options' => array('Yes' => t('Yes, I have read this and accepted')),
      '#element_validate' => array('guifi_location_agreement_validate'),
      '#weight' => $form_weight++,
    );
  } else {
    $form['agreement']= array(
      '#type' => 'hidden',
      '#default_value' => 'Yes',
    );
  }

  if (empty($node->nid)) {
    if (empty($node->ndfzone)) {
      if (empty($node->zone_id)) {
        if(!empty($user->guifi_default_zone)) {
          $zone_id = $user->guifi_default_zone;
        }
      } else {
        $zone_id = $node->zone_id;
      }
    } else {
      $zone_id = $node->ndfzone;
    }
  } else {
    $zone_id = $node->zone_id;
  }

  $form['zone_did'] = guifi_zone_autocomplete_field($zone_id,'zone_id');
  $form['zone_did']['#weight'] = $form_weight++;

  $form['zone_id'] = array(
    '#type' => 'hidden',
    '#value'=> $zone_id,
  );
  // ----
  // position
  // ------------------------------------------------

  $form['position'] = array(
    '#type' => 'fieldset',
    '#title' => t('Node position settings'),
    '#weight' => $form_weight++,
    '#collapsible' => FALSE,
    '#weight' => $form_weight++,
  );

  
  if (guifi_gmap_key()) {
    drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_gmap_node.js');
    $form['position']['GMAP'] = array(
      '#type' => 'item',
      '#title' => t('Map'),
      '#description' => t('Select the point where the node has to be placed.'),
      '#suffix' => '<input style="width: 240px;" id="mapSearch" type="text" /><div id="map" style="width: 100%; height: 437px; margin:5px;"></div>',
      '#weight' => 0,
    );
    $form['guifi_wms'] = array(
      '#type' => 'hidden',
      '#value' => variable_get('guifi_wms_service',''),
      '#attributes' => array('id' => 'guifi-wms'),
    );
    $form['lat'] = array(
      '#type' => 'hidden',
      '#value' => $node->lat,
      '#attributes' => array('id' => 'lat'),
    );
    $form['lon'] = array(
      '#type' => 'hidden',
      '#value' => $node->lon,
      '#attributes' => array('id' => 'lon'),
    );
  }
  $form['position']['longitude'] = array(
    '#type' => 'item',
    '#title' => t('Longitude'),
    '#prefix' => '<table><tr><th>&nbsp;</th><th>'.
      t('degrees (decimal values allowed)').
        '</th><th>'.
        t('minutes').
        '</th><th>'.
        t('seconds').
        '</th></tr><tr><td>',
    '#suffix' => '</td>',
    '#weight' => 1,
  );
  $form['position']['londeg'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->londeg,
    '#size' => 12,
    '#maxlength' => 24,
    '#element_validate' => array('guifi_lon_validate'),
    '#prefix' => '<td>',
    '#suffix' => '</td>',
    '#weight' => 2,
    '#attributes' => array('id' => 'edit-londeg'),
  );
  $form['position']['lonmin'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->lonmin,
    '#size' => 12,
    '#maxlength' => 24,
    '#prefix' => '<td>',
    '#suffix' => '</td>',
    '#weight' => 3,
    '#attributes' => array('id' => 'edit-lonmin'),
  );
  $form['position']['lonseg'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->lonseg,
    '#size' => 12,
    '#maxlength' => 24,
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#weight' => 4,
    '#attributes' => array('id' => 'edit-lonseg'),
  );
  $form['position']['latitude'] = array(
    '#type' => 'item',
    '#title' => t('Latitude'),
    '#prefix' => '<tr><td>',
    '#suffix' => '</td>',
    '#weight' => 5,
  );
  $form['position']['latdeg'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->latdeg,
    '#size' => 12,
    '#maxlength' => 24,
    '#element_validate' => array('guifi_lat_validate'),
    '#prefix' => '<td>',
    '#suffix' => '</td>',
    '#weight' => 6,
    '#attributes' => array('id' => 'edit-latdeg'),
  );
  $form['position']['latmin'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->latmin,
    '#size' => 12,
    '#maxlength' => 24,
    '#prefix' => '<td>',
    '#suffix' => '</td>',
    '#weight' => 7,
    '#attributes' => array('id' => 'edit-latmin'),
  );
  $form['position']['latseg'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->latseg,
    '#size' => 12,
    '#maxlength' => 24,
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
    '#weight' => 8,
    '#attributes' => array('id' => 'edit-latseg'),
  );

  $form['position']['zone_description'] = array(
    '#type' => 'textfield',
    '#title' => t('Zone description'),
    '#required' => FALSE,
    '#size' => 60,
    '#maxlength' => 128,
    '#default_value' => $node->zone_description,
    '#description' => t("Zone, address, neighborhood. Something that describes your area within your location.<br />If you don't know your lat/lon, please provide street and number or crossing street."),
    '#weight' => 9,
  );

  $form['position']['elevation'] = array(
    '#type' => 'textfield',
    '#title' => t('Antenna elevation'),
    '#required' => FALSE,
    '#size' => 5,
    '#length' => 20,
    '#maxlength' => 20,
    '#default_value' => $node->elevation,
    '#element_validate' => array('guifi_elevation_validate'),
    '#description' => t("Antenna height over the floor level."),
    '#weight' => 10,
  );

  if ( !empty($node->id) ) {
    $radios = array();
    $query = db_query("SELECT * FROM {guifi_radios} WHERE nid = :nid", array(':nid' => $node->id));
    while ($radio = $query->fetchAssoc()) {
      $radios[] = $radio;
    }
  }
  else {
    $radios = '';
  }

  if (count($radios) < 1) {
    $form['status_flag'] = array(
      '#type' => 'select',
      '#title' => t("Node status"),
      '#default_value' => ( $node->status_flag ? $node->status_flag : 'Planned') ,
      '#required' => FALSE,
      '#options' => array('Reserved' => t('Reserved'),'Inactive' => t('Inactive'),'Planned' => t('Planned')),
    '#weight' => $form_weight++,
    );
  } else {
    $form['status_flag'] = array(
      '#type' => 'hidden',
      '#default_value' => $node->status_flag,
    '#weight' => $form_weight++,
    );
  }
  return $form;
}

function guifi_location_agreement_validate($element, &$form_state) {
  if ($element['#value'] != 'Yes'){
    form_error($element,
      t('You must read and accept the license terms and conditions to be allowed to create nodes.'));
  }
}

function guifi_location_nick_validate($element, &$form_state) {
  if (empty($element['#value'])) {
    $nick = guifi_abbreviate($form_state['values']['title']);
    drupal_set_message(t('Zone nick has been set to:').' '.$nick);
    $form_state['values']['nick'] = $nick;

    return;
  }
  guifi_validate_nick($element['#value']);

  $query = db_query("SELECT nick FROM {guifi_location} WHERE lcase(nick) = :nick AND id <> :id",
    array(':nick' => strtolower($element['#value']), ':id' => $form_state['values']['nid']));
  if ($query->fetchField()){
    form_set_error('nick', t('Nick already in use.'));
  }
}


function guifi_location_get_service($id, $type ,$path = FALSE) {
  if (is_numeric($id))
    $z = node_load($id);
  else
    $z = $id;

  $ret = NULL;
  if (!empty($z->$type))
    $ret = $z->$type;
  else
    $ret = guifi_zone_get_service($z->zone_id,$type);

  if ($path)
    if ($ret)
      $ret = 'node/'.$ret;

  return $ret;
}

/** guifi_location_validate(): Confirm that an edited guifi item has fields properly filled in.
 */
function guifi_location_validate($node,$form) {
  guifi_validate_nick($node->nick);

  $zone_id = explode('-',$node->zone_id);
  // not at root zone
  if (($zone_id[0] == 0) or ($zone_id[0] == guifi_zone_root())){
    form_set_error('zone_id',
      t('Can\'t be assigned to root zone, please assign the node to an appropiate zone.'));
  }else{
    $nz=0;
    guifi_zone_childs_tree($zone_id[0], 3, $nz);
    if($nz>2){
      form_set_error('zone_id',
        t('Can\'t be assigned to parent zone, please assign the node to an final zone.'));
    }
  }

  if ($node->elevation == 0){$node->elevation = NULL;}
  if (($node->elevation < -1) && ($node->elevation != NULL)){
    form_set_error('elevation',
      t('Elevation must be above the floor! :)'));
  }
  if (($node->elevation > 261) && ($node->elevation != NULL)){
    form_set_error('elevation',
      t('Do you mean that you are flying over the earth??? :)'));
  }

  /*
   * Validate maintainer(s)
   */
  guifi_maintainers_validate($node);
  /*
   * Validate funder(s)
   */
  guifi_funders_validate($node);

}

/** guifi_location_insert(): Create a new node in the database
 */
function guifi_location_insert($node) {
  global $user;
  $log = '';

  $coord=guifi_coord_dmstod($node->latdeg,$node->latmin,$node->latseg);
  if($coord!=NULL){
    $node->lat=$coord;
  }
  $coord=guifi_coord_dmstod($node->londeg,$node->lonmin,$node->lonseg);
  if($coord!=NULL){
    $node->lon=$coord;
  }

  if ($node->lat == 0){$node->lat = NULL;}
  if ($node->lon == 0){$node->lon = NULL;}

  $to_mail = explode(',',$node->notification);
  $node->new=TRUE;
  $node->id  = $node->nid;
  $node->lat = (float)$node->lat;
  $node->lon = (float)$node->lon;
  $nnode = _guifi_db_sql(
    'guifi_location',
    array('id' => $node->nid),(array)$node,$log,$to_mail);
  guifi_notify(
    $to_mail,
    t('The node %name has been CREATED by %user.',array('%name' => $node->title, '%user' => $user->name)),
    $log);

  guifi_maintainers_save($nnode,'location',$node->maintainers);
  guifi_funders_save($nnode,'location',$node->funders);


  // Refresh maps
  variable_set('guifi_refresh_cnml',time());
  variable_set('guifi_refresh_maps',time());

  guifi_clear_cache($node->id);
}

/** guifi_location_update(): Update a node in the database
*/
function guifi_location_update($node) {
  global $user;
  $log = '';

  $coord=guifi_coord_dmstod($node->latdeg,$node->latmin,$node->latseg);
  if($coord!=NULL){
    $node->lat=$coord;
  }
  $coord=guifi_coord_dmstod($node->londeg,$node->lonmin,$node->lonseg);
  if($coord!=NULL){
    $node->lon=$coord;
  }

  if ($node->lat == 0){$node->lat = NULL;}
  if ($node->lon == 0){$node->lon = NULL;}

  $to_mail = explode(',',$node->notification);

  // Refresh maps?
  $pn = db_query(
    'SELECT l.*
    FROM {guifi_location} l
    WHERE l.id = :nid',
    array(':nid' => $node->nid))->fetchObject();

  if (($pn->lat != $node->lat) || ($pn->lon != $node->lon) || ($pn->status_flag != $node->status_flag)) {
  // touch(variable_get('guifi_rebuildmaps','/tmp/ms_tmp/REBUILD'));
    variable_set('guifi_refresh_cnml',time());
    variable_set('guifi_refresh_maps',time());
    cache_clear_all();
  }

  $node->lat = (float)$node->lat;
  $node->lon = (float)$node->lon;

  guifi_maintainers_save($node->nid,'location',$node->maintainers);
  guifi_funders_save($node->nid,'location',$node->funders);

  $nnode = _guifi_db_sql(
    'guifi_location',
    array('id' => $node->nid),
    (array)$node,
    $log,$to_mail);
  guifi_notify(
    $to_mail,
    t('The node %name has been UPDATED by %user.',array('%name' => $node->title, '%user' => $user->name)),
    $log);

  guifi_clear_cache($node->nid);
}

/** guifi_location_delete(): deletes a given node

**/
function guifi_location_delete($node) {
  global $user;
  $depth = 0;

  $to_mail = explode(',',$node->notification);
  $log = _guifi_db_delete('guifi_location',array('id' => $node->nid),$to_mail,$depth);
  drupal_set_message($log);
  guifi_notify(
           $to_mail,
           t('The node %name has been DELETED by %user.',array('%name' => $node->title, '%user' => $user->name)),
           $log);
  cache_clear_all();
  variable_set('guifi_refresh_cnml',time());
  variable_set('guifi_refresh_maps',time());

  return;
}

/** node visualization (view) function calls */
/** guifi_location_view(): outputs the node information

**/
function guifi_location_view($node, $view_mode, $langcode = NULL) {

  if ($view_mode == 'teaser')
    return $node;
  if ($view_mode == 'block')
    return $node;

  drupal_set_breadcrumb(guifi_location_ariadna($node));

  $node->content['data'] = array(
    '#type' => 'markup',
    '#weight' => 1,
    '#markup' => theme('table',
                  array('header' => NULL, 
                        'rows' => array(
                          array(
                            array(
                              'data' =>'<small>'.theme_guifi_location_data($node).theme_guifi_contacts($node).'</small>',
                              'width' => '50%'),
                            array(
                              'data' => theme_guifi_location_map($node),
                               'width' => '50%'),
                          )
                        ),
                        'attributes' => array('width' => '100%')
                        )
                  )
  );

  $node->content['graphs'] = array(
    '#type' => 'markup',
    '#weight' => 2,
    '#markup' => theme_guifi_location_graphs_overview($node),
    );
  $node->content['devices'] = array(
    '#type' => 'markup',
    '#weight' => 3,
    '#markup' => theme_guifi_location_devices_list($node),
    );
  $node->content['wdsLinks'] = array(
    '#type' => 'markup',
    '#weight' => 4,
    '#markup' => theme_guifi_location_links_by_type($node->id,'wds'),
    );
  $node->content['cableLinks'] = array(
    '#type' => 'markup',
    '#weight' => 5,
    '#markup' => theme_guifi_location_links_by_type($node->id,'cable'),
    );
  $node->content['clientLinks'] = array(
    '#type' => 'markup',
    '#weight' => 6,
    '#markup' => theme_guifi_location_links_by_type($node->id,'ap/client'),
    );
    return $node;
}

function guifi_location_hidden_map_fileds($node) {
  $output  = '<from>';
  $output .= '<input type="hidden" id="lat" value="'.$node->lat.'"/>';
  $output .= '<input type="hidden" id="lon" value="'.$node->lon.'"/>';
  $output .= '<input type="hidden" id="zone_id" value="'.$node->zone_id.'"/>';
  $output .= '<input type="hidden" id="guifi-wms" value="'.variable_get('guifi_wms_service','').'"/></form>';
  return $output;
}

/** guifi_location_print_distances(): list of neighbors

**/
function guifi_location_distances_map($node) {

  if (empty($node->id))
    $node = node_load($node);

  $rows = array();

  $lat2='';
  $lon2='';
  if (!empty($_GET['lat2']))
    $lat2 = $_GET['lat2'];
  else
    $lat2 = "NA";
  if (!empty($_GET['lon2']))
    $lon2 = $_GET['lon2'];
  else
    $lon2 = "NA";
  if (!empty($_GET['name2']))
    $name2 = $_GET['name2'];
  else
    $name2 = "NA";


  drupal_set_title(t('distances map from').' '.
    guifi_get_zone_nick($node->zone_id).
    '-'.$node->nick);
  if (guifi_gmap_key()) {
    drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_gmap_dist.js');


    $rows[] = array(array('data' => t('Click on the map to get a new path profile ( Thanks to <a href="http://www.heywhatsthat.com">HeyWhatsThat</a> ) to check the Line Of Sight<br />Click on the path profile to see the point on the map'),'align' => 'center'));
    $rows[] = array(array('data' => 'Profile graph and Countour Layer provided by: <a href="http://www.heywhatsthat.com">HeyWhatsThat</a> Copyright 2012 Michael Kosowsky. <b>All rights reserved</b><br>Visit <a href="http://wisp.heywhatsthat.com">HeyWhatsThat WISP</a> for tools for planning wireless networks.<br><a href="javascript:;" onclick="profileclick(event)"><img id="profile" src="'.base_path().drupal_get_path('module', 'guifi').'/js/marker_start.png" /></a>','align' => "center"));
    
    $rows[] = array('<div id="map" style="width: 100%; height: 600px; margin:5px;"></div>');
    
    $rows[] = array(array('data' => '<div style="float:left;">'.t('Distance:').'&nbsp;</div>'.'<div id="tdistance" style="float:left;">0</div>'.'<div style="float:left;">&nbsp;Km.&nbsp;&nbsp;&nbsp;&nbsp;'.t('Azimuth:').'&nbsp;</div>'.'<div id="tazimut" style="float:left;">0</div>&nbsp;'.t('degrees')));
    $output = theme('table', array('header' => NULL, 'rows' => $rows));

    $output .=  '<form>' .
      '<input type="hidden" value="'.$node->lat.'" id="lat" />'.
      '<input type="hidden" value="'.$node->lon.'" id="lon" />' .
      '<input type="hidden" value="'.$lat2.'" id="lat2" />'.
      '<input type="hidden" value="'.$lon2.'" id="lon2" />' .
      '<input type="hidden" value="'.base_path().drupal_get_path('module','guifi').'/js/'.'" id="edit-jspath" />' .
      '<input type="hidden" value="'.variable_get('guifi_wms_service','').'" id="guifi-wms" />' .
      '<input type="hidden" value="'.$node->elevation.'" id="elevation" />' .
      '</form>';
  }

  $node = node_load($node->id);
  drupal_set_breadcrumb(guifi_location_ariadna($node));

  return $output;

}

function guifi_location_distances($node) {
  if (empty($node->id))
    $node = node_load($node);

  drupal_set_title(t('distances from').' '.
    guifi_get_zone_nick($node->zone_id).
    '-'.$node->nick);
  $output .= drupal_render(drupal_get_form('guifi_location_distances_form',$node));
  drupal_set_breadcrumb(guifi_location_ariadna($node));

  return $output;
}

function guifi_location_distances_form($none ,$form_state, $node) {
  global $base_url;


  $form = array();
  $form_state['#redirect'] = FALSE;

  // default values
  $filters = array(
    'dmin'   => 0,
    'sn' => 1,
    'dmax'   => 30,
    'search' => NULL,
    'max'    => 25,
    'skip'   => 0,
    'status' => "All",
    'from_node' => $node->id,
    'azimuth' => "0,360",
  );

  // initialize filters using default values or passed by form
  if (!empty($form_state['values']['filters']))
    $form_state['values']['filters'] =
      array_merge($filters,$form_state['values']['filters']);
    else
      $form_state['values']['filters'] = $filters;

  $form['filters_region'] = guifi_devices_select_filter($form_state,'guifi_location_distances');

  $form['list-devices'] = guifi_location_distances_list($form_state['values']['filters'],$node);

  return $form;
}

function guifi_location_distances_list($filters,$node) {

  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_location_distances_list(%d)',$node->id),
    $_POST);

  $orig = $node->id;

  // storing lat/lon from the current node to be user for computing
  // distances with the other nodes
  $lat1 = $node->lat;
  $long1 = $node->lon;

  // store the node nickname to be used for literal at the profiles
  $node1 = $node->nick;

//  $filters = $form_state['values']['filters'];

  // get the nodes and compute distances

  $result = db_query("SELECT n.id, n.lat, n.lon, n.nick, n.status_flag, n.zone_id, n.timestamp_changed, count(*) radios FROM {guifi_location} n LEFT JOIN {guifi_radios} r ON n.id = r.nid WHERE n.id != :nid AND (n.lat != '' AND n.lon != '')AND (n.lat != 0 AND n.lon != 0) GROUP BY 1", array(':nid' => $node->id));

  $oGC = new GeoCalc();
  $nodes = array();
  $rows = array();
  $totals[] = NULL;

  if (isset($_POST['op'])) {
    if ($_POST['op'] == t('Next page'))
       $filters['skip'] = $filters['skip'] + $filters['max'];
    if ($_POST['op'] == t('Previous page'))
       $filters['skip'] = $filters['skip'] - $filters['max'];

    $nc = 0;

    $allow_next = FALSE;
    if ($filters['skip'])
      $allow_prev = TRUE;
    else
      $allow_prev = FALSE;
  }

  while ($node = $result->fetchAssoc()) {
     $distance = round($oGC->EllipsoidDistance($lat1, $long1, $node["lat"], $node["lon"]),3);

     // Apply filters
     if ( $filters['sn'] and $node["radios"] < 2) continue;

     if ($distance <=  $filters['dmax'])
     if ($distance >=  $filters['dmin'])
     if (($filters['status'] == 'All') or ($filters['status'] == $node['status_flag']))
     {
       $nodes[] = array_merge(array('distance' => $distance),$node);
     }
  }

  // Filter form
  $fw = 0;
//  guifi_devices_select_filter($form,$form_state,$fw);

  $form = array(
    '#type' => 'fieldset',
 //   '#title' => t('filters'),
 //   '#weight' => 0,
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
 //   '#weight' => $fweight++,
    '#prefix' => '<div id="list-devices">',
    '#suffix' => '</div>',
  );


  if (count($nodes)==0) {
    $form['empty'] = array(
      '#type'=> 'item',
      '#title'=> t('No nodes found. The list is empty'),
      '#value'=> t('Th given query has returned no rows.'),
      '#description'=> t('Use the filters to get some results'),
      '#weight' => $fw++,
    );
    return $form;
  }

  asort($nodes);

  // header

  $form['z'] = array(
    '#type' => 'fieldset',
    '#tree' => TRUE,
    '#weight' => $fw++
  );
  $form['z'][-1]['h_node'] = array(
    '#type'=> 'item',
    '#title'=> t('Node'),
    '#description'=> t('Zone'),
    '#prefix' => '<table><tr><th>',
    '#suffix' => '</th>',
    '#weight' => $fw++,
  );
  $form['z'][-1]['h_distance'] = array(
    '#type'=> 'item',
    '#title'=> t('Distance'),
    '#value'=> t('Status'),
    '#description'=> t('Azimuth'),
    '#prefix' => '<th>',
    '#suffix' => '</th>',
    '#weight' => $fw++,
  );
  $form['z'][-1]['h_heights'] = array(
    '#type'=> 'item',
    '#title'=> t('Heights image'),
    '#description'=> t('Click over the image to view in large format<br>
      Visit <a href="http://wisp.heywhatsthat.com">HeyWhatsThat WISP</a> for tools
      for planning wireless networks.<br><a href="http://www.heywhatsthat.com">HeyWhatsThat</a>. Copyright 2012 Michael Kosowsky. <b>All rights reserved</b>'),
    '#prefix' => '<th>',
    '#suffix' => '</th></tr>',
    '#weight' => $fw++,
  );


  $nc = 0;
  $tc = count($nodes);

  foreach ($nodes as $key => $node) {

    $dAz = round($oGC->GCAzimuth($lat1, $long1, $node["lat"], $node["lon"]));
    // Calculo orientacio
    if ($dAz < 23) $dOr =t("North"); else
    if ($dAz < 68) $dOr =t("North East"); else
    if ($dAz < 113) $dOr =t("East"); else
    if ($dAz < 158) $dOr =t("South East"); else
    if ($dAz < 203) $dOr =t("South"); else
    if ($dAz < 248) $dOr =t("South West"); else
    if ($dAz < 293) $dOr =t("West"); else
    if ($dAz < 338) $dOr =t("North West"); else
      $dOr =t("North");
//    $output .=  _wifi_state_class($rows[$key]["state"]) .t($rows[$key]["state"]) ."</td>";

    // conversio de les coordenades a UTM

    $UTMnode1 = guifi_WG842UTM($long1,$lat1,5,31,1);
    $UTMnode2 = guifi_WG842UTM($node["lon"],$node["lat"],5,31,1);

    // genero URL del Perfil

    $height_url = base_path(). drupal_get_path('module', 'guifi') .'/guifi_heights.php?x1='
      .$UTMnode1[0]."&y1=".$UTMnode1[1]."&x2=".$UTMnode2[0]."&y2=".$UTMnode2[1];
    $height_url_long = $height_url."&node1=".$node1."&node2=".$node["nick"]."&width=1100&height=700";
//    $height_url_small = $height_url."&width=200&height=100";
    $height_url_small =
      'http://www.heywhatsthat.com/bin/profile.cgi?axes=0&curvature=0&metric=1' .
      '&pt0='.$lat1.','.$long1.',ff0000,9' .
      '&pt1='.$node[lat].','.$node[lon].',00c000,9' .
      '&groundrelative=1' .
      '&src=guifi.net' .
      '&width=300&height=100';
    // heywhatsthat.com integration
//    $height_url = "http://www.heywhatsthat.com/bin/profile.cgi?src=profiler&axes=1&curvature=1&metric=1&" .
//        "pt0=".$20.96144,-9.84375,ff0000&pt1=42.293564,11.25,00c000";
    $height_url_long = base_path().'node/'.$orig.'/view/distancesmap?lat2='.$node['lat'].'&lon2='.$node['lon'];
    $zone = node_load($node['zone_id']);

    if ($filters['search'])
    if (!(stristr($zone->nick.$node['nick'],$filters['search'])))
     continue;

    if ($filters['azimuth']) {
      $l = FALSE;
      foreach (explode('-',$filters['azimuth']) as $minmax) {
        list($min,$max) = explode(',',$minmax);
        if (($dAz <= $max) and ($dAz >= $min))
          $l = TRUE;
      }
      if (!$l)
       continue;
    }

   // All filters applied, see if fits in the current chunk (skip/max)
   if ($nc >= $filters['skip'] + $filters['max']) {
     $allow_next = TRUE;
     break;
    }
    $nc++;
    if ($nc < $filters['skip'])
        continue;

    $suffix = '</td></tr>';
    if ((!$allow_prev) and (!$allow_next))
      if ($nc == $tc)
        $suffix = '</td></tr></table>';
//    $form['z'][$nc]['d_nid'] = array (
//      '#type' => 'hidden',
//      '#parents'=> array('z',$nc,'d_nid'),
//      '#value' => $node['id'],
//      '#weight' => $fw++,
//    );
    $form['z'][$nc]['d_node'] = array(
      '#type'=> 'item',
      '#parents'=> array('z',$nc,'d_node'),
      '#title'=> l($node['nick'],'node/'.$node['id']),
      '#description'=> l($zone->nick,'node/'.$node['zone_id']),
      '#prefix' => '<tr><td>',
      '#suffix' => '</td>',
      '#weight' => $fw++,
    );
    $form['z'][$nc]['d_distance'] = array(
      '#type'=> 'item',
      '#parents'=> array('z',$nc,'d_distance'),
      '#title'=> $node['distance'].' '.t('kms'),
      '#value'=> $node['status_flag']." (". date('d/m/Y',$node["timestamp_changed"]). ")",
      '#description'=> $dAz.'º - '.$dOr,
      '#prefix' => '<td>',
      '#suffix' => '</td>',
      '#weight' => $fw++,
    );
    $form['z'][$nc]['d_status'] = array(
      '#type'=> 'item',
      '#parents'=> array('z',$nc,'d_status'),
      '#markup'=> '<a href="'.$height_url_long.'" alt="'.t('Click to view in large format').'" target="_blank">' .
//          '<img src="'.$height_url_small.'"></a>',
          '<img src="'.$height_url_small.'"></a>',

//      '#prefix'=> '<td><img src="'.$height_url_small.'">',
      '#prefix'=> '<td>',
      '#suffix' => $suffix,
      '#weight' => $fw++,
    );
  } // eof while distance < max:distance
  if (!$allow_next)
    $suffix = '</td></tr></table>';
  else
    $suffix = '<td>';
  if ($allow_prev) {
    $prefix = '<td>';
    $form['z'][$nc++]['prev'] = array(
    '#type' => 'submit',
    '#parents' => array('z',$nc++,'prev'),
    '#value' => t('Previous page'),
    '#name'=> 'op',
    '#prefix'=> '<tr><td>',
    '#suffix' => $suffix,
    '#weight' => $fw++,
    );
  } else
    $prefix = '<tr><td>';
  if ($allow_next)
    $form['z'][$nc++]['next'] = array(
    '#type' => 'submit',
    '#parents' => array('z',$nc++,'next'),
    '#value' => t('Next page'),
    '#prefix'=> $prefix,
    '#suffix' => '</td></tr></table>',
    '#name'=> 'op',
    '#weight' => $fw++,
  );
  return $form;
}

function guifi_location_distances_form_submit($form, &$form_state) {
  $form_state['rebuild'] = TRUE;
}

function guifi_location_set_flag($id) {

  $scores = array(
    'Dropped' => 0,
    'Inactive' => 1,
    'Planned' => 2,
    'Reserved' => 3,
    'Building' => 4,
    'Testing' => 5,
    'Working' => 6
    );
  $score = -1;
  $query = db_query(
    "SELECT d.id, d.flag " .
    "FROM {guifi_devices} d " .
    "WHERE d.nid = :id",
    array(':id' => $id));

  while ($device = $query->fetchObject()) {
    if ($scores[$device->flag] > $score)
      $score = $scores[$device->flag];
  } // eof while devices

  if ($score == -1)
    // no devices status found, default Planned
    $score = 1;

  // set the highest score found
  $scores = array_flip($scores);

  $node = node_load($id);
  $node->status_flag = $scores[$score];
  node_save($node);
}

/* Themes (presentation) functions */
function theme_guifi_location_data($node) {
  if (empty($node->id))
  $node = node_load($node);
  
  guifi_log(GUIFILOG_TRACE,'function guifi_location_data(node)',$node);
  
  $zone = db_query('SELECT id, title FROM {guifi_zone} WHERE id = :zid', array(':zid' => $node->zone_id))->fetchObject();
  $rows[] = array(t('node'),$node->nid .' ' .$node->nick,'<b>' .$node->title .'</b>');
  $rows[] = array(t('zone'),l($zone->title,'node/'.$zone->id),$node->zone_description);
  $rows[] = array(t('position (lat/lon)'),sprintf('<a href="http://maps.guifi.net/world.phtml?Lat=%f&Lon=%f&Layers=all" target="_blank">Lat:%f<br />Lon:%f</a>',
                   $node->lat,$node->lon,$node->lat,$node->lon),$node->elevation .'&nbsp;'.t('meters above the ground'));
  $rows[] = array(t('available for mesh &#038; status'),$node->stable,array('data' => t($node->status_flag),'class' => $node->status_flag));

  if (count($node->funders)) {
    $rows[] = array(
      count($node->funders) == 1 ?
      t('Funder') : t('Funders'),
      array('data'=>implode(', ',guifi_funders_links($node->funders)),
            'colspan'=>2));
  }
  if (count($node->maintainers)) {
    $rows[] = array(
      t('Maintenance & SLAs'),
      array('data'=>implode(', ',guifi_maintainers_links($node->maintainers)),
            'colspan'=>2));
  } else {
  	$radios = db_query(
      'SELECT count(id) c FROM {guifi_radios} WHERE nid = :nid', array(':nid' => $node->id))->fetchObject();
    if ($radios->c > 1) {
  	  $pmaintainers = guifi_maintainers_parents($node->zone_id);
  	  if (!empty($pmaintainers))
        $rows[] = array(
          t('Maintenance & SLAs').' '.t('(from parents)'),
          implode(', ',guifi_maintainers_links($pmaintainers)));
    }
  }

  if ($node->graph_server > 0)
    $gs = node_load($node->graph_server);
  else
    $gs = node_load(guifi_graphs_get_server($node->id,'node'));

  $rows[] = array(t('graphs provided from'),array(
    'data' => l(guifi_service_str($node->graph_server),
              $gs->l, array('attributes' => array('title' => $gs->nick.' - '.$gs->title))),
    'colspan' => 2));

  $output = theme('table', array('header' => NULL, 'rows' => array_merge($rows)));


  return $output;
}

function theme_guifi_location_map($node) {

  drupal_set_breadcrumb(guifi_location_ariadna($node->id,'node/%d/view/map'));

  if (guifi_gmap_key()) {
    $output ='<div id="map" style="width: 100%; height: 340px; margin:5px;"></div>';
    $output .= guifi_location_hidden_map_fileds($node);
    drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_gmap_point.js');
   }

  return $output;
  }

/**
 * guifi_location_graph_overview
 * outputs an overiew graph of the node
**/
function theme_guifi_location_graphs_overview($node) {
  if (empty($node->id))
    $node = node_load($node);

  $gs = node_load(guifi_graphs_get_server($node->id,'node'));

  $radios = array();
  $query = db_query("SELECT * FROM {guifi_radios} WHERE nid = :nid", array(':nid' => $node->id));
  while ($radio = $query->fetchAssoc()) {
    $radios[] = $radio;
  }
  if (count($radios) > 1) {
      $args = array('type' => 'supernode',
        'node' => $node->id,
      );

//      $args = sprintf('type=supernode&node=%d&direction=',$node->id);
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

      $ret = array_merge($rows);
  } else {
    if (count($radios)==1)
    $ret = guifi_device_graph_overview($radios[0]);
  }

  $output = theme('table', array('header' => NULL, 'rows' => $ret));

  return $output;
}

function theme_guifi_location_devices_list($node) {
  if (empty($node->id))
    $node = node_load($node);
  $id = $node->id;
  $rows = array();

  $header = array(
    '<h2>'.t('device').'</h2>',
    t('type'),
    t('ip'),
    t('status'),
    array('data' => t('last available'),'style' => 'text-align: right;'),
    t('unsolclic'));

  // Form for adding a new device
  $form = drupal_render(drupal_get_form('guifi_device_create_form',$node));

  $query = db_query("SELECT d.id FROM {guifi_devices} d WHERE nid = :nid", array(':nid' => $id));
  while ($d = $query->fetchObject()) {
     $device = guifi_device_load($d->id);

     // Edit and delete buttons
     if (guifi_device_access('update',$device['id'])) {
        $edit_device =  l(guifi_img_icon('edit.png'),'guifi/device/'.$device['id'].'/edit',
            array(
              'html' => TRUE,
              'attributes' => array('target' => '_blank','title' => t('edit device'))));
        $delete_device = l(guifi_img_icon('drop.png'),'guifi/device/'.$device['id'].'/delete',
            array(
              'html' => TRUE,
              'attributes' => array('target' => '_blank','title' => t('delete device'))));
     } else {
       $edit_device = '';
       $delete_device = '';
     }

     // Traceroute button
     if (user_access('create guifi nodes')) {
       $traceroute = l(guifi_img_icon('discover-routes.png'),'guifi/menu/ip/traceroute/'.$device['id'],
            array(
              'html' => TRUE,
              'attributes' => array(
                'target' => '_blank',
                'title' => t('trace routes, discover services from this device'))));
     } else $traceroute = '';

     // Firmware text which links to unsolclic feature
     if ($device->variable['firmware'] != "n/d") {
       $unsolclic = l($device[variable]['firmware'],
         'guifi/device/'.$device['id'].'/view/unsolclic',
         array('attributes' => array('title' => t("Get radio configuration with singleclick")))
       );
     }

     // Get IP assigned to the device
     $ip = guifi_main_ip($device[id]);

     // Availability image
     $status_url = guifi_cnml_availability(
       array('device' => $device['id'],'format' => 'short'));

     // Device main attributes
     if (!empty($device['manufacturer']))
       $mDescr = $device['manufacturer'].'-'.$device['model'];
     else
       $mDescr = '';
     $uCreated = db_query('SELECT u.name FROM {users} u WHERE u.uid = :uid', array(':uid' => $device[user_created]))->fetchObject();
     $deviceAttr = $device[id].' '.$mDescr.'
         '
       .t('created by').': '.$uCreated->name
       .' '. t('at') .' '. format_date($device[timestamp_created], 'small');
     if (!empty($device[timestamp_changed])) {
       $uChanged = db_query('SELECT u.name FROM {users} u WHERE u.uid = :uid', array(':uid' =>  $device[user_changed]))->fetchObject();
       $deviceAttr .= '
           '.t('updated by').': '.$uChanged->name
       .' '. t('at') .' '. format_date($device[timestamp_changed], 'small');
     }

     // Groups all this data in an array for the theme() function
     $rows[] = array(
                 l($device[nick],'guifi/device/'.$device[id],
                   array('attributes'=>array('title'=>$deviceAttr))),
                 $device[type],
                 array('data' => $ip[ipv4].'/'.$ip[maskbits], 'align' => 'left'),
                 (empty($ip[ipv4])) ? '&nbsp;' :
                   array('data' => t($device[flag]),'class' => $device['flag']),
                 (empty($ip[ipv4])) ? '&nbsp;' :
                   array('data' => $status_url,'class' => $device['flag']),
                 $unsolclic,
                 $edit_device,
                 $delete_device,
                 (empty($ip[ipv4])) ? '&nbsp;' :
                   $traceroute
                    );
  }

  // Creates the table with devices if any, otherwise just outputs the node has not devices
  if (count($rows))
    $output = '<h4>'.t('devices').'</h4>'.
      theme('table', array('header' => $header, 'rows' => $rows,
        array('class' => 'device-data'))).
      $form;
  else
    $output = '<p>'.t('This node does not have any device').'</p>'.$form;

  return $output;
}

function theme_guifi_location_links($node) {
  if (empty($node->id))
    $node = node_load($node);

  $output =
    theme_guifi_location_links_by_type($node->id,'wds').
    theme_guifi_location_links_by_type($node->id,'cable').
    theme_guifi_location_links_by_type($node->id,'ap/client');

  return $output;
}

function theme_guifi_location_links_by_type($id = 0, $ltype = '%') {
  $oGC = new GeoCalc();

  $total = 0;
  if ($ltype == '%')
    $titlebox = array(t('links'));
  else
    $titlebox = array(t('links').' ('.$ltype.')');

  $header = array(t('linked nodes (device)'), t('ip'), t('status'), t('kms.'),t('az.'));

  $listed = array('0');
  $queryloc1 = db_query(
    "SELECT c.id, l.id nid, l.nick, c.device_id, d.nick device_nick, a.ipv4 ip," .
    "  c.flag, l.lat, l.lon, r.ssid " .
    "FROM {guifi_links} c " .
    "  LEFT JOIN {guifi_devices} d ON c.device_id=d.id " .
    "  LEFT JOIN {guifi_interfaces} i ON c.interface_id = i.id " .
    "  LEFT JOIN {guifi_location} l ON d.nid = l.id " .
    "  LEFT JOIN {guifi_ipv4} a ON i.id=a.interface_id " .
    "    AND a.id=c.ipv4_id " .
    "  LEFT JOIN {guifi_radios} r ON d.id = r.id " .
    "    AND i.radiodev_counter = r.radiodev_counter " .
    "WHERE d.nid = :nid AND link_type like :type " .
    "ORDER BY c.device_id, i.id",
    array(':nid' => $id, ':type' => $ltype));

  $devant = ' ';
  while ($loc1 = $queryloc1->fetchObject()) {
    $queryloc2 = db_query(
      "SELECT c.id, l.id nid, l.nick, r.ssid, c.device_id, d.nick device_nick, " .
      "  a.ipv4 ip, l.lat, l.lon " .
      "FROM {guifi_links} c " .
      "  LEFT JOIN {guifi_devices} d ON c.device_id=d.id " .
      "  LEFT JOIN {guifi_interfaces} i ON c.interface_id = i.id " .
      "  LEFT JOIN {guifi_location} l ON d.nid = l.id " .
      "  LEFT JOIN {guifi_ipv4} a ON i.id=a.interface_id " .
      "    AND a.id = c.ipv4_id " .
      "  LEFT JOIN {guifi_radios} r ON d.id=r.id " .
      "    AND i.radiodev_counter=r.radiodev_counter " .
      "WHERE c.id = :id " .
      "  AND c.device_id <> :did " .
      "  AND c.id NOT IN (:listed)",
      array(':id' => $loc1->id, ':did' => $loc1->device_id, ':listed' => $listed));
    $listed[] = $loc1->device_id;
    $devact = $loc1->device_nick;
    if ($loc1->ssid)
      $devact.= ' - '.$loc1->ssid;
    while ($loc2 = $queryloc2->fetchObject()) {
      $gDist = round($oGC->EllipsoidDistance($loc1->lat, $loc1->lon, $loc2->lat, $loc2->lon),3);
      if ($gDist) {
        $total = $total + $gDist;
        $dAz = round($oGC->GCAzimuth($loc1->lat, $loc1->lon, $loc2->lat,$loc2->lon));
        // Calculo orientacio
        if ($dAz < 23) $dOr =t("N"); else
          if ($dAz < 68) $dOr =t("NE"); else
            if ($dAz < 113) $dOr =t("E"); else
              if ($dAz < 158) $dOr =t("SE"); else
                if ($dAz < 203) $dOr =t("S"); else
                  if ($dAz < 248) $dOr =t("SW"); else
                    if ($dAz < 293) $dOr =t("W"); else
                      if ($dAz < 338) $dOr =t("NW"); else
                        $dOr =t("N");
      }
      else
        $gDist = 'n/a';
      if ($loc1->nid <> $loc2->nid) {
        $cr = db_query("SELECT count(*) count FROM {guifi_radios} r WHERE id = :loc2", array(':loc2' => $loc2->device_id))->fetchObject;
        if ($cr->count > 1)
          $dname = $loc2->device_nick.'/'.$loc2->ssid;
        else
          $dname = $loc2->device_nick;
        $linkname = $loc1->id.'-'.'<a href='.base_path().'node/'.$loc2->nid.'>'.$loc2->nick.'</a> (<a href='.base_path().'guifi/device/'.$loc2->device_id.'>'.$dname.'</a>)';
      }
      else
        $linkname = $loc1->id.'-'.'<a href='.base_path().'guifi/device/'.$loc1->device_id.'>'.$loc1->device_nick.'</a>/<a href='.base_path().'guifi/device/'.$loc2->device_id.'>'.$loc2->device_nick.'</a>';

      $status_url = guifi_cnml_availability(
         array('device' => $loc2->device_id,'format' => 'short'));

      if ($devant != $devact) {
        $devant = $devact;
        $rows[] = array(array('data'=> '<b><a href='.base_path().'guifi/device/'.$loc1->device_id.'>'.$devact.'</a></b>','colspan' => 5));
      }
      $rows[] = array($linkname,
        $loc1->ip.'/'.$loc2->ip,
        array('data' => t($loc1->flag).$status_url,
          'class' => $loc1->flag),
          array('data' => $gDist,'class' => 'number'),
          $dAz.'-'.$dOr);
    } // whhile loc2
  } // while loc1

  if (count($rows)) {
    $output = theme('table', array('header' => $header, 'rows' => $rows,array('class' => 'device-data')));
    if ($total)
      $output .= t('Total:').'&nbsp;'.$total.'&nbsp;'.t('kms.');
  } else
    if ($ltype == '%')
      $output = '<p align="right">'.t('No links defined').'</p>';
    else
      return;

  return theme('table', array('header' => $titlebox, 'rows' => array(array($output))));

}

function guifi_elevation_validate($element, &$form_state) {
  if ($element['#value'] != '0') {
    if (empty($element['#value']))
      form_error($element,t('Antenna elevation in meters must be specified.'));
  }
  if (!is_numeric($element['#value']))
    form_error($element,t('Antenna elevation in meters must be numeric'));
  if ($element['#value'] < 0)
    form_error($element,t('Antenna elevation in meters must be a positive number'));
}

?>
