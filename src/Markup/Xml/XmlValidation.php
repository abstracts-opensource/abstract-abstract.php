<?php

namespace X\Markup\Xml;

class XmlValidation {

  public static function tag($name = null) {
    $result = static::key($name);
    return $result;
  }

  public static function key($name) {
    $result = $name;
    return $result;
  }

  public static function attribute($value) {
    $result = $value;
    if (empty($value)) {
      $value = 'true';
    }
    return $result;
  }

  public static function value($value) {
    $result = $value;
    return $result;
  }
}
