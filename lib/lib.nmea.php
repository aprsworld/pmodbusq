<?
function parseNMEA_encodeParsed($title,$units,$val,$type='',$typeUnits='') {
	$j=array();
	$j[md5($title)]['title']=$title;
	$j[md5($title)]['units']=$units;
	$j[md5($title)]['value']=$val;

	if ( '' != $type && '' != $typeUnits ) {
		$j[md5($title)]['type']=$type;
		$j[md5($title)]['typeUnits']=$typeUnits;
	}

	return $j;
}

function parseNMEA_YXXDR($parts) {
/*
(
    [0] => $YXXDR
    [1] => C
    [2] =>
    [3] => C
    [4] => WCHR
    [5] => C
    [6] =>
    [7] => C
    [8] => WCHT
    [9] => C
    [10] =>
    [11] => C
    [12] => HINX
    [13] => P
    [14] => 0.9676
    [15] => B
    [16] => STNP
)
*/

	$j=array();
	/* on Airmar the YXXDR has two different formats. STNP (station pressure) and ROLL */
	if ( 17 == count($parts) && 'STNP' == $parts[16] ) {
		$j += parseNMEA_encodeParsed('Station Pressure','bars',$parts[14],'atmosphericPressure','Bar');
	}

	return $j;
}
function parseNMEA_GPZDA($parts) {
/*
(
    [0] => $GPZDA
    [1] => 040505
    [2] => 14
    [3] => 12
    [4] => 2015
    [5] => 00
    [6] => 00
)
*/
	$zDate=sprintf("%04d-%02d-%02d %02d:%02d:%02d",
		$parts[4],
		$parts[3],
		$parts[2],
		substr($parts[1],0,2),
		substr($parts[1],2,2),
		substr($parts[1],4,2)
	);

	$j=array();
	$j += parseNMEA_encodeParsed('GPS Date','UTC',$zDate);

	return $j;
}

function parseNMEA_GPGGA($parts) {
/*
    [0] => $GPGGA
    [1] => 002831
    [2] => 4405.4100
    [3] => N
    [4] => 09106.7816
    [5] => W
    [6] => 2
    [7] => 9
    [8] => 0.8
    [9] => 239.4
    [10] => M
    [11] =>
    [12] =>
    [13] =>
    [14] =>
*/
	/* GPGGA (and NMEA0183 in general) is in degrees and minutes.decimalMinutes */
	$latitudeDegrees=substr($parts[2],0,2);
	$latitudeMinutes=substr($parts[2],2);
	$latitudeDD=$latitudeDegrees + ($latitudeMinutes/60.0);
	if ( 'S' == $parts[3] )
		$latitudeDD = 0 - $latitudeDD;

	$longitudeDegrees=substr($parts[4],0,3);
	$longitudeMinutes=substr($parts[4],3);
	$longitudeDD=$longitudeDegrees + ($longitudeMinutes/60.0);
	if ( 'E' == $parts[5] )
		$longitudeDD = 0 - $longitudeDD;

	/*
	printf("latitude degrees=%s latitude minutes=%s longitude degrees=%s longitude minutes=%s\n",
		$latitudeDegrees, $latitudeMinutes, 
		$longitudeDegrees, $longitudeMinutes
	);
	*/

	switch ( $parts[6] ) {
		case 0: $q='Fix not available or invalid'; break;
		case 1: $q='Fix not available or invalid'; break;
		case 2: $q='Differential GPS, SPS Mode, fix valid'; break;
		case 3: $q='GPS PPS Mode, fix valid';  break;
		case 4: $q='Real Time Kinematic (RTK)'; break;
		case 5: $q='Float RTK'; break;
		case 6: $q='Estimated (dead reckoning) Mode'; break;
		case 7: $q='Manual Input Mode'; break;
		case 8: $q='Simulator Mode'; break;
		default: $q='Unknown Indicator: ' . $parts[6];
	}

	$j=array();
	$j += parseNMEA_encodeParsed('Latitude','&deg;',$latitudeDD);
	$j += parseNMEA_encodeParsed('Longitude','&deg;',$longitudeDD);
	$j += parseNMEA_encodeParsed('GPS Quality Indicator','',$parts[6]);
	$j += parseNMEA_encodeParsed('GPS Quality','',$q);
	$j += parseNMEA_encodeParsed('Number of Satellites','',$parts[7]);
	$j += parseNMEA_encodeParsed('Horizontal Dilution of Precision','',$parts[8]);
	$j += parseNMEA_encodeParsed('Altitude','meters',$parts[9],'length','m');
//	$j += parseNMEA_encodeParsed('Air Temperature','&deg;C',$parts[5]);

	return $j;
}
function parseNMEA_WIMDA($parts) {
/*
    [0] => $WIMDA
    [1] => 29.3853
    [2] => I
    [3] => 0.9951
    [4] => B
    [5] => 9.3
    [6] => C
    [7] =>
    [8] =>
    [9] =>
    [10] =>
    [11] =>
    [12] =>
    [13] => 209.0
    [14] => T
    [15] => 209.9
    [16] => M
    [17] => 1.1
    [18] => N
    [19] => 0.6
    [20] => M
*/

	$j=array();
	$j += parseNMEA_encodeParsed('Barometric Pressure','bars',$parts[3],'atmosphericPressure','bars');
	$j += parseNMEA_encodeParsed('Air Temperature','&deg;C',$parts[5],'temperature','C');
	$j += parseNMEA_encodeParsed('Air Temperature (float)','&deg;C',(float) $parts[5],'temperature','C');
	$j += parseNMEA_encodeParsed('Air Temperature (string)','&deg;C',(string) $parts[5],'temperature','C');
	$j += parseNMEA_encodeParsed('Relative Humidity','%',$parts[9]);
	$j += parseNMEA_encodeParsed('Wind Direction (true)','&deg;',$parts[13]);
	$j += parseNMEA_encodeParsed('Wind Direction (magnetic)','&deg;',$parts[15]);
	$j += parseNMEA_encodeParsed('Wind Speed','m/s',$parts[19],'speed','m/s');

	return $j;
}

