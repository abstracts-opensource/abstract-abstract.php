<?php

namespace X\Model;

use \X\Annotations\Name;

use DOMDocument;
use Exception;

class Import {

  public static function asElement($data = null) {

    $element = null;

    if (!empty($data)) {

      // Clear error globally
      libxml_clear_errors();

      // Disable error validation temporarily
      libxml_use_internal_errors(true);

      // Use DOM to create document
      $doc = new DOMDocument();
      try {
        $doc->loadHTML($data, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
      } catch (Exception $e) {
      }

      // Re-enable error validation
      libxml_use_internal_errors(false);

      // Import dom to xml element object
      $element = simplexml_import_dom($doc);
    }

    return $element;
  }

  public static function attributes($value) {
    $result = [];
    if (!empty($value)) {
      $array = (array)$value;
      if (array_key_exists('@attributes', $array)) {
        if (!empty($array['@attributes'])) {
          $result = (array) $array['@attributes'];
        }
      }
      if (array_key_exists(Name::$key . '-attributes', $array)) {
        if (!empty($array[Name::$key . '-attributes'])) {
          $result = array_merge($result, (array) $array[Name::$key . '-attributes']);
        }
      }
    }
    return (array)$result;
  }
}
