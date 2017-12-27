<?php


require_once __DIR__ . '/vendor/autoload.php';
ini_set('max_execution_time', 10003);
ini_set('max_input_time', 10003);

// Library Namespace Usages
use \TeamWorkPm\Factory as TeamWorkPm;
use \TeamWorkPm\Auth;
use \avc\Factory as Avc;
use \avc\Config as Cfg;

//require_once __DIR__ . '/lib/Factory.class.php';
//require_once __DIR__ . '/lib/Task.class.php';


// API Constants
const API_KEY = 'twp_3A4MaLtgRCEllViHO9ShCEBms3Ej';
const API_URL = 'http://avctw.teamwork.com/';
const ADF_USERID = '153910';
const WH_TOKEN = 'AvcToken625';

// <editor-fold defaultstate="collapsed" desc="Autoloader"> ------------------\\

function twpAutoload($class_name) {
  //class directories
  $directories = array(
    '/lib/',
  );

  $class_name = str_replace(['\\', '.'], '/', $class_name);
  //for each directory
  foreach($directories as $directory)  {
    //see if the file exsists
    // Check $class_name.class.php
    $nameClassPhp = __DIR__ . $directory . $class_name . '.class.php';
    if(file_exists($nameClassPhp)) {
      require_once($nameClassPhp);
      //only require the class once, so quit after to save effort (if you got more, then name them something else 
      return;
    }
    // check $class_name.php
    $namePhp = __DIR__ . $directory . $class_name . '.php';
    if(file_exists($namePhp)) {
      require_once($namePhp);
      //only require the class once, so quit after to save effort (if you got more, then name them something else 
      return;
    }    
  }
}
spl_autoload_register('twpAutoload');
    
// </editor-fold> Autoloader -------------------------------------------------\\

// <editor-fold defaultstate="collapsed" desc="Logging - Monolog"> ------------------\\


// </editor-fold> Logging - Monolog -------------------------------------------------\\

// <editor-fold defaultstate="collapsed" desc="Security"> ------------------\\

/**
 * Validate the Webhook Payload using SHA_HMAC-256 to confirm this 
 * request came from Teamwork server
 * 
 * @param type $wh_payload
 * @param type $http_signature
 */
function isPayloadValid($wh_payload, $http_signature) {
  $token = WH_TOKEN;
  $wh_checksum = hash_hmac('sha256', $wh_payload, $token);
  return $wh_checksum == $http_signature;  
}

// </editor-fold> Security -------------------------------------------------\\

// <editor-fold defaultstate="collapsed" desc="Config Settings"> ------------------\\

//TODO Convert getConfigSettings to a Public class with a Config Singleton

// Google Sheet with ConfigSettings: https://docs.google.com/spreadsheets/d/1T9eIXYorwN-V5SIxp7XDyyJKdoLuKc3fgFTIgCwv1SU/edit#gid=0
define("CONFIG_SETTINGS_URL", "https://docs.google.com/spreadsheets/d/e/2PACX-1vSwv4_YWRZ4ogkyOAjRqkjbSR0mnOoHgc8JUkT3BaMHMCD7BFc5ju1ugZJjSHur3vwHi-CNw_dQyv6T/pub?output=csv");                               
define("CONFIG_FILENAME", __DIR__ . "/cfg/twp_avc_ConfigSettings.csv");
$mcpConfig = array();   // MinCommentPeriod
$auConfig = array();    // Admin Users
$lrConfig = array();    // Location Replacements

// ReminderFrequency Tags
$defTagsRF = array(
  'RF1' => ['color' => '#a6a6a6', 'id' => null], //Grey
  'RF2' => ['color' => '#f4bd38', 'id' => null], //Mustard
  'RF3' => ['color' => '#f78234', 'id' => null], //Orange
  'RF4' => ['color' => '#f47fbe', 'id' => null], //Pink
  'RF5' => ['color' => '#d84640', 'id' => null]  //Red
);

