<?php

$debug = true;
$debug_file = "./poll.log";

require_once('../twp_util.php');

// Library Namespace Usages
use \TeamWorkPm\Factory as TeamWorkPm;
use \TeamWorkPm\Auth;
use \avc\Factory as Avc;


wdebug("=========================================================");
wdebug("| BEGIN - twp_poll.php                                  |");
wdebug("|                                                       |");
// set keys
Auth::set(API_URL, API_KEY);

//testGetTag();
function testGetTag() {
  /* @var $tag \TeamWorkPm\Tag */
  try {
    // set keys
    //Auth::set(API_URL, API_KEY);

    $tag = TeamWorkPm::build('tag');    
    $resp = $tag->getAll();
    wdebug("twp_poll: resp:", $resp);
    //$resp_str = $resp->string;            // doesn't work, ->string is [protected]
    //wdebug("twp_poll: resp->string:", $resp_str);
    //$tags = json_decode($resp->string);   // doesn't work ->string is [protected]
    //wdebug("twp_poll: tags:", $tags);
    //$tags2 = $resp->getContent();
    //wdebug("twp_poll: tags2:", $tags2);

    $toString = strval($resp);              // if you want to force call __toString(), call strval($var)
    wdebug("twp_poll: toString:", $toString);
    $tags = json_decode($toString);
    wdebug("twp_poll: tags:", $tags);

    //$storage = $resp->storage;                    // Doens't work. $data[0] is a 'storage' but it isn't named.
    //wdebug("twp_poll: storage:", $storage);

    $toArray = $resp->toArray();
    wdebug("twp_poll: toArray:", $toArray);

    $getHeaders = $resp->getHeaders();
    wdebug("twp_poll: getHeaders:", $getHeaders);




  //  $account = TeamWorkPm::build('account');
  //  $resp_acct = $account->get();
  //  wdebug("twp_poll: resp_acct:", $resp_acct);
  //  $data_acct = $resp_acct->data;
  //  wdebug("twp_poll: data_acct:", $data_acct);
    
  } catch (Exception $ex) {
    wdebug('Exception: ', $ex);
  }  
}

//testGetTask();
function testGetTask() {
  try {
    $taskId = 7529247;
    $taskId = 7529249;
    $task = TeamWorkPm::build('task');
    $task1 = $task->get($taskId);
    wdebug("twp_poll: task1: ", $task1);
    
    $task_tags = $task1->tags;
    wdebug("twp_poll: task_tags: ", $task_tags);
    
  } catch (Exception $ex) {
    wdebug('Exception: ', $ex);
  }
}

//testGetAllTasks();
function testGetAllTasks() {
  /* @var $task \TeamWorkPm\Task */
  
  try {
    $task = TeamWorkPm::build('task');    
    $tasks = $task->getAll();
    wdebug("twp_poll: tasks: ", $tasks);    
    
  } catch (Exception $exc) {
    wdebug('Exception: ', $ex);
  } finally {
    
  }

}


//testGetAllCategories();
function testGetAllCategories() {
  try {
    $pcat = TeamWorkPm::build('category/project');
    $pcats = $pcat->getAll();
    wdebug("twp_poll: All Project Categories: ", $pcats);
  } catch (Exception $ex) {
    wdebug('Exception: ', $ex);
  }
}

//testGetCategoryById();
function testGetCategoryById() {
  try {
    $catId = getProjectCategoryId("PM");
    wdebug("twp_poll: Category ID PM: ", $catId);


    $catId2 = getProjectCategoryId("PM");
    wdebug("twp_poll: Category ID PM: ", $catId2);
  } catch (Exception $exc) {
    wdebug('Exception: ', $ex);
  }

}

