<?php

namespace avc;
use \avc\Factory as Avc;
use \TeamWorkPm\Factory as TeamWorkPm;
use \DateTime;


/**
 * Description of Avc\Task
 *
 * @author fields
 */
class Task extends \TeamWorkPm\Task {
  static public $statusTagPrefix = "NeedsStatus";
  static public $rfTagPrefix = "RF";
  static public $rfNone = -1;
  
  public function getComments($pageSize = 100) {
    $taskId = $this->id;
    $avcComment = Avc::build('Comment\Task');    
    $comments = $avcComment->getRecent($taskId, $pageSize);
    return $comments;
  }
  
  // Gets only those comments made by the Responsible user
  public function getResponsibleComments() {    
    
    // get ALL the comments
    $taskId = $this->id;
    $commResp = $this->getComments(100);   
    $comments = $commResp->toArray();
    //wdebug("getResponsibleComments ALL: ", $comments);    
    $respIds = explode(",", $this->responsiblePartyId);
    wdebug("getResponsibleComments respPartyId: ", $this->responsiblePartyId);
    
    // Now remove ALL the ones that aren't made by the Task Responsible User(s)
    $i = 0;
    foreach ($comments as $com) {
      $comId = $com->id;
      $comCreatedById = $com->authorId;
      if (in_array($comCreatedById, $respIds)) {  
        // Keep this comment
      } else {
        // remove this comment
        unset($comments[$i]);
        //$comments->remove($comId);
      }
      $i++;
    }
    //wdebug("getResponsibleComments Resp Only: ", $comments);    
    return array_values($comments);
  }

/**
   * Return a PodioCollection of all comments by the passed $user_id
   * 
   * @param integer $user_id
   */
  public function getCommentsByUser($user_id, $pageSize = 100, $page = 0) {
    // get ALL the comments
    $commResp = $this->getComments($pageSize);
    $comments = $commResp->toArray();
    
    // Now remove ALL the ones that aren't made by User $user_id
    $i = 0;
    foreach ($comments as $com) {
      $comId = $com->id;
      $comCreatedById = $com->authorId;
      if ($comCreatedById == $user_id) {
        // Keep this comment
      } else {
        // remove this comment
        unset($comments[$i]);
        //$comments->remove($comId);
      }
      $i++;
    }
    return array_values($comments);
  }  

  
/**
   * Get the most recent comment made by the Responsible User
   * 
   * @return PodioComment
   */
  public function getMostRecentResponsibleComment() {
    /* @var $taskComments avc\Comment\Task */
    
    $taskComments = $this->getResponsibleComments();

    if (count($taskComments)) {
      $taskComment = $taskComments[count($taskComments)-1]; // get LAST Comment in list
      return $taskComment;
    }
    return null;    
  }

  public function getMostRecentStatusComment() {
    /* @var $taskCommentsObj avc\ResponseObject */
    $taskComments = $this->getCommentsByUser(ADF_USERID);
    // loop in REVERSE ORDER since the array is from oldest to newest by default
    // we want NEWEST first 
    //$taskComments = $taskCommentsObj->getData();    // Don't have to do this anymore, since we convert to array_values() in getCommentsByUser()
    foreach (array_reverse($taskComments) as $comment) {
      if (contains($comment->body, "Status requested")) {
        return $comment;
      }
    }
  }


/**
   * 
   * Returns the Minimum Comment Period in HOURS
   * based upon the DueDate of the RMTicket and the Priority
   * 
   * @param DateTime $nowDate Optional, defaults to the current DateTime
   * @return integer in hours
   */
  public function getMinCommentPeriod($nowDate = null, $forcePriority = null) {
    if ($nowDate == null) {
      $nowDate = new DateTime();     
    }
    $taskPriority = ($forcePriority) ? $forcePriority : $this->getTaskPriority();
    return $this->calcMinCommentPeriod($nowDate, $this->dueDate_DT, $taskPriority);
  }
  
