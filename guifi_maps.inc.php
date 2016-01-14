<?php
/**
 * @file guifi_maps.inc.php
 * Modul de mapes per a en Carles (exemple)
 */

/*
 * Return de array of datum requerest
 */
function guifi_get_datum($datum) {
  $datums = array(
                    array( 'GRS 80',              6378137.000000, 6356752.314100 ),
                    array( 'WGS 72',              6378135.000000, 6356750.500000 ),
                    array( 'Australian 1965',     6378160.000000, 6356774.700000 ),
                    array( 'Krasovsky 1940',      6378245.000000, 6356863.000000 ),
                    array( 'North American 1927', 6378206.400000, 6356583.800000 ),
                    array( 'International 1924',  6378388.000000, 6356911.900000 ),
                    array( 'Hayford 1909',        6378388.000000, 6356911.900000 ),
                    array( 'Clarke 1880',         6378249.100000, 6356514.900000 ),
                    array( 'Clarke 1866',         6378206.400000, 6356583.800000 ),
                    array( 'Airy 1830',           6377563.400000, 6356256.900000 ),
                    array( 'Bessel 1841',         6377397.200000, 6356079.000000 ),
                    array( 'Everest 1830',        6377276.300000, 6356075.400000 ));

  return $datums[$datum];
}

/*
 * Return the distance between 2 positions in UTM coordinates
 *
function guifi_get_dist_UTM($x1, $y1, $x2, $y2) {
  return sqrt( ($x2-$x1)*($x2-$x1) + ($y2-$y1)*($y2-$y1) );
}

/*
 * Convert coordinates from pixels to UTM.
 *
function guifi_pixel2UTM ( $coords, $x, $y ) {
  return guifi_units2units ( $coords, $x, $y, 1 );
}

/*
 * Convert coordinates from UTM to pixels.
 *
function guifi_UTM2pixel ( $coords, $x, $y ) {
  return guifi_units2units ( $coords, $x, $y, 0 );
}

/*
 * Convert coordinates from Longitude, Latitude and Elevation to XYZ.
 */
function guifi_lonlat2XYZ ( $datum, $lon, $lat, $h = 0 ) {
  $lon = deg2rad($lon);
  $lat = deg2rad($lat);

  $dat = guifi_get_datum($datum);
  $a = $dat[1];
  $b = $dat[2];
  $N = ( $a*$a ) / sqrt($a*$a * cos($lat)*cos($lat) + $b*$b * sin($lat)*sin($lat) );

  $X = ($N + $h) * cos($lat)*cos($lon);
  $Y = ($N + $h) * cos($lat)*sin($lon);
  $Z = ( ($b*$b/($a*$a)) * $N + $h ) * sin($lat);

  return array($X, $Y, $Z);
}

/*
 * Convert coordinates from XYZ to Longitude, Latitude and Elevation.
 */
function guifi_XYZ2lonlat ( $datum, $X, $Y, $Z ) {
  $dat = guifi_get_datum($datum);
  $a = $dat[1];
  $b = $dat[2];
  $e2 = ( $a*$a - $b*$b ) / ($a*$a);
  $e2_ = ( $a*$a - $b*$b ) / ($b*$b);
  $p = sqrt( $X*$X + $Y*$Y );
  $phi = atan(($Z * $a) / ($p * $b));

  $lat = atan( ($Z + $e2_ * $b * sin($phi)*sin($phi)*sin($phi) ) / ($p - $e2 * $a * cos($phi)*cos($phi)*cos($phi) ) );
  $lon = atan( $Y / $X );
  $N = ( $a*$a ) / ( sqrt($a*$a * cos($lat)*cos($lat) + $b*$b * sin($lat)*sin($lat) ) );
  $h = ( $p / cos($lat) ) - $N ;

  $lon = rad2deg($lon);
  $lat = rad2deg($lat);

  return array($lon, $lat, $h);
}

/*
 * Type 1: Convert coordinates from ND50 to WG84
 * Type 0: Convert coordinates from WG84 to ND50
 */
function guifi_ND50_WG84 ( $Xs, $Ys, $Zs, $type=0 ) {
/*  $Tx = 181.5; $Ty = 90.3; $Tz = 187.2;
  $Rx = deg2rad(0.144/3600); $Ry = deg2rad(0.492/3600); $Rz = deg2rad(-0.394/3600);
  $D = 17.57 / 1000000.0 ;
  $Tx = 131.0; $Ty = 100.3; $Tz = 163.4;
  $Rx = deg2rad(-1.244/3600); $Ry = deg2rad(-0.020/3600); $Rz = deg2rad(-1.144/3600);
  $D = 9.39 / 1000000.0 ;
*/  $Tx = 131.03; $Ty = 100.25; $Tz = 163.35;
  $Rx = deg2rad(-1.244/3600); $Ry = deg2rad(-0.019/3600); $Rz = deg2rad(-1.144/3600);
  $D = 9.39 / 1000000.0 ;

  if ($type) {
    $Tx = -$Tx; $Ty = -$Ty; $Tz = -$Tz;
    $Rx = -$Rx; $Ry = -$Ry; $Rz = -$Rz;
    $D = -$D;
  }

  $D = $D + 1.0 ;
  $Xs = $D * $Xs;
  $Ys = $D * $Ys;
  $Zs = $D * $Zs;

  $Xt = $Tx + ( $Xs + $Rz*$Ys - $Ry*$Zs );
  $Yt = $Ty + ( -$Rz*$Xs + $Ys + $Rx*$Zs );
  $Zt = $Tz + ( $Ry*$Xs - $Rx*$Ys + $Zs );

  return array( $Xt, $Yt, $Zt );
}

