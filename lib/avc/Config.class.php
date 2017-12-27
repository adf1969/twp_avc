<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace avc;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

/**
 * Description of Config
 *
 * @author fields
 */
class Config {
  static public $log = [];
  static public function Log($channel = 'twp_avc') {
    if (!isset(self::$log[$channel])) {
      return self::initLog($channel);
    }
    return self::$log[$channel];
  }
  
  static public function initLog($channel = 'twp_avc') {
    if (!isset(self::$log[$channel])) {
      $streamHandler = new StreamHandler(__DIR__.'/../../logs/app.log', Logger::DEBUG);
      $firephpHandler = new FirePHPHandler();

      self::$log[$channel] = new Logger($channel);
      self::$log[$channel]->pushHandler($streamHandler);
      self::$log[$channel]->pushHandler($firephpHandler);
      self::$log[$channel]->info('Static Log Created.');      
    }
    return self::$log[$channel];    
  }
    
}