// NeedsStatus Tags
$defTagsNS = array(
  \avc\Task::$statusTagPrefix . '1' => ['color' => '#f4bd38', 'id' => null], //Mustard
  \avc\Task::$statusTagPrefix . '2' => ['color' => '#f78234', 'id' => null], //Orange
  \avc\Task::$statusTagPrefix . '3' => ['color' => '#d84640', 'id' => null], //Red
);

getConfigSettings();
//TODO Convert getConfigSettings to a Public class with a Config Singleton
// so I can do Config::mcpConfig instead of having to declare the global all the time
function getConfigSettings() {
  global $mcpConfig;
  global $auConfig;  
  global $lrConfig;
  //if(!ini_set('default_socket_timeout', 15)) echo "<!-- unable to change socket timeout -->";
  
  // Default, is to get settings from CONFIG_SETTING_URL
  $cfgFile = CONFIG_SETTINGS_URL;
  $updateCfgFile = true;

  // Doesn't work - can't do a stat on a google CSV file
//  if (file_exists(CONFIG_SETTINGS_URL)) {
//    $cfgUrlFileAge = (time() - filemtime(CONFIG_SETTINGS_URL))/ (60*60);  // hours
//  } else {
//    $cfgUrlFileAge = 24*60*60;    // Default 1 day old
//  }
  
  // Open from Local file?
  if (file_exists(CONFIG_FILENAME)) {
    $cfgFileAge = (time() - filemtime(CONFIG_FILENAME))/ (60*60);  // hours
    if ($cfgFileAge < 1 || ($cfgFileAge > $cfgUrlFileAge)) {
      // Local File < 1 hr old, and newer than GoogleSheet, use the local file
      $cfgFile = CONFIG_FILENAME;
      $updateCfgFile = false;
    }
  }
      
  wdebug("getConfigSettings", $cfgFile);
  Cfg::Log()->info('getConfigSettings', ['cfgFile' => $cfgFile]);
  //$log->info('getConfigSettings', $cfgFile);
  
  if (($handle = fopen($cfgFile, "r")) !== FALSE) {
      $rowNum = 0;
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
          $ssData[] = $data;
          // MinCommentPeriod
          if ($data[0] == "MinCommentPeriod") {
            $mcpStart = $rowNum;
          }
          if ($data[0] == "MinCommentPeriod:End") {
            $mcpEnd = $rowNum;
          }
          
          // AdminUsers
          if ($data[0] == "AdminUsers") {
            $auStart = $rowNum;
          }
          if ($data[0] == "AdminUsers:End") {
            $auEnd = $rowNum;
          }

          // LocationReplacement
          if ($data[0] == "LocationReplacement") {
            $lrStart = $rowNum;
          }
          if ($data[0] == "LocationReplacement:End") {
            $lrEnd = $rowNum;
          }
          
          
          $rowNum++;
      }
      fclose($handle);
  }
  else {
      die("Problem reading csv");
  }
  
  // Update the local ConfigSettings file
  if ($updateCfgFile) {
    updateConfigSettingsFile($ssData);
  }
  
  
  // Process MinCommentPeriod
  // Format of resulting values will be
  // $mcpConfig = array["1"] = array(           // 1 is the Priority
  //  '0' => 72,                                // 0 is for the OVERDUE
  //  '-21' => 336,                             // -21 is for 21 days PRIOR to Due Date
  //  '-14' => 168,                             // -14 is for 14 days PRIOR to Due Date
  //  '-7' => 72
  //  '-3' => 72
  //  );
  // Get Pending Cols
  $pending = array();
  $hdrs = $ssData[$mcpStart+1];
  $cNum = 0;
  foreach ($hdrs as $c) {
    if (beginsWith($c, "Overdue")) {
      $pending[$cNum] = 0;
    }
    if (beginsWith($c, "DueDate -")) {
      $pendingDays = intval(end(explode("-", $c, 2)));
      $pending[$cNum] = $pendingDays;
    }
    $cNum++;
  }
  for ($r = $mcpStart+2; $r < $mcpEnd; $r++) {
    $row = $ssData[$r];
    $pri = $row[0];
    $mcpConfig[$pri] = array(
      '0' => $row[1]
    );
    foreach ($pending as $pCol => $pVal) {
      $mcpConfig[$pri][$pVal] = $row[$pCol];
    }    
  }
  
  // Process AdminUsers
  // $auConfig = array(
  //   'user@emailaddr.com',
  //   'user2@emailaddr.com',
  //  )
  for ($r = $auStart+2; $r < $auEnd; $r++) {    
    $row = $ssData[$r];
    $email = strtolower($row[1]);
    $name = $row[0];
    $auConfig[$email] = $name;
  }
  
  // Process LocationReplacement
  // $lrConfig = array(
  //   'rules' => array('MD',...),
  //   'replace' => array('MH',...),
  //   ),
  //  
  //  )
  $lrConfig['rules'] = array();
  $lrConfig['replace'] = array();
  for ($r = $lrStart+2; $r < $lrEnd; $r++) {    
    $row = $ssData[$r];
    $ar = array(
      'find' => '/' . trim($row[0], '/') . '/',
      'replace' => $row[1],
    );
    $lrConfig['rules'][] = '/' . trim($row[0], '/') . '/';
    $lrConfig['replace'][] = $row[1];
  }  
} // getConfigSettings()

