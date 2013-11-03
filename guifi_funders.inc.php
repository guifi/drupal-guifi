<?php
/**
 * @file guifi_funders.inc.php
 * Manage guifi_funders fieldssets, validations, etc...
 */

 function guifi_funders_load($id, $subject_type) {
   $qsql = sprintf('SELECT * FROM {guifi_funders} ' .
   		'WHERE subject_id = %d ' .
   		' AND subject_type = "%s" ' .
   		'ORDER BY id',
   		$id,$subject_type);
   $result = db_query($qsql);
   guifi_log(GUIFILOG_TRACE,
     'function guifi_funders_load(sql)',
     $qsql);

   while ($m = db_fetch_array($result)) {
   	 if (!(empty($m['supplier_id'])))
   	   $m['supplier'] = $m['supplier_id'].'-'.budgets_supplier_get_suppliername($m['supplier_id']);
   	 if (!(empty($m['user_id']))) {
   	   $u = user_load($m['user_id']);
       $m['user'] = $m['user_id'].'-'.$u->name.' ('.$u->mail.') ';
   	 }
     $funders[] = $m;
   }
   guifi_log(GUIFILOG_TRACE,
     'function guifi_funders_load(funders)',
     $funders);

   return $funders;
 }

 function guifi_funders_save($subject_id, $subject_type, $funders) {
   guifi_log(GUIFILOG_TRACE,
     'function guifi_funders_save(funders)',
     $funders);

  foreach ($funders as $k => $f) {
  	if (!guifi_funders_access('update',$f))
  	  continue;

  	$f['subject_type']=$subject_type;
  	$f['subject_id']=$subject_id;

    if ((empty($f['comment'])) and
        (empty($f['user'])) and
        (empty($f['supplier'])) and
        !(empty($f['id'])))
      $f['deleted']=true;

    if (empty($f['id']))
      $f['new']=true;

    if ($f['new']==true and ((empty($f['supplier']))
      and (empty($f['user']))
      and (empty($f['comment']))))
        continue;

    if (!(empty($f['supplier']))) {
      $mid = explode('-',$f['supplier']);
      $f['supplier_id'] = $mid[0];
    }

    if (!(empty($f['user']))) {
      $mid = explode('-',$f['user']);
      $f['user_id'] = $mid[0];
    }

    guifi_log(GUIFILOG_TRACE,
     'function guifi_funders_save(sql)',
     $f);
    _guifi_db_sql('guifi_funders',array('id'=>$f['id']),$f);
  }
  return;
 }

function guifi_funders_validate($node) {
  /*
   * Validate funder(s)
   */
   foreach ($node->funders as $k => $f) {
   	 guifi_log(GUIFILOG_TRACE,
     'function guifi_funders_validate(m)',
     $f);
   	 if (!empty($f['supplier'])) {
   	   $mtemp = explode('-',$f['supplier']);
   	   if (is_numeric($mtemp[0]))
   	      $supplier = node_load($mtemp[0]);
   	   else
         form_set_error('funders]['.$k.'][supplier',t('%supplier has to be registered as supplier/provider',array('%supplier'=>$f['supplier'])));
   	 }
   	 if (!empty($f['user'])) {
   	   $mtemp = explode('-',$f['user']);
   	   if (is_numeric($mtemp[0]))
   	      $user = user_load($mtemp[0]);
   	   else
         form_set_error('funders]['.$k.'][user',t('%user has to be a valid user',array('%user'=>$f['user'])));
   	 }
   }

   	guifi_log(GUIFILOG_TRACE,
    'function guifi_funders_validate(funder)',
    $funder);
}

