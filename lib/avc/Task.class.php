<?php

namespace avc;
use \avc\Factory as Avc;
use \TeamWorkPm\Factory as TeamWorkPm;
use \DateTime;


/**
 * Description of AvcTask
 *
 * @author fields
 */
class Task extends \TeamWorkPm\Task {
    
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
    $comments = $this->getComments(100);    
    $respIds = explode(",", $this->responsiblePartyId);
    
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
    return $comments;
  }

/**
   * Return a PodioCollection of all comments by the passed $user_id
   * 
   * @param integer $user_id
   */
  public function getCommentsByUser($user_id, $pageSize = 100, $page = 0) {
    // get ALL the comments
    $comments = $this->getComments($pageSize);
    
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
    return $comments;
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
    $taskCommentsObj = $this->getCommentsByUser(ADF_USERID);
    // loop in REVERSE ORDER since the array is from oldest to newest by default
    // we want NEWEST first 
    $taskComments = $taskCommentsObj->getData();
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
  
  public function getTaskPriority() {
    //TODO Finish getTaskPriority - get this from the task Labels
    return 3; // If label is "RF3" it should return "3"
    
    if ($this->taskPriority == null) {
      $newPri = null;      
      // Prority is equal to highest value of "RF#" entry.
      foreach ($this->labels as $label) {
        // The only type of Labels we have so far are RF# entries
        // If we add any others, we can filter for those, but for now,
        // this is all we look for.
        // This allows *other* labels to be added, without causing issues.
        if (substr($label->text, 0, 2) == "RF") {
          $i = intval(substr($label->text, 2));
          if (($newPri == null) || ($i > $newPri)) {
            $newPri = $i;
          }
        }
      }
      $this->taskPriority = $newPri;
    }
    return $this->taskPriority;    
  }

  public function addComment($commentText, $contentType = 'TEXT') {
    wdebug("Task.addComment", $commentText);
    
    // $this->responsiblePartyId = "154123,153910"
    // $this->commentFollowerIds = "153910,154123,156830"
    $data = array(
      'resource_id' => $this->id,
      'body' => $commentText,      
      //'notify' => $this->commentFollowerIds,
      'notify' => 'all',
      'isprivate' => false,
      'content-type' => 'TEXT'
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
    $localDueOn = convertDateTimeToLocal($this->dueDate_DT);
    switch (strtoupper($contentType)) {
      case 'HTML':
        $commentText = "<b>Status requested for:</b> <br>";
        $commentText .= $this->content . ": <br>";
        $commentText .= "Due on: " . ($localDueOn->format("D, m/d/y"));        
        break;
      
      default:
        $commentText = "**Status requested for:** \n";
        $commentText .= $this->content . ": \n";
        $commentText .= "Due on: " . ($localDueOn->format("D, m/d/y"));        
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
    $localDueOn = convertDateTimeToLocal($this->dueDate_DT);
    switch (strtoupper($contentType)) {
      case 'HTML':
        $commentText = "<b>!OVERDUE TASK!</b>\n" . 
        $commentText .= "<b>Status requested for:</b> <br>";
        $commentText .= $this->content . ": <br>";
        $commentText .= "Due on: " . ($localDueOn->format("D, m/d/y"));        
        break;
      
      default:
        $commentText = "**!OVERDUE TASK!**\n" . 
        $commentText .= "**Status requested for:** \n";
        $commentText .= $this->content . ": \n";
        $commentText .= "Due on: " . ($localDueOn->format("D, m/d/y"));        
        break;      
    }
    
    $this->addComment($commentText, $contentType);  
  }  
  
  public function addTag($tagName) {
    $resource = 'tasks';
    $id = $this->id;
    $tags = array(
      "tags" => array(
        "content" => $tagName,
      )
    );
    $resp = Avc::updateTagsOnResource($resource, $id, $tags);
    return $resp;
  }
  
  public function removeTag($tagName) {
    if ($tags = $this->tags) {
      wdebug("Tasks.removeTag. Tags: ", $tags);
      $tagsToRemove = [];
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
    }
    
    //TODO Finish removeTag - need to add code to remove all Tags found above
    // Or, just remove them above...probably best to get a list, THEN remove
    // them with 1 call.
    if ($tagsToRemove) {
      $resource = 'tasks';
      $id = $this->id;
      $tagNames = implode(",", $tagNames);
      $tags = array(
        "tags" => array(
          "content" => $tagNames,
        )
      );
      $resp = Avc::updateTagsOnResource($resource, $id, $tags, false, true);  
    }
  }
  
} // Task
