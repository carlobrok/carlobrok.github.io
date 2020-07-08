<?php
$content = file_get_contents("php://input");
$json    = json_decode($content, true);
$file    = fopen(LOGFILE, "a");
$time    = time();
$token   = false;
$sha     = false;
$DIR     = preg_match("/\/$/", DIR) ? DIR : DIR . "/";

$pTOKEN  = file_get_contents("token.txt", true);
$pTOKEN  = substr($pTOKEN, 0, -1);



// retrieve the token
if (!$token && isset($_SERVER["HTTP_X_HUB_SIGNATURE"])) {
    list($algo, $token) = explode("=", $_SERVER["HTTP_X_HUB_SIGNATURE"], 2) + array("", "");
} elseif (isset($_SERVER["HTTP_X_GITLAB_TOKEN"])) {
    $token = $_SERVER["HTTP_X_GITLAB_TOKEN"];
} elseif (isset($_GET["token"])) {
    $token = $_GET["token"];
}


// retrieve the checkout_sha
if (isset($json["checkout_sha"])) {
    $sha = $json["checkout_sha"];
} elseif (isset($_SERVER["checkout_sha"])) {
    $sha = $_SERVER["checkout_sha"];
} elseif (isset($_GET["sha"])) {
    $sha = $_GET["sha"];
}

// write the time to the log
date_default_timezone_set("UTC");
fputs($file, date("d-m-Y (H:i:s)", $time) . "\n");

// specify that the response does not contain HTML
header("Content-Type: text/plain");

// use user-defined max_execution_time
if (!empty(MAX_EXECUTION_TIME)) {
    ini_set("max_execution_time", MAX_EXECUTION_TIME);
}

// function to forbid access
function forbid($file, $reason) {
    // format the error
    $error = "=== ERROR: " . $reason . " ===\n*** ACCESS DENIED ***\n";

    // forbid
    http_response_code(403);

    // write the error to the log and the body
    fputs($file, $error . "\n\n");
    echo $error;

    // close the log
    fclose($file);

    // stop executing
    exit;
}

// Check for a GitHub signature
if (!empty($pTOKEN) && isset($_SERVER["HTTP_X_HUB_SIGNATURE"]) && $token !== hash_hmac($algo, $content, $pTOKEN)) {
    forbid($file, "X-Hub-Signature does not match TOKEN");
// Check for a GitLab token
} elseif (!empty($pTOKEN) && isset($_SERVER["HTTP_X_GITLAB_TOKEN"]) && $token !== $pTOKEN) {
    forbid($file, "X-GitLab-Token does not match TOKEN");
// Check for a $_GET token
} elseif (!empty($pTOKEN) && isset($_GET["token"]) && strcmp($token,$pTOKEN) !== 0) {
    forbid($file, "\$_GET[\"token\"] does not match TOKEN");
// if none of the above match, but a token exists, exit
} elseif (!empty($pTOKEN) && !isset($_SERVER["HTTP_X_HUB_SIGNATURE"]) && !isset($_SERVER["HTTP_X_GITLAB_TOKEN"]) && !isset($_GET["token"])) {
    forbid($file, "No token detected");
} else {
    shell_exec("./update.sh");
    echo "Successfully updated repo";
}

// close the log
fputs($file, "\n\n" . PHP_EOL);
fclose($file);
