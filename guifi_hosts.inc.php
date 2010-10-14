<?php

/* guifi_host_form(): Main host form (Common parameters)*/
function guifi_host_form($edit,$form_weight) {

  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_host_form()',$edit);

  $collapse = TRUE;
  
  $msg = (count($edit['hosts'])) ?
    format_plural(count($edit['hosts']),'1 host','@count hosts') :
    t('No hosts');

  if (count($edit['hosts']))
    foreach ($edit['hosts'] as $value)
    if ($value['unfold'])
      $collapse =FALSE;


  $form['r'] = array(
    '#type' => 'fieldset',
    '#title' => $msg ,
    '#collapsible' => TRUE,
    '#collapsed' => $collapse,
    '#tree' => FALSE,
    '#prefix' => '<img src="/'.
      drupal_get_path('module', 'guifi').
     '/icons/home.png"> '.t("Host Name's for this domain"),
    '#weight' => $form_weight++,
  );

  $form['r']['hosts'] = array('#tree' => TRUE);

  $rc = 0;
  
  if (!empty($edit['hosts']))
    foreach ($edit['hosts'] as $key => $host) {
      $form['r']['hosts'][$key] = guifi_host_host_form($host,$key,$form_weight);
      $form['r']['hosts'][$key]['aliases'] = array(
        '#type' => 'fieldset',
        '#title' => t('Aliases'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#description' => t('Host aliases. Save or press "Preview" to get more entries'),
        '#weight' => $form_weight++,
      );

      // after rebuild forms, check serialized values.
      unset ($alias);
      if (!empty($host['aliases'])) {
        $checkalias = substr((string)$host['aliases'], 0, 2);
          if ($checkalias == 'a:') {
            $alias = unserialize($host['aliases']);
          } else {
            $alias = $host['aliases'];
          }
       }
      $cname_description = t('Enter the host alias. ex: www<br> Must put a DOT "<strong>.</strong>" at the end of the domain name if the alias points to an external domain. ex: "outsidehost.dyndns.org<strong>.</strong>"');

      if (count($alias)) {
        foreach ($alias as $delta => $value)
          if (!empty($value))
            $form['r']['hosts'][$key]['aliases'][] = array(
              '#type' => 'textfield',
              '#title' => t('CNAME'),
              '#size' => 48,
              '#maxlength' => 48,
              '#description' => $cname_description,
              '#default_value' => $value,
            );
      }
      for ($i = 0; $i < 1; $i++)
        $form['r']['hosts'][$key]['aliases'][] = array(
           '#type' => 'textfield',
           '#size' => 48,
           '#maxlength' => 48,
           '#description' => $cname_description,
         );

     if (($host['host'] == 'ns1' ) AND ($host['counter'] == '0')) {
        $access = FALSE;
        $options = array('MX' => 'MX');
        $optionsdesc = t('Select these options only if this feature has made by this host.
                                   <br><b>NS</b>: Selected as default on host \'ns1\'. This is your primary NameServer forced, cannot uncheck it.
                                   <br><b>MX</b>: Choose this option if this host has to perform tasks of <b>MailHost</b> server. Select, then Save and edit again to set priority on a new field.');
      } else {
        $access = TRUE;
        $options = array('NS' => 'NS','MX' => 'MX');
        $optionsdesc = t('Select these options only if this feature has made by this host.
                                   <br><b>NS</b>: Choose this option if this host has to perform tasks of <b>NameServer</b>.
                                   <br><b>MX</b>: Choose this option if this host has to perform tasks of <b>MailHost</b> server. Select, then Save and edit again to set priority on a new field.');
      }

      unset ($nsoptions);

      $form['r']['hosts'][$key]['opt'] = array(
        '#type' => 'fieldset',
        '#title' => t('Advanced settings'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#description' => $optionsdesc,
        '#weight' => $form_weight++,
      );
      if (!empty($host['options'])) {
        $checkoptions = substr((string)$host['options'], 0, 2);
          if ($checkoptions == 'a:')
            $nsoptions = unserialize((string)$host['options']);
            $nsmx = unserialize((string)$host['options']);
       } else {
            $nsoptions = array($host['opt']['options']['NS'],$host['opt']['options']['MX']);
             if  ($host['opt']['options']['MX'] === 'MX') {
               $nsmx['mxprior']= $host['opt']['mxprior'];
            }
         }

      $form['r']['hosts'][$key]['opt']['options'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Options'),
        '#default_value' => $nsoptions,
        '#options' => $options,
      );

      if  ($host['opt']['options']['MX'] === 'MX') {
        $mxprior = $nsmx['mxprior'] ? $nsmx['mxprior'] : 10;
}
      if ( $nsoptions['MX'] === 'MX') {
        $mxprior = $nsmx['mxprior'] ? $nsmx['mxprior'] : 10;

}
      if  (($host['opt']['options']['MX'] === 'MX') OR ( $nsoptions['MX'] === 'MX')) {
        $form['r']['hosts'][$key]['opt']['mxprior'] = array(
          '#type' => 'textfield',
          '#title' => t('MX Priority'),
          '#size' => 3,
          '#maxlength' => 3,
          '#default_value' => $mxprior,
          '#description' => t('One can have several mail servers, with priorities of 10, 20 and so on. A mail server attempting to deliver to example.org would first try the highest priority MX (the record with the lowest priority number), then the second highest, etc, until the mail can be properly delivered. Must be a multiple of 10 and can not be repeated.'),
        );
      }
      $bw = $form_weight - 1000;
      if (!isset($host['deleted'])) {
        // Only allow delete and move functions if the host has been saved
        if ($host['new']==FALSE)  {
          $form['r']['hosts'][$key]['delete'] = array(
            '#type' => 'image_button',
            '#access' => $access,
            '#src' => drupal_get_path('module', 'guifi').'/icons/drop.png',
            '#parents' => array('hosts',$key,'delete'),
            '#attributes' => array('title' => t('Delete host')),
            '#submit' => array('guifi_host_delete_submit'),
            '#weight' => $bw++
          );
        }
        $rc++;
      }
    } // foreach host

    // Add host?
  $newhostname = 'HostName-'.(count($edit['hosts'])+1);
  $form['r']['hosts']['newhost_name'] = array(
    '#type' => 'hidden',
    '#parents' => array('newhost_name'),
    '#default_value' =>  $newhostname,
  );
  $form['r']['hosts']['AddHost'] = array(
    '#type' => 'image_button',
    '#src' => drupal_get_path('module', 'guifi').'/icons/add.png',
    '#attributes' => array('title' => t('Add New Host')),
    '#parents' => array('AddHost'),
    '#executes_submit_callback' => TRUE,
    '#submit' => array(guifi_host_add_host_submit),
    '#weight' => $form_weight++,
  );
  
  return $form;
}

function guifi_host_host_form($host, $key, &$form_weight = -200) {
    guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_host_host_form(key=%d)',$key),$host);

    $f['storage'] = guifi_form_hidden_var(
      $host,
      array('counter'),
      array('hosts',$key)
    );

    $f = array(
      '#type' => 'fieldset',
      '#title' => t('HostName:').' '.$host['host'].' - '.
                  t('IPv4:').' '.$host['ipv4'],
      '#collapsible' => TRUE,
      '#collapsed' => !(isset($host['unfold'])),
      '#tree'=> TRUE,
      '#weight' => $form_weight++,
    );

     if (($host['host'] == 'ns1' ) AND ($host['counter'] == '0')) {
        $access = FALSE;
     } else {
         $access = TRUE;
     }

    $f[] = array(
      '#type' => 'textfield',
      '#access' => $access,
      '#title' => t('Host Name'),
      '#parents' => array('hosts',$key,'host'),
      '#default_value' => $host['host'],
      '#weight' => $form_weight++,
    );
              
    $f[] = array(
      '#type' => 'textfield',
      '#title' => t("IP Address"),
      '#parents' => array('hosts',$key,'ipv4'),
      '#default_value' => $host['ipv4'],
      '#element_validate' => array('guifi_ipv4_validate'),
      '#description' => t('Leave it blank if you want to use an alias to an external domain.<br> ex: hostname is an alias of hostname.dyndns.org.'),
      '#weight' => $form_weight++,
    );
      
    if ($host['deleted']) {
      $f['deletedMsg'] = array(
        '#type' => 'item',
        '#value' => guifi_device_item_delete_msg(
            "This host has been deleted, " .
            "deletion will cascade to all properties, including interfaces, " .
            "links and ip addresses."),
        '#weight' => $form_weight++
      );
      $f['deleted'] = array(
        '#type' => 'hidden',
        '#value' => TRUE
      );
    }
    if ($host['new']) {
      $f['new'] = array(
        '#type' => 'hidden',
        '#parents' => array('hosts',$key,'new'),
        '#value' => TRUE
      );
    }
  return $f;
}


function guifi_ipv4_validate($element,&$form_state) {
  guifi_log(GUIFILOG_TRACE,"function _guifi_host_ipv4_validate()");

    if ($form_state['clicked_button']['#value'] == t('Reset'))
    return;
    
    if ($element['#value']) {
    $value = $element['#value'];
      if(filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return $element;
        } else {
            form_error($element, t('Invalid %ipv4 address',array('%ipv4' => $value)));
        }
    }
}

/* Add  a host to the device */
function guifi_host_add_host_submit(&$form, &$form_state) {
  guifi_log(GUIFILOG_TRACE, "function guifi_host_add_host_submit()",$form_state['values']);

  if ($form_state['values']['newhost_name'] == NULL)
    return TRUE;

  $edit = $form_state['values'];

  // next id
  $rc = 0; // Host hostdev_counter next pointer
  $tc = 0; // Total active hosts

  // fills $rc & $tc proper values
  if (isset($edit['hosts'])) foreach ($edit['hosts'] as $k => $r)
    if ($k+1 > $rc)  {
      $rc = $k+1;
      if (!$edit['hosts'][$k][delete])
        $tc++;
    }
    $node=node_load(array('nid' => $edit['sid']));
    $host=array();
    $host['new']=TRUE;
    $host['host']=$edit['newhost_name'];
    $host['counter'] = $rc;
    $host['unfold'] = TRUE;
    $form_state['rebuild'] = TRUE;
    $form_state['values']['hosts'][] = $host;

    drupal_set_message(t(' %host added',
       array('%host' => $host['host'])));
    return;
}

function guifi_host_delete_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,"function guifi_host_delete_submit()",$form_state['clicked_button]']);
  $host_id = $form_state['clicked_button']['#parents'][1];
  $form_state['values']['hosts'][$host_id]['deleted'] = TRUE;
  $form_state['values']['hosts'][$host_id]['unfold'] = TRUE;
  drupal_set_message(t('Hostname <strong>%hostname</strong> has been deleted.',
    array('%hostname' => $form_state['values']['hosts'][$host_id]['host'])));
  $form_state['rebuild'] = TRUE;
  return;
}

function guifi_hosts_print_data($id) {

  $querydom = db_query("
    SELECT *
    FROM {guifi_dns_domains}
    WHERE id = %d", 
    $id
  );
  $domain = db_fetch_object($querydom);
  $queryhosts = db_query("
     SELECT *
     FROM {guifi_dns_hosts}
     WHERE id = '%d'", 
     $domain->id
   );

  while ($host = db_fetch_array($queryhosts)) {
    $aliases = unserialize($host['aliases']);
    $options = unserialize($host['options']);
    if ($aliases) {
      $alias = implode(' | ', $aliases);
    } else {
      $alias = NULL;
    }
    if ($options) {
      if ($options['NS'] === 'NS')
         $nameserver = 'yes';
      else
         $nameserver = 'no';
      if ($options['MX'] === 'MX') {
         $mailserver = 'yes';
         $priority = $options['mxprior'];
      } else {
         $mailserver = 'no';
         $priority = '';
      }
    } else {
         $nameserver = 'no';
         $mailserver = 'no';
    }
      $rows[] = array(
        strtolower($host['host']).'.'.$domain->name,
        $alias,
        $host['ipv4'],
        $nameserver,
        $mailserver,
        $priority
        );
      }
    if ($rows) 
      return array_merge($rows);
}

?>