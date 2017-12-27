<?php

// DEBUG SETUP
$debug = true;
$debug_file = "./webhook.log";

require_once('../twp_util.php');


// Get the various X-Header values
$http_event = $_SERVER['HTTP_X_PROJECTS_EVENT'];
$http_signature = $_SERVER['HTTP_X_PROJECTS_SIGNATURE'];
$http_delivery = $_SERVER['HTTP_X_PROJECTS_DELIVERY'];
wdebug("twp_comment_updated: Event", $http_event);
wdebug("twp_comment_updated: Signature", $http_signature);

// Get the Webhook Payload - it is in JSON
$wh_payload = file_get_contents('php://input');

// Convert the Payload from a JSON String into an object
$wh_data = json_decode($wh_payload);
wdebug("twp_comment_updated: JSON", $wh_data);

$payloadValid = isPayloadValid($wh_payload, $http_signature);
wdebug("twp_comment_updated: payloadValid", $payloadValid);

// We have a valid payload, we can continue
if ($payloadValid) {
  
}

echo("twp_comment_updated<br />");