/*
 * Transforma un texte en unes coordenades
 *
 */
function text2Coord ( $text ) {
		$signe = ($text[0]=='-') ? -1 : 1;
		$valors = preg_split("/[º|'|'']/", $text, 4, PREG_SPLIT_NO_EMPTY);
		$coord = abs($valors[0]) + abs($valors[1])/60 + abs($valors[2])/3600 * $signe;

		return $coord;
}

/*
 * Transforma una coordenades en texte.
 *
 */
function coord2Text ( $coord ) {
		$signe = ($coord == abs($coord)) ? "" : "-" ;
		$coord = abs($coord);

		$text  = $signe;
		$graus = $coord;
		$minuts = ($graus - floor($graus)) * 60;
		$segons = ($minuts - floor($minuts)) * 60;

		$text .= floor($graus) . "º" . floor($minuts) . "'" . floor($segons*10000)/10000 . "''";

		return $text;
}

/*
 * Convert coordinates from Longitude and Latitude WG84 to UTM with datum.
 */
function guifi_WG842UTM ( $lon, $lat, $datum, $zone, $nord ) {
  $lonLat_ED50 = guifi_WG842ED50( $lon, $lat, $datum);
  return guifi_Lonlat2UTM( $lonLat_ED50[0], $lonLat_ED50[1], $datum, $zone, $nord);
}

/*
 * Convert coordinates from Longitude and Latitude WG84 to LonLat with datum.
 */
function guifi_WG842ED50 ( $lon, $lat, $datum ) {
    // Transformar WG84 to ED50
    $XYZ_WG84 = guifi_lonlat2XYZ(0, $lon, $lat, 0);
    $XYZ_ED50 = guifi_ND50_WG84( $XYZ_WG84[0], $XYZ_WG84[1], $XYZ_WG84[2], 0);
    $lonLat_ED50 = guifi_XYZ2lonlat($datum, $XYZ_ED50[0], $XYZ_ED50[1], $XYZ_ED50[2]);

    return $lonLat_ED50;
}

/*
 * Convert coordinates from Longitude and Latitude to UTM.
 */
function guifi_Lonlat2UTM ( $lon, $lat, $datum, $zone, $nord=TRUE ) {
    // Tranformar a radians
    $lat = $lat * M_PI / 180;

    // Constants del datum
    $dat = guifi_get_datum($datum);
    $a = $dat[1];
    $b = $dat[2];
    $f = ( $a - $b ) / $a;
    $_f = 1 / $f;
    $rm = pow( $a * $b, 0.5 );

    $k0 = 0.9996;
    $e = sqrt( 1 - pow($b/$a, 2) );
    $e2 = $e * $e / ( 1 - $e * $e );
    $n = ( $a - $b ) / ( $a + $b );
    $rho = $a * ( 1 - $e*$e ) / pow( 1 - pow( $e * sin( $lat ), 2 ), 1.5 );
    $nu = $a / pow( 1 -  pow ( $e * sin( $lat ), 2 ), 0.5 );

    // Calcul Longitud Arc Meridional
    $A0 = $a * ( 1 - $n + ( 5*$n*$n / 4 ) * ( 1 - $n ) + ( 81 * pow($n, 4) / 64 ) * ( 1 - $n ) );
    $B0 = ( 3 * $a * $n / 2 ) * ( 1 - $n - ( 7*$n*$n/8 ) * ( 1 - $n ) + 55 * pow($n, 4) / 64 );
    $C0 = ( 15 * $a * $n * $n / 16 ) * ( 1 - $n + ( 3 * $n * $n / 4 ) * ( 1 - $n ) );
    $D0 = ( 35 * $a * pow($n, 3) / 48 ) * ( 1 - $n + 11*$n*$n/16 );
    $E0 = ( 315 * $a * pow($n, 4) / 51 ) * ( 1 - $n );
    $S = $A0*$lat - $B0*sin( 2*$lat ) + $C0*sin( 4*$lat ) - $D0*sin( 6*$lat ) + $E0*sin( 8*$lat );

    // Càlcul de constants
//    if ( $lon < 0 )    $zone = floor( ( 180 + $lon ) / 6 ) + 1;
//    else        $zone = floor( $lon  / 6 ) + 31;
    $zoneCM = 6 * $zone - 183;
    $deltaLong = $lon - $zoneCM;
    $pSec = $deltaLong * 3600 / 10000;
    $sin1 = M_PI / ( 180 * 3600 );

    // Coeficients per Coordenades UTM
    $Ki = $S * $k0;
    $Kii = $nu * sin($lat) * cos($lat) * pow($sin1, 2) * $k0 * 100000000 / 2;
    $Kiii = ( ( pow($sin1, 4) * $nu * sin($lat) * pow(cos($lat), 3) ) / 24 ) *
          ( 5 - pow(tan($lat), 2) + 9 * $e2 * pow(cos($lat), 2) + 4 * $e2*$e2 * pow(cos($lat), 4) ) * $k0 * 1e16;
    $Kiv = $nu * cos($lat) * $sin1 * $k0 * 10000;
    $Kv = pow( $sin1 * cos($lat) , 3) * ( $nu / 6 ) *
      ( 1 - pow(tan($lat), 2) + $e2 * pow(cos($lat), 2) ) * $k0 * 1000000000000;
    $A6 = ( pow($pSec*$sin1, 6) * $nu * sin($lat) * pow(cos($lat),5) / 720 ) *
      ( 61 - 58 * pow(tan($lat),2) + pow(tan($lat),4) + 270 * $e2 * pow(cos(lat),2) - 330 * $e2 * pow(sin($lat),2) ) * $k0 * 1e24;

    $nordUTM = $Ki + $Kii * $pSec * $pSec + $Kiii * pow($pSec,4);
    $eastUTM = 500000 + ( $Kiv * $pSec + $Kv * pow($pSec, 3) );

    return array( $eastUTM, $nordUTM );
}

