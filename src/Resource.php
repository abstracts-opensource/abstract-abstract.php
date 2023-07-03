<?php

namespace X;

use \X\Interfacer;
use \X\Name;

use \X\Execution;

class Resource {

  public $content;
  public $interface;

  public function __construct($content = null) {

    $this->content = $content;

  }

  public static function get($path) {
    $content = file_get_contents($path);
    return $content;
  }

  public static function interface($aml) {

    if (empty($aml)) {
      $aml = '';
    }
    
  }

}
