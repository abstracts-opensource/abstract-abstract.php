<?php

namespace X\Model;

use \X\Annotations\Name;

use DOMXPath;
use DOMDocument;
use DOMElement;
use Exception;

class Normalize {

  public $doc;
  public $xpath;
  public $normalized;

  public function __construct($data) {

    // Clear error globally
    libxml_clear_errors();

    // Disable error validation temporarily
    libxml_use_internal_errors(true);

    // Use DOM to create document
    $this->doc = new DOMDocument();
    try {
      $this->doc->loadHTML($data, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    } catch (Exception $e) {
    }

    // Re-enable error validation
    libxml_use_internal_errors(false);

    $this->xpath = new DOMXPath($this->doc);

    $this->normalized = $this->doc->saveHTML();
  }

  function strings() {

    $texts = $this->xpath->query('//text()');

    foreach ($texts as $text) {
      $textContent = trim($text->textContent);
      if (!empty($textContent)) {
        $element = $this->doc->createElement('text', $textContent);
        $text->parentNode->replaceChild($element, $text);
      }
    }

    $result = $this->doc->saveHTML();
    $this->normalized = $result;
    return $result;
  }

  function nodes($path = '') {

    $nodes = $this->xpath->query($path . 'node()[not(self::text())]');

    $staging = [];
    foreach ($nodes as $node) {
      if ($node instanceof DOMElement) {
        if ($node->tagName !== Name::$key && $node->hasChildNodes()) {
          $this->nodes($path . $node->tagName . '/');
        }
        array_push($staging, ['parent' => $node->parentNode, 'child' => $node]);
        $element = $this->doc->createElement(Name::$key, '');
        $element->appendChild($node->cloneNode(true));
        $node->parentNode->appendChild($element);
      }
    }
    foreach ($staging as $stage) {
      $stage['parent']->removeChild($stage['child']);
    }

    $result = $this->doc->saveHTML();
    $this->normalized = $result;
    return $result;
  }

  function embeddings() {

    $embeddings = [
      'script',
      'style'
    ];

    foreach ($embeddings as $embedding) {

      $scripts = $this->xpath->query('//' . $embedding);

      foreach ($scripts as $script) {
        $scriptContent = $script->nodeValue;
        if (!empty($scriptContent)) {
          $element = $this->doc->createElement(Name::$key . '-' . $embedding, $scriptContent);
          $attributes = $script->attributes;
          foreach ($attributes as $attribute) {
            $element->setAttribute($attribute->name, $attribute->value);
          }
          $script->parentNode->replaceChild($element, $script);
        }
      }

      $result = $this->doc->saveHTML();
    }

    $this->normalized = $result;
    return $result;
  }
}
