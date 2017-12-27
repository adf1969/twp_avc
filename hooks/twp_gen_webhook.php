<?php


  // DEBUG SETUP
  $debug = true;
  $debug_file = "./webhook.log";
  
  
  require_once('../twp_util.php');
  use \TeamWorkPm\Factory as TeamWorkPm;
  use \TeamWorkPm\Auth;
  use \avc\Factory as Avc;

  $old_error_handler = set_error_handler("avcErrorHandler");
    
try {
  // Hooks are added here:
  // Go to the Company, the select Settings menu in upper Right, then Webhooks 
  // https://avctw.teamwork.com/#settings/webhooks


  // Get the various X-Header values
  $http_event = $_SERVER['HTTP_X_PROJECTS_EVENT'];
  $http_signature = $_SERVER['HTTP_X_PROJECTS_SIGNATURE'];
  $http_delivery = $_SERVER['HTTP_X_PROJECTS_DELIVERY'];
  wdebug("twp_gen_webhook: Event", $http_event);
  wdebug("twp_gen_webhook: Signature", $http_signature);

  // Get the Webhook Payload - it is in JSON
  $wh_payload = file_get_contents('php://input');

  // Convert the Payload from a JSON String into an object
  $wh_data = json_decode($wh_payload);
  wdebug("twp_gen_webhook: JSON", $wh_data);

  $payloadValid = isPayloadValid($wh_payload, $http_signature);
  wdebug("twp_gen_webhook: payloadValid", $payloadValid);

  // We have a valid payload, we can continue
  if ($payloadValid) {
    wdebug("twp_gen_webhook: Payload is Valid. Event:", $http_event);
    Auth::set(API_URL, API_KEY);

    switch ($http_event) {

      case 'COMMENT.CREATED':
        twpHook_CommentCreated($wh_data);
        break;

      case 'TASK.CREATED':
        twpHook_TaskCreated($wh_data);
        break;

      case 'TASK.UPDATED':
        twpHook_TaskUpdated($wh_data);
        break;

    } // switch $http_event

  }
} catch (Exception $e) {
  wdebug("Caught Exception: ", $e);
} finally {
  http_response_code(200);
  echo("twp_gen_webhook<br />");
}

