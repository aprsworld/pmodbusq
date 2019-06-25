#!/usr/bin/php -q
<?
require_once dirname(__FILE__) . '/../pmodbusq.php';
require 'lib/lib.modbus.php';
require 'lib/lib.morningstar.tristar.php';
require 'lib/lib.morningstar.tristarmppt.php';
require 'lib/lib.senddata.php';

$hostname=gethostname();
if ( 'cam' == substr($hostname,0,3) ) {
	$hostname=substr($hostname,3);
}

if ( $_SERVER['argc'] != 2 || false === strpos($_SERVER['argv'][1],':') ) {
	printf("usage: %s hostname:destination[,hostname1:port1,...hostnameN:portN]\n",$_SERVER['argv'][0]);
	return 1;
}
$dest=$_SERVER['argv'][1];


$r=array();
$r['TriStar']=array();
$r['TriStar']['Dump']=array();
ms_tristar_get_data('localhost',1,$r['TriStar']['Dump']);

$r['TriStar MPPT']=array();
$r['TriStar MPPT']['Solar A']=array();
ms_tristarmppt_get_data('localhost',2,$r['TriStar MPPT']['Solar A']);

//print_r($r);


die("\ndone\n");

/* modbus registers read from XRW2G */
$r=array();

/* current value registers */
$r = $r + getModbusRegisters('localhost',38,0,47);

$jd=array();

$jd[$hostname]['sensors']=array();
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Input Voltage','volts',$r[18]*0.008545);

$rpm=pcwx_anemometer(12.0,0.0,$r[7]);
$rpmGust=pcwx_anemometer(12.0,0.0,$r[8]);
$rpmAverage=pcwx_anemometer_average(12.0,0.0,$r[6],$r[43]/100.0);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Turbine Rotor Speed','RPM',$rpm,'','',true);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Turbine Rotor Gust','RPM',$rpmGust,'','',true);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Turbine Rotor Average','RPM',$rpmAverage,'','',true);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Turbine Rotor Count','',$r[6],'','',true);


$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Sequence Number','',$r[42]);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Ticks','seconds',$r[43]*0.001);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Uptime','minutes',$r[44]);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Watchdog Timer','seconds',$r[45]);

$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Packet Date','UTC',date("Y-m-d H:i:s"));
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Linux Uptime','',exec("uptime"));

/* TriStar dump controller on modbus, device ID=1 */
/* current value registers */
printf("Querying Tristar\n");
$r = getModbusRegisters('localhost',1,8,21);
print_r($r);


$jd[$hostname]['TriStar']=array();
$jd[$hostname]['TriStar'] += pcwx_encodeForBroadcast('Packet Date','UTC',date("Y-m-d H:i:s"));

/* sunsaver MPPT */
$jd[$hostname]['TriStar'] += pcwx_encodeForBroadcast('Battery Voltage','VDC',$r[8]*96.667*pow(2,-15));
$jd[$hostname]['TriStar'] += pcwx_encodeForBroadcast('Load Voltage','VDC',$r[10]*96.667*pow(2,-15));
$jd[$hostname]['TriStar'] += pcwx_encodeForBroadcast('Load Current','amps',$r[12]*316.67*pow(2,-15));

/* signed temperatures */
$t_hs=$r[0x000e];
if ( $t_hs > 32767 ) 
	$t_hs = $t_hs - 65536;
$jd[$hostname]['TriStar'] += pcwx_encodeForBroadcast('Heatsink Temperature','&deg;C',$t_hs,'temperature','c');

$t_batt=$r[0x000f];
if ( $t_batt > 32767 ) 
	$t_batt = $t_batt - 65536;
$jd[$hostname]['TriStar'] += pcwx_encodeForBroadcast('Battery Temperature','&deg;C',$t_batt,'temperature','c');

$jd[$hostname]['TriStar'] += pcwx_encodeForBroadcast('Reference Voltage','VDC',$r[0x0010]*96.667*pow(2,-15));
$jd[$hostname]['TriStar'] += pcwx_encodeForBroadcast('Duty Cycle','%',$r[0x001c]);

/* SunSaver MPPT on modbus, device ID=2 */
/* current value registers */
printf("Querying SunSaver MPPT\n");
$r = getModbusRegisters('localhost',2,8,20);
print_r($r);

$jd[$hostname]['SunSaverMPPT']=array();
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Packet Date','UTC',date("Y-m-d H:i:s"));

/* sunsaver MPPT */
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Battery Voltage','VDC',$r[8]*100*pow(2,-15));
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Array Voltage','VDC',$r[9]*100*pow(2,-15));
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Load Voltage','VDC',$r[10]*100*pow(2,-15));
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Charging Current','A',$r[11]*79.16*pow(2,-15));
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Load Current','A',$r[12]*79.16*pow(2,-15));

/* signed temperatures */
if ( $r[13] > 32767 ) $r[13] -= 65536;
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Heatsink Temperature','&deg;C',$r[13],'temperature','c');

if ( $r[14] > 32767 ) $r[14] -= 65536;
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Battery Temperature','&deg;C',$r[14],'temperature','c');

if ( $r[15] > 32767 ) $r[15] -= 65536;
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Ambient Temperature','&deg;C',$r[15],'temperature','c');

if ( $r[16] > 32767 ) $r[16] -= 65536;
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Remote Battery Temperature','&deg;C',$r[16],'temperature','c');

/* need to write library decoding functions */
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Charge State','',$r[17]);
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Array Fault','',$r[18]);

$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Battery Voltage, slow','VDC',$r[19]*100*pow(2,-15));
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Battery Regulator Reference Voltage','VDC',$r[20]*96.667*pow(2,-15));

/* not working */
/*
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Ah charge, resettable','Ah',($r[21]<<16 + $r[22])*0.1);
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Ah charge, total','Ah',($r[23]<<16 + $r[24])*0.1);

$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('kWh charge','kWh',$r[25]);
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Load State','',$r[26]);
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Load Fault','',$r[27]);
*/


/* send data to broadcast server(s) */
print_r($jd);
sendDataTCP($dest,$jd);

?>
