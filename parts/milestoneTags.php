<?php

header('Access-Control-Allow-Origin: *');

$debug = true;
$debug_file = "./parts.log";

require_once('../twp_util.php');

// Library Namespace Usages
use \TeamWorkPm\Factory as TeamWorkPm;
use \TeamWorkPm\Auth;
use \avc\Factory as Avc;

$old_error_handler = set_error_handler("avcErrorHandler");


wdebug("=========================================================");
wdebug("| BEGIN - milestoneTags.php                             |");
wdebug("|                                                       |");
// set keys
Auth::set(API_URL, API_KEY);

wdebug("_GET = ", $_GET);
if (isset($_GET['milestoneId'])) {
  $milestoneId = $_GET['milestoneId'];
  wdebug("milestoneTags: milestoneId = ", $milestoneId);
  outputMilestoneTags($milestoneId);
}







function outputMilestoneTags($milestoneId) {
  /* @var $avcMilestone \avc\Milestone */
  $avcMilestone = Avc::getMilestone($milestoneId);
  $tags = $avcMilestone->getTagsHtml();
  wdebug("tags output = ", $tags);
  //echo '<div class="avcTags"> ' . $tags . '</div>';
  echo $tags;
}

wdebug("|                                                       |");
wdebug("| END - milestoneTags.php                               |");
wdebug("=========================================================");