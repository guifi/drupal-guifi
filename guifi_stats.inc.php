<?php
/**
 * @file guifi_zone.inc.php
 * Created on 1/08/2009 by Eduard
 * Functions for statistics graphs
 */

 function guifi_stats($action,$statsid = 0) {
 if (!is_numeric($statsid))
    return;
    
  switch ($action) {
  case 'chart':
    guifi_stats_chart();
    return;
    break;
  case 'chart01': //growth_chart
    guifi_stats_chart01();
    return;
    break;
  case 'chart02':  //annualincrement
    guifi_stats_chart02();
    return;
    break;
  case 'chart03':  //monthlyaverage':
    guifi_stats_chart03();
    return;
    break;
  case 'chart04': //lastyear':
    guifi_stats_chart04();
    return;
    break;
  case 'chart05': //':
    guifi_stats_chart05($statsid);
    return;
    break;
  case 'chart06': //pie zones':
    guifi_stats_chart06();
    return;
    break;
  case 'chart07': //Areas with the highest annual increase:
    guifi_stats_chart07();
    return;
    break;
  case 'feeds': //total working nodes http://guifi.net/guifi/stats/nodes/0
    $ret=guifi_stats_feeds($statsid);
    echo $ret;
    return;
    break;
  }
}

/*
 * growthmap
 */
function guifi_stats_growthmap() {
  $output = "";
  if (guifi_gmap_key()) {
    drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_gmap_growthmap.js','module');
    $output .=  '<form>' .
        '<input type=hidden value='.base_path().drupal_get_path('module','guifi').'/js/'.' id=edit-jspath />' .
        '<input type=hidden value='.variable_get('guifi_wms_service','').' id=guifi-wms />' .
        '</form>';
    $output .= drupal_get_form('guifi_growthmap_map_form');
    $output .= '<div id="map" style="width: 800px; height: 600px; margin:5px;"></div>';
    $output .= '<div id="footmap" style="margin:5px;">'.t('Mode:').'</div>';
    $output .= '<canvas id="testcanvas" width="1px" height="1px"></canvas>';
    if(isset($_GET['id'])){
      $output .='<form><input type=hidden value=1 id=maprun /></form>';
    }else{
      $output .='<form><input type=hidden value=0 id=maprun /></form>';
    }
  }

  guifi_log(GUIFILOG_TRACE,'growthmap',1);

  return $output;
}
 
function guifi_growthmap_map_form($form_state) { 
  $form['#action'] = '';
  $form['formmap2'] = array(
    '#type' => 'textfield',
    '#name' => 'formmap2',
    '#default_value' => '',
    '#size' => 63,
    '#attributes' => array('style' => 'margin:5px;text-align:center;font-size:24px'),
    '#prefix' => '<div style="align:center">',
    '#suffix' => '</div>'
  );
  return $form;
}

/*
 * nodes statistics
 */