function parseNMEA_unknown($parts) {
	print_r($parts);
}

function parseNMEA0183($val) {
	$val=trim($val);
	printf("# parseNMEA0183(%s)\n",$val);

	/* simple checks before we start parsing */
	if ( strlen($val)<9 || ( '!' != substr($val,0,1) && '$' != substr($val,0,1) ) ) {
		return array();
	}

	/* calculate local checksum */
	$lChecksum=0;
	for ( $i=1 ; $i<strlen($val)-3 ; $i++ ) {
		$lChecksum ^= ord(substr($val,$i,1));
	}

	$rChecksum=hexdec(substr($val,strlen($val)-2));

	if ( $lChecksum != $rChecksum ) {
		printf("# invalid checksum.\n");
		return array();
	}

	/* remove checksum */
	$val=substr($val,0,strlen($val)-3);

	/* split string by ',' delimeters */
	$parts=explode(',',$val);


	switch ( $parts[0] ) {
		case '$WIMDA': return parseNMEA_WIMDA($parts);
		case '$GPGGA': return parseNMEA_GPGGA($parts);
		case '$GPZDA': return parseNMEA_GPZDA($parts);
		case '$YXXDR': return parseNMEA_YXXDR($parts);
		default:       return parseNMEA_unknown($parts);
	}
}

function getNMEA0183nmeaReader($host, $port) {
	$gpsDataJSON='';

	$fp = @fsockopen($host,$port, $errno, $errstr, 30);
	
	if ( ! $fp ) {
		return NULL;
	}

	while (!feof($fp)) {
		$gpsDataJSON .= fgets($fp, 128);
	}

	fclose($fp);

	return json_decode($gpsDataJSON,true);
}

/* we can get all the latest NMEA sentences from nmeaReader and then decode to wsBroadcast format  with something like:

<?
require 'lib.nmea.php';

$r = getNMEA0183nmeaReader('192.168.1.2',2627);

print_r($r);
var_dump($r);

$g=array();

if ( NULL !== $r ) {
	foreach( $r as $key=>$value) {
		$g .= parseNMEA0183($value['sentence']);
	}
}

print_r($g);
?>
*/

?>