  /**
   * 
   * Calculates the Minimum Comment Period in HOURS
   * based upon the DueDate of the RMTicket and the Priority
   * 
   * @param DateTime $nowDate Optional, default to the current DateTime
   * @param DateTime $dueOn the Due Date of the Task
   * @param integer $pri The Priority
   * @return integer in hours
   */
  public static function calcMinCommentPeriod($nowDate = null, $dueOn, $pri) {
    /* @var $nowDate DateTime */
    /* @var $dueOn DateTime */
    global $mcpConfig;
    if (count($mcpConfig) > 0) {
      return self::calcMinCommentPeriodFromConfig($nowDate, $dueOn, $pri);
    }
    wdebug("AvcPodioTask.calcMinCommentPeriod", "WARNING! mcpConfig does not exist. Check GetConfig.");
    
    $mcp = 24*5; // Default is 5 days
    if ($nowDate == null) {
      $nowDate = new DateTime();
    }
    
    // Overdue or Not overdue?
    if ($dueOn < $nowDate) {
      // Overdue
      switch ($pri) {
        case 1:
          $mcp = 24 * 3; // default is 1 status every 3 days
          break;
        case 2:
          $mcp = 24 * 1;  // 1 days
          break;
        case 3:
          $mcp = 6;       // Since we only poll from 8am - 5pm, this is effectively 3x/day
          break;
        case 4:
          $mcp = 3;       // Since we only poll from 8am - 5pm, this is effectively 3x/day
          break;
        case 5:
          $mcp = 3;       // Since we only poll from 8am - 5pm, this is effectively 3x/day
        default:
          break;
      }
    } else {
      // Not overdue
      $interval = $dueOn->diff($nowDate);
      $hrsTilDue = getTotalInterval($interval, "hours");
      switch ($pri) {
        case 1:
          $mcp = 24 * 14; // default is 1 status every 14 days
          break;
        case 2:
          $mcp = 24 * 3;  // 3 days
          break;
        case 3:
          $mcp = 24 * 2;  // 2 days
          break;
        case 4:
          $mcp = 24 * 1;  // 1 day
          break;
        case 5:
          $mcp = 3;       // Since we only poll from 8am - 5pm, this is effectively 3x/day
        default:
          break;
      }
      // If $hrsTilDue < 7 Days (24*7)
      
      
      // if $hrsTilDue < 3 Days (24*3)
      
      
    }
    return $mcp;           
  }

/**
 * Calculates the Min Comment Period in Hours using the $mcpConfig
 * variable set from the Google Sheet
 * 
 * @param DateTime $nowDate
 * @param DateTime $dueOn
 * @param integer $pri
 */  
  public static function calcMinCommentPeriodFromConfig($nowDate = null, $dueOn, $pri) {
    global $mcpConfig;
    /* @var $nowDate DateTime */
    /* @var $dueOn DateTime */
    
    if ($nowDate == null) {
      $nowDate = new DateTime();
    }
    if ($pri > max(array_keys($mcpConfig))) {
      $pri = max(array_keys($mcpConfig));
    }
    if ($pri < min(array_keys($mcpConfig))) {
      $pri = min(array_keys($mcpConfig));
    }
    
    if ($dueOn < $nowDate) {
      // Overdue
      $mcp = $mcpConfig[$pri][0];      
      return $mcp;
    } else {
      // Not Overdue
      $interval = $dueOn->diff($nowDate);
      $hrsTilDue = getTotalInterval($interval, "hours");
      $daysTilDue = ($hrsTilDue / 24);
      $mcp = getHighestKey_ReturnValue($mcpConfig[$pri], $daysTilDue);
      return $mcp;
    }
    
  }  
  
// <editor-fold defaultstate="collapsed" desc="Date Methods"> ------------------\\

  public function getStartDate() {
    if ($this->startDate != null) {
      return $this->startDate_DT;
    }
    // If we don't have a start date, return the creation date
    return $this->createdOn_DT;
  }