function guifi_stats_nodes() {
  drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_stats_nodes.js','module');
  $output = "";
  if(isset($_GET['id'])){
    $vid=$_GET['id'];
    if(isset($_GET['zone'])){
      $zone_id=$_GET['zone'];
      if($zone_id=="3671") $zone_id="0";
    }else{
      $zone_id="0";
    }
    if($zone_id!="0")
      $vz="?zone=".$zone_id;
    else
      $vz="?zone=0";
    if(isset($_GET['width']))
      $vz.="&width=".$_GET['width'];
    if(isset($_GET['height']))
      $vz.="&height=".$_GET['height'];
    if(isset($_GET['title']))
      $vz.="&title=".$_GET['title'];
    switch($vid){
    case ($vid=='5'):
      if(isset($_GET['sid'])){
        $v=$_GET['sid'];
      }else{
        $v='12';
      }
      $output .= '<div id="plot" style="width: 500px; border-style:none; margin:5px;"><img src="/guifi/stats/chart0'.$vid.'/'.$v.$vz.'"></div>';
      break;
    case ($vid>=1 && $vid<=9):
      $output .= '<div id="plot" style="width: 500px; border-style:none; margin:5px;"><img src="/guifi/stats/chart0'.$vid.'/0'.$vz.'"></div>';
      break;
    default:
      $vid='0';
      break;
    }
  }else{
    $vid='0';
    if(isset($_GET['zone'])){
      $zone_id=$_GET['zone'];
      if($zone_id=="3671") $zone_id="0";
    }else{
      $zone_id="0";
    }
  }
  
  if($vid=='0'){
    $output .= drupal_get_form('guifi_stats_nodes_form',$zone_id);
    $output .= '<div id="sep" style="height: 5px; border-style:none; float:none; margin:5px;"></div>';
    $output .= '<div id="plot" style="width: 500px; border-style:none; float:right; margin:5px;"></div>';
    $output .= '<div id="menu" style="width: 230px; margin:5px;">';
    $output .= '<a href="javascript:guifi_stats_chart01()">'.t("1 Growth chart").'</a>';
    $output .= '<br /><a href="javascript:guifi_stats_chart02()">'.t("2 Annual increment").'</a>';
    $output .= '<br /><a href="javascript:guifi_stats_chart03()">'.t("3 Monthly average").'</a>';
    $output .= '<br /><a href="javascript:guifi_stats_chart04()">'.t("4 Last year").'</a>';
    $output .= '<br /><a href="javascript:guifi_stats_chart05(3)">'.t("5.3 Nodes per month, avr. 3m.").'</a>';
    $output .= '<br /><a href="javascript:guifi_stats_chart05(6)">'.t("5.6 Nodes per month, avr. 6m.").'</a>';
    $output .= '<br /><a href="javascript:guifi_stats_chart05(12)">'.t("5.12 Nodes per month, avr. 12m.").'</a>';
    $output .= '<br /><a href="javascript:guifi_stats_chart06()">'.t("6 Zones").'</a>';
    $output .= '<br /><a href="javascript:guifi_stats_chart07()">'.t("7 Largest annual increase").'</a>';
    $output .= '</div>';
    $output .= '<div style="height:300px">&nbsp;</div>';
    $output .= '<div style="width:700px;">';
    $output .= t('link:').' http://guifi.net/guifi/menu/stats/nodes?id=N';
    $output .= '<br />'.t('link:').' http://guifi.net/guifi/menu/stats/nodes?id=N&sid=M';
    $output .= '<br />'.t('image:').' &lt;img src="http://guifi.net/guifi/stats/chart?id=N"&gt;';
    $output .= '<br />'.t('image:').' &lt;img src="http://guifi.net/guifi/stats/chart?id=N&sid=M"&gt;';
    $output .= '<br />'.t('options:').' &zone=nnnn&width=nnn&height=nnn&title=';
    $output .= '</div>';
  }    
  guifi_log(GUIFILOG_TRACE,'stats_nodes',1);

  return $output;
}
function guifi_stats_nodes_form($form_state,$zone_id) {
    $form['#action'] = '';
    $form['zone_id'] = guifi_zone_select_field((int)$zone_id,'zone_id');
    $form['zone_id']['#weight'] = 1;
    return $form;
}
function guifi_stats_chart() {
  if(isset($_GET['id'])){
    $vid=$_GET['id'];
    switch($vid){
    case '1':
      guifi_stats_chart01();
      break;
    case '2':
      guifi_stats_chart02();
      break;
    case '3':
      guifi_stats_chart03();
      break;
    case '4':
      guifi_stats_chart04();
      break;
    case '5':
      if(isset($_GET['sid'])){
        $v=$_GET['sid'];
      }else{
        $v='12';
      }
      guifi_stats_chart05($v);
      break;
    case '6':
      guifi_stats_chart06();
      break;
    case '7':
      guifi_stats_chart07();
      break;
    default:
      guifi_stats_chart01();
      break;
    }
  }
}

