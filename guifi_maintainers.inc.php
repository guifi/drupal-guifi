<?php
/**
 * @file guifi_maintainers.inc.php
 * Manage guifi_maintainers fieldssets, validations, etc...
 */

 function guifi_maintainers_load($id, $subject_type) {
   $qsql = sprintf('SELECT * FROM {guifi_maintainers} ' .
   		'WHERE subject_id = %d ' .
   		' AND subject_type = "%s" ' .
   		'ORDER BY id',
   		$id,$subject_type);
   $result = db_query($qsql);
//   $result = db_query('SELECT * FROM {guifi_maintainers} ' .
//   		'WHERE subject_id = :id ' .
//   		' AND subject_type = ":subject_type" ' .
//   		'ORDER BY weight, id',
//   		array(':id'=>$id,':subject_type'=>$subject_type));
   guifi_log(GUIFILOG_TRACE,
     'function guifi_zone_load(sql)',
     $qsql);

   while ($m = db_fetch_array($result)) {
   	 $m['maintainer'] = $m['supplier_id'].'-'.budgets_supplier_get_suppliername($m['supplier_id']);
     $maintainers[] = $m;
   }
   guifi_log(GUIFILOG_TRACE,
     'function guifi_zone_load(maintainers)',
     $maintainers);

   return $maintainers;
 }

 function guifi_maintainers_save($subject_id, $subject_type, $maintainers) {

  foreach ($maintainers as $k => $m) {

  	$m['subject_type']=$subject_type;
  	$m['subject_id']=$subject_id;

    if ((empty($m['maintainer'])) and !(empty($m['id'])))
      $m['deleted']=true;

    if (empty($m['id']))
      $m['new']=true;

    if ($m['new']==true and (empty($m['maintainer'])))
      continue;

    $mid = explode('-',$m['maintainer']);
    $m['supplier_id'] = $mid[0];

    _guifi_db_sql('guifi_maintainers',array('id'=>$m['id']),$m);
  }
  return;
 }

function guifi_maintainers_validate($node) {

  /*
   * Validate maintainer(s)
   */
   foreach ($node->maintainers as $k => $m) {
   	 guifi_log(GUIFILOG_TRACE,
     'function guifi_zone_validate(m)',
     $m);
   	 if (!empty($m['maintainer'])) {
   	   $mtemp = explode('-',$m['maintainer']);
   	   if (is_numeric($mtemp[0]))
   	      $maintainer = node_load($mtemp[0]);
   	   else
         form_set_error('maintainers]['.$k.'][maintainer',t('%supplier has to be registered as supplier/provider',array('%supplier'=>$m['maintainer'])));
   	 } else
   	   continue;

   	guifi_log(GUIFILOG_TRACE,
    'function guifi_zone_validate(maintainer)',
    $maintainer);

     if ($m['commitment']=='Volunteer') {
       if ($m['sla'] != 'none')
         form_set_error('maintainers]['.$k.'][commitment',t('%supplier has to act as a professional for commiting a SLA',array('%supplier'=>$m['maintainer'])));
       if ($maintainer->role == 'professional')
         form_set_error('maintainers]['.$k.'][commitment',t('%supplier act as professional, so has to commit a SLA objective.<br>' .
         		'Use values ​​relaxed enough to best ensure objectives',array('%supplier'=>$m['maintainer'])));
     } else {
       // SLA set, validate that is professional, and is enabled for
       if ($maintainer->role != 'professional') {
         form_set_error('maintainers]['.$k.'][sla',t('%supplier is registered as a volunteer, needs to become a professional for commiting a SLA',array('%supplier'=>$m['maintainer'])));
       }
       // Check if it has signed the maintenance agreements
       if ($m['commitment'] == 'FO') {
         if (empty($maintainer->certs['guifi_certs']['FO-Mgmt']) and
             empty($maintainer->certs['guifi_certs']['FO-Dist']))
           form_set_error('maintainers]['.$k.'][commitment',t('%supplier needs to sign the agreement for maintaining fibre optics network infrastructure',array('%supplier'=>$m['maintainer'])));
//         form_set_error('maintainers]['.$k.'][commitment',t('%supplier needs to sign the agreement for maintaining fibre optics ( %fo-mgmt - %fo-dist )',
//           array('%supplier'=>$m['maintainer'],
//             '%fo-mgmt'=> $maintainer->certs['guifi_certs']['FO-Mgmt'],
//             '%fo-dist'=> serialize($maintainer->certs),)));
       }
       if ($m['commitment'] == 'Wireless') {
         if (empty	($maintainer->certs['guifi_certs']['W-Mgmt']) and
             empty($maintainer->certs['guifi_certs']['W-Dist']))
           form_set_error('maintainers]['.$k.'][commitment',t('%supplier needs to sign the agreement for maintaining wireless network infrastructure',array('%supplier'=>$m['maintainer'])));
       }

     }
     if ($m['sla'] == 'none') {
       if ($m['sla_resp'])
         form_set_error('maintainers]['.$k.'][sla_resp',t('%m: SLA response time  (%d) not applicable',array('%m'=>$m['maintainer'],'%d'=>$m['sla_resp'])));
       if ($m['sla_fix'])
         form_set_error('maintainers]['.$k.'][sla_fix',t('%m: SLA solving objective  (%d) not applicable',array('%m'=>$m['maintainer'],'%d'=>$m['sla_fix'])));
     } else {
       if (empty($m['sla_resp']))
         form_set_error('maintainers]['.$k.'][sla_resp',t('%m: Response time should be set for SLA',array('%m'=>$m['maintainer'])));
       if (empty($m['sla_fix']))
         form_set_error('maintainers]['.$k.'][sla_fix',t('%m: Solving objective should be set for SLA',array('%m'=>$m['maintainer'])));

     }
   }
}

