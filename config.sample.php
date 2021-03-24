<?php
/**
 * Add your credentials to this file and rename the file to config.inc.php afterwards.
 */

// Put your sub-domain ame here without the ".freedns.io" suffix (e.g., if your domain is "example.freedns.io" then put "example" here).
define('DYN_HOSTNAME', 'hostname');

// Put your username here.
define('DYN_USER', 'username');

// Put your password here.
define('DYN_PASS', 'password');

// Write a log file into the script's folder. Commented out, because it's also defined as fallback in log_msg() function.
//define('LOG_FILE', substr(__FILE__, 0, strrpos(__FILE__, '.php')) . '.log');