//create gif working nodes
function guifi_stats_chart01(){ //growth_chart
    include drupal_get_path('module','guifi').'/contrib/phplot/phplot.php';
    $gDirTTFfonts=drupal_get_path('module','guifi').'/contrib/fonts/';  
    if(isset($_GET['width'])){
      $gwidth=$_GET['width'];
    }else{
      $gwidth=500;
    }
    if(isset($_GET['height'])){
      $gheight=$_GET['height'];
    }else{
      $gheight=450;
    }
    if(isset($_GET['zone'])){
      $zone_id=$_GET['zone'];
      if($zone_id=="3671") $zone_id="0";
    }else{
      $zone_id="0";
    }
    $vsql="select COUNT(*) as num, MONTH(FROM_UNIXTIME(timestamp_created)) as mes, YEAR(FROM_UNIXTIME(timestamp_created)) as ano
      from {guifi_location} where status_flag='Working' ";
    if($zone_id!="0"){
      $achilds=guifi_zone_childs($zone_id);
      $v="";
      foreach ($achilds as $key => $child) {
        if($v=="")
          $v .= "zone_id=".$child;
        else
          $v .= " or zone_id=".$child;
      }
      $vsql .= "AND (".$v.") ";
    }
    $vsql .= "GROUP BY YEAR(FROM_UNIXTIME(timestamp_created)),MONTH(FROM_UNIXTIME(timestamp_created)) ";
    $result=db_query($vsql);
    $inicial=5;
    $nreg=$inicial;
    $tot=0;
    $ano=2004;
    $mes=5;
    $items=2004;
    $label="a";
    $today=getdate();
	while ($record=db_fetch_object($result)){
      if($record->ano>=2004){
        if($mes==12){
          $mes=1;
          $ano++;
        }else{
          $mes++;
        }
        if($ano==$today[year] && $mes>=$today[mon]){
          if($mes==1){
            $mes=12;
            $ano--;
          }else{
            $mes--;
          }
          break;
        }
        while ($ano<$record->ano || $mes<$record->mes){
          $nreg++;
          if($mes==6){
            $label=$ano;
          }else{
            $label='';
          }
          $data[]=array("$label",$nreg,$tot);
          if($mes==12){
            $mes=1;
            $ano++;
          }else{
            $mes++;
          }
        }
        $tot+=$record->num;
        $nreg++;
        if($mes==6){
          $label=$ano;
        }else{
          $label='';
        }
        $data[]=array("$label",$nreg,$tot);
      }else{
         $tot+=$record->num;
      };
	};
    while($mes<12){
      $nreg++;
      $mes++;
      if($mes==6){
        $label=$ano;
      }else{
        $label='';
      }
      $data[]=array("$label",$nreg,"");
    }
    if($zone_id=="0") $inc=1000;
    else if($tot<=10) $inc=1;
    else {
      $vlen=strlen($tot);
      $vini=substr($tot,0,1);
      $inc=str_pad($vini,$vlen-1,"0");
    }
    $items=($ano-$items+1)*12;
    $shapes = array( 'none');
    $plot = new PHPlot($gwidth,$gheight);
    $plot->SetPlotAreaWorld(0, 0,$items, NULL);
    $plot->SetFileFormat('png');
    $plot->SetDataType("data-data");
    $plot->SetDataValues($data);
    $plot->SetPlotType("linepoints");
    $plot->SetYTickIncrement($inc);
    $plot->SetXTickIncrement(12);
    $plot->SetSkipBottomTick(TRUE);
    $plot->SetSkipLeftTick(TRUE);
    $plot->SetXAxisPosition(0);
    $plot->SetPointShapes($shapes); 
    $plot->SetPointSizes(10);
    $plot->SetTickLength(3);
    $plot->SetDrawXGrid(TRUE);
    $plot->SetTickColor('grey');
    $plot->SetTTFPath($gDirTTFfonts);
    $plot->SetFontTTF('title', 'Vera.ttf', 12);
    if(isset($_GET['title'])){
      if($_GET['title']!='void')
        $plot->SetTitle("guifi.net      \n".t($_GET['title']));
    }else{
      if($zone_id=="0")
        $plot->SetTitle("guifi.net      \n".t('Growth chart'));
      else
        $plot->SetTitle("guifi.net    ".t('zone').": ".guifi_get_zone_name($zone_id)."\n".t('Growth chart'));
    }
    $plot->SetXTitle(t('Years'));
    $plot->SetYTitle(t('Working nodes'));
    $plot->SetDrawXDataLabelLines(FALSE);
    $plot->SetXLabelAngle(0);
    $plot->SetXLabelType('custom', 'guifi_stats_chart01_LabelFormat');
    $plot->SetGridColor('red');
    $plot->SetPlotBorderType('left');
    $plot->SetDataColors(array('orange'));
    $plot->SetTextColor('DimGrey');
    $plot->SetTitleColor('DimGrey');
    $plot->SetLightGridColor('grey');
    $plot->SetBackgroundColor('white');
    $plot->SetTransparentColor('white');
    $plot->SetXTickLabelPos('none');
    $plot->SetXDataLabelPos('plotdown');
    $plot->SetIsInline(TRUE);
    $plot->DrawGraph();
}
function guifi_stats_chart01_LabelFormat($value){
   return($value);
}
//create gif annual increment
function guifi_stats_chart02(){
    include drupal_get_path('module','guifi').'/contrib/phplot/phplot.php';
    $gDirTTFfonts=drupal_get_path('module','guifi').'/contrib/fonts/';  
    if(isset($_GET['width'])){
      $gwidth=$_GET['width'];
    }else{
      $gwidth=500;
    }
    if(isset($_GET['height'])){
      $gheight=$_GET['height'];
    }else{
      $gheight=450;
    }
    if(isset($_GET['zone'])){
      $zone_id=$_GET['zone'];
      if($zone_id=="3671") $zone_id="0";
    }else{
      $zone_id="0";
    }
    $vsql="select COUNT(*) as num, YEAR(FROM_UNIXTIME(timestamp_created)) as ano
      from {guifi_location} where status_flag='Working' ";
    if($zone_id!="0"){
      $achilds=guifi_zone_childs($zone_id);
      $v="";
      foreach ($achilds as $key => $child) {
        if($v=="")
          $v .= "zone_id=".$child;
        else
          $v .= " or zone_id=".$child;
      }
      $vsql .= "AND (".$v.") ";
    }
    $vsql .= "GROUP BY YEAR(FROM_UNIXTIME(timestamp_created)) ";
    
    $result=db_query($vsql);    
    $tot=0;
    $max=0;
	while ($record=db_fetch_object($result)){
      if($record->ano>=2004){
         //$nreg++;
         $tot+=$record->num;
         $data[]=array("$record->ano",$tot);
         if($tot>$max) $max=$tot;
         $tot=0;
      }else{
         $tot+=$record->num;
      };
	};
    if($max<=10) $inc=1;
    else {
      $vlen=strlen($max);
      $vini=substr($max,0,1);
      $inc=str_pad($vini,$vlen-1,"0");
    }
    $shapes = array( 'none');
    $plot = new PHPlot($gwidth,$gheight);
    $plot->SetPlotAreaWorld(0, 0, NULL, NULL);
    $plot->SetFileFormat('png');
    $plot->SetDataType("text-data");
    $plot->SetDataValues($data);
    $plot->SetPlotType("bars"); 
    $plot->SetYTickIncrement($inc);
    $plot->SetSkipBottomTick(TRUE);
    $plot->SetSkipLeftTick(TRUE);
    $plot->SetTickLength(0);
    $plot->SetXTickPos('none');
    $plot->SetYDataLabelPos('plotin');
    $plot->SetTickColor('grey');
    $plot->SetTTFPath($gDirTTFfonts);
    $plot->SetFontTTF('title', 'Vera.ttf', 12);
    if(isset($_GET['title'])){
        $plot->SetTitle("guifi.net      \n".t($_GET['title']));
    }else{
      if($zone_id=="0")
        $plot->SetTitle("guifi.net      \n".t('Annual increment'));
      else
        $plot->SetTitle("guifi.net    ".t('zone').": ".guifi_get_zone_name($zone_id)."\n".t('Annual increment'));
    }    
    $plot->SetXTitle(t('Years'));
    $plot->SetYTitle(t('Working nodes'));
    $plot->SetXDataLabelPos('plotdown');
    $plot->SetXLabelAngle(0);
    $plot->SetGridColor('red');
    $plot->SetPlotBorderType('left');
    $plot->SetDataColors(array('orange'));
    $plot->SetTextColor('DimGrey');
    $plot->SetTitleColor('DimGrey');
    $plot->SetLightGridColor('grey');
    $plot->SetBackgroundColor('white');
    $plot->SetTransparentColor('white');
    $plot->SetIsInline(TRUE);
    $plot->DrawGraph();
}

