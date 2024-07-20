#!/usr/bin/php -q
<?
require_once dirname(__FILE__) . '/../pmodbusq.php';
require 'lib/lib.modbus.php';
require 'lib/lib.comwintop.uwd.php';
require 'lib/lib.pcwx.php';
require 'lib/lib.senddata.php';

$hostname=gethostname();
if ( 'cam' == substr($hostname,0,3) ) {
	$hostname=substr($hostname,3);
}

$hostname='A5575';

if ( $_SERVER['argc'] != 2 || false === strpos($_SERVER['argv'][1],':') ) {
	printf("usage: %s hostname:destination[,hostname1:port1,...hostnameN:portN]\n",$_SERVER['argv'][0]);
	return 1;
}
$dest=$_SERVER['argv'][1];


/* query devices */
$cwd_uwd = comwintop_uwd_get_data('localhost',1);

/* apply local barometer correction */
$atmo_corrected=$cwd_uwd['ATMOSPHERIC_PRESSURE']['value'] + 2.23; /* from NWS AWOS station on 2024-07-20 */



/* encode $r for broadcast as $jd[$hostname]['sensors'] */
$jd=array();
$jd[$hostname]['title']='Madeline Island: Bank Weather Station';
$jd[$hostname]['sensors']=$cwd_uwd;
comwintop_uwd_add_result($jd[$hostname]['sensors'],'BAROMETER_MBAR','Barometer','mb', $atmo_corrected*10.0);
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('Packet Date','UTC',date("Y-m-d H:i:s"));
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('System Uptime','',exec("uptime"));
$jd[$hostname]['sensors'] += pcwx_encodeForBroadcast('802.11 Signal','',exec("iwconfig wlan0 | grep Quality | cut -c 11-"));


/* send data to broadcast server(s) */
print_r($jd);
sendDataTCP($dest,$jd);

?>
