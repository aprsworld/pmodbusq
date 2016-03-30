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

$rainSum=$r[16]+($r[17]<<16);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Rain Pulses Sum','pulses',$rainSum);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Rain Inches Sum','inches',$rainSum*0.01);

/* global water pressure sensor
0' = 4.009mA
8.19' = 18.857mA

dropped over 120 ohm resistor on user ADC 3 

feet = 4.5938*volts-2.205

5.44' on this gauge is approximately 39.35' at black river falls station
(datum 700')

*/
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Water Depth (pressure) volts','volts',$r[40]*0.001220703);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Water Depth (pressure) amps','amps',$r[40]*0.001220703/120.0);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Water Depth (pressure) feet','feet',4.5938*($r[40]*0.001220703)-2.205);


$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Sequence Number','',$r[42]);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Ticks','seconds',$r[43]*0.001);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Uptime','minutes',$r[44]);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Watchdog Timer','seconds',$r[45]);

$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Packet Date','UTC',date("Y-m-d H:i:s"));
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Linux Uptime','',exec("uptime"));
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Linux Uptime','',exec("uptime"));

/* send data to broadcast server(s) */
print_r($jd);
sendDataTCP($dest,$jd);

?>
