<?
function ms_tristarmppt_lookup_charge_state_human($val) {
	$control_state_human=array('START','NIGHT CHECK','DISCONNECT','NIGHT','FAULT','MPPT','ABSORPTION','FLOAT','EQUALIZE','SLAVE');

	if ( array_key_exists($val,$control_state_human) ) {
		return $control_state_human[$val];
	}

	return sprintf("INVALID CHARGE STATE %d",$val);
}

function ms_tristarmppt_implode_human($a) {
	return implode(', ',$a);
}

function ms_tristarmppt_explode_alarm($val) {
	$alarm=array();
	$alarm[0]='RTS OPEN';
	$alarm[1] ='RTS SHORTED';
	$alarm[2] ='RTS DISCONNECTED';
	$alarm[3] ='HEATSINK TEMP SENSOR OPEN';
	$alarm[4] ='HEATSINK TEMP SENSOR SHORTED';
	$alarm[5] ='HIGH TEMPERATURE CURRENT LIMIT';
	$alarm[6] ='CURRENT LIMIT';
	$alarm[7] ='CURRENT OFFSET';
	$alarm[8] ='BATTERY SENSE OUT OF RANGE';
	$alarm[9] ='BATTERY SENSE DISCONNECTED';
	$alarm[10]='UNCALIBRATED';
	$alarm[11]='RTS MISWIRE';
	$alarm[12]='HVD';
	$alarm[13]='UNDEFINED';
	$alarm[14]='SYSTEM MISWIRE';
	$alarm[15]='MOSFET OPEN';
	$alarm[16]='P12 VOLTAGE OFF';
	$alarm[17]='HIGH INPUT VOLTAGE CURRENT LIMIT';
	$alarm[18]='ADC INPUT MAX';
	$alarm[19]='CONTROLLER WAS RESET';
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

function ms_tristarmppt_explode_fault($val) {
	$fault=array();
	$fault[0] ='OVER CURRENT';
	$fault[1] ='FETS SHORTED';
	$fault[2] ='SOFTWARE BUG';
	$fault[3] ='BATTERY HVD';
	$fault[4] ='ARRAY HVD';
	$fault[5] ='SETTINGS SWITCH CHANGED';
	$fault[6] ='CUSTOM SETTINGS EDIT';
	$fault[7] ='RTS SHORTED';
	$fault[8] ='RTS DISCONNECTED';
	$fault[9] ='EEPROM RETRY LIMIT';
	$fault[10]='RESERVED';
	$fault[11]='SLAVE CONTROL TIMEOUT';
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

function ms_tristarmppt_dip_switch_human($val) {
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


function ms_tristarmppt_add_result(& $result,$block,$title,$units,$value,$rp="") {
	$result[$block]['title']=$title;
	if ( '' == $rp ) {
		$result[$block]['value']=$value;
	} else {
		$result[$block]['value']=round($value,$rp);
	}

	$result[$block]['units']=$units;

	return $result;
}

function ms_tristarmppt_scale($whole, $fraction) {
	return $whole + $fraction / 65536.0;
}

function ms_tristarmppt_scaleSigned($adc, $scale, $power) {
	if ( $adc >= 32768 ) {
		$adc -= 65536;
	}

	return ( $adc*$scale ) / pow(2,$power);
}


function ms_tristarmppt_get_data($modbusHost,$modbusAddress) {
	$result=array();

	/* read registers 0x8 to 0x4d */
	$r = getModbusRegisters($modbusHost,$modbusAddress,0x00,0x49+1);

//	foreach ( $r as $k => $v ) { printf("# r[0x%04x]=%s\n",$k,$v); }

	if ( 0 == count($r) ) {
		/* no results returned */
		return $result;
	}

//	foreach ( $r as $k => $v ) { printf("# r[0x%04x]=%s\n",$k,$v); }


	/* calculated values */
	$v_pu = ms_tristarmppt_scale($r[0x00],$r[0x01]);
	$i_pu = ms_tristarmppt_scale($r[0x02],$r[0x03]);

	$t_hs = $r[0x23];
	if ( $t_hs > 32767 )
		$t_hs -= 65536;

	$t_rts = $r[0x24];
	if ( $t_rts > 32767 )
		$t_rts -= 65536;

	$t_batt = $r[0x23];
	if ( $t_batt > 32767 )
		$t_batt -= 65536;

	/* Logger - Today's values */
	$today_t_batt_min = $r[0x47];
	if ( $today_t_batt_min > 32767 )
		$today_t_batt_min -= 65536;

	$today_t_batt_max = $r[0x47];
	if ( $today_t_batt_max > 32767 )
		$today_t_batt_max -= 65536;
	

	/* put into descriptive array */
	/* filtered ADC */
//	$result=ms_tristarmppt_add_result($result,'VER_SOFTWARE','Software Version','', $r[0x4]); /* TODO: SHOULD BE BCD */
	$result=ms_tristarmppt_add_result($result,'V_BATTERY','Battery Voltage','VDC', $r[0x18]*$v_pu*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'V_BATTERY_TERM','Battery Terminal Voltage','VDC', $r[0x19]*$v_pu*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'V_BATTERY_SENSE','Battery Sense Voltage','VDC', $r[0x1a]*$v_pu*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'V_ARRAY','Array Voltage','VDC', $r[0x1b]*$v_pu*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'I_BATTERY','Battery Current','amps DC', $r[0x1c]*$v_pu*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'I_ARRAY','Array Current','amps DC', $r[0x1d]*$v_pu*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'V_12V','Internal 12V Supply','VDC', $r[0x1e]*18.612*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'V_3V','Internal 3V Supply','VDC', $r[0x1f]*6.6*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'V_METERBUS','Internal MeterBus Supply','VDC', $r[0x20]*18.612*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'V_1V8','Internal 1.8V Supply','VDC', $r[0x21]*3*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'V_REF','Reference Voltage','VDC', $r[0x22]*3*pow(2,-15), 3);


	/* temperatures */
	$result=ms_tristarmppt_add_result($result,'T_HEATSINK','Heatsink Temperature','&deg; C',$t_hs);
	$result=ms_tristarmppt_add_result($result,'T_RTS','RTS Temperature','&deg; C',$t_rts);
	$result=ms_tristarmppt_add_result($result,'T_BATT','Battery Regulation Temperature','&deg; C',$t_batt);

	/* status */
	$result=ms_tristarmppt_add_result($result,'V_BATTERY_SLOW','Battery Voltage (slow)','VDC', $r[0x26]*$v_pu*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'I_BATTERY_SLOW','Battery Current (slow)','VDC', $r[0x27]*$i_pu*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'V_BATTERY_MIN','Battery Minimum Voltage','VDC', $r[0x28]*$v_pu*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'V_BATTERY_MAX','Battery Maximum Voltage','VDC', $r[0x29]*$v_pu*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'HOURS_HOURMETER','Hour meter','hours', (($r[0x2a]<<16) + $r[0x2b]));
	$result=ms_tristarmppt_add_result($result,'BITFIELD_FAULT','Fault Value','', $r[0x2c]);
	$result=ms_tristarmppt_add_result($result,'BITFIELD_ALARM','Alarm Value','', (($r[0x2e]<<16) + $r[0x2f]));
	$result=ms_tristarmppt_add_result($result,'BITFIELD_DIP_SWITCH','DIP Switch Value','',$r[0x30]);

	/* charger */
	$result=ms_tristarmppt_add_result($result,'CHARGE_STATE','Charging State','',$r[0x32]);
	$result=ms_tristarmppt_add_result($result,'V_TARGET','Voltage Target','VDC', $r[0x33]*$v_pu*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'AMPHOURS_RESETTABLE','Amp/hours (resettable)','amp/hours', (($r[0x34]<<16) + $r[0x35])*0.1);
	$result=ms_tristarmppt_add_result($result,'AMPHOURS_TOTAL','Amp/hours (total)','amp/hours', (($r[0x36]<<16) + $r[0x37])*0.1);
	$result=ms_tristarmppt_add_result($result,'KWH_RESETTABLE','Energy (resettable)','kWh', $r[0x38]);
	$result=ms_tristarmppt_add_result($result,'KWH_TOTAL','Energy (total)','kWh', $r[0x39]);

	/* MPPT */
	$result=ms_tristarmppt_add_result($result,'P_OUT','Power Output','watts', $r[0x3a]*$v_pu*$i_pu*pow(2,-17), 0);
	$result=ms_tristarmppt_add_result($result,'P_IN','Power Input','watts', $r[0x3b]*$v_pu*$i_pu*pow(2,-17), 0);
	$result=ms_tristarmppt_add_result($result,'SWEEP_PIN_MAX','Last Sweep Maximum Power','watts', $r[0x3c]*$v_pu*$i_pu*pow(2,-17), 0);
	$result=ms_tristarmppt_add_result($result,'SWEEP_VMP','Last Sweep Vmp','VDC', $r[0x3d]*$v_pu*pow(2,-15), 0);
	$result=ms_tristarmppt_add_result($result,'SWEEP_VOC','Last Sweep Voc','VDC', $r[0x3e]*$v_pu*pow(2,-15), 0);

	/* Logger - Today's Values */
	$result=ms_tristarmppt_add_result($result,'TODAY_V_BATTERY_MIN','Today\'s Battery Minimum Voltage','VDC', $r[0x40]*$v_pu*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'TODAY_V_BATTERY_MAX','Today\'s Battery Maximum Voltage','VDC', $r[0x41]*$v_pu*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'TODAY_V_ARRAY_MAX','Today\'s Array Maximum Voltage','VDC', $r[0x42]*$v_pu*pow(2,-15), 2);
	$result=ms_tristarmppt_add_result($result,'TODAY_AMPHOURS','Today\'s Ah Charge','amp/hours', $r[0x43]*0.1);
	$result=ms_tristarmppt_add_result($result,'TODAY_WATTHOURS','Today\'s Wh Charge','watt/hours', $r[0x44]);
	/* TODO flags daily */
	$result=ms_tristarmppt_add_result($result,'TODAY_P_OUT_MAX','Today\'s Power Maximum Output','watts', $r[0x46]*$v_pu*$i_pu*pow(2,-17), 0);
	$result=ms_tristarmppt_add_result($result,'TODAY_T_BATT_MIN','Today\'s Battery Minimum Temperature','&deg; C',$today_t_batt_min);
	$result=ms_tristarmppt_add_result($result,'TODAY_T_BATT_MAX','Today\'s Battery Maximum Temperature','&deg; C',$today_t_batt_max);
	/* TODO fault daily */



	/* decode or make human readable values */
	$result=ms_tristarmppt_add_result($result,'BITFIELD_ALARM_HUMAN','Alarm(s)','',
		ms_tristarmppt_implode_human(ms_tristarmppt_explode_alarm($result['BITFIELD_ALARM']['value']))
	);

	$result=ms_tristarmppt_add_result($result,'BITFIELD_FAULT_HUMAN','Fault(s)','',
		ms_tristarmppt_implode_human(ms_tristarmppt_explode_fault($result['BITFIELD_FAULT']['value']))
	);

	$result=ms_tristarmppt_add_result($result,'BITFIELD_DIP_SWITCH_HUMAN','DIP Switch Settings','', ms_tristarmppt_dip_switch_human($result['BITFIELD_DIP_SWITCH']['value']));

	$result=ms_tristarmppt_add_result($result,'CHARGE_STATE_HUMAN','Charge State','', ms_tristarmppt_lookup_charge_state_human($result['CHARGE_STATE']['value']));


//	print_r($result);
	

	return $result;

}

?>
