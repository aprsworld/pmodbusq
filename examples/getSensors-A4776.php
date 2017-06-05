#!/usr/bin/php -q
<?
require_once dirname(__FILE__) . '/../pmodbusq.php';
require 'lib/lib.senddata.php';
require 'lib/lib.nmea.php';
require 'lib/lib.pcwx.php';


$hostname=gethostname();
if ( 'cam' == substr($hostname,0,3) ) {
	$hostname=substr($hostname,3);
}

if ( $_SERVER['argc'] != 2 || false === strpos($_SERVER['argv'][1],':') ) {
	printf("usage: %s hostname:destination[,hostname1:port1,...hostnameN:portN]\n",$_SERVER['argv'][0]);
	return 1;
}
$dest=$_SERVER['argv'][1];

$jd=array();

$jd[$hostname]['title']='APRS @ Boswell Construction Camera';
$jd[$hostname]['sensors']=array();

$r = getNMEA0183nmeaReader('localhost',2627);
if ( NULL !== $r ) {
        foreach( $r as $key=>$value) {
                $jd[$hostname]['sensors'] += parseNMEA0183($value['sentence']);
        }
}


$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Packet Date','UTC',date("Y-m-d H:i:s"));
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Linux Uptime','',exec("uptime"));
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('802.11 Signal','',exec('iwconfig wlan0 | grep Quality | cut -c 11-'));


/* send data to broadcast server(s) */
print_r($jd);
sendDataTCP($dest,$jd);

?>
