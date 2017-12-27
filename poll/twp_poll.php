<?php

$debug = true;
$debug_file = "./poll.log";

require_once('../twp_util.php');

// Library Namespace Usages
use \TeamWorkPm\Factory as TeamWorkPm;
use \TeamWorkPm\Auth;
use \avc\Factory as Avc;

$old_error_handler = set_error_handler("avcErrorHandler");


wdebug("=========================================================");
wdebug("| BEGIN - twp_poll.php                                  |");
wdebug("|                                                       |");
// set keys
Auth::set(API_URL, API_KEY);


pollPMProjects();
/*
 * Methodology of Polling for Task Reminders
 * * There is NO WAY to store different Reminder frequencies for each Task
 *    of an RMTicket. The RMTicket Priority will set the same Reminder frequency
 *    for EVERY Task tied to that RMTicket.
 *    eg. If the RMTicket Priority is "0 Low", *every* Task for that RMTicket will 
 *    have a reminder frequency of 1/3 days.
 * * Each Task will get created with a Label to indicate the Reminder Frequency
 *    of that Task. These labels will have Text/IDs as follows:
 *    ID = Text
 *    1 = -1 Negative
 *    2 = 0 Low
 *    3 = 1 Medium
 *    ... This is because Podio #s Categories starting @ 1 ascending.
 *    While I *could* pull the # out of the Priority Text, I think that will
 *    place too great a constraint on the "Label" of the Priority. This way,
 *    I can change the text there to what I want, and it won't break the code.
 * * Every Task is created with a DueDate (at this time, we are NOT setting
 *    a Time with the DueDate, but I could add that).
 * * Reminders will NOT be set using Podio, but will use the Item/Task Comment
 *    system. A Comment like the following will be added to Notify the user
 *    when Status is requested:
 *    > Status requested for: <loc>: <RMTicket Title> : <Task Title> : Due on <Due Date>
 *    I could also add a code to indicate the Status Frequency, but first version
 *    will not have that.
 * * At this time, I will NOT be creating Reminders for RMTickets that pass
 *    their DueDate, but only for Tasks tied to the RMTicket.
 *    I could add Reminders to the RMTicket at a later time.
 * * To determine if a Task needs a reminder, I will do the following:
 *  * First, deal with the Status Update Reminders - these are Reminders that
 *  * take place BEFORE a Task is Due.
 *  1) Get EVERY Task in the RMTicket App that is NOT Closed and that 
 *    has a DueDate > Now().
 *  2) Calculate the the Min Comment Period in HOURS 
 *     ex. If RemFreq = 1 / 3 days, the MinCommentPeriod = 36
 *     NOTE: The MinCommentPeriod might go DOWN as we get closer to the DueDate
 *  3) Get the most *recent* comment by the Responsible User of the Task
 *     (probably only need to get 10 of them)
 *  4) Calculate the # of hours since that comment and Now (HrsSinceComment)
 *  5) Calculate the # of hours since the last Reminder (HrsSinceReminder)
 *  6) If HrsSinceComment > MinCommentPeriod AND HrsSinceReminder > 1
 *     - create a Status Update Comment
 * 
 *  * Second, deal with OverDue Notices - these are Reminders that take place
 *  * AFTER a Task is Due.
 *  1) Get EVERY Task in the RMTicket App that is NOT Closed and that 
 *    has a DueDate <= Now()
 *  2) Calculate the the Min Comment Period in HOURS 
 *     ex. If RemFreq = 1 / 3 days, the MinCommentPeriod = 36
 *  3) Get the most *recent* comment by the Responsible User of the Task
 *     (probably only need to get 10 of them)
 *  4) Calculate the # of hours since that comment and Now (HrsSinceComment)
 *  5) Calculate the # of hours since the last Reminder (HrsSinceReminder)
 *  6) If HrsSinceComment > MinCommentPeriod AND HrsSinceReminder > 1
 *     - Create OverDue Status Update Comment
 * 
 * NOTE: The only difference between Pre-DueDate reminders and Post-DueDate
 *  Reminders is: the type of message.
 *  Instead of breaking this into 2 steps, I could probably do it all in ONE
 *  Step but do the following:
 *  All steps as above, with one change
 *  5) If HrsSinceComment > MinCommentPeriod - Create Message
 *  5a)
 *  if Now() < DueDate = Gen Status Reminder
 *  if Now() >= DueDate = Gen OverDue Notice
 *     
 */
