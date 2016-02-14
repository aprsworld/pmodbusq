<?
/* volts per bit for voltage (0 to 35 volt) input */
define('PCWX_VIN_V_PER_BIT',0.008544922); 
/* volts per bit for 0 to 5 volt input */
define('PCWX_5VOLT_V_PER_BIT',0.001220703);

function pcwx_encodeForBroadcast($title,$units,$val,$type='',$typeUnits='',$log=false) {
	$j=array();
	$j[md5($title)]['title']=$title;
	$j[md5($title)]['units']=$units;
	$j[md5($title)]['value']=$val;

	if ( '' != $type && '' != $typeUnits ) {
		$j[md5($title)]['type']=$type;
		$j[md5($title)]['typeUnits']=$typeUnits;
	}
	if ( false != $log ) {
		$j[md5($title)]['log']=true;
	}

	return $j;
}

function pcwx_anemometer($m,$b,$val) {
	if ( 65535 == $val || 0 == $val ) 
		return 0.0;

	$data=10000/$val;


	return $m*$data + $b;
}

/** calculate value from transfer function, counter value, and sample seconds */
function pcwx_anemometer_average($m,$b,$val,$sampleSeconds) {
	if ( 65535 == $val || 0 == $val || 655.35 == $sampleSeconds || 0 == $sampleSeconds ) 
		return 0.0;

	$data = $val / $sampleSeconds;

	return $m*$data + $b;
}

?>