function updateConfigSettingsFile($ssData) {
  wdebug("updateConfigSettingsFile", "> " . CONFIG_FILENAME);
  if (($handle = fopen(CONFIG_FILENAME, "w")) !== FALSE) {
    foreach ($ssData as $line) {
      fputcsv($handle, $line);
    }
    fclose($handle);
  }
}

// </editor-fold> Config Settings -------------------------------------------------\\


// <editor-fold defaultstate="collapsed" desc="Error Handler"> ------------------\\

function avcErrorHandler($errno, $errstr, $errfile, $errline) {
  wdebug("Fatal Error -", "------------------------------------");
  wdebug("Fatal Error - No: ", $errno);
  wdebug("Fatal Error - String: ", $errstr);
  wdebug("Fatal Error - File: ", $errfile);
  wdebug("Fatal Error - Line: ", $errline);
  //wdebug("Fatal Error - Backtrace", debug_backtrace());
  wdebug("Fatal Error -", "------------------------------------");
  
  http_response_code(200);
  return true;
}

// </editor-fold> Error Handler -------------------------------------------------\\

// <editor-fold defaultstate="collapsed" desc="TWP Lib Helper Functions"> ------------------\\

// Static Vars -----------------------------------------------------------------
$projectCategories = null;


// Helper Functions ------------------------------------------------------------
function getProjectCategoryId($categoryName, $reload = false) {
  global $projectCategories;
  
  // If we haven't loaded Categories or if users requests, load them.
  if ($reload || $projectCategories == null) {
    wdebug("getProjectCategoryId", "Load projectCategories.");
    $pcat = TeamWorkPm::build('category/project');
    $projectCategories = $pcat->getAll();
  }

  foreach ($projectCategories as $pc) {
    if (strtolower($pc->name) == strtolower($categoryName)) {
      // Found it, return it
      return $pc->id;
    }
  }
  // If we get here, we didn't find it, return null
  return null;
}