function pollPMProjects() {
  /* @var $projects \avc\ResponseObject */
  /* @var $project \avc\Project */
  /* @var $task \avc\Task */
  
  $nowDate = new DateTime();
  
  
  try {
    // 1) Get all Projects in PM Category
    $projects = Avc::getProjects("PM");
    //wdebug("twp_poll: Projects in PM: ", $projects);
    
    $projArr = $projects->getData(); 
    wdebug("twp_poll: Projects in PM COUNT: ", count($projArr));
    
    // 1b) Loop thru all Projects and get the incomplete Tasks for each
    foreach ($projArr as $project) {
      wdebug("twp_poll: Process PM Project: ", $project->name);
      
      //TODO: Perhaps get ALL Tasks, then check the RFTag but also check
      //the Milestone (if there is one) and set the Reminder Frequency
      //from that. This would allow Tasks to "inherit" the RF# from the Milestone
      //I should *override* if a RF# is set at the Task explicitly.
      //So, if Milestone.RF3 is set, but Task.RF1 is set, it would use RF1
      //
      // getTasks(rfTagsOnly = TRUE)
      // getTasks(rfTagsOnly = FALSE) - we now get ALL incomplete tasks, since
      // we check the priority of each one, that way we can check the Priority
      // of the Milestone, if a Task is part of a TaskList that has a Milestone
      // attached to it.
      $projTasks = $project->getTasks(false);
      wdebug("twp_poll: Projects Tasks: COUNT: ", count($projTasks));
      
      foreach ($projTasks as $task) {
        wdebug("twp_poll:-------------------------", "");
        wdebug("twp_poll: PROCESS TASK: ", $task->content);
        wdebug("twp_poll: Process Task: ", $task->id);
        
        //TODO: Move almost ALL of the below to a avcTask->Method
        // That way, I can just call: $avcTask->processPolling($nowDate) 
        // and have it run.
        $task->processPolling($nowDate);
        
      } // foreach $projTasks as $task           
      wdebug("twp_poll: Finished Processing Tasks", "------");
      
    } // foreach $projArr as $project
    
    wdebug("twp_poll: Finished Processing Projects", "------");
    
  } catch (Exception $exc) {
    wdebug('Exception: ', $ex);
  }
  
  
  
  
  
  
}

wdebug("|                                                       |");
wdebug("| END - twp_poll.php                                    |");
wdebug("=========================================================");



function codeMovedToProcessPolling() {
        // 2) Calculate MinCommentPeriod        
        $minCommentPeriod = $task->getMinCommentPeriod();
        
        //TODO SET THIS BELOW FOR TESTING
        //$minCommentPeriod = 3; // 3 hrs    
        wdebug("twp_poll", "> minCommentPeriod = " . $minCommentPeriod);
        
        // 3) Get the most recent comment
        $comment = $task->getMostRecentResponsibleComment();

        if ($comment != null) {
          $createdOn = convertDateTimeToLocal($comment->datetime_DT, "UTC");
          wdebug("twp_poll", "> Most Recent Resp. Comment Date = " . $createdOn->format("Y-m-d H:i:s"));

          // 4) Calculate # of Hrs since that comment and Now      
          $hrsSinceComment = calcHrsSinceLastComment($createdOn, $nowDate);
          wdebug("twp_poll", "> Hrs Since Resp. Comment Date = " . $hrsSinceComment);
        } else {
          wdebug("twp_poll", "> NO RECENT RESPONSIBLE COMMENT");
        }
        
        // 5) Get most recent Reminder
        $lastReminderComment = $task->getMostRecentStatusComment();
        if ($lastReminderComment != null) {
          $lastReminderCreatedOn = convertDateTimeToLocal($lastReminderComment->datetime_DT, 'UTC');
          wdebug("twp_poll", "> Most Recent Status Comment Date = " . $lastReminderCreatedOn->format("Y-m-d H:i:s"));

          $hrsSinceLastReminder = calcHrsBetweenDates($lastReminderCreatedOn, $nowDate);
          wdebug("twp_poll", "> Hrs Since Status Comment Date = " . $hrsSinceLastReminder);
        } else {
          wdebug("twp_poll", "> NO RECENT STATUS COMMENT");
        }
        
        // 6) If Task Started AND No comment at all OR If HrsSinceComment >= MinCommentPeriod - Create Status
        //TODO Add a check for how MANY Status Requests we have made that have gone unanswered.
        // Perhaps instead of just checking "do we have a recent reminder" check
        // how MANY recent reminders we have...if the Resp User has missed the last
        // 2 reminders, perhaps we need to send more?
        // 
        //TODO Add code to check the RMTicket->StartDate. If the date is BEFORE the StartDate
        // Don't create Reminder Notifications.    
        $taskStarted = $task->getStarted($nowDate);
        $noRecentReminder = ($lastReminderComment == null || $hrsSinceLastReminder > 1);
        $noRecentComment = ($comment == null || $hrsSinceComment >= $minCommentPeriod);
$noRecentReminder = true; // for testing - force noRecentReminder
$noRecentComment = true; // for testing - force noRecentComment
        wdebug("twp_poll", "> taskStarted = " . $taskStarted);
        wdebug("twp_poll", "> noRecentReminder = " . $noRecentReminder);
        wdebug("twp_poll", "> noRecentComment = " . $noRecentComment);
        
        if ($taskStarted && $noRecentReminder && $noRecentComment) {
          // 6a) Create Status depending on Overdue status
          $dueDate = $task->getDueDate();
          if ($nowDate < $dueDate) {
            // NOT Overdue - Status Reminder
            wdebug("twp_poll", ">+ addReminderComment");
            $task->addReminderComment('HTML');

            $task->addStatusTag();
          } else {
            // OVERDUE - Overdue Notice
            wdebug("twp_poll", ">+ addOverdueComment");
            $task->addOverdueComment('HTML');

            $task->addStatusTag();
          }
        }
        wdebug("twp_poll:-------------------------", "");
  
}

