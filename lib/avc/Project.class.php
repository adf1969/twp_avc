<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace avc;

use \avc\Factory as Avc;
use \TeamWorkPm\Factory as TeamWorkPm;
use \DateTime;


/**
 * Description of AvcTask
 *
 * @author fields
 */
class Project extends \TeamWorkPm\Project {
     
  public function getTasks($rfTagsOnly = true, $showCompleted = false, $params = []) {
    $taskObj = Avc::build('Task');
    if ($rfTagsOnly) { 
      $tagIds = getRFTagIds();
      $params['tag-ids'] = implode(',', $tagIds);
    }
    if ($showCompleted) {
      $params['includeCompletedTasks'] = true;
      $params['includeCompletedsubtasks'] = true;
    }
    // Need to fix the Task action since it defaults to 'tasks'
    $taskObj->action = "projects/" . $this->id . "/tasks";
    $tasks = $taskObj->getAll($params);
    return $tasks;
  }
}