//testGetComments();    // WORKS
function testGetComments() {
  /* @var $tcom \TeamWorkPm\Comment\Task */
  $taskId = 7529247;
  try {
    $tcom = TeamWorkPm::build('comment/task');
    \TeamWorkPm\Rest::$RESPONSE_CLASS = '\avc\ResponseObject';
    $comments = $tcom->getRecent($taskId);
    wdebug("twp_poll: Comments for TaskID {$taskId}: ", $comments);
    $com1 = $comments[0];
    $postDate = $com1->datetime;
    wdebug("twp_poll: PostDate for Comment1 for TaskID {$taskId}: ", $postDate);
    
  } catch (Exception $ex) {
    wdebug('Exception: ', $ex);
  }
}

//testGetAvcTaskAndComments();
function testGetAvcTaskAndComments() {
  /* @var $avcTask \avc\Task */
  /* @var $avcTaskResp \avc\ResponseObject */
  
  try {
    $taskId = 7529247;
    $taskId = 7529249;
    //$task = TeamWorkPm::build('AvcTask');
    //wdebug('twp_poll: task class type: ', get_class($task));
    
    $taskObj = Avc::build('Task');
    wdebug('twp_poll: taskObj class type: ', get_class($taskObj));
    // \TeamWorkPm\Rest::$RESPONSE_CLASS = '\avc\ResponseObject';
    $avcTaskResp = $taskObj->get($taskId);
    wdebug('twp_poll: avcTask class type: ', get_class($avcTaskResp));
    wdebug("twp_poll: avcTask: ", $avcTaskResp);
    $respId = $avcTaskResp->responsiblePartyId;   
    wdebug('twp_poll: avcTask->responsiblePartyId: ', $respId);
    $avcTask = $avcTaskResp->getObj();
    
    $listName = $avcTask->todoListName;
    wdebug('twp_poll: avcTask->todoListName: ', $listName);    
    
    $rc = $avcTask->getResponsibleComments();
    wdebug("twp_poll: Responsible Comments: ", $rc);
    
    wdebug("end test");
    
//    $task1 = $task->get($taskId);
//    wdebug("twp_poll: task1: ", $task1);
//    
//    $task_tags = $task1->tags;
//    wdebug("twp_poll: task_tags: ", $task_tags);
    
  } catch (Exception $ex) {   
    wdebug('Exception: ', $ex);
  }  
}

//testGetAvcTask();
function testGetAvcTask() {
  $taskId = 7529249;
  $avcTask = Avc::getTask($taskId);
  wdebug("twp_poll: avcTask: ", $avcTask);
}

//testGetTaskRecentComment();
function testGetTaskRecentComment() {
try {
    $taskId = 7529249;
    //$task = TeamWorkPm::build('AvcTask');
    //wdebug('twp_poll: task class type: ', get_class($task));
    
    $taskObj = Avc::build('Task');
    wdebug('twp_poll: taskObj class type: ', get_class($taskObj));
    // \TeamWorkPm\Rest::$RESPONSE_CLASS = '\avc\ResponseObject';
    $avcTaskResp = $taskObj->get($taskId);
    wdebug('twp_poll: avcTask class type: ', get_class($avcTaskResp));
    wdebug("twp_poll: avcTask: ", $avcTaskResp);
    $respId = $avcTaskResp->responsiblePartyId;   
    wdebug('twp_poll: avcTask->responsiblePartyId: ', $respId);
    $avcTask = $avcTaskResp->getObj();
    
    $listName = $avcTask->todoListName;
    wdebug('twp_poll: avcTask->todoListName: ', $listName);    
    
    $rc = $avcTask->getResponsibleComments();
    wdebug("twp_poll: Responsible Comments: ", $rc);
    
    wdebug("end test");
    
//    $task1 = $task->get($taskId);
//    wdebug("twp_poll: task1: ", $task1);
//    
//    $task_tags = $task1->tags;
//    wdebug("twp_poll: task_tags: ", $task_tags);
    
  } catch (Exception $ex) {
    wdebug('Exception: ', $ex);
  }    
}

wdebug("|                                                       |");
wdebug("| END - twp_poll.php                                    |");
wdebug("=========================================================");



