<?php
/**
 * @file guifi_funders.inc.php
 * Manage guifi_funders fieldssets, validations, etc...
 */

 function guifi_funders_load($id, $subject_type, $ret = "txt") {
   $qsql = db_query('SELECT * FROM {guifi_funders} ' .
     'WHERE subject_id = :id ' .
     ' AND subject_type = :type ' .
     'ORDER BY id',
     array(':id' => $id, ':type' => $subject_type));

   while ($m = $qsql->fetchAssoc()) {
   	 switch ($ret) {
   	   case "txt":
   	     if (!(empty($m['supplier_id'])))
   	       $m['supplier'] = $m['supplier_id'].'-'.budgets_supplier_get_suppliername($m['supplier_id']);
   	     if (!(empty($m['user_id']))) {
   	       $u = user_load($m['user_id']);
           $m['user'] = $m['user_id'].'-'.$u->name.' ('.$u->mail.') ';
         }
         $funders[] = $m;
         break;
       case "uid":
   	     if (!(empty($m['supplier_id']))) {
   	       $s = node_load($m['supplier_id']);
   	       $funders[] = $s->uid;
   	     }
   	     if (!(empty($m['user_id'])))
   	       $funders[] = $m['user_id'];
         guifi_log(GUIFILOG_TRACE,
           'function guifi_funders_load(funders)',
           $funders);
         break;
   	 }
   }

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
    } else
      $f['supplier_id'] = 0;

    if (!(empty($f['user']))) {
      $mid = explode('-',$f['user']);
      $f['user_id'] = $mid[0];
    } else
      $f['user_id'] = 0;

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
        array('html'=>false,'attributes'=>
          array('title'=> $v['comment']))
        );
  	}
  	if (!empty($v['user'])) {
      $mid=explode('-',$v['user']);
      $u=user_load($mid[0]);
      $mstr[] = l($u->name,
        'user/'.$mid[0],
        array('html'=>false,'attributes'=>
          array('title'=> $v['comment']))
        );
  	}
  	if (empty($v['user']) and empty($v['supplier']))
      $mstr[] = $v['comment'];

  }
  return $mstr;
}

function guifi_funders_form($node,&$form_weight) {

  guifi_log(GUIFILOG_TRACE, 'function guifi_funders_form(funders 1)', $node->funders);
  foreach ($node->funders as $km => $vm)
    if (($vm['new'] and (empty($vm['user']) and empty($vm['supplier']) and empty($vm['comment'])))) {
      unset($node->funders[$km]);
      guifi_log(GUIFILOG_TRACE, 'function guifi_funders_form(funders -)', $km);
    }
  guifi_log(GUIFILOG_TRACE, 'function guifi_funders_form(funders 2)', $node->funders);

  $form['funders'] = array(
    '#type'        => 'fieldset',
    '#title'       => t('Funder(s)'),
    '#description' => t('Funders for this infrastructure. Used to recognize the ownership.<br>' .
      'A funder is who contribute to this infrastructure. When a funder claims for refunds, new funder contributors become co-owners.<br>' .
      'Use web username, supplier or a free text description, whatever is more suitable.<br>'.
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
      '#size' => 40,
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

    if ($node->funders[$funder_id]) {
      $u = user_load($node->funders[$funder_id]['user_created']);
      $form['funders'][$funder_id]['created'] = array (
        '#title'=> t('Created'),
        '#disabled'=> yes,
        '#type' => 'item',
        '#value' => l($u->name,'user/'.$u->uid).
          ', '.format_date($node->funders[$funder_id]['timestamp_created'],'small'),
        '#weight' => $form_weight++,
      );
    }

    if ($funder_id == $nfunders)
      $form['funders'][$funder_id]['new'] = array('#type'=>'hidden','#value'=>true);

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