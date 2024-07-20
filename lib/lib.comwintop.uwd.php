<?
function comwintop_uwd_add_result(& $result,$block,$title,$units,$value,$rp="") {
	$result[$block]['title']=$title;
	if ( '' == $rp ) {
		$result[$block]['value']=$value;
	} else {
		$result[$block]['value']=round($value,$rp);
	}

	$result[$block]['units']=$units;

	return $result;
}


function comwintop_uwd_get_data($modbusHost,$modbusAddress) {
	$result=array();

	$r = getModbusRegisters($modbusHost,$modbusAddress,500,14);

	foreach ( $r as $k => $v ) { printf("# r[0x%04x]=%s\n",$k,$v); }

	if ( 0 == count($r) ) {
		/* no results returned */
		return $result;
	}

	/* put into descriptive array */
	$result=comwintop_uwd_add_result($result,'WIND_SPEED','Wind Speed','m/s', $r[0x01f4]*0.01);
//	$result=comwintop_uwd_add_result($result,'WIND_STRENGTH','Wind Strength','?', $r[0x01f5]);
//	$result=comwintop_uwd_add_result($result,'WIND_DIRECTION_SECTOR','Wind Direction Sector','', $r[0x01f6]);
	$result=comwintop_uwd_add_result($result,'WIND_DIRECTION','Wind Direction','&deg;', $r[0x01f7]);
	$result=comwintop_uwd_add_result($result,'RELATIVE_HUMIDITY','Relative Humidity','%', $r[0x01f8]*0.1);
	$result=comwintop_uwd_add_result($result,'TEMPERATURE','Temperature','&deg;C', $r[0x01f9]*0.1);
	$result=comwintop_uwd_add_result($result,'NOISE','Noise','db', $r[0x01fa]*0.1);
	$result=comwintop_uwd_add_result($result,'PM2_5','PM2.5','ug/m^2', $r[0x01fb]);
	$result=comwintop_uwd_add_result($result,'PM10','PM10','ug/m^2', $r[0x01fc]);
	$result=comwintop_uwd_add_result($result,'ATMOSPHERIC_PRESSURE','Atmospheric Pressure','kpa', $r[0x01fd]*0.1);
	$result=comwintop_uwd_add_result($result,'ILLUMINANCE','Illumance (high resolution)','lux', ($r[0x01fe]<<16)  +  $r[0x01ff] );
//	$result=comwintop_uwd_add_result($result,'ILLUMINANCE_LOW','Illumance (low resolution)','lux', $r[0x0200] * 100.0 );
	$result=comwintop_uwd_add_result($result,'RAINFALL','Rainfall','mm', $r[0x0201] * 0.1 );
	print_r($result);
	

	return $result;

}

?>
