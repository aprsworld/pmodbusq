<?
function pcwx_encodeForBroadcast($title,$units,$val,$type='',$typeUnits='') {
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

function pcwx_anemometer($m,$b,$val) {
	if ( 65535 == $val || 0 == $val ) 
		return 0.0;

	$data=10000/$val;

	return $m*$data + $b;
}

?>
