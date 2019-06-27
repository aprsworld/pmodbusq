<?
function ms_tristar_lookup_control_state_human($mode,$val) {
	if ( 0x00 == $mode || 0x02 == $mode ) {
		$control_state_human=array('START','NIGHT CHECK','DISCONNECT','NIGHT','FAULT','BULK','PWM','FLOAT','EQUALIZE');
	} else if ( 0x01 == $mode || 0x03 == $mode ) {
		$control_state_human=array('START','NORMAL','LVD WARN','LVD','FAULT','DISCONNECT','NORMAL OFF','OVERRIDE LVD');
	} else {
		return sprintf("INVALID CONTROL MODE %d WITH CONTROL STATE %d",$mode,$val);
	}

	if ( array_key_exists($val,$control_state_human) ) {
		return $control_state_human[$val];
	}

	return sprintf("INVALID CONTROL STATE %d",$val);
}

function ms_tristar_lookup_control_mode_human($val) {
	$control_mode_human=array(0=>'CHARGE',1=>'LOAD',2=>'DIVERSION',3=>'LIGHTING');

	if ( array_key_exists($val,$control_mode_human) ) {
		return $control_mode_human[$val];
	}

	return sprintf("INVALID CONTROL MODE %d",$val);
}

function ms_tristar_implode_human($a) {
	return implode(', ',$a);
}

function ms_tristar_explode_alarm($val) {
	$alarm=array();
	$alarm[0]='RTS OPEN';
	$alarm[1] ='RTS SHORTED';
	$alarm[2] ='RTS DISCONNECTED';
	$alarm[3] ='THS DISCONNECTED';
	$alarm[4] ='THIS SHORTED';
	$alarm[5] ='TRISTAR HOT';
	$alarm[6] ='CURRENT LIMIT';
	$alarm[7] ='CURRENT OFFSET';
	$alarm[8] ='BATTERY SENSE';
	$alarm[9] ='BATTERY SENSE DISCONNECT';
	$alarm[10]='UNCALIBRATED';
	$alarm[11]='RTS MISWIRE';
	$alarm[12]='HVD';
	$alarm[13]='HIGH D';
	$alarm[14]='MISWIRE';
	$alarm[15]='FET OPEN';
	$alarm[16]='P12';
	$alarm[17]='LOAD DISCONNECT';
	$alarm[18]='ALARM 19';
	$alarm[19]='ALARM 20';
	$alarm[20]='ALARM 21';
	$alarm[21]='ALARM 22';
	$alarm[22]='ALARM 23';
	$alarm[23]='ALARM 24';

	$r=array();

	for ( $i=0 ; $i<24 ; $i++ ) {
		if ( $val & (1 << $i) ) {
			$r[]=$alarm[$i];
		}
	}

	return $r;

}

function ms_tristar_explode_fault($val) {
	$fault=array();
	$fault[0] ='EXTERNAL SHORT';
	$fault[1] ='OVERCURRENT';
	$fault[2] ='FET SHORT';
	$fault[3] ='SOFTWARE';
	$fault[4] ='HVD';
	$fault[5] ='TRISTAR HOT';
	$fault[6] ='DIP SWITCH CHANGED';
	$fault[7] ='SETTINGS EDIT';
	$fault[8] ='RESET';
	$fault[9] ='MISWIRE';
	$fault[10]='RTS SHORTED';
	$fault[11]='RTS DISCONNECTED';
	$fault[12]='FAULT 12';
	$fault[13]='FAULT 13';
	$fault[14]='FAULT 14';
	$fault[15]='FAULT 15';

	$r=array();

	for ( $i=0 ; $i<24 ; $i++ ) {
		if ( $val & (1 << $i) ) {
			$r[]=$fault[$i];
		}
	}

	return $r;
}

function ms_tristar_dip_switch_human($val) {
	$r="";

	for ( $i=0 ; $i<8 ; $i++ ) {
		if ( $val & (1 << $i) ) {
			$r .= "ON ";
		} else {
			$r .= "OFF ";
		}
	}

	return trim($r);
}


function ms_tristar_add_result(& $result,$block,$title,$units,$value,$rp="") {
	$result[$block]['title']=$title;
	if ( '' == $rp ) {
		$result[$block]['value']=$value;
	} else {
		$result[$block]['value']=round($value,$rp);
	}

	$result[$block]['units']=$units;

	return $result;
}