  public function getStarted($nowDate = null) {    
    if ($nowDate == null) {
      $nowDate = new DateTime();
    }        
    $startDate = $this->getStartDate();
    //wdebug("Task.startDate = ", $startDate);
    // Task is Started if StartDate BEFORE NowDate
    return ($startDate <= $nowDate);
  }
  
  public function getDueDate() {
    if ($this->dueDate != null) {
      return $this->dueDate_DT;
    }
    //TODO Need to do something besides return NULL if there is no Due Date!
    return null;
    
  }
  
// </editor-fold> Date Methods -------------------------------------------------\\

  public function getMilestone() {
    /* @var $avcTaskList \avc\Task_List */
    // To get the Milestone (if one exists) we need to get the TaskList, then get
    // the Milestone from that
    //TODO Add singleton loading to Milestone
    if ($avcTaskList = $this->getTaskList()) {
      //wdebug("Tasks.getMilestone avcTaskList = ", $avcTaskList);
      $avcMilestone = $avcTaskList->getMilestone();      
      return $avcMilestone;
    } else {
      return null;
    }    
  }
  
  public function getTaskList() {
    // Task List is stored in:
    // $this->todoListId = id
    // $this->todoListName = name
    //TODO Add singleton loading to TaskList
    if ($taskListId = $this->todoListId) {
      //wdebug("Tasks.getTaskList todoListId = ", $taskListId);
      $avcTaskList = Avc::getTaskList($taskListId);
      return $avcTaskList;
    } else {
      return null;
    }    
  }
  
  public function getTaskPriority() {
    /* @var $avcMilestone \avc\Milestone */
    
    if ($this->taskPriority == null) {      
      $rfLevel = self::$rfNone; // same as NONE
      
      // Check THIS Tasks Tags
      if ($tags = $this->tags) {
        //wdebug("Tasks.getStatusTagLevel. Tags: ", $tags);
        foreach ($tags as $tag) {
          $tagName = $tag->name;
          if (beginsWith($tagName, self::$rfTagPrefix)) {
            $newLevel = intval(substr($tagName, strlen(self::$rfTagPrefix)));
            $rfLevel = ($newLevel > $rfLevel) ? $newLevel : $rfLevel;
          }
        }      
      } else {
        // For some reason, object didn't load tags...this shouldn't ever happen
        //TODO Load the tags? And start over?
        //If we do fix this, best way would be to add a getTag() that either
        // gets the ->tags or loads them, then sets them.
      }
      
      // Check the Tags of Parent Tasks (if it has one)
      if ($rfLevel == self::$rfNone) {
        if ($parentTaskAO = $this->parentTask) {
          if ($parentTaskId = $parentTaskAO->id) {
            wdebug("Task.getTaskPriority. rfLevel == None. Check Parent Priority. id = ", $parentTaskId);
            $parentTask = Avc::getTask($parentTaskAO->id);
            $parentTaskPriority = $parentTask->getTaskPriority();
            $rfLevel = $parentTaskPriority;
            wdebug("Task.getTaskPriority. Parent Priority = ", $parentTaskPriority);
          }
        }
      }
      
      // Check the Tags of Milestone
      wdebug("Task.getTaskPriority. rfLevel: ", $rfLevel);
      if ($rfLevel == self::$rfNone) {
        wdebug("Task.getTaskPriority. rfLevel == None. Check Milestone Priority ", "");
        if ($avcMilestone = $this->getMilestone()) {
          $milestonePriority = $avcMilestone->getMilestonePriority();
          wdebug("Task.getTaskPriority. Milestone Priority =", $milestonePriority);
          $rfLevel = $milestonePriority;          
        }        
      }
      
      $this->taskPriority = $rfLevel;
    }
    return $this->taskPriority;    
  }

