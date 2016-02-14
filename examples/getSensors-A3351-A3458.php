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
$r = $r + getModbusRegisters('192.168.8.2:503',90,0,46);
print_r($r);

$jd=array();

$jd[$dname]['A3458']=array();
/* anemometer on top of container */
$ws=pcwx_anemometer(0.765,0.350,$r[1]);
$wg=pcwx_anemometer(0.765,0.350,$r[2]);
$wa=pcwx_anemometer_average(0.765,0.350,$r[0],$r[43]/100.0);
$jd[$dname]['A3458'] += pcwx_encodeForBroadcast('Wind Speed','m/s',$ws,'speed','m/s',true);
$jd[$dname]['A3458'] += pcwx_encodeForBroadcast('Wind Gust','m/s',$wg,'speed','m/s',true);
$jd[$dname]['A3458'] += pcwx_encodeForBroadcast('Wind Average','m/s',$wa,'speed','m/s',true);
$jd[$dname]['A3458'] += pcwx_encodeForBroadcast('Wind Count','',$r[0],'','',true);

/* top of container / purple wires wind turbine */
$rpm=pcwx_anemometer(12.0,0.0,$r[7]);
$rpmGust=pcwx_anemometer(12.0,0.0,$r[8]);
$rpmAverage=pcwx_anemometer_average(12.0,0.0,$r[6],$r[43]/100.0);
$jd[$dname]['A3458'] += pcwx_encodeForBroadcast('Container Turbine Rotor Speed','RPM',$rpm,'','',true);
$jd[$dname]['A3458'] += pcwx_encodeForBroadcast('Container Turbine Rotor Gust','RPM',$rpmGust,'','',true);
$jd[$dname]['A3458'] += pcwx_encodeForBroadcast('Container Turbine Rotor Average','RPM',$rpmAverage,'','',true);
$jd[$dname]['A3458'] += pcwx_encodeForBroadcast('Container Turbine Rotor Count','',$r[6],'','',true);



$jd[$dname]['A3458'] += pcwx_encodeForBroadcast('Supply Voltage','volts',$r[19]*XR2G_VIN_V_PER_BIT);
$jd[$dname]['A3458'] += pcwx_encodeForBroadcast('12V Rectifier 1 Temperature','&deg;C',$r[28]*XRW2G_5VOLT_V_PER_BIT*100.0-273.15,'temperature','c',true);
$jd[$dname]['A3458'] += pcwx_encodeForBroadcast('Matrix Open Circuit Voltage','VDC',$r[31]*XRW2G_5VOLT_V_PER_BIT*20.0);

/* meta data */
$jd[$dname]['A3458'] += pcwx_encodeForBroadcast('Modbus Host','','192.168.8.2:503');


$jd[$dname]['A3458'] += pcwx_encodeForBroadcast('Sequence Number','',$r[42],'','',true);
$jd[$dname]['A3458'] += pcwx_encodeForBroadcast('Ticks','seconds',$r[43]/100.0,'','',true);
$jd[$dname]['A3458'] += pcwx_encodeForBroadcast('Uptime','minutes',$r[44],'','',true);

$jd[$dname]['A3458'] += pcwx_encodeForBroadcast('Packet Date','UTC',date("Y-m-d H:i:s"),'','',true);
$jd[$dname]['A3458'] += pcwx_encodeForBroadcast('Run Date','',$_SERVER['REQUEST_TIME_FLOAT'],'','',true);

/* send data to broadcast server(s) */
print_r($jd);
sendDataTCP($dest,$jd);

?>
