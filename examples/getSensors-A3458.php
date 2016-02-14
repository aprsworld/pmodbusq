#!/usr/bin/php -q
<?
/* get our paths right */
require_once dirname(__FILE__) . '/../pmodbusq.php';
require_once 'lib/lib.modbus.php';
require_once 'lib/lib.pcwx.php';
require_once 'lib/lib.senddata.php';

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
$r = $r + getModbusRegisters('localhost:503',90,1000,8);
//print_r($r);

$jd=array();

$jd[$hostname]['sensors']=array();
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Serial Prefix','',chr($r[1000]));
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Serial Number','',$r[1001]);
/*
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Optical Rain Gauge','counts',$r[10]);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Input Voltage','volts',$r[18]*0.008545);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Ambient Temperature','&deg;C',$r[21]*0.001220703*100.0-273.15,'temperature','c');
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Sequence Number','',$r[42]);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Ticks','seconds',$r[43]*0.001);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Uptime','minutes',$r[44]);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Watchdog Timer','seconds',$r[45]);

$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Packet Date','UTC',date("Y-m-d H:i:s"));
*/
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Linux Uptime','',exec("uptime"));

/* send data to broadcast server(s) */
print_r($jd);
sendDataTCP($dest,$jd);

?>