function guifi_maintainers_links($maintainers) {
  foreach ($maintainers as $k=>$v) {
    $mid=explode('-',$v['maintainer']);
    $mstr[] = l($mid[1].' ('.$v['commitment'].')',
      'node/'.$mid[0],
      array('attributes'=>
        array('title'=> ($v['sla'] == 'none') ?
          $v['commitment']
          : $v['commitment'].' - '.$v['sla'].': '.
          t('Resp. %resp, Fix: %fix',
            array('%resp'=>$v['sla_resp'],
              '%fix'=>$v['sla_fix']
                  ))))
      );
  }
  return $mstr;
}

function guifi_maintainers_form($node,&$form_weight) {

  $form['maintainers'] = array(
    '#type'        => 'fieldset',
    '#title'       => t('Maintainer(s)'),
    '#description' => t('If they are, maintainer(s) for this item.<br>If there aren\'t, either take from parents, or use notification contacts.<br>'.
      'Use "Preview" button if you need more rows to fill.'),
    '#collapsible' => TRUE,
    '#collapsed'   => ($node->maintainers[0]!='') ? TRUE : FALSE,
    '#attributes'  => array('class'=>'maintainers'),
    '#weight'      => $form_weight++,
    '#tree'        => TRUE,
  );
  $maintainer_id=0;
  $nmaintainers = count($node->maintainers);
//  guifi_log(GUIFILOG_BASIC, 'function guifi_zone_form(mantainers)', $node->maintainers[$maintainer_id]);
  guifi_log(GUIFILOG_TRACE, 'function guifi_maintainers_form(mantainers)', $nmaintainers);
  do {
    $form['maintainers'][$maintainer_id]['maintainer'] = array (
      '#title'=>t('maintainer'),
      '#type' => 'textfield',
      '#description'=>($node->maintainers[$maintainer_id]['maintainer']!='') ?
         t('Leave blank for delete this maintainer'):t('Fill for register a new maintainer'),
      '#size' => 40,
      '#default_value'=> ($node->maintainers[$maintainer_id]['maintainer']!='') ?
         $node->maintainers[$maintainer_id]['maintainer'] : NULL,
      '#maxsize'=> 256,
      '#autocomplete_path' => 'budgets/js/select-supplier',
//      '#attributes'  => array('class'=>'maintainers'),
      '#prefix'     => '<div class="maintainer-item">',
      '#weight'      => $form_weight++,
    );
  	$form['maintainers'][$maintainer_id]['id'] = array (
      '#type' => 'hidden',
      '#value' =>$node->maintainers[$maintainer_id]['id'],
      '#weight'      => $form_weight++,
  	);
    $form['maintainers'][$maintainer_id]['commitment'] = array (
      '#title'=>t('commitment type'),
      '#type' => 'select',
      '#required' => FALSE,
      '#default_value' => $node->maintainers[$maintainer_id]['commitment'],
      '#options' => guifi_types('maintainer'),
      '#weight' => $form_weight++,
    );
    $form['maintainers'][$maintainer_id]['sla'] = array (
      '#title'=>t('SLA'),
      '#description'=>t('Service Level Objective'),
      '#type' => 'select',
      '#required' => FALSE,
      '#default_value' => $node->maintainers[$maintainer_id]['sla'],
      '#options' => guifi_types('sla'),
      '#weight' => $form_weight++,
    );
    $form['maintainers'][$maintainer_id]['sla_resp'] = array (
      '#title'=>t('resp.'),
      '#description'=>t('Hours'),
      '#type' => 'select',
      '#required' => FALSE,
      '#default_value' => $node->maintainers[$maintainer_id]['sla_resp'],
      '#options' => array(''=>t('n/a'),4=>4,6=>6,8=>8,12=>12,24=>24,48=>48,72=>72),
      '#weight' => $form_weight++,
    );
    $form['maintainers'][$maintainer_id]['sla_fix'] = array (
      '#title'=>t('fix'),
      '#description'=>t('Hours'),
      '#type' => 'select',
      '#required' => FALSE,
      '#default_value' => $node->maintainers[$maintainer_id]['sla_fix'],
      '#options' => array(''=>t('n/a'),4=>4,6=>6,8=>8,12=>12,24=>24,48=>48,72=>72),
      '#suffix'     => '</div>',
      '#weight' => $form_weight++,
    );
    $maintainer_id++;
  } while ($maintainer_id < ($nmaintainers + 1));

  return $form['maintainers'];
}

?>