function ms_tristar_get_data($modbusHost,$modbusAddress) {
	$result=array();

	/* read registers 0x8 to 0x1d -- compatible with software newer than 1.04.02 */
	$r = getModbusRegisters($modbusHost,$modbusAddress,0x08,(0x1d-0x08)+1);

//	foreach ( $r as $k => $v ) { printf("# r[0x%04x]=%s\n",$k,$v); }

	if ( 0 == count($r) ) {
		/* no results returned */
		return $result;
	}

	/* calculated values */
	/* signed temperatures */
	$t_hs=$r[0x000E];
	if ( $t_hs > 32767 ) 
		$t_hs = $t_hs - 65536;

	$t_batt=$r[0x000F];
	if ( $t_batt > 32767 ) 
		$t_batt = $t_batt - 65536;

	$pwm = $r[0x1c]*(1.0/2.3);
	if ( $pwm > 100.0 )
		$pwm=100.0;

	/* put into descriptive array */
	$result=ms_tristar_add_result($result,'V_BATTERY','Battery Voltage','VDC', $r[0x8]*96.667*pow(2,-15), 2);
	$result=ms_tristar_add_result($result,'V_BATTERY_SENSE','Battery Sense Voltage','VDC', $r[0x9]*96.667*pow(2,-15), 2);
	$result=ms_tristar_add_result($result,'V_ARRAY_LOAD','Array / Load Voltage','VDC', $r[0xa]*139.15*pow(2,-15), 2);
	$result=ms_tristar_add_result($result,'I_CHARGING','Charging Current','amps DC', $r[0xb]*66.667*pow(2,-15), 2);
	$result=ms_tristar_add_result($result,'I_LOAD','Load Current','amps DC', $r[0xc]*316.67*pow(2,-15), 2);
	$result=ms_tristar_add_result($result,'V_BATTERY_SLOW','Battery Voltage (slow)','VDC', $r[0xd]*96.667*pow(2,-15), 2);
	$result=ms_tristar_add_result($result,'T_HEATSINK','Heatsink Temperature','&deg; C', $t_hs);
	$result=ms_tristar_add_result($result,'T_BATT','Battery Temperature','&deg; C', $t_batt);
	$result=ms_tristar_add_result($result,'V_TARGET','Target Voltage','VDC', $r[0x10]*96.667*pow(2,-15), 2);

	$result=ms_tristar_add_result($result,'AMPHOURS_RESETTABLE','Amp/hours (resettable)','amp/hours', (($r[0x11]<<16) + $r[0x12])*0.1);
	$result=ms_tristar_add_result($result,'AMPHOURS_TOTAL','Amp/hours (total)','amp/hours', (($r[0x13]<<16) + $r[0x14])*0.1);
	$result=ms_tristar_add_result($result,'HOURS_HOURMETER','Hour meter','hours', (($r[0x15]<<16) + $r[0x16]));
	$result=ms_tristar_add_result($result,'BITFIELD_ALARM','Alarm Value','', (($r[0x1d]<<16) + $r[0x17]));
	$result=ms_tristar_add_result($result,'BITFIELD_FAULT','Fault Value','', $r[0x18]);
	$result=ms_tristar_add_result($result,'BITFIELD_DIP_SWITCH','DIP Switch Value','',$r[0x19]);
	$result=ms_tristar_add_result($result,'CONTROL_MODE','Control Mode','',$r[0x1a]);
	$result=ms_tristar_add_result($result,'CONTROL_STATE','Control State','',$r[0x1b]);
	$result=ms_tristar_add_result($result,'DUTYCYCLE_PWM','PWM Duty Cycle','%',$pwm);


	/* decode some things into human readable */
	$result=ms_tristar_add_result($result,'CONTROL_MODE_HUMAN','Control Mode','', ms_tristar_lookup_control_mode_human($result['CONTROL_MODE']['value']));

	$result=ms_tristar_add_result($result,'CONTROL_STATE_HUMAN','Control State','', ms_tristar_lookup_control_state_human($result['CONTROL_MODE']['value'],$result['CONTROL_STATE']['value']));

	$result=ms_tristar_add_result($result,'BITFIELD_ALARM_HUMAN','Alarm(s)','',
		ms_tristar_implode_human(ms_tristar_explode_alarm($result['BITFIELD_ALARM']['value']))
	);

	$result=ms_tristar_add_result($result,'BITFIELD_FAULT_HUMAN','Fault(s)','',
		ms_tristar_implode_human(ms_tristar_explode_fault($result['BITFIELD_FAULT']['value']))
	);

	$result=ms_tristar_add_result($result,'BITFIELD_DIP_SWITCH_HUMAN','DIP Switch Settings','', ms_tristar_dip_switch_human($result['BITFIELD_DIP_SWITCH']['value']));




	/* read TriStar kWh EEPROM register 0xe02c */
	$r = getModbusRegisters($modbusHost,$modbusAddress,0xe02c,1);

//	foreach ( $r as $k => $v ) { printf("# r(eeprom)[0x%04x]=%s\n",$k,$v); }

	if ( 0 == count($r) ) {
		/* no results returned */
		return true;
	}

	$result=ms_tristar_add_result($result,'KWH','kWh','kWh',$r[0xe02c]);


//	print_r($result);
	

	return $result;

}

?>
