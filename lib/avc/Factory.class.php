<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace avc;
use \TeamWorkPm\Factory as TeamWorkPm;

/**
 * Description of Factory
 *
 * @author fields
 */

class Factory
{
    public static function build($class_name, $data = [], $headers = [])
    {
        // We are using our factory, so by default, set this so we use our ResponseObject
        \TeamWorkPm\Rest::$RESPONSE_CLASS = '\avc\ResponseObject';
        
        // Any special pre-processing of $class_name
        switch ($class_name) {
          case 'Comment':
            // Comment is NOT a class, it is a base-class (called Model)
            // We need to figure out what KIND of comment, we can get this from the 
            // $data['commentableType'] value
            $class_name = self::getCommentClass($data);
            break;          
        }
        
        $class_name = str_replace(['/', '.'], '\\', $class_name);
        $class_name = preg_replace_callback('/(\\\.)/',
                        function($matches) {
                            return strtoupper($matches[1]);
                        },
                        $class_name
                      );
        $class_name = ucfirst($class_name);
        if (strcasecmp($class_name, 'task\\list') === 0) {
            $class_name = 'Task_List';
        }
        $class_name = '\\' . __NAMESPACE__ . '\\' .  $class_name;
        if (is_object($data)) {
          $data = (array)$data;
        }
        if (is_array($data) && is_array($headers)) {
          return forward_static_call_array(
              [$class_name, 'getInstance'],
              array_merge(\TeamWorkPm\Auth::get(), array($data, $headers))
          );
        } else {
          return forward_static_call_array(
                [$class_name, 'getInstance'],
                array_merge(\TeamWorkPm\Auth::get())
          );          
        }

    }
    
    static function getCommentClass($data) {
      $type = $data->commentableType;
      switch ($type) {
        case 'todo_items':
          return 'Comment\Task';        
          
        case 'milestones':
          return 'Comment\Milestone';
          
        case 'notebooks':
          return 'Comment\Notebook';
          
        case 'links':
          return 'Comment\Link';
          
        case 'files':
          return 'Comment\File';
        default:
          // This should NEVER happen, but if we can't figure out what to return
          // just return the base Model class.
          //TODO Perhaps we should return Task?
          return 'Comment\Model';
      }
    }
    
// <editor-fold defaultstate="collapsed" desc="get<object> Helper Static Methods"> ------------------\\

  static public function getAllTags() {
    $tagObj = TeamWorkPm::build('tag');   // Use TeamWorkPm Factory
    $tags = $tagObj->getAll();
    return $tags;
  }
  
  static public function getTask($taskId) {
    $taskObj = self::build('Task');
    $avcTaskResp = $taskObj->get($taskId);
    return $avcTaskResp->getObj();
  }
  
  static public function getTaskList($taskListId) {
    $taskListObj = self::build('Task_List');
    $avcTaskListResp = $taskListObj->get($taskListId);
    return $avcTaskListResp->getObj();
  }
  
  static public function getMilestone($milestoneId) {
    $milestoneObj = self::build('Milestone');
    $avcMilestoneResp = $milestoneObj->get($milestoneId);
    return $avcMilestoneResp->getObj();
  }  
  
  static public function getProjects($catName, $status = 'ACTIVE', $filter = []) {
    /* @var $projObj \avc\Project */
    if ($catId = getProjectCategoryId($catName)) {
      $filter['status'] = $status;
      $projObj = self::build('Project');
      $projects = $projObj->getByCategory($catId, $filter);
      return $projects;
    }
  }
  
  /*
   * $resource = projects, tasklists, tasks, milestones, timelogs, files, users, companys, links
   * $id = id# of the resource
   * $tags = array(
   *  "tags": {
   *    "content": "tag1, tag2, tag3"
   *  }
   * )
   * $replaceExistingTags: Replace any existing tags with the tags sent in content
   * $removeProvidedTags:  Don't add tags, just remove any tags sent in content
   */
  static public function updateTagsOnResource($resource, $id, $tags = [], 
      $replaceExistingTags = false, 
      $removeProvidedTags = false) {
    /* @var $newTag \TeamWorkPm\Tag */
    $action = "{$resource}/{$id}/tags";
    $newTag = TeamWorkPm::build('tag');
    $tags['action'] = $action;
    //$newTag->action = $action;

    if ($replaceExistingTags) {
      $tags['tags']['replaceExistingTags'] = true;
    }
    if ($removeProvidedTags) {
      $tags['tags']['removeProvidedTags'] = true;
    }
    
    $resp = $newTag->save($tags);
    return $resp;
  }
  
  
// </editor-fold> get<object> Helper Static Methods -------------------------------------------------\\

}