  public function addComment($commentText, $contentType = 'TEXT') {
    //wdebug("Task.addComment", $commentText);
    wdebug("Task.addComment", substr($commentText, 0, 15));
    
    // $this->responsiblePartyId = "154123,153910"
    // $this->commentFollowerIds = "153910,154123,156830"
    $data = array(
      'resource_id' => $this->id,
      'body' => $commentText,      
      //'notify' => $this->commentFollowerIds,
      'notify' => 'all',
      'isprivate' => false,
      'content-type' => 'TEXT',
      //'fireWebhook' => false    // This doens't work
    );
    switch (strtoupper($contentType)) {
      case 'HTML':
        // Add HTML to commentText
        $commentText = 
          "<div class='fr-view'>" . $commentText . "</div>";
        $data['content-type'] = 'html';
        break;
      default:
        $data['content-type'] = 'TEXT';
        break;      
    }
    
    $newComment = TeamWorkPm::build('comment\task');
    $resp = $newComment->save($data);
    wdebug("Task.addComment resp", $resp);
  }
  
  /**
   * Creates a Status Comment in the form:
   * Status requested for: <loc>: <RMTicket Title> : <Task Title> : Due on <Due Date>
   */
  public function addReminderComment($contentType = 'text') {
    
    // Due Date Format DDD m/d/yy hh:ss AM, eg: Tue, 11/28/17 3:00 PM
    $localDueOn = null;
    if ($dueDate = $this->getDueDate()) {
      $localDueOn = convertDateTimeToLocal($dueDate);
    }
    switch (strtoupper($contentType)) {
      case 'HTML':
        $commentText = "<b>Status requested for:</b> <br>";
        $commentText .= $this->content . ": <br>";
        $commentText .= (!is_null($localDueOn)) ? 
            "Due on: " . ($localDueOn->format("D, m/d/y")) :
            "";
        break;
      
      default:
        $commentText = "**Status requested for:** \n";
        $commentText .= $this->content . ": \n";
        $commentText .= (!is_null($localDueOn)) ? 
            "Due on: " . ($localDueOn->format("D, m/d/y")) :
            "";
        break;      
    }
    
    $this->addComment($commentText, $contentType);  
  }
  
  /**
   * Creates a Status Comment in the form:
   * Status requested for: <loc>: <RMTicket Title> : <Task Title> : Due on <Due Date>
   */
  public function addOverdueComment($contentType = 'text') {
    
    // Due Date Format DDD m/d/yy hh:ss AM, eg: Tue, 11/28/17 3:00 PM
    if ($dueDate = $this->getDueDate()) {
      $localDueOn = convertDateTimeToLocal($dueDate);
      switch (strtoupper($contentType)) {
        case 'HTML':
          $commentText = "<b>!OVERDUE TASK!</b>\n";         
          $commentText .= "<b>Status requested for:</b> <br>";
          $commentText .= $this->content . ": <br>";
          $commentText .= "Due on: " . ($localDueOn->format("D, m/d/y"));        
          break;

        default:
          $commentText = "**!OVERDUE TASK!**\n";        
          $commentText .= "**Status requested for:** \n";
          $commentText .= $this->content . ": \n";
          $commentText .= "Due on: " . ($localDueOn->format("D, m/d/y"));        
          break;      
      }

      $this->addComment($commentText, $contentType);        
    }
  }  
  
  public function addTag($tagName) {
    $resource = 'tasks';
    $id = $this->id;
    $tags = array(
      "tags" => array(
        "content" => $tagName,
      )
    );
    wdebug("addTag id = " . $this->id . "  tagName = ", $tagName);
    $resp = Avc::updateTagsOnResource($resource, $id, $tags);
    return $resp;
  }
  
