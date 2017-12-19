<?php

$debug = true;
$debug_file = "./poll_test.log";

require_once('../twp_util.php');

// Library Namespace Usages
use \TeamWorkPm\Factory as TeamWorkPm;
use \TeamWorkPm\Auth;
use \avc\Factory as Avc;


wdebug("=========================================================");
wdebug("| BEGIN - twp_poll_test.php                             |");
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
    wdebug("twp_poll_test: resp:", $resp);
    //$resp_str = $resp->string;            // doesn't work, ->string is [protected]
    //wdebug("twp_poll_test: resp->string:", $resp_str);
    //$tags = json_decode($resp->string);   // doesn't work ->string is [protected]
    //wdebug("twp_poll_test: tags:", $tags);
    //$tags2 = $resp->getContent();
    //wdebug("twp_poll_test: tags2:", $tags2);

    $toString = strval($resp);              // if you want to force call __toString(), call strval($var)
    wdebug("twp_poll_test: toString:", $toString);
    $tags = json_decode($toString);
    wdebug("twp_poll_test: tags:", $tags);

    //$storage = $resp->storage;                    // Doens't work. $data[0] is a 'storage' but it isn't named.
    //wdebug("twp_poll_test: storage:", $storage);

    $toArray = $resp->toArray();
    wdebug("twp_poll_test: toArray:", $toArray);

    $getHeaders = $resp->getHeaders();
    wdebug("twp_poll_test: getHeaders:", $getHeaders);




  //  $account = TeamWorkPm::build('account');
  //  $resp_acct = $account->get();
  //  wdebug("twp_poll_test: resp_acct:", $resp_acct);
  //  $data_acct = $resp_acct->data;
  //  wdebug("twp_poll_test: data_acct:", $data_acct);
    
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
    wdebug("twp_poll_test: task1: ", $task1);
    
    $task_tags = $task1->tags;
    wdebug("twp_poll_test: task_tags: ", $task_tags);
    
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
    wdebug("twp_poll_test: tasks: ", $tasks);    
    
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
    wdebug("twp_poll_test: All Project Categories: ", $pcats);
  } catch (Exception $ex) {
    wdebug('Exception: ', $ex);
  }
}

//testGetCategoryById();
function testGetCategoryById() {
  try {
    $catId = getProjectCategoryId("PM");
    wdebug("twp_poll_test: Category ID PM: ", $catId);


    $catId2 = getProjectCategoryId("PM");
    wdebug("twp_poll_test: Category ID PM: ", $catId2);
  } catch (Exception $exc) {
    wdebug('Exception: ', $ex);
  }

}

//testGetComments();    // WORKS
function testGetComments() {
  /* @var $tcom \TeamWorkPm\Comment\Task */
  $taskId = 7529247;
  try {
    $tcom = TeamWorkPm::build('comment\task');
    \TeamWorkPm\Rest::$RESPONSE_CLASS = '\avc\ResponseObject';
    $comments = $tcom->getRecent($taskId);
    wdebug("twp_poll_test: Comments for TaskID {$taskId}: ", $comments);
    $com1 = $comments[0];
    $postDate = $com1->datetime;
    wdebug("twp_poll_test: PostDate for Comment1 for TaskID {$taskId}: ", $postDate);
    
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
    //wdebug('twp_poll_test: task class type: ', get_class($task));
    
    $taskObj = Avc::build('Task');
    wdebug('twp_poll_test: taskObj class type: ', get_class($taskObj));
    // \TeamWorkPm\Rest::$RESPONSE_CLASS = '\avc\ResponseObject';
    $avcTaskResp = $taskObj->get($taskId);
    wdebug('twp_poll_test: avcTask class type: ', get_class($avcTaskResp));
    wdebug("twp_poll_test: avcTask: ", $avcTaskResp);
    $respId = $avcTaskResp->responsiblePartyId;   
    wdebug('twp_poll_test: avcTask->responsiblePartyId: ', $respId);
    $avcTask = $avcTaskResp->getObj();
    
    $listName = $avcTask->todoListName;
    wdebug('twp_poll_test: avcTask->todoListName: ', $listName);    
    
    $rc = $avcTask->getResponsibleComments();
    wdebug("twp_poll_test: Responsible Comments: ", $rc);
    
    wdebug("end test");
    
//    $task1 = $task->get($taskId);
//    wdebug("twp_poll_test: task1: ", $task1);
//    
//    $task_tags = $task1->tags;
//    wdebug("twp_poll_test: task_tags: ", $task_tags);
    
  } catch (Exception $ex) {   
    wdebug('Exception: ', $ex);
  }  
}


