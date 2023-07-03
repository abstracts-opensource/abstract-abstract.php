<?php

namespace X\Model\Xml;

use \X\Annotations\Name;

use \X\Model\Export;

use DOMDocument;
use SimpleXMLElement;

class XmlModel {

  public $model;

  public function __construct(
    string $data = null,
    ?bool $associative = null
  ) {

    // Initiate structure
    if (empty($data)) {
      $data = '<' . Name::$key . '/>';
    }
    
    // Create element from data
    $element = new SimpleXMLElement($data);
    
    // Convert element object to model
    $this->model = json_decode(
      json_encode(
        [$element->getName() => $element]
      ), 
      $associative
    );
    
    // Encapsulate root with "abstract"
    if (array_key_first((array)$this->model) !== Name::$key) {
      $this->model = (object)[Name::$key => $this->model];
      if ($associative) {
        $this->model = (array)$this->model;
      }
    }
    
    return $this->model;
  }

  public function asXML(?bool $formatOutput = true) {

    $markup = $this->markup('xml');

    $result = $markup;
    if ($formatOutput) {
      // Format XML
      $element = new SimpleXMLElement($markup);
      $doc = new DOMDocument('1.0');
      $doc->preserveWhiteSpace = false;
      $doc->formatOutput = true;
      $doc->loadXML($element->asXML());
      $result = $doc->saveXML();
    }

    return $result;
  }

  private function markup($type = 'xml') {

    // Use global model
    $model = $this->model;

    // Normalize model to array
    if (!empty($model) && is_object($model)) {
      $model = (array)$model;
    }

    $markup = Export::asMarkup($type, $model);

    return $markup;
  }

  // Manipulate class to return as object/array
  public function __debugInfo() {
    return (array)$this->model;
  }
}
