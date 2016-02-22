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

/* modbus registers read from pcp board */
$r=array();

/* current value registers */
$r = $r + getModbusRegisters('localhost',38,0,20);

$jd=array();

$jd[$hostname]['sensors']=array();


$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Input Voltage','volts',$r[1]*0.024477);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Magnetic Switch State','',$r[6]);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Magnetic Switch Latch','',$r[7]);

$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Sequence Number','',$r[10]);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Ticks','seconds',$r[11]*0.001);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Uptime','minutes',$r[12]);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Watchdog Timer','seconds',$r[13]);

$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Packet Date','UTC',date("Y-m-d H:i:s"));
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Linux Uptime','',exec("uptime"));
//$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('802.11 Signal','',exec('iwconfig wlan0 | grep Quality | cut -c 11-'));


/* send data to broadcast server(s) */
print_r($jd);
sendDataTCP($dest,$jd);

?>