//testGetAvcTask();
function testGetAvcTask() {
  $taskId = 7529249;
  $avcTask = Avc::getTask($taskId);
  wdebug("twp_poll_test: avcTask: ", $avcTask);
}

//testGetTaskRecentComment();
function testGetTaskRecentComment() {
try {
    $taskId = 7529249;
    $avcTask = Avc::getTask($taskId);
    $comment = $avcTask->getMostRecentResponsibleComment();  
    wdebug("twp_poll_test: Most Recent Responsible Comments: ", $comment);
    
    wdebug("end test");
    
//    $task1 = $task->get($taskId);
//    wdebug("twp_poll_test: task1: ", $task1);
//    
//    $task_tags = $task1->tags;
//    wdebug("twp_poll_test: task_tags: ", $task_tags);
    
  } catch (Exception $ex) {
    wdebug('Exception: ', $ex);
  }    
}

//testMinCommentPeriod();
function testMinCommentPeriod() {
  /* @var $avcTask avc\Task */
  $taskId = 7529249;
  $avcTask = Avc::getTask($taskId);
  $minCommentPeriod = $avcTask->getMinCommentPeriod(null, 3);
  wdebug("twp_poll_test: minCommentPeriod: ", $minCommentPeriod);
}

//testAddRFTags();
function testAddRFTags() {
  // Test adding the required/default RF Tags
  // This works. Adds the tags specified in the addDefaultRFTags function
  // The default tags are defined there
  addDefaultRFTags();   
}

//testAddReminderComment();
function testAddReminderComment() {
  /* @var $avcTask avc\Task */
  $taskId = 7529249;
  $avcTask = Avc::getTask($taskId);

  $avcTask->addReminderComment('html');
}

//testGetRecentStatusComment();
function testGetRecentStatusComment() {
  /* @var $avcTask avc\Task */
  $taskId = 7529249;
  $avcTask = Avc::getTask($taskId);
  wdebug("twp_poll_test: avcTask: ", $avcTask);
  $com = $avcTask->getMostRecentStatusComment();
  wdebug("testGetRecentStatusComment ", $com);  
  
  // Test convert DateTime to Local
  $lastReminderCreatedOn = convertDateTimeToLocal($com->datetime_DT, 'UTC');
  wdebug("testGetRecentStatusComment Time: ", $lastReminderCreatedOn);  
}


//testGetRFTagIds();
function testGetRFTagIds() {
  $tagIds = getRFTagIds();  
  $params['tag-ids'] = implode(',', $tagIds);
  wdebug("testGetRFTagIds: ", $tagIds);  
}

//testGetProjectsinCategory();
function testGetProjectsinCategory() {
  /* @var $projects \avc\ResponseObject */
  /* @var $project \avc\Project */
  try {
    $projects = Avc::getProjects("PM");
    wdebug("twp_poll_test: Projects in PM: ", $projects);
    
    $projArr = $projects->getData(); 
    //TODO Need to fix the ResponseObject.parse so it can create avc\Project objects - it doesn't work currently.
    foreach ($projArr as $project) {
      wdebug("twp_poll_test: Process PM Project: ", $project->name);
      $projTasks = $project->getTasks(true);
      wdebug("twp_poll_test: RF Projects Tasks: COUNT: ", count($projTasks));
      wdebug("twp_poll_test: RF Projects Tasks: ", $projTasks);
    }
  } catch (Exception $exc) {
    wdebug('Exception: ', $ex);
  }

}

//testAddTagsToTask();
function testAddTagsToTask() {
  /* @var $avcTask \avc\Task */
  $taskId = 7529249;
  $avcTask = Avc::getTask($taskId);
  wdebug("twp_poll_test: avcTask: ", $avcTask->content);
  
  // Now add a Label
  addDefaultNSTags();
  $avcTask->addTag("NeedsStatus2");
  
}

testRemoveTagsToTask();
function testRemoveTagsToTask() {
  /* @var $avcTask \avc\Task */
  $taskId = 7529249;
  $avcTask = Avc::getTask($taskId);
  wdebug("twp_poll_test: avcTask Name: ", $avcTask->content);
  wdebug("twp_poll_test: avcTask: ", $avcTask);
  
  // Now add a Label
  addDefaultNSTags();
  $avcTask->removeTag("/NeedsStatus/");
  
}

wdebug("|                                                       |");
wdebug("| END - twp_poll_test_test.php                          |");
wdebug("=========================================================");