function addDefaultTags(&$defTags, $reload = false) {  
  
  $firstTag = reset($defTags);
  if ($reload || $firstTag->id == null) {
    $allTags = Avc::getAllTags();
    //wdebug("addDefaultRFTags. allTags: ", $allTags);
    $tagNames = [];
    $allTagsArr = $allTags->toArray();
    // Create an array of Tags indexed by ->name
    foreach ($allTagsArr as $key => $item) {
      $tagNames[$item->name] = $item;
    }
    foreach ($defTags as $dTagName => $dItem) {
      if (array_key_exists($dTagName, $tagNames)) {
        $defTags[$dTagName]['id'] = $tagNames[$dTagName]->id;
        // Ensure that it has the correct color
        if ($dItem['color'] != $tagNames[$dTagName]->color) {
          // Color doesn't match - need to update existing tag
          $updTag = TeamWorkPm::build('tag');
          //wdebug("addDefaultRFTags. update tag - Existing: ", $tagNames[$dTagName]);
          //wdebug("addDefaultRFTags. update tag - New: ", $defTags[$dTagName]);
          $tagId = $updTag->save([
            'id' => $tagNames[$dTagName]->id,
            'color' => $dItem['color']
          ]);
        }
      } else {
        // Tag doesn't exist - need to add a NEW tag
        $newTag = TeamWorkPm::build('tag');
        //wdebug("addDefaultRFTags. add tag: ", $defTags[$dTagName]);
        $tagId = $newTag->save([
          'name' => $dTagName,
          'color' => $dItem['color']
        ]);
        $defTags[$dTagName]['id'] = $tagId;
      }
    }
    $allTagsNew = Avc::getAllTags();
    //wdebug("addDefaultRFTags. allTagsNew: ", $allTagsNew);
  }
  
}

function addDefaultRFTags($reload = false) {
  global $defTagsRF;
  addDefaultTags($defTagsRF, $reload);
}

function addDefaultNSTags($reload = false) {
  global $defTagsNS;
  addDefaultTags($defTagsNS, $reload);
}
    
function getRFTagIds() {
  global $defTagsRF;
  addDefaultRFTags();
  $tagIds = [];
  foreach ($defTagsRF as $name => $tag) {
    $tagIds[] = $tag['id'];
  }
  return $tagIds;
}


// </editor-fold> TWP Lib Helper Functions -------------------------------------------------\\

// <editor-fold defaultstate="collapsed" desc="TWP Webhook Processing"> ------------------\\

function twpHook_CommentCreated($wh_data) {  
  try {
    $eventObj = $wh_data->eventCreator;
    $comObj = $wh_data->comment;


    $objType = $comObj->objectType;
    $objId = $comObj->objectId;
    switch ($objType) {
      case 'task':
        // Comment added to a task
        $avcTask = Avc::getTask($objId);
        $avcTask->onCommentCreated($wh_data, $comObj);
        break;
    }
  } catch (Exception $exc) {
    wdebug("Caught Exception: ", $exc);
  }
}

function twpHook_TaskCreated($wh_data) {
  $eventObj = $wh_data->eventCreator;
  $taskObj = $wh_data->task;
  $taskListObj = $wh_data->taskList;
}

function twpHook_TaskUpdated($wh_data) {
  $eventObj = $wh_data->eventCreator;
  $taskObj = $wh_data->task;
  $taskListObj = $wh_data->taskList;
}


// </editor-fold> TWP Webhook Processing -------------------------------------------------\\


// <editor-fold defaultstate="collapsed" desc="String Handling"> ------------------\\
function beginsWith($haystack, $needle) {
  return strpos($haystack, $needle) === 0;
}

function contains($haystack, $needle) {
  return strpos($haystack, $needle) !== false;
}

function isRegularExpression($string) {
  set_error_handler(function() {}, E_WARNING);
  $isRegularExpression = preg_match($string, "") !== FALSE;
  restore_error_handler();
  return $isRegularExpression;
}

// </editor-fold> String Handling -------------------------------------------------\\


// <editor-fold defaultstate="collapsed" desc="Array Handling"> ------------------\\
function arrSameValues($arr1, $arr2) {
  sort($arr1);
  sort($arr2);
  
  return ($arr1 == $arr2);
}

function getHighestKey_ReturnValue($arr, $search) {
  $data = $arr;
  // sort it so you can find 1st highest value in range
  asort($data, SORT_NUMERIC);

  $lastKey = null;
  foreach ($data as $k => $v) {
    if ($k > $search) {
      if ($lastKey) { 
        return $data[$lastKey]; } 
      else {
        return $data[$k];
      }
    }
    $lastKey = $k;
  }
  if ($lastKey) { return $data[$lastKey]; } 
}