/*
 * Convert coordinates from UTM to Longitude and Latitude WG84.
 */
function guifi_UTM2WG84 ( $eastUTM, $nordUTM, $datum, $zone, $nord=TRUE ) {
  $lonLat_ED50 = guifi_UTM2LonLat( $eastUTM, $nordUTM, $datum, $zone, $nord );
  return guifi_ED502WG84( $lonLat_ED50[0], $lonLat_ED50[1], $datum );
}

/*
 * Convert coordinates from UTM to Longitude and Latitude WG84.
 */
function guifi_ED502WG84 ( $lon, $lat, $datum ) {
  // Transformar WG84 to ED50
  $XYZ_ED50 = guifi_lonlat2XYZ($datum, $lon, $lat, 0);
  $XYZ_WG84 = guifi_ND50_WG84( $XYZ_ED50[0], $XYZ_ED50[1], $XYZ_ED50[2], 1);
  $lonLat_WG84 = guifi_XYZ2lonlat(0, $XYZ_WG84[0], $XYZ_WG84[1], $XYZ_WG84[2]);

  return $lonLat_WG84;
}

/*
 * Convert coordinates from UTM to Longitude and Latitude WG84.
 */
function guifi_UTM2LonLat ( $eastUTM, $nordUTM, $datum, $zone, $nord=TRUE ) {
    // Zona
    if ( $zone > 0 )  $zoneCM = 6 * $zone - 183;
    else        $zoneCM = 3;
    $signe = ( $zone < 31 ) ? -1 : 1 ;
    if ( !$nord ) $nordUTM = 10000000 - $nordUTM;

    // Constants del datum
    $dat = guifi_get_datum($datum);
    $a = $dat[1];
    $b = $dat[2];
    $e = sqrt( 1 - (($b/$a) * ($b/$a)) );
    $e2 = ( $e * $e ) / ( 1 - $e * $e );
    $k0 = 0.9996;

    // Calcular peu de projecció de la latitud
    $arc = $nordUTM / $k0;
    $mu = $arc / ( $a * ( 1 - pow($e, 2) / 4 - 3 * pow($e, 4) / 64 - 5 * pow($e, 6) / 256 ) );
    $e1 = ( 1 - pow(1 - $e*$e, 0.5) ) / ( 1 + pow(1 - $e*$e, 0.5) );
    $C1 = 3 * $e1 / 2 - 27 * pow($e1, 3) / 32;
    $C2 = 21 * pow($e1, 2) / 16 - 5 * pow($e1, 4) / 32;
    $C3 = 151 * pow($e1, 3) / 96;
    $C4 = 1097 * pow($e1, 4) / 512;
    $fp = $mu + $C1 * sin(2*$mu) + $C2 * sin(4*$mu) + $C3 * sin(6*$mu) + $C4 * sin(8*$mu);

    // Formules de constants
    $C = $e2 * pow(cos($fp), 2);
    $T1 = pow(tan($fp), 2);
    $N1 = $a / pow( 1 - pow($e * sin($fp), 2), 0.5 );
    $R1 = $a * ( 1 - $e*$e ) / pow( 1 - pow($e * sin($fp), 2) , 1.5);
    $D = ( 500000 - $eastUTM ) / ($N1 * $k0);

    // Coeficients per calcular la latitud
    $F1 = $N1 * tan($fp) / $R1;
    $F2 = $D*$D / 2;
    $F3 = ( 5 + 3 * $T1 + 10 * $C*$C - 9 * $e2 ) * pow($D, 4) / 24;
    $F4 = ( 61 + 90 * $T1 + 298 * $C + 45 * $T1*$T1 - 252 * $e2 - 3 * $C*$C ) * pow($D, 6) / 720;

    // Coeficients per calcular la longitud
    $J1 = $D;
    $J2 = ( 1 + 2 * $T1 + $C ) * pow($D, 3) / 6;
    $J3 = ( 5 - 2 * $C + 28 * $T1 - 3 * $C*$C + 8 * $e2 + 24 * $T1*$T1 ) * pow($D, 5) / 120;

    // Calculs finals
    $deltaLong = ( $J1 - $J2 + $J3 ) / cos($fp);
    $latitud = 180 * ( $fp - $F1 * ( $F2 + $F3 + $F4 ) ) / M_PI;
    $longitud = $zoneCM - ( $deltaLong * 180 / M_PI );

    return array( $longitud, $latitud );
}

