<?php
/**
 * @file guifi_heights.php
 */

/*
  Busca una dada d'elevació dins el cata.grd
  --------------------------------------------------------------
  Es calcula la posició $pos a partir dels indexs del array,
  partint de que cada nombre ocupa 4 bytes ASCII, i es retorna
  el valor de l'alçada real del punt
  */

function height($x,$y)
  {
    $grid = fopen('../../files/guifi/cata.grd','r');
    $pos = ((($y-1)*9235)+$x-1)*4;
    fseek($grid,$pos);
    $z = fread($grid,4);
    fclose($grid);
    return($z);
  }

/*
  Ubica una cordenada UTM dins la graella d'elevacions
  -------------------------------------------------------------
  Fa la traducció de UTM a la psició corresponent dins la graella
  agafant com a paràmetres la posicio que volem trobar, les coordenades
  de l'extrem i la resolucio de la graella
  */
  
function utm2grid(&$ux,&$uy)
  {
   $ux = (($ux-257980) / 30) +1;
   $uy = 8902-(($uy-4484960) / 30);
  }

/* 
  Escala els eixos en funció de la distància/alcada
  ----------------------------------------------------------------
  En funció de la llargada de les dades a representar determina a quins
  intervals s'han de dibuixar les marques dels eixos per que siguin 
  lògiques i llegibles, sempre conservant una proporcio múltiple de 1, 5
  o 10 en funcio de la mida.
  */

function set_tics($th)
  {
    If ($th>=80)
    {
         $h = (floor($th / 15));
         if ($h>10) {$h = $h - ($h % 5);}
         else {$h = 10;}
    }
    else
    {
       If ($th>15)
       {
         $h = (floor($th / 15));
         if ($h>5) {$h = $h - ($h % 5);}
         else {$h = 5;}
        }
        else
        {
         $h = (floor($th / 15));
         $h = $h - ($h % 5);
         if ($h==0) {$h=1;}
        }
    }  
    return ($h);
  }  

/* 
  Inerpola una alçada correponent a un punt de la graella
  -----------------------------------------------------------------------
  Calcula a partir de 4 punts coneguts, l'alçada paroximada d'un punt qualsevol
  entre els 4, a partir de l'equació d'un pla ajsutat als 4 punts
  */
  
  
function interpole_height($x,$y)
  {
  $basex=floor($x); // component x en la graella
  $basey=floor($y); // component y en la graella
  $dx=$x-$basex;    // desplaçament x dins la cel.la
  $dy=$y-$basey;    // desplaçament y dins la cel.la  
  $p1=height($basex,$basey);                        
  $p2=height($basex+1,$basey);
  $p3=height($basex,$basey-1);
  $p4=height($basex+1,$basey-1);    // alçades dels 4 punts + propers
  $a = ($p1 +$p2 +$p3 +$p4) /4;
  $b = (($p1*-1) +$p2 +($p3*-1) +$p4) /4;
  $c = (($p1*-1) +($p2*-1) +$p3 +$p4) /4;  // càlcul de paràmetres per l'equació del plà
  $dx=($dx*2) -1;
  $dy=($dy*2) -1;
  $z=$a + ($b*$dx) + ($c*$dy);      // substitucio i calcul de l'alçada
  return($z);
  }  
  
/*
   Format del pas de paràmetres 

   guifi_heights.php?
   x1=&y1=&x2=&y2=      // Obligatoris - les coordeandes UTM dels 2 nodes
   &node1=&node2=      // Opcionals - els noms dels 2 nodes
   &width=&height=      // Amplada de la imatge [ Per defecte 800 x 400 ]
   &h1=&h2=        // alçada Terra->Antena dels dos punts respectivament [ Per defecte 10 / 10]
   &res=        // resolució de la gràfica : NUmero de punts interpolats entre 2 reals [ Per defecte 1]
*/ 