//create gif monthly average
function guifi_stats_chart03(){
    include drupal_get_path('module','guifi').'/contrib/phplot/phplot.php';
    $gDirTTFfonts=drupal_get_path('module','guifi').'/contrib/fonts/';  
    if(isset($_GET['width'])){
      $gwidth=$_GET['width'];
    }else{
      $gwidth=500;
    }
    if(isset($_GET['height'])){
      $gheight=$_GET['height'];
    }else{
      $gheight=450;
    }
    if(isset($_GET['zone'])){
      $zone_id=$_GET['zone'];
      if($zone_id=="3671") $zone_id="0";
    }else{
      $zone_id="0";
    }
    $vsql="select COUNT(*) as num, month(FROM_UNIXTIME(timestamp_created)) as mes
      from {guifi_location} where status_flag='Working' ";
    if($zone_id!="0"){
      $achilds=guifi_zone_childs($zone_id);
      $v="";
      foreach ($achilds as $key => $child) {
        if($v=="")
          $v .= "zone_id=".$child;
        else
          $v .= " or zone_id=".$child;
      }
      $vsql .= "AND (".$v.") ";
    }

    $vsql .= "GROUP BY MONTH(FROM_UNIXTIME(timestamp_created)) ";
    
    $result=db_query($vsql);        
    
    $tot=0;
    $valor=0;
	while ($record=db_fetch_object($result)){
        $tot+=$record->num;
        $data[]=array("$record->mes",$record->num);
    };
	foreach ($data as &$dat){
        $dat[1]=$dat[1]*100/$tot;
	};
    $shapes = array( 'none');
    $plot = new PHPlot($gwidth,$gheight);
    $plot->SetPlotAreaWorld(0, 0, NULL, NULL);
    $plot->SetFileFormat('png');
    $plot->SetDataType("text-data");
    $plot->SetDataValues($data);
    $plot->SetPlotType("bars"); 
    //$plot->SetYTickIncrement(10);
    $plot->SetSkipBottomTick(TRUE);
    $plot->SetSkipLeftTick(TRUE);
    $plot->SetTickLength(0);
    $plot->SetXTickPos('none');
    $plot->SetYTickPos('none');
    $plot->SetDrawYGrid(FALSE);
    $plot->SetYTickLabelPos('none');
    $plot->SetYDataLabelPos('plotin');
    $plot->SetTickColor('grey');
    $plot->SetTTFPath($gDirTTFfonts);
    $plot->SetFontTTF('title', 'Vera.ttf', 12);
    if(isset($_GET['title'])){
        $plot->SetTitle("guifi.net      \n".t($_GET['title']));
    }else{
      if($zone_id=="0")
        $plot->SetTitle("guifi.net      \n".t('Monthly average'));
      else
        $plot->SetTitle("guifi.net    ".t('zone').": ".guifi_get_zone_name($zone_id)."\n".t('Monthly average'));
    }
    $plot->SetXTitle(t('Months'));
    $plot->SetYTitle(t('% Working nodes'));
    $plot->SetXDataLabelPos('plotdown');
    $plot->SetXLabelAngle(0);
    $plot->SetYLabelType('data', 2);
    $plot->SetGridColor('red');
    $plot->SetPlotBorderType('left');
    $plot->SetDataColors(array('orange'));
    $plot->SetTextColor('DimGrey');
    $plot->SetTitleColor('DimGrey');
    $plot->SetLightGridColor('grey');
    $plot->SetBackgroundColor('white');
    $plot->SetTransparentColor('white');
    $plot->SetIsInline(TRUE);
    $plot->DrawGraph();
}