/*
 * Convert coordinates from pixels to UTM and viceversa.
 */
function guifi_units2units ( $coords, $lon, $lat, $type=0 ) {
    // Trobem els tres punts més propers i vàlids per fer la triangulació.
    $p = guifi_trobaPuntsMesPropers( $coords, $lon, $lat, $type );

    // Si el punt equival a un dels tres se salta el càlcul.
    for ( $i = 0 ; $i < 3 ; $i++)
      if ( $p[$i][2] == $lon && $p[$i][3] == $lat )  return array( $p[$i][0], $p[$i][1] );

    // Calculem els angles en píxels dels tres punts respecte el més proper
    for ( $i = 0 ; $i < 3 ; $i++)
      $angleC[($i+2)%3] = atan2( ($p[($i+1)%3][3] - $p[$i][3]) , ($p[($i+1)%3][2] - $p[$i][2]) );

    // Calcular l'angle en píxels del punt a trobar.
    $angleCX = atan2( ($lat - $p[0][3]) , ($lon - $p[0][2]) );

    // Trobar el punt origen on la recta passi pel mig del triangle
    for ( $i=0 ; $i<3 ; $i++)
      $sinCX[$i] = abs(sin( $angleCX - $angleC[$i] ));    // Angle més proper
    for ( $i=0 ; $i<3 ; $i++)
      if ( $sinCX[$i] < $sinCX[($i+2)%3] && $sinCX[$i] < $sinCX[($i+1)%3] )
        $puntRef = $i;

    for ( $i=0 ; $i<2 ; $i++) {    // Mirar quin dels altres dos punts ha de ser l'origen
      $distPRX[$i] = $p[$puntRef][2] - $p[($puntRef+1+$i)%3][2];
      $distPRY[$i] = $p[$puntRef][3] - $p[($puntRef+1+$i)%3][3];
      $distPR[$i] = sqrt( $distPRX[$i]*$distPRX[$i] + $distPRY[$i]*$distPRY[$i] );
      $sinPR[$i] = $distPR[$i] * abs( sin( $angleC[($puntRef+2-$i)%3] - $angleCX ) );
    }

    if ( $sinPR[0] < $sinPR[1] )  $origen = ($puntRef+1)%3;
    else              $origen = ($puntRef+2)%3;


    // Trobar la intersecció de la recta dels punts que no pasen per l'orígen
    // amb la recta paral·lela en el mateix sentit la projecció del punt que passa per l'orígen
    $x0 = $p[($origen+1)%3][2];    // Recta delimitada pels altres 2 punts diferents de l'orígen
    $y0 = $p[($origen+1)%3][3];
    $v0 = $p[($origen+2)%3][2] - $p[($origen+1)%3][2];
    $w0 = $p[($origen+2)%3][3] - $p[($origen+1)%3][3];

    $x1 = $p[$origen][2];      // Recta des de l'orígen en direcció al punt
    $y1 = $p[$origen][3];
    $v1 = $lon - $p[0][2];
    $w1 = $lat - $p[0][3];

    $lambda = ( ($y0-$y1)*$v1 - ($x0-$x1)*$w1 ) / ( $v0*$w1 - $w0*$v1 );  // El valor proporcional de la recta

    $xC = $x0 + $lambda * $v0;    $yC = $y0 + $lambda * $w0;  // El punt intersecció

    // Trobem el punt equivalent en pixels. $lamba és la proporció.
    $xP = $p[($origen+1)%3][0] + $lambda * ( $p[($origen+2)%3][0] - $p[($origen+1)%3][0] );
    $yP = $p[($origen+1)%3][1] + $lambda * ( $p[($origen+2)%3][1] - $p[($origen+1)%3][1] );

    // Trobem l'angle i la distància equivalent en les unitats de destí
    $distC0X = sqrt( ($lon - $p[0][2])*($lon - $p[0][2]) + ($lat - $p[0][3])*($lat - $p[0][3]));
    $distCY = $yC-$p[$origen][3];  $distCX = $xC-$p[$origen][2];
    $distPY = $yP-$p[$origen][1];  $distPX = $xP-$p[$origen][0];
    $distCO = sqrt( $distCX*$distCX + $distCY*$distCY);
    $distPO = sqrt( $distPX*$distPX + $distPY*$distPY);

    $anglePX = atan2( $distPY , $distPX );
    if ( cos($angleCX - atan2( $distCY , $distCX )) < 0.98 ) $anglePX += M_PI;
    $distPX = $distC0X * ($distPO / $distCO);

    // Calculem el punt buscat en píxels.
    $xReal = ( cos($anglePX) * $distPX ) + $p[0][0];
    $yReal = ( sin($anglePX) * $distPX ) + $p[0][1];

    /*
    $longitud  = "angleC(2): ".$angleC[2]*180/M_PI."<br />";
    $longitud .= "angleC(1): ".$angleC[1]*180/M_PI."<br />";
    $longitud .= "angleC(0): ".$angleC[0]*180/M_PI."<br />";
    $longitud .= "angleCX: ".$angleCX*180/M_PI."<br />";
    $longitud .= "sinCX: ".$sinCX[0].",".$sinCX[1].",".$sinCX[2]."<br />";
    $longitud .= "sinPR: ".$sinPR[0].",".$sinPR[1]."<br />";
    $longitud .= "puntRef: ".$puntRef."<br />";
    $longitud .= "origen: ".$origen."<br />";
    $longitud .= "R(x): (".$x0.",".$y0."|".$v0.",".$w0.")<br />";
    $longitud .= "R(0): (".$x1.",".$y1."|".$v1.",".$w1.")<br />";
    $longitud .= "P(x): (".$xP.",".$yP."|".$xC.",".$yC.")<br />";
    $longitud .= "lambda: ".$lambda."<br />";
    $longitud .= "anglePX: ".$anglePX*180/M_PI."<br />";
    $longitud .= "proporció: ".($distPO / $distCO)."<br />";

    $latitud = "";
    $result  = "(".$xReal.", ".$yReal.")<br />";

    global $wgOut;
    $wgOut->addHTML($longitud . "<br />" . $latitud . "<br />" . $result);
    */

    return array( $xReal, $yReal );
}