function guifi_funders_links($funders) {
  foreach ($funders as $k=>$v) {
  	if (!empty($v['supplier'])) {
      $mid=explode('-',$v['supplier']);
      $mstr[] = l($mid[1],
        'node/'.$mid[0],
        array('attributes'=>
          array('title'=> $v['comment']))
        );
  	} else
  	if (!empty($v['user'])) {
      $mid=explode('-',$v['user']);
      $u=user_load($mid[0]);
      $mstr[] = l($u->name,
        'user/'.$mid[0],
        array('attributes'=>
          array('title'=> $v['comment']))
        );
  	} else
    	$mstr[] = $v['comment'];

  }
  return $mstr;
}

function guifi_funders_form($node,&$form_weight) {

  $form['funders'] = array(
    '#type'        => 'fieldset',
    '#title'       => t('Funder(s)'),
    '#description' => t('Funders for this item.<br>'.
      'Use "Preview" button if you need more rows to fill.<br>' .
      'Leave all fields in blank for delete a row.'),
    '#collapsible' => TRUE,
    '#collapsed'   => ($node->funders[0]!='') ? TRUE : FALSE,
    '#attributes'  => array('class'=>'funders'),
    '#weight'      => $form_weight++,
    '#tree'        => TRUE,
  );

  $funder_id=0;
  $nfunders = count($node->funders);
  guifi_log(GUIFILOG_TRACE, 'function guifi_funders_form(funders)', $nfunders);
  do {
  	$disabled = !guifi_funders_access('update',$node->funders[$funder_id]);


    $form['funders'][$funder_id]['user'] = array (
      '#title'=>t('User'),
      '#type' => 'textfield',
      '#size' => 50,
      '#default_value'=> ($node->funders[$funder_id]['user']!='') ?
         $node->funders[$funder_id]['user'] : NULL,
      '#maxsize'=> 256,
      '#disabled'=> $disabled,
      '#autocomplete_path' => 'guifi/js/select-user',
      '#prefix'     => '<div class="funder-item">',
      '#weight'      => $form_weight++,
    );
    $form['funders'][$funder_id]['supplier'] = array (
      '#title'=>t('Supplier'),
      '#type' => 'textfield',
      '#disabled'=> $disabled,
      '#size' => 30,
      '#default_value'=> ($node->funders[$funder_id]['supplier']!='') ?
         $node->funders[$funder_id]['supplier'] : NULL,
      '#maxsize'=> 256,
      '#autocomplete_path' => 'budgets/js/select-supplier',
      '#weight'      => $form_weight++,
    );
  	$form['funders'][$funder_id]['id'] = array (
      '#type' => 'hidden',
      '#value' =>$node->funders[$funder_id]['id'],
      '#weight'      => $form_weight++,
  	);
  	$form['funders'][$funder_id]['user_created'] = array (
      '#type' => 'hidden',
      '#value' =>$node->funders[$funder_id]['user_created'],
      '#weight'      => $form_weight++,
  	);
    $form['funders'][$funder_id]['comment'] = array (
      '#title'=>t('Comment'),
      '#disabled'=> $disabled,
      '#description'=> t('Funder description or not registered funder name'),
      '#type' => 'textfield',
      '#size'=> 50,
      '#maxsize'=> 256,
      '#required' => FALSE,
      '#default_value' => $node->funders[$funder_id]['comment'],
      '#weight' => $form_weight++,
    );
    if ($node->funders[$funder_id])
    $form['funders'][$funder_id]['created'] = array (
      '#title'=>t('Created'),
      '#disabled'=> yes,
      '#type' => 'item',
      '#value' => format_date($node->funders[$funder_id]['timestamp_created']),
      '#weight' => $form_weight++,
    );
    $funder_id++;
  } while ($funder_id < ($nfunders + 1));

  return $form['funders'];
}

function guifi_funders_access($op, $f) {
  global $user;

  if (empty($f['id']))
    return true;

  if (!empty($f['user'])) {
    $fid = explode('-',$f['user']);
  }


  switch ($op) {
    case 'update':
      if ($user->uid == $f['user_created'])
        return TRUE;
      if ($user->uid == $fid[0])
        return TRUE;
  }
  return FALSE;

}

?>