if ( (array_key_exists("x1",$_GET)) &
     (array_key_exists("y1",$_GET)) &
     (array_key_exists("x2",$_GET)) &
     (array_key_exists("y2",$_GET)) )
  {
  $script_header = "";  // Espai per la capçalera
  $script_data = "";    // Espai per les dades del perfil
  $script_data2 = "";   // Espai per les dades de la LOV
  
  // Recollida de paràmetres
    
  $x1 = $_GET["x1"];
  $y1 = $_GET["y1"];
  $x2 = $_GET["x2"];
  $y2 = $_GET["y2"];
  
  $node1 = $_GET["node1"];
  $node2 = $_GET["node2"];
  
  $minheight = 4000 ;
  $maxheight = 0;

   // DEFAULTS
  
  if (array_key_exists("res",$_GET)) 
      {   $res = $_GET["res"];  }
  else 
      {   $res = 1;}   
        
  if (array_key_exists("h1",$_GET)) 
      {   $hnode1 = $_GET["h1"];  }
  else 
      {   $hnode1 = 10;          }
  if (array_key_exists("h2",$_GET)) 
      {   $hnode2 = $_GET["h2"];  }
  else 
      {   $hnode2 = 10;          }
  
  if (array_key_exists("width",$_GET)) 
      {   $width = $_GET["width"];  }
  else 
      {   $width = 800;          }
if (array_key_exists("height",$_GET)) 
      {   $height = $_GET["height"];  }
  else 
      {   $height = 400;          }
  
    
  // diferenciem entre els punts en UMT (x,y) i els de la graella (g,h)
  
  $g1 = $x1;
  $h1 = $y1;
  $g2 = $x2;
  $h2 = $y2;
  
  utm2grid($g1,$h1);
  utm2grid($g2,$h2);

  //falta comprovar si son iguals
  
  //------------------------1
  
  // calcul de les alçades dels punts intermitjos que passen per la recta entre node1 i node 2
  // el numero de punts (steps) ve determinat per la distància entre els nodes
  If ( ($g2<$g1) & ($h2<$h1) )
    {
    $a = $g1 - $g2;
    $b = $h1 - $h2;
    $m = $b / $a;
    $dist = sqrt(pow($a,2)+pow($b,2));
    $steps = round($dist*$res);
    $step_inc = abs($g1-$g2)/$steps;
    
    for ($i=0;$i<=$steps;$i++) 
      {
      $x = $i*$step_inc;
      $y = $m * $x;
      $x = $g1 - $x;
      $y = $h1 - $y;
      $z = interpole_height($x,$y);
      if ($z>$maxheight) {$maxheight = $z;}
      if ($z<$minheight) {$minheight = $z;}
      
      if ($i==0) 
        {
           $s=0;
           $script_data2 = $script_data2.$s." ".($z+$hnode1)."\n";
        }
      else 
        {
           $s=$dist * ($i/$steps) * 0.03;
        }
      $script_data = $script_data.$s." ".$z."\n";
      }
    $script_data2 = $script_data2.$s." ".($z+$hnode2)."\n";  
    }
  //------------------------2
  
  If ( ($g2>$g1) & ($h2<$h1) )
    {
    $a = $g2 - $g1;
    $b = $h1 - $h2;
    $m = $b / $a;
    $dist = sqrt(pow($a,2)+pow($b,2));
    $steps = round($dist*$res);
    $step_inc = abs($g1-$g2)/$steps;
    
    for ($i=0;$i<=$steps;$i++) 
      {
      $x = $i*$step_inc;
      $y = $m * $x;
      $x = $g1 + $x;
      $y = $h1 - $y;
      $z = interpole_height($x,$y);
     if ($z>$maxheight) {$maxheight = $z;}
      if ($z<$minheight) {$minheight = $z;}

      if ($i==0) 
        {
           $s=0;
           $script_data2 = $script_data2.$s." ".($z+$hnode1)."\n";
        }
      else 
        {
           $s=$dist * ($i/$steps) * 0.03;
        }
      $script_data = $script_data.$s." ".$z."\n";
      }
    $script_data2 = $script_data2.$s." ".($z+$hnode2)."\n";  
    }
    //------------------------3
  
  If ( ($g2<$g1) & ($h2>$h1) )
    {
    $a = $g1 - $g2;
    $b = $h2 - $h1;
    $m = $b / $a;
    $dist = sqrt(pow($a,2)+pow($b,2));
    $steps = round($dist*$res);
    $step_inc = abs($g1-$g2)/$steps;
    
    for ($i=0;$i<=$steps;$i++) 
      {
      $x = $i*$step_inc;
      $y = $m * $x;
      $x = $g1 - $x;
      $y = $h1 + $y;
      $z = interpole_height($x,$y);
      if ($z>$maxheight) {$maxheight = $z;}
      if ($z<$minheight) {$minheight = $z;}

      if ($i==0) 
        {
           $s=0;
           $script_data2 = $script_data2.$s." ".($z+$hnode1)."\n";
        }
      else 
        {
           $s=$dist * ($i/$steps) * 0.03;
        }
      $script_data = $script_data.$s." ".$z."\n";
      }
    $script_data2 = $script_data2.$s." ".($z+$hnode2)."\n";  
    }
    //------------------------4
  
  If ( ($g2>$g1) & ($h2>$h1) )
    {
    $a = $g2 - $g1;
    $b = $h2 - $h1;
    $m = $b / $a;
    $dist = sqrt(pow($a,2)+pow($b,2));
    $steps = round($dist*$res);
    $step_inc = abs($g1-$g2)/$steps;
    
    for ($i=0;$i<=$steps;$i++) 
      {
      $x = $i*$step_inc;
      $y = $m * $x;
      $x = $g1 + $x;
      $y = $h1 + $y;
      $z = interpole_height($x,$y);
      if ($z>$maxheight) {$maxheight = $z;}
      if ($z<$minheight) {$minheight = $z;}

      if ($i==0) 
        {
           $s=0;
           $script_data2 = $script_data2.$s." ".($z+$hnode1)."\n";
        }
      else 
        {
           $s=$dist * ($i/$steps) * 0.03;
        }
      $script_data = $script_data.$s." ".$z."\n";
      }
    $script_data2 = $script_data2.$s." ".($z+$hnode2)."\n";  
  }

  $totalheight= $maxheight - $minheight;
  $ytics = set_tics($totalheight);
  $xtics = set_tics($dist*0.3)/10;
  
  // capçalera de l'script pel GNUPLot
  
        $script_header =  $script_header."set terminal png size ".$width." ".$height."\n";       // imatge en format PNG
  $script_header =  $script_header."set output\n";    // Sortida per STDOUT  
  // $script_header =  $script_header."set mxtics 40\n"; // interval de les sub ralletes [eix x]
  // $script_header =  $script_header."set mytics 40\n"; // interval de les sub ralletes [eix y]  
  // $script_header =  $script_header."set tics out\n";  // subralletes capa fora del grafic    
  if ($height >= 320) {
    $script_header =  $script_header."set title '".$node1." - ".$node2."'\n";                    // Títol del Gràfic
    $script_header =  $script_header."set ylabel \"Altitud (m)\"\n";  // Etiqueta Eix Y
    $script_header =  $script_header."set grid\n";   // Mostrar graella de fons
 } else 
   $script_header =  $script_header."set noytics\n"; // subralletes capa fora del grafic    
         
  if ($width >= 640)
    $script_header =  $script_header."set xlabel \"Recorregut (Km)\"\n";                        // Etiqueta Eix X
  else
   $script_header =  $script_header."set noxtics\n"; // subralletes capa fora del grafic    
          
   
  //if ($height >= 320)
  //$script_header =  $script_header."set xtics ".$xtics."\n"; // Interval de la llegenda [Eix x]
  //$script_header =  $script_header."set ytics ".$ytics."\n"; // Interval de la llegenda [Eix y]  
  //$script_header =  $script_header."plot '-' notitle with filledcurves 6 , \\\n";// Grafica del perfil color 1 [vermell] color 6 [marro]
  //$script_header =  $script_header."set style fill solid 1.0 \n";  
  $script_header =  $script_header."plot '-' notitle with lines 6,\\\n";// Grafica del perfil color 1 [vermell] color 6 [marro]
  $script_header =  $script_header."     '-' notitle with lines 3\n";  // Gràfica del LOV color 3 [blau]    
  
  
  // Ajuntar parts de l'script    
    
  $script = $script_header.$script_data."e\n".$script_data2."e\n";                            
  
  // Guardem l'script a fitxer
        $fname = '/tmp/heights.'.getmypid();  
  $heights = fopen($fname,'w');
  fwrite($heights,$script);
  fclose($heights);    
    
  //  l'executem, agafant el resultat i passant-lo per la sortida com a imatge png  
    
  header("Content-type: image/png");                                                            
  passthru("gnuplot ".$fname);
  }
  
?>
