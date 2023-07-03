<?php

namespace X\Markup\Html;

use \X\Annotations\Name;

use DOMDocument;
use Exception;

class HtmlValidation {

  private static $embeddings = [
    'script',
    'style'
  ];

  public static function tag($name = null, $validate = true) {
    $result = static::key($name);
    // Catch errors without terminates
    if ($validate && !in_array($result, static::$embeddings)) {

      // Clear error globally
      libxml_clear_errors();

      // Disable error validation temporarily
      libxml_use_internal_errors(true);

      // Use DOM to create document
      $doc = new DOMDocument();
      try {
        $doc->loadHTML('<' . $name . ' />');
      } catch (Exception $e) {
        $result = false;
      }
      $errors = libxml_get_errors();
      if (!empty($errors)) {
        $result = false;
      }

      // Re-enable error validation
      libxml_use_internal_errors(false);

      if ($name === Name::$key) {
        $result = false;
      }
    }
    return $result;
  }

  public static function key($name) {
    $result = $name;
    $tag = str_replace(Name::$key . '-', '', $name);
    if (in_array($tag, static::$embeddings)) {
      $result = $tag;
    }
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