//create gif last year
function guifi_stats_chart04(){
    include drupal_get_path('module','guifi').'/contrib/phplot/phplot.php';
    $gDirTTFfonts=drupal_get_path('module','guifi').'/contrib/fonts/';  
    if(isset($_GET['width'])){
      $gwidth=$_GET['width'];
    }else{
      $gwidth=500;
    }
    if(isset($_GET['height'])){
      $gheight=$_GET['height'];
    }else{
      $gheight=450;
    }
    $today=getdate();
    $year=$today[year];
    $month=$today[mon];
    $month=$month-12;
    $n=0;
    $tot=0;
    if($month<1){
      $year=$year-1;
      $month=12+$month;
    }
    $datemin=mktime(0,0,0,$month,1,$year);
    
    if(isset($_GET['zone'])){
      $zone_id=$_GET['zone'];
      if($zone_id=="3671") $zone_id="0";
    }else{
      $zone_id="0";
    }
    $vsql="select COUNT(*) as num, max(timestamp_created) as fecha, max(month(FROM_UNIXTIME(timestamp_created))) as mes,max(year(FROM_UNIXTIME(timestamp_created))) as year
      from {guifi_location}
      where timestamp_created >= ".$datemin." and status_flag='Working' ";
    if($zone_id!="0"){
      $achilds=guifi_zone_childs($zone_id);
      $v="";
      foreach ($achilds as $key => $child) {
        if($v=="")
          $v .= "zone_id=".$child;
        else
          $v .= " or zone_id=".$child;
      }
      $vsql .= "AND (".$v.") ";
    }

    $vsql .= "GROUP BY Year(FROM_UNIXTIME(timestamp_created)), month(FROM_UNIXTIME(timestamp_created)) ";
    
    $result=db_query($vsql);        
    
    while ($record=db_fetch_object($result)){
      $data[]=array("$record->mes".'/'.substr("$record->year",2,2),$record->num);
      if($record->mes!=$today[mon] || $record->year!=$today[year]){
        $n++;
        $tot=$tot+$record->num;
      }
    };
    if($n>0){
      $tot=$tot/$n;
    }
    $shapes = array( 'none');
    $plot = new PHPlot($gwidth,$gheight);
    $plot->SetPlotAreaWorld(0, 0, NULL, NULL);
    $plot->SetFileFormat('png');
    $plot->SetDataType("text-data");
    $plot->SetDataValues($data);
    $plot->SetPlotType("bars"); 
    $plot->SetYTickIncrement($tot);
    $plot->SetSkipBottomTick(TRUE);
    $plot->SetSkipLeftTick(TRUE);
    $plot->SetTickLength(0);
    //$plot->SetXTickPos('none');
    $plot->SetYDataLabelPos('plotin');
    $plot->SetYLabelType('data', 0);
    $plot->SetTickColor('grey');
    $plot->SetTTFPath($gDirTTFfonts);
    $plot->SetFontTTF('title', 'Vera.ttf', 12);
    if(isset($_GET['title'])){
        $plot->SetTitle("guifi.net      \n".t($_GET['title']));
    }else{
      if($zone_id=="0")
        $plot->SetTitle("guifi.net      \n".t('Last year'));
      else
        $plot->SetTitle("guifi.net    ".t('zone').": ".guifi_get_zone_name($zone_id)."\n".t('Last year'));
    }
    $plot->SetXTitle(t('Months'));
    $plot->SetYTitle(t('Working nodes'));
    $plot->SetXDataLabelPos('plotdown');
    $plot->SetXLabelAngle(0);
    $plot->SetGridColor('red');
    $plot->SetPlotBorderType('left');
    $plot->SetDataColors(array('orange'));
    $plot->SetTextColor('DimGrey');
    $plot->SetTitleColor('DimGrey');
    $plot->SetLightGridColor('grey');
    $plot->SetBackgroundColor('white');
    $plot->SetTransparentColor('white');
    $plot->SetIsInline(TRUE);
    $plot->DrawGraph();
}
//Nodes per month, average of 6 months
function guifi_stats_chart05($nmonths){ 
    include drupal_get_path('module','guifi').'/contrib/phplot/phplot.php';
    $gDirTTFfonts=drupal_get_path('module','guifi').'/contrib/fonts/';  
    if(isset($_GET['width'])){
      $gwidth=$_GET['width'];
    }else{
      $gwidth=500;
    }
    if(isset($_GET['height'])){
      $gheight=$_GET['height'];
    }else{
      $gheight=450;
    }
    if(isset($_GET['zone'])){
      $zone_id=$_GET['zone'];
      if($zone_id=="3671") $zone_id="0";
    }else{
      $zone_id="0";
    }
    $vsql="select COUNT(*) as num, MONTH(FROM_UNIXTIME(timestamp_created)) as mes, YEAR(FROM_UNIXTIME(timestamp_created)) as ano 
      from {guifi_location}
      where status_flag='Working' ";
    if($zone_id!="0"){
      $achilds=guifi_zone_childs($zone_id);
      $v="";
      foreach ($achilds as $key => $child) {
        if($v=="")
          $v .= "zone_id=".$child;
        else
          $v .= " or zone_id=".$child;
      }
      $vsql .= "AND (".$v.") ";
    }

    $vsql .= "GROUP BY YEAR(FROM_UNIXTIME(timestamp_created)),MONTH(FROM_UNIXTIME(timestamp_created)) ";
    
    $result=db_query($vsql);
    
    $inicial=5;
    $nreg=$inicial;
    $tot=0;
    $ano=2004;
    $mes=5;
    $items=2004;
    $label="a";
    $n=0;
    $med=0;
    $datos=array(0,0,0,0,0,0,0,0,0,0,0,0,0);
    $today=getdate();
    if($nmonths==0) $nmonths=12;
    $max=0;
	while ($record=db_fetch_object($result)){
      if($record->ano>=2004){
        if($mes==12){
          $mes=1;
          $ano++;
        }else{
          $mes++;
        }
        if($ano==$today[year] && $mes>=$today[mon]){
          if($mes==1){
            $mes=12;
            $ano--;
          }else{
            $mes--;
          }
          break;
        }
        while ($ano<$record->ano || $mes<$record->mes){
          $nreg++;
          if($mes==6){
            $label=$ano;
          }else{
            $label='';
          }
          if($n==0){
            $tot+=$record->num;
          }else{
            $tot=$record->num;
          }
          $tot2=fmediacalc($tot,$datos,$n,$nmonths);
          $data[]=array("$label",$nreg,$tot2);
          if(floor($tot2)>$max) $max=floor($tot2);
          if($mes==12){
            $mes=1;
            $ano++;
          }else{
            $mes++;
          }
        }
        $tot+=$record->num;
        $nreg++;
        if($mes==6){
          $label=$ano;
        }else{
          $label='';
        }
        if($n==0){
          $tot+=$record->num;
        }else{
          $tot=$record->num;
        }
        $tot2=fmediacalc($tot,$datos,$n,$nmonths);
        $data[]=array("$label",$nreg,$tot2);
        if(floor($tot2)>$max) $max=floor($tot2);
      }else{
         $tot+=$record->num;
      };
	};
    while($mes<12){
      $nreg++;
      $mes++;
      if($mes==6){
        $label=$ano;
      }else{
        $label='';
      }
      $data[]=array("$label",$nreg,"");
    }
    if($tot<=10) $inc=1;
    else {
      $vlen=strlen($max);
      $vini=substr($max,0,1);
      $inc=str_pad($vini,$vlen-1,"0");
    }
    $items=($ano-$items+1)*12;
    $shapes = array( 'none');
    $plot = new PHPlot($gwidth,$gheight);
    $plot->SetPlotAreaWorld(0, 0,$items, NULL);
    $plot->SetFileFormat('png');
    $plot->SetDataType("data-data");
    $plot->SetDataValues($data);
    $plot->SetPlotType("linepoints"); 
    $plot->SetYTickIncrement($inc);
    $plot->SetXTickIncrement(12);
    $plot->SetSkipBottomTick(TRUE);
    $plot->SetSkipLeftTick(TRUE);
    $plot->SetXAxisPosition(0);
    $plot->SetPointShapes($shapes); 
    $plot->SetPointSizes(10);
    $plot->SetTickLength(3);
    $plot->SetDrawXGrid(TRUE);
    $plot->SetTickColor('grey');
    $plot->SetTTFPath($gDirTTFfonts);
    $plot->SetFontTTF('title', 'Vera.ttf', 12);
    if(isset($_GET['title'])){
        $plot->SetTitle("guifi.net      \n".t($_GET['title']));
    }else{
      if($zone_id=="0")
        $plot->SetTitle("guifi.net      \n".t('Nodes per month, '."$nmonths".' months average'));
      else
        $plot->SetTitle("guifi.net    ".t('zone').": ".guifi_get_zone_name($zone_id)."\n".t('Nodes per month, '."$nmonths".' months average'));
    }
    $plot->SetXTitle(t('Years'));
    $plot->SetYTitle(t('Working nodes'));
    $plot->SetDrawXDataLabelLines(FALSE);
    $plot->SetXLabelAngle(0);
    $plot->SetXLabelType('custom', 'guifi_stats_chart05_LabelFormat');
    $plot->SetGridColor('red');
    $plot->SetPlotBorderType('left');
    $plot->SetDataColors(array('orange'));
    $plot->SetTextColor('DimGrey');
    $plot->SetTitleColor('DimGrey');
    $plot->SetLightGridColor('grey');
    $plot->SetBackgroundColor('white');
    $plot->SetTransparentColor('white');
    $plot->SetXTickLabelPos('none');
    $plot->SetXDataLabelPos('plotdown');
    $plot->SetIsInline(TRUE);
    $plot->DrawGraph();
}
function guifi_stats_chart05_LabelFormat($value){
   return($value);
}
function fmediacalc($tot,&$datos,&$n,$nmonths){
  $v=0;
  $i=0;
  if($n>=$nmonths){
    $n=1;
  }else{
    $n++;
  }
  $datos[$n]=$tot;
  for($i=1;$i<=$nmonths;$i++){
    $v=$v+$datos[$i];
  }
  //return($datos[$n]);
  return($v/$nmonths);
}

