<?
function sendDataTCP($broadcastHost,$data) {
	// Wrap the JSON object and convert it to a string
	$js = json_encode($data);
//	printf("#sendDataTCP(JSON: %s)\n", $js);

	$bhost=explode(',',$broadcastHost);

	print_r($bhost);


	for ( $i=0 ; $i<count($bhost) ; $i++ ) {
		$json_string = $js;

		$p=explode(':',$bhost[$i]);
		$hostname=$p[0];
		$port=$p[1];

		printf("# connecting to %s:%d\n",$hostname,$port);

		// Create a TCP socket
		$sock_update = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if ($sock_update == FALSE) {
			printf("ERROR: Could not create socket - %s", socket_strerror(socket_last_error($sock_update)));
			continue;
		}

		// Connect to the wsBroadcast Server
		if (!socket_connect($sock_update, $hostname, $port)) {
			printf("ERROR: Could not connect - %s", socket_strerror(socket_last_error($sock_update)));
			socket_close($sock_update);
			continue;
		}

		// Send the JSON string over the TCP connection
		while (strlen($json_string) > 0) {
			$ret = socket_write($sock_update, $json_string, strlen($json_string));

			// Error
			if ($ret == FALSE) {
				printf("ERROR: Could not write to socket - %s", socket_strerror(socket_last_error($sock_update)));
				socket_close($sock_update);
				continue;
			}

			// Trim string to what's left to send
			$json_string = substr($json_string, $ret);
		}

		// Send Message Terminator (Currently a newline)
		while (($ret = socket_write($sock_update, "\n", 1)) != 1) {

			// Error
			if ($ret == FALSE) {
				printf("ERROR: Could not write to socket - %s", socket_strerror(socket_last_error($sock_update)));
				$socket_close($sock_update);
				continue;
			}
		}

		// Close the TCP Connection and be done.
		socket_close($sock_update);

		printf("# socket for %s:%d closed\n",$hostname,$port);
	}

	return true;
}

?>
