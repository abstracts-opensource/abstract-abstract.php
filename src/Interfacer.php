<?php

namespace Ab;

use Exception;
use DOMXPath;
use DOMDocument;
use SimpleXMLElement;

class Interfacer {

  public static function resource($path) {
    $interface = file_get_contents($path);
    return $interface;
  }

  public static function read($interface, bool $associative = true, $asXML = true) {
    libxml_clear_errors();
    if (!empty($interface)) {
      libxml_use_internal_errors(true);
      $dom = new DOMDocument();
      $dom->loadHTML(static::clean(static::encapsulate($interface), $asXML));
      if (!$asXML) {
        $head = $dom->getElementsByTagName('head')->item(0);
        $body = $dom->getElementsByTagName('body')->item(0);
        $head->parentNode->removeChild($head);
        while ($body->childNodes->length > 0) {
          $child = $body->childNodes->item(0);
          $dom->documentElement->appendChild($child);
        }
      }
      if (!$associative) {
        return $dom;
      }
      $xml = simplexml_import_dom($dom);
      libxml_use_internal_errors(false);
      return $xml;
    } else {
      return null;
    }
  }

  public static function write($DOMDocument_or_SimpleXMLElement) {

    $object = $DOMDocument_or_SimpleXMLElement;

    $data = '';

    if ($object instanceof DOMDocument) {
      $data = $object->saveHTML();
    } elseif ($object instanceof SimpleXMLElement) {
      $data = $object->asXML();
    }

    return $data;
  }

  public static function dom($interface, bool $associative = true) {
    libxml_clear_errors();
    libxml_use_internal_errors(true);
    $data = '';
    if ($associative) {
      $data = new SimpleXMLElement(static::encapsulate($interface));
    } else {
      $data = new DOMDocument(static::encapsulate($interface));
    }
    libxml_use_internal_errors(false);
    return $data;
  }

  public static function is_valid($tag_name) {
    libxml_clear_errors();
    try {
      libxml_use_internal_errors(true);
      $dom = new DOMDocument();
      $dom->loadHTML('<' . $tag_name . '>');
      // Check for errors
      $errors = libxml_get_errors();
      if (!empty($errors)) {
        return false;
      }
      libxml_use_internal_errors(false);
    } catch (Exception $e) {
      return false;
    }
    return true;
  }

  private static function encapsulate($interface) {

    libxml_clear_errors();

    $dom = new DOMDocument();
    $dom->loadHTML($interface, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $xpath = new DOMXPath($dom);
    $textNodes = $xpath->query('//text()');

    foreach ($textNodes as $textNode) {
      $parent = $textNode->parentNode;
      $textContent = trim($textNode->textContent);

      if (!empty($textContent) && $textContent !== '{{' && $textContent !== '}}') {
        $tNode = $dom->createElement('encapsulated', $textContent);
        $parent->replaceChild($tNode, $textNode);
      }
    }

    return $dom->saveHTML();
  }

  private static function clean($strings, $asXML = true) {
    if ($asXML) {
      $strings = preg_replace('/>[\s\r\n\t]+</', '><', $strings);
    } else {
      $strings = preg_replace('/>[\s\r\n\t]+</', '><', $strings);
      $strings = preg_replace('/[\r\n\t]+/', '', $strings);
    }
    return $strings;
  }
}
