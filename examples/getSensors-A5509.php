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

$hostname='A5509';

if ( $_SERVER['argc'] != 2 || false === strpos($_SERVER['argv'][1],':') ) {
	printf("usage: %s hostname:destination[,hostname1:port1,...hostnameN:portN]\n",$_SERVER['argv'][0]);
	return 1;
}
$dest=$_SERVER['argv'][1];


/* query devices */
$ts_dump = ms_tristar_get_data('localhost',1);
$ts_dump = wsbroadcast_prefix_block($ts_dump,"DUMP_LOAD_","Dump Load: ");
$ts_dump = wsbroadcast_md5_block($ts_dump);

$tsmppt_solar_a = ms_tristarmppt_get_data('localhost',2);
$tsmppt_solar_a = wsbroadcast_prefix_block($tsmppt_solar_a,"SOLAR_A_","Solar A: ");
$tsmppt_solar_a = wsbroadcast_md5_block($tsmppt_solar_a);

$tsmppt_solar_b = ms_tristarmppt_get_data('localhost',3);
$tsmppt_solar_b = wsbroadcast_prefix_block($tsmppt_solar_b,"SOLAR_B_","Solar B: ");
$tsmppt_solar_b = wsbroadcast_md5_block($tsmppt_solar_b);

/* merge sensor data */
$sensors = array_merge($ts_dump,$tsmppt_solar_a,$tsmppt_solar_b);



if ( false ) {
	printf("# Dump:\n");
	print_r($ts_dump);
	printf("# Solar A:\n");
	print_r($tsmppt_solar_a);
	printf("# Solar B:\n");
	print_r($tsmppt_solar_b);
}

/* encode $r for broadcast as $jd[$hostname]['sensors'] */
$jd=array();
$jd[$hostname]['title']='SolaBlok Demo System';
$jd[$hostname]['sensors']=$sensors;


/* send data to broadcast server(s) */
print_r($jd);
sendDataTCP($dest,$jd);

?>
