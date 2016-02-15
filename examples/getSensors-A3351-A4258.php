#!/usr/bin/php -q
<?
set_time_limit(2);

require_once dirname(__FILE__) . '/../pmodbusq.php';
require_once 'lib/lib.modbus.php';
require_once 'lib/lib.pcwx.php';
require_once 'lib/lib.xrw2g.php';
require_once 'lib/lib.senddata.php';

$dname='A3351';

if ( $_SERVER['argc'] != 3 || false === strpos($_SERVER['argv'][2],':') ) {
	printf("usage: %s datetime hostname:destination[,hostname1:port1,...hostnameN:portN]\n",$_SERVER['argv'][0]);
	return 1;
}
$datetime=$_SERVER['argv'][1];
$dest=$_SERVER['argv'][2];

/* modbus registers read from XRW2G */
$r=array();

/* current value registers */
$r = $r + getModbusRegisters('192.168.8.2:505',24,0,46);
print_r($r);

$jd=array();

$jd[$dname]['A4258']=array();
/* first class anemometer on big tower */
$wa0=pcwx_anemometer_average(0.0462,0.21,$r[0],$r[43]/100.0);
$ws0=pcwx_anemometer(0.0462,0.21,$r[1]);
$wg0=pcwx_anemometer(0.0462,0.21,$r[2]);
$wc0=$r[0];
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Average (Primary)','m/s',$wa0,'speed','m/s',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Speed (Primary)','m/s',$ws0,'speed','m/s',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Gust (Primary)','m/s',$wg0,'speed','m/s',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Count (Primary)','',$wc0,'','',true);

/* NRG #40HC on big tower */
$wa1=pcwx_anemometer_average(0.765,0.35,$r[6],$r[43]/100.0);
$ws1=pcwx_anemometer(0.765,0.35,$r[7]);
$wg1=pcwx_anemometer(0.765,0.35,$r[8]);
$wc1=$r[6];
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Average (Secondary)','m/s',$wa1,'speed','m/s',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Speed (Secondary)','m/s',$ws1,'speed','m/s',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Gust (Secondary)','m/s',$wg1,'speed','m/s',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Count (Secondary)','',$wc1,'','',true);

/* turbine on big tower */
$rpma=pcwx_anemometer_average(12.0,0.0,$r[12],$r[43]/100.0);
$rpms=pcwx_anemometer(12.0,0.0,$r[13]);
$rpmg=pcwx_anemometer(12.0,0.0,$r[14]);
$rpmc=$r[12];
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Turbine Rotor Average','RPM',$rpma,'','',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Turbine Rotor Speed','RPM',$rpms,'','',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Turbine Rotor Gust','RPM',$rpmg,'','',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Turbine Rotor Count','',$rpmc,'','',true);

/* wind vane on big tower */
/* MOD(ROUND((^^analog1Current/4096.0)*360.0+145.0,0),360) AS windDirection */
$wd=(($r[21]*XR2G_VIN_V_PER_BIT)*360.0 + 145.0) % 360;
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Wind Direction','&deg;',$wd,'','',true);


$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Supply Voltage','volts',$r[19]*XR2G_VIN_V_PER_BIT);
$temperatureAmbient=$r[28]*XRW2G_5VOLT_V_PER_BIT*100.0-273.15;
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Ambient Temperature','&deg;C',$temperatureAmbient,'temperature','c',true);

/* meta data */
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Modbus Host','','192.168.8.2:505');


$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Sequence Number','',$r[42],'','',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Ticks','seconds',$r[43]/100.0,'','',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Uptime','minutes',$r[44],'','',true);

$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Packet Date','UTC',$datetime,'','',true);
$jd[$dname]['A4258'] += pcwx_encodeForBroadcast('Run Date','',$_SERVER['REQUEST_TIME_FLOAT'],'','',true);

/* send data to broadcast server(s) */
print_r($jd);
sendDataTCP($dest,$jd);

/* 
add to powerPeformance table 

mysql> describe powerPerformance;
+--------------------+------------+------+-----+---------+-------+
| Field              | Type       | Null | Key | Default | Extra |
+--------------------+------------+------+-----+---------+-------+
| packet_date        | datetime   | NO   | PRI | NULL    |       |
| wx_time            | double     | YES  |     | NULL    |       |
| windAverage0       | float      | YES  |     | NULL    |       |
| windSpeed0         | float      | YES  |     | NULL    |       |
| windGust0          | float      | YES  |     | NULL    |       |
| windCount0         | int(11)    | YES  |     | NULL    |       |
| windAverage1       | float      | YES  |     | NULL    |       |
| windSpeed1         | float      | YES  |     | NULL    |       |
| windGust1          | float      | YES  |     | NULL    |       |
| windCount1         | int(11)    | YES  |     | NULL    |       |
| rpmAverage         | int(11)    | YES  |     | NULL    |       |
| rpmSpeed           | int(11)    | YES  |     | NULL    |       |
| rpmGust            | int(11)    | YES  |     | NULL    |       |
| rpmCount           | int(11)    | YES  |     | NULL    |       |
| windDirection      | int(11)    | YES  |     | NULL    |       |
| temperatureAmbient | float      | YES  |     | NULL    |       |
| power_time         | double     | YES  |     | NULL    |       |
| vBatt              | float      | YES  |     | NULL    |       |
| iRectifier         | float      | YES  |     | NULL    |       |
| pOutput            | float      | YES  |     | NULL    |       |
| valid              | tinyint(1) | YES  |     | NULL    |       |
+--------------------+------------+------+-----+---------+-------+
21 rows in set (0.00 sec)
*/

/* connect to local mysql server */
$mysqli = mysqli_connect('localhost','ppInsert','','powerPerformance');
/* build SQL query. Using the ON DUPLICATE KEY UPDATE syntax we can come first or second for this mesurement */
$sql=sprintf("INSERT INTO powerPerformance (packet_date,wx_time,windAverage0,windSpeed0,windGust0,windCount0,windAverage1,windSpeed1,windGust1,windCount1,rpmAverage,rpmSpeed,rpmGust,rpmCount,windDirection,temperatureAmbient) VALUES('%s',%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s) ON DUPLICATE KEY UPDATE wx_time=%s, windAverage0=%s, windSpeed0=%s, windGust0=%s, windCount0=%s, windAverage1=%s, windSpeed1=%s, windGust1=%s, windCount1=%s, rpmAverage=%s, rpmSpeed=%s, rpmGust=%s, rpmCount=%s, windDirection=%s, temperatureAmbient=%s",
	$datetime,
	$_SERVER['REQUEST_TIME_FLOAT'],
	$wa0, $ws0, $wg0, $wc0,
	$wa1, $ws1, $wg1, $wc1,
	$rpma, $rpms, $rpmg, $rpmc,
	$wd,
	$temperatureAmbient,
	$_SERVER['REQUEST_TIME_FLOAT'],
	$wa0, $ws0, $wg0, $wc0,
	$wa1, $ws1, $wg1, $wc1,
	$rpma, $rpms, $rpmg, $rpmc,
	$wd,
	$temperatureAmbient
);
echo $sql;
/* run query */
$res = mysqli_query($mysqli, $sql);


?>

?>