function guifi_trobaPuntsMesPropers ( $coords, $lon, $lat, $type=0 ) {
		$coordenades = $coords;

		// Decidim si buquem punts propers per pixels o per coordenades
		$x = 2; $y = 3;
		if ( $type ) {
			$x = 0; $y = 1;
		}

		// Calculem la distància de cada punt
		foreach ( $coordenades as $coord ) {
			$distX = $coord[$x] - $lon;
			$distY = $coord[$y] - $lat;
			$coord[4] = sqrt( $distX * $distX + $distY * $distY );
		}

		// Ordenem tots els punts per la seva distància
		usort( $coordenades, guifi_compararDistanciaPunts );

		// Busca els 3 punts vàlids més propers
    $punt = array( $lon, $lat );
    $nearest = array();
		for ( $i = 2 ; $i < count( $coordenades ) ; $i++ ) {
			for ( $j = 1 ; $j < $i ; $j++ ) {
				if ( guifi_validate3Points( $coordenades[0], $coordenades[$i], $coordenades[$j] ) ) {
					$punts[0] = array( $coordenades[0][($x+2)%4], $coordenades[0][($y+2)%4], $coordenades[0][$x], $coordenades[0][$y]);
					$punts[1] = array( $coordenades[$j][($x+2)%4], $coordenades[$j][($y+2)%4], $coordenades[$j][$x], $coordenades[$j][$y]);
					$punts[2] = array( $coordenades[$i][($x+2)%4], $coordenades[$i][($y+2)%4], $coordenades[$i][$x], $coordenades[$i][$y]);

          if ( ! count( $nearest ) )   $nearest = $punts;
          if ( guifi_isPointInsideTriangle( $punt, $coordenades[0], $coordenades[$i], $coordenades[$j], $type ) )
            return $punts;
				}
			}
		}

    if ( count( $nearest ) )   return $nearest;

		$punts[0] = array( $coordenades[0][($x+2)%4], $coordenades[0][($y+2)%4], $coordenades[0][$x], $coordenades[0][$y]);
		$punts[1] = array( $coordenades[1][($x+2)%4], $coordenades[1][($y+2)%4], $coordenades[1][$x], $coordenades[1][$y]);
		$punts[2] = array( $coordenades[2][($x+2)%4], $coordenades[2][($y+2)%4], $coordenades[2][$x], $coordenades[2][$y]);

		return $punts;
}

