<?php
date_default_timezone_set('America/Los_Angeles');

http_response_code(200);

// work with get or post
$request = array_merge($_GET, $_POST);

$logFile = fopen('logs/texts.txt', 'a');
$str = "Ping:" . json_encode($request) . "\n";
fputs($logFile, $str);
fclose($logFile);

// Check that this is a delivery receipt.
if (!isset($request['messageId']) OR !isset($request['status'])) {
    error_log('This is not a delivery receipt');
    return;
}

//Check if your message has been delivered correctly.
if ($request['status'] == 'delivered') {
    error_log("Your message to {$request['msisdn']} (message id {$request['messageId']}) was delivered.");
    error_log("The cost was {$request['price']}.");
} elseif ($request['status'] == 'accepted') {
    error_log("Your message to {$request['msisdn']} (message id {$request['messageId']}) was accepted by the carrier.");
    error_log("The cost was {$request['price']}.");
} else {
    error_log("Your message to {$request['msisdn']} has a status of: {$request['status']}.");
    error_log("Check err-code {$request['err-code']} against the documentation.");
}
