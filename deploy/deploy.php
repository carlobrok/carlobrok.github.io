<?php

/**
 * Copied from https://gist.github.com/tott/7684443 and modified to work with an array
 *
 * Check if a given ip is in a network
 * @param  string $ip          IP to check in IPV4 format eg. 127.0.0.1
 * @param  string $range_ips   list of IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed
 * @return boolean true if the ip is in this range / false if not.
 */

function ip_in_range( $ip, $range_ips ) {
	foreach ($range_ips as $range) {
		if ( strpos( $range, '/' ) == false ) {
		 $range .= '/32';
		}
		// $range is in IP/CIDR format eg 127.0.0.1/24
		list( $range, $netmask ) = explode( '/', $range, 2 );
		$range_decimal = ip2long( $range );
		$ip_decimal = ip2long( $ip );
		$wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
		$netmask_decimal = ~ $wildcard_decimal;
		if( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) ) {
			return true;
		}
	}
	return false;
}

$github_ips = array('192.30.252.0/22', '185.199.108.0/22', '140.82.112.0/20');


/**
 *  The MIT License

 * Copyright (c) 2013 Marko Markovic <okram (at) civokram (dot) com>

 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:

 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.

 * Simple PHP Git deploy script
 *
 * Automatically deploy the code using PHP and Git.
 *
 * @version 1.3.1
 * @link    https://github.com/markomarkovic/simple-php-git-deploy/
 */

// =========================================[ Configuration start ]===

/**
 * It's preferable to configure the script using `deploy-config.php` file.
 *
 * Rename `deploy-config.example.php` to `deploy-config.php` and edit the
 * configuration options there instead of here. That way, you won't have to edit
 * the configuration again if you download the new version of `deploy.php`.
 */
if (file_exists(basename(__FILE__, '.php').'-config.php')) {
	define('CONFIG_FILE', basename(__FILE__, '.php').'-config.php');
	require_once CONFIG_FILE;
} else {
	define('CONFIG_FILE', __FILE__);
}

/**
 * Protect the script from unauthorized access by using a secret access token.
 * If it's not present in the access URL as a GET variable named `sat`
 * e.g. deploy.php?sat=Bett...s the script is not going to deploy.
 *
 * @var string
 */
if (!defined('SECRET_ACCESS_TOKEN')) define('SECRET_ACCESS_TOKEN', 'DoYouReallyThinkImSoStupid');

/**
 * The address of the remote Git repository that contains the code that's being
 * deployed.
 * If the repository is private, you'll need to use the SSH address.
 *
 * @var string
 */
if (!defined('REMOTE_REPOSITORY')) define('REMOTE_REPOSITORY', 'https://github.com/carlobrok/website.git');

/**
 * The branch that's being deployed.
 * Must be present in the remote repository.
 *
 * @var string
 */
if (!defined('BRANCH')) define('BRANCH', 'master');

/**
 * The location that the code is going to be deployed to.
 * Don't forget the trailing slash!
 *
 * @var string Full path including the trailing slash
 */
if (!defined('TARGET_DIR')) define('TARGET_DIR', '/tmp/website/');

/**
 * Time limit for each command.
 *
 * @var int Time in seconds
 */
if (!defined('TIME_LIMIT')) define('TIME_LIMIT', 30);

/**
 * OPTIONAL
 * Email address to be notified on deployment failure.
 *
 * @var string A single email address, or comma separated list of email addresses
 *      e.g. 'someone@example.com' or 'someone@example.com, someone-else@example.com, ...'
 */
if (!defined('EMAIL_ON_ERROR')) define('EMAIL_ON_ERROR', false);

// ===========================================[ Configuration end ]===

$content = file_get_contents("php://input");
$token = false;

if (!$token && isset($_SERVER["HTTP_X_HUB_SIGNATURE"])) {
    list($algo, $token) = explode("=", $_SERVER["HTTP_X_HUB_SIGNATURE"], 2) + array("", "");
}


ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="robots" content="noindex">
	<title>Simple PHP Git deploy script</title>
	<style>
body { padding: 0 1em; background: #222; color: #fff; }
.error { color: #c33; }
.prompt { color: #6be234; }
.command { color: #729fcf; }
.output { color: #999; }
	</style>
</head>
<body>
<?php


// If there's authorization error or the ip doesn't belong to github, set the correct HTTP header.
if ((!empty(SECRET_ACCESS_TOKEN) && isset($_SERVER["HTTP_X_HUB_SIGNATURE"]) && $token !== hash_hmac($algo, $content, SECRET_ACCESS_TOKEN)) || !isset($_SERVER["HTTP_X_HUB_SIGNATURE"]) || !ip_in_range($_SERVER['REMOTE_ADDR'], $github_ips)) {
	header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
	die('<h2>Access Denied!</h2>');
}

?>
<pre>

Accepted access key.

Checking the environment ...

Running as <b><?php echo trim(shell_exec('whoami')); ?></b>.

<?php
// Check if the required programs are available
$requiredBinaries = array('git');

foreach ($requiredBinaries as $command) {
	$path = trim(shell_exec('which '.$command));
	if ($path == '') {
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		die(sprintf('<div class="error"><b>%s</b> not available. It needs to be installed on the server for this script to work.</div>', $command));
	} else {
		$version = explode("\n", shell_exec($command.' --version'));
		printf('<b>%s</b> : %s'."\n"
			, $path
			, $version[0]
		);
	}
}
?>

Environment OK.

Using configuration defined in <?php echo CONFIG_FILE."\n"; ?>

Deploying <?php echo REMOTE_REPOSITORY; ?> <?php echo BRANCH."\n"; ?>
to        <?php echo TARGET_DIR; ?> ...

<?php
// The commands
$commands = array();

// ======================== HARD-RESET REPO AND PULL NEW CODE ========

if (is_dir(TARGET_DIR)) {
  $commands[] = sprintf(
    'cd %s'
    , TARGET_DIR
  );
  $commands[] = sprintf(
    'git reset --hard'
  );
  $commands[] = sprintf(
    'git pull'
  );
}

// =======================================[ Run the command steps ]===
$output = '';
foreach ($commands as $command) {
	set_time_limit(TIME_LIMIT); // Reset the time limit for each command

	$tmp = array();
	exec($command.' 2>&1', $tmp, $return_code); // Execute the command
	// Output the result
	printf('
<span class="prompt">$</span> <span class="command">%s</span>
<div class="output">%s</div>
'
		, htmlentities(trim($command))
		, htmlentities(trim(implode("\n", $tmp)))
	);
	$output .= ob_get_contents();
	ob_flush(); // Try to output everything as it happens

	// Error handling and cleanup
	if ($return_code !== 0) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		printf('
<div class="error">
Error encountered!
Stopping the script to prevent possible data loss.
CHECK THE DATA IN YOUR TARGET DIR!
</div>
'
		);

		$error = sprintf(
			'Deployment error on %s using %s!'
			, $_SERVER['HTTP_HOST']
			, __FILE__
		);
		error_log($error);
		if (EMAIL_ON_ERROR) {
			$output .= ob_get_contents();
			$headers = array();
			$headers[] = sprintf('From: Simple PHP Git deploy script <simple-php-git-deploy@%s>', $_SERVER['HTTP_HOST']);
			$headers[] = sprintf('X-Mailer: PHP/%s', phpversion());
			mail(EMAIL_ON_ERROR, $error, strip_tags(trim($output)), implode("\r\n", $headers));
		}
		break;
	}
}
?>

Done.
</pre>
</body>
</html>