function guifi_compararDistanciaPunts ( $p1, $p2 ) {
		return $p1[4] < $p2[4] ? -1 : ( $p1[4] == $p2[4] ? 0 : 1 );
}

/**
 * Comprova que un punt està dins del triangle definit per tres punts.
 *
 *
function guifi_isPointInsideTriangle ( $p, $t1, $t2, $t3, $type = 0 ) {
  // Decidim si buquem punts propers per pixels o per coordenades
  $x = 2; $y = 3;
  if ( $type ) {
    $x = 0; $y = 1;
  }

  // Calculem les normals dels triangles formats
  // pel punt a comprovar i dos punts del triangle.
  $normal1 = ( ( $t2[$x] - $p[0] ) * ( $t3[$y] - $p[1] ) ) -
             ( ( $t2[$y] - $p[1] ) * ( $t3[$x] - $p[0] ) );
  $normal2 = ( ( $t3[$x] - $p[0] ) * ( $t1[$y] - $p[1] ) ) -
             ( ( $t3[$y] - $p[1] ) * ( $t1[$x] - $p[0] ) );
  $normal3 = ( ( $t1[$x] - $p[0] ) * ( $t2[$y] - $p[1] ) ) -
             ( ( $t1[$y] - $p[1] ) * ( $t2[$x] - $p[0] ) );

  if ( ( $normal1 >= 0 && $normal2 >= 0 && $normal3 >= 0 ) ||
       ( $normal1 <= 0 && $normal2 <= 0 && $normal3 <= 0 ) )
    return TRUE;

  return FALSE;
}


/**
 * Comprova que 3 punts són compatibles per calcular una coordenada
 * Es comprova que no contenen valors iguals
 *
 */
function guifi_validate3Points ( $p1, $p2, $p3 ) {
  /*		No cal, amb la distància mínima ja n'hi ha suficient.
		// Valors iguals tant en pixels com en coordenades
		if ( $p1[0] == $p2[0] && $p1[1] == $p2[1] )	return FALSE;
		if ( $p1[0] == $p3[0] && $p1[1] == $p3[1] )	return FALSE;
		if ( $p2[0] == $p3[0] && $p2[1] == $p3[1] )	return FALSE;

		if ( $p1[2] == $p2[2] && $p1[3] == $p2[3] )	return FALSE;
		if ( $p1[2] == $p3[2] && $p1[3] == $p3[3] )	return FALSE;
		if ( $p2[2] == $p3[2] && $p2[3] == $p3[3] )	return FALSE;
  */
		// Comprovem les distàncies màximes
		$distP12 = sqrt( (($p2[0] - $p1[0]) * ($p2[0] - $p1[0])) + (($p2[1] - $p1[1]) * ($p2[1] - $p1[1])) );
		$distP13 = sqrt( (($p3[0] - $p1[0]) * ($p3[0] - $p1[0])) + (($p3[1] - $p1[1]) * ($p3[1] - $p1[1])) );
		$distP23 = sqrt( (($p3[0] - $p2[0]) * ($p3[0] - $p2[0])) + (($p3[1] - $p2[1]) * ($p3[1] - $p2[1])) );
		$limitPixels = 100;		// Distància mínima per poder interpolar
		if ( $distP12 < $limitPixels || $distP13 < $limitPixels || $distP23 < $limitPixels) return FALSE;
		$distC12 = sqrt( (($p2[2] - $p1[2]) * ($p2[2] - $p1[2])) + (($p2[3] - $p1[3]) * ($p2[3] - $p1[3])) );
		$distC13 = sqrt( (($p3[2] - $p1[2]) * ($p3[2] - $p1[2])) + (($p3[3] - $p1[3]) * ($p3[3] - $p1[3])) );
		$distC23 = sqrt( (($p3[2] - $p2[2]) * ($p3[2] - $p2[2])) + (($p3[3] - $p2[3]) * ($p3[3] - $p2[3])) );
		$limitCoord = 0.001;	// Distància mínima per poder interpolar
		if ( $distC12 < $limitCoord || $distC13 < $limitCoord || $distC23 < $limitCoord) return FALSE;

		// Comprovem els angles en píxels i coordenades
		$angleP12 = atan2( $p2[1] - $p1[1], $p2[0] - $p1[0]);
		$angleP13 = atan2( $p3[1] - $p1[1], $p3[0] - $p1[0]);
		$angleP23 = atan2( $p3[1] - $p2[1], $p3[0] - $p2[0]);
		$limit = sin(deg2rad(10.0));	// 10º d'angle límit per donar per vàlid algun punt
		if ( abs( sin( $angleP13 - $angleP12 )) < $limit )	return FALSE;

		$angleC12 = atan2( $p2[3] - $p1[3], $p2[2] - $p1[2]);
		$angleC13 = atan2( $p3[3] - $p1[3], $p3[2] - $p1[2]);
		$angleC23 = atan2( $p3[3] - $p2[3], $p3[2] - $p2[2]);
		if ( abs( sin( $angleC13 - $angleC12 )) < $limit )	return FALSE;

		return TRUE;
}



