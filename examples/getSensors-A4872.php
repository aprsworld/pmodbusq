#!/usr/bin/php -q
<?
require_once dirname(__FILE__) . '/../pmodbusq.php';
require 'lib/lib.modbus.php';
require 'lib/lib.pcwx.php';
require 'lib/lib.w1temp.php';
require 'lib/lib.nmea.php';
require 'lib/lib.senddata.php';

define('NMEA_MAX_AGE',5000);
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

/* NMEA status registers */
$r = $r + getModbusRegisters('localhost',38,6500,24);


/* NMEA Data */
$sn=array();
$j=array();

for ( $n=0 ; $n<12 ; $n++ ) {
	if ( $r[6500+$n*2] > NMEA_MAX_AGE ) {
//		printf("# No or old data for sentence %d. Skipping.\n",$n);
		continue;
	}

	/* read the data in 40 word (packed) registers representing 80 bytes */
	$sn[$n] = trim(getModbusRegistersPackedAsString('localhost',38,6000+$n*40,40));

	$j += parseNMEA0183($sn[$n]);
}

$jd=array();
/* NMEA (Airmar) sensors */
$jd[$hostname]['airmar']=$j;
/* 1-wire sensors */
$jd[$hostname]['sensors']=array();
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Ambient Temperature','&deg;C',getW1Temp('/sys/bus/w1/devices/28-000006cb19f1/w1_slave'),'temperature','c');

var_dump($jd);

sendDataTCP($dest,$jd);

/*
printf("#### Raw Register Results ####\n");
print_r($r);

printf("#### NMEA Data ####\n");
var_dump($sn);
*/

return 0;
?>
