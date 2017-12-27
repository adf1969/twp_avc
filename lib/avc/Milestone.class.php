<?php

namespace avc;
use \avc\Factory as Avc;
use \TeamWorkPm\Factory as TeamWorkPm;
use \DateTime;


/**
 * Description of Avc\Milestone
 *
 * @author fields
 */
class Milestone extends \TeamWorkPm\Milestone {
  //TODO Perhaps remove these and just use \avc\Task:: instead, or add a use \avc\Task as Task;
  static public $rfTagPrefix = "RF";
  static public $rfNone = -1;
  
  public function getMilestonePriority() {    
    if ($this->milestonePriority == null) {
      
      $rfLevel = self::$rfNone; // same as NONE
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
      wdebug("Milestone.getMilestonePriority. rfLevel: ", $rfLevel);            
      $this->milestonePriority = $rfLevel;
    }
    return $this->milestonePriority;    
  }
  
  public function getTagsHtml() {
    $tagHtml = '';
    if ($tags = $this->tags) {
      foreach ($tags as $tag) {
//        $tagName = $tag->name;
//        $tagColor = $tag->color;
//        $tagId = $tag->id;
//        $tagHtml .= "$tagName - $tagColor - $tagId";
        $tagHtml .= $this->buildTagHtml($tag);
      }
    }
    if ($tagHtml) {
      $tagHtml = 
          '<div class="tagHolder">' . 
            $tagHtml .
          '</div>';
    }
    return $tagHtml;
  }
  
  private function buildTagHtml($tag) {
    $tagName = $tag->name;
    $tagColor = $tag->color;
    $tagId = $tag->id;
    $tagHtml = '';
    $tagHtml .= '<span class="tag" style="cursor: auto; background-color: ' . $tagName . ';">';
    $tagHtml .=   '<div style="display: inline-block;">' . $tagName . '</div>';
    $tagHtml .= '</span>';
    
    return $tagHtml;
  }
  
}