/*
 * Return the real scale of relation
 */
function guifi_get_map_info($node) {
  $map_info = array();

  // Obtenim les variables de retall del mapa.
  //  - (X,Y) ó (UTMx, UTMy) ó (Lon,Lat)
  $map_info['datum'] = 5;
  $map_info['zone'] = 31;
  $map_info['nord'] = 1;
  $map_info['xPixel'] = isset($_GET["xPixel"]) ? $_GET["xPixel"] : $_POST["xPixel"];
  $map_info['yPixel'] = isset($_GET["yPixel"]) ? $_GET["yPixel"] : $_POST["yPixel"];
  $map_info['xUTM'] = isset($_GET["xUTM"]) ? $_GET["xUTM"] : $_POST["xUTM"];
  $map_info['yUTM'] = isset($_GET["yUTM"]) ? $_GET["yUTM"] : $_POST["yUTM"];
  $map_info['londec'] = isset($_GET["londec"]) ? $_GET["londec"] : $_POST["londec"];
  $map_info['latdec'] = isset($_GET["latdec"]) ? $_GET["latdec"] : $_POST["latdec"];
  $map_info['lon'] = isset($_GET["lon"]) ? $_GET["lon"] : $_POST["lon"];
  $map_info['lat'] = isset($_GET["lat"]) ? $_GET["lat"] : $_POST["lat"];
  //  - Zoom ó Dist
  $map_info['zoom'] = isset($_GET["zoom"]) ? $_GET["zoom"] : $_POST["zoom"];
  $map_info['dist'] = isset($_GET["dist"]) ? $_GET["dist"] : $_POST["dist"];
  if ( !isset($map_info['xPixel']) || !isset($map_info['yPixel'])) {
    $map_info['xPixel'] = 0;
    $map_info['yPixel'] = 0;
  }
  if ( !isset($map_info['zoom']) )
    $map_info['zoom'] = 0;


  // Comprovem les dades i les transformem en X, Y, S
  $map_info['width'] = $map_info['height'] = 500;    // This variables will are in drupal
  $map_info['quadrants'] = 3;    // This variables will are in drupal
  $image_info = guifi_get_image_info ( guifi_get_file_map_zone($node) );

  // Find max scale of de map to draw from zoom of this view
  $map_info['maxRel'] = $image_info['width'] / $map_info['width'];
  if ( $map_info['maxRel'] < $image_info['height'] / $map_info['height'] )
    $map_info['maxRel'] = $image_info['height'] / $map_info['height'];
  $map_info['maxScale'] = guifi_get_scale_relation ( $map_info['maxRel'] );
  if ( $node->valid ) {
    // Calcule dist of original map and his relation
    $point_zero = guifi_pixel2UTM ($node->coord, 0, 0);
    $point_right = guifi_pixel2UTM ($node->coord, $image_info['width']-1, 0);
    $point_bottom = guifi_pixel2UTM ($node->coord, 0, $image_info['height']-1);

    $map_info['distX'] = guifi_get_dist_UTM($point_zero[0], $point_zero[1], $point_right[0], $point_right[1]);
    $map_info['distY'] = guifi_get_dist_UTM($point_zero[0], $point_zero[1], $point_bottom[0], $point_bottom[1]);
  }

  // Find de scale to draw the map
  if ( $node->valid && isset($map_info['dist']) && is_numeric($map_info['dist']) ) {
    // Find scale of de map to draw from dist of this view
    $map_info['maxRel'] = ( $image_info['width'] * $map_info['dist'] ) / ( $map_info['distX'] * $map_info['width'] );
    if ( $map_info['maxRel'] < ( $image_info['height'] * $map_info['dist'] ) / ( $map_info['distY'] * $map_info['height'] ) )
      $map_info['maxRel'] = ( $image_info['height'] * $map_info['dist'] ) / ( $map_info['distY'] * $map_info['height'] );

    $map_info['scale'] = guifi_get_scale_relation ( $map_info['maxRel'] );
    if ( $map_info['scale'] > $map_info['maxScale'] )
      $map_info['scale'] = $map_info['maxScale'];
    $map_info['zoom'] = $map_info['maxScale'] - $map_info['scale'];
  }
  if ( isset($map_info['zoom']) ) {
    // Recalcule de zoom to a correct zoom
    if ( $map_info['zoom'] > $map_info['maxScale'] )
      $map_info['zoom'] = $map_info['maxScale'];
    $map_info['scale'] = $map_info['maxScale'] - $map_info['zoom'];
  }

  if ( $node->valid ) {
    if ( isset($map_info['lon']) && isset($map_info['lat']) ) {
      // Transform Geo's corrdinates to UTM Coordinates
      $map_info['londec'] = text2Coord($map_info['lon']);
      $map_info['latdec'] = text2Coord($map_info['lat']);
    }
    if ( isset($map_info['londec']) && isset($map_info['latdec']) ) {
      // Transform Geo's corrdinates to UTM Coordinates
      $point = guifi_WG842UTM ($map_info['londec'], $map_info['latdec'], $map_info['datum'], $map_info['zone'], $map_info['nord']);
      $map_info['xUTM'] = $point[0];
      $map_info['yUTM'] = $point[1];
    }
    if ( isset($map_info['xUTM']) && isset($map_info['yUTM']) ) {
      // Transform UTM's corrdinates to Pixel Coordinates
      $point = guifi_UTM2pixel ($node->coord, $map_info['xUTM'], $map_info['yUTM']);
      $map_info['xPixel'] = $point[0];
      $map_info['yPixel'] = $point[1];
    }
  }

  // Find quadrants of de map scaled.
  $map_info['width_quadrant'] = $map_info['width'] / $map_info['quadrants'] ;
  $map_info['height_quadrant'] = $map_info['height'] / $map_info['quadrants'] ;
  $quadrantsX = ceil (( $image_info['width'] / guifi_get_rel_scale( $map_info['scale'] ) ) / $map_info['width_quadrant'] );
  $quadrantsY = ceil (( $image_info['height'] / guifi_get_rel_scale( $map_info['scale'] ) ) / $map_info['height_quadrant'] );

  // Find de quadrant center of de map scaled from pixel x,y in center.
  $quadCentX = floor (( $map_info['xPixel'] / guifi_get_rel_scale( $map_info['scale'] ) ) / $map_info['width_quadrant'] );
  $quadCentY = floor (( $map_info['yPixel'] / guifi_get_rel_scale( $map_info['scale'] ) ) / $map_info['height_quadrant'] );

  // Find de quadrant top-left of de map scaled from pixel x,y in center.
  $map_info['quadX'] = $quadCentX - floor( $map_info['quadrants'] / 2 );
  $map_info['quadY'] = $quadCentY - floor( $map_info['quadrants'] / 2 );
  if ( $map_info['quadX'] > $quadrantsX - $map_info['quadrants'] )
    $map_info['quadX'] = $quadrantsX - $map_info['quadrants'];
  if ( $map_info['quadY'] > $quadrantsY - $map_info['quadrants'] )
    $map_info['quadY'] = $quadrantsY - $map_info['quadrants'];
  if ( $map_info['quadX'] < 0 ) $map_info['quadX'] = 0;
  if ( $map_info['quadY'] < 0 ) $map_info['quadY'] = 0;

  // Recalcule the width and heigth of scaled image
  if ( $map_info['quadX'] >= $quadrantsX - $map_info['quadrants'] )
    $map_info['width'] = floor( ($image_info['width'] / guifi_get_rel_scale( $map_info['scale'] )) - ( $map_info['quadX'] * $map_info['width_quadrant'] ));
  if ( $map_info['quadY'] >= $quadrantsY - $map_info['quadrants'] )
    $map_info['height'] = floor( ($image_info['height'] / guifi_get_rel_scale( $map_info['scale'] )) - ( $map_info['quadY'] * $map_info['height_quadrant'] ));

  // Calculem la distància de la màxima de la imatge
  if ( $node->valid ) {
    if ( $map_info['width'] < $map_info['height'] )
      $map_info['dist'] = floor($map_info['distX'] * ( $map_info['width'] * guifi_get_rel_scale( $map_info['scale'] ) ) / $image_info['width']);
    else
      $map_info['dist'] = floor($map_info['distY'] * ( $map_info['height'] * guifi_get_rel_scale( $map_info['scale'] ) ) / $image_info['height']);
  }

/*
  echo "<br />quadrantsX: ".$quadrantsX;
  echo "<br />quadrantsY: ".$quadrantsY;
  echo "<br />map_width: ".$image_info['width'];
  echo "<br />map_height: ".$image_info['height'];
*/
/*
  echo "<br />datum: ".$map_info['datum'];
  echo "<br />zone: ".$map_info['zone'];
  echo "<br />nord: ".$map_info['nord'];
  echo "<br />zoom: ".$map_info['zoom'];
  echo "<br />dist: ".$map_info['dist'];
  echo "<br />scale: ".$map_info['scale'];
  echo "<br />xPixel: ".$map_info['xPixel'];
  echo "<br />yPixel: ".$map_info['yPixel'];
  echo "<br />xUTM: ".$map_info['xUTM'];
  echo "<br />yUTM: ".$map_info['yUTM'];
  echo "<br />lon: ".$map_info['londec'];
  echo "<br />lan: ".$map_info['latdec'];

  echo "<br />quadX: ".$map_info['quadX'];
  echo "<br />quadY: ".$map_info['quadY'];
  echo "<br />width: ".$map_info['width'];
  echo "<br />height: ".$map_info['height'];
*/

  return $map_info;
}

?>