function codeToMove() {
  $tasks = getAllTasks(0);
  foreach ($tasks as $task) {
    // 2) Calculate MinCommentPeriod
    $minCommentPeriod = $task->getMinCommentPeriod();
    wdebug("pollRMTickets", "> task->text = " . $task->text);
    
    //TODO SET THIS BELOW FOR TESTING
    //$minCommentPeriod = 3; // 3 hrs    
    wdebug("pollRMTickets", "> minCommentPeriod = " . $minCommentPeriod);
    
    // 3) Get the most recent comment
    $comment = $task->getMostRecentResponsibleComment();
    if ($comment != null) {
      $createdOn = convertDateTimeToLocal($comment->created_on, "UTC");
      wdebug("pollRMTickets", "> Most Recent Resp. Comment Date = " . $createdOn->format("Y-m-d H:i:s"));
      
      // 4) Calculate # of Hrs since that comment and Now      
      $hrsSinceComment = calcHrsSinceLastComment($createdOn, $nowDate);
      wdebug("pollRMTickets", "> Hrs Since Resp. Comment Date = " . $hrsSinceComment);
    }    
    
    // 5) Get most recent Reminder
    $lastReminderComment = $task->getMostRecentStatusComment();
    if ($lastReminderComment != null) {
      $lastReminderCreatedOn = convertDateTimeToLocal($lastReminderComment->created_on, 'UTC');
      wdebug("pollRMTickets", "> Most Recent Rem. Comment Date = " . $lastReminderCreatedOn->format("Y-m-d H:i:s"));
      
      $hrsSinceLastReminder = calcHrsBetweenDates($lastReminderCreatedOn, $newerDate);
      wdebug("pollRMTickets", "> Hrs Since Rem. Comment Date = " . $hrsSinceLastReminder);
    }
    
    // 6) If Task Started AND No comment at all OR If HrsSinceComment >= MinCommentPeriod - Create Status
    //TODO Add a check for how MANY Status Requests we have made that have gone unanswered.
    // Perhaps instead of just checking "do we have a recent reminder" check
    // how MANY recent reminders we have...if the Resp User has missed the last
    // 2 reminders, perhaps we need to send more?
    // 
    //TODO Add code to check the RMTicket->StartDate. If the date is BEFORE the StartDate
    // Don't create Reminder Notifications.    
    $taskStarted = $task->getStarted($nowDate);
    $noRecentReminder = ($lastReminderComment == null || $hrsSinceLastReminder > 1);
    $noRecentComment = ($comment == null || $hrsSinceComment >= $minCommentPeriod);
    wdebug("pollRMTickets", "> taskStarted = " . $taskStarted);
    wdebug("pollRMTickets", "> noRecentReminder = " . $noRecentReminder);
    wdebug("pollRMTickets", "> noRecentComment = " . $noRecentComment);
    //if ($comment == null || $hrsSinceComment >= $minCommentPeriod) {
    if ($taskStarted && $noRecentReminder && $noRecentComment) {
      // 6a) Create Status depending on Overdue status
      $dueDate = $task->due_on;
      if ($nowDate < $dueDate) {
        // NOT Overdue - Status Reminder
        wdebug("pollRMTickets", "> addReminderComment");
        $task->addReminderComment();
        
        //TODO I can't add this until I add code to REMOVE it later when a comment is added
        // I will have to add a rmticket_comment_update routine to handle that.
        // Could be done, but this would cause confusion if I added it now
        // NOTE: There is NO WAY to fire on Task Comments. It turns out, the comment_create ONLY
        //  fires on AppItem Comments Creations, but NOTHING fires on task Comment creation
        // The ONLY way I can think of to do this would be with "polling".
        // If I ran the poll routine every 5 minutes, I could REMOVE these
        // tags within 5 minutes of the Status being added...not great, but better than
        // nothing...
        //$task->addTaskLabels("NeedsStatus");
      } else {
        // OVERDUE - Overdue Notice
        wdebug("pollRMTickets", "> addOverdueComment");
        $task->addOverdueComment();
        
        //TODO I can't add this until I add code to REMOVE it later when a comment is added
        // I will have to add a rmticket_comment_update routine to handle that.
        // Could be done, but this would cause confusion if I added it now
        //$task->addTaskLabels(array("NeedsStatus", "Overdue"));
      }
    }
    
  } // foreach ($tasks...)
  
  
  
  
}