//create gif pie zones
function guifi_stats_chart06(){
    include drupal_get_path('module','guifi').'/contrib/phplot/phplot.php';
    $gDirTTFfonts=drupal_get_path('module','guifi').'/contrib/fonts/';  
    if(isset($_GET['width'])){
      $gwidth=$_GET['width'];
    }else{
      $gwidth=500;
    }
    if(isset($_GET['height'])){
      $gheight=$_GET['height'];
    }else{
      $gheight=450;
    }
    $today=getdate();
    $year=$today[year];
    $month=$today[mon];
    $month=$month-12;
    $n=0;
    $tot=0;
    if($month<1){
      $year=$year-1;
      $month=12+$month;
    }
    $datemin=mktime(0,0,0,$month,1,$year);
    
    if(isset($_GET['zone'])){
      $zone_id=$_GET['zone'];
      if($zone_id=="0") $zone_id="3671";
    }else{
      $zone_id="3671";
    }
    
    $azone=array();
    $avalue=array();
    $azone[$zone_id]=array($zone_id);
    $achilds=array_keys(guifi_zone_childs_tree($zone_id,1));
    foreach ($achilds as $key => $child) {
      if ($child != $zone_id){
        $azone[$child]=array();
        $avalue[$child]=0;
        $aschilds=guifi_zone_childs($child);
        foreach ($aschilds as $skey => $schild) {
          array_push($azone[$child],$schild);
        }
      }
    }
    $vsql="select COUNT(*) as num, zone_id
      from {guifi_location}
      where timestamp_created >= ".$datemin." and status_flag='Working' ";
    if($zone_id!="0"){
      $achilds=guifi_zone_childs($zone_id);
      $v="";
      foreach ($achilds as $key => $child) {
        if($v=="")
          $v .= "zone_id=".$child;
        else
          $v .= " or zone_id=".$child;
      }
      $vsql .= "AND (".$v.") ";
    }
    $vsql .= "GROUP BY zone_id ";
    
    $result=db_query($vsql);        
    while ($record=db_fetch_object($result)){
      foreach ($azone as $key => $grupzone) {
        if (in_array($record->zone_id,$grupzone)){
          $avalue[$key]=$avalue[$key]+$record->num;
        }
      }
    };
    foreach ($avalue as $key => $value) {
      if($value!=0){
        $data[]=array(guifi_get_zone_name($key),$value);
        $tot=$tot+$value;
      }
    }

    $shapes = array( 'none');
    $plot = new PHPlot($gwidth,$gheight);
    $plot->SetPlotAreaWorld(0, 0, NULL, NULL);
    $plot->SetImageBorderType('plain');
    $plot->SetFileFormat('png');
    $plot->SetPlotType("pie"); 
    $plot->SetDataType("text-data-single");
    $plot->SetDataValues($data);
    $plot->SetDataColors(array('red', 'green', 'blue', 'yellow', 'cyan',
                        'magenta', 'brown', 'lavender', 'pink',
                        'gray', 'orange'));
    $plot->SetTTFPath($gDirTTFfonts);
    $plot->SetFontTTF('title', 'Vera.ttf', 12);
    $plot->SetFontTTF('legend', 'Vera.ttf', 7);
    if(isset($_GET['title'])){
        $plot->SetTitle("guifi.net      \n".t($_GET['title']));
    }else{
      if($zone_id=="0")
        $plot->SetTitle("guifi.net      \n".t('Last year'));
      else
        $plot->SetTitle("guifi.net    \n".t('zone').": ".guifi_get_zone_name($zone_id)."\n".t('Last year'));
    }

    $plot->SetShading(1);
    $plot->SetLabelScalePosition(0.45);
    $plot->SetLegendStyle("left","left");
    $plot->SetLegendPixels(0, 0);
    foreach ($data as $row)
      $plot->SetLegend(implode(': ', $row));
    $plot->SetIsInline(TRUE);
    $plot->DrawGraph();
}