function getHighestValue($arr, $high = 200, $low =100) {
    $data = $arr;
    // sort it so you can find 1st highest value in range
    asort($data);

    foreach ($data as $item) {
    //trim dollar sign as i can see from your example
        $item = ltrim($item, "$");
        if ($item >= $low && $item <= $high) {
            return $item;
        }
    }
  return false;
}

// </editor-fold> Array Handling -------------------------------------------------\\


// <editor-fold defaultstate="collapsed" desc="Date Handling"> ------------------\\

date_default_timezone_set(getDefaultTimezone());
function getDefaultTimezone() {
  //TODO Can I get this another way? Perhaps from the user Acct?
  // Or maybe set it in the ConfigSettings?
  return "America/Los_Angeles";
}

function is_Date($str){         
  $str = str_replace('/', '-', $str);     
  $stamp = strtotime($str);
  if (is_numeric($stamp)){  
    $month = date( 'm', $stamp ); 
    $day   = date( 'd', $stamp ); 
    $year  = date( 'Y', $stamp ); 

    return checkdate($month, $day, $year); 

  }  
  return false; 
}

/*
 * Safe Date Add - this adds to the Date but does NOT modify the passed date
 * $dt : DateTime Variable or String
 * $interval: DateInterval Variable or String
 */
function dateAdd($pDate, $pInterval) {
  if (gettype($pDate) == "string") {
    $dt = new DateTime($pDate);    
  } else {
    $dt = clone $pDate;
  }
  if (gettype($pInterval) == "string") {
    $interval = new DateInterval($pInterval);
  } else {
    $interval = $pInterval;
  }
  
  return $dt->add($interval);
}

/*
 * Safe Date Sub - this subtracts to the Date but does NOT modify the passed date
 * $dt : DateTime Variable or String
 * $interval: DateInterval Variable or String
 */
function dateSub($pDate, $pInterval) {
  if (gettype($pDate) == "string") {
    $dt = new DateTime($pDate);    
  } else {
    $dt = clone $pDate;
  }
  if (gettype($pInterval) == "string") {
    $interval = new DateInterval($pInterval);
  } else {
    $interval = $pInterval;
  }
  
  return $dt->sub($interval);
}

/*
 * Safe Date Modify - this adds to the Date but does NOT modify the passed date
 * $dt : DateTime Variable or String
 * $modify: Date Time Modification String
 * > See here for formats: http://php.net/manual/en/datetime.formats.php
 */
function dateModify($pDate, $modify) {
  if (gettype($pDate) == "string") {
    $dt = new DateTime($pDate);    
  } else {
    $dt = clone $pDate;
  }
  
  return $dt->modify($modify);
}

/*
 * If a DateTime doesn't have a Time, this will set the default
 * time to be the set value in "HH:MM" format
 * $pDate: DateTime Variable or String
 * $pTime: String in the format "HH:MM"
 * 
 */
function setDefaultDateTime_Time($pDate, $pTime) {
  if (gettype($pDate) == "string") {
    $dt = new DateTime($pDate);    
  } else {
    $dt = clone $pDate;
  }

  $time = $dt->format("H:i:s");
  if ($time == "00:00:00") {
    // No time set, set with pTime
    return new DateTime($dt->format("Y-m-d " . $pTime));
  }
  return $dt;
}

/*
 * Convert the passed date to UTC and return that as DateTime
 * $pDate: DateTime variable or String
 */
function convertDateTimeToUTC($pDate) {
  if (gettype($pDate) == "string") {
    $dt = new DateTime($pDate);    
  } else {
    $dt = clone $pDate;
  }
  $dt->setTimezone(new DateTimeZone('UTC'));  
  
  return $dt;
}

/*
 * Convert the passed date from UTC to the Local Timezone and return that as DateTime
 * $pDate: DateTime variable or String
 * $pTimeZone: Timezone variable or String of the passed $pDate
 * 
 */
