#!/usr/bin/php -q
<?
require_once dirname(__FILE__) . '/../pmodbusq.php';
require 'lib/lib.senddata.php';

/* arguments
1 unix timestamp
2 fullsize image
3 thumbnail image
*/

$hostname=gethostname();
if ( 'cam' == substr($hostname,0,3) ) {
	$hostname=substr($hostname,3);
}

if ( $_SERVER['argc'] != 4  ) {
	printf("usage: %s unixTimeStamp fullSizeFile thumbnailFile\n",$_SERVER['argv'][0]);
	return 1;
}
$dest="localhost:1229";


$jd=array();

$jd[$hostname]['cameras']=array();
$jd[$hostname]['cameras']['image_url']=sprintf("/cam/latest/latest.jpg");
$jd[$hostname]['cameras']['image_size']=filesize($_SERVER['argv'][2]);
$jd[$hostname]['cameras']['source_serial']=$hostname;
/* source_ip_addr, source_ip_port */

/* send data to broadcast server(s) */
print_r($jd);
sendDataTCP($dest,$jd);

?>