  public function removeTag($tagName) {
    $tagsToRemove = [];
    if ($tags = $this->tags) {
      //wdebug("Tasks.removeTag. Tags: ", $tags);      
      if (beginsWith($tagName, "/") && isRegularExpression($tagName)) {
        $tagNamePattern = $tagName;
      }            
      foreach ($tags as $tag) {
        $existingTagName = $tag->name;
        if ($tagNamePattern) {
          // Test with RegEx
          if (preg_match($tagNamePattern, $existingTagName)) {            
            wdebug("Tasks.removeTag. RegEx Remove: ", $existingTagName);
            $tagsToRemove[] = $existingTagName;
          }          
        } else {
          // Test with ==
          if ($tagName == $existingTagName) {
            wdebug("Tasks.removeTag. == Remove: ", $existingTagName);
            $tagsToRemove[] = $existingTagName;
          }
        }
      }
    } else {
      // For some reason, object didn't load tags...this shouldn't ever happen
      //TODO Load the tags? And start over?
    }
    
    if ($tagsToRemove) {
      $resource = 'tasks';
      $id = $this->id;
      $tagNames = implode(",", $tagsToRemove);
      $tags = array(
        "tags" => array(
          "content" => $tagNames,
        )
      );
      $resp = Avc::updateTagsOnResource($resource, $id, $tags, false, true);  
    }
  }
  
  public function getStatusTagLevel() {
    $statusLevel = 0; // same as NONE
    if ($tags = $this->tags) {
      //wdebug("Tasks.getStatusTagLevel. Tags: ", $tags);
      foreach ($tags as $tag) {
        $tagName = $tag->name;
        if (beginsWith($tagName, self::$statusTagPrefix)) {
          $newLevel = intval(substr($tagName, strlen(self::$statusTagPrefix)));
          $statusLevel = ($newLevel > $statusLevel) ? $newLevel : $statusLevel;
        }
      }      
    } else {
      // For some reason, object didn't load tags...this shouldn't ever happen
      //TODO Load the tags? And start over?
      //If we do fix this, best way would be to add a getTag() that either
      // gets the ->tags or loads them, then sets them.
    }
    wdebug("Tasks.getStatusTagLevel. statusLevel: ", $statusLevel);
    return $statusLevel;
  }
  
  /*
   * Either Adds a Level 1 tag or upgrades it to the next level
   */
  public function addStatusTag() {
    $currentStatusLevel = $this->getStatusTagLevel();
    $newStatusLevel = $currentStatusLevel + 1;
    if ($newStatusLevel <= 0) { $newStatusLevel = 1; }
    if ($newStatusLevel > 3) { $newStatusLevel = 3; }
    wdebug("addStatusTag id = " . $this->id . 
        "  currentStatusLevel = " . $currentStatusLevel .
        "  newStatusLevel = " . $newStatusLevel, "");

    // Only add/remove tags if we NEED to
    if ($currentStatusLevel != $newStatusLevel) {
      // Remove existing Status Lavels - if any
      $this->removeStatusTag();

      // We MUST sleep a bit here. If we don't, the Remove might execute AFTER the
      // ADD, which means the Tag will NOT get added correctly
      // 1/2 a sec seems to work fine. Adjust to greater if it stops working.
      usleep(500000); // sleep for 1/2 sec 

      // Add new one
      $newStatusTag = self::$statusTagPrefix . $newStatusLevel;
      $this->addTag($newStatusTag);    
    }
  }
  
  public function removeStatusTag() {
    wdebug("removeStatusTag id = " . $this->id, "");
    $this->removeTag("/" . self::$statusTagPrefix . "/");
  }
  
  
// <editor-fold defaultstate="collapsed" desc="Polling"> ------------------\\

