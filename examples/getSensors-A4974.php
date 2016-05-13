#!/usr/bin/php -q
<?
require_once dirname(__FILE__) . '/../pmodbusq.php';
require 'lib/lib.modbus.php';
require 'lib/lib.pcwx.php';
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

/* TriSTar dump controller on modbus, device ID=1 */


/* SunSaver MPPT on modbus, device ID=2 */
/* current value registers */
$r = $r + getModbusRegisters("127.0.0.1",2,8,45);
print_r($r);

$jd=array();

$jd[$hostname]['SunSaverMPPT']=array();
/* some meta data */

/* sunsaver MPPT */
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Battery Voltage','VDC',$r[8]*100*pow(2,-15));
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Array Voltage','VDC',$r[9]*100*pow(2,-15));
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Load Voltage','VDC',$r[10]*100*pow(2,-15));
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Charging Current','A',$r[11]*79.16*pow(2,-15));
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Load Current','A',$r[12]*79.16*pow(2,-15));

/* need to figure out encoding */
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Heatsink Temperature','&deg;C',$r[13],'temperature','c');
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Battery Temperature','&deg;C',$r[14],'temperature','c');
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Ambient Temperature','&deg;C',$r[15],'temperature','c');
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Remote Battery Temperature','&deg;C',$r[16],'temperature','c');

/* need to write library decoding functions */
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Charge State','',$r[17]);
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Array Fault','',$r[18]);

$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Battery Voltage, slow','VDC',$r[19]*100*pow(2,-15));
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Battery Regulator Reference Voltage','VDC',$r[20]*96.667*pow(2,-15));

/* not working */
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Ah charge, resettable','Ah',($r[21]<<16 + $r[22])*0.1);
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Ah charge, total','Ah',($r[23]<<16 + $r[24])*0.1);

$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('kWh charge','kWh',$r[25]);
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Load State','',$r[26]);
$jd[$hostname]['SunSaverMPPT'] += pcwx_encodeForBroadcast('Load Fault','',$r[27]);


/* send data to broadcast server(s) */
print_r($jd);
sendDataTCP($dest,$jd);

?>
