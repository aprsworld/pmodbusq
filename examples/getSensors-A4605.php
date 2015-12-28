#!/usr/bin/php -q
<?
require 'lib.modbus.php';
require 'lib.pcwx.php';
require 'lib.senddata.php';

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

$ws=pcwx_anemometer(0.765,0.350,$r[1]);
$wg=pcwx_anemometer(0.765,0.350,$r[2]);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Wind Speed','m/s',$ws,'speed','m/s');
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Wind Gust','m/s',$wg,'speed','m/s');


$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Input Voltage','volts',$r[18]*0.024477);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Temperature','&deg;C',$r[30]*0.001220703*39.394 - 30.0,'temperature','c');
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Relative Humidity','%',$r[33]*0.001220703*30.303);

$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Sequence Number','',$r[42]);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Ticks','seconds',$r[43]*0.001);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Uptime','minutes',$r[44]);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Watchdog Timer','seconds',$r[45]);

$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Packet Date','UTC',date("Y-m-d H:i:s"));
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Linux Uptime','',exec("uptime"));
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('802.11 Signal','',exec('iwconfig wlan0 | grep Quality | cut -c 11-'));


/* send data to broadcast server(s) */
print_r($jd);
sendDataTCP($dest,$jd);

?>