//Largest annual increase
function guifi_stats_chart07(){
    include drupal_get_path('module','guifi').'/contrib/phplot/phplot.php';
    $gDirTTFfonts=drupal_get_path('module','guifi').'/contrib/fonts/';  
    if(isset($_GET['width'])){
      $gwidth=$_GET['width'];
    }else{
      $gwidth=500;
    }
    if(isset($_GET['height'])){
      $gheight=$_GET['height'];
    }else{
      $gheight=450;
    }
    $today=getdate();
    $year=$today[year];
    $month=$today[mon];
    $month=$month-12;
    $n=0;
    $tot=0;
    if($month<1){
      $year=$year-1;
      $month=12+$month;
    }
    $datemin=mktime(0,0,0,$month,1,$year);
    
    if(isset($_GET['zone'])){
      $zone_id=$_GET['zone'];
      if($zone_id=="0") $zone_id="0"; //"3671";
    }else{
      $zone_id="0";
    }
    
    $avalue=array();
    $adata=array();
    for($i=0;$i<10;$i++){
      $adata[]=array(0,0);
    }
    $vsql="select sum(if(timestamp_created >= ".$datemin.",1,0)) as num, count(*) as total, zone_id
      from {guifi_location}
      where status_flag='Working' ";
    if($zone_id!="0"){
      $achilds=guifi_zone_childs($zone_id);
      $v="";
      foreach ($achilds as $key => $child) {
        if($v=="")
          $v .= "zone_id=".$child;
        else
          $v .= " or zone_id=".$child;
      }
      $vsql .= "AND (".$v.") ";
    }
    $vsql .= "GROUP BY zone_id ";
    
    $result=db_query($vsql);        
    while ($record=db_fetch_object($result)){
      if($record->total>=20){
        $vn=$record->num/$record->total*100;
        $vmin=0;
        for($i=1;$i<10;$i++){
          if($adata[$vmin][1]>$adata[$i][1]){
            $vmin=$i;
          }
        }
        if($vn>$adata[$vmin][1]){
          $adata[$vmin][0]=$record->zone_id;
          $adata[$vmin][1]=$vn;
        }
      }
    }
    
    for($i=0;$i<10;$i++){
      if($adata[$i][1]!=0){
        $avalue[$adata[$i][0]]=$adata[$i][1];
      }
    }
    arsort($avalue);
    foreach ($avalue as $key => $value) {
      if($value!=0){
        $data[]=array(substr(guifi_get_zone_name($key),0,20)."   ",$value);
      }
    }



    $shapes = array( 'none');
    $plot = new PHPlot($gwidth,$gheight);
    $plot->SetPlotAreaWorld(0, 0, NULL, NULL);
    $plot->SetFileFormat('png');
    $plot->SetDataType("text-data");
    $plot->SetDataValues($data);
    $plot->SetPlotType("bars"); 
    $plot->SetXTickIncrement(1);
    $plot->SetSkipBottomTick(TRUE);
    $plot->SetSkipLeftTick(TRUE);
    $plot->SetTickLength(0);
    //$plot->SetXTickPos('none');
    $plot->SetYDataLabelPos('plotin');
    $plot->SetYLabelType('data', 0);
    $plot->SetTickColor('grey');
    $plot->SetTTFPath($gDirTTFfonts);
    $plot->SetFontTTF('title', 'Vera.ttf', 12);
    $plot->SetFontTTF('x_label', 'Vera.ttf', 8);
    if(isset($_GET['title'])){
        $plot->SetTitle("guifi.net      \n".t($_GET['title']));
    }else{
      if($zone_id=="0")
        $plot->SetTitle("guifi.net      \n".t('Largest annual increase'));
      else
        $plot->SetTitle("guifi.net    ".t('zone').": ".guifi_get_zone_name($zone_id)."\n".t('Largest annual increase'));
    }
    //$plot->SetXTitle(t('Zones'));
    $plot->SetYTitle(t('% increase'));
    $plot->SetXDataLabelPos('plotdown');
    //$plot->SetXLabelAngle(45);
    $plot->SetXDataLabelAngle(75);
    $plot->SetGridColor('red');
    $plot->SetPlotBorderType('left');
    $plot->SetDataColors(array('orange'));
    $plot->SetTextColor('DimGrey');
    $plot->SetTitleColor('DimGrey');
    $plot->SetLightGridColor('grey');
    $plot->SetBackgroundColor('white');
    $plot->SetTransparentColor('white');
    $plot->SetIsInline(TRUE);
    $plot->DrawGraph();
}


