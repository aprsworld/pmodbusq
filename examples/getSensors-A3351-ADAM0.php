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
$r = $r + getModbusRegisters('192.168.8.2:504',1,0,8);
//print_r($r);

$jd=array();

$jd[$dname]['ADAM0']=array();
/* ROUND((5/65536)*(^^analog0-1)*19.99663-0.00161,2) AS vBatt12 */
$vBatt12=(5/65536)*($r[0]-1)*19.99663-0.00161;
$jd[$dname]['ADAM0'] += pcwx_encodeForBroadcast('vBatt12','VDC',$vBatt12,'','',true);

/* ROUND((5/65536)*(^^analog1-1)*19.99813-0.00832,2) AS vBatt24 */
$vBatt24=(5/65536)*($r[1]-1)*19.99813-0.00832;
$jd[$dname]['ADAM0'] += pcwx_encodeForBroadcast('vBatt24','VDC',$vBatt24,'','',true);

/* ROUND((5/65536)*(^^analog2-1)*20.00126-0.00182,2) AS vBatt48 */
$vBatt48=(5/65536)*($r[2]-1)*20.00126-0.00182;
$jd[$dname]['ADAM0'] += pcwx_encodeForBroadcast('vBatt48','VDC',$vBatt48,'','',true);

/* ROUND(((10/65535)*(^^analog3-32768))*10.9375,3) AS iRectifier24_1 */
$iRectifier24_1 = ((10/65535)*($r[3]-32768))*10.9375;
$jd[$dname]['ADAM0'] += pcwx_encodeForBroadcast('iRectifier24_1','amps',$iRectifier24_1,'','',true);

/* ROUND(((10/65535)*(^^analog4-32768))*25.0000,3) AS iRectifier12_1 */
$iRectifier12_1 = ((10/65535)*($r[4]-32768))*25.0000;
$jd[$dname]['ADAM0'] += pcwx_encodeForBroadcast('iRectifier12_1','amps',$iRectifier12_1,'','',true);

/* ROUND(((10/65535)*(^^analog5-32768))*58.3333,3) AS iRectifier24_MPPT600 */
$iRectifier24_MPPT600=((10/65535)*($r[5]-32768))*58.3333;
$jd[$dname]['ADAM0'] += pcwx_encodeForBroadcast('iRectifier24_MPPT600','amps',$iRectifier24_MPPT600,'','',true);

/* power calculations */
$jd[$dname]['ADAM0'] += pcwx_encodeForBroadcast('pRectifier24_1','watts',$vBatt24 * $iRectifier24_1,'','',true);
$pRectifier12_1=$vBatt12 * $iRectifier12_1;
$jd[$dname]['ADAM0'] += pcwx_encodeForBroadcast('pRectifier12_1','watts',$pRectifier12_1,'','',true);
$jd[$dname]['ADAM0'] += pcwx_encodeForBroadcast('pRectifier24_MPPT600','watts',$vBatt24 * $iRectifier24_MPPT600,'','',true);

/*
    ROUND( ((5/65536)*(^^analog1-1)*19.99813-0.00832) * (((10/65535)*(^^analog3-32768))*10.9375) ,0) AS pRectifier24_1
    ROUND( ((5/65536)*(^^analog0-1)*19.99663-0.00161) * (((10/65535)*(^^analog4-32768))*25.0000) ,0) AS pRectifier12_1
    ROUND( ((5/65536)*(^^analog1-1)*19.99813-0.00832) * (((10/65535)*(^^analog5-32768))*58.3333) ,0) AS pRectifier24_MPPT600

*/


/* meta data */
$jd[$dname]['ADAM0'] += pcwx_encodeForBroadcast('Modbus Host','','192.168.8.2:504');


$jd[$dname]['ADAM0'] += pcwx_encodeForBroadcast('Packet Date','UTC',$datetime,'','',true);
$jd[$dname]['ADAM0'] += pcwx_encodeForBroadcast('Run Date','',$_SERVER['REQUEST_TIME_FLOAT'],'','',true);

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
$sql=sprintf("INSERT INTO powerPerformance (packet_date,power_time,vBatt,iRectifier,pOutput) VALUES('%s',%s,%s,%s,%s) ON DUPLICATE KEY UPDATE power_time=%s, vBatt=%s, iRectifier=%s, pOutput=%s",
	$datetime,
	$_SERVER['REQUEST_TIME_FLOAT'],
	$vBatt12,
	$iRectifier12_1,
	$pRectifier12_1,
	$_SERVER['REQUEST_TIME_FLOAT'],
	$vBatt12,
	$iRectifier12_1,
	$pRectifier12_1	
);
/* run query */
$res = mysqli_query($mysqli, $sql);


?>
