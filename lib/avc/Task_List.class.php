<?php

namespace avc;
use \avc\Factory as Avc;
use \TeamWorkPm\Factory as TeamWorkPm;
use \DateTime;


/**
 * Description of Avc\Task_List
 *
 * @author fields
 */
class Task_List extends \TeamWorkPm\Task_List {
  
  public function getMilestone() {
    // Milestone is stored in:
    // $this->milestoneId = id    
    //TODO Add singleton loading to Milestone
    if ($milestoneId = $this->milestoneId) {
      //wdebug("Task_List.getMilestone milestoneId = ", $milestoneId);
      $avcMilestone = Avc::getMilestone($milestoneId);
      return $avcMilestone;
    } else {
      return null;
    }    
  }
  
}
