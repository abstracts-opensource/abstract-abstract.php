<?php

namespace X\Markup\Aml;

class AmlValidation {

  public static function tag($name) {
    $result = static::key($name);
    return $result;
  }

  public static function key($name) {
    $result = $name;
    return $result;
  }

  public static function attribute($value) {
    $result = $value;
    return $result;
  }

  public static function value($value) {
    $result = $value;
    return $result;
  }
}
