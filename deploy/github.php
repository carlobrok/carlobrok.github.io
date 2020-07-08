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


/*
 * Copied from https://gist.github.com/gka/4627519  github ips edited
 *
 * Endpoint for Github Webhook URLs
 *
 * see: https://help.github.com/articles/post-receive-hooks
 *
 */

// script errors will be send to this email:
$error_mail = "bimmelmonn@gmail.com";

function run() {
    global $rawInput;

    // read config.json
    $config_filename = '/home/ubuntu/website_config/deploy_config.json';
    if (!file_exists($config_filename)) {
        throw new Exception("Can't find ".$config_filename);
    }
    $config = json_decode(file_get_contents($config_filename), true);

    $postBody = $_POST['payload'];
    $payload = json_decode($postBody);

    if (isset($config['email'])) {
        $headers = 'From: '.$config['email']['from']."\r\n";
        $headers .= 'CC: ' . $payload->pusher->email . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
    }

    // check if the request comes from github server    ->    see: https://api.github.com/meta
    $github_ips = array('192.30.252.0/22', '185.199.108.0/22', '140.82.112.0/20');
    if (ip_in_range($_SERVER['REMOTE_ADDR'], $github_ips)) {
        foreach ($config['endpoints'] as $endpoint) {
            // check if the push came from the right repository and branch
            if ($payload->repository->url == 'https://github.com/' . $endpoint['repo']
                && $payload->ref == 'refs/heads/' . $endpoint['branch']) {

                // execute update script, and record its output
                ob_start();
                passthru($endpoint['run']);
                $output = ob_end_contents();

                // prepare and send the notification email
                if (isset($config['email'])) {
                    // send mail to someone, and the github user who pushed the commit
                    $body = '<p>The Github user <a href="https://github.com/'
                    . $payload->pusher->name .'">@' . $payload->pusher->name . '</a>'
                    . ' has pushed to ' . $payload->repository->url
                    . ' and consequently, ' . $endpoint['action']
                    . '.</p>';

                    $body .= '<p>Here\'s a brief list of what has been changed:</p>';
                    $body .= '<ul>';
                    foreach ($payload->commits as $commit) {
                        $body .= '<li>'.$commit->message.'<br />';
                        $body .= '<small style="color:#999">added: <b>'.count($commit->added)
                            .'</b> &nbsp; modified: <b>'.count($commit->modified)
                            .'</b> &nbsp; removed: <b>'.count($commit->removed)
                            .'</b> &nbsp; <a href="' . $commit->url
                            . '">read more</a></small></li>';
                    }
                    $body .= '</ul>';
                    $body .= '<p>What follows is the output of the script:</p><pre>';
                    $body .= $output. '</pre>';
                    $body .= '<p>Cheers, <br/>Github Webhook Endpoint</p>';

                    mail($config['email']['to'], $endpoint['action'], $body, $headers);
                }
                return true;
            }
        }
    } else {
        throw new Exception("This does not appear to be a valid requests from Github.\n");
    }
}

try {
    echo isset($_POST['payload']);
    if (!isset($_POST['payload'])) {
        echo "Works fine.";
    } else {
        run();
    }
} catch ( Exception $e ) {
    $msg = $e->getMessage();
    mail($error_mail, $msg, ''.$e);
}
