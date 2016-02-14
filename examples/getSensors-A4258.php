#!/usr/bin/php -q
<?
require_once dirname(__FILE__) . '/../pmodbusq.php';
require_once 'lib/lib.modbus.php';
require_once 'lib/lib.pcwx.php';
require_once 'lib/lib.xrw2g.php';
require_once 'lib/lib.senddata.php';

$dname='A3351';

if ( $_SERVER['argc'] != 2 || false === strpos($_SERVER['argv'][1],':') ) {
	printf("usage: %s hostname:destination[,hostname1:port1,...hostnameN:portN]\n",$_SERVER['argv'][0]);
	return 1;
}
$dest=$_SERVER['argv'][1];

/* modbus registers read from XRW2G */
$r=array();

/* current value registers */
$r = $r + getModbusRegisters('192.168.8.2:505',24,0,46);
print_r($r);

$jd=array();

$jd[$dname]['A4258']=array();
/* first class anemometer on big tower */
$ws=pcwx_anemometer(0.0462,0.21,$r[1]);
$wg=pcwx_anemometer(0.0462,0.21,$r[2]);
$wa=pcwx_anemometer_average(0.0462,0.21,$r[0],$r[43]/100.0);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Speed (Primary)','m/s',$ws,'speed','m/s',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Gust (Primary)','m/s',$wg,'speed','m/s',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Average (Primary)','m/s',$wa,'speed','m/s',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Count (Primary)','',$r[0],'','',true);

/* NRG #40HC on big tower */
$ws=pcwx_anemometer(0.765,0.35,$r[7]);
$wg=pcwx_anemometer(0.765,0.35,$r[8]);
$wa=pcwx_anemometer_average(0.765,0.35,$r[6],$r[43]/100.0);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Speed (Secondary)','m/s',$ws,'speed','m/s',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Gust (Secondary)','m/s',$wg,'speed','m/s',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Average (Secondary)','m/s',$wa,'speed','m/s',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Count (Secondary)','',$r[6],'','',true);

/* turbine on big tower */
$rpm=pcwx_anemometer(12.0,0.0,$r[7]);
$rpmGust=pcwx_anemometer(12.0,0.0,$r[8]);
$rpmAverage=pcwx_anemometer_average(12.0,0.0,$r[6],$r[43]/100.0);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Turbine Rotor Speed','RPM',$rpm,'','',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Turbine Rotor Gust','RPM',$rpmGust,'','',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Turbine Rotor Average','RPM',$rpmAverage,'','',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Turbine Rotor Count','',$r[6],'','',true);

/* wind vane on big tower */
/* MOD(ROUND((^^analog1Current/4096.0)*360.0+145.0,0),360) AS windDirection */
$wd=(($r[21]*XR2G_VIN_V_PER_BIT)*360.0 + 145.0) % 360;
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Direction','&deg;',$wd,'','',true);


$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Supply Voltage','volts',$r[19]*XR2G_VIN_V_PER_BIT);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Ambient Temperature','&deg;C',$r[28]*XRW2G_5VOLT_V_PER_BIT*100.0-273.15,'temperature','c',true);

/* meta data */
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Modbus Host','','192.168.8.2:505');


$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Sequence Number','',$r[42],'','',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Ticks','seconds',$r[43]/100.0,'','',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Uptime','minutes',$r[44],'','',true);

$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Packet Date','UTC',date("Y-m-d H:i:s"),'','',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Run Date','',$_SERVER['REQUEST_TIME_FLOAT'],'','',true);

/* send data to broadcast server(s) */
print_r($jd);
sendDataTCP($dest,$jd);

?>
