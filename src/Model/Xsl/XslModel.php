<?php

namespace X\Model\Xsl;

use \X\Model\Import;
use \X\Model\Export;

use DOMDocument;
use SimpleXMLElement;

class XslModel {

  public $model;

  public function __construct(
    string $data = null,
    ?bool $associative = null
  ) {

    // Create element from data
    $element = Import::asElement($data);

    // Convert element object to model
    $this->model = json_decode(
      json_encode(
        [$element->getName() => $element]
      ), 
      $associative
    );

    return $this->model;
  }

  public function asXSL(?bool $formatOutput = true) {

    $markup = $this->markup('xsl');

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

  private function markup($type = 'xsl') {

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
