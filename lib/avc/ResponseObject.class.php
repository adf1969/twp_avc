<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace avc;
use \avc\Factory as Avc;
use \TeamWorkPm\Helper\Str;
use \ArrayObject;
use \DateTime;

/**
 * Description of ResponseObject
 *
 * @author fields
 */
class ResponseObject extends \TeamWorkPm\Response\Model {

  private $jsonData = null;
  
  public function parse($data, array $headers) {    
    
    // Now parse the $data into Class Objects
    $source = json_decode($data);
    $source = self::camelizeObject($source);
    if ($headers['Status'] === 201 || $headers['Status'] === 200) {
      switch ($headers['Method']) {
        case 'UPLOAD':
            return empty($source->pendingFile->ref) ? null :
                                (string) $source->pendingFile->ref;
        case 'POST':
            // print_r($headers);
            if (!empty($headers['id'])) {
                return (int) $headers['id'];
            } elseif (!empty($source->fileId)) {
                return (int) $source->fileId;
            }
            // no break
        case 'PUT':
        case 'DELETE':
             return true;
          
        case 'GET':
          // We are getting data in JSON format, check that it is valid using the JSON object
          $jsonResp = new \TeamWorkPm\Response\JSON();
          $jsonResp = $jsonResp->parse($data, $headers);
          $this->string = $jsonResp->string;
          $this->headers = $jsonResp->headers;
          $this->jsonData = $jsonResp->data;
          
          $dataObj = $this->parseObjects($source, $headers);
          if ($dataObj != null) {
            // We were able to parse the json into Objects
            $this->data = $dataObj;
          } else {
            // Unable to parse the json into Objects, just return the Json
            $this->data = $jsonResp->data;
          }
          break;
          
        default:
          // Just return the JSON data
          $this->data = $jsonResp->data;        
      }
    } elseif (!empty($source->MESSAGE)) {
      $errors = $source->MESSAGE;
    } else {
      $errors = null;
    }  
    
    return $this;
  }
    
  protected function parseObjects($data, $headers) {
    $processed = false;
    $retData = null;
    foreach ($data as $k => $d) {
      switch ($k) {
        case 'projects':  // Array of Projects
          // convert all sub-elements to array of avc\Project
          $retData = [];
          foreach ($d as $project) {
            $avcProject = Avc::build('Project', $project, $headers);
            $retData[] = $avcProject;
          }
          $processed = true;
          break;
          
        case 'project': // Project
          // convert sub-element to avc\Project
          $avcProject = Avc::build('Project', $d, $headers);
          $retData = $avcProject;
          $processed = true;          
          break;
        
        case 'comments':  // Comment
          // convert all sub-elements to array of avc\Comment          
          $retData = [];
          foreach ($d as $comment) {
            // NOTE: The exact "type" of the Comment will be determined
            // by the \avc\Factory class - see there for details.
            $avcComment = Avc::build('Comment', $comment, $headers);
            $retData[] = $avcComment;
          }
          $processed = true;
          break;
          
        case 'todoItem':  // Task
          // convert sub-element to avc\Task
          $avcTask = Avc::build('Task', $d, $headers);
          $retData = $avcTask;
          $processed = true;
          break;
        
        case 'todoItems': // Array of Tasks
          // convert sub-elements to array of avc\Task
          $retData = [];
          foreach ($d as $task) {
            $avcTask = Avc::build('Task', $task, $headers);
            $retData[] = $avcTask;
          }
          $processed = true;          
          break;

        case 'todoList': // Task_List Object - NOT the same as Array of Tasks
          // convert sub-element to avc\Task_List
          $avcTaskList = Avc::build('Task_List', $d, $headers);
          $retData = $avcTaskList;
          $processed = true;
          break;

        case 'milestone': // Milestone Object
          // convert sub-element to avc\Milestone
          $avcMilestone = Avc::build('Milestone', $d, $headers);
          $retData = $avcMilestone;
          $processed = true;
          break;
        
        default:
          // Attempt to "figure it out" from the value
          break;
      }
    }    
    return ($processed) ? $retData : null;
  }

  //TODO Add the ability to fill the camelizedObject with actual DateTime values with correct local TZ
  //
  // Date Type Fields: startDate, dueDateBase, dueDate, createdOn, lastChangedOn
  // How to make the value be a DateTime
  // $tz = new DateTimeZone('UTC');
  //  return DateTime::createFromFormat($this->date_format_for_property($name), $this->__attributes[$name], $tz);
  // I think best option is to ADD the same value as before, so for startDate we will have
  //  ->startDate (which will be a string) but we will also have
  //  ->startDate_DT (which will be a DateTime object in LOCAL tz)
  // Taken from \Response\JSON.php
  protected static function camelizeObject($source)  {      
      $destination = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);     
      if (is_null($source) || (is_array($source) && count($source) == 0 )) {
        return $destination;
      }
      try {
        foreach ($source as $key => $value) {
//          wdebug("camelizeObject. key = ", $key);
//          wdebug("camelizeObject. value = ", $value);
          if (ctype_upper($key)) {
            $key = strtolower($key);
          }
          $key = Str::camel($key);
          $destination->$key = is_scalar($value) ?
              $value : self::camelizeObject($value);
          list($objType, $objVal) = self::getSpecialValue($key, $value);
          switch ($objType) {
            case 'Date':
              $key_special = $key . "_DT";
              $destination->$key_special = $objVal;
              break;
            case 'DateTime':
              $key_special = $key . "_DT";
              $destination->$key_special = $objVal;

              break;
          }
        }
      } catch (Exception $exc) {        
        wdebug("** Exception: ", $exc);
        wdebug("** camelizeObject source = ", $source);
      }

      return $destination;
  }  
  protected static function getSpecialValue($key, $value) {
    // YYYY-MM-DDTHH:MM:SSZ
    if (is_scalar($value) && strlen($value) == 20) {
      $dt = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $value);
      if ($dt !== false) {
        return array('DateTime', $dt);
      }            
    }
    
    // YYYYMMDD
    if (is_scalar($value) && is_numeric($value) && strlen($value) == 8) {    
      $dt = DateTime::createFromFormat('Ymd\TH:i:s', $value . 'T00:00:00');
      if ($dt !== false) {
        return array('Date', $dt);
      }      
    }

  }
  
  protected function getContent()
  {
      $object = json_decode($this->string);

      return json_encode($object, JSON_PRETTY_PRINT);
  }  
  
  public function getData() {
    if (is_subclass_of($this->data, '\TeamWorkPm\Rest\Model')) {
      return $this->data;
    } else {
      return $this->toArray();
    }
  }
  
  public function getObj() {
    if (is_subclass_of($this->data, '\TeamWorkPm\Rest\Model')) {
      return $this->data;
    } else {
      return null;
    }    
  }
  
  public function getJsonData() {
    return $this->jsonData;
  }
  
  
}