<?
require_once '/var/www/cam/phpmodbus/ModbusMaster.php';

function setModbusRegisters($modbusHostname,$slaveAddress,$startRegister,$dataToWrite) {
	$modbus = new ModbusMaster($modbusHostname, "TCP");

	/* create an array with 'INT' strings to denote data types */
	$dataTypes=array_fill(0,count($dataToWrite),'INT');

	try {
		$modbus->writeMultipleRegister($slaveAddress, $startRegister, $dataToWrite, $dataTypes);
	} catch ( Exception $e ) {
		printf("Exception: %s\n",$e);
		return false;
	}

	return true;

}

function setModbusRegistersString($modbusHostname,$slaveAddress,$startRegister,$stringToWrite,$stripNull=true) {
	$a=array();

	for ( $i=0 ; $i<strlen($stringToWrite) ; $i++ ) {
		$a[$i]=ord(substr($stringToWrite,$i,1));
	}

	if ( ! $stripNull )
		$a[++$i]=0;

	return setModbusRegisters($modbusHostname,$slaveAddress,$startRegister,$a);
}

function getModbusRegisters($modbusHostname,$slaveAddress,$startRegister,$nRegisters) {
	/*
	printf("# getModbusRegisters(modbusHostname=%s, slaveAddress=%s, startRegister=%s, nRegisters=%s\n",
		$modbusHostname,
		$slaveAddress,
		$startRegister,
		$nRegisters
	);
	*/

	$modbus = new ModbusMaster($modbusHostname, "TCP");

	/* read registers */
	try {
		$result = $modbus->readMultipleRegisters($slaveAddress,$startRegister,$nRegisters);
	} catch ( Exception $e ) {
		printf("Exception: %s\n",$e);
		return array();
	}

	/* split into 1 word (2 byte) chunks */
	$result = array_chunk($result, 2); 

	/* word results */
	$r=array();

	for ( $i=0 ; $i<sizeof($result) ; $i++ ) {
		$r[$i+$startRegister]=PhpType::bytes2unsignedInt($result[$i]);  
	}

	return $r;
}

function getModbusRegistersAsString($modbusHostname,$slaveAddress,$startRegister,$nRegisters) {
	$r=getModbusRegisters($modbusHostname,$slaveAddress,$startRegister,$nRegisters);

	$s='';
	foreach ( $r as $val ) {
		$s .= chr($val);
	}

	return $s;
}

function getModbusRegistersPackedAsString($modbusHostname,$slaveAddress,$startRegister,$nRegisters) {
	$r=getModbusRegisters($modbusHostname,$slaveAddress,$startRegister,$nRegisters);

	$s='';
	foreach ( $r as $val ) {
		$hb=($val>>8)&0xff;
		$lb=$val&0xff;
		$s .= chr($hb);
		$s .= chr($lb);
	}

	return $s;
}

?>