//stats feeds
function guifi_stats_feeds($pnum){
  $output="";
  switch ($pnum) {
  case 0: //total nodes
    $vst=t('statistics');
    if(isset($_GET['tex'])){
      $vt=$_GET['tex'];
    }else{
      $vt="%d% - %n% ".t('working nodes');
    }
    $output ='<?xml version="1.0" encoding="utf-8"?>';
    //$output .= '<rss version="2.0" xml:base="http://guifi.net"  xmlns:dc="http://purl.org/dc/elements/1.1/">';
    $output .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
    $output .= '<channel>';
    $output .= '<title>'.'guifi.net - '.$vst.'</title>';
    $output .= '<link>http://guifi.net/guifi/stats/feeds/0</link>';
    $output .= '<atom:link href="http://guifi.net/guifi/stats/feeds/0" rel="self" type="application/rss+xml" />';
    $output .= '<description>'.$vst.' guifi.net'.'</description>';
    $result=db_query("select COUNT(*) as num from {guifi_location} where status_flag='Working'");
    if ($record=db_fetch_object($result)){
      $output .= '<item>';
      $output .= '<guid isPermaLink="FALSE">http://guifi.net/guifi/menu/stats/nodes?dat='.date("d/m/Y",time()).'</guid>';
      $output .= '<description>';
      $vt = str_replace("%d%", date("d/m/Y",time()), $vt);
      $vt = str_replace("%n%", $record->num, $vt);
      $output .= $vt;
      //$output .= date("d/m/Y",time()).' = nodos activos = '.$record->num.' = fully working nodes!';
      $output .= '</description>';
      $output .= '</item>';
    };
    $output .= '</channel>';
    $output .= '</rss>';
    break;
  }
  return($output);
}

?>