  public function processPolling($nowDate) {
    if ($nowDate == null) {
      $nowDate = new DateTime();     
    }
    wdebug("Task.processPolling: Start -------------------------", "");
    
    // 2) Calculate MinCommentPeriod        
    $minCommentPeriod = $this->getMinCommentPeriod();

    //TODO SET THIS BELOW FOR TESTING
    //$minCommentPeriod = 3; // 3 hrs    
    wdebug("Task.processPolling", "> minCommentPeriod = " . $minCommentPeriod);

    // 3) Get the most recent comment
    $comment = $this->getMostRecentResponsibleComment();

    if ($comment != null) {
      $createdOn = convertDateTimeToLocal($comment->datetime_DT, "UTC");
      wdebug("Task.processPolling", "> Most Recent Resp. Comment Date = " . $createdOn->format("Y-m-d H:i:s"));

      // 4) Calculate # of Hrs since that comment and Now      
      $hrsSinceComment = calcHrsSinceLastComment($createdOn, $nowDate);
      wdebug("Task.processPolling", "> Hrs Since Resp. Comment Date = " . $hrsSinceComment);
    } else {
      wdebug("Task.processPolling", "> NO RECENT RESPONSIBLE COMMENT");
    }

    // 5) Get most recent Reminder
    $lastReminderComment = $this->getMostRecentStatusComment();
    if ($lastReminderComment != null) {
      $lastReminderCreatedOn = convertDateTimeToLocal($lastReminderComment->datetime_DT, 'UTC');
      wdebug("Task.processPolling", "> Most Recent Status Comment Date = " . $lastReminderCreatedOn->format("Y-m-d H:i:s"));

      $hrsSinceLastReminder = calcHrsBetweenDates($lastReminderCreatedOn, $nowDate);
      wdebug("Task.processPolling", "> Hrs Since Status Comment Date = " . $hrsSinceLastReminder);
    } else {
      wdebug("Task.processPolling", "> NO RECENT STATUS COMMENT");
    }

    // 6) If Task Started AND No comment at all OR If HrsSinceComment >= MinCommentPeriod - Create Status
    //TODO Add a check for how MANY Status Requests we have made that have gone unanswered.
    // Perhaps instead of just checking "do we have a recent reminder" check
    // how MANY recent reminders we have...if the Resp User has missed the last
    // 2 reminders, perhaps we need to send more?
    // 
    //TODO Add code to check the RMTicket->StartDate. If the date is BEFORE the StartDate
    // Don't create Reminder Notifications.    
    $taskStarted = $this->getStarted($nowDate);
    $noRecentReminder = ($lastReminderComment == null || $hrsSinceLastReminder > 1);
    $noRecentComment = ($comment == null || $hrsSinceComment >= $minCommentPeriod);
//$noRecentReminder = true; // for testing - force noRecentReminder
//$noRecentComment = true; // for testing - force noRecentComment
    wdebug("Task.processPolling", "> taskStarted = " . $taskStarted);
    wdebug("Task.processPolling", "> noRecentReminder = " . $noRecentReminder);
    wdebug("Task.processPolling", "> noRecentComment = " . $noRecentComment);

    if ($taskStarted && $noRecentReminder && $noRecentComment) {
      // 6a) Create Status depending on Overdue status
      $dueDate = $this->getDueDate();
      if (is_null($dueDate) || $nowDate < $dueDate) {
        // NOT Overdue - Status Reminder
        wdebug("Task.processPolling", ">+ addReminderComment");
        $this->addReminderComment('HTML');

        $this->addStatusTag();
      } else {
        // OVERDUE - Overdue Notice
        wdebug("Task.processPolling", ">+ addOverdueComment");
        $this->addOverdueComment('HTML');

        $this->addStatusTag();
      }
    }
    wdebug("Task.processPolling: End -------------------------", "");
    
  }
  
// </editor-fold> Polling -------------------------------------------------\\

  
// <editor-fold defaultstate="collapsed" desc="Webhook Processing / Events"> ------------------\\

  public function onCommentCreated($wh_data, $comObj) {
    wdebug("Tasks.onCommentCreated Task: ", $this->content);
    //wdebug("Tasks.onCommentCreated Comment Body:", substr($comObj->body, 0, 15));
    
    // Was comment made by a Responsible User?
    $respIds = explode(",", $this->responsiblePartyId);   
    $comCreatedById = $comObj->userId;
    if (in_array($comCreatedById, $respIds)) {
      // Yes, Remove any Status Tags
      wdebug("Tasks.onCommentCreated removeStatusTags: ", $comCreatedById);
      $this->removeStatusTag();
    }
  }

// </editor-fold> Webhook Processing / Events -------------------------------------------------\\

} // Task