function convertDateTimeToLocal($pDate, $pTimeZone = 'UTC') {
  if (gettype($pTimeZone) == "string") {
    $tz = new DateTimeZone($pTimeZone);
  } else {
    $tz = $pTimeZone;
  }
  if (gettype($pDate) == "string") {
    $dt = new DateTime($pDate, $tz);
  } else {
    //$dt = clone $pDate;
    $dt = new DateTime($pDate->format("Y-m-d H:i:s"), $tz);
  }  
  $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));  
  
  return $dt;
}

/**
 * 
 * Calculates the # of HRs since the last comment was made
 * Adjusts for comments made after hours.
 * Comments made from 10pm the prior day to 6am the current day
 * are treated as if they were made at 6am the current day.
 * 
 * @param DateTime $commentDate
 * @param DateTime $nowDate
 */
function calcHrsSinceLastComment($commentDate, $nowDate) {
  $interval = $commentDate->diff($nowDate);
  //$interval = date_diff($commentDate, $nowDate);
  $hrsSinceComment = getTotalInterval($interval, "hours");
  return $hrsSinceComment;
}

/**
 * Calculates the # of HRs between 2 dates.
 * Subtracts $olderDate from $newerDate
 * 
 * @param DateTime $date1
 * @param DateTime $date2
 */
function calcHrsBetweenDates($olderDate, $newerDate = null) {
  if ($newerDate == null) { $newerDate = new DateTime(); }
  $interval = $olderDate->diff($newerDate);
  $hrs = getTotalInterval($interval, "hours");
  return $hrs;
}

function getTotalInterval($interval, $type){
  switch($type){
      case 'years':
          return $interval->format('%Y');
          break;
      case 'months':
          $years = $interval->format('%Y');
          $months = 0;
          if($years){
              $months += $years*12;
          }
          $months += $interval->format('%m');
          return $months;
          break;
      case 'days':
          return $interval->format('%a');
          break;
      case 'hours':
          $days = $interval->format('%a');
          $hours = 0;
          if($days){
              $hours += 24 * $days;
          }
          $hours += $interval->format('%H');
          return $hours;
          break;
      case 'minutes':
          $days = $interval->format('%a');
          $minutes = 0;
          if($days){
              $minutes += 24 * 60 * $days;
          }
          $hours = $interval->format('%H');
          if($hours){
              $minutes += 60 * $hours;
          }
          $minutes += $interval->format('%i');
          return $minutes;
          break;
      case 'seconds':
          $days = $interval->format('%a');
          $seconds = 0;
          if($days){
              $seconds += 24 * 60 * 60 * $days;
          }
          $hours = $interval->format('%H');
          if($hours){
              $seconds += 60 * 60 * $hours;
          }
          $minutes = $interval->format('%i');
          if($minutes){
              $seconds += 60 * $minutes;
          }
          $seconds += $interval->format('%s');
          return $seconds;
          break;
      case 'milliseconds':
          $days = $interval->format('%a');
          $seconds = 0;
          if($days){
              $seconds += 24 * 60 * 60 * $days;
          }
          $hours = $interval->format('%H');
          if($hours){
              $seconds += 60 * 60 * $hours;
          }
          $minutes = $interval->format('%i');
          if($minutes){
              $seconds += 60 * $minutes;
          }
          $seconds += $interval->format('%s');
          $milliseconds = $seconds * 1000;
          return $milliseconds;
          break;
      default:
          return NULL;
    }
  }


// </editor-fold> Date Handling -------------------------------------------------\\


// <editor-fold defaultstate="collapsed" desc="Debug"> ------------------\\

function wdebug($desc, $var = "") {
  global $debug, $debug_file;
  if ($debug) {
    $str_date = gmdate('Y-m-d H:i:s'). " : ";
    $str_out =  $desc . ": " . print_r($var, true) . "\n";    
    file_put_contents($debug_file, $str_date . $str_out, FILE_APPEND | LOCK_EX);    
    file_put_contents('php://stderr', $str_out);
    
    // Also send debug to Logger
    Cfg::Log('wdebug')->debug($desc, ['var' => $var]);
  }
}

// </editor-fold> Debug -------------------------------------------------\\



