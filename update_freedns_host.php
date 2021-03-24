<?php
/*
     The FreeDns.io project
     This script updates an A record to the client's current IP address.
	 
	 @author nrekow
*/

require_once 'config.inc.php';

// Run the update process
update_dyndns();



/**
 * Log messages
 *
 * @param string $msg
 * @param boolean $is_fatal
 */
function log_msg($msg, $is_fatal = false) {
	// Create a log file in the script's folder unless defined otherwise.
	if (!defined('LOG_FILE')) {
		define('LOG_FILE', substr(__FILE__, 0, strrpos(__FILE__, '.php')) . '.log');
	}
	
	error_log('[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", 3, LOG_FILE);
	if ($is_fatal !== false) {
		die();
	}
}


/**
 * Check if a given string is a valid IPv4 and skip local IP addresses (e.g., 127.0.0.1, ...).
 * 
 * @param string $ip
 * @return mixed
 */
function is_valid_ip($ip) {
	return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE| FILTER_FLAG_IPV4);
}


/**
 * Get client's IP address.
 * 
 * @return string
 */
function get_ip() {
	$ip = '';
	
	// We need to validate each value or otherwise some else-clauses might be skipped.
	if (isset($_SERVER['HTTP_CLIENT_IP']) && is_valid_ip($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && is_valid_ip($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else if (isset($_SERVER['REMOTE_ADDR']) && is_valid_ip($_SERVER['REMOTE_ADDR'])) {
		$ip = $_SERVER['REMOTE_ADDR'];
	} else {
		// In case no valid address could be found, try to get your current IP from my external service.
		$ip = file_get_contents('https://ip.rekow.ch');
	}
	
	// Check if the found IP address is really valid.
	if (is_valid_ip($ip) === false) {
		log_msg('Invalid IP address: ' . $ip);
		die();
	}
	
	return $ip;
}


/**
 * Update a freedns.io domain name with the current IP.
 * 
 * @param boolean $ip
 * @param string $record
 */
function update_dyndns($record = 'A', $ip = false) {
	// Get client's IP address.
	if (!$ip) {
		$ip = get_ip();
	}
	
	$data = array(
			'username' => DYN_USER,
			'password' => DYN_PASS,
			'host' => DYN_HOSTNAME,
			'record' => $record,
			'value' => $ip
	);
	
	$http_response_header = array();
	
	$options = array(
			'http' => array(
					'header' => "Content-type: application/x-www-form-urlencoded\r\n",
					'method' => 'POST',
					'ignore_errors' => true, // This one's important if you want to read the response status message from the server even in the case of error
					'content' => http_build_query($data),
			),
	);
	
	$url = 'https://freedns.io/request';
	
	$context = stream_context_create($options);
	$result = file_get_contents($url, false, $context); // Will contain the response status message
	$retcode = $http_response_header[0];                // Will contain the HTML-style response code (401 on errors, 200 on success). Gets filled by file_get_contents().
	
	log_msg(DYN_HOSTNAME . '.freedns.io -> ' . $ip . ': ' . $result . ' (' . $retcode . ')');
}
