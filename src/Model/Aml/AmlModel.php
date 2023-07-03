<?php

namespace X\Model\Aml;

use \X\Annotations\Name;

use \X\Model\Normalize;
use \X\Model\Import;
use \X\Model\Export;

use DOMDocument;
use SimpleXMLElement;
use Exception;

class AmlModel {

  public $model;

  public function __construct(
    string $data = null,
    ?bool $associative = null
  ) {

    // Initiate structure
    $data = '<' . Name::$key . '>' . (!empty($data) ? $data : '') . '</' . Name::$key . '>';

    // Encapsulate
    $normalize = new Normalize($data);
    $normalize->embeddings();
    $normalize->strings();
    $normalize->nodes();

    $data = $normalize->normalized;

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

  public function asAML() {
    $result = $this->markup('aml');
    return $result;
  }

  public function asHTML(?bool $formatOutput = true) {

    $markup = $this->markup('html');

    $result = $markup;
    if ($formatOutput) {

      // Clear error globally
      libxml_clear_errors();

      // Disable error validation temporarily
      libxml_use_internal_errors(true);

      // Use DOM to create document
      $doc = new DOMDocument();
      $doc->preserveWhiteSpace = false;
      $doc->formatOutput = true;
      try {
        $doc->loadHTML($markup);
      } catch (Exception $e) {
      }

      // Re-enable error validation
      libxml_use_internal_errors(false);

      $result = $doc->saveHTML();
    }

    return $result;
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

  private function markup($type = 'aml') {

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
