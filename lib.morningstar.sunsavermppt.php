<?
function scaleSigned($adc, $scale, $power) {
	if ( $adc >= 32768 ) {
		$adc=$adc-65536;
	}

	return ((double) $adc*$scale) / pow(2,$power);
}

function tsmppt_scale($whole, $fraction) {
	return $whole + $fraction/65536.0;
}
?>
