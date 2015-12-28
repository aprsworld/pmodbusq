<?
function getW1Temp($filename) {
   if ( ! file_exists($filename) ) {
      return false;
   }

   $fp=fopen($filename,"r");
   $line[0]=fgets($fp,256);
   $line[1]=trim(fgets($fp,256));
   fclose($fp);

   /* check if we have valid CRC */
   if ( false === strpos($line[0],'YES') )  {
      return false;
   }

   $p=explode('=',$line[1]);

   /* $p[1] is temperature in thousandsths of a degree */
   if ( ! is_numeric($p[1]) ) {
      return false;
   }

   return ($p[1]/1000.0);
}
?>
